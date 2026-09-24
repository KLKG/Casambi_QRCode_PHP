<?php
/**
 * Shared page footer and scripts.
 *
 * @var list<string> $scripts  optional extra script bundles ('scanner')
 */
?>
<footer class="copyright py-4 text-center text-white">
    <div class="container"><small><?= e(t('Copyright')) ?> &copy; Licht Manufaktur Berlin GmbH 2022&ndash;<?= date('Y') ?></small></div>
</footer>
<?php if (in_array('scanner', $scripts, true)): ?>
<script src="<?= e(asset('assets/js/vendor/barcode-detector/barcode-detector.ponyfill.js')) ?>"></script>
<?php endif; ?>
<script src="<?= e(asset('assets/js/app.js')) ?>"></script>
</body>
</html>
