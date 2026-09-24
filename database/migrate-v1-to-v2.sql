-- Migration from the 2022 schema (former mysql/lithernet.sql) to schema v2.
--
-- The application runs this file automatically when it detects the older schema
-- (src/migrations.php). Manual use only when the database user lacks ALTER rights.
--
-- BACK UP THE DATABASE FIRST:  mysqldump -u root -p lithernet > lithernet-backup.sql
-- Then run:                    mysql -u root -p lithernet < database/migrate-v1-to-v2.sql
--
-- Passwords: existing plain-text passwords keep working once and are replaced by a
-- bcrypt hash at the first successful login. To set a new password right away:
--   php bin/create-admin.php <username>

SET NAMES utf8mb4;

-- ---------------------------------------------------------------- user
ALTER TABLE `user`
    MODIFY `password`   VARCHAR(255) NOT NULL,
    MODIFY `last_login` TIMESTAMP NULL DEFAULT NULL,
    ADD COLUMN `failed_logins` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `password`,
    ADD COLUMN `locked_until`  DATETIME NULL DEFAULT NULL AFTER `failed_logins`;

-- The v1 dump created a redundant UNIQUE KEY next to the PRIMARY KEY.
ALTER TABLE `user` DROP INDEX `username`;

-- ---------------------------------------------------------------- qrcodes
-- v1 accepted any 3-digit number; clamp values that no longer fit.
UPDATE `qrcodes` SET `lithernet_id` = 255 WHERE `lithernet_id` IS NULL OR `lithernet_id` > 255 OR `lithernet_id` < 0;
UPDATE `qrcodes` SET `target_id`    = 0   WHERE `target_id`    IS NULL OR `target_id`    > 255 OR `target_id`    < 0;
UPDATE `qrcodes` SET `target_type`  = 0   WHERE `target_type`  IS NULL OR `target_type`  NOT IN (0, 1, 2, 4, 255);
UPDATE `qrcodes` SET `code_type`    = 0   WHERE `code_type`    IS NULL OR `code_type`    NOT IN (0, 1, 2, 3, 4);

ALTER TABLE `qrcodes`
    MODIFY `code_type`    TINYINT UNSIGNED  NOT NULL DEFAULT 0,
    MODIFY `lithernet_id` SMALLINT UNSIGNED NOT NULL DEFAULT 255,
    MODIFY `target_type`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    MODIFY `target_id`    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `qrcodes` DROP INDEX `code`;

-- ---------------------------------------------------------------- qrcode_level
-- Remove orphans, make sure every code has a level row, clamp to 0-254.
DELETE FROM `qrcode_level` WHERE `code` NOT IN (SELECT `code` FROM `qrcodes`);
INSERT IGNORE INTO `qrcode_level` (`code`) SELECT `code` FROM `qrcodes`;

UPDATE `qrcode_level` SET
    `level` = LEAST(GREATEST(`level`, 0), 254),
    `red`   = LEAST(GREATEST(`red`,   0), 254),
    `green` = LEAST(GREATEST(`green`, 0), 254),
    `blue`  = LEAST(GREATEST(`blue`,  0), 254),
    `white` = LEAST(GREATEST(`white`, 0), 254),
    `tc`    = LEAST(GREATEST(`tc`,    0), 254);

ALTER TABLE `qrcode_level`
    MODIFY `level` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    MODIFY `red`   TINYINT UNSIGNED NOT NULL DEFAULT 0,
    MODIFY `green` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    MODIFY `blue`  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    MODIFY `white` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    MODIFY `tc`    TINYINT UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE `qrcode_level` DROP INDEX `code`;

-- ---------------------------------------------------------------- charset + foreign key
ALTER TABLE `qrcodes`      CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `qrcode_level` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `user`         CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `qrcode_level`
    ADD CONSTRAINT `fk_qrcode_level_code`
        FOREIGN KEY (`code`) REFERENCES `qrcodes` (`code`)
        ON DELETE CASCADE ON UPDATE CASCADE;

-- ---------------------------------------------------------------- record schema version
CREATE TABLE IF NOT EXISTS `schema_migrations` (
    `version`    SMALLINT UNSIGNED NOT NULL,
    `applied_at` TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO `schema_migrations` (`version`) VALUES (2);
