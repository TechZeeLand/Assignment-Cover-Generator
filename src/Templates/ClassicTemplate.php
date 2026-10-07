<?php

declare(strict_types=1);

namespace App\Templates;

use App\CoverBuilder;
use App\CoverData;

/**
 * The original design. All layout logic still lives, unchanged, in
 * CoverBuilder - this class only exposes it through the Template interface.
 */
final class ClassicTemplate implements Template
{
    public function key(): string
    {
        return 'classic';
    }

    public function category(): string
    {
        return 'Classic';
    }

    public function label(): string
    {
        return 'Classic';
    }

    public function description(): string
    {
        return 'The original design: bold frame, centered header and labelled rows.';
    }

    public function thumbnailSvg(): string
    {
        return ThumbKit::svg(
            ThumbKit::frame(3, 5, 'a')
            . ThumbKit::r(46, 17, 28, 3, 's')
            . ThumbKit::r(20, 24, 80, 7, 'a')
            . ThumbKit::r(36, 35, 48, 3.5, 's')
            . ThumbKit::r(16, 47, 44, 5, 'p')
            . ThumbKit::rows(20, 20, 48, 34, 58, 5)
            . ThumbKit::r(16, 94, 44, 5, 'p')
            . ThumbKit::rows(20, 20, 48, 34, 105, 3)
            . ThumbKit::r(16, 128, 16, 4, 'p')
            . ThumbKit::r(38, 128, 50, 3, 's')
            . ThumbKit::r(38, 134, 36, 3, 's')
            . ThumbKit::r(16, 150, 70, 3, 'a')
        );
    }

    public function bottomMarginPt(CoverData $d, float $spacingScale, float $fontScale): float
    {
        return CoverBuilder::marginsPt($d, $spacingScale, $fontScale)['bottom'];
    }

    public function buildHtml(CoverData $d, float $fontScale, float $spacingScale): string
    {
        return CoverBuilder::buildHtml($d, $fontScale, $spacingScale);
    }
}
