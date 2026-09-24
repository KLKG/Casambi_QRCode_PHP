<?php
/**
 * Edit page for one QR code: name, gateway id, photo and the list of control elements.
 *
 * @var array       $entry     row from qrGetCode()
 * @var list<array> $elements  rows from qrElements()
 */
$code = (string) $entry['code'];
?>
<section class="startscreen bg-primary text-white mb-0" id="edit">
    <div class="container">
        <h2 class="page-section-heading text-center text-uppercase text-white"><?= e(t('Edit Code')) ?> <?= e($code) ?></h2>
        <div class="divider-custom divider-light">
            <div class="divider-custom-line"></div>
            <div class="divider-custom-icon"><?= icon('pencil') ?></div>
            <div class="divider-custom-line"></div>
        </div>
        <?php require APP_ROOT . '/src/templates/layout/flash.php'; ?>

        <div class="app-edit-toolbar">
            <a class="btn btn-xl btn-outline-light" href="index.php?site=control&amp;code=<?= e($code) ?>" target="_blank" rel="noopener"><?= icon('sliders') ?> <?= e(t('Preview')) ?></a>
            <a class="btn btn-xl btn-outline-light" href="admin.php?site=print&amp;code=<?= e($code) ?>"><?= icon('printer') ?> <?= e(t('QR code')) ?></a>
            <a class="btn btn-xl btn-outline-light" href="admin.php?site=list"><?= icon('list-ul') ?> <?= e(t('Back to list')) ?></a>
        </div>

        <!-- Photo -->
        <div class="app-card">
            <h3 class="app-card-title"><?= e(t('Photo')) ?></h3>
            <div class="app-photo-row">
                <?php if ((int) $entry['has_image'] > 0): ?>
                <img class="app-photo app-photo-small" src="image.php?code=<?= e($code) ?>" alt="<?= e(t('Photo for {name}', ['name' => $entry['name']])) ?>">
                <?php else: ?>
                <p class="app-hint"><?= e(t('No photo yet. JPEG, PNG, GIF or WebP; the image is downscaled to {px} px and stored as JPEG.', ['px' => (int) ($config['image_max_px'] ?? 1200)])) ?></p>
                <?php endif; ?>
                <form action="admin.php?site=image" method="post" enctype="multipart/form-data" class="app-photo-form">
                    <?= csrfField() ?>
                    <input type="hidden" name="code" value="<?= e($code) ?>">
                    <input type="hidden" name="MAX_FILE_SIZE" value="<?= (int) ($config['image_max_upload_bytes'] ?? 8388608) ?>">
                    <label for="image" class="visually-hidden"><?= e(t('Image file')) ?></label>
                    <input class="app-file" type="file" name="image" id="image" accept="image/jpeg,image/png,image/gif,image/webp">
                    <div class="app-photo-actions">
                        <button class="btn btn-xl btn-outline-light" type="submit"><?= icon('download') ?> <?= e(t('Upload')) ?></button>
                        <?php if ((int) $entry['has_image'] > 0): ?>
                        <button class="btn btn-xl btn-outline-light" type="submit" name="remove_image" value="1" data-confirm="<?= e(t('Remove the photo?')) ?>"><?= icon('trash') ?> <?= e(t('Remove')) ?></button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Settings + elements -->
        <form action="admin.php?site=save" method="post" class="app-form-wide">
            <?= csrfField() ?>
            <input type="hidden" name="code" value="<?= e($code) ?>">

            <div class="app-card">
                <h3 class="app-card-title"><?= e(t('Settings')) ?></h3>
                <div class="app-field">
                    <label for="name"><?= e(t('Name')) ?>:</label>
                    <input class="btn btn-xl btn-outline-light app-text-input" type="text" name="name" id="name"
                           value="<?= e($entry['name']) ?>" maxlength="60" required>
                </div>
                <div class="app-field">
                    <label for="lithernet_id"><?= e(t('Lithernet ID')) ?>:</label>
                    <input class="btn btn-xl btn-outline-light app-text-input" type="number" name="lithernet_id" id="lithernet_id"
                           min="0" max="255" step="1" value="<?= (int) $entry['lithernet_id'] ?>" required>
                </div>
                <p class="app-hint"><?= e(t('Lithernet ID 255 addresses every gateway in the network.')) ?></p>
            </div>

            <div class="app-card">
                <h3 class="app-card-title"><?= e(t('Control elements')) ?></h3>
                <?php if ($elements === []): ?>
                <p class="app-hint"><?= e(t('No elements yet. Add the first one below.')) ?></p>
                <?php endif; ?>

                <?php foreach ($elements as $element):
                    $id  = (int) $element['id'];
                    $def = elementTypeDef((string) $element['element_type']);
                    if ($def === null) { continue; }
                    $n = 'elements[' . $id . ']';
                ?>
                <fieldset class="app-element-row">
                    <legend><?= e($def['label']) ?></legend>
                    <div class="app-element-grid">
                        <div class="app-field-sm">
                            <label for="el<?= $id ?>-position"><?= e(t('Order')) ?></label>
                            <input class="app-input" type="number" name="<?= $n ?>[position]" id="el<?= $id ?>-position"
                                   min="1" max="999" value="<?= (int) $element['position'] ?>">
                        </div>
                        <div class="app-field-sm app-field-grow">
                            <label for="el<?= $id ?>-name"><?= e(t('Name')) ?></label>
                            <input class="app-input" type="text" name="<?= $n ?>[name]" id="el<?= $id ?>-name"
                                   value="<?= e($element['name']) ?>" maxlength="60" required>
                        </div>

                        <?php if ($def['target']): ?>
                        <div class="app-field-sm">
                            <label for="el<?= $id ?>-target_type"><?= e(t('Target type')) ?></label>
                            <select class="app-input" name="<?= $n ?>[target_type]" id="el<?= $id ?>-target_type">
                                <?php foreach (targetTypes() as $value => $label): ?>
                                <option value="<?= $value ?>"<?= $value === (int) $element['target_type'] ? ' selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="app-field-sm">
                            <label for="el<?= $id ?>-target_id"><?= e(t('Target ID')) ?></label>
                            <input class="app-input" type="number" name="<?= $n ?>[target_id]" id="el<?= $id ?>-target_id"
                                   min="0" max="255" value="<?= (int) $element['target_id'] ?>">
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($def['id_label'])): ?>
                        <div class="app-field-sm">
                            <label for="el<?= $id ?>-target_id"><?= e($def['id_label']) ?></label>
                            <input class="app-input" type="number" name="<?= $n ?>[target_id]" id="el<?= $id ?>-target_id"
                                   min="0" max="255" value="<?= (int) $element['target_id'] ?>">
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($def['index'])): ?>
                        <div class="app-field-sm">
                            <label for="el<?= $id ?>-index"><?= e(t('Element index')) ?></label>
                            <input class="app-input" type="number" name="<?= $n ?>[param_index]" id="el<?= $id ?>-index"
                                   min="0" max="255" value="<?= (int) $element['param_index'] ?>">
                        </div>
                        <?php endif; ?>

                        <?php if ($def['fade']): ?>
                        <div class="app-field-sm">
                            <label for="el<?= $id ?>-fade"><?= e(t('Fade [ms]')) ?></label>
                            <input class="app-input" type="number" name="<?= $n ?>[fade_ms]" id="el<?= $id ?>-fade"
                                   min="0" max="65535" step="100" value="<?= (int) $element['fade_ms'] ?>">
                        </div>
                        <?php endif; ?>

                        <?php if ($def['range']): ?>
                        <div class="app-field-sm">
                            <label for="el<?= $id ?>-min"><?= e(t('Min Kelvin')) ?></label>
                            <input class="app-input" type="number" name="<?= $n ?>[param_min]" id="el<?= $id ?>-min"
                                   min="1000" max="20000" step="50" value="<?= (int) $element['param_min'] ?>">
                        </div>
                        <div class="app-field-sm">
                            <label for="el<?= $id ?>-max"><?= e(t('Max Kelvin')) ?></label>
                            <input class="app-input" type="number" name="<?= $n ?>[param_max]" id="el<?= $id ?>-max"
                                   min="1000" max="20000" step="50" value="<?= (int) $element['param_max'] ?>">
                        </div>
                        <?php endif; ?>

                        <?php if ($def['level']): ?>
                        <div class="app-field-sm">
                            <label for="el<?= $id ?>-level"><?= e(t('Level')) ?></label>
                            <input class="app-input" type="number" name="<?= $n ?>[param_level]" id="el<?= $id ?>-level"
                                   min="0" max="254" value="<?= (int) $element['param_level'] ?>">
                        </div>
                        <?php endif; ?>

                        <div class="app-field-sm app-field-check">
                            <input type="checkbox" name="<?= $n ?>[delete]" id="el<?= $id ?>-delete" value="1">
                            <label for="el<?= $id ?>-delete"><?= icon('trash') ?> <?= e(t('Delete')) ?></label>
                        </div>
                    </div>
                    <p class="app-hint app-hint-left"><?= e($def['help']) ?></p>
                </fieldset>
                <?php endforeach; ?>

                <fieldset class="app-element-row app-element-new">
                    <legend><?= e(t('Add element')) ?></legend>
                    <div class="app-element-grid">
                        <div class="app-field-sm">
                            <label for="new_type"><?= e(t('Type')) ?></label>
                            <select class="app-input" name="new_type" id="new_type">
                                <option value="">&ndash; <?= e(t('none')) ?> &ndash;</option>
                                <?php foreach (elementTypes() as $type => $def): ?>
                                <option value="<?= e($type) ?>"><?= e($def['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="app-field-sm app-field-grow">
                            <label for="new_name"><?= e(t('Name (optional)')) ?></label>
                            <input class="app-input" type="text" name="new_name" id="new_name" maxlength="60" placeholder="<?= e(t('e.g. Ceiling light')) ?>">
                        </div>
                    </div>
                    <p class="app-hint app-hint-left"><?= e(t('Choose a type and press save; the element appears at the end of the list and can then be configured.')) ?></p>
                </fieldset>
            </div>

            <div class="text-center mt-3">
                <button class="btn btn-xl btn-outline-light" type="submit"><?= e(t('save')) ?></button>
            </div>
        </form>
    </div>
</section>
