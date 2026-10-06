<?php

declare(strict_types=1);

namespace App;

/**
 * Tiny colour helpers used by the cover designs (contrast-aware text colours,
 * light tints for zebra rows / hairlines). Input is always a validated
 * "#rrggbb" string (see Sanitize::color()).
 */
final class Color
{
    /** @return array{0:int,1:int,2:int} */
    public static function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
            return [0, 0, 0];
        }
        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    public static function hex(int $r, int $g, int $b): string
    {
        return sprintf(
            '#%02x%02x%02x',
            max(0, min(255, $r)),
            max(0, min(255, $g)),
            max(0, min(255, $b))
        );
    }

    /** WCAG relative luminance, 0 (black) .. 1 (white). */
    public static function luminance(string $hex): float
    {
        [$r, $g, $b] = self::rgb($hex);
        $lin = static function (int $c): float {
            $v = $c / 255;
            return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        };
        return 0.2126 * $lin($r) + 0.7152 * $lin($g) + 0.0722 * $lin($b);
    }

    /** A readable text colour (near-white or near-black) for text on $bgHex. */
    public static function contrastText(string $bgHex): string
    {
        return self::luminance($bgHex) > 0.35 ? '#111111' : '#ffffff';
    }

    /** Linear mix: 0 = $a, 1 = $b. */
    public static function mix(string $a, string $b, float $t): string
    {
        $t = max(0.0, min(1.0, $t));
        [$r1, $g1, $b1] = self::rgb($a);
        [$r2, $g2, $b2] = self::rgb($b);
        return self::hex(
            (int) round($r1 + ($r2 - $r1) * $t),
            (int) round($g1 + ($g2 - $g1) * $t),
            (int) round($b1 + ($b2 - $b1) * $t)
        );
    }

    /** Lighten towards white: 0 = unchanged, 1 = white. */
    public static function tint(string $hex, float $amount): string
    {
        return self::mix($hex, '#ffffff', $amount);
    }
}
