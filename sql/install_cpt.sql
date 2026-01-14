-- WePresta ACF - Custom Post Types (CPT) Schema

CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_cpt_type` (
    `id_wepresta_acf_cpt_type` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` CHAR(36) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `config` JSON,
    `url_prefix` VARCHAR(255) NOT NULL,
    `has_archive` TINYINT(1) NOT NULL DEFAULT 1,
    `archive_slug` VARCHAR(255) DEFAULT NULL,
    `seo_config` JSON,
    `icon` VARCHAR(50) DEFAULT 'article',
    `position` INT NOT NULL DEFAULT 0,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    UNIQUE KEY `uuid` (`uuid`),
    UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_cpt_type_lang` (
    `id_wepresta_acf_cpt_type` INT UNSIGNED NOT NULL,
    `id_lang` INT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    PRIMARY KEY (`id_wepresta_acf_cpt_type`, `id_lang`),
    CONSTRAINT `fk_cpt_type_lang` FOREIGN KEY (`id_wepresta_acf_cpt_type`) REFERENCES `PREFIX_wepresta_acf_cpt_type`(`id_wepresta_acf_cpt_type`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_cpt_type_shop` (
    `id_wepresta_acf_cpt_type` INT UNSIGNED NOT NULL,
    `id_shop` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id_wepresta_acf_cpt_type`, `id_shop`),
    CONSTRAINT `fk_cpt_type_shop` FOREIGN KEY (`id_wepresta_acf_cpt_type`) REFERENCES `PREFIX_wepresta_acf_cpt_type`(`id_wepresta_acf_cpt_type`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_cpt_type_group` (
    `id_wepresta_acf_cpt_type` INT UNSIGNED NOT NULL,
    `id_wepresta_acf_group` INT UNSIGNED NOT NULL,
    `position` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id_wepresta_acf_cpt_type`, `id_wepresta_acf_group`),
    CONSTRAINT `fk_cpt_type_group_type` FOREIGN KEY (`id_wepresta_acf_cpt_type`) REFERENCES `PREFIX_wepresta_acf_cpt_type`(`id_wepresta_acf_cpt_type`) ON DELETE CASCADE,
    CONSTRAINT `fk_cpt_type_group_group` FOREIGN KEY (`id_wepresta_acf_group`) REFERENCES `PREFIX_wepresta_acf_group`(`id_wepresta_acf_group`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_cpt_taxonomy` (
    `id_wepresta_acf_cpt_taxonomy` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` CHAR(36) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `hierarchical` TINYINT(1) NOT NULL DEFAULT 1,
    `config` JSON,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    UNIQUE KEY `uuid` (`uuid`),
    UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_cpt_taxonomy_lang` (
    `id_wepresta_acf_cpt_taxonomy` INT UNSIGNED NOT NULL,
    `id_lang` INT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    PRIMARY KEY (`id_wepresta_acf_cpt_taxonomy`, `id_lang`),
    CONSTRAINT `fk_cpt_taxonomy_lang` FOREIGN KEY (`id_wepresta_acf_cpt_taxonomy`) REFERENCES `PREFIX_wepresta_acf_cpt_taxonomy`(`id_wepresta_acf_cpt_taxonomy`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_cpt_type_taxonomy` (
    `id_wepresta_acf_cpt_type` INT UNSIGNED NOT NULL,
    `id_wepresta_acf_cpt_taxonomy` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id_wepresta_acf_cpt_type`, `id_wepresta_acf_cpt_taxonomy`),
    CONSTRAINT `fk_cpt_type_tax_type` FOREIGN KEY (`id_wepresta_acf_cpt_type`) REFERENCES `PREFIX_wepresta_acf_cpt_type`(`id_wepresta_acf_cpt_type`) ON DELETE CASCADE,
    CONSTRAINT `fk_cpt_type_tax_taxonomy` FOREIGN KEY (`id_wepresta_acf_cpt_taxonomy`) REFERENCES `PREFIX_wepresta_acf_cpt_taxonomy`(`id_wepresta_acf_cpt_taxonomy`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_cpt_term` (
    `id_wepresta_acf_cpt_term` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `id_wepresta_acf_cpt_taxonomy` INT UNSIGNED NOT NULL,
    `id_parent` INT UNSIGNED DEFAULT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `position` INT NOT NULL DEFAULT 0,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    UNIQUE KEY `unique_slug_taxonomy` (`id_wepresta_acf_cpt_taxonomy`, `slug`),
    CONSTRAINT `fk_cpt_term_taxonomy` FOREIGN KEY (`id_wepresta_acf_cpt_taxonomy`) REFERENCES `PREFIX_wepresta_acf_cpt_taxonomy`(`id_wepresta_acf_cpt_taxonomy`) ON DELETE CASCADE,
    CONSTRAINT `fk_cpt_term_parent` FOREIGN KEY (`id_parent`) REFERENCES `PREFIX_wepresta_acf_cpt_term`(`id_wepresta_acf_cpt_term`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_cpt_term_lang` (
    `id_wepresta_acf_cpt_term` INT UNSIGNED NOT NULL,
    `id_lang` INT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    PRIMARY KEY (`id_wepresta_acf_cpt_term`, `id_lang`),
    CONSTRAINT `fk_cpt_term_lang` FOREIGN KEY (`id_wepresta_acf_cpt_term`) REFERENCES `PREFIX_wepresta_acf_cpt_term`(`id_wepresta_acf_cpt_term`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_cpt_post` (
    `id_wepresta_acf_cpt_post` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` CHAR(36) NOT NULL,
    `id_wepresta_acf_cpt_type` INT UNSIGNED NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `status` ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    `id_employee` INT UNSIGNED DEFAULT NULL,
    `seo_title` VARCHAR(255) DEFAULT NULL,
    `seo_description` TEXT DEFAULT NULL,
    `seo_meta` JSON,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    UNIQUE KEY `uuid` (`uuid`),
    UNIQUE KEY `unique_slug_type` (`id_wepresta_acf_cpt_type`, `slug`),
    CONSTRAINT `fk_cpt_post_type` FOREIGN KEY (`id_wepresta_acf_cpt_type`) REFERENCES `PREFIX_wepresta_acf_cpt_type`(`id_wepresta_acf_cpt_type`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_cpt_post_lang` (
    `id_wepresta_acf_cpt_post` INT UNSIGNED NOT NULL,
    `id_lang` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `seo_title` VARCHAR(255) DEFAULT NULL,
    `seo_description` TEXT DEFAULT NULL,
    PRIMARY KEY (`id_wepresta_acf_cpt_post`, `id_lang`),
    CONSTRAINT `fk_cpt_post_lang` FOREIGN KEY (`id_wepresta_acf_cpt_post`) REFERENCES `PREFIX_wepresta_acf_cpt_post`(`id_wepresta_acf_cpt_post`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_cpt_post_shop` (
    `id_wepresta_acf_cpt_post` INT UNSIGNED NOT NULL,
    `id_shop` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id_wepresta_acf_cpt_post`, `id_shop`),
    CONSTRAINT `fk_cpt_post_shop` FOREIGN KEY (`id_wepresta_acf_cpt_post`) REFERENCES `PREFIX_wepresta_acf_cpt_post`(`id_wepresta_acf_cpt_post`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_cpt_post_term` (
    `id_wepresta_acf_cpt_post` INT UNSIGNED NOT NULL,
    `id_wepresta_acf_cpt_term` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id_wepresta_acf_cpt_post`, `id_wepresta_acf_cpt_term`),
    CONSTRAINT `fk_cpt_post_term_post` FOREIGN KEY (`id_wepresta_acf_cpt_post`) REFERENCES `PREFIX_wepresta_acf_cpt_post`(`id_wepresta_acf_cpt_post`) ON DELETE CASCADE,
    CONSTRAINT `fk_cpt_post_term_term` FOREIGN KEY (`id_wepresta_acf_cpt_term`) REFERENCES `PREFIX_wepresta_acf_cpt_term`(`id_wepresta_acf_cpt_term`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_cpt_relation` (
    `id_wepresta_acf_cpt_relation` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` CHAR(36) NOT NULL,
    `id_cpt_type_source` INT UNSIGNED NOT NULL,
    `id_cpt_type_target` INT UNSIGNED NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `config` JSON,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    UNIQUE KEY `uuid` (`uuid`),
    CONSTRAINT `fk_cpt_relation_source` FOREIGN KEY (`id_cpt_type_source`) REFERENCES `PREFIX_wepresta_acf_cpt_type`(`id_wepresta_acf_cpt_type`) ON DELETE CASCADE,
    CONSTRAINT `fk_cpt_relation_target` FOREIGN KEY (`id_cpt_type_target`) REFERENCES `PREFIX_wepresta_acf_cpt_type`(`id_wepresta_acf_cpt_type`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_wepresta_acf_cpt_relation_data` (
    `id_wepresta_acf_cpt_relation` INT UNSIGNED NOT NULL,
    `id_cpt_post_source` INT UNSIGNED NOT NULL,
    `id_cpt_post_target` INT UNSIGNED NOT NULL,
    `position` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id_wepresta_acf_cpt_relation`, `id_cpt_post_source`, `id_cpt_post_target`),
    CONSTRAINT `fk_cpt_relation_data_rel` FOREIGN KEY (`id_wepresta_acf_cpt_relation`) REFERENCES `PREFIX_wepresta_acf_cpt_relation`(`id_wepresta_acf_cpt_relation`) ON DELETE CASCADE,
    CONSTRAINT `fk_cpt_relation_data_source` FOREIGN KEY (`id_cpt_post_source`) REFERENCES `PREFIX_wepresta_acf_cpt_post`(`id_wepresta_acf_cpt_post`) ON DELETE CASCADE,
    CONSTRAINT `fk_cpt_relation_data_target` FOREIGN KEY (`id_cpt_post_target`) REFERENCES `PREFIX_wepresta_acf_cpt_post`(`id_wepresta_acf_cpt_post`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
