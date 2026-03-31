<?php
/**
 * Patch 01_0006 — Tabela planos, colunas admin/plano/overrides em usuarios,
 *                usuario_cadastro_id em clientes, índice em ordens_servico
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

$colExists = static function ($db, string $table, string $col): bool {
    $r = $db->fetch(
        "SELECT COUNT(*) AS t FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
        [$table, $col]
    );
    return (int)($r['t'] ?? 0) > 0;
};

$tableExists = static function ($db, string $table): bool {
    $r = $db->fetch(
        "SELECT COUNT(*) AS t FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
        [$table]
    );
    return (int)($r['t'] ?? 0) > 0;
};

$fkExists = static function ($db, string $name): bool {
    $r = $db->fetch(
        "SELECT COUNT(*) AS t FROM information_schema.TABLE_CONSTRAINTS
         WHERE TABLE_SCHEMA = DATABASE() AND CONSTRAINT_NAME = ?",
        [$name]
    );
    return (int)($r['t'] ?? 0) > 0;
};

$indexExists = static function ($db, string $table, string $indexName): bool {
    $r = $db->fetch(
        "SELECT COUNT(*) AS t FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?",
        [$table, $indexName]
    );
    return (int)($r['t'] ?? 0) > 0;
};

try {
    $db = Database::getInstance();

    if (!$isCheckMode) {
        echo "Executando Patch 01_0006 — planos, limites por conta...\n";
    }

    if (!$tableExists($db, 'planos')) {
        $db->execute(
            "CREATE TABLE `planos` (
                `id`                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `codigo`               VARCHAR(32)  NOT NULL COMMENT 'free, ilimitado, etc.',
                `nome`                 VARCHAR(100) NOT NULL,
                `max_imagens_por_os`   INT UNSIGNED NULL,
                `max_clientes`         INT UNSIGNED NULL,
                `max_os_mes`           INT UNSIGNED NULL,
                `ativo`                TINYINT(1)   NOT NULL DEFAULT 1,
                `ordem`                INT          NOT NULL DEFAULT 0,
                `created_at`           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_plano_codigo` (`codigo`),
                KEY `idx_plano_ativo` (`ativo`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        if (!$isCheckMode) {
            echo "  [OK] Tabela planos criada\n";
        }
    }

    $db->execute(
        "INSERT INTO `planos` (`id`, `codigo`, `nome`, `max_imagens_por_os`, `max_clientes`, `max_os_mes`, `ativo`, `ordem`) VALUES
        (1, 'free', 'Free', 3, 25, 50, 1, 1),
        (2, 'ilimitado', 'Ilimitado', NULL, NULL, NULL, 1, 0)
        ON DUPLICATE KEY UPDATE `nome` = VALUES(`nome`)"
    );

    if (!$colExists($db, 'usuarios', 'admin')) {
        $db->execute(
            "ALTER TABLE `usuarios` ADD COLUMN `admin` TINYINT(1) NOT NULL DEFAULT 0 AFTER `perfil`"
        );
        if (!$isCheckMode) {
            echo "  [OK] usuarios.admin\n";
        }
    }

    if (!$colExists($db, 'usuarios', 'plano_id')) {
        $db->execute(
            "ALTER TABLE `usuarios` ADD COLUMN `plano_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `admin`"
        );
        if (!$isCheckMode) {
            echo "  [OK] usuarios.plano_id\n";
        }
    }

    if (!$colExists($db, 'usuarios', 'max_imagens_por_os_override')) {
        $db->execute(
            "ALTER TABLE `usuarios` ADD COLUMN `max_imagens_por_os_override` INT UNSIGNED NULL DEFAULT NULL AFTER `plano_id`"
        );
        if (!$isCheckMode) {
            echo "  [OK] usuarios.max_imagens_por_os_override\n";
        }
    }

    if (!$colExists($db, 'usuarios', 'max_clientes_override')) {
        $db->execute(
            "ALTER TABLE `usuarios` ADD COLUMN `max_clientes_override` INT UNSIGNED NULL DEFAULT NULL AFTER `max_imagens_por_os_override`"
        );
        if (!$isCheckMode) {
            echo "  [OK] usuarios.max_clientes_override\n";
        }
    }

    if (!$colExists($db, 'usuarios', 'max_os_mes_override')) {
        $db->execute(
            "ALTER TABLE `usuarios` ADD COLUMN `max_os_mes_override` INT UNSIGNED NULL DEFAULT NULL AFTER `max_clientes_override`"
        );
        if (!$isCheckMode) {
            echo "  [OK] usuarios.max_os_mes_override\n";
        }
    }

    $db->execute("UPDATE `usuarios` SET `admin` = 1 WHERE `perfil` = 'admin'");
    $db->execute("UPDATE `usuarios` SET `plano_id` = 2 WHERE `admin` = 1");
    $db->execute("UPDATE `usuarios` SET `plano_id` = 1 WHERE `plano_id` IS NULL OR `plano_id` = 0");

    if (!$fkExists($db, 'fk_usuario_plano')) {
        if (!$indexExists($db, 'usuarios', 'idx_usuario_plano')) {
            $db->execute("ALTER TABLE `usuarios` ADD KEY `idx_usuario_plano` (`plano_id`)");
        }
        $db->execute(
            "ALTER TABLE `usuarios` ADD CONSTRAINT `fk_usuario_plano` FOREIGN KEY (`plano_id`) REFERENCES `planos` (`id`)"
        );
        if (!$isCheckMode) {
            echo "  [OK] FK fk_usuario_plano\n";
        }
    }

    if (!$colExists($db, 'clientes', 'usuario_cadastro_id')) {
        $db->execute(
            "ALTER TABLE `clientes` ADD COLUMN `usuario_cadastro_id` INT UNSIGNED NULL AFTER `ativo`"
        );
        if (!$isCheckMode) {
            echo "  [OK] clientes.usuario_cadastro_id (nullable)\n";
        }

        $row = $db->fetch(
            "SELECT MIN(id) AS id FROM `usuarios` WHERE `perfil` = 'admin'"
        );
        $aid = (int)($row['id'] ?? 0);
        if ($aid === 0) {
            $row = $db->fetch("SELECT MIN(id) AS id FROM `usuarios`");
            $aid = max(1, (int)($row['id'] ?? 1));
        }
        $db->execute(
            "UPDATE `clientes` SET `usuario_cadastro_id` = ? WHERE `usuario_cadastro_id` IS NULL",
            [$aid]
        );

        $db->execute(
            "ALTER TABLE `clientes` MODIFY COLUMN `usuario_cadastro_id` INT UNSIGNED NOT NULL"
        );

        if (!$indexExists($db, 'clientes', 'idx_cliente_cadastro_user')) {
            $db->execute(
                "ALTER TABLE `clientes` ADD KEY `idx_cliente_cadastro_user` (`usuario_cadastro_id`)"
            );
        }
        if (!$fkExists($db, 'fk_cliente_usuario_cadastro')) {
            $db->execute(
                "ALTER TABLE `clientes` ADD CONSTRAINT `fk_cliente_usuario_cadastro`
                 FOREIGN KEY (`usuario_cadastro_id`) REFERENCES `usuarios` (`id`)"
            );
        }
        if (!$isCheckMode) {
            echo "  [OK] clientes.usuario_cadastro_id (NOT NULL + FK)\n";
        }
    }

    if (!$indexExists($db, 'ordens_servico', 'idx_os_criador_data')) {
        $db->execute(
            "ALTER TABLE `ordens_servico` ADD KEY `idx_os_criador_data` (`usuario_criador_id`, `data_abertura`)"
        );
        if (!$isCheckMode) {
            echo "  [OK] idx_os_criador_data\n";
        }
    }

    if (!$isCheckMode) {
        echo "\nPatch 01_0006 executado com sucesso!\n";
    }
} catch (Exception $e) {
    echo "Erro ao executar Patch 01_0006: " . $e->getMessage() . "\n";
    error_log("Erro Patch 01_0006: " . $e->getMessage());
    exit(1);
}
