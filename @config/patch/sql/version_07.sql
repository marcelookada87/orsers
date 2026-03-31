-- ============================================================
-- Patch 01_0007 — descricao em planos; planos P, M, G
-- Data: 2026-03-29
-- Versão: 1
-- (Preferir execução via patch_01_0007.php para idempotência.)
-- ============================================================

USE `orsers`;

ALTER TABLE `planos`
    ADD COLUMN `descricao` VARCHAR(500) NULL DEFAULT NULL AFTER `nome`;

INSERT INTO `planos` (`id`, `codigo`, `nome`, `descricao`, `max_imagens_por_os`, `max_clientes`, `max_os_mes`, `ativo`, `ordem`) VALUES
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
    `ordem` = VALUES(`ordem`);

UPDATE `planos` SET `ordem` = 999 WHERE `codigo` = 'ilimitado';
