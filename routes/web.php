<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ListController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SavedSegmentController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SmsTemplateController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Contacts
    Route::post('/contacts/bulk-action', [ContactController::class, 'bulkAction'])->name('contacts.bulk-action');
    Route::get('/contacts/export', [ContactController::class, 'export'])->name('contacts.export');
    Route::resource('contacts', ContactController::class);

    // Tags
    Route::resource('tags', TagController::class);

    // Lists
    Route::post('/lists/{list}/add-contacts', [ListController::class, 'addContacts'])->name('lists.add-contacts');
    Route::post('/lists/{list}/remove-contacts', [ListController::class, 'removeContacts'])->name('lists.remove-contacts');
    Route::get('/lists/{list}/export', [ListController::class, 'export'])->name('lists.export');
    Route::resource('lists', ListController::class);

    // Imports
    Route::post('/imports/upload', [ImportController::class, 'upload'])->name('imports.upload');
    Route::get('/imports/{import}/mapping', [ImportController::class, 'mapping'])->name('imports.mapping');
    Route::post('/imports/{import}/preview', [ImportController::class, 'preview'])->name('imports.preview');
    Route::post('/imports/{import}/confirm', [ImportController::class, 'confirm'])->name('imports.confirm');
    Route::get('/imports/{import}/download-failed', [ImportController::class, 'downloadFailed'])->name('imports.download-failed');
    Route::resource('imports', ImportController::class)->only(['index', 'show', 'destroy']);

    // SMS Templates
    Route::post('/templates/{template}/duplicate', [SmsTemplateController::class, 'duplicate'])->name('templates.duplicate');
    Route::resource('templates', SmsTemplateController::class);

    // Campaigns
    Route::post('/campaigns/{campaign}/send', [CampaignController::class, 'send'])->name('campaigns.send');
    Route::post('/campaigns/{campaign}/pause', [CampaignController::class, 'pause'])->name('campaigns.pause');
    Route::post('/campaigns/{campaign}/resume', [CampaignController::class, 'resume'])->name('campaigns.resume');
    Route::post('/campaigns/{campaign}/cancel', [CampaignController::class, 'cancel'])->name('campaigns.cancel');
    Route::post('/campaigns/{campaign}/duplicate', [CampaignController::class, 'duplicate'])->name('campaigns.duplicate');
    Route::get('/campaigns/{campaign}/report', [CampaignController::class, 'report'])->name('campaigns.report');
    Route::get('/campaigns/{campaign}/recipients', [CampaignController::class, 'recipients'])->name('campaigns.recipients');
    Route::resource('campaigns', CampaignController::class);

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/campaigns/{campaign}', [ReportController::class, 'campaignReport'])->name('reports.campaign');
    Route::get('/reports/campaigns/{campaign}/export', [ReportController::class, 'exportCampaign'])->name('reports.campaign.export');

    // Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/test-sms', [SettingController::class, 'testSms'])->name('settings.test-sms');

    // Saved Segments
    Route::post('/segments/{segment}/preview', [SavedSegmentController::class, 'preview'])->name('segments.preview');
    Route::resource('segments', SavedSegmentController::class);

    // Activity Logs
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Users (admin only)
Route::middleware(['auth', 'verified', 'role:owner,admin'])->group(function () {
    Route::resource('users', UserController::class);
});

require __DIR__.'/auth.php';
