<?php
/**
 * Setup wizard page.
 *
 * @var bool        $locked        setup key required and not yet entered
 * @var list<array> $requirements  from setupRequirements()
 * @var array       $values        form values
 * @var list<string> $errors
 * @var array|null  $testResult    from setupTestDatabase()
 */
$allOk = setupRequirementsMet($requirements);
?>
<section class="startscreen bg-primary text-white mb-0" id="setup">
    <div class="container">
        <h2 class="page-section-heading text-center text-uppercase text-white"><?= e(t('Setup')) ?></h2>
        <div class="divider-custom divider-light">
            <div class="divider-custom-line"></div>
            <div class="divider-custom-icon"><?= icon('key') ?></div>
            <div class="divider-custom-line"></div>
        </div>
        <?php require APP_ROOT . '/src/templates/layout/flash.php'; ?>

        <?php if ($locked): ?>
        <div class="app-card">
            <h3 class="app-card-title"><?= e(t('Setup key')) ?></h3>
            <p class="app-hint"><?= e(t('This installation is protected: enter the content of config/setup.key.')) ?></p>
            <form action="setup.php" method="post" class="text-center">
                <?= csrfField() ?>
                <label for="setup_key" class="visually-hidden"><?= e(t('Setup key')) ?></label>
                <input class="btn btn-xl btn-outline-light app-text-input" type="password" name="setup_key" id="setup_key" autocomplete="off" required autofocus>
                <br><br>
                <button class="btn btn-xl btn-outline-light" type="submit"><?= e(t('unlock')) ?></button>
            </form>
        </div>
        <?php else: ?>

        <div class="app-card">
            <h3 class="app-card-title">1. <?= e(t('Requirements')) ?></h3>
            <ul class="app-req">
                <?php foreach ($requirements as $r): ?>
                <li class="<?= $r['ok'] ? 'is-ok' : ($r['warn'] ? 'is-warn' : 'is-fail') ?>">
                    <span class="app-req-state"><?= $r['ok'] ? e(t('OK')) : ($r['warn'] ? e(t('Note')) : e(t('Missing'))) ?></span>
                    <span class="app-req-label"><?= e($r['label']) ?></span>
                    <span class="app-req-detail"><?= e($r['detail']) ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php if (!$allOk): ?>
            <p class="app-flash app-flash-error"><?= e(t('Fix the missing requirements, then reload this page.')) ?></p>
            <?php endif; ?>
        </div>

        <form action="setup.php" method="post" class="app-form-wide" autocomplete="off">
            <?= csrfField() ?>

            <div class="app-card">
                <h3 class="app-card-title">2. <?= e(t('Database')) ?></h3>
                <p class="app-hint"><?= e(t('MySQL 8 or MariaDB 10.4+. The user needs all rights on the database so the tables can be created automatically.')) ?></p>
                <div class="app-element-grid app-grid-center">
                    <div class="app-field-sm app-field-grow">
                        <label for="db_host"><?= e(t('Host')) ?></label>
                        <input class="app-input" type="text" name="db_host" id="db_host" value="<?= e($values['db_host']) ?>" required>
                    </div>
                    <div class="app-field-sm">
                        <label for="db_port"><?= e(t('Port')) ?></label>
                        <input class="app-input" type="number" name="db_port" id="db_port" min="1" max="65535" value="<?= (int) $values['db_port'] ?>" required>
                    </div>
                    <div class="app-field-sm app-field-grow">
                        <label for="db_user"><?= e(t('User')) ?></label>
                        <input class="app-input" type="text" name="db_user" id="db_user" value="<?= e($values['db_user']) ?>" required>
                    </div>
                    <div class="app-field-sm app-field-grow">
                        <label for="db_password"><?= e(t('Password')) ?></label>
                        <input class="app-input" type="password" name="db_password" id="db_password" value="<?= e($values['db_password']) ?>" autocomplete="new-password">
                    </div>
                    <div class="app-field-sm app-field-grow">
                        <label for="db_database"><?= e(t('Database name')) ?></label>
                        <input class="app-input" type="text" name="db_database" id="db_database" value="<?= e($values['db_database']) ?>" pattern="[A-Za-z0-9_$\-]{1,64}" required>
                    </div>
                    <div class="app-field-sm app-field-check">
                        <input type="checkbox" name="create_database" id="create_database" value="1"<?= !empty($values['create_database']) ? ' checked' : '' ?>>
                        <label for="create_database"><?= e(t('create database if missing')) ?></label>
                    </div>
                </div>
                <?php if ($testResult !== null): ?>
                <p class="app-flash <?= $testResult['ok'] ? ($testResult['ddl'] ? 'app-flash-success' : '') : 'app-flash-error' ?>"><?= e($testResult['message']) ?></p>
                <?php endif; ?>
            </div>

            <div class="app-card">
                <h3 class="app-card-title">3. <?= e(t('Gateway')) ?></h3>
                <div class="app-element-grid app-grid-center">
                    <div class="app-field-sm app-field-grow">
                        <label for="gw_ip"><?= e(t('Broadcast address')) ?></label>
                        <input class="app-input" type="text" name="gw_ip" id="gw_ip" value="<?= e($values['gw_ip']) ?>" required>
                    </div>
                    <div class="app-field-sm">
                        <label for="gw_port"><?= e(t('UDP port')) ?></label>
                        <input class="app-input" type="number" name="gw_port" id="gw_port" min="1" max="65535" value="<?= (int) $values['gw_port'] ?>" required>
                    </div>
                    <div class="app-field-sm">
                        <label for="mode"><?= e(t('Operation mode')) ?></label>
                        <select class="app-input" name="mode" id="mode">
                            <option value="demo"<?= $values['mode'] === 'demo' ? ' selected' : '' ?>><?= e(t('demo (send nothing)')) ?></option>
                            <option value="run"<?= $values['mode'] === 'run' ? ' selected' : '' ?>><?= e(t('run (send to gateway)')) ?></option>
                        </select>
                    </div>
                </div>
                <p class="app-hint"><?= e(t('Broadcast address of the network segment the gateway is in, e.g. 192.168.1.255. Both values can be changed later in the admin settings.')) ?></p>
            </div>

            <?php if ($errors !== []): ?>
            <div class="app-flash app-flash-error">
                <?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="text-center mt-3 d-flex flex-wrap justify-content-center gap-3">
                <button class="btn btn-xl btn-outline-light" type="submit" name="action" value="test"><?= e(t('test connection')) ?></button>
                <button class="btn btn-xl btn-outline-light" type="submit" name="action" value="install"<?= $allOk ? '' : ' disabled' ?>><?= e(t('save and continue')) ?></button>
            </div>
            <p class="app-hint mt-3"><?= e(t('"Save and continue" tests the connection, writes config/config.php and opens the admin area, where the tables are created and the first admin account is set up.')) ?></p>
        </form>
        <?php endif; ?>
    </div>
</section>
