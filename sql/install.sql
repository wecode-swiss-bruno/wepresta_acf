-- WePresta ACF - Field Groups
CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_group` (
    `id_wepresta_acf_group` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` CHAR(36) NOT NULL COMMENT 'Immutable UUID for external reference',
    `title` VARCHAR(255) NOT NULL COMMENT 'Default title (fallback)',
    `slug` VARCHAR(255) NOT NULL COMMENT 'Editable slug for templates',
    `description` TEXT COMMENT 'Default description (fallback)',
    `location_rules` JSON COMMENT 'JSONLogic rules for when to display',
    `placement_tab` VARCHAR(100) NOT NULL DEFAULT 'description',
    `placement_position` VARCHAR(100) DEFAULT NULL COMMENT 'after:field_name or before:field_name',
    `priority` INT NOT NULL DEFAULT 10,
    `bo_options` JSON COMMENT 'Back-office display options',
    `fo_options` JSON COMMENT 'Front-office display options',
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    UNIQUE KEY `uuid` (`uuid`),
    UNIQUE KEY `slug` (`slug`),
    INDEX `idx_active` (`active`),
    INDEX `idx_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Group translations (multilang metadata)
CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_group_lang` (
    `id_wepresta_acf_group` INT UNSIGNED NOT NULL,
    `id_lang` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    PRIMARY KEY (`id_wepresta_acf_group`, `id_lang`),
    CONSTRAINT `fk_wepresta_acf_group_lang_group`
        FOREIGN KEY (`id_wepresta_acf_group`)
        REFERENCES `PREFIX_wepresta_acf_group`(`id_wepresta_acf_group`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Group to Shop association (multi-shop support)
CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_group_shop` (
    `id_wepresta_acf_group` INT UNSIGNED NOT NULL,
    `id_shop` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id_wepresta_acf_group`, `id_shop`),
    CONSTRAINT `fk_wepresta_acf_group_shop_group` 
        FOREIGN KEY (`id_wepresta_acf_group`) 
        REFERENCES `PREFIX_wepresta_acf_group`(`id_wepresta_acf_group`) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Field Definitions
CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_field` (
    `id_wepresta_acf_field` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` CHAR(36) NOT NULL COMMENT 'Immutable UUID',
    `id_wepresta_acf_group` INT UNSIGNED NOT NULL,
    `id_parent` INT UNSIGNED DEFAULT NULL COMMENT 'For repeaters and nested fields',
    `type` VARCHAR(50) NOT NULL,
    `title` VARCHAR(255) NOT NULL COMMENT 'Default title (fallback)',
    `slug` VARCHAR(255) NOT NULL COMMENT 'Unique within group',
    `instructions` TEXT COMMENT 'Default instructions (fallback)',
    `config` JSON COMMENT 'Type-specific configuration',
    `validation` JSON COMMENT 'Validation rules',
    `conditions` JSON COMMENT 'Intra-group conditions (show/required/disabled)',
    `wrapper` JSON COMMENT 'HTML wrapper options (class, id, width)',
    `fo_options` JSON COMMENT 'Front-office rendering options',
    `position` INT NOT NULL DEFAULT 0,
    `value_translatable` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Whether field VALUES are translatable (not metadata)',
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    UNIQUE KEY `uuid` (`uuid`),
    UNIQUE KEY `unique_slug_group` (`id_wepresta_acf_group`, `slug`),
    INDEX `idx_type` (`type`),
    INDEX `idx_position` (`id_wepresta_acf_group`, `position`),
    INDEX `idx_parent` (`id_parent`),
    CONSTRAINT `fk_wepresta_acf_field_group` 
        FOREIGN KEY (`id_wepresta_acf_group`) 
        REFERENCES `PREFIX_wepresta_acf_group`(`id_wepresta_acf_group`) 
        ON DELETE CASCADE,
    CONSTRAINT `fk_wepresta_acf_field_parent` 
        FOREIGN KEY (`id_parent`) 
        REFERENCES `PREFIX_wepresta_acf_field`(`id_wepresta_acf_field`) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Field translations (multilang metadata: title, instructions, placeholder)
CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_field_lang` (
    `id_wepresta_acf_field` INT UNSIGNED NOT NULL,
    `id_lang` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `instructions` TEXT,
    `placeholder` VARCHAR(255) DEFAULT NULL COMMENT 'Translated placeholder for text fields',
    PRIMARY KEY (`id_wepresta_acf_field`, `id_lang`),
    CONSTRAINT `fk_wepresta_acf_field_lang_field`
        FOREIGN KEY (`id_wepresta_acf_field`)
        REFERENCES `PREFIX_wepresta_acf_field`(`id_wepresta_acf_field`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Field Values (entity data - supports multiple entity types: product, category, customer, etc.)
CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_field_value` (
    `id_wepresta_acf_field_value` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `id_wepresta_acf_field` INT UNSIGNED NOT NULL,
    `entity_type` VARCHAR(100) NOT NULL COMMENT 'Entity type: product, category, customer, etc.',
    `entity_id` INT UNSIGNED NOT NULL COMMENT 'Generic entity ID',
    `id_product` INT UNSIGNED DEFAULT NULL COMMENT 'Legacy: kept for backward compatibility',
    `id_shop` INT UNSIGNED NOT NULL DEFAULT 1,
    `id_lang` INT UNSIGNED DEFAULT NULL COMMENT 'NULL = non-translatable field',
    `value` LONGTEXT COMMENT 'JSON or string depending on field type',
    `value_index` VARCHAR(255) DEFAULT NULL COMMENT 'Indexed value for search/sort',
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    UNIQUE KEY `unique_value` (`id_wepresta_acf_field`, `entity_type`, `entity_id`, `id_shop`, `id_lang`),
    INDEX `idx_entity` (`entity_type`, `entity_id`),
    INDEX `idx_entity_shop` (`entity_type`, `entity_id`, `id_shop`),
    INDEX `idx_search` (`id_wepresta_acf_field`, `value_index`),
    -- Legacy indexes (can be removed in future version)
    INDEX `idx_product` (`id_product`),
    INDEX `idx_product_shop` (`id_product`, `id_shop`),
    CONSTRAINT `fk_wepresta_acf_value_field`
        FOREIGN KEY (`id_wepresta_acf_field`)
        REFERENCES `PREFIX_wepresta_acf_field`(`id_wepresta_acf_field`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
