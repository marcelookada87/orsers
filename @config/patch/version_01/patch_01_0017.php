<?php
/**
 * Patch 01_0017 — Menu Gestão: Categorias antes de Catálogo
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
        echo "Executando Patch 01_0017 — Ordem do menu Categorias / Catálogo...\n";
    }

    $db->execute(
        'UPDATE `nav_menu_itens` SET `sort_order` = 24 WHERE `id` = 17'
    );
    $db->execute(
        'UPDATE `nav_menu_itens` SET `sort_order` = 25 WHERE `id` = 16'
    );

    if (!$isCheckMode) {
        echo "  [OK] sort_order: Categorias (24), Catálogo (25)\n";
        echo "\nPatch 01_0017 executado com sucesso!\n";
    }
} catch (Exception $e) {
    echo 'Erro ao executar Patch 01_0017: ' . $e->getMessage() . "\n";
    error_log('Erro Patch 01_0017: ' . $e->getMessage());
    exit(1);
}
