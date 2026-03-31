<?php
/**
 * Patch 01_0009 — Módulo de estoque (catálogo, saldo por usuário, materiais na OS)
 * Data: 2026-03-29
 * Versão: 1
 */

if (!defined('DB_VERSION')) {
    require_once dirname(__DIR__, 2) . '/config.php';
}
if (!class_exists('Database')) {
    require_once dirname(__DIR__, 3) . '/@core/Database.php';
}

$isCheckMode = (defined('PATCH_CHECK_MODE') && PATCH_CHECK_MODE === true);

try {
    $db = Database::getInstance();

    if (!$isCheckMode) {
        echo "Executando Patch 01_0009 — Módulo de estoque...\n";
    }

    $col = $db->fetch(
        "SELECT COUNT(*) AS total FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'estoque_ativo'"
    );
    if ((int)$col['total'] === 0) {
        $db->execute(
            "ALTER TABLE `usuarios` ADD COLUMN `estoque_ativo` TINYINT(1) NOT NULL DEFAULT 0 AFTER `telegram_token`"
        );
        if (!$isCheckMode) {
            echo "  [OK] Coluna usuarios.estoque_ativo\n";
        }
    }

    $col = $db->fetch(
        "SELECT COUNT(*) AS total FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'estoque_limite_itens'"
    );
    if ((int)$col['total'] === 0) {
        $db->execute(
            "ALTER TABLE `usuarios` ADD COLUMN `estoque_limite_itens` INT UNSIGNED NULL DEFAULT NULL
             COMMENT 'NULL = ilimitado tipos de item' AFTER `estoque_ativo`"
        );
        if (!$isCheckMode) {
            echo "  [OK] Coluna usuarios.estoque_limite_itens\n";
        }
    }

    $existe = $db->fetch(
        "SELECT COUNT(*) AS total FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'estoque_categorias'"
    );
    if ((int)$existe['total'] === 0) {
        $db->execute(
            "CREATE TABLE `estoque_categorias` (
                `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `nome`       VARCHAR(120) NOT NULL,
                `descricao`  VARCHAR(500) NULL DEFAULT NULL,
                `ativo`      TINYINT(1)   NOT NULL DEFAULT 1,
                `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_estoque_cat_ativo` (`ativo`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        if (!$isCheckMode) {
            echo "  [OK] Tabela estoque_categorias\n";
        }
    }

    $existe = $db->fetch(
        "SELECT COUNT(*) AS total FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'estoque_itens'"
    );
    if ((int)$existe['total'] === 0) {
        $db->execute(
            "CREATE TABLE `estoque_itens` (
                `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `categoria_id` INT UNSIGNED NULL DEFAULT NULL,
                `codigo`       VARCHAR(64)  NOT NULL,
                `nome`         VARCHAR(200) NOT NULL,
                `descricao`    VARCHAR(500) NULL DEFAULT NULL,
                `unidade`      VARCHAR(16)  NOT NULL DEFAULT 'un',
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        if (!$isCheckMode) {
            echo "  [OK] Tabela estoque_itens\n";
        }
    }

    $existe = $db->fetch(
        "SELECT COUNT(*) AS total FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'estoque_saldo'"
    );
    if ((int)$existe['total'] === 0) {
        $db->execute(
            "CREATE TABLE `estoque_saldo` (
                `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `usuario_id`        INT UNSIGNED NOT NULL,
                `item_id`           INT UNSIGNED NOT NULL,
                `quantidade`        DECIMAL(10,3) NOT NULL DEFAULT 0.000,
                `quantidade_minima` DECIMAL(10,3) NOT NULL DEFAULT 0.000,
                `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_estoque_saldo_user_item` (`usuario_id`, `item_id`),
                KEY `idx_estoque_saldo_item` (`item_id`),
                CONSTRAINT `fk_estoque_saldo_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_estoque_saldo_item` FOREIGN KEY (`item_id`)    REFERENCES `estoque_itens` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        if (!$isCheckMode) {
            echo "  [OK] Tabela estoque_saldo\n";
        }
    }

    $existe = $db->fetch(
        "SELECT COUNT(*) AS total FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'estoque_movimentacoes'"
    );
    if ((int)$existe['total'] === 0) {
        $db->execute(
            "CREATE TABLE `estoque_movimentacoes` (
                `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `usuario_id`     INT UNSIGNED NOT NULL,
                `item_id`        INT UNSIGNED NOT NULL,
                `tipo`           ENUM('entrada','saida','ajuste') NOT NULL,
                `quantidade`     DECIMAL(10,3) NOT NULL,
                `saldo_anterior` DECIMAL(10,3) NOT NULL,
                `saldo_posterior` DECIMAL(10,3) NOT NULL,
                `referencia_tipo` ENUM('os','manual','ajuste') NOT NULL DEFAULT 'manual',
                `referencia_id`  INT UNSIGNED NULL DEFAULT NULL,
                `observacao`     VARCHAR(500) NULL DEFAULT NULL,
                `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_estoque_mov_user` (`usuario_id`, `created_at`),
                KEY `idx_estoque_mov_item` (`item_id`, `created_at`),
                KEY `idx_estoque_mov_ref` (`referencia_tipo`, `referencia_id`),
                CONSTRAINT `fk_estoque_mov_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
                CONSTRAINT `fk_estoque_mov_item` FOREIGN KEY (`item_id`)    REFERENCES `estoque_itens` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        if (!$isCheckMode) {
            echo "  [OK] Tabela estoque_movimentacoes\n";
        }
    }

    $existe = $db->fetch(
        "SELECT COUNT(*) AS total FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'estoque_os_itens'"
    );
    if ((int)$existe['total'] === 0) {
        $db->execute(
            "CREATE TABLE `estoque_os_itens` (
                `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `ordem_id`         INT UNSIGNED NOT NULL,
                `item_id`          INT UNSIGNED NOT NULL,
                `usuario_id`       INT UNSIGNED NOT NULL,
                `quantidade`       DECIMAL(10,3) NOT NULL,
                `movimentacao_id`  INT UNSIGNED NOT NULL,
                `observacao`       VARCHAR(500) NULL DEFAULT NULL,
                `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_estoque_os_ordem` (`ordem_id`),
                KEY `idx_estoque_os_item` (`item_id`),
                CONSTRAINT `fk_estoque_os_ordem` FOREIGN KEY (`ordem_id`) REFERENCES `ordens_servico` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_estoque_os_item` FOREIGN KEY (`item_id`) REFERENCES `estoque_itens` (`id`),
                CONSTRAINT `fk_estoque_os_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
                CONSTRAINT `fk_estoque_os_mov` FOREIGN KEY (`movimentacao_id`) REFERENCES `estoque_movimentacoes` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        if (!$isCheckMode) {
            echo "  [OK] Tabela estoque_os_itens\n";
        }
    }

    $db->execute(
        "INSERT IGNORE INTO `estoque_categorias` (`id`, `nome`, `descricao`, `ativo`) VALUES
        (1, 'Geral', 'Itens sem categoria específica', 1)"
    );

    if (!$isCheckMode) {
        echo "\nPatch 01_0009 executado com sucesso!\n";
    }
} catch (Exception $e) {
    echo 'Erro ao executar Patch 01_0009: ' . $e->getMessage() . "\n";
    error_log('Erro Patch 01_0009: ' . $e->getMessage());
    exit(1);
}
