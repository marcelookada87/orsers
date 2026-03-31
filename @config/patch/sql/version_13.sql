-- ============================================================
-- Patch 01_0013 — estoque_categorias.updated_at + menu Categorias
-- Data: 2026-03-29
-- Preferir execução idempotente via patch_01_0013.php.
-- ============================================================

USE `orsers`;

ALTER TABLE `estoque_categorias`
    ADD COLUMN `updated_at` DATETIME NULL DEFAULT NULL AFTER `created_at`;

UPDATE `estoque_categorias` SET `updated_at` = `created_at` WHERE `updated_at` IS NULL;

ALTER TABLE `estoque_categorias`
    MODIFY `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

INSERT IGNORE INTO `nav_menu_itens` (`id`,`section_code`,`label`,`icon_class`,`url_path`,`sort_order`,`ativo`,`requer_estoque_ativo`,`item_class`,`active_rule`) VALUES
(17,'gestao','Categorias','fas fa-folder','/estoque/categorias',26,1,1,NULL,'estoque_categorias');

INSERT IGNORE INTO `nav_menu_item_perfis` (`menu_item_id`, `perfil`) VALUES (17, 'tecnico');
