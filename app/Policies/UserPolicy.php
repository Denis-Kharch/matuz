<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, User $record): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, User $record): bool
    {
        return true;
    }

    /** Себя удалить нельзя: иначе можно остаться без единого доступа. */
    public function delete(User $user, User $record): bool
    {
        return $user->isNot($record);
    }
}
