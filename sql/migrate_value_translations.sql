-- Migration script: Move translations from wepresta_acf_field_value.id_lang to wepresta_acf_field_value_lang
-- This script migrates existing data when upgrading to the new translation system

-- Step 1: Create the new lang table if it doesn't exist
CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_field_value_lang` (
    `id_wepresta_acf_field_value` INT UNSIGNED NOT NULL,
    `id_lang` INT UNSIGNED NOT NULL,
    `value` LONGTEXT COMMENT 'Translated value',
    `value_index` VARCHAR(255) DEFAULT NULL COMMENT 'Indexed value for search/sort',
    PRIMARY KEY (`id_wepresta_acf_field_value`, `id_lang`),
    INDEX `idx_lang` (`id_lang`),
    INDEX `idx_search` (`id_wepresta_acf_field_value`, `value_index`),
    CONSTRAINT `fk_wepresta_acf_value_lang_value`
        FOREIGN KEY (`id_wepresta_acf_field_value`)
        REFERENCES `PREFIX_wepresta_acf_field_value`(`id_wepresta_acf_field_value`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 2: Migrate existing translations (where id_lang IS NOT NULL) to the lang table
-- First, ensure we have a main value record for each field (with id_lang IS NULL)
-- Then, copy translated values to the lang table
INSERT INTO `PREFIX_wepresta_acf_field_value_lang` (`id_wepresta_acf_field_value`, `id_lang`, `value`, `value_index`)
SELECT 
    `id_wepresta_acf_field_value`,
    `id_lang`,
    `value`,
    `value_index`
FROM `PREFIX_wepresta_acf_field_value`
WHERE `id_lang` IS NOT NULL
ON DUPLICATE KEY UPDATE
    `value` = VALUES(`value`),
    `value_index` = VALUES(`value_index`);

-- Step 3: For translatable fields, ensure main value is set to default language
-- If main value (id_lang IS NULL) doesn't exist but translations do, copy default language as main
UPDATE `PREFIX_wepresta_acf_field_value` AS fv_main
INNER JOIN `PREFIX_wepresta_acf_field_value` AS fv_trans ON 
    fv_main.`id_wepresta_acf_field` = fv_trans.`id_wepresta_acf_field`
    AND fv_main.`entity_type` = fv_trans.`entity_type`
    AND fv_main.`entity_id` = fv_trans.`entity_id`
    AND fv_main.`id_shop` = fv_trans.`id_shop`
INNER JOIN `PREFIX_wepresta_acf_field` AS f ON fv_main.`id_wepresta_acf_field` = f.`id_wepresta_acf_field`
INNER JOIN (
    SELECT `id_lang` 
    FROM `PREFIX_lang` 
    WHERE `id_lang` = (SELECT `value` FROM `PREFIX_configuration` WHERE `name` = 'PS_LANG_DEFAULT' LIMIT 1)
) AS default_lang ON fv_trans.`id_lang` = default_lang.`id_lang`
WHERE fv_main.`id_lang` IS NULL 
    AND fv_trans.`id_lang` IS NOT NULL
    AND (f.`value_translatable` = 1 OR f.`translatable` = 1)
    AND (fv_main.`value` IS NULL OR fv_main.`value` = '')
SET 
    fv_main.`value` = fv_trans.`value`,
    fv_main.`value_index` = fv_trans.`value_index`;

-- Step 4: Remove id_lang column from main table (run after ensuring all data is migrated)
-- ALTER TABLE `PREFIX_wepresta_acf_field_value` DROP COLUMN `id_lang`;

-- Step 5: Update unique key constraint (remove id_lang from unique constraint)
-- ALTER TABLE `PREFIX_wepresta_acf_field_value` DROP INDEX `unique_value`;
-- ALTER TABLE `PREFIX_wepresta_acf_field_value` ADD UNIQUE KEY `unique_value` (`id_wepresta_acf_field`, `entity_type`, `entity_id`, `id_shop`);

-- Note: Steps 4 and 5 are commented out for safety. 
-- Uncomment them after verifying that all data has been migrated correctly.
