<?php
/**
 * Admin area: first admin account, login/logout, QR codes with their control elements and
 * photo, runtime settings, languages and translations.
 * All state-changing actions are POST requests with a CSRF token and redirect afterwards.
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

$site       = cleanSite($_GET['site'] ?? '');
$needsSetup = userCount() === 0;

// ---------------------------------------------------------------------------
// Actions (POST)
// ---------------------------------------------------------------------------
if (isPost()) {
    requireCsrf();

    switch ($site) {
        case 'setup':
            if (!$needsSetup) {
                redirect('admin.php');
            }
            $username = cleanUsername($_POST['username'] ?? '');
            $password = (string) ($_POST['password'] ?? '');
            $repeat   = (string) ($_POST['password_repeat'] ?? '');
            if ($username === '' || strlen($password) < 8 || $password !== $repeat) {
                flash(t('Please enter a username and a password with at least 8 characters; both password fields must match.'), 'error');
                redirect('admin.php');
            }
            createUser($username, $password);
            flash(t('Admin account created. Please log in.'), 'success');
            redirect('admin.php');

        case 'login':
            if (isLoggedIn()) {
                redirect('admin.php');
            }
            if (attemptLogin((string) ($_POST['username'] ?? ''), (string) ($_POST['password'] ?? ''))) {
                redirect('admin.php');
            }
            flash(t('Login failed. After repeated failures the account is locked for a while.'), 'error');
            redirect('admin.php');

        case 'logout':
            logout();
            redirect('admin.php');

        // ---- codes -------------------------------------------------------
        case 'add':
            requireLogin();
            $code = qrGenerateCode();
            qrCreate($code, t('New code'), 255);
            flash(t('Code {code} created. Give it a name and add control elements.', ['code' => $code]), 'success');
            redirect('admin.php?site=edit&code=' . $code);

        case 'delete':
            requireLogin();
            $code = cleanCode($_POST['code'] ?? '');
            if (qrExists($code)) {
                qrDelete($code);
                flash(t('Code {code} deleted.', ['code' => $code]), 'success');
            } else {
                flash(t('Code not found.'), 'error');
            }
            redirect('admin.php?site=list');

        case 'save':
            requireLogin();
            $code = cleanCode($_POST['code'] ?? '');
            if (!qrExists($code)) {
                flash(t('Code not found.'), 'error');
                redirect('admin.php?site=list');
            }
            $errors = saveCodeForm($code, $_POST);
            if ($errors !== []) {
                flash(implode(' ', $errors), 'error');
            } else {
                flash(t('Code {code} saved.', ['code' => $code]), 'success');
            }
            redirect('admin.php?site=edit&code=' . $code);

        case 'image':
            requireLogin();
            $code = cleanCode($_POST['code'] ?? '');
            if (!qrExists($code)) {
                flash(t('Code not found.'), 'error');
                redirect('admin.php?site=list');
            }
            if (!empty($_POST['remove_image'])) {
                qrDeleteImage($code);
                flash(t('Image removed.'), 'success');
                redirect('admin.php?site=edit&code=' . $code);
            }
            $file = $_FILES['image'] ?? null;
            if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                flash(t('Please choose an image file.'), 'error');
                redirect('admin.php?site=edit&code=' . $code);
            }
            if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
                flash(t('Upload failed (error {code}). Check upload_max_filesize in php.ini.', ['code' => (int) $file['error']]), 'error');
                redirect('admin.php?site=edit&code=' . $code);
            }
            try {
                $img = processUploadedImage((string) $file['tmp_name'], (int) $file['size']);
                qrSetImage($code, $img['mime'], $img['width'], $img['height'], $img['data']);
                flash(t('Image saved ({width} x {height} px).', ['width' => $img['width'], 'height' => $img['height']]), 'success');
            } catch (RuntimeException $e) {
                flash($e->getMessage(), 'error');
            }
            redirect('admin.php?site=edit&code=' . $code);

        // ---- settings ----------------------------------------------------
        case 'settings':
            requireLogin();
            if (!empty($_POST['reset'])) {
                settingsReset();
                flash(t('Settings reset; config/config.php applies again.'), 'success');
                redirect('admin.php?site=settings');
            }
            $errors = settingsSave($_POST);
            if ($errors !== []) {
                flash(implode(' ', $errors), 'error');
            } else {
                flash(t('Settings saved.'), 'success');
            }
            redirect('admin.php?site=settings');

        // ---- languages ---------------------------------------------------
        case 'langadd':
            requireLogin();
            $code = strtolower(trim((string) ($_POST['code'] ?? '')));
            $name = cleanName($_POST['name'] ?? '', 40);
            if (!i18nValidCode($code) || $code === I18N_SOURCE_LANGUAGE) {
                flash(t('Invalid language code. Use two or three lowercase letters, optionally with a region (de, fr, pt-br).'), 'error');
            } elseif ($name === '') {
                flash(t('Please enter a language name.'), 'error');
            } elseif (languageGet($code) !== null) {
                flash(t('Language {code} already exists.', ['code' => $code]), 'error');
            } else {
                languageAdd($code, $name);
                flash(t('Language {name} added. Translate the strings and enable it when ready.', ['name' => $name]), 'success');
                redirect('admin.php?site=translate&lang=' . $code);
            }
            redirect('admin.php?site=languages');

        case 'langtoggle':
            requireLogin();
            $code = strtolower(trim((string) ($_POST['code'] ?? '')));
            if (languageGet($code) !== null) {
                languageSetEnabled($code, !empty($_POST['enabled']));
                flash(!empty($_POST['enabled']) ? t('Language {code} enabled.', ['code' => $code]) : t('Language {code} disabled.', ['code' => $code]), 'success');
            }
            redirect('admin.php?site=languages');

        case 'langdelete':
            requireLogin();
            $code = strtolower(trim((string) ($_POST['code'] ?? '')));
            if (languageGet($code) !== null) {
                languageDelete($code);
                if (($_SESSION['lang'] ?? '') === $code) {
                    unset($_SESSION['lang']);
                }
                flash(t('Language {code} deleted.', ['code' => $code]), 'success');
            }
            redirect('admin.php?site=languages');

        case 'translate':
            requireLogin();
            $code = strtolower(trim((string) ($_GET['lang'] ?? '')));
            if (languageGet($code) === null) {
                flash(t('Language not found.'), 'error');
                redirect('admin.php?site=languages');
            }
            $saved = translationsSave($code, is_array($_POST['tr'] ?? null) ? $_POST['tr'] : []);
            flash(t('{count} translations saved.', ['count' => $saved]), 'success');
            redirect('admin.php?site=translate&lang=' . $code . (!empty($_GET['missing']) ? '&missing=1' : ''));

        default:
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            exit("Unknown action.\n");
    }
}

/**
 * Store the edit form: code name and gateway id, every existing element
 * (settings, order, deletion) and optionally one new element.
 *
 * @param array<string, mixed> $post
 * @return list<string> user-readable validation errors (empty on success)
 */
function saveCodeForm(string $code, array $post): array
{
    $errors = [];

    $name        = cleanName($post['name'] ?? '');
    $lithernetId = cleanInt($post['lithernet_id'] ?? null, 0, 255, -1);
    if ($name === '') {
        $errors[] = t('Please enter a name.');
        $name     = $code;
    }
    if ($lithernetId < 0) {
        $errors[]    = t('Lithernet ID must be between 0 and 255.');
        $lithernetId = 255;
    }
    qrUpdateMeta($code, $name, $lithernetId);

    $rows = is_array($post['elements'] ?? null) ? $post['elements'] : [];
    foreach (qrElements($code) as $element) {
        $id  = (int) $element['id'];
        $row = is_array($rows[$id] ?? null) ? $rows[$id] : null;
        if ($row === null) {
            continue; // not part of the submitted form
        }
        if (!empty($row['delete'])) {
            qrDeleteElement($id, $code);
            continue;
        }
        $def   = elementTypeDef((string) $element['element_type']) ?? [];
        $label = cleanName($row['name'] ?? '') !== '' ? cleanName($row['name']) : (string) $element['name'];

        $fields = [
            'position'    => cleanInt($row['position'] ?? null, 1, 999, (int) $element['position']),
            'name'        => $label,
            'target_type' => (int) $element['target_type'],
            'target_id'   => (int) $element['target_id'],
            'fade_ms'     => (int) $element['fade_ms'],
            'param_min'   => (int) $element['param_min'],
            'param_max'   => (int) $element['param_max'],
            'param_level' => (int) $element['param_level'],
        ];

        if (!empty($def['target'])) {
            $tt = cleanInt($row['target_type'] ?? null, 0, 255, -1);
            if (!array_key_exists($tt, targetTypes())) {
                $errors[] = t('"{name}": invalid target type.', ['name' => $label]);
            } else {
                $fields['target_type'] = $tt;
            }
        }
        if (!empty($def['target']) || !empty($def['scene'])) {
            $tid = cleanInt($row['target_id'] ?? null, 0, 255, -1);
            if ($tid < 0) {
                $errors[] = t('"{name}": target / scene id must be between 0 and 255.', ['name' => $label]);
            } else {
                $fields['target_id'] = $tid;
            }
        }
        if (!empty($def['fade'])) {
            $fade = cleanInt($row['fade_ms'] ?? null, 0, 65535, -1);
            if ($fade < 0) {
                $errors[] = t('"{name}": fade time must be between 0 and 65535 ms.', ['name' => $label]);
            } else {
                $fields['fade_ms'] = $fade;
            }
        }
        if (!empty($def['range'])) {
            $min = cleanInt($row['param_min'] ?? null, 1000, 20000, -1);
            $max = cleanInt($row['param_max'] ?? null, 1000, 20000, -1);
            if ($min < 0 || $max < 0 || $min >= $max) {
                $errors[] = t('"{name}": Kelvin range must be within 1000-20000 and min below max.', ['name' => $label]);
            } else {
                $fields['param_min'] = $min;
                $fields['param_max'] = $max;
            }
        }
        if (!empty($def['level'])) {
            $lvl = cleanInt($row['param_level'] ?? null, 0, 254, -1);
            if ($lvl < 0) {
                $errors[] = t('"{name}": level must be between 0 and 254.', ['name' => $label]);
            } else {
                $fields['param_level'] = $lvl;
            }
        }
        qrUpdateElement($id, $code, $fields);
    }

    $newType = (string) ($post['new_type'] ?? '');
    if ($newType !== '') {
        if (elementTypeDef($newType) === null) {
            $errors[] = t('Unknown element type.');
        } else {
            qrAddElement($code, $newType, cleanName($post['new_name'] ?? ''));
        }
    }

    qrNormalizePositions($code);
    return $errors;
}

// ---------------------------------------------------------------------------
// Pages (GET)
// ---------------------------------------------------------------------------
if ($needsSetup) {
    render('admin/setup', ['title' => t('Setup'), 'area' => 'admin']);
    exit;
}

if (!isLoggedIn()) {
    render('admin/login', ['title' => t('Admin Login'), 'area' => 'admin']);
    exit;
}

switch ($site) {
    case 'list':
        render('admin/list', ['title' => t('Codes'), 'area' => 'admin', 'activeTab' => 'codes', 'codes' => qrListCodes()]);
        break;

    case 'edit':
        $entry = qrGetCode(cleanCode($_GET['code'] ?? ''));
        if ($entry === null) {
            flash(t('Code not found.'), 'error');
            redirect('admin.php?site=list');
        }
        render('admin/edit', [
            'title'     => t('Edit Code'),
            'area'      => 'admin',
            'activeTab' => 'codes',
            'entry'     => $entry,
            'elements'  => qrElements((string) $entry['code']),
        ]);
        break;

    case 'print':
        $entry = qrGetCode(cleanCode($_GET['code'] ?? ''));
        if ($entry === null) {
            flash(t('Code not found.'), 'error');
            redirect('admin.php?site=list');
        }
        render('admin/print', [
            'title'       => t('Print Code'),
            'area'        => 'admin',
            'activeTab'   => 'codes',
            'entry'       => $entry,
            'qrAvailable' => class_exists(\chillerlan\QRCode\QRCode::class),
        ]);
        break;

    case 'settings':
        render('admin/settings', [
            'title'     => t('Settings'),
            'area'      => 'admin',
            'activeTab' => 'settings',
            'effective' => settingsEffective(),
            'dbInfo'    => $config['db'],
        ]);
        break;

    case 'languages':
        render('admin/languages', [
            'title'     => t('Languages'),
            'area'      => 'admin',
            'activeTab' => 'languages',
            'languages' => languagesList(),
            'total'     => count(translationCatalogue()),
        ]);
        break;

    case 'translate':
        $language = languageGet(strtolower(trim((string) ($_GET['lang'] ?? ''))));
        if ($language === null) {
            flash(t('Language not found.'), 'error');
            redirect('admin.php?site=languages');
        }
        render('admin/translate', [
            'title'        => t('Translate'),
            'area'         => 'admin',
            'activeTab'    => 'languages',
            'language'     => $language,
            'catalogue'    => translationCatalogue(),
            'translations' => translationsFor((string) $language['code']),
            'missingOnly'  => !empty($_GET['missing']),
        ]);
        break;

    default:
        render('admin/dashboard', ['title' => t('Admin Area'), 'area' => 'admin']);
}
