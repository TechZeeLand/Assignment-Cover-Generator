<?php

declare(strict_types=1);

namespace App;

/**
 * Finds out which writing systems a TTF/OTF font can draw by reading its
 * `cmap` table (the list of characters the font has glyphs for).
 *
 * Used for two things:
 *  - the font picker shows a sample in the font's own script (Bangla, Arabic ...)
 *    so fonts of different languages are easy to tell apart;
 *  - fonts that need OpenType shaping (Bangla, Arabic, Devanagari ...) are
 *    switched to mPDF's OTL mode, without which their letters are joined wrongly.
 */
final class FontScripts
{
    /**
     * key => [label shown in the picker, sample text, test code points, needs shaping].
     * A script counts as supported when the font has at least 60% of its test characters.
     */
    public const SCRIPTS = [
        'latin'      => ['Latin',    'Abc 123',          [0x41, 0x42, 0x43, 0x44, 0x45, 0x61, 0x62, 0x63, 0x64, 0x65, 0x31, 0x32], false],
        'bengali'    => ['বাংলা',    'আমার সোনার বাংলা', [0x985, 0x995, 0x996, 0x9A4, 0x9AE, 0x9B0, 0x9B2, 0x9BE, 0x9BF, 0x9CD], true],
        'arabic'     => ['العربية',  'أبجد هوز',         [0x627, 0x628, 0x62A, 0x62C, 0x62F, 0x631, 0x633, 0x639, 0x644, 0x645], true],
        'devanagari' => ['देवनागरी', 'अआइ कखग',          [0x905, 0x915, 0x916, 0x917, 0x924, 0x92E, 0x930, 0x93E, 0x93F, 0x94D], true],
        'cyrillic'   => ['Кирилица', 'Абвгд Жзик',       [0x410, 0x411, 0x412, 0x416, 0x417, 0x430, 0x431, 0x432, 0x436, 0x437], false],
        'greek'      => ['Ελληνικά', 'Αβγδε Ωμέγα',      [0x391, 0x392, 0x393, 0x3A9, 0x3B1, 0x3B2, 0x3B3, 0x3C9], false],
    ];

    /** @var array<string, list<string>>|null */
    private static ?array $cache = null;

    /** @return list<string> script keys the font supports (empty when the file cannot be read) */
    public static function detect(string $path): array
    {
        $cacheFile = rtrim(Config::tempPath(), '/') . '/font-scripts.json';
        if (self::$cache === null) {
            $raw = is_file($cacheFile) ? json_decode((string) @file_get_contents($cacheFile), true) : null;
            self::$cache = is_array($raw) ? $raw : [];
        }
        $stat = @stat($path);
        if ($stat === false) {
            return [];
        }
        $id = basename($path) . ':' . $stat['size'] . ':' . $stat['mtime'];
        if (isset(self::$cache[$id])) {
            return self::$cache[$id];
        }

        $found = [];
        $chars = self::readCmap($path);
        if ($chars !== null) {
            foreach (self::SCRIPTS as $key => [, , $tests]) {
                $have = 0;
                foreach ($tests as $cp) {
                    if (self::covers($chars, $cp)) {
                        $have++;
                    }
                }
                if ($have >= (int) ceil(count($tests) * 0.6)) {
                    $found[] = $key;
                }
            }
        }
        self::$cache[$id] = $found;
        if (count(self::$cache) > 400) {
            self::$cache = array_slice(self::$cache, -200, null, true);
        }
        @file_put_contents($cacheFile, json_encode(self::$cache), LOCK_EX);
        return $found;
    }

    /** True when any of the scripts needs OpenType shaping to look right. */
    public static function needsShaping(array $scripts): bool
    {
        foreach ($scripts as $s) {
            if ((self::SCRIPTS[$s][3] ?? false) === true) {
                return true;
            }
        }
        return false;
    }

    /** True if $path starts like a TTF/OTF whose cmap can be read (used to reject broken uploads). */
    public static function isReadable(string $path): bool
    {
        return self::readCmap($path) !== null;
    }

    // ---- cmap reading --------------------------------------------------

    /** @param array{ranges:list<array{0:int,1:int}>} $chars */
    private static function covers(array $chars, int $cp): bool
    {
        foreach ($chars['ranges'] as [$lo, $hi]) {
            if ($cp >= $lo && $cp <= $hi) {
                return true;
            }
        }
        return false;
    }

    /**
     * Character ranges covered by the font's Unicode cmap (format 4 and 12).
     * Glyph ids are not checked for 0: a few missing characters never change the verdict.
     *
     * @return array{ranges:list<array{0:int,1:int}>}|null
     */
    private static function readCmap(string $path): ?array
    {
        $size = @filesize($path);
        if ($size === false || $size < 64 || $size > 8 * 1024 * 1024) {
            return null;
        }
        $data = @file_get_contents($path);
        if ($data === false) {
            return null;
        }
        $tag = substr($data, 0, 4);
        if (!in_array($tag, ["\x00\x01\x00\x00", 'OTTO', 'true'], true)) {
            return null; // .ttc collections are not supported for uploads
        }
        $numTables = self::u16($data, 4);
        if ($numTables === null || $numTables < 1 || $numTables > 64) {
            return null;
        }
        $cmapOff = null;
        for ($i = 0; $i < $numTables; $i++) {
            $rec = 12 + $i * 16;
            if (substr($data, $rec, 4) === 'cmap') {
                $cmapOff = self::u32($data, $rec + 8);
                break;
            }
        }
        if ($cmapOff === null) {
            return null;
        }
        $n = self::u16($data, $cmapOff + 2);
        if ($n === null || $n < 1 || $n > 64) {
            return null;
        }

        $ranges = [];
        $ok = false;
        for ($i = 0; $i < $n; $i++) {
            $platform = self::u16($data, $cmapOff + 4 + $i * 8);
            $encoding = self::u16($data, $cmapOff + 6 + $i * 8);
            $off      = self::u32($data, $cmapOff + 8 + $i * 8);
            if ($platform === null || $encoding === null || $off === null) {
                continue;
            }
            // Unicode tables only: platform 0, or Windows 3/1 (BMP) and 3/10 (full).
            if (!($platform === 0 || ($platform === 3 && ($encoding === 1 || $encoding === 10)))) {
                continue;
            }
            $sub = $cmapOff + $off;
            $format = self::u16($data, $sub);
            if ($format === 4) {
                $segX2 = self::u16($data, $sub + 6);
                if ($segX2 === null) {
                    continue;
                }
                $seg = intdiv($segX2, 2);
                $endBase = $sub + 14;
                $startBase = $endBase + $segX2 + 2;
                for ($s = 0; $s < $seg && $s < 5000; $s++) {
                    $end = self::u16($data, $endBase + $s * 2);
                    $start = self::u16($data, $startBase + $s * 2);
                    if ($end === null || $start === null) {
                        break;
                    }
                    if ($start <= $end && $start !== 0xFFFF) {
                        $ranges[] = [$start, $end];
                    }
                }
                $ok = true;
            } elseif ($format === 12) {
                $groups = self::u32($data, $sub + 12);
                if ($groups === null) {
                    continue;
                }
                for ($g = 0; $g < $groups && $g < 20000; $g++) {
                    $start = self::u32($data, $sub + 16 + $g * 12);
                    $end = self::u32($data, $sub + 20 + $g * 12);
                    if ($start === null || $end === null) {
                        break;
                    }
                    $ranges[] = [$start, $end];
                }
                $ok = true;
            }
        }
        return $ok ? ['ranges' => $ranges] : null;
    }

    private static function u16(string $d, int $o): ?int
    {
        return $o >= 0 && $o + 2 <= strlen($d) ? (ord($d[$o]) << 8) | ord($d[$o + 1]) : null;
    }

    private static function u32(string $d, int $o): ?int
    {
        return $o >= 0 && $o + 4 <= strlen($d)
            ? (ord($d[$o]) << 24) | (ord($d[$o + 1]) << 16) | (ord($d[$o + 2]) << 8) | ord($d[$o + 3])
            : null;
    }
}
