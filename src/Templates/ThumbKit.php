<?php

declare(strict_types=1);

namespace App\Templates;

/**
 * Helpers for drawing the little wireframe previews in the design picker.
 * Shapes use classes (t-a / t-p / t-s / ...) that style.css maps to CSS
 * variables, so the previews follow the user's chosen colours live.
 *
 *   a = accent (title/border colour)   p = primary   s = secondary
 *   w = white (text on coloured areas) al/pl = light accent / primary
 */
final class ThumbKit
{
    public static function r(float $x, float $y, float $w, float $h, string $cls): string
    {
        return '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h
            . '" class="t-' . $cls . '"/>';
    }

    public static function svg(string $inner): string
    {
        return '<svg viewBox="0 0 120 170" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">'
            . '<rect width="120" height="170" fill="#fff"/>' . $inner . '</svg>';
    }

    /**
     * A block of "label : value" placeholder rows.
     *
     * @param float $step vertical distance between rows
     */
    public static function rows(
        float $labelX,
        float $labelW,
        float $valueX,
        float $valueW,
        float $y,
        int $n,
        float $step = 6.5,
        float $h = 2.6
    ): string {
        $out = '';
        for ($i = 0; $i < $n; $i++) {
            $yy = $y + $i * $step;
            $out .= self::r($labelX, $yy, $labelW, $h, 'p');
            $out .= self::r($valueX, $yy, $valueW + (($i % 2) ? -6.0 : 0.0), $h, 's');
        }
        return $out;
    }

    /** Four bars forming a rectangular frame. */
    public static function frame(float $inset, float $thick, string $cls): string
    {
        $w = 120 - 2 * $inset;
        $h = 170 - 2 * $inset;
        return self::r($inset, $inset, $w, $thick, $cls)
            . self::r($inset, $inset + $h - $thick, $w, $thick, $cls)
            . self::r($inset, $inset, $thick, $h, $cls)
            . self::r($inset + $w - $thick, $inset, $thick, $h, $cls);
    }
}
