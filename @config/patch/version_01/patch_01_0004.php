<?php
/**
 * Patch 01_0004 — Financeiro e motivo de finalização em ordens_servico
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
        echo "Executando Patch 01_0004 — ordens_servico (financeiro / finalização)...\n";
    }

    $hasCol = static function ($db, string $col): bool {
        $r = $db->fetch(
            "SELECT COUNT(*) AS t FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ordens_servico' AND COLUMN_NAME = ?",
            [$col]
        );
        return (int)($r['t'] ?? 0) > 0;
    };

    if (!$hasCol($db, 'motivo_finalizacao')) {
        $db->execute(
            "ALTER TABLE `ordens_servico` ADD COLUMN `motivo_finalizacao` VARCHAR(500) NULL AFTER `observacoes`"
        );
        if (!$isCheckMode) {
            echo "  [OK] motivo_finalizacao\n";
        }
    }
    if (!$hasCol($db, 'valor_servico')) {
        $db->execute(
            "ALTER TABLE `ordens_servico` ADD COLUMN `valor_servico` DECIMAL(12,2) NULL COMMENT 'Valor cobrado/orçamento' AFTER `motivo_finalizacao`"
        );
        if (!$isCheckMode) {
            echo "  [OK] valor_servico\n";
        }
    }
    if (!$hasCol($db, 'valor_pago')) {
        $db->execute(
            "ALTER TABLE `ordens_servico` ADD COLUMN `valor_pago` DECIMAL(12,2) NULL AFTER `valor_servico`"
        );
        if (!$isCheckMode) {
            echo "  [OK] valor_pago\n";
        }
    }
    if (!$hasCol($db, 'forma_pagamento')) {
        $db->execute(
            "ALTER TABLE `ordens_servico` ADD COLUMN `forma_pagamento` VARCHAR(40) NULL AFTER `valor_pago`"
        );
        if (!$isCheckMode) {
            echo "  [OK] forma_pagamento\n";
        }
    }
    if (!$hasCol($db, 'detalhe_financeiro')) {
        $db->execute(
            "ALTER TABLE `ordens_servico` ADD COLUMN `detalhe_financeiro` TEXT NULL COMMENT 'Peças, materiais, custos' AFTER `forma_pagamento`"
        );
        if (!$isCheckMode) {
            echo "  [OK] detalhe_financeiro\n";
        }
    }

    if (!$isCheckMode) {
        echo "\nPatch 01_0004 executado com sucesso!\n";
    }
} catch (Exception $e) {
    echo "Erro ao executar Patch 01_0004: " . $e->getMessage() . "\n";
    error_log("Erro Patch 01_0004: " . $e->getMessage());
    exit(1);
}
