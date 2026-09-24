<?php
/**
 * @var list<array<string, mixed>> $codes
 */
?>
<section class="startscreen bg-primary text-white mb-0" id="list">
    <div class="container">
        <h2 class="page-section-heading text-center text-uppercase text-white"><?= e(t('Codes')) ?></h2>
        <div class="divider-custom divider-light">
            <div class="divider-custom-line"></div>
            <div class="divider-custom-icon"><?= icon('list-ul') ?></div>
            <div class="divider-custom-line"></div>
        </div>
        <?php require APP_ROOT . '/src/templates/layout/flash.php'; ?>

        <form action="admin.php?site=add" method="post" class="text-center mt-4">
            <?= csrfField() ?>
            <button class="btn btn-xl btn-outline-light" type="submit"><?= icon('plus-lg') ?> <?= e(t('Add Code')) ?></button>
        </form>

        <div class="table-responsive mt-4">
            <table class="app-table">
                <thead>
                    <tr>
                        <th scope="col"><?= e(t('Name')) ?></th>
                        <th scope="col"><?= e(t('Code')) ?></th>
                        <th scope="col"><?= e(t('Lithernet ID')) ?></th>
                        <th scope="col"><?= e(t('Elements')) ?></th>
                        <th scope="col"><?= e(t('Photo')) ?></th>
                        <th scope="col"><?= e(t('Edit')) ?></th>
                        <th scope="col"><?= e(t('Print')) ?></th>
                        <th scope="col"><?= e(t('Delete')) ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($codes === []): ?>
                    <tr><td colspan="8"><?= e(t('No codes yet.')) ?></td></tr>
                <?php endif; ?>
                <?php foreach ($codes as $row):
                    $c     = (string) $row['code'];
                    $types = $row['element_types'] !== null ? explode(',', (string) $row['element_types']) : [];
                    $types = array_map(static fn (string $t): string => elementTypeLabel($t), $types);
                ?>
                    <tr>
                        <td><?= e($row['name']) ?></td>
                        <td class="app-code"><?= e($c) ?></td>
                        <td><?= (int) $row['lithernet_id'] ?></td>
                        <td class="app-cell-left">
                            <?php if ($types === []): ?>
                                <span class="app-muted"><?= e(t('none')) ?></span>
                            <?php else: ?>
                                <?= (int) $row['element_count'] ?>: <?= e(implode(', ', $types)) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= (int) $row['has_image'] > 0 ? e(t('yes')) : '&ndash;' ?></td>
                        <td><a class="app-icon-link" href="admin.php?site=edit&amp;code=<?= e($c) ?>" title="<?= e(t('Edit')) ?> <?= e($c) ?>"><?= icon('pencil') ?><span class="visually-hidden"><?= e(t('Edit')) ?></span></a></td>
                        <td><a class="app-icon-link" href="admin.php?site=print&amp;code=<?= e($c) ?>" title="<?= e(t('Print')) ?> <?= e($c) ?>"><?= icon('printer') ?><span class="visually-hidden"><?= e(t('Print')) ?></span></a></td>
                        <td>
                            <form action="admin.php?site=delete" method="post" data-confirm="<?= e(t('Delete code {code} ({name}) with all its elements?', ['code' => $c, 'name' => $row['name']])) ?>">
                                <?= csrfField() ?>
                                <input type="hidden" name="code" value="<?= e($c) ?>">
                                <button type="submit" class="app-icon-link btn btn-link" title="<?= e(t('Delete')) ?> <?= e($c) ?>"><?= icon('trash') ?><span class="visually-hidden"><?= e(t('Delete')) ?></span></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
