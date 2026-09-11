<?php
declare(strict_types=1);
$currentYear = date('Y');
?>
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
                </ul>
            </nav>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo htmlspecialchars($currentYear, ENT_QUOTES); ?> TechZeeLand. Assignment Cover Generator is free &amp; open source under the <a href="https://www.gnu.org/licenses/agpl-3.0.html" target="_blank" rel="noopener">AGPL-3.0</a> license.</p>
            <p><a href="https://github.com/TechZeeLand" target="_blank" rel="noopener">Made by TechZeeLand</a></p>
        </div>
    </div>
</footer>

<script src="/assets/js/theme.js"></script>
