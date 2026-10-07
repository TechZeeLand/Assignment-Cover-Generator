<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\Auth;
use App\GoogleOAuth;
use App\Http;
use App\Stats;
use App\Users;

$error = null;
try {
    if (!Auth::signInAvailable()) {
        throw new \RuntimeException('Sign-in is currently turned off.');
    }
    $result = GoogleOAuth::finish($_GET);
    $userId = Users::upsert($result['email'], $result['name']);
    Auth::login($userId);
    Stats::hit('signin');
    Http::redirect($result['return']);
} catch (\RuntimeException $e) {
    $error = $e->getMessage();
} catch (\Throwable $e) {
    error_log('[assignment-cover-generator] sign-in failed: ' . $e->getMessage());
    $error = 'Something went wrong while signing you in. Please try again.';
}

$pageTitle = 'Sign-in problem';
$pageDescription = 'Sign-in problem';
require __DIR__ . '/../partials/head.php';
?>
<body>
<?php require __DIR__ . '/../partials/topbar.php'; ?>
<main id="main-content" class="content-wrap">
    <h1>We couldn't sign you in</h1>
    <div class="content-card">
        <p><?php echo App\Html::e($error); ?></p>
        <p>Signing in is optional &mdash; you can keep using the generator without it.</p>
        <p><a class="btn btn-primary" href="/">Back to the generator</a></p>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
