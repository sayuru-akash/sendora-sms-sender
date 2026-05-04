<?php

namespace App\Policies;

use App\Models\SmsTemplate;
use App\Models\User;

class SmsTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isViewer();
    }

    public function view(User $user, SmsTemplate $template): bool
    {
        return $user->isViewer();
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, SmsTemplate $template): bool
    {
        return $user->isStaff();
    }

    public function delete(User $user, SmsTemplate $template): bool
    {
        return $user->isStaff();
    }
}
