<?php
/**
 * Patch 01_0011 — Menu técnico: item «Catálogo de peças» (/estoque/catalogo)
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
        echo "Executando Patch 01_0011 — Nav: Catálogo de peças (técnico)...\n";
    }

    $exists = $db->fetch('SELECT `id` FROM `nav_menu_itens` WHERE `id` = 16 LIMIT 1');
    if (!$exists) {
        $db->execute(
            "INSERT INTO `nav_menu_itens`
            (`id`,`section_code`,`label`,`icon_class`,`url_path`,`sort_order`,`ativo`,`requer_estoque_ativo`,`item_class`,`active_rule`) VALUES
            (16,'gestao','Catálogo de peças','fas fa-barcode','/estoque/catalogo',25,1,1,NULL,'estoque_catalogo')"
        );
        if (!$isCheckMode) {
            echo "  [OK] Item nav id=16\n";
        }
    }

    $db->execute(
        'INSERT IGNORE INTO `nav_menu_item_perfis` (`menu_item_id`, `perfil`) VALUES (16, ?)',
        ['tecnico']
    );
    if (!$isCheckMode) {
        echo "  [OK] Perfil técnico vinculado\n";
    }

    if (!$isCheckMode) {
        echo "\nPatch 01_0011 executado com sucesso!\n";
    }
} catch (Exception $e) {
    echo 'Erro ao executar Patch 01_0011: ' . $e->getMessage() . "\n";
    error_log('Erro Patch 01_0011: ' . $e->getMessage());
    exit(1);
}
