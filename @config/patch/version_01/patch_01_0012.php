<?php
/**
 * Patch 01_0012 — Menu admin: estoque só permissões; rótulo catálogo técnico
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
        echo "Executando Patch 01_0012 — Nav estoque (admin = permissões)...\n";
    }

    $db->execute(
        "UPDATE `nav_menu_itens` SET `label` = 'Permissões de estoque', `url_path` = '/admin/estoque/usuarios'
         WHERE `id` = 9"
    );
    $db->execute(
        "UPDATE `nav_menu_itens` SET `label` = 'Catálogo e cadastros' WHERE `id` = 16"
    );

    if (!$isCheckMode) {
        echo "  [OK] nav_menu_itens id 9 e 16\n";
        echo "\nPatch 01_0012 executado com sucesso!\n";
    }
} catch (Exception $e) {
    echo 'Erro ao executar Patch 01_0012: ' . $e->getMessage() . "\n";
    error_log('Erro Patch 01_0012: ' . $e->getMessage());
    exit(1);
}
