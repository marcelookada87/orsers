-- ============================================================
-- Patch 01_0008 — Renomear planos; Ultra e Ultra Mega
-- Data: 2026-03-29
-- Versão: 1
-- (Preferir execução via patch_01_0008.php para idempotência.)
-- ============================================================

USE `orsers`;

UPDATE `planos` SET
    `codigo` = 'basico',
    `nome` = 'Básico',
    `descricao` = 'Até 5 imagens por OS, 50 clientes cadastrados e 100 ordens de serviço por mês. Ideal para autônomos e equipes enxutas.',
    `max_imagens_por_os` = 5,
    `max_clientes` = 50,
    `max_os_mes` = 100,
    `ativo` = 1,
    `ordem` = 10
WHERE `id` = 3;

UPDATE `planos` SET
    `codigo` = 'premium',
    `nome` = 'Premium',
    `descricao` = 'Até 7 imagens por OS, 75 clientes e 150 OS por mês. Para operação em crescimento.',
    `max_imagens_por_os` = 7,
    `max_clientes` = 75,
    `max_os_mes` = 150,
    `ativo` = 1,
    `ordem` = 20
WHERE `id` = 4;

UPDATE `planos` SET
    `codigo` = 'premium_plus',
    `nome` = 'Premium Plus',
    `descricao` = 'Até 10 imagens por OS, 100 clientes e 250 OS por mês. Para maior volume de atendimento.',
    `max_imagens_por_os` = 10,
    `max_clientes` = 100,
    `max_os_mes` = 250,
    `ativo` = 1,
    `ordem` = 30
WHERE `id` = 5;

INSERT INTO `planos` (`id`, `codigo`, `nome`, `descricao`, `max_imagens_por_os`, `max_clientes`, `max_os_mes`, `ativo`, `ordem`) VALUES
(6, 'ultra', 'Ultra',
 'Até 10 imagens por OS, 150 clientes e 300 ordens de serviço por mês. Para operações com volume elevado de atendimentos.',
 10, 150, 300, 1, 40),
(7, 'ultra_mega', 'Ultra Mega',
 'Até 12 imagens por OS, 250 clientes e 400 ordens de serviço por mês. Nosso plano mais completo para alta demanda.',
 12, 250, 400, 1, 50)
ON DUPLICATE KEY UPDATE
    `codigo` = VALUES(`codigo`),
    `nome` = VALUES(`nome`),
    `descricao` = VALUES(`descricao`),
    `max_imagens_por_os` = VALUES(`max_imagens_por_os`),
    `max_clientes` = VALUES(`max_clientes`),
    `max_os_mes` = VALUES(`max_os_mes`),
    `ativo` = VALUES(`ativo`),
    `ordem` = VALUES(`ordem`);

UPDATE `planos` SET `ordem` = 999 WHERE `codigo` = 'ilimitado';
