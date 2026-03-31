<?php
/**
 * Patch 01_0015 — usuario_configuracoes + menu Configuração em Conta
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
        echo "Executando Patch 01_0015 — usuario_configuracoes + menu Conta...\n";
    }

    $tbl = $db->fetch(
        "SELECT COUNT(*) AS total FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuario_configuracoes'"
    );
    if ((int)$tbl['total'] === 0) {
        $db->execute(
            "CREATE TABLE `usuario_configuracoes` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `usuario_id` INT UNSIGNED NOT NULL,
                `chave` VARCHAR(64) NOT NULL,
                `valor` VARCHAR(512) NOT NULL DEFAULT '',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_usuario_chave` (`usuario_id`, `chave`),
                KEY `idx_usuario` (`usuario_id`),
                CONSTRAINT `fk_uc_usuario` FOREIGN KEY (`usuario_id`)
                    REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        if (!$isCheckMode) {
            echo "  [OK] Tabela usuario_configuracoes\n";
        }
    }

    $col = $db->fetch(
        "SELECT COUNT(*) AS total FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'estoque_item_codigo_tag'"
    );
    if ((int)$col['total'] === 1) {
        $db->execute(
            "INSERT IGNORE INTO `usuario_configuracoes` (`usuario_id`, `chave`, `valor`)
             SELECT `id`, 'catalogo.codigo_item_tag', `estoque_item_codigo_tag` FROM `usuarios`"
        );
        $db->execute(
            "ALTER TABLE `usuarios` DROP COLUMN `estoque_item_codigo_tag`"
        );
        if (!$isCheckMode) {
            echo "  [OK] Migração estoque_item_codigo_tag → usuario_configuracoes\n";
        }
    }

    $db->execute(
        "INSERT INTO `nav_menu_itens`
         (`id`,`section_code`,`label`,`icon_class`,`url_path`,`sort_order`,`ativo`,`requer_estoque_ativo`,`item_class`,`active_rule`)
         VALUES (18,'conta','Configuração','fas fa-sliders-h','/conta/configuracao',20,1,0,NULL,'conta_configuracao')
         ON DUPLICATE KEY UPDATE
            `section_code` = VALUES(`section_code`),
            `label` = VALUES(`label`),
            `icon_class` = VALUES(`icon_class`),
            `url_path` = VALUES(`url_path`),
            `sort_order` = VALUES(`sort_order`),
            `requer_estoque_ativo` = VALUES(`requer_estoque_ativo`),
            `active_rule` = VALUES(`active_rule`)"
    );

    $db->execute(
        "INSERT IGNORE INTO `nav_menu_item_perfis` (`menu_item_id`, `perfil`) VALUES (18, 'admin')"
    );
    $db->execute(
        "INSERT IGNORE INTO `nav_menu_item_perfis` (`menu_item_id`, `perfil`) VALUES (18, 'cliente')"
    );
    $db->execute(
        "INSERT IGNORE INTO `nav_menu_item_perfis` (`menu_item_id`, `perfil`) VALUES (18, 'tecnico')"
    );

    if (!$isCheckMode) {
        echo "  [OK] Menu id 18 → Conta / Configuração\n";
        echo "\nPatch 01_0015 executado com sucesso!\n";
    }
} catch (Exception $e) {
    echo 'Erro ao executar Patch 01_0015: ' . $e->getMessage() . "\n";
    error_log('Erro Patch 01_0015: ' . $e->getMessage());
    exit(1);
}
