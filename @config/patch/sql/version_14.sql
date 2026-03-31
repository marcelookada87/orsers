-- ============================================================
-- Patch 01_0014 — usuarios.estoque_item_codigo_tag
-- Data: 2026-03-29
-- Preferir execução idempotente via patch_01_0014.php.
-- Menu e tabela usuario_configuracoes: patch 01_0015.
-- ============================================================

USE `orsers`;

ALTER TABLE `usuarios`
    ADD COLUMN `estoque_item_codigo_tag` VARCHAR(16) NOT NULL DEFAULT 'ITEM'
    COMMENT 'Prefixo sugerido para novo código no catálogo (A–Z, 0–9)' AFTER `estoque_limite_itens`;
