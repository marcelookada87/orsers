-- ============================================================
-- Patch 01_0002 - Tabela clientes + vínculo em ordens_servico
-- Data: 2026-03-21
-- Versão: 1
-- ============================================================

USE `orsers`;

CREATE TABLE IF NOT EXISTS `clientes` (
    `id`                  INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `nome_razao_social`   VARCHAR(200)  NOT NULL,
    `nome_fantasia`       VARCHAR(200)  NULL,
    `tipo_pessoa`         ENUM('fisica','juridica') NOT NULL DEFAULT 'juridica',
    `documento`           VARCHAR(18)   NULL COMMENT 'CPF ou CNPJ',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Coluna em ordens_servico (executar apenas se ainda não existir — ver patch PHP)
ALTER TABLE `ordens_servico`
    ADD COLUMN `cliente_id` INT UNSIGNED NULL DEFAULT NULL AFTER `usuario_responsavel_id`;

ALTER TABLE `ordens_servico`
    ADD KEY `idx_cliente` (`cliente_id`);

ALTER TABLE `ordens_servico`
    ADD CONSTRAINT `fk_os_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
