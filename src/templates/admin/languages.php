<?php
/**
 * Language management: list, add, enable/disable, delete; link to the translation editor.
 *
 * @var list<array{code: string, name: string, enabled: int}> $languages
 * @var int $total  number of translatable strings
 */
?>
<section class="startscreen bg-primary text-white mb-0" id="languages">
    <div class="container">
        <h2 class="page-section-heading text-center text-uppercase text-white"><?= e(t('Languages')) ?></h2>
        <div class="divider-custom divider-light">
            <div class="divider-custom-line"></div>
            <div class="divider-custom-icon"><?= icon('keyboard') ?></div>
            <div class="divider-custom-line"></div>
        </div>
        <?php require APP_ROOT . '/src/templates/layout/flash.php'; ?>

        <div class="app-card">
            <p class="app-hint"><?= e(t('English is built in and the source of all texts ({count} strings). Add a language, translate the strings and enable it; visitors then get it via the browser language, the language switcher or the default language in the settings.', ['count' => $total])) ?></p>

            <div class="app-table-wrap">
                <table class="app-table app-table-stack">
                    <thead>
                        <tr>
                            <th scope="col"><?= e(t('Code')) ?></th>
                            <th scope="col" class="app-cell-left"><?= e(t('Name')) ?></th>
                            <th scope="col"><?= e(t('Translated')) ?></th>
                            <th scope="col"><?= e(t('Enabled')) ?></th>
                            <th scope="col"><?= e(t('Actions')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="app-code" data-label="<?= e(t('Code')) ?>">en</td>
                            <td class="app-cell-left app-cell-name" data-label="<?= e(t('Name')) ?>">English</td>
                            <td data-label="<?= e(t('Translated')) ?>"><?= e(t('source')) ?></td>
                            <td data-label="<?= e(t('Enabled')) ?>"><?= e(t('always')) ?></td>
                            <td class="app-actions" data-label="<?= e(t('Actions')) ?>">&ndash;</td>
                        </tr>
                        <?php foreach ($languages as $l): $p = translationProgress((string) $l['code']); ?>
                        <tr>
                            <td class="app-code" data-label="<?= e(t('Code')) ?>"><?= e($l['code']) ?></td>
                            <td class="app-cell-left app-cell-name" data-label="<?= e(t('Name')) ?>"><?= e($l['name']) ?></td>
                            <td data-label="<?= e(t('Translated')) ?>"><?= $p['done'] ?> / <?= $p['total'] ?></td>
                            <td data-label="<?= e(t('Enabled')) ?>">
                                <form action="admin.php?site=langtoggle" method="post">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="code" value="<?= e($l['code']) ?>">
                                    <input type="hidden" name="enabled" value="<?= (int) $l['enabled'] === 1 ? 0 : 1 ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-light"><?= (int) $l['enabled'] === 1 ? e(t('enabled')) : e(t('disabled')) ?></button>
                                </form>
                            </td>
                            <td class="app-actions" data-label="<?= e(t('Actions')) ?>">
                                <a class="app-icon-link" href="admin.php?site=translate&amp;lang=<?= e($l['code']) ?>" title="<?= e(t('Translate')) ?>"><?= icon('pencil') ?><span class="visually-hidden"><?= e(t('Translate')) ?></span></a>
                                <form action="admin.php?site=langdelete" method="post" data-confirm="<?= e(t('Delete language {name} with all its translations?', ['name' => $l['name']])) ?>">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="code" value="<?= e($l['code']) ?>">
                                    <button type="submit" class="app-icon-link btn btn-link" title="<?= e(t('Delete')) ?>"><?= icon('trash') ?><span class="visually-hidden"><?= e(t('Delete')) ?></span></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="app-card">
            <h3 class="app-card-title"><?= e(t('Add language')) ?></h3>
            <form action="admin.php?site=langadd" method="post">
                <?= csrfField() ?>
                <div class="app-element-grid app-grid-center">
                    <div class="app-field-sm">
                        <label for="lang_code"><?= e(t('Code')) ?></label>
                        <input class="app-input" type="text" name="code" id="lang_code" maxlength="12" pattern="[a-z]{2,3}(-[a-z0-9]{2,8})?" placeholder="de" required>
                    </div>
                    <div class="app-field-sm app-field-grow">
                        <label for="lang_name"><?= e(t('Name')) ?></label>
                        <input class="app-input" type="text" name="name" id="lang_name" maxlength="40" placeholder="Deutsch" required>
                    </div>
                    <div class="app-field-sm">
                        <label>&nbsp;</label>
                        <button class="btn btn-xl btn-outline-light" type="submit"><?= icon('plus-lg') ?> <?= e(t('add')) ?></button>
                    </div>
                </div>
                <p class="app-hint app-hint-left"><?= e(t('Code as used by browsers: two or three lowercase letters, optionally with a region, e.g. de, fr, pt-br. The name is shown in the language switcher.')) ?></p>
            </form>
        </div>
    </div>
</section>
