<?php

namespace App\Http\Controllers;

use App\Content\ContentImporter;
use App\Models\User;
use App\Site\NullPublishQueue;
use App\Site\PublishQueue;
use App\Site\SitePublisher;
use Closure;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Установка/обновление на хостинге без SSH: CI выгружает файлы по FTPS и вызывает этот адрес.
 * Шаги идемпотентны — повторный вызов безопасен. Пароли и токен в ответ не попадают.
 */
class DeployHookController extends Controller
{
    /** Внутренний адрес первого администратора, если почта не передана (вход — только по паролю). */
    public const DEFAULT_ADMIN_EMAIL = 'admin@matuzov.ru';

    /** @var list<array{step: string, ok: bool, message: string}> */
    private array $steps = [];

    /** @var list<string> строки, которые нельзя показывать в ответе */
    private array $secrets = [];

    public function __invoke(Request $request, string $token = ''): Response|JsonResponse
    {
        $this->steps = [];
        $expected = (string) config('site.deploy_token');
        if ($expected === '' || ! hash_equals($expected, $token)) {
            // Без страницы 404 сайта: до первой установки ей не из чего рендериться.
            return response('Not Found', 404);
        }
        $password = $request->input('admin_password');
        $this->secrets = array_values(array_filter([$token, is_string($password) ? $password : '']));

        $admin = Validator::make($request->only('admin_email', 'admin_password', 'reset_admin'), [
            'reset_admin' => ['nullable', 'boolean'],
            // Вход в админку только по паролю: почта необязательна (внутренний идентификатор).
            'admin_email' => ['nullable', 'email'],
            'admin_password' => ['nullable', 'required_with:admin_email', 'required_if_accepted:reset_admin', 'string', 'min:10'],
        ]);
        if ($admin->fails()) {
            return response()->json(['ok' => false, 'error' => 'Неверные admin_email/admin_password: '.implode(' ', array_keys($admin->errors()->messages()))], 422);
        }

        // Одна установка за раз: иначе вторая (например, повтор CI) уберёт файлы идущего импорта по его журналу.
        $lockFile = storage_path('framework/deploy.lock');
        is_dir(dirname($lockFile)) || @mkdir(dirname($lockFile), 0755, true);
        $lock = @fopen($lockFile, 'c');
        if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
            $lock && fclose($lock);

            return response()->json(['ok' => false, 'error' => 'Установка уже выполняется — повторите позже.'], 423);
        }

        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        ignore_user_abort(true); // обрыв соединения (таймаут прокси) не прерывает установку

        // Сайт публикуется явно в конце; импорт не должен ставить ещё одну публикацию после ответа.
        app()->instance(PublishQueue::class, new NullPublishQueue);
        try {
            $ok = $this->install($admin->validated());
            $publish = $ok ? $this->publish() : ['ok' => false, 'error' => 'Не выполнялась: установка прервана'];
            // Кеши — последними: config:cache поднимает в этом же процессе второе приложение и подменяет глобальное.
            $ok = $ok && $this->step('optimize', fn () => $this->optimize());
        } finally {
            app()->forgetInstance(PublishQueue::class);
            flock($lock, LOCK_UN);
            fclose($lock);
        }

        $ok = $ok && $publish['ok'];

        return response()->json(['ok' => $ok, 'steps' => $this->steps, 'publish' => $publish], $ok ? 200 : 500);
    }

    /** @param array{admin_email?: ?string, admin_password?: ?string} $admin */
    private function install(array $admin): bool
    {
        return $this->step('database', function () {
            $db = config('database.connections.'.config('database.default'));
            if (($db['driver'] ?? null) !== 'sqlite' || in_array($db['database'] ?? '', ['', ':memory:'], true) || is_file($db['database'])) {
                return 'есть';
            }
            if (! touch($db['database'])) {
                throw new \RuntimeException('Не удалось создать файл базы '.$db['database']);
            }

            return 'создан файл базы';
        })
            && $this->step('migrate', fn () => $this->artisan('migrate', ['--force' => true]))
            && $this->step('import', fn () => app(ContentImporter::class)->importIfEmpty() ? 'наполнение перенесено в базу' : 'в базе уже есть наполнение')
            && $this->step('admin', fn () => $this->createAdmin($admin))
            && $this->step('filament:assets', fn () => $this->artisan('filament:assets'));
    }

    /** @param array{admin_email?: ?string, admin_password?: ?string} $admin */
    private function createAdmin(array $admin): string
    {
        if (User::query()->exists()) {
            // Сброс — только по явному флагу (галочка при ручном запуске выкладки): пароль первого администратора.
            if (! empty($admin['reset_admin']) && ! empty($admin['admin_password'])) {
                User::query()->orderBy('id')->firstOrFail()->update(['password' => $admin['admin_password']]);

                return 'пароль администратора сброшен';
            }

            return 'пользователи уже есть — не создаём';
        }
        if (empty($admin['admin_password'])) {
            return 'пользователей нет, а admin_password не передан — администратор не создан';
        }
        $email = ($admin['admin_email'] ?? null) ?: self::DEFAULT_ADMIN_EMAIL;
        User::create(['name' => 'Администратор', 'email' => $email, 'password' => $admin['admin_password']]);

        return 'создан администратор '.$email;
    }

    /** @return array{ok: bool, error: ?string} */
    private function publish(): array
    {
        try {
            $result = app(SitePublisher::class)->publishNow();
        } catch (Throwable $e) {
            report($e);

            return ['ok' => false, 'error' => $this->redact($e->getMessage())];
        }
        if ($result === null) {
            // Идёт другая публикация: её держатель повторит её ещё раз (флаг поднят) — уже с новым кодом.
            return ['ok' => true, 'error' => null, 'queued' => true];
        }

        return ['ok' => $result->ok, 'error' => $result->error === null ? null : $this->redact($result->error)];
    }

    private function step(string $name, Closure $run): bool
    {
        try {
            $this->steps[] = ['step' => $name, 'ok' => true, 'message' => $this->redact((string) $run())];

            return true;
        } catch (Throwable $e) {
            report($e);
            $this->steps[] = ['step' => $name, 'ok' => false, 'message' => $this->redact($e->getMessage())];

            return false;
        }
    }

    /**
     * Кеши конфигурации, маршрутов, событий и шаблонов. Только кеши Laravel: команды Filament/иконок
     * для optimize регистрируются лишь в консоли (в веб-запросе их нет), а в тестах легли бы в репозиторий.
     */
    private function optimize(): string
    {
        $app = app();
        try {
            return $this->artisan('optimize', ['--except' => 'filament,blade-icons']);
        } finally {
            // config:cache загружает свежую копию приложения и оставляет её глобальной — возвращаем своё.
            Facade::clearResolvedInstances();
            Facade::setFacadeApplication($app);
            Container::setInstance($app);
            Model::setConnectionResolver($app['db']);
            Model::setEventDispatcher($app['events']);
        }
    }

    private function artisan(string $command, array $parameters = []): string
    {
        $code = Artisan::call($command, $parameters);
        $output = trim(preg_replace('/[ \t]+/', ' ', Artisan::output()));
        if ($code !== 0) {
            throw new \RuntimeException("{$command}: код {$code}. ".mb_substr($output, -1500));
        }

        return mb_substr($output, -1500);
    }

    private function redact(string $text): string
    {
        return $this->secrets ? str_replace($this->secrets, '***', $text) : $text;
    }
}
