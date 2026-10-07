<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

use App\Csrf;
use App\Html;
use App\Users;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requirePost();
    $id = (int) ($_POST['delete'] ?? 0);
    if ($id > 0) {
        Users::delete($id);
        admin_flash('ok', 'User and their saved details were deleted.');
    }
    admin_redirect('users.php');
}

$q = trim((string) ($_GET['q'] ?? ''));
$perPage = 25;

if (isset($_GET['export'])) {
    $all = Users::search($q, 100000, 0);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="users.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['name', 'email', 'first_sign_in', 'last_sign_in', 'sign_ins', 'has_saved_details']);
    foreach ($all['rows'] as $r) {
        // Prefix cells that spreadsheets would execute as formulas.
        $safe = static fn (string $v): string => preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v;
        fputcsv($out, [$safe((string) $r['name']), $safe((string) $r['email']), gmdate('c', (int) $r['created_at']),
            gmdate('c', (int) $r['last_login_at']), $r['login_count'], $r['has_profile'] ? 'yes' : 'no']);
    }
    exit;
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$res = Users::search($q, $perPage, ($page - 1) * $perPage);
$pages = max(1, (int) ceil($res['total'] / $perPage));

admin_head('Users', 'users');
?>
<section class="panel">
    <form method="get" class="inline">
        <input type="search" name="q" value="<?php echo Html::e($q); ?>" placeholder="Search name or email">
        <button class="btn" type="submit">Search</button>
        <a class="btn" href="?export=1&amp;q=<?php echo rawurlencode($q); ?>">Export CSV</a>
    </form>
    <p class="muted"><?php echo $res['total']; ?> user<?php echo $res['total'] === 1 ? '' : 's'; ?>. Only a name and email are stored for each person.</p>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Name</th><th>Email</th><th>First sign-in</th><th>Last sign-in</th><th class="num">Sign-ins</th><th>Saved</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($res['rows'] as $r): ?>
            <tr>
                <td><?php echo Html::e($r['name']); ?></td>
                <td><?php echo Html::e($r['email']); ?></td>
                <td><?php echo gmdate('Y-m-d', (int) $r['created_at']); ?></td>
                <td><?php echo gmdate('Y-m-d H:i', (int) $r['last_login_at']); ?> UTC</td>
                <td class="num"><?php echo (int) $r['login_count']; ?></td>
                <td><?php echo $r['has_profile'] ? '<span class="pill ok">Yes</span>' : '<span class="pill off">No</span>'; ?></td>
                <td>
                    <form method="post" onsubmit="return confirm('Delete this user and their saved details?');">
                        <?php echo Csrf::field(); ?>
                        <input type="hidden" name="delete" value="<?php echo (int) $r['id']; ?>">
                        <button type="submit" class="btn danger sm">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$res['rows']): ?><tr><td colspan="7" class="muted">No users yet.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
    <?php if ($pages > 1): ?>
    <p class="pager">
        <?php if ($page > 1): ?><a class="btn sm" href="?q=<?php echo rawurlencode($q); ?>&amp;page=<?php echo $page - 1; ?>">&larr; Previous</a><?php endif; ?>
        Page <?php echo $page; ?> of <?php echo $pages; ?>
        <?php if ($page < $pages): ?><a class="btn sm" href="?q=<?php echo rawurlencode($q); ?>&amp;page=<?php echo $page + 1; ?>">Next &rarr;</a><?php endif; ?>
    </p>
    <?php endif; ?>
</section>
<?php admin_foot();
