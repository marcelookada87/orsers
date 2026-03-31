-- ============================================================
-- Patch 01_0006 — Planos, admin em usuarios, dono do cliente
-- Data: 2026-03-29
-- Versão: 1
-- (Preferir execução via patch_01_0006.php para idempotência.)
-- ============================================================

USE `orsers`;

CREATE TABLE IF NOT EXISTS `planos` (
    `id`                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `codigo`               VARCHAR(32)  NOT NULL COMMENT 'free, ilimitado, etc.',
    `nome`                 VARCHAR(100) NOT NULL,
    `max_imagens_por_os`   INT UNSIGNED NULL COMMENT 'NULL = ilimitado no plano (teto global no app)',
    `max_clientes`         INT UNSIGNED NULL COMMENT 'NULL = ilimitado',
    `max_os_mes`           INT UNSIGNED NULL COMMENT 'NULL = ilimitado',
    `ativo`                TINYINT(1)   NOT NULL DEFAULT 1,
    `ordem`                INT          NOT NULL DEFAULT 0,
    `created_at`           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_plano_codigo` (`codigo`),
    KEY `idx_plano_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `planos` (`id`, `codigo`, `nome`, `max_imagens_por_os`, `max_clientes`, `max_os_mes`, `ativo`, `ordem`) VALUES
(1, 'free',       'Free',       3,  25, 50, 1, 1),
(2, 'ilimitado',  'Ilimitado',  NULL, NULL, NULL, 1, 0)
ON DUPLICATE KEY UPDATE `nome` = VALUES(`nome`);
