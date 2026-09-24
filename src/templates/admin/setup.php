<section class="startscreen bg-primary text-white text-center">
    <div class="container d-flex align-items-center flex-column">
        <img class="startscreen-avatar mb-5" src="assets/img/logo_white.svg" alt="Lithernet">
        <div class="divider-custom divider-light">
            <div class="divider-custom-line"></div>
            <div class="divider-custom-icon"><?= icon('plus-lg') ?></div>
            <div class="divider-custom-line"></div>
        </div>
        <h2 class="page-section-heading text-uppercase text-white"><?= e(t('Create the admin account')) ?></h2>
        <?php require APP_ROOT . '/src/templates/layout/flash.php'; ?>

        <div class="app-card app-card-narrow">
            <p class="app-intro"><?= e(t('There is no admin account yet. This is not a login: choose a username and a password for the first administrator. You will log in with them afterwards.')) ?></p>

            <form action="admin.php?site=setup" method="post" class="app-form-wide">
                <?= csrfField() ?>
                <div class="app-field-sm app-field-stack">
                    <label for="username"><?= e(t('Choose a username')) ?></label>
                    <input class="app-input" type="text" name="username" id="username"
                           autocomplete="username" placeholder="admin" maxlength="20" pattern="[A-Za-z0-9._-]{1,20}" required autofocus>
                    <span class="app-hint app-hint-left"><?= e(t('Letters, digits, dot, dash and underscore; max. 20 characters.')) ?></span>
                </div>
                <div class="app-field-sm app-field-stack">
                    <label for="password"><?= e(t('Choose a password')) ?></label>
                    <input class="app-input" type="password" name="password" id="password"
                           autocomplete="new-password" minlength="8" required>
                    <span class="app-hint app-hint-left"><?= e(t('At least 8 characters.')) ?></span>
                </div>
                <div class="app-field-sm app-field-stack">
                    <label for="password_repeat"><?= e(t('Repeat the password')) ?></label>
                    <input class="app-input" type="password" name="password_repeat" id="password_repeat"
                           autocomplete="new-password" minlength="8" required>
                </div>
                <div class="text-center mt-3">
                    <button class="btn btn-xl btn-outline-light" type="submit"><?= icon('plus-lg') ?> <?= e(t('Create admin account')) ?></button>
                </div>
            </form>
        </div>
        <p class="app-hint mt-3"><?= e(t('More accounts can be added later with "php bin/create-admin.php <username>".')) ?></p>
    </div>
</section>
