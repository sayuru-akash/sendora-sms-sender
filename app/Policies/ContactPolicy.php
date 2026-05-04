<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;

class ContactPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isViewer();
    }

    public function view(User $user, Contact $contact): bool
    {
        return $user->isViewer();
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, Contact $contact): bool
    {
        return $user->isStaff();
    }

    public function delete(User $user, Contact $contact): bool
    {
        return $user->isStaff();
    }
}
