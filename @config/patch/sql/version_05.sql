-- ============================================================
-- Patch 01_0005 — sistema_config (limite de imagens por OS)
-- Data: 2026-03-29
-- Versão: 1
-- ============================================================

USE `orsers`;

CREATE TABLE IF NOT EXISTS `sistema_config` (
    `chave` VARCHAR(64) NOT NULL,
    `valor` VARCHAR(255) NOT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sistema_config` (`chave`, `valor`) VALUES ('max_imagens_por_os', '5')
ON DUPLICATE KEY UPDATE `chave` = `chave`;
