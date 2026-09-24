-- Casambi QR-Code - database schema (v4)
--
-- The application installs this schema automatically on the first request when the
-- database is empty (src/migrations.php) and upgrades older databases with the
-- migrate-*.sql files. Manual import is only needed when the database user has no
-- CREATE / ALTER rights:
--      mysql -u lithernet -p lithernet < database/schema.sql
--
-- Create the database and user first, e.g.:
--      CREATE DATABASE lithernet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
--      CREATE USER 'lithernet'@'localhost' IDENTIFIED BY 'change-me';
--      GRANT ALL PRIVILEGES ON lithernet.* TO 'lithernet'@'localhost';

SET NAMES utf8mb4;

-- Applied schema versions (read by src/migrations.php).
CREATE TABLE IF NOT EXISTS `schema_migrations` (
    `version`    SMALLINT UNSIGNED NOT NULL,
    `applied_at` TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO `schema_migrations` (`version`) VALUES (4);

-- Settings edited in the admin area; they override the values in config/config.php.
CREATE TABLE IF NOT EXISTS `settings` (
    `name`       VARCHAR(60) NOT NULL,
    `value`      TEXT        NOT NULL,
    `updated_at` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Additional interface languages (English is built in and always available).
CREATE TABLE IF NOT EXISTS `languages` (
    `code`    VARCHAR(12)      NOT NULL,   -- e.g. de, fr, pt-br
    `name`    VARCHAR(40)      NOT NULL,   -- shown in the language switcher
    `enabled` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Translations of the fixed interface strings, keyed by the md5 of the English source text.
CREATE TABLE IF NOT EXISTS `translations` (
    `lang`        VARCHAR(12) NOT NULL,
    `source_hash` CHAR(32)    NOT NULL,
    `source`      TEXT        NOT NULL,
    `translation` TEXT        NOT NULL,
    `updated_at`  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`lang`, `source_hash`),
    CONSTRAINT `fk_translations_lang`
        FOREIGN KEY (`lang`) REFERENCES `languages` (`code`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One row per QR code.
CREATE TABLE IF NOT EXISTS `qrcodes` (
    `code`         VARCHAR(10)       NOT NULL,
    `name`         VARCHAR(60)       NOT NULL DEFAULT '',   -- shown as heading on the control page
    `lithernet_id` SMALLINT UNSIGNED NOT NULL DEFAULT 255,  -- gateway id, 255 = all gateways
    `created_at`   TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Control elements of a code (sliders / buttons), shown in `position` order.
-- element_type: level, switch, tc, rgbw, huesat, vertical, scene, scene_button, resume
CREATE TABLE IF NOT EXISTS `qrcode_elements` (
    `id`           INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `code`         VARCHAR(10)       NOT NULL,
    `position`     SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `name`         VARCHAR(60)       NOT NULL DEFAULT '',
    `element_type` VARCHAR(20)       NOT NULL,
    `target_type`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,    -- 0 broadcast, 1 device, 2 group, 3 scene active, 4 scene all, 5 vendor id, 8 multicast
    `target_id`    SMALLINT UNSIGNED NOT NULL DEFAULT 0,    -- 0-255; scene number for scene / scene_button
    `fade_ms`      SMALLINT UNSIGNED NOT NULL DEFAULT 0,    -- fade time in milliseconds (0-65535)
    `param_min`    SMALLINT UNSIGNED NOT NULL DEFAULT 2700, -- tc: lowest Kelvin of the slider
    `param_max`    SMALLINT UNSIGNED NOT NULL DEFAULT 6500, -- tc: highest Kelvin of the slider
    `param_level`  TINYINT UNSIGNED  NOT NULL DEFAULT 254,  -- scene_button: level sent when pressed
    `state`        TEXT              NULL,                  -- JSON with the last values (level, tc, red, ...)
    PRIMARY KEY (`id`),
    KEY `idx_qrcode_elements_code` (`code`, `position`),
    CONSTRAINT `fk_qrcode_elements_code`
        FOREIGN KEY (`code`) REFERENCES `qrcodes` (`code`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional photo per code, re-encoded and downscaled on upload.
CREATE TABLE IF NOT EXISTS `qrcode_images` (
    `code`       VARCHAR(10)       NOT NULL,
    `mime`       VARCHAR(32)       NOT NULL,
    `width`      SMALLINT UNSIGNED NOT NULL,
    `height`     SMALLINT UNSIGNED NOT NULL,
    `data`       MEDIUMBLOB        NOT NULL,
    `updated_at` TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`code`),
    CONSTRAINT `fk_qrcode_images_code`
        FOREIGN KEY (`code`) REFERENCES `qrcodes` (`code`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin accounts. Passwords are stored as password_hash() output (bcrypt/argon2), never in plain text.
CREATE TABLE IF NOT EXISTS `user` (
    `username`      VARCHAR(20)      NOT NULL,
    `password`      VARCHAR(255)     NOT NULL,
    `failed_logins` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `locked_until`  DATETIME         NULL DEFAULT NULL,
    `last_login`    TIMESTAMP        NULL DEFAULT NULL,
    PRIMARY KEY (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Intentionally no default user and no sample data.
