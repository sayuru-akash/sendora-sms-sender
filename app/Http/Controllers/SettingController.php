<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingRequest;
use App\Http\Requests\TestSmsRequest;
use App\Models\SystemSetting;
use App\Services\ActivityLogger;
use App\Services\PhoneNormalizer;
use App\Services\Sms\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function __construct(
        protected ActivityLogger $activityLogger,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->canManageSettings(), 403);

        $settings = [
            'company_name' => SystemSetting::get('company_name', 'Sendora'),
            'timezone' => SystemSetting::get('timezone', 'Asia/Colombo'),
            'date_format' => SystemSetting::get('date_format', 'd/m/Y'),
            'default_country_code' => SystemSetting::get('default_country_code', '+94'),
            'sender_id' => config('sms.source', ''),
            'sms_provider' => config('sms.provider', 'unknown'),
            'max_import_file_size' => (int) SystemSetting::get('max_import_file_size', 10),
            'default_duplicate_handling' => SystemSetting::get('default_duplicate_handling', 'skip'),
        ];

        return Inertia::render('Settings/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(SettingRequest $request): JsonResponse|RedirectResponse
    {
        $settingsMap = SystemSetting::EDITABLE_SETTINGS;

        foreach ($settingsMap as $key => $config) {
            if ($request->has($key)) {
                SystemSetting::set(
                    $key,
                    $request->input($key),
                    $config['type'],
                    $config['group'],
                );
            }
        }

        // Also handle legacy format (settings array)
        $settingsData = $request->input('settings', []);
        foreach ($settingsData as $settingData) {
            if (! isset($settingsMap[$settingData['key']])) {
                continue;
            }

            $config = $settingsMap[$settingData['key']];

            SystemSetting::set(
                $settingData['key'],
                $settingData['value'],
                $config['type'],
                $config['group'],
            );
        }

        $this->activityLogger->logSettingsChanged('general');

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Settings updated successfully.']);
        }

        return back()->with('success', 'Settings updated successfully.');
    }

    /**
     * Send a test SMS.
     */
    public function testSms(TestSmsRequest $request, SmsService $smsService, PhoneNormalizer $phoneNormalizer): JsonResponse|RedirectResponse
    {
        // Rate limit: 5 test SMS per minute per user
        $rateLimitKey = 'test-sms:'.$request->user()->id;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            $message = "Too many test SMS attempts. Please try again in {$seconds} seconds.";

            if (! $request->wantsJson()) {
                return back()->withErrors(['phone' => $message]);
            }

            return response()->json([
                'message' => $message,
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, 60);

        $phone = $request->input('phone');
        $message = $request->input('message', 'This is a test SMS from Sendora.');
        $senderId = config('sms.source');

        $result = $smsService->send($phone, $message, $senderId);

        $this->activityLogger->logTestSms(
            $phoneNormalizer->normalize($phone),
            $result->success,
        );

        $message = $result->success ? 'Test SMS sent successfully.' : 'Test SMS failed: '.$result->errorMessage;

        if (! $request->wantsJson()) {
            return $result->success
                ? back()->with('success', $message)
                : back()->withErrors(['phone' => $message]);
        }

        return response()->json([
            'success' => $result->success,
            'message' => $message,
        ], $result->success ? 200 : 422);
    }
}
