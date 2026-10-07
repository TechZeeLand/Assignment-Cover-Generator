<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

use App\Ads;
use App\Csrf;
use App\Html;
use App\Http;
use App\Settings;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requirePost();

    $pub = trim((string) ($_POST['adsense_publisher_id'] ?? ''));
    if ($pub !== '' && preg_match('/^pub-\d{8,20}$/', $pub)) {
        $pub = 'ca-' . $pub;
    }
    if ($pub !== '' && !preg_match('/^ca-pub-\d{8,20}$/', $pub)) {
        admin_flash('error', 'The publisher ID should look like ca-pub-1234567890123456.');
        admin_redirect('ads.php');
    }
    $values = [
        'ads_enabled'          => isset($_POST['ads_enabled']) ? '1' : '0',
        'ads_require_consent'  => isset($_POST['ads_require_consent']) ? '1' : '0',
        'adsense_publisher_id' => $pub,
    ];
    foreach (Ads::SLOTS as $slotKey) {
        $v = trim((string) ($_POST[$slotKey] ?? ''));
        if ($v !== '' && !preg_match('/^\d{6,20}$/', $v)) {
            admin_flash('error', 'Ad unit IDs are numbers only (e.g. 1234567890).');
            admin_redirect('ads.php');
        }
        $values[$slotKey] = $v;
    }
    $txt = str_replace("\r\n", "\n", (string) ($_POST['ads_txt'] ?? ''));
    $txt = trim($txt);
    if (strlen($txt) > 5000 || preg_match('/[^\x20-\x7E\n\t]/', $txt)) {
        admin_flash('error', 'ads.txt must be plain ASCII text (max 5000 characters).');
        admin_redirect('ads.php');
    }
    $values['ads_txt'] = $txt;

    Settings::setMany($values);
    admin_flash('ok', 'Ad settings saved.');
    admin_redirect('ads.php');
}

$effective = Ads::adsTxt();
admin_head('Ads & ads.txt', 'ads');
?>
<form method="post" class="panel">
    <?php echo Csrf::field(); ?>
    <h2>Google AdSense</h2>
    <?php echo admin_check('ads_enabled', 'Show ads on the site', Settings::bool('ads_enabled'), 'Nothing is shown until a valid publisher ID is saved. Ads are never placed inside the generated PDF.'); ?>
    <?php echo admin_check('ads_require_consent', 'Load ads only after the visitor accepts cookies', Settings::bool('ads_require_consent'), 'Recommended. Needs the cookie banner (Site settings). If you serve visitors in the EEA, UK or Switzerland, Google also requires a certified consent management platform for personalised ads &mdash; consider enabling Google\'s own consent message in your AdSense account.'); ?>

    <label for="pub">Publisher ID</label>
    <input type="text" id="pub" name="adsense_publisher_id" value="<?php echo Html::e(Settings::get('adsense_publisher_id')); ?>" placeholder="ca-pub-1234567890123456" autocomplete="off" spellcheck="false">

    <h3>Ad units</h3>
    <p class="muted">Create <em>Display ads</em> (responsive) in AdSense and paste each unit's numeric ID. Leave a field empty to skip that placement.</p>
    <div class="grid2">
        <div><label for="s1">Top &mdash; below the page intro</label>
            <input type="text" id="s1" name="ad_slot_top" value="<?php echo Html::e(Settings::get('ad_slot_top')); ?>" placeholder="1234567890" inputmode="numeric"></div>
        <div><label for="s2">Middle &mdash; between form sections</label>
            <input type="text" id="s2" name="ad_slot_middle" value="<?php echo Html::e(Settings::get('ad_slot_middle')); ?>" placeholder="1234567890" inputmode="numeric"></div>
        <div><label for="s3">Bottom &mdash; above the footer (all pages)</label>
            <input type="text" id="s3" name="ad_slot_bottom" value="<?php echo Html::e(Settings::get('ad_slot_bottom')); ?>" placeholder="1234567890" inputmode="numeric"></div>
    </div>

    <h3>ads.txt</h3>
    <p class="muted">Served at <a href="/ads.txt" target="_blank" rel="noopener"><?php echo Html::e(Http::baseUrl()); ?>/ads.txt</a>. Leave empty to generate the standard AdSense line from your publisher ID; paste your own lines (one per line) to override it, e.g. for other ad networks.</p>
    <textarea name="ads_txt" rows="6" spellcheck="false" placeholder="google.com, pub-1234567890123456, DIRECT, f08c47fec0942fa0"><?php echo Html::e(Settings::get('ads_txt')); ?></textarea>
    <p class="hint">Currently serving: <?php echo $effective === '' ? '<em>nothing (404)</em>' : '<code>' . nl2br(Html::e(trim($effective))) . '</code>'; ?></p>

    <p><button type="submit" class="btn primary">Save</button></p>
</form>
<?php admin_foot();
