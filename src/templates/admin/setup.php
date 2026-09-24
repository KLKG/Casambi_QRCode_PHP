<section class="startscreen bg-primary text-white text-center">
    <div class="container d-flex align-items-center flex-column">
        <img class="startscreen-avatar mb-5" src="assets/img/logo_white.svg" alt="Lithernet">
        <div class="divider-custom divider-light">
            <div class="divider-custom-line"></div>
            <div class="divider-custom-icon"><?= icon('key') ?></div>
            <div class="divider-custom-line"></div>
        </div>
        <p class="startscreen-subheading fw-light mb-0"><?= e(t('First start: create the admin account')) ?></p>
        <?php require APP_ROOT . '/src/templates/layout/flash.php'; ?>

        <form action="admin.php?site=setup" method="post" class="mt-4">
            <?= csrfField() ?>
            <label for="username" class="visually-hidden"><?= e(t('Username')) ?></label>
            <input class="btn btn-xl btn-outline-light app-text-input" type="text" name="username" id="username"
                   autocomplete="username" placeholder="<?= e(t('Username')) ?>" maxlength="20" pattern="[A-Za-z0-9._-]{1,20}" required autofocus>
            <br><br>
            <label for="password" class="visually-hidden"><?= e(t('Password')) ?></label>
            <input class="btn btn-xl btn-outline-light app-text-input" type="password" name="password" id="password"
                   autocomplete="new-password" placeholder="<?= e(t('Password (min. 8 characters)')) ?>" minlength="8" required>
            <br><br>
            <label for="password_repeat" class="visually-hidden"><?= e(t('Repeat password')) ?></label>
            <input class="btn btn-xl btn-outline-light app-text-input" type="password" name="password_repeat" id="password_repeat"
                   autocomplete="new-password" placeholder="<?= e(t('Repeat password')) ?>" minlength="8" required>
            <br><br>
            <button class="btn btn-xl btn-outline-light" type="submit"><?= e(t('create account')) ?></button>
        </form>
    </div>
</section>
