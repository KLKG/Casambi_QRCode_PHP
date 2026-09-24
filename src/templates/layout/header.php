<?php
/**
 * Shared page head and navigation (tabs in the admin area, language switcher everywhere).
 *
 * @var string $title
 * @var string $area       'index', 'admin' or 'setup'
 * @var string $activeTab  admin only: 'codes', 'settings', 'languages'
 */
$isAdmin   = ($area === 'admin');
$loggedIn  = $isAdmin && isLoggedIn();
$activeTab = $activeTab ?? '';
$languages = languagesList(true);
$current   = currentLanguage();
$appName   = t('Casambi QR-Code Scanner');
$tab       = static fn (string $name): string => $name === $activeTab ? ' is-active' : '';
?>
<!DOCTYPE html>
<html lang="<?= e($current) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="<?= e(t('Control Casambi lighting via QR codes and the Lithernet Casambi Gateway.')) ?>">
    <title><?= e($title === $appName ? $title : $title . ' – ' . $appName) ?></title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <link href="<?= e(asset('assets/css/fonts.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset('assets/css/style.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset('assets/css/app.css')) ?>" rel="stylesheet">
</head>
<body id="page-top"
      data-t-sending="<?= e(t('Sending…')) ?>"
      data-t-sent="<?= e(t('Command sent.')) ?>"
      data-t-demo="<?= e(t('Saved (demo mode, nothing sent to the gateway).')) ?>"
      data-t-failed="<?= e(t('Command failed.')) ?>"
      data-t-network="<?= e(t('Network error, command not sent.')) ?>"
      data-t-cam-unsupported="<?= e(t('Camera scanning is not supported in this browser. Please enter the code manually.')) ?>"
      data-t-cam-https="<?= e(t('Camera access requires HTTPS (or localhost). Please enter the code manually.')) ?>"
      data-t-cam-point="<?= e(t('Point the camera at a QR code.')) ?>"
      data-t-cam-error="<?= e(t('Camera could not be started: {error}')) ?>"
      data-t-found="<?= e(t('Code found: {code}')) ?>">
<nav class="navbar bg-secondary text-uppercase fixed-top" id="mainNav">
    <div class="container app-nav">
        <a class="navbar-brand" href="<?= $isAdmin ? 'admin.php' : 'index.php' ?>">
            <img class="app-brand-logo" src="assets/img/only_logo_white.svg" alt="Lithernet">
        </a>
        <?php if ($isAdmin): ?>
            <?php if ($loggedIn): ?>
                <a class="nav-link py-3 px-lg-4 rounded text-white app-tab<?= $tab('codes') ?>" href="admin.php?site=list"><?= e(t('Codes')) ?></a>
                <a class="nav-link py-3 px-lg-4 rounded text-white app-tab<?= $tab('settings') ?>" href="admin.php?site=settings"><?= e(t('Settings')) ?></a>
                <a class="nav-link py-3 px-lg-4 rounded text-white app-tab<?= $tab('languages') ?>" href="admin.php?site=languages"><?= e(t('Languages')) ?></a>
                <form action="admin.php?site=logout" method="post" class="ms-auto app-nav-inline">
                    <?= csrfField() ?>
                    <button type="submit" class="nav-link py-3 px-lg-4 rounded text-white btn btn-link">
                        <?= icon('box-arrow-right') ?> <?= e(t('Logout')) ?>
                    </button>
                </form>
            <?php endif; ?>
        <?php elseif ($area === 'setup'): ?>
            <span class="nav-link py-3 px-lg-4 text-white"><?= e(t('First-run setup')) ?></span>
        <?php else: ?>
            <a class="nav-link py-3 px-lg-4 rounded text-white" href="index.php?site=scan"><?= e(t('Scan')) ?></a>
            <a class="nav-link py-3 px-lg-4 rounded text-white" href="index.php?site=code"><?= e(t('Enter Code')) ?></a>
        <?php endif; ?>
        <?php if ($languages !== []): ?>
            <div class="app-lang<?= ($isAdmin && $loggedIn) ? '' : ' ms-auto' ?>" aria-label="<?= e(t('Language')) ?>">
                <a class="app-lang-link<?= $current === 'en' ? ' is-active' : '' ?>" href="<?= e(langSwitchUrl('en')) ?>" hreflang="en">EN</a>
                <?php foreach ($languages as $l): ?>
                <a class="app-lang-link<?= $current === $l['code'] ? ' is-active' : '' ?>" href="<?= e(langSwitchUrl((string) $l['code'])) ?>" hreflang="<?= e($l['code']) ?>" title="<?= e($l['name']) ?>"><?= e(strtoupper((string) $l['code'])) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</nav>
