<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SMS Provider
    |--------------------------------------------------------------------------
    |
    | The default SMS provider to use. Currently supports 'textware'.
    |
    */
    'provider' => env('SMS_PROVIDER', 'textware'),

    /*
    |--------------------------------------------------------------------------
    | TextWare Credentials
    |--------------------------------------------------------------------------
    */
    'username' => env('SMS_USERNAME'),
    'password' => env('SMS_PASSWORD'),
    'source' => env('SMS_SOURCE'),

    /*
    |--------------------------------------------------------------------------
    | TextWare API URL
    |--------------------------------------------------------------------------
    */
    'api_url' => env('SMS_API_URL', 'https://msg.text-ware.com/send_sms.php'),

    /*
    |--------------------------------------------------------------------------
    | Rate Limit
    |--------------------------------------------------------------------------
    |
    | Maximum number of SMS messages to send per minute.
    |
    */
    'rate_limit_per_minute' => (int) env('SMS_RATE_LIMIT_PER_MINUTE', 300),

    /*
    |--------------------------------------------------------------------------
    | Default Country Code
    |--------------------------------------------------------------------------
    */
    'default_country_code' => env('SMS_DEFAULT_COUNTRY_CODE', '94'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout
    |--------------------------------------------------------------------------
    |
    | Timeout in seconds for SMS API requests.
    |
    */
    'timeout_seconds' => (int) env('SMS_TIMEOUT_SECONDS', 30),

    /*
    |--------------------------------------------------------------------------
    | Message Safety Limit
    |--------------------------------------------------------------------------
    |
    | This is an application-level guardrail for accidental huge submissions.
    | Normal long SMS messages are sent as multiple billable segments.
    |
    */
    'max_message_characters' => (int) env('SMS_MAX_MESSAGE_CHARACTERS', 10000),
];
