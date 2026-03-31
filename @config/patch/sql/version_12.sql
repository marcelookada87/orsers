-- ============================================================
-- Patch 01_0012 — Nav: admin estoque → permissões; rótulo catálogo técnico
-- Data: 2026-03-29
-- (Preferir execução via patch_01_0012.php.)
-- ============================================================

USE `orsers`;

UPDATE `nav_menu_itens` SET `label` = 'Permissões de estoque', `url_path` = '/admin/estoque/usuarios' WHERE `id` = 9;
UPDATE `nav_menu_itens` SET `label` = 'Catálogo e cadastros' WHERE `id` = 16;
