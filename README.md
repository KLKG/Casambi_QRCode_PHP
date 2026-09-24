# Casambi_QRCode_PHP

Generate and manage QR codes that control a Casambi lighting network locally via the
[Lithernet Casambi Gateway](https://casambi.lithernet.de). Scan a code with the phone camera
(or type it in) and a control page appears with exactly the sliders and buttons the admin has
configured for that code: a name, an optional photo of the luminaire and any combination of
level, colour temperature, RGBW, hue/saturation, direct/indirect, scene and automation controls.

Screenshots, a demo and background information: **[qrcode.lithernet.de](https://qrcode.lithernet.de)**

The web cam only works over **HTTPS** (browser requirement for camera access).

--------------------------------------------------------------------------------------

## Topics
1. [Requirements](#requirements)
2. [Installation with Docker](#installation-with-docker)
3. [Installation on an existing web server](#installation-on-an-existing-web-server)
4. [Updating](#updating)
5. [Project structure](#project-structure)
6. [Using the application](#using-the-application)
7. [Control elements and protocol](#control-elements-and-protocol)
8. [Security notes](#security-notes)
9. [Third-party components](#third-party-components)
10. [About the project](#about-the-project)

--------------------------------------------------------------------------------------

## Requirements

Either **Docker** (Docker Engine with the compose plugin, e.g. on a small Linux board in the
same network as the gateway), or a web server with

- PHP 8.2 or newer with the extensions `mysqli`, `sockets`, `gd`, `mbstring`
- MySQL 8 / MariaDB 10.4 or newer
- Apache with `mod_rewrite` (or any web server whose document root can point at `public/`)
- [Composer](https://getcomposer.org/) for the QR image library
- HTTPS certificate (camera access)

The server must be in the network segment of the gateway: commands are sent as UDP broadcast.

## Installation with Docker

The Docker setup ships PHP 8.3 with Apache, a MariaDB container and a self-signed HTTPS
certificate. It also works on hosts with an old system PHP.

1. **Get the files** and create the environment file with the database passwords:

   ```
   git clone https://github.com/KLKG/Casambi_QRCode_PHP.git
   cd Casambi_QRCode_PHP
   cp .env.example .env
   ```

   Set `DB_ROOT_PASSWORD` and `DB_PASSWORD` in `.env` to your own values (for example from
   `openssl rand -base64 18`). They are applied when the database is created on the first
   start. `.env` is ignored by git.

2. **Build and start**:

   ```
   docker compose up -d --build
   ```

   The app container uses the host network so that UDP broadcasts reach the gateway. It listens
   on port 8080 (HTTP, redirects to HTTPS) and 8443 (HTTPS); both can be changed via
   `HTTP_PORT` / `HTTPS_PORT` in `docker-compose.yml`. On the first start a self-signed
   certificate is written to `config/ssl/`; to use your own, put `cert.pem` and `key.pem` there
   and restart. The database is only reachable from the host itself on `127.0.0.1:3307`.

3. **Setup wizard**: open `https://<ip-of-the-host>:8443` and accept the certificate warning
   (self-signed). The wizard checks the requirements and asks for the database connection:
   host `127.0.0.1`, port `3307`, user `lithernet`, the `DB_PASSWORD` from `.env`, database
   `lithernet`. Enter the broadcast address and UDP port of the gateway and the operation mode
   (`demo` = store values only, `run` = send commands). "Save and continue" writes
   `config/config.php`, creates the tables and opens the form for the first admin account.

`config/`, `logs/` and the database volume `db-data` persist across rebuilds; the source code
lives in the image and is replaced on every build (see [Updating](#updating)).

## Installation on an existing web server

1. **Files**: clone or copy the repository to the server and run

   ```
   composer install --no-dev
   ```

2. **Web server**: point the document root at `public/`.
   If that is not possible (shared hosting), keep the repository root as document root: the
   included `.htaccess` routes every request into `public/` and the other directories deny
   direct access.

3. **Database user**: create a user with all rights on the (new or empty) database:

   ```sql
   CREATE USER 'lithernet'@'localhost' IDENTIFIED BY 'change-me';
   GRANT ALL PRIVILEGES ON lithernet.* TO 'lithernet'@'localhost';
   ```

   The setup wizard can create the database itself and the tables are created automatically
   on the first request. If you prefer a user with `SELECT, INSERT, UPDATE, DELETE` only,
   create the database and import `database/schema.sql` by hand once.

4. **Setup wizard**: make `config/` and `logs/` writable by the web server, then open the site
   in the browser. Without a configuration every page redirects to `setup.php`, which

   - checks PHP version, extensions, writable directories, Composer packages and HTTPS,
   - asks for the database connection (with "test connection" and "create database if
     missing"), the gateway broadcast address and port and the operation mode,
   - writes `config/config.php` and continues to the admin area, where the tables are created
     and the first admin account is set up.

   `config/config.php` is ignored by git; all further options are documented in
   `config/config.example.php`. To protect the wizard on a publicly reachable server, put a
   secret into `config/setup.key` before opening the page; the wizard then asks for it.

   Without browser access run the same setup on the shell (database, gateway, admin account):

   ```
   php bin/setup.php
   ```

   For photo uploads `upload_max_filesize` and `post_max_size` in `php.ini` must be at least
   the configured photo upload limit (default 8 MB).

5. **Admin account**: after the wizard the admin area shows the form "Create the admin account"
   (this is not a login): choose the username and password of the first administrator, then log
   in with them. To add or reset an account later:

   ```
   php bin/create-admin.php <username>
   ```

## Updating

Configuration (`config/`), logs, photos, codes and settings are never touched by an update; the
database schema is upgraded automatically on the first request after the update. Back up the
database before major version jumps anyway (`mysqldump -u root -p lithernet > backup.sql`, or
with Docker `docker compose exec db mariadb-dump -u root -p lithernet > backup.sql`).

### Docker

```
docker/update.sh
```

The script fetches the current sources from GitHub, exits with "keine Änderungen" when there is
nothing new, otherwise pulls, rebuilds the app image (with fresh base images) and restarts the
container. Old build layers are pruned; volumes stay. `docker/update.sh --force` rebuilds even
without new commits, which also picks up security updates of the PHP base image.

To update automatically, run it from cron, e.g. daily at 04:00:

```
0 4 * * * /path/to/Casambi_QRCode_PHP/docker/update.sh >> /path/to/Casambi_QRCode_PHP/logs/update.log 2>&1
```

Manually the same happens with `git pull` followed by `docker compose up -d --build`. Note that
local changes to the source are lost with the next build; the image always contains the
checked-out commit.

### Existing web server

```
git pull            # or copy the new files over the old ones
composer install --no-dev
```

Then open any page: the application detects the schema version and applies the missing
migration steps, writes them to the error log and shows them once in the admin area.

### Database schema

The version is stored in the `schema_migrations` table; databases created before that table
existed are recognised by their structure. Upgrading needs `CREATE, ALTER, DROP, INDEX,
REFERENCES` rights for the database user; without them a plain text page explains what to do
and nothing is changed. In that case run the scripts by hand:

| Coming from | Run in this order |
|---|---|
| 2022 version (`mysql/lithernet.sql`, plain-text passwords) | `migrate-v1-to-v2.sql`, `migrate-v2-to-v3.sql`, `migrate-v3-to-v4.sql`, `migrate-v4-to-v5.sql` |
| v2 (one fixed code type per code) | `migrate-v2-to-v3.sql`, `migrate-v3-to-v4.sql`, `migrate-v4-to-v5.sql` |
| v3 (control elements, no settings / languages tables) | `migrate-v3-to-v4.sql`, `migrate-v4-to-v5.sql` |
| v4 (settings and languages, no element index) | `migrate-v4-to-v5.sql` |

What the migrations do:

- **v1 to v2**: password column for hashes, login throttling columns, value ranges, foreign
  keys. Existing plain-text passwords are converted to a bcrypt hash automatically at the first
  successful login. Passwords used to be cut to 10 lowercase letters/digits; after the first
  login the full password you typed is what counts.
- **v2 to v3**: every code gets a name (initially the code itself) and its fixed type is turned
  into control elements: Level becomes a level slider, Tc a level plus colour temperature slider
  (the old 0-254 values were not Kelvin, they are reset to 4000 K), RGBW a level plus RGBW slider
  group, Scene a scene slider. Old codes of type None get no elements.
- **v3 to v4**: adds the `settings`, `languages` and `translations` tables for the admin tabs
  Settings and Languages. Existing data is not touched.
- **v4 to v5**: adds the element index column used by the dimmer and element control types.

Printed QR codes keep working across all versions: the codes themselves are unchanged.

## Project structure

```
public/                 document root
  index.php             start page, scanner, code entry, control page
  control.php           JSON endpoint: store element values and send the gateway command
  image.php             streams the photo of a code
  admin.php             admin area (first admin, login, codes, settings, languages)
  qr.php                streams the QR PNG for a code (admin only)
  setup.php             first-run wizard, only while config/config.php is missing
  assets/css|js|fonts|img
src/
  bootstrap.php         config, error handling, session, security headers, schema check
  helpers.php           escaping, input cleaning, CSRF, flash messages, render()
  db.php                mysqli connection + prepared-statement helpers
  auth.php              password hashing, login throttling, logout
  casambi.php           target types, element type registry, command strings, UDP sending
  qrcodes.php           repository for codes, elements and images
  migrations.php        automatic schema installation and upgrades
  settings.php          runtime settings stored in the database (admin tab "Settings")
  i18n.php              languages, t() translation function, string catalogue
  setup.php             requirement checks, connection test, writing config.php
  icons.php             inline SVG icons (Bootstrap Icons)
  templates/            HTML templates (layout, index/, admin/, setup/)
config/                 config.example.php (template), config.php (local, ignored), setup.key (optional), ssl/ (Docker)
database/               schema.sql and the migrate-vN-to-vN+1.sql scripts
bin/setup.php           CLI: first-run setup (config, schema, admin account)
bin/create-admin.php    CLI: create admin / reset password
docker/                 Dockerfile, Apache and PHP configuration, entrypoint, update.sh
docker-compose.yml      app + MariaDB; passwords in .env (template: .env.example)
logs/                   PHP error log (ignored)
vendor/                 Composer packages (ignored)
```

--------------------------------------------------------------------------------------

## Using the application

**Public pages** (`index.php`): the start page offers "Scan" (camera) and "Enter Code". A
recognised or typed code opens its control page with the code's name, its photo (if one was
uploaded) and the configured elements in the configured order. Slider changes are sent when
the slider is released, buttons send immediately. Every element stores only its own values, so
changing the colour temperature never resets the stored level.

**Admin area** (`admin.php`), three tabs after login:

- **Codes**: **Add Code** creates a new random code and opens its edit page. There you set the
  **Name** (heading of the control page) and **Lithernet ID** (255 = all gateways), an optional
  **photo** (JPEG, PNG, GIF or WebP; downscaled, stored as JPEG in the database, re-encoded so
  no metadata survives) and the list of **control elements**: choose a type in "Add element",
  press save, then give the element a name, order number, target type / target id, fade time
  and, depending on the type, a Kelvin range or a fixed level. Tick "Delete" and save to remove
  an element. **Preview** opens the public control page, **QR code** shows and downloads the PNG
  for printing.
- **Settings**: gateway broadcast address, UDP port and operation mode, default language, admin
  session timeout, login lockout, photo limits. Values saved here are stored in the database
  and override `config/config.php`; a badge next to each field shows where the current value
  comes from, and "reset to config.php" removes all stored overrides. The database access itself
  stays in `config/config.php`.
- **Languages**: English is built in and the source of every fixed text. Add a language
  (browser code such as `de`, `fr`, `pt-br` plus a display name), open its translation editor
  and fill in the strings; the list of strings is read from the source code, so it is always
  complete, and empty entries fall back to English. Enable the language when ready. Visitors get
  a language by explicit choice (the EN / DE switcher in the navigation, remembered in the
  session), then browser preference, then the default language from the settings. Translations
  cover the public pages, the admin area and the status messages of the control page.

Screenshots of all pages: [qrcode.lithernet.de](https://qrcode.lithernet.de)

--------------------------------------------------------------------------------------

## Control elements and protocol

| Element type | Shown as | Sends |
|---|---|---|
| Level slider | 1 slider 0-254 | SetLevel (32) |
| On / Off buttons | 2 buttons | SetLevel (32) with 254 / 0 |
| Colour temperature slider | 1 slider, min-max Kelvin | SetTargetColorTemperature (72) |
| RGBW sliders | 4 sliders 0-254 | SetTargetColorRGBW (47) |
| Hue / saturation sliders | hue 0-359, saturation, white | SetTargetColorHueSat (61) |
| Direct / indirect slider | 1 slider 0-254 | SetTargetVertical (49) |
| Scene level slider | 1 slider 0-254 | SetSceneLevel (30), scene number = target id |
| Scene button | 1 button, fixed level | SetSceneLevel (30) |
| Group level slider | 1 slider 0-254 | SetGroupLevel (31), group number = target id |
| Resume automation button | 1 button | ResumeAutomation (74) |
| Dimmer slider | 1 slider 0-254 | SetTargetDimmers (62), dimmer selected by element index |
| Element slider | 1 slider 0-254 | SetTargetElements (63), element selected by element index |
| Colour XY sliders | x and y 0-65535 | SetTargetColorXY (56) |
| Push button | 1 button, hold to press | PushButtonPressed (16) while held, PushButtonReleased (17) on release; button id = target id |
| Push button level slider | 1 slider 0-254 | SetPushButtonLevel (33), button id = target id |

Several elements of any type can be combined on one code, e.g. one dimmer slider per dimmer of
a luminaire (element index 0, 1, 2, …) or several push buttons.

```
Target Type (per element):
0 = Broadcast          4 = Scene All
1 = Device             5 = Vendor ID
2 = Group              8 = Multicast (Unit Set index as target id)
3 = Scene Active
```
```
Target ID:
Broadcast => 0
Device    => 1-250
Group     => 0 = ungrouped, 1-255 = group
Scene     => 1-255 = scene number (scene slider / scene button)
Multicast => 0-15 = unit set index defined in the gateway
```

Commands are sent as UDP broadcast to the gateway address and port from the settings (default
`192.168.1.255:10009`) in the gateway's int format `<gateway id>#114#<length>#<opcode>#…`.
16-bit values (fade time in ms, Kelvin, hue) are sent as explicit low/high bytes:

```
level / on-off : <gw>#114#6#32#<level>#<fadeLo>#<fadeHi>#<target_type>#<target_id>
tc             : <gw>#114#7#72#<kelvinLo>#<kelvinHi>#<fadeLo>#<fadeHi>#<target_type>#<target_id>
rgbw           : <gw>#114#8#47#<red>#<green>#<blue>#<white>#<target_type>#<target_id>#255
hue/sat        : <gw>#114#8#61#<hueLo>#<hueHi>#<sat>#<white>#<target_type>#<target_id>#255
vertical       : <gw>#114#6#49#<ratio>#<fadeLo>#<fadeHi>#<target_type>#<target_id>
scene          : <gw>#114#5#30#<scene>#<level>#<fadeLo>#<fadeHi>
group          : <gw>#114#5#31#<group>#<level>#<fadeLo>#<fadeHi>
resume         : <gw>#114#3#74#<target_type>#<target_id>
dimmer         : <gw>#114#7#62#<target_type>#<target_id>#<fadeLo>#<fadeHi>#<element_index>#<level>
element        : <gw>#114#7#63#<target_type>#<target_id>#<fadeLo>#<fadeHi>#<element_index>#<value>
xy             : <gw>#114#7#56#<xLo>#<xHi>#<yLo>#<yHi>#<target_type>#<target_id>
push button    : <gw>#114#2#16#<button_id>   (pressed)   /   <gw>#114#2#17#<button_id>   (released)
button level   : <gw>#114#3#33#<button_id>#<level>
```

The trailing 255 of the colour commands keeps the current brightness; add a level slider
element to control it.

## Security notes

- Admin passwords are stored with `password_hash()`; after 5 failed logins (configurable) the
  account is locked for 15 minutes.
- Every state-changing request is a POST with a CSRF token; the session cookie is
  `HttpOnly`, `SameSite=Strict` and `Secure` on HTTPS. Admin sessions expire after 30 minutes
  of inactivity (configurable).
- All SQL uses prepared statements; all output is escaped. Uploaded photos are validated,
  decoded and re-encoded with GD before they are stored.
- A Content-Security-Policy, `X-Frame-Options`, `Referrer-Policy` and
  `Permissions-Policy` are sent by `src/bootstrap.php`.
- QR codes are 40-bit random values (`random_bytes`). They are the only protection of the
  public control page, so treat printed codes like keys and run the app inside the local
  network or behind HTTPS.
- Errors are logged to `logs/php-error.log` and never displayed to visitors.

## Third-party components

| Component | Version | License | Used for |
|-----------|---------|---------|----------|
| [Bootstrap](https://getbootstrap.com/) via [Start Bootstrap Freelancer](https://startbootstrap.com/theme/freelancer) | 5.1.3 / 7.0.6 | MIT | layout (`public/assets/css/style.css`) |
| [Bootstrap Icons](https://icons.getbootstrap.com/) | 1.13.1 | MIT | inline SVG icons (`src/icons.php`) |
| [barcode-detector](https://github.com/Sec-ant/barcode-detector) | 3.2.2 | MIT | QR scanning in the browser |
| [zxing-wasm](https://github.com/Sec-ant/zxing-wasm) | 3.1.3 | MIT | WebAssembly decoder used by barcode-detector, served from `public/assets/js/vendor/` |
| [chillerlan/php-qrcode](https://github.com/chillerlan/php-qrcode) | ^6.0 | MIT | QR image generation (Composer) |
| [Montserrat](https://fonts.google.com/specimen/Montserrat), [Lato](https://fonts.google.com/specimen/Lato) | v23 / v22 | SIL OFL 1.1 | self-hosted fonts |
| [php](https://hub.docker.com/_/php) 8.3-apache, [mariadb](https://hub.docker.com/_/mariadb) 11 | | PHP License / GPL-2.0 | Docker base images |

Replaced since the 2022 version: `phpqrcode` (LGPL, unmaintained since 2010), `html5-qrcode`
(Apache-2.0, no releases since 2023) and Font Awesome (CC BY 4.0 icons).

## About the project

We are not web designers and are happy about every hint / help that advances this small
project. Our goal is to give a little food for thought for the possibilities of our gateway.

Feel free to develop the project further. As part of our possibilities, we will also do some
fine-tuning. If you want more information or a demo, just contact us:
[qrcode.lithernet.de](https://qrcode.lithernet.de) / [casambi.lithernet.de](https://casambi.lithernet.de)

License: MIT (see `LICENSE`).
