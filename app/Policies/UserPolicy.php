<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {
        // Owner can update anyone, admin can update non-owner/non-admin
        if ($user->isOwner()) {
            return true;
        }

        return $user->isAdmin() && !$model->isOwner() && !$model->isAdmin();
    }

    public function delete(User $user, User $model): bool
    {
        // Cannot delete yourself
        if ($user->id === $model->id) {
            return false;
        }

        // Only owner can delete admin/owner, admin can delete non-admin
        if ($user->isOwner()) {
            return true;
        }

        return $user->isAdmin() && !$model->isOwner() && !$model->isAdmin();
    }
}
