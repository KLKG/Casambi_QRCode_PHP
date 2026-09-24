<?php
/**
 * Control page for one QR code: optional photo, then the configured elements in order.
 *
 * @var string     $code
 * @var array|null $entry     row from qrGetCode() or null when unknown
 * @var list<array> $elements rows from qrElements()
 */
$title = $entry !== null && $entry['name'] !== '' ? (string) $entry['name'] : $code;
?>
<section class="startscreen bg-primary text-white mb-0" id="control">
    <div class="container">
        <h2 class="page-section-heading text-center text-uppercase text-white"><?= e($title) ?></h2>
        <div class="divider-custom divider-light">
            <div class="divider-custom-line"></div>
            <div class="divider-custom-icon"><?= icon('sliders') ?></div>
            <div class="divider-custom-line"></div>
        </div>
        <?php require APP_ROOT . '/src/templates/layout/flash.php'; ?>

        <div class="text-center mt-4">
        <?php if ($entry === null): ?>
            <p class="lead"><?= e(t('No valid code found.')) ?></p>
            <div class="d-flex flex-wrap justify-content-center gap-3 mt-4">
                <a class="btn btn-xl btn-outline-light" href="index.php?site=scan"><?= icon('camera') ?> <?= e(t('Scan again')) ?></a>
                <a class="btn btn-xl btn-outline-light" href="index.php?site=code"><?= icon('keyboard') ?> <?= e(t('Enter Code')) ?></a>
            </div>
        <?php else: ?>
            <?php if ((int) $entry['has_image'] > 0): ?>
            <img class="app-photo" src="image.php?code=<?= e($code) ?>" alt="<?= e($title) ?>">
            <?php endif; ?>

            <p class="app-control-meta"><?= e(t('Code')) ?> <?= e($code) ?> &middot; <?= e(t('Lithernet ID')) ?> <?= (int) $entry['lithernet_id'] ?></p>
            <p class="app-control-status" id="control-status" aria-live="polite">
                <?= isDemoMode() ? e(t('Demo mode: values are stored, nothing is sent to the gateway.')) : '' ?>
            </p>
            <noscript><p class="app-flash app-flash-error"><?= e(t('JavaScript is required to send commands.')) ?></p></noscript>

            <?php if ($elements === []): ?>
            <p class="lead"><?= e(t('No controls configured for this code yet.')) ?></p>
            <?php endif; ?>

            <?php foreach ($elements as $element):
                $def   = elementTypeDef((string) $element['element_type']);
                if ($def === null) { continue; }
                $state = elementState($element);
                $id    = (int) $element['id'];
            ?>
            <form class="app-element" data-control action="control.php" method="post">
                <?= csrfField() ?>
                <input type="hidden" name="code" value="<?= e($code) ?>">
                <input type="hidden" name="element" value="<?= $id ?>">
                <h3 class="app-element-title"><?= e($element['name']) ?></h3>

                <?php if ($def['kind'] === 'slider'): ?>
                    <?php foreach (elementFields($element) as $field => $f): $fid = 'el' . $id . '-' . $field; ?>
                    <div class="slider">
                        <label for="<?= e($fid) ?>"><?= e($f['label']) ?>:</label>
                        <input type="range" name="<?= e($field) ?>" id="<?= e($fid) ?>"
                               min="<?= $f['min'] ?>" max="<?= $f['max'] ?>" step="1" value="<?= $state[$field] ?>">
                        <output for="<?= e($fid) ?>"><?= $state[$field] ?></output>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="app-buttons">
                    <?php foreach (elementButtons($element) as $index => $b): ?>
                        <button type="button" class="btn btn-xl btn-outline-light app-action" data-action="<?= $index ?>"><?= e($b['label']) ?></button>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </form>
            <?php endforeach; ?>
        <?php endif; ?>
        </div>
    </div>
</section>
