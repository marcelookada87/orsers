-- ============================================================
-- Patch 01_0018 — estoque_itens.nf_valor_total
-- Data: 2026-03-31
-- Preferir execução idempotente via patch_01_0018.php.
-- ============================================================

USE `orsers`;

ALTER TABLE `estoque_itens`
    ADD COLUMN `nf_valor_total` DECIMAL(12,2) NULL DEFAULT NULL
    COMMENT 'Valor total da nota fiscal' AFTER `nf_emissao`;
