-- Migration from schema v4 to v5: element index for the dimmer / element control types.
--
-- The application runs this file automatically when it detects the older schema
-- (src/migrations.php). Manual use only when the database user lacks ALTER rights.

SET NAMES utf8mb4;

ALTER TABLE `qrcode_elements`
    ADD COLUMN `param_index` SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER `param_level`;

-- ---------------------------------------------------------------- record schema version
CREATE TABLE IF NOT EXISTS `schema_migrations` (
    `version`    SMALLINT UNSIGNED NOT NULL,
    `applied_at` TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO `schema_migrations` (`version`) VALUES (5);
