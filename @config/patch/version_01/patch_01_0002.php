<?php
/**
 * Patch 01_0002 - Tabela clientes + coluna cliente_id em ordens_servico
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
        echo "Executando Patch 01_0002 — Clientes...\n";
    }

    $existe = $db->fetch(
        "SELECT COUNT(*) AS total FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clientes'"
    );
    if ((int)$existe['total'] === 0) {
        $db->execute("CREATE TABLE `clientes` (
            `id`                  INT UNSIGNED  NOT NULL AUTO_INCREMENT,
            `nome_razao_social`   VARCHAR(200)  NOT NULL,
            `nome_fantasia`       VARCHAR(200)  NULL,
            `tipo_pessoa`         ENUM('fisica','juridica') NOT NULL DEFAULT 'juridica',
            `documento`           VARCHAR(18)   NULL,
            `email`               VARCHAR(200)  NULL,
            `telefone`            VARCHAR(20)   NULL,
            `celular`             VARCHAR(20)   NULL,
            `cep`                 VARCHAR(12)   NULL,
            `logradouro`          VARCHAR(200)  NULL,
            `numero`              VARCHAR(20)   NULL,
            `complemento`         VARCHAR(120)  NULL,
            `bairro`              VARCHAR(120)  NULL,
            `cidade`              VARCHAR(120)  NULL,
            `uf`                  CHAR(2)       NULL,
            `observacoes`         TEXT          NULL,
            `ativo`               TINYINT(1)    NOT NULL DEFAULT 1,
            `created_at`          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_nome` (`nome_razao_social`),
            KEY `idx_ativo` (`ativo`),
            KEY `idx_documento` (`documento`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        if (!$isCheckMode) echo "  [OK] Tabela clientes criada\n";
    }

    $col = $db->fetch(
        "SELECT COUNT(*) AS total FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ordens_servico' AND COLUMN_NAME = 'cliente_id'"
    );
    if ((int)$col['total'] === 0) {
        $db->execute(
            "ALTER TABLE `ordens_servico` ADD COLUMN `cliente_id` INT UNSIGNED NULL DEFAULT NULL AFTER `usuario_responsavel_id`"
        );
        $db->execute("ALTER TABLE `ordens_servico` ADD KEY `idx_cliente` (`cliente_id`)");
        $db->execute(
            "ALTER TABLE `ordens_servico` ADD CONSTRAINT `fk_os_cliente`
             FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE"
        );
        if (!$isCheckMode) echo "  [OK] Coluna cliente_id adicionada em ordens_servico\n";
    }

    if (!$isCheckMode) {
        echo "\nPatch 01_0002 executado com sucesso!\n";
    }
} catch (Exception $e) {
    echo "Erro ao executar Patch 01_0002: " . $e->getMessage() . "\n";
    error_log("Erro Patch 01_0002: " . $e->getMessage());
    exit(1);
}
