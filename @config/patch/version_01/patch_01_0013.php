<?php
/**
 * Patch 01_0013 — estoque_categorias.updated_at + menu lateral Categorias
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
        echo "Executando Patch 01_0013 — Categorias (updated_at + menu)...\n";
    }

    $col = $db->fetch(
        "SELECT COUNT(*) AS total FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'estoque_categorias' AND COLUMN_NAME = 'updated_at'"
    );
    if ((int)$col['total'] === 0) {
        $db->execute(
            "ALTER TABLE `estoque_categorias` ADD COLUMN `updated_at` DATETIME NULL DEFAULT NULL AFTER `created_at`"
        );
        $db->execute(
            "UPDATE `estoque_categorias` SET `updated_at` = `created_at` WHERE `updated_at` IS NULL"
        );
        $db->execute(
            "ALTER TABLE `estoque_categorias`
             MODIFY `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
        );
        if (!$isCheckMode) {
            echo "  [OK] Coluna estoque_categorias.updated_at\n";
        }
    }

    $db->execute(
        "INSERT IGNORE INTO `nav_menu_itens`
         (`id`,`section_code`,`label`,`icon_class`,`url_path`,`sort_order`,`ativo`,`requer_estoque_ativo`,`item_class`,`active_rule`)
         VALUES (17,'gestao','Categorias','fas fa-folder','/estoque/categorias',26,1,1,NULL,'estoque_categorias')"
    );
    $db->execute(
        "INSERT IGNORE INTO `nav_menu_item_perfis` (`menu_item_id`, `perfil`) VALUES (17, 'tecnico')"
    );
    if (!$isCheckMode) {
        echo "  [OK] nav_menu_itens id 17 (Categorias)\n";
        echo "\nPatch 01_0013 executado com sucesso!\n";
    }
} catch (Exception $e) {
    echo 'Erro ao executar Patch 01_0013: ' . $e->getMessage() . "\n";
    error_log('Erro Patch 01_0013: ' . $e->getMessage());
    exit(1);
}
