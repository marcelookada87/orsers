<?php
/**
 * Patch 01_0007 — Coluna descricao em planos; planos P, M, G (limites e textos).
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

$colExists = static function ($db, string $table, string $col): bool {
    $r = $db->fetch(
        "SELECT COUNT(*) AS t FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
        [$table, $col]
    );
    return (int)($r['t'] ?? 0) > 0;
};

try {
    $db = Database::getInstance();

    if (!$isCheckMode) {
        echo "Executando Patch 01_0007 — planos P/M/G e descricao...\n";
    }

    if (!$colExists($db, 'planos', 'descricao')) {
        $db->execute(
            "ALTER TABLE `planos` ADD COLUMN `descricao` VARCHAR(500) NULL DEFAULT NULL
             AFTER `nome`"
        );
        if (!$isCheckMode) {
            echo "  [OK] planos.descricao\n";
        }
    }

    $db->execute(
        "INSERT INTO `planos` (`id`, `codigo`, `nome`, `descricao`, `max_imagens_por_os`, `max_clientes`, `max_os_mes`, `ativo`, `ordem`) VALUES
        (3, 'p', 'Plano P (Pequeno)',
         'Até 5 imagens por OS, 50 clientes cadastrados e 100 ordens de serviço por mês. Ideal para autônomos e equipes enxutas.',
         5, 50, 100, 1, 10),
        (4, 'm', 'Plano M (Médio)',
         'Até 7 imagens por OS, 75 clientes e 150 OS por mês. Para operação em crescimento.',
         7, 75, 150, 1, 20),
        (5, 'g', 'Plano G (Grande)',
         'Até 10 imagens por OS, 100 clientes e 250 OS por mês. Para maior volume de atendimento.',
         10, 100, 250, 1, 30)
        ON DUPLICATE KEY UPDATE
            `nome` = VALUES(`nome`),
            `descricao` = VALUES(`descricao`),
            `max_imagens_por_os` = VALUES(`max_imagens_por_os`),
            `max_clientes` = VALUES(`max_clientes`),
            `max_os_mes` = VALUES(`max_os_mes`),
            `ativo` = VALUES(`ativo`),
            `ordem` = VALUES(`ordem`)"
    );

    $db->execute(
        "UPDATE `planos` SET `ordem` = 999 WHERE `codigo` = 'ilimitado'"
    );

    if (!$isCheckMode) {
        echo "\nPatch 01_0007 executado com sucesso!\n";
    }
} catch (Exception $e) {
    echo "Erro ao executar Patch 01_0007: " . $e->getMessage() . "\n";
    error_log("Erro Patch 01_0007: " . $e->getMessage());
    exit(1);
}
