<?php
/**
 * Patch 01_0010 — Menu lateral (nav) por perfil no banco
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
        echo "Executando Patch 01_0010 — Menu lateral (nav_menu_itens)...\n";
    }

    $existe = $db->fetch(
        "SELECT COUNT(*) AS total FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'nav_menu_itens'"
    );
    if ((int)$existe['total'] === 0) {
        $db->execute(
            "CREATE TABLE `nav_menu_itens` (
                `id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `section_code`          VARCHAR(32)  NOT NULL COMMENT 'principal, gestao, administracao, conta',
                `label`                 VARCHAR(120) NOT NULL,
                `icon_class`            VARCHAR(80)  NULL DEFAULT NULL,
                `url_path`              VARCHAR(255) NOT NULL,
                `sort_order`            INT          NOT NULL DEFAULT 0,
                `ativo`                 TINYINT(1)   NOT NULL DEFAULT 1,
                `requer_estoque_ativo`  TINYINT(1)   NOT NULL DEFAULT 0,
                `item_class`            VARCHAR(160) NULL DEFAULT NULL,
                `active_rule`           VARCHAR(40)  NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_nav_section` (`section_code`, `sort_order`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        if (!$isCheckMode) {
            echo "  [OK] Tabela nav_menu_itens\n";
        }
    }

    $existe = $db->fetch(
        "SELECT COUNT(*) AS total FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'nav_menu_item_perfis'"
    );
    if ((int)$existe['total'] === 0) {
        $db->execute(
            "CREATE TABLE `nav_menu_item_perfis` (
                `menu_item_id` INT UNSIGNED NOT NULL,
                `perfil`       ENUM('admin','tecnico','cliente') NOT NULL,
                PRIMARY KEY (`menu_item_id`, `perfil`),
                CONSTRAINT `fk_nav_perfil_item` FOREIGN KEY (`menu_item_id`) REFERENCES `nav_menu_itens` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        if (!$isCheckMode) {
            echo "  [OK] Tabela nav_menu_item_perfis\n";
        }
    }

    $n = $db->fetch('SELECT COUNT(*) AS c FROM `nav_menu_itens`');
    if ((int)($n['c'] ?? 0) === 0) {
        $db->execute(
            "INSERT INTO `nav_menu_itens`
            (`id`,`section_code`,`label`,`icon_class`,`url_path`,`sort_order`,`ativo`,`requer_estoque_ativo`,`item_class`,`active_rule`) VALUES
            (1,'principal','Dashboard','fas fa-tachometer-alt','/dashboard',10,1,0,NULL,'dashboard'),
            (2,'principal','Ordens de Serviço','fas fa-clipboard-list','/ordens',20,1,0,NULL,'ordens_index'),
            (3,'principal','Nova OS','fas fa-plus-circle','/ordens/criar',30,1,0,NULL,'ordens_criar'),
            (4,'principal','Painel SLA','fas fa-stopwatch','/sla',40,1,0,NULL,'sla_painel'),
            (5,'gestao','Cadastro SLA','fas fa-sliders-h','/sla/cadastros',10,1,0,NULL,'sla_cadastro'),
            (6,'gestao','Clientes','fas fa-address-book','/clientes',20,1,0,NULL,NULL),
            (7,'gestao','Meu estoque','fas fa-boxes','/estoque',30,1,1,NULL,'estoque_tecnico'),
            (8,'administracao','Painel admin','fas fa-shield-alt','/admin',10,1,0,NULL,'admin_hub'),
            (9,'administracao','Catálogo de estoque','fas fa-barcode','/admin/estoque',20,1,0,NULL,NULL),
            (10,'administracao','Usuários','fas fa-users','/usuarios',30,1,0,NULL,NULL),
            (11,'administracao','Planos','fas fa-layer-group','/admin/planos',40,1,0,NULL,NULL),
            (12,'administracao','Relatórios','fas fa-chart-bar','/admin/relatorios',50,1,0,NULL,NULL),
            (13,'administracao','Configurações','fas fa-cog','/admin/configuracoes',60,1,0,NULL,NULL),
            (14,'conta','Meu Perfil','fas fa-user-circle','/perfil',10,1,0,NULL,NULL),
            (15,'conta','Sair','fas fa-sign-out-alt','/logout',90,1,0,'nav-item nav-logout',NULL)"
        );

        $perfis = [
            1  => ['admin', 'tecnico', 'cliente'],
            2  => ['tecnico', 'cliente'],
            3  => ['tecnico', 'cliente'],
            4  => ['tecnico', 'cliente'],
            5  => ['tecnico'],
            6  => ['tecnico'],
            7  => ['tecnico'],
            8  => ['admin'],
            9  => ['admin'],
            10 => ['admin'],
            11 => ['admin'],
            12 => ['admin'],
            13 => ['admin'],
            14 => ['admin', 'tecnico', 'cliente'],
            15 => ['admin', 'tecnico', 'cliente'],
        ];
        foreach ($perfis as $mid => $plist) {
            foreach ($plist as $pf) {
                $db->execute(
                    'INSERT IGNORE INTO `nav_menu_item_perfis` (`menu_item_id`, `perfil`) VALUES (?, ?)',
                    [$mid, $pf]
                );
            }
        }
        if (!$isCheckMode) {
            echo "  [OK] Seed nav_menu (15 itens)\n";
        }
    }

    if (!$isCheckMode) {
        echo "\nPatch 01_0010 executado com sucesso!\n";
    }
} catch (Exception $e) {
    echo 'Erro ao executar Patch 01_0010: ' . $e->getMessage() . "\n";
    error_log('Erro Patch 01_0010: ' . $e->getMessage());
    exit(1);
}
