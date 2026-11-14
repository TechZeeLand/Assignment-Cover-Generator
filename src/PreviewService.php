<?php

declare(strict_types=1);

namespace App;

/**
 * Turns the generated PDF into a PNG of page 1 for the live preview, using
 * poppler's `pdftoppm` (installed in the Docker image). Because the picture is
 * made from the very same PDF that "Generate PDF" produces, the preview can
 * never disagree with the download. When pdftoppm is missing the preview is
 * simply not offered.
 */
final class PreviewService
{
    private const DPI = 110;
    private const TIMEOUT_SECONDS = 15;
    private const MAX_PER_MINUTE = 45;

    /** Absolute path of pdftoppm, or null. PDFTOPPM_PATH overrides the search. */
    public static function binary(): ?string
    {
        $candidates = [getenv('PDFTOPPM_PATH') ?: '', '/usr/bin/pdftoppm', '/usr/local/bin/pdftoppm'];
        foreach ($candidates as $path) {
            if (is_string($path) && $path !== '' && is_file($path) && is_executable($path)) {
                return $path;
            }
        }
        return null;
    }

    public static function available(): bool
    {
        return function_exists('proc_open') && self::binary() !== null;
    }

    /** Per-IP limit (the preview is the one expensive public endpoint). Counts this request. */
    public static function throttled(): bool
    {
        try {
            $pdo = Db::pdo();
            $key = 'p:' . Http::clientIp();
            $pdo->prepare('DELETE FROM login_attempts WHERE ts < :t')->execute([':t' => time() - 900]);
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip = :k AND ts > :t');
            $stmt->execute([':k' => $key, ':t' => time() - 60]);
            if ((int) $stmt->fetchColumn() >= self::MAX_PER_MINUTE) {
                return true;
            }
            $pdo->prepare('INSERT INTO login_attempts (ip, ts) VALUES (:k, :t)')->execute([':k' => $key, ':t' => time()]);
        } catch (\Throwable $e) {
            error_log('[assignment-cover-generator] preview throttle: ' . $e->getMessage());
        }
        return false;
    }

    /** PNG bytes of page 1, or null on any failure. */
    public static function pngFromPdf(string $pdf): ?string
    {
        $bin = self::binary();
        if ($bin === null || !function_exists('proc_open')) {
            return null;
        }
        $base = rtrim(Config::tempPath(), '/') . '/preview-' . bin2hex(random_bytes(8));
        $pdfFile = $base . '.pdf';
        $pngFile = $base . '.png';

        try {
            if (file_put_contents($pdfFile, $pdf) === false) {
                return null;
            }
            // Argument array: no shell, nothing from the request ever reaches a command line.
            $proc = proc_open(
                [$bin, '-png', '-r', (string) self::DPI, '-f', '1', '-l', '1', '-singlefile', $pdfFile, $base],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes
            );
            if (!is_resource($proc)) {
                return null;
            }
            foreach ($pipes as $pipe) {
                stream_set_blocking($pipe, false);
            }
            $deadline = time() + self::TIMEOUT_SECONDS;
            while (proc_get_status($proc)['running']) {
                if (time() > $deadline) {
                    proc_terminate($proc, 9);
                    break;
                }
                usleep(20000);
            }
            foreach ($pipes as $pipe) {
                fclose($pipe);
            }
            proc_close($proc);

            $png = is_file($pngFile) ? file_get_contents($pngFile) : false;
            return is_string($png) && $png !== '' ? $png : null;
        } finally {
            @unlink($pdfFile);
            @unlink($pngFile);
        }
    }
}
