<?php
/**
 * Patch 01_0005 — Tabela sistema_config (limite de imagens por OS configurável)
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
        echo "Executando Patch 01_0005 — sistema_config...\n";
    }

    $existe = $db->fetch(
        "SELECT COUNT(*) AS t FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sistema_config'"
    );
    if ((int)($existe['t'] ?? 0) === 0) {
        $db->execute(
            "CREATE TABLE `sistema_config` (
                `chave` VARCHAR(64) NOT NULL,
                `valor` VARCHAR(255) NOT NULL,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`chave`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        if (!$isCheckMode) {
            echo "  [OK] Tabela sistema_config criada\n";
        }
    }

    $db->execute(
        "INSERT INTO `sistema_config` (`chave`, `valor`) VALUES ('max_imagens_por_os', '5')
         ON DUPLICATE KEY UPDATE `chave` = `chave`"
    );
    if (!$isCheckMode) {
        echo "  [OK] Chave max_imagens_por_os (padrão 5)\n";
    }

    if (!$isCheckMode) {
        echo "\nPatch 01_0005 executado com sucesso!\n";
    }
} catch (Exception $e) {
    echo "Erro ao executar Patch 01_0005: " . $e->getMessage() . "\n";
    error_log("Erro Patch 01_0005: " . $e->getMessage());
    exit(1);
}
