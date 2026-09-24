<section class="startscreen bg-primary text-white text-center">
    <div class="container d-flex align-items-center flex-column">
        <img class="startscreen-avatar mb-5" src="assets/img/logo_white.svg" alt="Lithernet">
        <div class="divider-custom divider-light">
            <div class="divider-custom-line"></div>
            <div class="divider-custom-icon"><?= icon('key') ?></div>
            <div class="divider-custom-line"></div>
        </div>
        <?php require APP_ROOT . '/src/templates/layout/flash.php'; ?>

        <form action="admin.php?site=login" method="post" class="mt-4">
            <?= csrfField() ?>
            <label for="username" class="visually-hidden"><?= e(t('Username')) ?></label>
            <input class="btn btn-xl btn-outline-light app-text-input" type="text" name="username" id="username"
                   autocomplete="username" placeholder="<?= e(t('Username')) ?>" maxlength="20" required autofocus>
            <br><br>
            <label for="password" class="visually-hidden"><?= e(t('Password')) ?></label>
            <input class="btn btn-xl btn-outline-light app-text-input" type="password" name="password" id="password"
                   autocomplete="current-password" placeholder="<?= e(t('Password')) ?>" required>
            <br><br>
            <button class="btn btn-xl btn-outline-light" type="submit"><?= e(t('login')) ?></button>
        </form>
    </div>
</section>
