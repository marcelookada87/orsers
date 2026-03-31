-- ============================================================
-- Patch 01_0015 — usuario_configuracoes + menu Configuração (Conta)
-- Data: 2026-03-29
-- Preferir execução idempotente via patch_01_0015.php.
-- Este .sql é referência; migração da coluna só após existir 01_0014.
-- ============================================================

USE `orsers`;

CREATE TABLE IF NOT EXISTS `usuario_configuracoes` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Se existir a coluna do patch 01_0014:
-- INSERT IGNORE INTO `usuario_configuracoes` (`usuario_id`, `chave`, `valor`)
--     SELECT `id`, 'catalogo.codigo_item_tag', `estoque_item_codigo_tag` FROM `usuarios`;
-- ALTER TABLE `usuarios` DROP COLUMN `estoque_item_codigo_tag`;

INSERT INTO `nav_menu_itens`
    (`id`,`section_code`,`label`,`icon_class`,`url_path`,`sort_order`,`ativo`,`requer_estoque_ativo`,`item_class`,`active_rule`)
VALUES (18,'conta','Configuração','fas fa-sliders-h','/conta/configuracao',20,1,0,NULL,'conta_configuracao')
ON DUPLICATE KEY UPDATE
    `section_code` = VALUES(`section_code`),
    `label` = VALUES(`label`),
    `icon_class` = VALUES(`icon_class`),
    `url_path` = VALUES(`url_path`),
    `sort_order` = VALUES(`sort_order`),
    `requer_estoque_ativo` = VALUES(`requer_estoque_ativo`),
    `active_rule` = VALUES(`active_rule`);

INSERT IGNORE INTO `nav_menu_item_perfis` (`menu_item_id`, `perfil`) VALUES (18, 'admin');
INSERT IGNORE INTO `nav_menu_item_perfis` (`menu_item_id`, `perfil`) VALUES (18, 'cliente');
INSERT IGNORE INTO `nav_menu_item_perfis` (`menu_item_id`, `perfil`) VALUES (18, 'tecnico');
