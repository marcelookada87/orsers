-- ============================================================
-- Patch 01_0016 — estoque_itens: NF, fornecedor e dados de compra
-- Data: 2026-03-30
-- Preferir execução idempotente via patch_01_0016.php.
-- ============================================================

USE `orsers`;

ALTER TABLE `estoque_itens`
    ADD COLUMN `nf_numero` VARCHAR(64) NULL DEFAULT NULL
        COMMENT 'Número NF-e / nota fiscal' AFTER `descricao`,
    ADD COLUMN `nf_emissao` DATE NULL DEFAULT NULL
        COMMENT 'Data emissão NF (opcional)' AFTER `nf_numero`,
    ADD COLUMN `fornecedor` VARCHAR(200) NULL DEFAULT NULL
        COMMENT 'Nome ou razão social do fornecedor' AFTER `nf_emissao`,
    ADD COLUMN `fornecedor_cnpj` VARCHAR(18) NULL DEFAULT NULL
        COMMENT 'CNPJ do fornecedor (opcional)' AFTER `fornecedor`,
    ADD COLUMN `compra_observacoes` VARCHAR(600) NULL DEFAULT NULL
        COMMENT 'Lote, pedido, serial, demais informações da compra' AFTER `fornecedor_cnpj`;
