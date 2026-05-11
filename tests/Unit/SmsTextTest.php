<?php

namespace Tests\Unit;

use App\Support\SmsText;
use PHPUnit\Framework\TestCase;

class SmsTextTest extends TestCase
{
    public function test_counts_gsm_segments_and_extended_characters(): void
    {
        $metrics = SmsText::metrics('Payment link: https://example.com/pay?x={amount}');

        $this->assertSame('GSM-7', $metrics['encoding']);
        $this->assertSame(50, $metrics['character_count']);
        $this->assertSame(1, $metrics['sms_segments']);
        $this->assertSame(110, $metrics['remaining_in_segment']);
    }

    public function test_counts_unicode_message_segments(): void
    {
        $metrics = SmsText::metrics(str_repeat('💳', 36));

        $this->assertSame('Unicode', $metrics['encoding']);
        $this->assertSame(72, $metrics['character_count']);
        $this->assertSame(2, $metrics['sms_segments']);
        $this->assertSame(62, $metrics['remaining_in_segment']);
    }
}
