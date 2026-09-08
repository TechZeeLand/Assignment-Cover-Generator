<?php

declare(strict_types=1);

namespace App;

final class Config
{
    /** Absolute path to the folder where user-uploaded fonts are persisted. */
    public static function fontsStoragePath(): string
    {
        $path = getenv('FONTS_STORAGE_PATH') ?: (dirname(__DIR__) . '/storage/fonts');
        if (!is_dir($path)) {
            @mkdir($path, 0775, true);
        }
        return $path;
    }

    /**
     * Absolute path to a writable scratch folder for mPDF's temp/cache files.
     * mPDF's own default temp dir lives inside vendor/mpdf/mpdf/tmp, which
     * usually isn't writable by the web server user (it's owned by whoever
     * ran `composer install` / built the image), so we give it a folder
     * inside our own storage/ tree instead, alongside storage/fonts.
     */
    public static function tempPath(): string
    {
        $path = getenv('MPDF_TEMP_PATH') ?: (dirname(__DIR__) . '/storage/tmp');
        if (!is_dir($path)) {
            @mkdir($path, 0775, true);
        }
        return $path;
    }

    /** Absolute path to bundled static assets (e.g. the Amiri font). */
    public static function assetsFontsPath(): string
    {
        return dirname(__DIR__) . '/public/assets/fonts';
    }

    /**
     * Built-in, always-available font choices.
     * These map to mPDF's bundled DejaVu font family aliases, which are
     * metric-compatible, fully open-source, and support the accented/Latin
     * characters most assignment covers need without any embedding step.
     *
     * @return array<string, string> label => mpdf font family key
     */
    public static function builtInFonts(): array
    {
        return [
            'Arial'                => 'sans',
            'Times New Roman'      => 'serif',
            'Courier New'          => 'mono',
            'Old English Text MT'  => 'oldenglish',
            'Gandhi Serif'         => 'gandhiserif',
            'Alata'                => 'alata',
        ];
    }

    /** Max upload size for a custom font file, in bytes (2 MB). */
    public static function maxFontUploadBytes(): int
    {
        return 2 * 1024 * 1024;
    }

    /** File extensions accepted for font uploads. */
    public static function allowedFontExtensions(): array
    {
        return ['ttf', 'otf'];
    }

    /** HTML tags allowed inside rich-text fields (Course Title, Topic). */
    public static function allowedRichTextTags(): string
    {
        return '<b><strong><i><em><br>';
    }
}
