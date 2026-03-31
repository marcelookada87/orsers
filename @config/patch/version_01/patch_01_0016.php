<?php
/**
 * Patch 01_0016 — estoque_itens: NF, fornecedor e dados de compra
 * Data: 2026-03-30
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
        echo "Executando Patch 01_0016 — Campos NF / fornecedor em estoque_itens...\n";
    }

    $col = $db->fetch(
        "SELECT COUNT(*) AS total FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'estoque_itens' AND COLUMN_NAME = 'nf_numero'"
    );
    if ((int)$col['total'] === 0) {
        $db->execute(
            "ALTER TABLE `estoque_itens`
             ADD COLUMN `nf_numero` VARCHAR(64) NULL DEFAULT NULL
                 COMMENT 'Número NF-e / nota fiscal' AFTER `descricao`,
             ADD COLUMN `nf_emissao` DATE NULL DEFAULT NULL
                 COMMENT 'Data emissão NF (opcional)' AFTER `nf_numero`,
             ADD COLUMN `fornecedor` VARCHAR(200) NULL DEFAULT NULL
                 COMMENT 'Nome ou razão social do fornecedor' AFTER `nf_emissao`,
             ADD COLUMN `fornecedor_cnpj` VARCHAR(18) NULL DEFAULT NULL
                 COMMENT 'CNPJ do fornecedor (opcional)' AFTER `fornecedor`,
             ADD COLUMN `compra_observacoes` VARCHAR(600) NULL DEFAULT NULL
                 COMMENT 'Lote, pedido, serial, demais informações da compra' AFTER `fornecedor_cnpj`"
        );
        if (!$isCheckMode) {
            echo "  [OK] Colunas NF / fornecedor / compra\n";
        }
    }

    if (!$isCheckMode) {
        echo "\nPatch 01_0016 executado com sucesso!\n";
    }
} catch (Exception $e) {
    echo 'Erro ao executar Patch 01_0016: ' . $e->getMessage() . "\n";
    error_log('Erro Patch 01_0016: ' . $e->getMessage());
    exit(1);
}
