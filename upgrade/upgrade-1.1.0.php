<?php

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Upgrade to version 1.1.0
 * Adds support for multiple entity types (not just products)
 */
function upgrade_module_1_1_0($module): bool
{
    $db = Db::getInstance();
    $tableName = _DB_PREFIX_ . 'wepresta_acf_field_value';

    try {
        // Step 1: Check if columns already exist
        $columns = $db->executeS("SHOW COLUMNS FROM `{$tableName}` LIKE 'entity_type'");
        if (empty($columns)) {
            // Add entity_type and entity_id columns
            $db->execute("ALTER TABLE `{$tableName}`
                ADD COLUMN `entity_type` VARCHAR(100) DEFAULT NULL COMMENT 'Entity type: product, category, customer, etc.' AFTER `id_wepresta_acf_field`,
                ADD COLUMN `entity_id` INT UNSIGNED DEFAULT NULL COMMENT 'Generic entity ID (replaces id_product)' AFTER `entity_type`");
        }

        // Step 2: Migrate existing product data
        $db->execute("UPDATE `{$tableName}`
            SET `entity_type` = 'product', `entity_id` = `id_product`
            WHERE `entity_type` IS NULL AND `entity_id` IS NULL");

        // Step 3: Make columns NOT NULL (after migration)
        $columns = $db->executeS("SHOW COLUMNS FROM `{$tableName}` WHERE Field = 'entity_type' AND Null = 'YES'");
        if (!empty($columns)) {
            $db->execute("ALTER TABLE `{$tableName}`
                MODIFY COLUMN `entity_type` VARCHAR(100) NOT NULL,
                MODIFY COLUMN `entity_id` INT UNSIGNED NOT NULL");
        }

        // Step 4: Drop old unique key and indexes if they exist
        $indexes = $db->executeS("SHOW INDEX FROM `{$tableName}` WHERE Key_name = 'unique_value'");
        if (!empty($indexes)) {
            $db->execute("ALTER TABLE `{$tableName}` DROP INDEX `unique_value`");
        }

        $indexes = $db->executeS("SHOW INDEX FROM `{$tableName}` WHERE Key_name = 'idx_product'");
        if (!empty($indexes)) {
            $db->execute("ALTER TABLE `{$tableName}` DROP INDEX `idx_product`");
        }

        $indexes = $db->executeS("SHOW INDEX FROM `{$tableName}` WHERE Key_name = 'idx_product_shop'");
        if (!empty($indexes)) {
            $db->execute("ALTER TABLE `{$tableName}` DROP INDEX `idx_product_shop`");
        }

        // Step 5: Create new unique key with entity_type + entity_id
        $indexes = $db->executeS("SHOW INDEX FROM `{$tableName}` WHERE Key_name = 'unique_value'");
        if (empty($indexes)) {
            $db->execute("ALTER TABLE `{$tableName}`
                ADD UNIQUE KEY `unique_value` (`id_wepresta_acf_field`, `entity_type`, `entity_id`, `id_shop`, `id_lang`)");
        }

        // Step 6: Create new indexes for entity_type + entity_id
        $indexes = $db->executeS("SHOW INDEX FROM `{$tableName}` WHERE Key_name = 'idx_entity'");
        if (empty($indexes)) {
            $db->execute("ALTER TABLE `{$tableName}`
                ADD INDEX `idx_entity` (`entity_type`, `entity_id`)");
        }

        $indexes = $db->executeS("SHOW INDEX FROM `{$tableName}` WHERE Key_name = 'idx_entity_shop'");
        if (empty($indexes)) {
            $db->execute("ALTER TABLE `{$tableName}`
                ADD INDEX `idx_entity_shop` (`entity_type`, `entity_id`, `id_shop`)");
        }

        return true;
    } catch (\Exception $e) {
        PrestaShopLogger::addLog('ACF Upgrade 1.1.0 failed: ' . $e->getMessage(), 3);
        return false;
    }
}

