<?php

namespace App\Policies;

use App\Models\SmsCampaign;
use App\Models\User;

class SmsCampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isViewer();
    }

    public function view(User $user, SmsCampaign $campaign): bool
    {
        return $user->isViewer();
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, SmsCampaign $campaign): bool
    {
        return $user->isStaff();
    }

    public function delete(User $user, SmsCampaign $campaign): bool
    {
        return $user->isStaff();
    }

    public function send(User $user, SmsCampaign $campaign): bool
    {
        return $user->canSendCampaigns();
    }

    public function pause(User $user, SmsCampaign $campaign): bool
    {
        return $user->canSendCampaigns();
    }

    public function resume(User $user, SmsCampaign $campaign): bool
    {
        return $user->canSendCampaigns();
    }

    public function cancel(User $user, SmsCampaign $campaign): bool
    {
        return $user->canSendCampaigns();
    }

    public function duplicate(User $user, SmsCampaign $campaign): bool
    {
        return $user->isStaff();
    }
}
