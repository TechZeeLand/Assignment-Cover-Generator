<?php

declare(strict_types=1);

namespace App\Templates;

use App\CoverData;

/**
 * One selectable cover design.
 *
 * A template turns a CoverData into the HTML that mPDF renders, and tells
 * PdfService how much page-bottom margin it needs (that margin is what makes
 * mPDF spill to a 2nd page, which PdfService then reacts to by shrinking
 * spacing/fonts - see PdfService::render()).
 */
interface Template
{
    /** Stable machine key posted by the form, e.g. "modern". */
    public function key(): string;

    /** Short human name shown in the design picker. */
    public function label(): string;

    /** One-line description shown in the design picker. */
    public function description(): string;

    /** Inline <svg> miniature for the picker (colours via CSS variables). */
    public function thumbnailSvg(): string;

    /** Real mPDF bottom page margin, in points. */
    public function bottomMarginPt(CoverData $d, float $spacingScale, float $fontScale): float;

    public function buildHtml(CoverData $d, float $fontScale, float $spacingScale): string;
}
