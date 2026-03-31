-- ============================================================
-- Patch 01_0004 — Financeiro e motivo de finalização (ordens_servico)
-- Data: 2026-03-21
-- Versão: 1
-- ============================================================

USE `orsers`;

ALTER TABLE `ordens_servico`
    ADD COLUMN `motivo_finalizacao` VARCHAR(500) NULL AFTER `observacoes`,
    ADD COLUMN `valor_servico` DECIMAL(12,2) NULL COMMENT 'Valor cobrado/orçamento' AFTER `motivo_finalizacao`,
    ADD COLUMN `valor_pago` DECIMAL(12,2) NULL AFTER `valor_servico`,
    ADD COLUMN `forma_pagamento` VARCHAR(40) NULL AFTER `valor_pago`,
    ADD COLUMN `detalhe_financeiro` TEXT NULL COMMENT 'Peças, materiais, custos' AFTER `forma_pagamento`;
