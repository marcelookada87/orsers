-- ============================================================
-- Patch 01_0011 — Menu técnico: «Catálogo de peças»
-- Data: 2026-03-29
-- (Preferir execução via patch_01_0011.php.)
-- ============================================================

USE `orsers`;

INSERT IGNORE INTO `nav_menu_itens`
(`id`,`section_code`,`label`,`icon_class`,`url_path`,`sort_order`,`ativo`,`requer_estoque_ativo`,`item_class`,`active_rule`) VALUES
(16,'gestao','Catálogo de peças','fas fa-barcode','/estoque/catalogo',25,1,1,NULL,'estoque_catalogo');

INSERT IGNORE INTO `nav_menu_item_perfis` (`menu_item_id`, `perfil`) VALUES (16, 'tecnico');
