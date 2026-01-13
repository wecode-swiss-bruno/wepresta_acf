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
    `value` LONGTEXT COMMENT 'JSON or string depending on field type (default language or non-translatable)',
    `value_index` VARCHAR(255) DEFAULT NULL COMMENT 'Indexed value for search/sort',
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    UNIQUE KEY `unique_value` (`id_wepresta_acf_field`, `entity_type`, `entity_id`, `id_shop`),
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

-- Field Value Translations (for translatable field values)
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

-- =========================================================================
-- WePresta ACF - Custom Post Types (CPT) Module
-- =========================================================================
-- This SQL file contains the database schema for the CPT functionality
-- To be executed during module installation/upgrade
-- =========================================================================

-- =========================================================================
-- CPT Type Definition (Blog, Portfolio, Events, etc.)
-- =========================================================================
CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_cpt_type` (
    `id_wepresta_acf_cpt_type` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` CHAR(36) NOT NULL COMMENT 'Immutable UUID for external reference',
    `slug` VARCHAR(255) NOT NULL COMMENT 'Unique identifier (blog, portfolio, etc.)',
    `name` VARCHAR(255) NOT NULL COMMENT 'Default name (fallback)',
    `description` TEXT COMMENT 'Default description (fallback)',
    `config` JSON COMMENT 'Type configuration: labels, capabilities, supports',
    `url_prefix` VARCHAR(255) NOT NULL COMMENT 'URL prefix for front-office (/blog, /portfolio, etc.)',
    `has_archive` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Enable archive page',
    `archive_slug` VARCHAR(255) DEFAULT NULL COMMENT 'Custom archive slug (if different from url_prefix)',
    `seo_config` JSON COMMENT 'SEO patterns: title_pattern, description_pattern',
    `icon` VARCHAR(50) DEFAULT 'article' COMMENT 'Icon identifier',
    `position` INT NOT NULL DEFAULT 0 COMMENT 'Menu position',
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    UNIQUE KEY `uuid` (`uuid`),
    UNIQUE KEY `slug` (`slug`),
    INDEX `idx_active` (`active`),
    INDEX `idx_position` (`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- CPT Type Translations (multilang)
-- =========================================================================
CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_cpt_type_lang` (
    `id_wepresta_acf_cpt_type` INT UNSIGNED NOT NULL,
    `id_lang` INT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    PRIMARY KEY (`id_wepresta_acf_cpt_type`, `id_lang`),
    CONSTRAINT `fk_wepresta_acf_cpt_type_lang_type`
        FOREIGN KEY (`id_wepresta_acf_cpt_type`)
        REFERENCES `PREFIX_wepresta_acf_cpt_type`(`id_wepresta_acf_cpt_type`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- CPT Type to Shop association (multi-shop, per boutique)
-- =========================================================================
CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_cpt_type_shop` (
    `id_wepresta_acf_cpt_type` INT UNSIGNED NOT NULL,
    `id_shop` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id_wepresta_acf_cpt_type`, `id_shop`),
    CONSTRAINT `fk_wepresta_acf_cpt_type_shop_type`
        FOREIGN KEY (`id_wepresta_acf_cpt_type`)
        REFERENCES `PREFIX_wepresta_acf_cpt_type`(`id_wepresta_acf_cpt_type`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================================
-- CPT Type to ACF Group association (which fields to show)
-- =========================================================================
CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_cpt_type_group` (
    `id_wepresta_acf_cpt_type` INT UNSIGNED NOT NULL,
    `id_wepresta_acf_group` INT UNSIGNED NOT NULL,
    `position` INT NOT NULL DEFAULT 0 COMMENT 'Display order',
    PRIMARY KEY (`id_wepresta_acf_cpt_type`, `id_wepresta_acf_group`),
    INDEX `idx_position` (`id_wepresta_acf_cpt_type`, `position`),
    CONSTRAINT `fk_wepresta_acf_cpt_type_group_type`
        FOREIGN KEY (`id_wepresta_acf_cpt_type`)
        REFERENCES `PREFIX_wepresta_acf_cpt_type`(`id_wepresta_acf_cpt_type`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_wepresta_acf_cpt_type_group_group`
        FOREIGN KEY (`id_wepresta_acf_group`)
        REFERENCES `PREFIX_wepresta_acf_group`(`id_wepresta_acf_group`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================================
-- CPT Taxonomy Definition (Hierarchical categories only)
-- =========================================================================
CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_cpt_taxonomy` (
    `id_wepresta_acf_cpt_taxonomy` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` CHAR(36) NOT NULL COMMENT 'Immutable UUID',
    `slug` VARCHAR(255) NOT NULL COMMENT 'Unique identifier (category, tag, etc.)',
    `name` VARCHAR(255) NOT NULL COMMENT 'Default name (fallback)',
    `description` TEXT COMMENT 'Default description (fallback)',
    `hierarchical` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Always true for now',
    `config` JSON COMMENT 'Additional configuration',
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    UNIQUE KEY `uuid` (`uuid`),
    UNIQUE KEY `slug` (`slug`),
    INDEX `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- CPT Taxonomy Translations (multilang)
-- =========================================================================
CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_cpt_taxonomy_lang` (
    `id_wepresta_acf_cpt_taxonomy` INT UNSIGNED NOT NULL,
    `id_lang` INT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    PRIMARY KEY (`id_wepresta_acf_cpt_taxonomy`, `id_lang`),
    CONSTRAINT `fk_wepresta_acf_cpt_taxonomy_lang_taxonomy`
        FOREIGN KEY (`id_wepresta_acf_cpt_taxonomy`)
        REFERENCES `PREFIX_wepresta_acf_cpt_taxonomy`(`id_wepresta_acf_cpt_taxonomy`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- CPT Type to Taxonomy association (which taxonomies for each type)
-- =========================================================================
CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_cpt_type_taxonomy` (
    `id_wepresta_acf_cpt_type` INT UNSIGNED NOT NULL,
    `id_wepresta_acf_cpt_taxonomy` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id_wepresta_acf_cpt_type`, `id_wepresta_acf_cpt_taxonomy`),
    CONSTRAINT `fk_wepresta_acf_cpt_type_taxonomy_type`
        FOREIGN KEY (`id_wepresta_acf_cpt_type`)
        REFERENCES `PREFIX_wepresta_acf_cpt_type`(`id_wepresta_acf_cpt_type`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_wepresta_acf_cpt_type_taxonomy_taxonomy`
        FOREIGN KEY (`id_wepresta_acf_cpt_taxonomy`)
        REFERENCES `PREFIX_wepresta_acf_cpt_taxonomy`(`id_wepresta_acf_cpt_taxonomy`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================================
-- CPT Terms (taxonomy items with hierarchy support)
-- =========================================================================
CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_cpt_term` (
    `id_wepresta_acf_cpt_term` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `id_wepresta_acf_cpt_taxonomy` INT UNSIGNED NOT NULL,
    `id_parent` INT UNSIGNED DEFAULT NULL COMMENT 'Parent term for hierarchy',
    `slug` VARCHAR(255) NOT NULL,
    `name` VARCHAR(255) NOT NULL COMMENT 'Default name (fallback)',
    `description` TEXT COMMENT 'Default description (fallback)',
    `position` INT NOT NULL DEFAULT 0,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    UNIQUE KEY `unique_slug_taxonomy` (`id_wepresta_acf_cpt_taxonomy`, `slug`),
    INDEX `idx_taxonomy` (`id_wepresta_acf_cpt_taxonomy`),
    INDEX `idx_parent` (`id_parent`),
    INDEX `idx_position` (`id_wepresta_acf_cpt_taxonomy`, `position`),
    CONSTRAINT `fk_wepresta_acf_cpt_term_taxonomy`
        FOREIGN KEY (`id_wepresta_acf_cpt_taxonomy`)
        REFERENCES `PREFIX_wepresta_acf_cpt_taxonomy`(`id_wepresta_acf_cpt_taxonomy`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_wepresta_acf_cpt_term_parent`
        FOREIGN KEY (`id_parent`)
        REFERENCES `PREFIX_wepresta_acf_cpt_term`(`id_wepresta_acf_cpt_term`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- CPT Term Translations (multilang)
-- =========================================================================
CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_cpt_term_lang` (
    `id_wepresta_acf_cpt_term` INT UNSIGNED NOT NULL,
    `id_lang` INT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    PRIMARY KEY (`id_wepresta_acf_cpt_term`, `id_lang`),
    CONSTRAINT `fk_wepresta_acf_cpt_term_lang_term`
        FOREIGN KEY (`id_wepresta_acf_cpt_term`)
        REFERENCES `PREFIX_wepresta_acf_cpt_term`(`id_wepresta_acf_cpt_term`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- CPT Posts (individual content items)
-- =========================================================================
CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_cpt_post` (
    `id_wepresta_acf_cpt_post` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` CHAR(36) NOT NULL COMMENT 'Immutable UUID',
    `id_wepresta_acf_cpt_type` INT UNSIGNED NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `title` VARCHAR(255) NOT NULL COMMENT 'Default title (fallback)',
    `status` ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    `id_employee` INT UNSIGNED DEFAULT NULL COMMENT 'Author (employee who created)',
    `seo_title` VARCHAR(255) DEFAULT NULL COMMENT 'Default SEO title (fallback)',
    `seo_description` TEXT DEFAULT NULL COMMENT 'Default SEO description (fallback)',
    `seo_meta` JSON COMMENT 'Additional SEO metadata (OG, Schema.org)',
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    UNIQUE KEY `uuid` (`uuid`),
    UNIQUE KEY `unique_slug_type` (`id_wepresta_acf_cpt_type`, `slug`),
    INDEX `idx_type` (`id_wepresta_acf_cpt_type`),
    INDEX `idx_status` (`status`),
    INDEX `idx_author` (`id_employee`),
    INDEX `idx_type_status` (`id_wepresta_acf_cpt_type`, `status`),
    CONSTRAINT `fk_wepresta_acf_cpt_post_type`
        FOREIGN KEY (`id_wepresta_acf_cpt_type`)
        REFERENCES `PREFIX_wepresta_acf_cpt_type`(`id_wepresta_acf_cpt_type`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- CPT Post Translations (multilang)
-- =========================================================================
CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_cpt_post_lang` (
    `id_wepresta_acf_cpt_post` INT UNSIGNED NOT NULL,
    `id_lang` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `seo_title` VARCHAR(255) DEFAULT NULL,
    `seo_description` TEXT DEFAULT NULL,
    PRIMARY KEY (`id_wepresta_acf_cpt_post`, `id_lang`),
    INDEX `idx_lang` (`id_lang`),
    CONSTRAINT `fk_wepresta_acf_cpt_post_lang_post`
        FOREIGN KEY (`id_wepresta_acf_cpt_post`)
        REFERENCES `PREFIX_wepresta_acf_cpt_post`(`id_wepresta_acf_cpt_post`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- CPT Post to Shop association (multi-shop, per boutique)
-- =========================================================================
CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_cpt_post_shop` (
    `id_wepresta_acf_cpt_post` INT UNSIGNED NOT NULL,
    `id_shop` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id_wepresta_acf_cpt_post`, `id_shop`),
    CONSTRAINT `fk_wepresta_acf_cpt_post_shop_post`
        FOREIGN KEY (`id_wepresta_acf_cpt_post`)
        REFERENCES `PREFIX_wepresta_acf_cpt_post`(`id_wepresta_acf_cpt_post`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================================
-- CPT Post to Term association (many-to-many)
-- =========================================================================
CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_cpt_post_term` (
    `id_wepresta_acf_cpt_post` INT UNSIGNED NOT NULL,
    `id_wepresta_acf_cpt_term` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id_wepresta_acf_cpt_post`, `id_wepresta_acf_cpt_term`),
    INDEX `idx_post` (`id_wepresta_acf_cpt_post`),
    INDEX `idx_term` (`id_wepresta_acf_cpt_term`),
    CONSTRAINT `fk_wepresta_acf_cpt_post_term_post`
        FOREIGN KEY (`id_wepresta_acf_cpt_post`)
        REFERENCES `PREFIX_wepresta_acf_cpt_post`(`id_wepresta_acf_cpt_post`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_wepresta_acf_cpt_post_term_term`
        FOREIGN KEY (`id_wepresta_acf_cpt_term`)
        REFERENCES `PREFIX_wepresta_acf_cpt_term`(`id_wepresta_acf_cpt_term`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================================
-- CPT Relations (CPT to CPT relations, e.g., Author → Book)
-- =========================================================================
CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_cpt_relation` (
    `id_wepresta_acf_cpt_relation` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` CHAR(36) NOT NULL,
    `id_cpt_type_source` INT UNSIGNED NOT NULL COMMENT 'Source CPT type (e.g., Book)',
    `id_cpt_type_target` INT UNSIGNED NOT NULL COMMENT 'Target CPT type (e.g., Author)',
    `slug` VARCHAR(255) NOT NULL,
    `name` VARCHAR(255) NOT NULL COMMENT 'Relation name (e.g., "author")',
    `config` JSON COMMENT 'Relation configuration (cardinality, etc.)',
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    UNIQUE KEY `uuid` (`uuid`),
    UNIQUE KEY `unique_relation` (`id_cpt_type_source`, `id_cpt_type_target`, `slug`),
    INDEX `idx_source` (`id_cpt_type_source`),
    INDEX `idx_target` (`id_cpt_type_target`),
    CONSTRAINT `fk_wepresta_acf_cpt_relation_source`
        FOREIGN KEY (`id_cpt_type_source`)
        REFERENCES `PREFIX_wepresta_acf_cpt_type`(`id_wepresta_acf_cpt_type`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_wepresta_acf_cpt_relation_target`
        FOREIGN KEY (`id_cpt_type_target`)
        REFERENCES `PREFIX_wepresta_acf_cpt_type`(`id_wepresta_acf_cpt_type`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- CPT Relation Data (actual post-to-post links)
-- =========================================================================
CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_cpt_relation_data` (
    `id_wepresta_acf_cpt_relation` INT UNSIGNED NOT NULL,
    `id_cpt_post_source` INT UNSIGNED NOT NULL,
    `id_cpt_post_target` INT UNSIGNED NOT NULL,
    `position` INT NOT NULL DEFAULT 0 COMMENT 'Order in relation',
    PRIMARY KEY (`id_wepresta_acf_cpt_relation`, `id_cpt_post_source`, `id_cpt_post_target`),
    INDEX `idx_source` (`id_cpt_post_source`),
    INDEX `idx_target` (`id_cpt_post_target`),
    INDEX `idx_relation` (`id_wepresta_acf_cpt_relation`),
    CONSTRAINT `fk_wepresta_acf_cpt_relation_data_relation`
        FOREIGN KEY (`id_wepresta_acf_cpt_relation`)
        REFERENCES `PREFIX_wepresta_acf_cpt_relation`(`id_wepresta_acf_cpt_relation`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_wepresta_acf_cpt_relation_data_source`
        FOREIGN KEY (`id_cpt_post_source`)
        REFERENCES `PREFIX_wepresta_acf_cpt_post`(`id_wepresta_acf_cpt_post`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_wepresta_acf_cpt_relation_data_target`
        FOREIGN KEY (`id_cpt_post_target`)
        REFERENCES `PREFIX_wepresta_acf_cpt_post`(`id_wepresta_acf_cpt_post`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
