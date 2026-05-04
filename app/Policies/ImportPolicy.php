<?php

namespace App\Policies;

use App\Models\Import;
use App\Models\User;

class ImportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Import $import): bool
    {
        return $user->isStaff();
    }

    public function create(User $user): bool
    {
        return $user->canImportContacts();
    }

    public function delete(User $user, Import $import): bool
    {
        return $user->isAdmin();
    }
}
