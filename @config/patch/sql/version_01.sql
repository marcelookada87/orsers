-- ============================================================
-- Patch 01_0001 - Criação da estrutura completa do banco
-- Data: 2026-03-21
-- Versão: 1
-- ============================================================

CREATE DATABASE IF NOT EXISTS `orsers`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `orsers`;

CREATE TABLE IF NOT EXISTS `patches_applied` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `patch_file`   VARCHAR(100) NOT NULL,
    `patch_number` VARCHAR(20)  NOT NULL DEFAULT '',
    `status`       ENUM('success','error') NOT NULL DEFAULT 'success',
    `output`       TEXT,
    `applied_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_patch_file` (`patch_file`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `usuarios` (
    `id`                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `nome`                VARCHAR(150)    NOT NULL,
    `email`               VARCHAR(200)    NOT NULL,
    `senha`               VARCHAR(255)    NOT NULL,
    `perfil`              ENUM('admin','tecnico','cliente') NOT NULL DEFAULT 'cliente',
    `ativo`               TINYINT(1)      NOT NULL DEFAULT 1,
    `avatar`              VARCHAR(255)    NULL,
    `telefone`            VARCHAR(20)     NULL,
    `telegram_chat_id`    VARCHAR(50)     NULL,
    `telegram_ativo`      TINYINT(1)      NOT NULL DEFAULT 0,
    `telegram_token`      VARCHAR(64)     NULL,
    `token`               VARCHAR(100)    NULL,
    `token_expira`        DATETIME        NULL,
    `ultimo_acesso`       DATETIME        NULL,
    `created_at`          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_email` (`email`),
    KEY `idx_perfil` (`perfil`),
    KEY `idx_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `categorias_os` (
    `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nome`      VARCHAR(100) NOT NULL,
    `descricao` VARCHAR(255) NULL,
    `cor`       VARCHAR(7)   NOT NULL DEFAULT '#3B82F6',
    `sla_horas` DECIMAL(8,2) NOT NULL DEFAULT 24.00,
    `ativo`     TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `prioridades` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nome`             VARCHAR(50)  NOT NULL,
    `nivel`            TINYINT      NOT NULL DEFAULT 3,
    `cor`              VARCHAR(7)   NOT NULL DEFAULT '#6B7280',
    `sla_multiplicador` DECIMAL(4,2) NOT NULL DEFAULT 1.00,
    PRIMARY KEY (`id`),
    KEY `idx_nivel` (`nivel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ordens_servico` (
    `id`                   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `numero`               VARCHAR(20)   NOT NULL,
    `titulo`               VARCHAR(200)  NOT NULL,
    `descricao`            TEXT          NOT NULL,
    `status`               ENUM('aberta','em_andamento','aguardando','finalizada','cancelada') NOT NULL DEFAULT 'aberta',
    `prioridade_id`        INT UNSIGNED  NOT NULL,
    `categoria_id`         INT UNSIGNED  NOT NULL,
    `usuario_criador_id`   INT UNSIGNED  NOT NULL,
    `usuario_responsavel_id` INT UNSIGNED NULL,
    `sla_prazo`            DATETIME      NULL,
    `sla_horas_previstas`  DECIMAL(8,2)  NULL,
    `data_abertura`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `data_inicio`          DATETIME      NULL,
    `data_finalizacao`     DATETIME      NULL,
    `observacoes`          TEXT          NULL,
    `origem`               ENUM('web','telegram') NOT NULL DEFAULT 'web',
    `created_at`           DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_numero` (`numero`),
    KEY `idx_status` (`status`),
    KEY `idx_prioridade` (`prioridade_id`),
    KEY `idx_categoria` (`categoria_id`),
    KEY `idx_criador` (`usuario_criador_id`),
    KEY `idx_responsavel` (`usuario_responsavel_id`),
    KEY `idx_sla_prazo` (`sla_prazo`),
    CONSTRAINT `fk_os_prioridade`  FOREIGN KEY (`prioridade_id`)        REFERENCES `prioridades` (`id`),
    CONSTRAINT `fk_os_categoria`   FOREIGN KEY (`categoria_id`)         REFERENCES `categorias_os` (`id`),
    CONSTRAINT `fk_os_criador`     FOREIGN KEY (`usuario_criador_id`)   REFERENCES `usuarios` (`id`),
    CONSTRAINT `fk_os_responsavel` FOREIGN KEY (`usuario_responsavel_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ordens_imagens` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ordem_id`   INT UNSIGNED NOT NULL,
    `arquivo`    VARCHAR(255) NOT NULL,
    `tamanho_kb` INT UNSIGNED NOT NULL DEFAULT 0,
    `largura`    INT UNSIGNED NOT NULL DEFAULT 0,
    `altura`     INT UNSIGNED NOT NULL DEFAULT 0,
    `criado_por` INT UNSIGNED NOT NULL,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ordem` (`ordem_id`),
    CONSTRAINT `fk_img_ordem`   FOREIGN KEY (`ordem_id`)   REFERENCES `ordens_servico` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_img_usuario` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ordens_historico` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ordem_id`    INT UNSIGNED NOT NULL,
    `usuario_id`  INT UNSIGNED NOT NULL,
    `acao`        VARCHAR(50)  NOT NULL,
    `descricao`   VARCHAR(500) NOT NULL,
    `dados_json`  JSON         NULL,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ordem` (`ordem_id`),
    CONSTRAINT `fk_hist_ordem`   FOREIGN KEY (`ordem_id`)   REFERENCES `ordens_servico` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_hist_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ordens_comentarios` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ordem_id`   INT UNSIGNED NOT NULL,
    `usuario_id` INT UNSIGNED NOT NULL,
    `comentario` TEXT         NOT NULL,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ordem` (`ordem_id`),
    CONSTRAINT `fk_com_ordem`   FOREIGN KEY (`ordem_id`)   REFERENCES `ordens_servico` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_com_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notificacoes_fila` (
    `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `tipo`           VARCHAR(50)   NOT NULL,
    `destinatario_id` INT UNSIGNED NOT NULL,
    `canal`          ENUM('telegram','email') NOT NULL DEFAULT 'telegram',
    `mensagem`       TEXT          NOT NULL,
    `status`         ENUM('pendente','enviado','erro') NOT NULL DEFAULT 'pendente',
    `tentativas`     TINYINT       NOT NULL DEFAULT 0,
    `enviado_at`     DATETIME      NULL,
    `created_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_status`  (`status`),
    KEY `idx_destino` (`destinatario_id`),
    CONSTRAINT `fk_fila_usuario` FOREIGN KEY (`destinatario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `telegram_updates` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `update_id`  BIGINT       NOT NULL,
    `chat_id`    VARCHAR(50)  NOT NULL,
    `mensagem`   TEXT         NULL,
    `processado` TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_update_id` (`update_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed
INSERT IGNORE INTO `prioridades` (`id`, `nome`, `nivel`, `cor`, `sla_multiplicador`) VALUES
(1, 'Urgente', 1, '#EF4444', 0.50),
(2, 'Alta',    2, '#F97316', 0.75),
(3, 'Normal',  3, '#3B82F6', 1.00),
(4, 'Baixa',   4, '#10B981', 1.50),
(5, 'Mínima',  5, '#6B7280', 2.00);

INSERT IGNORE INTO `categorias_os` (`id`, `nome`, `descricao`, `cor`, `sla_horas`) VALUES
(1, 'Suporte Técnico',   'Problemas de hardware e software',   '#3B82F6', 8),
(2, 'Manutenção',        'Manutenção preventiva e corretiva',  '#10B981', 24),
(3, 'Instalação',        'Instalação de equipamentos',         '#8B5CF6', 48),
(4, 'Consultoria',       'Consultorias e assessorias',         '#F59E0B', 72),
(5, 'Infraestrutura',    'Rede e servidores',                  '#EF4444', 4);

-- Admin padrão (senha: Admin@123)
INSERT IGNORE INTO `usuarios` (`id`, `nome`, `email`, `senha`, `perfil`) VALUES
(1, 'Administrador', 'admin@orsers.local',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uXkdCopCa', 'admin');
