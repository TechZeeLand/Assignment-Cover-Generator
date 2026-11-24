<?php
/**
 * Self-test: lints every PHP file, then requests key pages through the real front controller
 * (in throw-away data) and reports status codes and PHP errors.
 *   docker compose exec <container> php bin/selftest.php            (or: php bin/selftest.php)
 *   php bin/selftest.php /extra-path /another-path                  (add your own paths)
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root = dirname(__DIR__);
$bad = 0;

echo "== 1. php -l on every file\n";
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
$n = 0;
foreach ($it as $f) {
    $p = (string)$f;
    if (substr($p, -4) !== '.php' || strpos($p, '/vendor/') !== false || strpos($p, '/data/') !== false) { continue; }
    $out = []; $rc = 0;
    exec('php -l ' . escapeshellarg($p) . ' 2>&1', $out, $rc);
    $n++;
    if ($rc !== 0) { $bad++; echo "  FAIL " . substr($p, strlen($root) + 1) . ': ' . implode(' ', $out) . "\n"; }
}
echo "  checked $n files\n";

echo "== 2. requests through public/index.php\n";
$paths = array_merge(['/', '/about', '/contact', '/privacy-policy', '/terms-and-conditions', '/cookie-policy', '/robots.txt', '/sitemap.xml', '/health', '/manifest.webmanifest', '/admin/login', '/does-not-exist'], array_slice($argv, 1));
$tmp = sys_get_temp_dir() . '/tzl-selftest-' . getmypid();
@mkdir($tmp, 0775, true);
$runner = $tmp . '/run.php';
file_put_contents($runner, '<?php
$_SERVER["REQUEST_METHOD"]="GET"; $_SERVER["REQUEST_URI"]=getenv("TZL_PATH"); $_SERVER["HTTP_HOST"]="localhost"; $_SERVER["REMOTE_ADDR"]="127.0.0.1";
register_shutdown_function(function(){ fwrite(STDERR, "\n__STATUS__".(http_response_code() ?: 200)); });
chdir(' . var_export($root . '/public', true) . ');
require ' . var_export($root . '/public/index.php', true) . ';
');
foreach ($paths as $p) {
    $desc = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $proc = proc_open([PHP_BINARY, '-d', 'display_errors=1', '-d', 'error_reporting=E_ALL', $runner], $desc, $pipes, $root, ['TZL_PATH' => $p, 'TZL_DATA_DIR' => $tmp . '/data', 'PATH' => getenv('PATH')]);
    $out = stream_get_contents($pipes[1]); $err = stream_get_contents($pipes[2]);
    proc_close($proc);
    preg_match('/__STATUS__(\d+)/', $err, $m);
    $status = (int)($m[1] ?? 0);
    $php = preg_match('/(Fatal error|Parse error|Warning|Notice|Deprecated|Uncaught)[^\n]*/i', $out . $err, $pm) ? $pm[0] : '';
    $expect = ($p === '/does-not-exist') ? 404 : 200;
    $ok = $status === $expect && $php === '';
    if (!$ok) { $bad++; }
    printf("  %-5s %-28s HTTP %s %s\n", $ok ? 'ok' : 'FAIL', $p, $status ?: '?', $php);
}
function rrm($d) { foreach (glob($d . '/{,.}[!.]*', GLOB_BRACE) ?: [] as $x) { is_dir($x) ? rrm($x) : @unlink($x); } @rmdir($d); }
rrm($tmp);
echo "== 3. fonts (script detection, per-field fonts, Bangla fallback)\n";
$autoload = $root . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
    $check = static function (string $name, bool $ok) use (&$bad): void {
        if (!$ok) { $bad++; }
        printf("  %-5s %s\n", $ok ? 'ok' : 'FAIL', $name);
    };
    try {
        $fontsDir = $root . '/public/assets/fonts';
        $check('Alata is detected as Latin', \App\FontScripts::detect($fontsDir . '/Alata-Regular.ttf') === ['latin']);
        $check('Amiri is detected as Arabic', in_array('arabic', \App\FontScripts::detect($fontsDir . '/Amiri-Regular.ttf'), true));
        $check('garbage is not accepted as a font', !\App\FontScripts::isReadable(__FILE__));

        $d = (new ReflectionClass(\App\CoverData::class))->newInstanceWithoutConstructor();
        $d->primaryFont = 'alata'; $d->secondaryFont = 'alata'; $d->versityFont = 'alata';
        $d->fontScripts = ['alata' => ['latin'], 'bnfont' => ['latin', 'bengali']];
        $check('no override: text is left untouched', $d->fv('topic', 'Hello') === 'Hello');
        $d->fontOverrides = ['topic' => 'bnfont'];
        $check('override wraps the field in its font', $d->fv('topic', 'Hello') === '<span style="font-family:bnfont;">Hello</span>');
        $d->fontOverrides = [];
        $check('Bangla in a Latin-only font gets a fallback font', str_contains($d->fv('topic', 'আমার'), 'font-family:freeserif'));
        $d->fontOverrides = ['topic' => 'bnfont'];
        $check('Bangla in a Bangla font keeps the chosen font only', !str_contains($d->fv('topic', 'আমার'), 'freeserif'));
        $check('HTML tags are never touched by the fallback', str_contains((new ReflectionClass($d))->getMethod('fv')->invoke($d, 'student-name', '<b>x</b>'), '<b>x</b>'));
    } catch (Throwable $e) {
        $bad++;
        echo '  FAIL font checks crashed: ' . $e->getMessage() . "\n";
    }
} else {
    echo "  skipped (run composer install first)\n";
}
echo $bad ? "\n$bad problem(s). Send the FAIL lines to your assistant.\n" : "\nAll good.\n";
exit($bad ? 1 : 0);
