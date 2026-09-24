<section class="startscreen bg-primary text-white text-center">
    <div class="container d-flex align-items-center flex-column">
        <img class="startscreen-avatar mb-5" src="assets/img/logo_white.svg" alt="Lithernet">
        <div class="divider-custom divider-light">
            <div class="divider-custom-line"></div>
            <div class="divider-custom-icon"><?= icon('qr-code') ?></div>
            <div class="divider-custom-line"></div>
        </div>
        <p class="startscreen-subheading fw-light mb-0"><?= e(t('Casambi QR-Code Scanner')) ?></p>
        <p class="startscreen-subheading fw-light mb-0"><?= e(t('Admin Area')) ?></p>
        <?php require APP_ROOT . '/src/templates/layout/flash.php'; ?>
        <?php if (isDemoMode()): ?>
        <p class="app-control-meta mt-4"><?= e(t('Demo mode is active: no commands are sent to the gateway.')) ?></p>
        <?php endif; ?>
        <div class="mt-5 d-flex flex-wrap justify-content-center gap-3">
            <a class="btn btn-xl btn-outline-light" href="admin.php?site=list"><?= icon('list-ul') ?> <?= e(t('Codes')) ?></a>
            <form action="admin.php?site=add" method="post">
                <?= csrfField() ?>
                <button class="btn btn-xl btn-outline-light" type="submit"><?= icon('plus-lg') ?> <?= e(t('Add Code')) ?></button>
            </form>
            <a class="btn btn-xl btn-outline-light" href="admin.php?site=settings"><?= icon('sliders') ?> <?= e(t('Settings')) ?></a>
            <a class="btn btn-xl btn-outline-light" href="admin.php?site=languages"><?= icon('keyboard') ?> <?= e(t('Languages')) ?></a>
        </div>
    </div>
</section>
