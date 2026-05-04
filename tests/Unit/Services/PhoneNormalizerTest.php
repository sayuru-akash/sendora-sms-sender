<?php

namespace Tests\Unit\Services;

use App\Services\PhoneNormalizer;
use Tests\TestCase;

class PhoneNormalizerTest extends TestCase
{
    protected PhoneNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new PhoneNormalizer();
    }

    public function test_normalizes_local_format(): void
    {
        $result = $this->normalizer->normalize('0771234567');
        $this->assertEquals('94771234567', $result);
    }

    public function test_normalizes_international_format(): void
    {
        $result = $this->normalizer->normalize('+94771234567');
        $this->assertEquals('94771234567', $result);
    }

    public function test_normalizes_without_plus(): void
    {
        $result = $this->normalizer->normalize('94771234567');
        $this->assertEquals('94771234567', $result);
    }

    public function test_normalizes_short_format(): void
    {
        $result = $this->normalizer->normalize('771234567');
        $this->assertEquals('94771234567', $result);
    }

    public function test_normalizes_with_spaces(): void
    {
        $result = $this->normalizer->normalize('077 123 4567');
        $this->assertEquals('94771234567', $result);
    }

    public function test_normalizes_with_dashes(): void
    {
        $result = $this->normalizer->normalize('077-123-4567');
        $this->assertEquals('94771234567', $result);
    }

    public function test_normalizes_with_brackets(): void
    {
        $result = $this->normalizer->normalize('+94 (77) 123-4567');
        $this->assertEquals('94771234567', $result);
    }

    public function test_validates_sri_lankan_mobile(): void
    {
        $this->assertTrue($this->normalizer->validate('0771234567'));
        $this->assertTrue($this->normalizer->validate('+94771234567'));
        $this->assertTrue($this->normalizer->validate('94771234567'));
        $this->assertTrue($this->normalizer->validate('771234567'));
    }

    public function test_rejects_invalid_numbers(): void
    {
        // Too short
        $this->assertFalse($this->normalizer->validate('12345'));
        // Wrong prefix (not 947)
        $this->assertFalse($this->normalizer->validate('94111234567'));
        // Landline format
        $this->assertFalse($this->normalizer->validate('0112345678'));
        // Empty
        $this->assertFalse($this->normalizer->validate(''));
    }

    public function test_formats_for_display(): void
    {
        $this->assertEquals('+94 77 123 4567', $this->normalizer->formatForDisplay('94771234567'));
    }

    public function test_format_for_display_returns_original_if_not_valid(): void
    {
        $this->assertEquals('12345', $this->normalizer->formatForDisplay('12345'));
    }

    public function test_get_validation_error_returns_message_for_invalid(): void
    {
        $error = $this->normalizer->getValidationError('123');
        $this->assertNotNull($error);
        $this->assertIsString($error);
    }

    public function test_get_validation_error_returns_null_for_valid(): void
    {
        $error = $this->normalizer->getValidationError('0771234567');
        $this->assertNull($error);
    }
}
