<?php
use App\Auth as TopbarAuth;
use App\Csrf as TopbarCsrf;
use App\Html as TopbarHtml;

$acgUser = $acgUser ?? TopbarAuth::user();
$acgReturn = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$acgReturn = ($acgReturn !== '' && $acgReturn[0] === '/' && !str_starts_with($acgReturn, '//')) ? $acgReturn : '/';
$acgSiteName = $siteName ?? 'Assignment Cover Generator';
?>
<?php if (\App\Settings::bool('announcement_enabled') && trim(\App\Settings::get('announcement_text')) !== ''): ?>
<div class="announcement" role="status"><?php echo TopbarHtml::e(\App\Settings::get('announcement_text')); ?></div>
<?php endif; ?>
<header class="topbar">
    <div class="topbar-inner">
        <div class="site-title">
            <a class="brand-link" href="/index.php">
                <img class="brand-logo" src="/assets/img/logo.svg" alt="Assignment Cover Generator logo" width="26" height="26">
                <span><?php echo TopbarHtml::e($acgSiteName); ?></span>
            </a>
        </div>
        <div class="topbar-actions">
            <a class="gh-link" href="https://github.com/TechZeeLand/Assignment-Cover-Generator" target="_blank" rel="noopener" id="gh-link">
                <?php echo \App\Icon::svg('github'); ?>
                <span>Source on GitHub</span>
            </a>
            <?php if ($acgUser !== null): ?>
            <details class="user-menu">
                <summary aria-label="Account menu">
                    <span class="user-avatar" aria-hidden="true"><?php echo TopbarHtml::e(mb_strtoupper(mb_substr($acgUser['name'], 0, 1))); ?></span>
                    <span class="user-name"><?php echo TopbarHtml::e(explode(' ', $acgUser['name'])[0]); ?></span>
                </summary>
                <div class="user-pop">
                    <p class="user-pop-name"><?php echo TopbarHtml::e($acgUser['name']); ?></p>
                    <p class="user-pop-email"><?php echo TopbarHtml::e($acgUser['email']); ?></p>
                    <form method="post" action="/auth/logout.php">
                        <?php echo TopbarCsrf::field(); ?>
                        <input type="hidden" name="return" value="<?php echo TopbarHtml::e($acgReturn); ?>">
                        <button type="submit" class="user-signout"><?php echo \App\Icon::svg('logout'); ?> Sign out</button>
                    </form>
                    <form method="post" action="/auth/delete-account.php" onsubmit="return confirm('Delete your account and all saved details? This cannot be undone.');">
                        <?php echo TopbarCsrf::field(); ?>
                        <button type="submit" class="user-delete">Delete my account &amp; data</button>
                    </form>
                </div>
            </details>
            <?php elseif (TopbarAuth::signInAvailable()): ?>
            <a class="google-btn" href="/auth/google.php?return=<?php echo rawurlencode($acgReturn); ?>">
                <?php echo \App\Icon::svg('google'); ?>
                <span>Sign in</span>
            </a>
            <?php endif; ?>
            <button type="button" class="theme-toggle" id="theme-toggle" aria-pressed="false" aria-label="Switch to dark mode" title="Switch theme">
                <?php echo \App\Icon::svg('sun'); ?>
                <?php echo \App\Icon::svg('moon'); ?>
            </button>
        </div>
    </div>
</header>
