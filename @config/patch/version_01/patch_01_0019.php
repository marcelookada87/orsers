<?php
/**
 * Patch 01_0019 — Menu lateral: seção "Produtos" (Categorias, Catálogo, Meu estoque)
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
        echo "Executando Patch 01_0019 — Seção Produtos no menu lateral...\n";
    }

    $db->execute(
        "UPDATE `nav_menu_itens` SET `section_code` = 'produtos' WHERE `id` IN (7, 16, 17)"
    );

    if (!$isCheckMode) {
        echo "  [OK] Itens 7, 16 e 17 na seção produtos\n";
        echo "\nPatch 01_0019 executado com sucesso!\n";
    }
} catch (Exception $e) {
    echo 'Erro ao executar Patch 01_0019: ' . $e->getMessage() . "\n";
    error_log('Erro Patch 01_0019: ' . $e->getMessage());
    exit(1);
}
