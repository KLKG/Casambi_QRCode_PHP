<?php
/**
 * Runtime settings (stored in the database, overriding config/config.php).
 *
 * @var array<string, array{value: string, source: string}> $effective
 * @var array<string, mixed> $dbInfo  host / database name from config.php (read-only)
 */
$definitions = settingsDefinitions();
$groups      = [];
foreach ($definitions as $name => $def) {
    $groups[$def['group']][$name] = $def;
}
$fromDb = count(array_filter($effective, static fn (array $v): bool => $v['source'] === 'database'));
?>
<section class="startscreen bg-primary text-white mb-0" id="settings">
    <div class="container">
        <h2 class="page-section-heading text-center text-uppercase text-white"><?= e(t('Settings')) ?></h2>
        <div class="divider-custom divider-light">
            <div class="divider-custom-line"></div>
            <div class="divider-custom-icon"><?= icon('sliders') ?></div>
            <div class="divider-custom-line"></div>
        </div>
        <?php require APP_ROOT . '/src/templates/layout/flash.php'; ?>

        <form action="admin.php?site=settings" method="post" class="app-form-wide">
            <?= csrfField() ?>

            <?php foreach ($groups as $group => $items): ?>
            <div class="app-card">
                <h3 class="app-card-title"><?= e($group) ?></h3>
                <?php foreach ($items as $name => $def): $cur = $effective[$name]; ?>
                <div class="app-setting">
                    <label for="s-<?= e($name) ?>" class="app-setting-label"><?= e($def['label']) ?></label>
                    <div class="app-setting-input">
                        <?php if ($def['type'] === 'select'): ?>
                        <select class="app-input" name="<?= e($name) ?>" id="s-<?= e($name) ?>">
                            <?php foreach ($def['options'] as $value => $label): ?>
                            <option value="<?= e($value) ?>"<?= $cur['value'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php elseif ($def['type'] === 'language'): ?>
                        <select class="app-input" name="<?= e($name) ?>" id="s-<?= e($name) ?>">
                            <?php foreach (availableLanguageCodes() as $code): ?>
                            <option value="<?= e($code) ?>"<?= $cur['value'] === $code ? ' selected' : '' ?>><?= e(languageName($code)) ?> (<?= e(strtoupper($code)) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <?php elseif ($def['type'] === 'int'): ?>
                        <input class="app-input" type="number" name="<?= e($name) ?>" id="s-<?= e($name) ?>"
                               min="<?= (int) $def['min'] ?>" max="<?= (int) $def['max'] ?>" value="<?= e($cur['value']) ?>" required>
                        <?php else: ?>
                        <input class="app-input" type="text" name="<?= e($name) ?>" id="s-<?= e($name) ?>" value="<?= e($cur['value']) ?>" required>
                        <?php endif; ?>
                        <span class="app-source app-source-<?= e($cur['source']) ?>"><?= $cur['source'] === 'database' ? e(t('database')) : e(t('config.php')) ?></span>
                    </div>
                    <p class="app-hint app-hint-left"><?= e($def['help']) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>

            <div class="app-card">
                <h3 class="app-card-title"><?= e(t('Database connection')) ?></h3>
                <p class="app-hint"><?= e(t('The database access is read from config/config.php and cannot be changed here.')) ?></p>
                <p class="text-center app-code"><?= e($dbInfo['user']) ?>@<?= e($dbInfo['host']) ?>:<?= (int) $dbInfo['port'] ?> / <?= e($dbInfo['database']) ?></p>
            </div>

            <div class="text-center mt-3 d-flex flex-wrap justify-content-center gap-3">
                <button class="btn btn-xl btn-outline-light" type="submit"><?= e(t('save')) ?></button>
                <?php if ($fromDb > 0): ?>
                <button class="btn btn-xl btn-outline-light" type="submit" name="reset" value="1" data-confirm="<?= e(t('Discard all settings stored in the database and use config/config.php again?')) ?>"><?= icon('trash') ?> <?= e(t('reset to config.php')) ?></button>
                <?php endif; ?>
            </div>
            <p class="app-hint mt-3"><?= e(t('Values saved here are stored in the database and override config/config.php; the badge next to each field shows where the current value comes from.')) ?></p>
        </form>
    </div>
</section>
