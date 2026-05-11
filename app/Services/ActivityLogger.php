<?php

namespace App\Services;

use App\Models\CampaignRecipient;
use App\Models\Contact;
use App\Models\Import;
use App\Models\SmsCampaign;
use App\Models\SmsMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
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

    public function logCampaignSendRequested(SmsCampaign $campaign): Activity
    {
        return activity()
            ->performedOn($campaign)
            ->causedBy($this->campaignCauser($campaign))
            ->event('send_requested')
            ->withProperties([
                'name' => $campaign->name,
                'status' => $campaign->status,
                'target_type' => $campaign->target_type,
                'requested_at' => now()->toIso8601String(),
                'timezone' => config('app.timezone'),
            ])
            ->log('Campaign send requested');
    }

    public function logCampaignQueued(SmsCampaign $campaign): Activity
    {
        return activity()
            ->performedOn($campaign)
            ->causedBy(auth()->user() ?? $campaign->creator)
            ->event('queued')
            ->withProperties($this->campaignStatusProperties($campaign))
            ->log('Campaign queued');
    }

    public function logCampaignSending(SmsCampaign $campaign): Activity
    {
        return activity()
            ->performedOn($campaign)
            ->causedBy(auth()->user() ?? $campaign->creator)
            ->event('sending')
            ->withProperties($this->campaignStatusProperties($campaign))
            ->log('Campaign sending started');
    }

    public function logCampaignCompleted(SmsCampaign $campaign): Activity
    {
        return activity()
            ->performedOn($campaign)
            ->causedBy(auth()->user() ?? $campaign->creator)
            ->event('completed')
            ->withProperties($this->campaignStatusProperties($campaign))
            ->log('Campaign completed');
    }

    public function logCampaignFailed(SmsCampaign $campaign): Activity
    {
        return activity()
            ->performedOn($campaign)
            ->causedBy(auth()->user() ?? $campaign->creator)
            ->event('failed')
            ->withProperties($this->campaignStatusProperties($campaign))
            ->log('Campaign failed');
    }

    public function logCampaignResendQueued(SmsCampaign $campaign, int $recipientCount, ?CampaignRecipient $recipient = null): Activity
    {
        return activity()
            ->performedOn($campaign)
            ->causedBy(auth()->user())
            ->event('resend_queued')
            ->withProperties([
                'name' => $campaign->name,
                'recipient_count' => $recipientCount,
                'recipient_id' => $recipient?->id,
                'contact_id' => $recipient?->contact_id,
                'phone' => $recipient?->phone_normalised,
                'queued_at' => now()->toIso8601String(),
                'timezone' => config('app.timezone'),
            ])
            ->log($recipient ? 'Campaign recipient resend queued' : 'Campaign failed recipients resend queued');
    }

    public function logCampaignRecipientSent(SmsCampaign $campaign, CampaignRecipient $recipient, SmsMessage $smsMessage): Activity
    {
        return activity()
            ->performedOn($campaign)
            ->causedBy($this->campaignCauser($campaign))
            ->event('recipient_sent')
            ->withProperties([
                ...$this->campaignRecipientProperties($campaign, $recipient, $smsMessage),
                'sent_at' => $smsMessage->sent_at?->toIso8601String() ?? now()->toIso8601String(),
            ])
            ->log('Campaign recipient sent');
    }

    public function logCampaignRecipientFailed(SmsCampaign $campaign, CampaignRecipient $recipient, SmsMessage $smsMessage, string $errorMessage): Activity
    {
        return activity()
            ->performedOn($campaign)
            ->causedBy($this->campaignCauser($campaign))
            ->event('recipient_failed')
            ->withProperties([
                ...$this->campaignRecipientProperties($campaign, $recipient, $smsMessage),
                'error_message' => Str::limit($errorMessage, 500),
                'failed_at' => $smsMessage->failed_at?->toIso8601String() ?? now()->toIso8601String(),
            ])
            ->log('Campaign recipient failed');
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

    public function logCampaignResumed(SmsCampaign $campaign): Activity
    {
        return activity()
            ->performedOn($campaign)
            ->causedBy(auth()->user())
            ->event('resumed')
            ->withProperties($this->campaignStatusProperties($campaign))
            ->log('Campaign resumed');
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

    /**
     * @return array<string, mixed>
     */
    private function campaignStatusProperties(SmsCampaign $campaign): array
    {
        return [
            'name' => $campaign->name,
            'status' => $campaign->status,
            'total_recipients' => $campaign->total_recipients,
            'pending_count' => $campaign->pending_count,
            'queued_count' => $campaign->queued_count,
            'sent_count' => $campaign->sent_count,
            'failed_count' => $campaign->failed_count,
            'skipped_count' => $campaign->skipped_count,
            'logged_at' => now()->toIso8601String(),
            'timezone' => config('app.timezone'),
        ];
    }

    private function campaignCauser(SmsCampaign $campaign): ?Model
    {
        return auth()->user() ?? $campaign->creator;
    }

    /**
     * @return array<string, mixed>
     */
    private function campaignRecipientProperties(SmsCampaign $campaign, CampaignRecipient $recipient, SmsMessage $smsMessage): array
    {
        return [
            'name' => $campaign->name,
            'recipient_id' => $recipient->id,
            'sms_message_id' => $smsMessage->id,
            'contact_id' => $recipient->contact_id,
            'phone' => $recipient->phone_normalised,
            'attempt_count' => $recipient->attempt_count,
            'recipient_status' => $recipient->status,
            'message_status' => $smsMessage->status,
            'provider' => $smsMessage->provider,
            'provider_message_id' => $smsMessage->provider_message_id,
            'logged_at' => now()->toIso8601String(),
            'timezone' => config('app.timezone'),
        ];
    }
}
