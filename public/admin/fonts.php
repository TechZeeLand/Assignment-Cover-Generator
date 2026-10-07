<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

use App\Csrf;
use App\FontManager;
use App\Html;

$fonts = new FontManager();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requirePost();
    $key = (string) ($_POST['delete'] ?? '');
    if ($key !== '' && preg_match('/^[a-z0-9\-]{1,40}$/', $key) && $fonts->deleteFont($key)) {
        admin_flash('ok', 'Font deleted. Saved covers that used it will fall back to a default font.');
    } else {
        admin_flash('error', 'Could not delete that font.');
    }
    admin_redirect('fonts.php');
}

$custom = $fonts->listCustom();
admin_head('Custom fonts', 'fonts');
?>
<section class="panel">
    <p class="muted">Fonts uploaded by visitors are shared with everyone. Remove anything you don't want to host. Uploads can be switched off in <a href="/admin/settings.php">Site settings</a>.</p>
    <table>
        <thead><tr><th>Name</th><th>Key</th><th>Files</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($custom as $f): ?>
            <tr>
                <td><?php echo Html::e($f['name']); ?></td>
                <td><code><?php echo Html::e($f['key']); ?></code></td>
                <td><?php echo Html::e(implode(', ', array_keys($f['files']))); ?></td>
                <td>
                    <form method="post" onsubmit="return confirm('Delete this font?');">
                        <?php echo Csrf::field(); ?>
                        <input type="hidden" name="delete" value="<?php echo Html::e($f['key']); ?>">
                        <button type="submit" class="btn danger sm">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$custom): ?><tr><td colspan="4" class="muted">No custom fonts uploaded.</td></tr><?php endif; ?>
        </tbody>
    </table>
</section>
<?php admin_foot();
