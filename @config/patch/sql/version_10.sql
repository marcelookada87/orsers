-- ============================================================
-- Patch 01_0010 — Menu lateral por perfil (tabelas + seed)
-- Data: 2026-03-29
-- Versão: 1
-- (Preferir execução via patch_01_0010.php para idempotência.)
-- ============================================================

USE `orsers`;

CREATE TABLE IF NOT EXISTS `nav_menu_itens` (
    `id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `section_code`          VARCHAR(32)  NOT NULL COMMENT 'principal, gestao, administracao, conta',
    `label`                 VARCHAR(120) NOT NULL,
    `icon_class`            VARCHAR(80)  NULL DEFAULT NULL COMMENT 'ex.: fas fa-tachometer-alt',
    `url_path`              VARCHAR(255) NOT NULL COMMENT 'caminho relativo ex.: /dashboard',
    `sort_order`            INT          NOT NULL DEFAULT 0,
    `ativo`                 TINYINT(1)   NOT NULL DEFAULT 1,
    `requer_estoque_ativo`  TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1 = só para usuário com estoque_ativo',
    `item_class`            VARCHAR(160) NULL DEFAULT NULL COMMENT 'classes extras no <a>, ex.: nav-item nav-logout',
    `active_rule`           VARCHAR(40)  NULL DEFAULT NULL COMMENT 'dashboard, ordens_index, sla_painel, admin_hub, estoque_tecnico, ...',
    PRIMARY KEY (`id`),
    KEY `idx_nav_section` (`section_code`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nav_menu_item_perfis` (
    `menu_item_id` INT UNSIGNED NOT NULL,
    `perfil`       ENUM('admin','tecnico','cliente') NOT NULL,
    PRIMARY KEY (`menu_item_id`, `perfil`),
    CONSTRAINT `fk_nav_perfil_item` FOREIGN KEY (`menu_item_id`) REFERENCES `nav_menu_itens` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
