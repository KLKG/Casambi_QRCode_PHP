-- Migration from schema v2 (one fixed code type per QR code) to v3
-- (free list of control elements per code, name and optional image).
--
-- Coming from the 2022 version? Run migrate-v1-to-v2.sql FIRST, then this file.
--
-- The application runs this file automatically when it detects the older schema
-- (src/migrations.php). Manual use only when the database user lacks ALTER rights.
--
-- BACK UP THE DATABASE FIRST:  mysqldump -u root -p lithernet > lithernet-backup.sql
-- Then run:                    mysql -u root -p lithernet < database/migrate-v2-to-v3.sql
--
-- Mapping of the old code types:
--   1 Level  -> element "Level"
--   2 Tc     -> elements "Level" + "Colour temperature" (old 0-254 values were not Kelvin, reset to 4000 K)
--   3 RGBW   -> elements "Level" + "Colour"
--   4 Scene  -> element "Scene" (scene number = old target id)
--   0 None   -> no elements

SET NAMES utf8mb4;

-- ---------------------------------------------------------------- new tables
CREATE TABLE IF NOT EXISTS `qrcode_elements` (
    `id`           INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `code`         VARCHAR(10)       NOT NULL,
    `position`     SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `name`         VARCHAR(60)       NOT NULL DEFAULT '',
    `element_type` VARCHAR(20)       NOT NULL,
    `target_type`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `target_id`    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `fade_ms`      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `param_min`    SMALLINT UNSIGNED NOT NULL DEFAULT 2700,
    `param_max`    SMALLINT UNSIGNED NOT NULL DEFAULT 6500,
    `param_level`  TINYINT UNSIGNED  NOT NULL DEFAULT 254,
    `state`        TEXT              NULL,
    PRIMARY KEY (`id`),
    KEY `idx_qrcode_elements_code` (`code`, `position`),
    CONSTRAINT `fk_qrcode_elements_code`
        FOREIGN KEY (`code`) REFERENCES `qrcodes` (`code`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- ---------------------------------------------------------------- qrcodes: name + updated_at
ALTER TABLE `qrcodes`
    ADD COLUMN `name` VARCHAR(60) NOT NULL DEFAULT '' AFTER `code`,
    ADD COLUMN `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

UPDATE `qrcodes` SET `name` = `code` WHERE `name` = '';

-- ---------------------------------------------------------------- convert code types to elements
-- Level element for types 1, 2, 3 (position 1)
INSERT INTO `qrcode_elements` (`code`, `position`, `name`, `element_type`, `target_type`, `target_id`, `fade_ms`, `state`)
SELECT q.`code`, 1, 'Level', 'level', q.`target_type`, q.`target_id`, 0,
       CONCAT('{"level":', COALESCE(l.`level`, 0), '}')
FROM `qrcodes` q LEFT JOIN `qrcode_level` l ON l.`code` = q.`code`
WHERE q.`code_type` IN (1, 2, 3);

-- Colour temperature for type 2 (position 2); old values were not Kelvin -> 4000 K
INSERT INTO `qrcode_elements` (`code`, `position`, `name`, `element_type`, `target_type`, `target_id`, `fade_ms`, `param_min`, `param_max`, `state`)
SELECT q.`code`, 2, 'Colour temperature', 'tc', q.`target_type`, q.`target_id`, 0, 2700, 6500, '{"tc":4000}'
FROM `qrcodes` q
WHERE q.`code_type` = 2;

-- RGBW for type 3 (position 2)
INSERT INTO `qrcode_elements` (`code`, `position`, `name`, `element_type`, `target_type`, `target_id`, `fade_ms`, `state`)
SELECT q.`code`, 2, 'Colour', 'rgbw', q.`target_type`, q.`target_id`, 0,
       CONCAT('{"red":', COALESCE(l.`red`, 0), ',"green":', COALESCE(l.`green`, 0),
              ',"blue":', COALESCE(l.`blue`, 0), ',"white":', COALESCE(l.`white`, 0), '}')
FROM `qrcodes` q LEFT JOIN `qrcode_level` l ON l.`code` = q.`code`
WHERE q.`code_type` = 3;

-- Scene for type 4 (scene number = old target id)
INSERT INTO `qrcode_elements` (`code`, `position`, `name`, `element_type`, `target_type`, `target_id`, `fade_ms`, `state`)
SELECT q.`code`, 1, 'Scene', 'scene', 4, q.`target_id`, 0,
       CONCAT('{"level":', COALESCE(l.`level`, 0), '}')
FROM `qrcodes` q LEFT JOIN `qrcode_level` l ON l.`code` = q.`code`
WHERE q.`code_type` = 4;

-- ---------------------------------------------------------------- drop old structures
DROP TABLE IF EXISTS `qrcode_level`;

ALTER TABLE `qrcodes`
    DROP COLUMN `code_type`,
    DROP COLUMN `target_type`,
    DROP COLUMN `target_id`;

-- ---------------------------------------------------------------- record schema version
CREATE TABLE IF NOT EXISTS `schema_migrations` (
    `version`    SMALLINT UNSIGNED NOT NULL,
    `applied_at` TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO `schema_migrations` (`version`) VALUES (3);
