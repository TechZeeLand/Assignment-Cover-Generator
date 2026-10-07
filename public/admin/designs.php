<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

use App\Csrf;
use App\Html;
use App\Settings;
use App\Templates\TemplateRegistry;

$all = TemplateRegistry::all();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requirePost();
    $selected = array_values(array_intersect(array_keys($all), (array) ($_POST['active'] ?? [])));
    if ($selected === []) {
        admin_flash('error', 'At least one design must stay active.');
        admin_redirect('designs.php');
    }
    $default = (string) ($_POST['default'] ?? '');
    if (!in_array($default, $selected, true)) {
        $default = $selected[0];
    }
    Settings::setMany([
        // "Everything selected" is stored as empty so designs added in future updates appear automatically.
        'active_designs' => count($selected) === count($all) ? '' : (string) json_encode($selected),
        'default_design' => $default,
    ]);
    admin_flash('ok', 'Designs saved.');
    admin_redirect('designs.php');
}

$active = TemplateRegistry::active();
$default = TemplateRegistry::defaultKey();

admin_head('Designs', 'designs');
?>
<form method="post" class="panel">
    <?php echo Csrf::field(); ?>
    <p class="muted">Choose which cover designs visitors can pick, and which one is selected by default. Hidden designs stay in the code and can be switched back on any time.</p>
    <div class="design-admin" style="--t-accent:#111;--t-primary:#0384fc;--t-secondary:#fc9803;">
        <?php foreach ($all as $key => $design): ?>
        <div class="dcard">
            <div class="thumb"><?php echo $design->thumbnailSvg(); ?></div>
            <strong><?php echo Html::e($design->label()); ?></strong>
            <small class="muted"><?php echo Html::e($design->category()); ?></small>
            <p class="hint"><?php echo Html::e($design->description()); ?></p>
            <label class="check"><input type="checkbox" name="active[]" value="<?php echo Html::e($key); ?>"<?php echo isset($active[$key]) ? ' checked' : ''; ?>> <span>Active</span></label>
            <label class="check"><input type="radio" name="default" value="<?php echo Html::e($key); ?>"<?php echo $key === $default ? ' checked' : ''; ?>> <span>Default</span></label>
        </div>
        <?php endforeach; ?>
    </div>
    <p><button type="submit" class="btn primary">Save designs</button></p>
</form>
<?php admin_foot();
