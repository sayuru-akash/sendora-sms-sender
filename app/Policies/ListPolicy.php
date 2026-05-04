<?php

namespace App\Policies;

use App\Models\ListModel;
use App\Models\User;

class ListPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isViewer();
    }

    public function view(User $user, ListModel $list): bool
    {
        return $user->isViewer();
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, ListModel $list): bool
    {
        return $user->isStaff();
    }

    public function delete(User $user, ListModel $list): bool
    {
        return $user->isStaff();
    }
}
