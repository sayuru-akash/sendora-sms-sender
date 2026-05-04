<?php

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;

class TagPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isViewer();
    }

    public function view(User $user, Tag $tag): bool
    {
        return $user->isViewer();
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, Tag $tag): bool
    {
        return $user->isStaff();
    }

    public function delete(User $user, Tag $tag): bool
    {
        return $user->isStaff();
    }
}
