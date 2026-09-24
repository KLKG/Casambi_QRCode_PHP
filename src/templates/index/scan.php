<section class="startscreen bg-primary text-white mb-0" id="scan">
    <div class="container">
        <h2 class="page-section-heading text-center text-uppercase text-white"><?= e(t('Scan Code')) ?></h2>
        <div class="divider-custom divider-light">
            <div class="divider-custom-line"></div>
            <div class="divider-custom-icon"><?= icon('camera') ?></div>
            <div class="divider-custom-line"></div>
        </div>
        <?php require APP_ROOT . '/src/templates/layout/flash.php'; ?>

        <div id="reader" class="app-reader mx-auto" data-wasm-url="assets/js/vendor/barcode-detector/zxing_reader.wasm">
            <p class="app-reader-status" data-role="status"><?= e(t('Starting camera…')) ?></p>
        </div>

        <form action="index.php" method="get" id="scanform" class="text-center mt-4">
            <input type="hidden" name="site" value="control">
            <label for="scan_code" class="me-3"><?= e(t('Result:')) ?></label>
            <input class="btn btn-xl btn-outline-light app-text-input" type="text" id="scan_code" name="code" readonly maxlength="10" value="">
            <br><br>
            <button class="btn btn-xl btn-outline-light" type="submit"><?= e(t('send')) ?></button>
        </form>
    </div>
</section>
