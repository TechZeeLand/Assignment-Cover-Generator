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
        <h1>
            <a class="brand-link" href="/index.php">
                <img class="brand-logo" src="/assets/img/logo.svg" alt="Assignment Cover Generator logo" width="26" height="26">
                <span><?php echo TopbarHtml::e($acgSiteName); ?></span>
            </a>
        </h1>
        <div class="topbar-actions">
            <a class="gh-link" href="https://github.com/TechZeeLand/Assignment-Cover-Generator" target="_blank" rel="noopener" id="gh-link">
                <svg viewBox="0 0 16 16" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.01 8.01 0 0 0 16 8c0-4.42-3.58-8-8-8Z"/></svg>
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
                        <button type="submit" class="user-signout"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i> Sign out</button>
                    </form>
                    <form method="post" action="/auth/delete-account.php" onsubmit="return confirm('Delete your account and all saved details? This cannot be undone.');">
                        <?php echo TopbarCsrf::field(); ?>
                        <button type="submit" class="user-delete">Delete my account &amp; data</button>
                    </form>
                </div>
            </details>
            <?php elseif (TopbarAuth::signInAvailable()): ?>
            <a class="google-btn" href="/auth/google.php?return=<?php echo rawurlencode($acgReturn); ?>">
                <svg viewBox="0 0 48 48" width="18" height="18" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9.1 3.6l6.8-6.8C35.8 2.4 30.3 0 24 0 14.6 0 6.5 5.4 2.6 13.2l7.9 6.1C12.4 13.6 17.7 9.5 24 9.5z"/><path fill="#4285F4" d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v9h12.7c-.6 3-2.3 5.5-4.8 7.2l7.6 5.9c4.4-4.1 7-10.1 7-17.6z"/><path fill="#FBBC05" d="M10.5 28.7a14.5 14.5 0 0 1 0-9.4l-7.9-6.1a24 24 0 0 0 0 21.6l7.9-6.1z"/><path fill="#34A853" d="M24 48c6.5 0 11.9-2.1 15.9-5.8l-7.6-5.9c-2.1 1.4-4.9 2.3-8.3 2.3-6.3 0-11.6-4.1-13.5-9.8l-7.9 6.1C6.5 42.6 14.6 48 24 48z"/></svg>
                <span>Sign in</span>
            </a>
            <?php endif; ?>
            <button type="button" class="theme-toggle" id="theme-toggle" aria-pressed="false" aria-label="Switch to dark mode" title="Switch theme">
                <i class="fa-solid fa-sun" aria-hidden="true"></i>
                <i class="fa-solid fa-moon" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</header>
