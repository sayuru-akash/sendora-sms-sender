<?php

namespace App\Providers;

use App\Services\Sms\SmsProviderInterface;
use App\Services\Sms\SmsService;
use App\Services\Sms\TextWareProvider;
use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind the SMS provider based on config
        $this->app->bind(SmsProviderInterface::class, function ($app) {
            $provider = config('sms.provider', 'textware');

            return match ($provider) {
                'textware' => new TextWareProvider(),
                default => new TextWareProvider(),
            };
        });

        // Bind the SMS service
        $this->app->singleton(SmsService::class, function ($app) {
            return new SmsService(
                $app->make(SmsProviderInterface::class),
                $app->make(\App\Services\PhoneNormalizer::class),
            );
        });

        // Bind PhoneNormalizer as singleton
        $this->app->singleton(\App\Services\PhoneNormalizer::class);
    }

    public function boot(): void
    {
        //
    }
}
