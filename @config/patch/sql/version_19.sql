-- ============================================================
-- Patch 01_0019 — Menu lateral: seção "Produtos"
-- Data: 2026-03-31
-- Preferir execução idempotente via patch_01_0019.php.
-- ============================================================

USE `orsers`;

UPDATE `nav_menu_itens` SET `section_code` = 'produtos' WHERE `id` IN (7, 16, 17);
