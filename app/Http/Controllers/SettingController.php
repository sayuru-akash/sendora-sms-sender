<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingRequest;
use App\Http\Requests\TestSmsRequest;
use App\Models\SystemSetting;
use App\Services\ActivityLogger;
use App\Services\Sms\SmsService;
use App\Services\PhoneNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
        $settings = SystemSetting::notSensitive()->get()->groupBy('group');

        return Inertia::render('Settings/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(SettingRequest $request): JsonResponse
    {
        $settingsData = $request->input('settings', []);

        foreach ($settingsData as $settingData) {
            SystemSetting::set(
                $settingData['key'],
                $settingData['value'],
                $settingData['type'] ?? 'string',
                $settingData['group'] ?? 'general',
            );
        }

        $this->activityLogger->logSettingsChanged(
            collect($settingsData)->pluck('group')->unique()->implode(', ')
        );

        return response()->json(['message' => 'Settings updated successfully.']);
    }

    /**
     * Send a test SMS.
     */
    public function testSms(TestSmsRequest $request, SmsService $smsService, PhoneNormalizer $phoneNormalizer): JsonResponse
    {
        // Rate limit: 5 test SMS per minute per user
        $rateLimitKey = 'test-sms:' . $request->user()->id;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            return response()->json([
                'message' => "Too many test SMS attempts. Please try again in {$seconds} seconds.",
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

        return response()->json([
            'success' => $result->success,
            'message' => $result->success ? 'Test SMS sent successfully.' : 'Test SMS failed: ' . $result->errorMessage,
        ], $result->success ? 200 : 422);
    }
}
