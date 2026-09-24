<?php
/**
 * QR image download page.
 *
 * @var array $entry        row from qrGetCode()
 * @var bool  $qrAvailable  chillerlan/php-qrcode installed via Composer
 */
$code = (string) $entry['code'];
?>
<section class="startscreen bg-primary text-white mb-0" id="print">
    <div class="container">
        <h2 class="page-section-heading text-center text-uppercase text-white"><?= e(t('Download')) ?></h2>
        <div class="divider-custom divider-light">
            <div class="divider-custom-line"></div>
            <div class="divider-custom-icon"><?= icon('download') ?></div>
            <div class="divider-custom-line"></div>
        </div>
        <?php require APP_ROOT . '/src/templates/layout/flash.php'; ?>

        <div class="text-center mt-4">
            <p class="lead"><?= e($entry['name']) ?></p>
        <?php if ($qrAvailable): ?>
            <img class="app-qr" src="qr.php?code=<?= e($code) ?>" alt="<?= e(t('QR code')) ?> <?= e($code) ?>" width="330" height="330">
            <p class="app-code mt-3"><?= e($code) ?></p>
            <a class="btn btn-xl btn-outline-light" href="qr.php?code=<?= e($code) ?>&amp;download=1"><?= icon('download') ?> <?= e(t('Download PNG')) ?></a>
            <a class="btn btn-xl btn-outline-light" href="admin.php?site=edit&amp;code=<?= e($code) ?>"><?= icon('pencil') ?> <?= e(t('Edit')) ?></a>
            <a class="btn btn-xl btn-outline-light" href="admin.php?site=list"><?= e(t('Back to list')) ?></a>
        <?php else: ?>
            <p class="app-flash app-flash-error"><?= e(t('The QR code library is not installed. Run "composer install" in the project directory.')) ?></p>
            <a class="btn btn-xl btn-outline-light" href="admin.php?site=list"><?= e(t('Back to list')) ?></a>
        <?php endif; ?>
        </div>
    </div>
</section>
