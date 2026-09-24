<section class="startscreen bg-primary text-white text-center">
    <div class="container d-flex align-items-center flex-column">
        <img class="startscreen-avatar mb-5" src="assets/img/logo_white.svg" alt="Lithernet">
        <div class="divider-custom divider-light">
            <div class="divider-custom-line"></div>
            <div class="divider-custom-icon"><?= icon('qr-code') ?></div>
            <div class="divider-custom-line"></div>
        </div>
        <p class="startscreen-subheading fw-light mb-0"><?= e(t('Casambi QR-Code Scanner')) ?></p>
        <?php require APP_ROOT . '/src/templates/layout/flash.php'; ?>
        <div class="mt-5 d-flex flex-wrap justify-content-center gap-3">
            <a class="btn btn-xl btn-outline-light" href="index.php?site=scan"><?= icon('camera') ?> <?= e(t('Scan')) ?></a>
            <a class="btn btn-xl btn-outline-light" href="index.php?site=code"><?= icon('keyboard') ?> <?= e(t('Enter Code')) ?></a>
        </div>
    </div>
</section>
