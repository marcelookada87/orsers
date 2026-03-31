<?php
/**
 * Patch 01_0003 - Coluna ativo em prioridades (cadastro SLA)
 * Data: 2026-03-21
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
        echo "Executando Patch 01_0003 — prioridades.ativo...\n";
    }

    $col = $db->fetch(
        "SELECT COUNT(*) AS total FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prioridades' AND COLUMN_NAME = 'ativo'"
    );
    if ((int)$col['total'] === 0) {
        $db->execute(
            "ALTER TABLE `prioridades` ADD COLUMN `ativo` TINYINT(1) NOT NULL DEFAULT 1 AFTER `sla_multiplicador`"
        );
        $db->execute("UPDATE `prioridades` SET `ativo` = 1");
        $db->execute("ALTER TABLE `prioridades` ADD KEY `idx_ativo` (`ativo`)");
        if (!$isCheckMode) echo "  [OK] Coluna ativo adicionada em prioridades\n";
    }

    if (!$isCheckMode) {
        echo "\nPatch 01_0003 executado com sucesso!\n";
    }
} catch (Exception $e) {
    echo "Erro ao executar Patch 01_0003: " . $e->getMessage() . "\n";
    error_log("Erro Patch 01_0003: " . $e->getMessage());
    exit(1);
}
