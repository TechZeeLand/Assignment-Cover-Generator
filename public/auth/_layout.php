<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Shared page frame for the account pages (sign in, register, password).
 * head.php / topbar.php expect $pageTitle etc. in the including scope, so the
 * variables are set here, inside the function that includes them.
 */
function auth_head(string $title): void
{
    $pageTitle = $title . ' — Assignment Cover Generator';
    $pageDescription = $title . ' — Assignment Cover Generator';
    $acgNoAds = true;
    require __DIR__ . '/../partials/head.php';
    echo "<body>\n";
    require __DIR__ . '/../partials/topbar.php';
    echo '<main id="main-content" class="content-wrap auth-wrap">';
}

function auth_foot(): void
{
    echo '</main>';
    require __DIR__ . '/../partials/footer.php';
    echo "</body>\n</html>";
}
