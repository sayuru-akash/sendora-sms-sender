<?php

namespace Tests\Unit;

use App\Services\Sms\SmsResult;
use App\Services\Sms\TextWareProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsProviderTest extends TestCase
{
    public function test_textware_provider_sends_sms(): void
    {
        config()->set('sms.username', 'testuser');
        config()->set('sms.password', 'testpass');
        config()->set('sms.source', 'SENDER');
        config()->set('sms.api_url', 'https://msg.text-ware.com/send_sms.php');

        Http::fake([
            'msg.text-ware.com/*' => Http::response('OK', 200),
        ]);

        $provider = new TextWareProvider();
        $result = $provider->send('94771234567', 'Test message');

        $this->assertInstanceOf(SmsResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertTrue($result->sent);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://msg.text-ware.com/send_sms.php'
                && $request->data()['username'] === 'testuser'
                && $request->data()['destination'] === '94771234567'
                && $request->data()['message'] === 'Test message';
        });
    }

    public function test_provider_returns_failure_on_error(): void
    {
        config()->set('sms.username', 'testuser');
        config()->set('sms.password', 'testpass');
        config()->set('sms.source', 'SENDER');
        config()->set('sms.api_url', 'https://msg.text-ware.com/send_sms.php');

        Http::fake([
            'msg.text-ware.com/*' => Http::response('Invalid credentials', 401),
        ]);

        $provider = new TextWareProvider();
        $result = $provider->send('94771234567', 'Test message');

        $this->assertFalse($result->success);
        $this->assertNotNull($result->errorMessage);
    }

    public function test_provider_handles_timeout(): void
    {
        config()->set('sms.username', 'testuser');
        config()->set('sms.password', 'testpass');
        config()->set('sms.source', 'SENDER');
        config()->set('sms.api_url', 'https://msg.text-ware.com/send_sms.php');

        Http::fake([
            'msg.text-ware.com/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
            },
        ]);

        $provider = new TextWareProvider();
        $result = $provider->send('94771234567', 'Test message');

        $this->assertFalse($result->success);
        $this->assertNotNull($result->errorMessage);
        $this->assertStringContainsString('Network error', $result->errorMessage);
    }

    public function test_provider_handles_missing_credentials(): void
    {
        config()->set('sms.username', null);
        config()->set('sms.password', null);
        config()->set('sms.api_url', 'https://msg.text-ware.com/send_sms.php');

        $provider = new TextWareProvider();
        $result = $provider->send('94771234567', 'Test message');

        $this->assertFalse($result->success);
        $this->assertNotNull($result->errorMessage);
        $this->assertStringContainsString('credentials', $result->errorMessage);
    }

    public function test_provider_get_name(): void
    {
        $provider = new TextWareProvider();
        $this->assertEquals('textware', $provider->getName());
    }

    public function test_sms_result_success_factory(): void
    {
        $result = SmsResult::success('msg123', ['status' => 'ok']);

        $this->assertTrue($result->success);
        $this->assertTrue($result->sent);
        $this->assertEquals('msg123', $result->providerMessageId);
        $this->assertNull($result->errorMessage);
    }

    public function test_sms_result_failure_factory(): void
    {
        $result = SmsResult::failure('Something went wrong', ['error' => true]);

        $this->assertFalse($result->success);
        $this->assertFalse($result->sent);
        $this->assertEquals('Something went wrong', $result->errorMessage);
        $this->assertNull($result->providerMessageId);
    }

    public function test_sms_result_to_array(): void
    {
        $result = SmsResult::success('msg123');
        $array = $result->toArray();

        $this->assertIsArray($array);
        $this->assertTrue($array['success']);
        $this->assertEquals('msg123', $array['provider_message_id']);
    }
}
