<?php
/**
 * Patch 01_0014 — usuarios.estoque_item_codigo_tag (migrada para usuario_configuracoes no 01_0015)
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
        echo "Executando Patch 01_0014 — Coluna tag de código (catálogo)...\n";
    }

    $col = $db->fetch(
        "SELECT COUNT(*) AS total FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'estoque_item_codigo_tag'"
    );
    if ((int)$col['total'] === 0) {
        $db->execute(
            "ALTER TABLE `usuarios`
             ADD COLUMN `estoque_item_codigo_tag` VARCHAR(16) NOT NULL DEFAULT 'ITEM'
             COMMENT 'Prefixo sugerido para novo código no catálogo (A–Z, 0–9)' AFTER `estoque_limite_itens`"
        );
        if (!$isCheckMode) {
            echo "  [OK] Coluna usuarios.estoque_item_codigo_tag\n";
        }
    }

    if (!$isCheckMode) {
        echo "\nPatch 01_0014 executado com sucesso! (Menu: aplicar patch 01_0015)\n";
    }
} catch (Exception $e) {
    echo 'Erro ao executar Patch 01_0014: ' . $e->getMessage() . "\n";
    error_log('Erro Patch 01_0014: ' . $e->getMessage());
    exit(1);
}
