<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

/**
 * Вход только по паролю, без почты: администраторов единицы, пользователь определяется по паролю.
 * Лимит попыток и выравнивание времени ответа — из базовой страницы Filament.
 */
class Login extends BaseLogin
{
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->getPasswordFormComponent()->autofocus(),
            $this->getRememberFormComponent(),
        ]);
    }

    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        $password = (string) ($data['password'] ?? '');
        $user = User::query()->orderBy('id')->get()
            ->first(fn (User $u) => $password !== '' && Hash::check($password, $u->password));

        // Не нашли — пустая почта: провайдер не найдёт пользователя, и базовая страница покажет ошибку.
        return ['email' => $user?->email ?? '', 'password' => $password];
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.password' => 'Неверный пароль.',
        ]);
    }
}
