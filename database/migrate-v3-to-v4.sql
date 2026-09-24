-- Migration from schema v3 to v4: settings stored in the database, languages and translations.
--
-- The application runs this file automatically when it detects the older schema
-- (src/migrations.php). Manual use only when the database user lacks CREATE rights.

SET NAMES utf8mb4;

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

-- ---------------------------------------------------------------- record schema version
CREATE TABLE IF NOT EXISTS `schema_migrations` (
    `version`    SMALLINT UNSIGNED NOT NULL,
    `applied_at` TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO `schema_migrations` (`version`) VALUES (4);
