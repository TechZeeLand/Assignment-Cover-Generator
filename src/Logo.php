<?php

declare(strict_types=1);

namespace App;

/**
 * University logo handling. The browser downsizes the picture and sends it
 * as a data: URI; here it is validated and re-encoded through GD, which
 * discards metadata and anything that isn't genuinely an image.
 */
final class Logo
{
    public const MAX_DATA_URI_BYTES = 700000;
    private const MAX_PIXELS = 12000000;
    private const MAX_SIDE = 600;

    /** @return array{uri:string,w:int,h:int}|null */
    public static function fromDataUri(mixed $value): ?array
    {
        if (!is_string($value) || $value === '' || strlen($value) > self::MAX_DATA_URI_BYTES) {
            return null;
        }
        if (!preg_match('#^data:image/(?:png|jpeg|jpg|webp|gif);base64,([A-Za-z0-9+/=\r\n]+)$#', $value, $m)) {
            return null;
        }
        if (!function_exists('imagecreatefromstring')) {
            return null;
        }
        $bin = base64_decode($m[1], true);
        if ($bin === false || $bin === '') {
            return null;
        }
        $info = @getimagesizefromstring($bin);
        if (!$info || $info[0] < 1 || $info[1] < 1 || $info[0] * $info[1] > self::MAX_PIXELS) {
            return null;
        }
        $src = @imagecreatefromstring($bin);
        if ($src === false) {
            return null;
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $scale = min(1.0, self::MAX_SIDE / max($w, $h));
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 255, 255, 255, 127));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        ob_start();
        imagepng($dst, null, 6);
        $png = (string) ob_get_clean();
        imagedestroy($src);
        imagedestroy($dst);

        if ($png === '') {
            return null;
        }
        return ['uri' => 'data:image/png;base64,' . base64_encode($png), 'w' => $nw, 'h' => $nh];
    }
}
