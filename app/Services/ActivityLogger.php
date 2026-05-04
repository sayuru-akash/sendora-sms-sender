<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Import;
use App\Models\SmsCampaign;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

class ActivityLogger
{
    /**
     * Log a user login.
     */
    public function logLogin(User $user): Activity
    {
        return activity()
            ->performedOn($user)
            ->causedBy($user)
            ->event('login')
            ->withProperties([
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('User logged in');
    }

    /**
     * Log a contact creation.
     */
    public function logContactCreated(Contact $contact): Activity
    {
        return activity()
            ->performedOn($contact)
            ->causedBy(auth()->user())
            ->event('created')
            ->withProperties([
                'phone' => $contact->phone_normalised,
                'name' => $contact->full_name,
            ])
            ->log('Contact created');
    }

    /**
     * Log a contact update.
     */
    public function logContactUpdated(Contact $contact): Activity
    {
        return activity()
            ->performedOn($contact)
            ->causedBy(auth()->user())
            ->event('updated')
            ->withProperties([
                'phone' => $contact->phone_normalised,
                'name' => $contact->full_name,
            ])
            ->log('Contact updated');
    }

    /**
     * Log a contact deletion.
     */
    public function logContactDeleted(Contact $contact): Activity
    {
        return activity()
            ->performedOn($contact)
            ->causedBy(auth()->user())
            ->event('deleted')
            ->withProperties([
                'phone' => $contact->phone_normalised,
                'name' => $contact->full_name,
            ])
            ->log('Contact deleted');
    }

    /**
     * Log a bulk import started.
     */
    public function logBulkImportStarted(Import $import): Activity
    {
        return activity()
            ->performedOn($import)
            ->causedBy($import->creator)
            ->event('import_started')
            ->withProperties([
                'filename' => $import->original_filename,
                'total_rows' => $import->total_rows,
            ])
            ->log('Bulk import started');
    }

    /**
     * Log a bulk import completed.
     */
    public function logBulkImportCompleted(Import $import): Activity
    {
        return activity()
            ->performedOn($import)
            ->causedBy($import->creator)
            ->event('import_completed')
            ->withProperties([
                'filename' => $import->original_filename,
                'total_rows' => $import->total_rows,
                'successful_rows' => $import->successful_rows,
                'failed_rows' => $import->failed_rows,
                'duplicate_rows' => $import->duplicate_rows,
            ])
            ->log('Bulk import completed');
    }

    /**
     * Log a campaign creation.
     */
    public function logCampaignCreated(SmsCampaign $campaign): Activity
    {
        return activity()
            ->performedOn($campaign)
            ->causedBy($campaign->creator)
            ->event('created')
            ->withProperties([
                'name' => $campaign->name,
                'target_type' => $campaign->target_type,
            ])
            ->log('Campaign created');
    }

    /**
     * Log a campaign sent.
     */
    public function logCampaignSent(SmsCampaign $campaign): Activity
    {
        return activity()
            ->performedOn($campaign)
            ->causedBy(auth()->user())
            ->event('sent')
            ->withProperties([
                'name' => $campaign->name,
                'total_recipients' => $campaign->total_recipients,
            ])
            ->log('Campaign sent');
    }

    /**
     * Log a campaign paused.
     */
    public function logCampaignPaused(SmsCampaign $campaign): Activity
    {
        return activity()
            ->performedOn($campaign)
            ->causedBy(auth()->user())
            ->event('paused')
            ->withProperties([
                'name' => $campaign->name,
                'sent_count' => $campaign->sent_count,
                'pending_count' => $campaign->pending_count,
            ])
            ->log('Campaign paused');
    }

    /**
     * Log a campaign cancelled.
     */
    public function logCampaignCancelled(SmsCampaign $campaign): Activity
    {
        return activity()
            ->performedOn($campaign)
            ->causedBy(auth()->user())
            ->event('cancelled')
            ->withProperties([
                'name' => $campaign->name,
                'sent_count' => $campaign->sent_count,
                'pending_count' => $campaign->pending_count,
            ])
            ->log('Campaign cancelled');
    }

    /**
     * Log settings changed.
     */
    public function logSettingsChanged(string $group): Activity
    {
        return activity()
            ->causedBy(auth()->user())
            ->event('settings_changed')
            ->withProperties([
                'group' => $group,
            ])
            ->log('Settings changed');
    }

    /**
     * Log a test SMS.
     */
    public function logTestSms(string $phone, bool $success): Activity
    {
        return activity()
            ->causedBy(auth()->user())
            ->event('test_sms')
            ->withProperties([
                'phone' => $phone,
                'success' => $success,
            ])
            ->log($success ? 'Test SMS sent successfully' : 'Test SMS failed');
    }
}
