-- ============================================================
-- Patch 01_0017 — Menu Gestão: Categorias antes de Catálogo
-- Data: 2026-03-30
-- Preferir execução idempotente via patch_01_0017.php.
-- ============================================================

USE `orsers`;

UPDATE `nav_menu_itens` SET `sort_order` = 24 WHERE `id` = 17;
UPDATE `nav_menu_itens` SET `sort_order` = 25 WHERE `id` = 16;
