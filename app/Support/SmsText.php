<?php

namespace App\Support;

class SmsText
{
    private const GSM_SINGLE_SEGMENT_LIMIT = 160;

    private const GSM_MULTI_SEGMENT_LIMIT = 153;

    private const UNICODE_SINGLE_SEGMENT_LIMIT = 70;

    private const UNICODE_MULTI_SEGMENT_LIMIT = 67;

    /**
     * @var array<int, string>
     */
    private const GSM_BASIC_CHARS = [
        '@', '£', '$', '¥', 'è', 'é', 'ù', 'ì', 'ò', 'Ç', "\n", 'Ø', 'ø', "\r", 'Å', 'å',
        'Δ', '_', 'Φ', 'Γ', 'Λ', 'Ω', 'Π', 'Ψ', 'Σ', 'Θ', 'Ξ',
        ' ', '!', '"', '#', '¤', '%', '&', "'", '(', ')', '*', '+', ',', '-', '.', '/',
        '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', ':', ';', '<', '=', '>', '?',
        '¡', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O',
        'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'Ä', 'Ö', 'Ñ', 'Ü', '§',
        '¿', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o',
        'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 'ä', 'ö', 'ñ', 'ü', 'à',
    ];

    /**
     * @var array<int, string>
     */
    private const GSM_EXTENDED_CHARS = ['^', '{', '}', '\\', '[', '~', ']', '|', '€'];

    /**
     * @return array{encoding: string, character_count: int, sms_segments: int, per_segment: int, remaining_in_segment: int}
     */
    public static function metrics(?string $text): array
    {
        $text = (string) $text;
        $encoding = self::encoding($text);
        $characterCount = self::characterCount($text, $encoding);

        if ($characterCount === 0) {
            return [
                'encoding' => $encoding,
                'character_count' => 0,
                'sms_segments' => 0,
                'per_segment' => self::GSM_SINGLE_SEGMENT_LIMIT,
                'remaining_in_segment' => self::GSM_SINGLE_SEGMENT_LIMIT,
            ];
        }

        $singleLimit = $encoding === 'GSM-7' ? self::GSM_SINGLE_SEGMENT_LIMIT : self::UNICODE_SINGLE_SEGMENT_LIMIT;
        $multiLimit = $encoding === 'GSM-7' ? self::GSM_MULTI_SEGMENT_LIMIT : self::UNICODE_MULTI_SEGMENT_LIMIT;

        if ($characterCount <= $singleLimit) {
            return [
                'encoding' => $encoding,
                'character_count' => $characterCount,
                'sms_segments' => 1,
                'per_segment' => $singleLimit,
                'remaining_in_segment' => $singleLimit - $characterCount,
            ];
        }

        $segments = (int) ceil($characterCount / $multiLimit);

        return [
            'encoding' => $encoding,
            'character_count' => $characterCount,
            'sms_segments' => $segments,
            'per_segment' => $multiLimit,
            'remaining_in_segment' => ($segments * $multiLimit) - $characterCount,
        ];
    }

    public static function encoding(?string $text): string
    {
        foreach (mb_str_split((string) $text) as $character) {
            if (! self::isGsmCharacter($character)) {
                return 'Unicode';
            }
        }

        return 'GSM-7';
    }

    private static function characterCount(string $text, string $encoding): int
    {
        if ($encoding === 'Unicode') {
            return (int) (strlen(mb_convert_encoding($text, 'UTF-16BE', 'UTF-8')) / 2);
        }

        return array_reduce(
            mb_str_split($text),
            static fn (int $count, string $character): int => $count + (in_array($character, self::GSM_EXTENDED_CHARS, true) ? 2 : 1),
            0,
        );
    }

    private static function isGsmCharacter(string $character): bool
    {
        return in_array($character, self::GSM_BASIC_CHARS, true)
            || in_array($character, self::GSM_EXTENDED_CHARS, true);
    }
}
