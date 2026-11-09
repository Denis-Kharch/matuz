<?php

use App\Content\ContentImporter;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Widgets\PublishStatusWidget;
use App\Models\Setting;
use App\Models\User;
use App\Site\PublishResult;
use App\Site\SiteBuilder;
use App\Site\SitePublisher;
use Filament\Notifications\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    $this->publicDir = sys_get_temp_dir().'/panel-test-'.bin2hex(random_bytes(4));
    mkdir($this->publicDir);
    $this->originalPublic = public_path();
    app()->usePublicPath($this->publicDir);
    $this->withoutVite();
    config(['site.content_source' => 'database', 'app.url' => 'https://matuzov.test']);
    app(ContentImporter::class)->importIfEmpty();
});

afterEach(function () {
    app()->usePublicPath($this->originalPublic);
    File::deleteDirectory($this->publicDir);
});

it('redirects guests to login', fn () => $this->get('/admin')->assertRedirect('/admin/login'));

it('lets a user into the dashboard with publish status', function () {
    Setting::put('publish_status', ['ok' => true, 'at' => now()->toIso8601String(), 'error' => null, 'pages' => 23]);
    $this->actingAs(User::factory()->create())->get('/admin')->assertOk()->assertSee('Сайт обновлён');
});

it('shows the publish error', function () {
    Setting::put('publish_status', ['ok' => false, 'at' => now()->toIso8601String(), 'error' => 'Сломалось', 'pages' => 0]);
    $this->actingAs(User::factory()->create())->get('/admin')->assertOk()->assertSee('Ошибка: Сломалось');
});

it('rebuild button publishes', function () {
    $this->actingAs(User::factory()->create());
    Livewire::test(PublishStatusWidget::class)->call('rebuild');
    expect(Setting::get('publish_status')['ok'])->toBeTrue();
});

it('cannot delete yourself', function () {
    $u = User::factory()->create();
    $this->actingAs($u);
    Livewire::test(EditUser::class, ['record' => $u->getKey()])->assertActionHidden('delete');
});

it('can delete another user', function () {
    $this->actingAs(User::factory()->create());
    $other = User::factory()->create();
    Livewire::test(EditUser::class, ['record' => $other->getKey()])->assertActionVisible('delete');
});

it('creates a user with a hashed password of at least 10 chars', function () {
    $this->actingAs(User::factory()->create());
    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'Анна', 'email' => 'anna@example.com', 'password' => 'short'])
        ->call('create')->assertHasFormErrors(['password']);
    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'Анна', 'email' => 'anna@example.com', 'password' => 'long-enough-pass'])
        ->call('create')->assertHasNoFormErrors();
    $user = User::where('email', 'anna@example.com')->first();
    expect($user)->not->toBeNull()->and(Hash::check('long-enough-pass', $user->password))->toBeTrue();
});

it('keeps the old password when editing without a new one', function () {
    $u = User::factory()->create(['password' => 'old-password-123']);
    $this->actingAs($u);
    $hash = $u->fresh()->password;
    Livewire::test(EditUser::class, ['record' => $u->getKey()])
        ->fillForm(['name' => 'Новое имя', 'password' => ''])
        ->call('save')->assertHasNoFormErrors();
    expect($u->fresh()->name)->toBe('Новое имя')->and($u->fresh()->password)->toBe($hash);
});

it('policy forbids deleting yourself on the server side', function () {
    $u = User::factory()->create();
    $other = User::factory()->create();
    expect($u->can('delete', $u))->toBeFalse()->and($u->can('delete', $other))->toBeTrue();
    $this->actingAs($u);
    Livewire::test(ListUsers::class)->assertTableActionHidden('delete', $u);
    expect(User::find($u->getKey()))->not->toBeNull();
});

it('renders the login page in Russian by default', function () {
    expect(config('app.locale'))->toBe('ru');
    $this->get('/admin/login')->assertOk()->assertSee('Войти');
});

it('warns when another publish is running', function () {
    $this->actingAs(User::factory()->create());
    $this->app->bind(SitePublisher::class, fn () => new class(app(SiteBuilder::class)) extends SitePublisher
    {
        public function publishNow(): ?PublishResult
        {
            return null;
        }
    });
    Livewire::test(PublishStatusWidget::class)->call('rebuild')
        ->assertNotified(Notification::make()->title('Сайт уже обновляется, изменения попадут в него автоматически')->warning());
});

it('shows an error notification when the publisher throws', function () {
    $this->actingAs(User::factory()->create());
    $this->app->bind(SitePublisher::class, fn () => new class(app(SiteBuilder::class)) extends SitePublisher
    {
        public function publishNow(): ?PublishResult
        {
            throw new RuntimeException('Диск недоступен');
        }
    });
    Livewire::test(PublishStatusWidget::class)->call('rebuild')
        ->assertNotified(Notification::make()->title('Ошибка: Диск недоступен')->danger());
});
