-- ============================================================
-- Patch 01_0009 — Módulo de estoque (usuários + catálogo + saldos + OS)
-- Data: 2026-03-29
-- Versão: 1
-- (Preferir execução via patch_01_0009.php para idempotência.)
-- ============================================================

USE `orsers`;

-- Colunas em `usuarios` (estoque_ativo, estoque_limite_itens): aplicar via patch_01_0009.php
-- (idempotente). Ou manualmente se ainda não existirem:
-- ALTER TABLE `usuarios` ADD COLUMN `estoque_ativo` TINYINT(1) NOT NULL DEFAULT 0 AFTER `telegram_token`;
-- ALTER TABLE `usuarios` ADD COLUMN `estoque_limite_itens` INT UNSIGNED NULL DEFAULT NULL AFTER `estoque_ativo`;

CREATE TABLE IF NOT EXISTS `estoque_categorias` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nome`       VARCHAR(120) NOT NULL,
    `descricao`  VARCHAR(500) NULL DEFAULT NULL,
    `ativo`      TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_estoque_cat_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `estoque_itens` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `categoria_id` INT UNSIGNED NULL DEFAULT NULL,
    `codigo`       VARCHAR(64)  NOT NULL,
    `nome`         VARCHAR(200) NOT NULL,
    `descricao`    VARCHAR(500) NULL DEFAULT NULL,
    `unidade`      VARCHAR(16)  NOT NULL DEFAULT 'un' COMMENT 'un, kg, m, cx, par, L, ...',
    `ativo`        TINYINT(1)   NOT NULL DEFAULT 1,
    `criado_por`   INT UNSIGNED NOT NULL,
    `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_estoque_item_codigo` (`codigo`),
    KEY `idx_estoque_item_cat` (`categoria_id`),
    KEY `idx_estoque_item_ativo` (`ativo`),
    CONSTRAINT `fk_estoque_item_cat` FOREIGN KEY (`categoria_id`) REFERENCES `estoque_categorias` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_estoque_item_user` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `estoque_saldo` (
    `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id`         INT UNSIGNED NOT NULL,
    `item_id`            INT UNSIGNED NOT NULL,
    `quantidade`         DECIMAL(10,3) NOT NULL DEFAULT 0.000,
    `quantidade_minima`  DECIMAL(10,3) NOT NULL DEFAULT 0.000,
    `created_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_estoque_saldo_user_item` (`usuario_id`, `item_id`),
    KEY `idx_estoque_saldo_item` (`item_id`),
    CONSTRAINT `fk_estoque_saldo_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_estoque_saldo_item` FOREIGN KEY (`item_id`)    REFERENCES `estoque_itens` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `estoque_movimentacoes` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id`       INT UNSIGNED NOT NULL,
    `item_id`          INT UNSIGNED NOT NULL,
    `tipo`             ENUM('entrada','saida','ajuste') NOT NULL,
    `quantidade`       DECIMAL(10,3) NOT NULL,
    `saldo_anterior`   DECIMAL(10,3) NOT NULL,
    `saldo_posterior`  DECIMAL(10,3) NOT NULL,
    `referencia_tipo`  ENUM('os','manual','ajuste') NOT NULL DEFAULT 'manual',
    `referencia_id`    INT UNSIGNED NULL DEFAULT NULL COMMENT 'ex.: ordem_id quando referencia_tipo=os',
    `observacao`       VARCHAR(500) NULL DEFAULT NULL,
    `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_estoque_mov_user` (`usuario_id`, `created_at`),
    KEY `idx_estoque_mov_item` (`item_id`, `created_at`),
    KEY `idx_estoque_mov_ref` (`referencia_tipo`, `referencia_id`),
    CONSTRAINT `fk_estoque_mov_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
    CONSTRAINT `fk_estoque_mov_item` FOREIGN KEY (`item_id`)    REFERENCES `estoque_itens` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `estoque_os_itens` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ordem_id`          INT UNSIGNED NOT NULL,
    `item_id`           INT UNSIGNED NOT NULL,
    `usuario_id`        INT UNSIGNED NOT NULL COMMENT 'Dono do saldo debitado',
    `quantidade`        DECIMAL(10,3) NOT NULL,
    `movimentacao_id`   INT UNSIGNED NOT NULL,
    `observacao`        VARCHAR(500) NULL DEFAULT NULL,
    `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_estoque_os_ordem` (`ordem_id`),
    KEY `idx_estoque_os_item` (`item_id`),
    CONSTRAINT `fk_estoque_os_ordem` FOREIGN KEY (`ordem_id`) REFERENCES `ordens_servico` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_estoque_os_item` FOREIGN KEY (`item_id`) REFERENCES `estoque_itens` (`id`),
    CONSTRAINT `fk_estoque_os_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
    CONSTRAINT `fk_estoque_os_mov` FOREIGN KEY (`movimentacao_id`) REFERENCES `estoque_movimentacoes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `estoque_categorias` (`id`, `nome`, `descricao`, `ativo`) VALUES
(1, 'Geral', 'Itens sem categoria específica', 1);
