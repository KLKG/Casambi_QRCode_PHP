<section class="startscreen bg-primary text-white mb-0" id="code">
    <div class="container">
        <h2 class="page-section-heading text-center text-uppercase text-white"><?= e(t('Enter Code')) ?></h2>
        <div class="divider-custom divider-light">
            <div class="divider-custom-line"></div>
            <div class="divider-custom-icon"><?= icon('keyboard') ?></div>
            <div class="divider-custom-line"></div>
        </div>
        <?php require APP_ROOT . '/src/templates/layout/flash.php'; ?>

        <form action="index.php" method="get" class="text-center mt-4">
            <input type="hidden" name="site" value="control">
            <label for="code" class="visually-hidden"><?= e(t('Code')) ?></label>
            <input class="btn btn-xl btn-outline-light app-text-input" type="text" name="code" id="code"
                   maxlength="10" pattern="[A-Za-z0-9]{1,10}" autocomplete="off" autocapitalize="none" spellcheck="false"
                   placeholder="<?= e(t('Code')) ?>" required autofocus>
            <br><br>
            <button class="btn btn-xl btn-outline-light" type="submit"><?= e(t('send')) ?></button>
        </form>
    </div>
</section>
