<?php
/**
 * Patch 01_0018 — estoque_itens.nf_valor_total
 * Data: 2026-03-31
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
        echo "Executando Patch 01_0018 — Valor total da nota fiscal em estoque_itens...\n";
    }

    $col = $db->fetch(
        "SELECT COUNT(*) AS total FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'estoque_itens' AND COLUMN_NAME = 'nf_valor_total'"
    );
    if ((int)$col['total'] === 0) {
        $db->execute(
            "ALTER TABLE `estoque_itens`
             ADD COLUMN `nf_valor_total` DECIMAL(12,2) NULL DEFAULT NULL
             COMMENT 'Valor total da nota fiscal' AFTER `nf_emissao`"
        );
        if (!$isCheckMode) {
            echo "  [OK] Coluna nf_valor_total adicionada\n";
        }
    }

    if (!$isCheckMode) {
        echo "\nPatch 01_0018 executado com sucesso!\n";
    }
} catch (Exception $e) {
    echo 'Erro ao executar Patch 01_0018: ' . $e->getMessage() . "\n";
    error_log('Erro Patch 01_0018: ' . $e->getMessage());
    exit(1);
}
