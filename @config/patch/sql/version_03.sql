-- ============================================================
-- Patch 01_0003 - Coluna ativo em prioridades
-- Data: 2026-03-21
-- Versão: 1
-- ============================================================

USE `orsers`;

ALTER TABLE `prioridades`
    ADD COLUMN `ativo` TINYINT(1) NOT NULL DEFAULT 1 AFTER `sla_multiplicador`;

UPDATE `prioridades` SET `ativo` = 1;

ALTER TABLE `prioridades` ADD KEY `idx_ativo` (`ativo`);
