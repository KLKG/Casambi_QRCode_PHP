<?php
/**
 * Translation editor for one language.
 *
 * @var array{code: string, name: string, enabled: int} $language
 * @var array<string, list<string>> $catalogue   source => files
 * @var array<string, string> $translations      source_hash => translation
 * @var bool $missingOnly
 */
$lang     = (string) $language['code'];
$progress = translationProgress($lang);
$rows     = [];
foreach ($catalogue as $source => $files) {
    $hash = md5($source);
    $done = isset($translations[$hash]);
    if ($missingOnly && $done) {
        continue;
    }
    $rows[] = ['source' => $source, 'hash' => $hash, 'files' => $files, 'value' => $translations[$hash] ?? ''];
}
?>
<section class="startscreen bg-primary text-white mb-0" id="translate">
    <div class="container">
        <h2 class="page-section-heading text-center text-uppercase text-white"><?= e(t('Translate')) ?>: <?= e($language['name']) ?> (<?= e($lang) ?>)</h2>
        <div class="divider-custom divider-light">
            <div class="divider-custom-line"></div>
            <div class="divider-custom-icon"><?= icon('pencil') ?></div>
            <div class="divider-custom-line"></div>
        </div>
        <?php require APP_ROOT . '/src/templates/layout/flash.php'; ?>

        <div class="app-edit-toolbar">
            <a class="btn btn-xl btn-outline-light" href="admin.php?site=languages"><?= icon('list-ul') ?> <?= e(t('Languages')) ?></a>
            <a class="btn btn-xl btn-outline-light" href="admin.php?site=translate&amp;lang=<?= e($lang) ?><?= $missingOnly ? '' : '&amp;missing=1' ?>">
                <?= $missingOnly ? e(t('show all strings')) : e(t('show untranslated only')) ?>
            </a>
            <a class="btn btn-xl btn-outline-light" href="index.php?lang=<?= e($lang) ?>" target="_blank" rel="noopener"><?= icon('sliders') ?> <?= e(t('Preview')) ?></a>
        </div>

        <p class="text-center mt-3"><?= e(t('{done} of {total} strings translated. Empty fields fall back to English. Keep placeholders like {name} unchanged.', ['done' => $progress['done'], 'total' => $progress['total'], 'name' => '{name}'])) ?></p>

        <form action="admin.php?site=translate&amp;lang=<?= e($lang) ?><?= $missingOnly ? '&amp;missing=1' : '' ?>" method="post">
            <?= csrfField() ?>
            <div class="text-center mb-3">
                <button class="btn btn-xl btn-outline-light" type="submit"><?= e(t('save')) ?></button>
            </div>

            <?php if ($rows === []): ?>
            <p class="app-flash app-flash-success"><?= e(t('Everything is translated.')) ?></p>
            <?php endif; ?>

            <div class="app-card app-translate">
                <?php foreach ($rows as $row): ?>
                <div class="app-tr-row<?= $row['value'] === '' ? ' is-missing' : '' ?>">
                    <div class="app-tr-source">
                        <div class="app-tr-text"><?= e($row['source']) ?></div>
                        <div class="app-tr-files"><?= e(implode(', ', $row['files'])) ?></div>
                    </div>
                    <div class="app-tr-target">
                        <label for="tr-<?= e($row['hash']) ?>" class="visually-hidden"><?= e($row['source']) ?></label>
                        <textarea class="app-input app-tr-input" name="tr[<?= e($row['hash']) ?>]" id="tr-<?= e($row['hash']) ?>" rows="<?= mb_strlen($row['source']) > 70 ? 3 : 1 ?>" maxlength="2000"><?= e($row['value']) ?></textarea>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center mt-3">
                <button class="btn btn-xl btn-outline-light" type="submit"><?= e(t('save')) ?></button>
            </div>
        </form>
    </div>
</section>
