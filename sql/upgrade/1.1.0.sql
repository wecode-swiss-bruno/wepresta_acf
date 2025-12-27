-- WePresta ACF - Upgrade to 1.1.0
-- Adds support for multiple entity types (not just products)

-- Step 1: Add entity_type and entity_id columns
ALTER TABLE `PREFIX_wepresta_acf_field_value`
    ADD COLUMN `entity_type` VARCHAR(100) DEFAULT NULL COMMENT 'Entity type: product, category, customer, etc.' AFTER `id_wepresta_acf_field`,
    ADD COLUMN `entity_id` INT UNSIGNED DEFAULT NULL COMMENT 'Generic entity ID (replaces id_product)' AFTER `entity_type`;

-- Step 2: Migrate existing product data
UPDATE `PREFIX_wepresta_acf_field_value`
SET
    `entity_type` = 'product',
    `entity_id` = `id_product`
WHERE `entity_type` IS NULL AND `entity_id` IS NULL;

-- Step 3: Make entity_type and entity_id NOT NULL (after migration)
ALTER TABLE `PREFIX_wepresta_acf_field_value`
    MODIFY COLUMN `entity_type` VARCHAR(100) NOT NULL,
    MODIFY COLUMN `entity_id` INT UNSIGNED NOT NULL;

-- Step 4: Drop old unique key and indexes
ALTER TABLE `PREFIX_wepresta_acf_field_value`
    DROP INDEX `unique_value`,
    DROP INDEX `idx_product`,
    DROP INDEX `idx_product_shop`;

-- Step 5: Create new unique key with entity_type + entity_id
ALTER TABLE `PREFIX_wepresta_acf_field_value`
    ADD UNIQUE KEY `unique_value` (`id_wepresta_acf_field`, `entity_type`, `entity_id`, `id_shop`, `id_lang`);

-- Step 6: Create new indexes for entity_type + entity_id
ALTER TABLE `PREFIX_wepresta_acf_field_value`
    ADD INDEX `idx_entity` (`entity_type`, `entity_id`),
    ADD INDEX `idx_entity_shop` (`entity_type`, `entity_id`, `id_shop`);

-- Step 7: Keep id_product column for backward compatibility (can be dropped in future version)
-- The column remains but is no longer used for new inserts
-- Existing code will continue to work until fully migrated

