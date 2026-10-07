<?php
declare(strict_types=1);
$currentYear = date('Y');
$footerSite = \App\Settings::get('site_name') ?: 'Assignment Cover Generator';
?>
<?php if (empty($acgNoAds)) { echo \App\Ads::slot('bottom'); } /* never show ads on error / empty pages (AdSense policy) */ ?>
<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-cols">
            <nav class="footer-col" aria-label="Site pages">
                <h3>Pages</h3>
                <ul class="footer-links">
                    <li><a href="/index.php">Home</a></li>
                    <li><a href="/about.php">About</a></li>
                    <li><a href="/contact.php">Contact</a></li>
                    <li><a href="/privacy-policy.php">Privacy Policy</a></li>
                    <li><a href="/terms-and-conditions.php">Terms &amp; Conditions</a></li>
                    <li><a href="/cookie-policy.php">Cookie Policy</a></li>
                    <li><button type="button" class="link-button" data-cookie-settings>Cookie settings</button></li>
                </ul>
            </nav>
            <nav class="footer-col" aria-label="Social links">
                <h3>Connect</h3>
                <ul class="social-links">
                    <li><a href="https://www.youtube.com/@TechZeeLand" target="_blank" rel="noopener"><i class="fa-brands fa-youtube" aria-hidden="true"></i> YouTube</a></li>
                    <li><a href="https://www.facebook.com/TechZeeLand" target="_blank" rel="noopener"><i class="fa-brands fa-facebook" aria-hidden="true"></i> Facebook</a></li>
                    <li><a href="https://www.instagram.com/TechZeeLand" target="_blank" rel="noopener"><i class="fa-brands fa-instagram" aria-hidden="true"></i> Instagram</a></li>
                    <li><a href="https://www.tiktok.com/@TechZeeLand" target="_blank" rel="noopener"><i class="fa-brands fa-tiktok" aria-hidden="true"></i> TikTok</a></li>
                    <li><a href="https://github.com/TechZeeLand" target="_blank" rel="noopener"><i class="fa-brands fa-github" aria-hidden="true"></i> GitHub</a></li>
                    <li><a href="https://pathaopay.me/@azlanaziz" target="_blank" rel="noopener"><span class="icon-box" aria-hidden="true"><span class="pathaopay-icon"></span></span> Support</a></li>
                </ul>
            </nav>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo htmlspecialchars($currentYear, ENT_QUOTES); ?> TechZeeLand. Assignment Cover Generator is free &amp; open source under the <a href="https://www.gnu.org/licenses/agpl-3.0.html" target="_blank" rel="noopener">AGPL-3.0</a> license.</p>
            <p><a href="https://github.com/TechZeeLand" target="_blank" rel="noopener">Made by TechZeeLand</a></p>
        </div>
    </div>
</footer>

<div id="cookie-banner" class="cookie-banner" role="dialog" aria-live="polite" aria-label="Cookie preferences" hidden>
    <p>We use a few essential cookies to keep you signed in, and &mdash; only if you agree &mdash; Google AdSense cookies to show ads that help keep this free. See our <a href="/cookie-policy.php">Cookie Policy</a>.</p>
    <div class="cookie-actions">
        <button type="button" class="btn btn-secondary" data-consent="rejected">Essential only</button>
        <button type="button" class="btn btn-primary" data-consent="accepted">Accept all</button>
    </div>
</div>

<script src="/assets/js/theme.js"></script>
<script src="/assets/js/consent.js"></script>
