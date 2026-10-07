<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

use App\Ads;
use App\Auth;
use App\Html;
use App\Settings;
use App\Stats;
use App\Templates\TemplateRegistry;
use App\Users;

$now = time();
$totals = [
    'users' => Users::total(),
    'signins' => Users::signInsTotal(),
    'new7' => Users::createdSince($now - 7 * 86400),
    'new30' => Users::createdSince($now - 30 * 86400),
    'active1' => Users::activeSince($now - 86400),
    'active7' => Users::activeSince($now - 7 * 86400),
    'active30' => Users::activeSince($now - 30 * 86400),
    'saved' => Users::withProfile(),
    'pdf' => Stats::total('pdf'),
    'pdf30' => Stats::total('pdf', 30),
];
$pdfSeries = Stats::series('pdf', 30);
$signinSeries = Stats::series('signin', 30);
$maxPdf = max(1, max($pdfSeries));
$maxSign = max(1, max($signinSeries));
$usage = Stats::designUsage();
arsort($usage);
$designs = TemplateRegistry::all();
$active = TemplateRegistry::active();

$checks = [
    ['Admin password', true, 'Set.'],
    ['Site URL', Settings::get('site_url') !== '', 'Set it in Site settings so the Google redirect URI is exact.'],
    ['Google sign-in', Auth::signInAvailable(), 'Add the client ID and secret under Sign-in (Google).'],
    ['Ads', Ads::enabled(), 'Optional: add your AdSense publisher ID and turn ads on.'],
    ['ads.txt', Ads::adsTxt() !== '', 'Add a publisher ID (or paste your own lines).'],
    ['Cookie banner', !Ads::enabled() || !Ads::requiresConsent() || Settings::bool('cookie_banner_enabled'), 'Ads need the banner when consent is required.'],
];

admin_head('Dashboard', 'dashboard');
?>
<div class="cards">
    <div class="stat"><span><?php echo $totals['users']; ?></span>Users who signed in</div>
    <div class="stat"><span><?php echo $totals['signins']; ?></span>Total sign-ins</div>
    <div class="stat"><span><?php echo $totals['active1']; ?> / <?php echo $totals['active7']; ?> / <?php echo $totals['active30']; ?></span>Active 24h / 7d / 30d</div>
    <div class="stat"><span><?php echo $totals['new7']; ?> / <?php echo $totals['new30']; ?></span>New users 7d / 30d</div>
    <div class="stat"><span><?php echo $totals['saved']; ?></span>Users with saved details</div>
    <div class="stat"><span><?php echo $totals['pdf']; ?></span>PDFs generated (<?php echo $totals['pdf30']; ?> in 30d)</div>
</div>

<section class="panel">
    <h2>Last 30 days</h2>
    <div class="legend"><i class="dot pdf"></i> PDFs generated <i class="dot sign"></i> Sign-ins</div>
    <div class="chart" role="img" aria-label="Daily PDFs and sign-ins for the last 30 days">
        <?php foreach ($pdfSeries as $day => $n): $s = $signinSeries[$day] ?? 0; ?>
        <div class="col" title="<?php echo Html::e($day); ?>: <?php echo $n; ?> PDFs, <?php echo $s; ?> sign-ins">
            <i class="bar pdf" style="height:<?php echo round($n / $maxPdf * 100); ?>%"></i>
            <i class="bar sign" style="height:<?php echo round($s / $maxSign * 100); ?>%"></i>
        </div>
        <?php endforeach; ?>
    </div>
    <p class="hint">Counters are anonymous: no IP addresses or user IDs are stored.</p>
</section>

<section class="panel">
    <h2>Design usage (all time)</h2>
    <table>
        <thead><tr><th>Design</th><th>Status</th><th class="num">PDFs</th></tr></thead>
        <tbody>
        <?php foreach ($designs as $key => $design): ?>
            <tr>
                <td><?php echo Html::e($design->label()); ?> <small class="muted"><?php echo Html::e($design->category()); ?></small></td>
                <td><?php echo isset($active[$key]) ? '<span class="pill ok">Active</span>' : '<span class="pill off">Hidden</span>'; ?></td>
                <td class="num"><?php echo (int) ($usage[$key] ?? 0); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="panel">
    <h2>Setup checklist</h2>
    <ul class="checklist">
        <?php foreach ($checks as [$label, $ok, $hint]): ?>
        <li class="<?php echo $ok ? 'ok' : 'todo'; ?>"><i class="fa-solid <?php echo $ok ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
            <strong><?php echo Html::e($label); ?></strong> <span class="muted"><?php echo $ok ? '' : Html::e($hint); ?></span></li>
        <?php endforeach; ?>
    </ul>
</section>
<?php admin_foot();
