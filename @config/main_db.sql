-- ============================================================
-- OS Manager — Banco de Dados Principal (main_db.sql)
-- Versão: 1  |  Última atualização: 2026-03-29 (estoque 01_0009)
-- ============================================================

CREATE DATABASE IF NOT EXISTS `orsers`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `orsers`;

-- ----------------------------------------------------------
-- Tabela de controle de patches
-- ----------------------------------------------------------
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

-- ----------------------------------------------------------
-- Planos (limites por conta)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `planos` (
    `id`                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `codigo`               VARCHAR(32)  NOT NULL COMMENT 'free, ilimitado, etc.',
    `nome`                 VARCHAR(100) NOT NULL,
    `descricao`            VARCHAR(500) NULL DEFAULT NULL COMMENT 'Texto para escolha do plano no cadastro',
    `max_imagens_por_os`   INT UNSIGNED NULL COMMENT 'NULL = ilimitado no plano (teto global no app)',
    `max_clientes`         INT UNSIGNED NULL COMMENT 'NULL = ilimitado',
    `max_os_mes`           INT UNSIGNED NULL COMMENT 'NULL = ilimitado',
    `ativo`                TINYINT(1)   NOT NULL DEFAULT 1,
    `ordem`                INT          NOT NULL DEFAULT 0,
    `created_at`           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_plano_codigo` (`codigo`),
    KEY `idx_plano_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `planos` (`id`, `codigo`, `nome`, `descricao`, `max_imagens_por_os`, `max_clientes`, `max_os_mes`, `ativo`, `ordem`) VALUES
(1, 'free',         'Free',         NULL, 3,  25, 50, 1, 1),
(2, 'ilimitado',    'Ilimitado',    NULL, NULL, NULL, NULL, 1, 999),
(3, 'basico',       'Básico',       'Até 5 imagens por OS, 50 clientes cadastrados e 100 ordens de serviço por mês. Ideal para autônomos e equipes enxutas.', 5, 50, 100, 1, 10),
(4, 'premium',      'Premium',      'Até 7 imagens por OS, 75 clientes e 150 OS por mês. Para operação em crescimento.', 7, 75, 150, 1, 20),
(5, 'premium_plus', 'Premium Plus', 'Até 10 imagens por OS, 100 clientes e 250 OS por mês. Para maior volume de atendimento.', 10, 100, 250, 1, 30),
(6, 'ultra',        'Ultra',        'Até 10 imagens por OS, 150 clientes e 300 ordens de serviço por mês. Para operações com volume elevado de atendimentos.', 10, 150, 300, 1, 40),
(7, 'ultra_mega',   'Ultra Mega',   'Até 12 imagens por OS, 250 clientes e 400 ordens de serviço por mês. Nosso plano mais completo para alta demanda.', 12, 250, 400, 1, 50);

-- ----------------------------------------------------------
-- Usuários do sistema
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id`                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `nome`                VARCHAR(150)    NOT NULL,
    `email`               VARCHAR(200)    NOT NULL,
    `senha`               VARCHAR(255)    NOT NULL,
    `perfil`              ENUM('admin','tecnico','cliente') NOT NULL DEFAULT 'cliente',
    `admin`               TINYINT(1)      NOT NULL DEFAULT 0,
    `plano_id`            INT UNSIGNED    NOT NULL DEFAULT 1,
    `max_imagens_por_os_override` INT UNSIGNED NULL DEFAULT NULL,
    `max_clientes_override`       INT UNSIGNED NULL DEFAULT NULL,
    `max_os_mes_override`         INT UNSIGNED NULL DEFAULT NULL,
    `ativo`               TINYINT(1)      NOT NULL DEFAULT 1,
    `avatar`              VARCHAR(255)    NULL,
    `telefone`            VARCHAR(20)     NULL,
    `telegram_chat_id`    VARCHAR(50)     NULL,
    `telegram_ativo`      TINYINT(1)      NOT NULL DEFAULT 0,
    `telegram_token`      VARCHAR(64)     NULL COMMENT 'Token para vinculação via /start',
    `estoque_ativo`       TINYINT(1)      NOT NULL DEFAULT 0,
    `estoque_limite_itens` INT UNSIGNED   NULL DEFAULT NULL COMMENT 'NULL = ilimitado tipos de item',
    `token`               VARCHAR(100)    NULL,
    `token_expira`        DATETIME        NULL,
    `ultimo_acesso`       DATETIME        NULL,
    `created_at`          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_email` (`email`),
    KEY `idx_perfil` (`perfil`),
    KEY `idx_ativo` (`ativo`),
    KEY `idx_usuario_plano` (`plano_id`),
    CONSTRAINT `fk_usuario_plano` FOREIGN KEY (`plano_id`) REFERENCES `planos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Configurações por usuário (chave/valor; ex.: catálogo, integrações)
-- ----------------------------------------------------------
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
    CONSTRAINT `fk_uc_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Categorias de OS
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categorias_os` (
    `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nome`      VARCHAR(100) NOT NULL,
    `descricao` VARCHAR(255) NULL,
    `cor`       VARCHAR(7)   NOT NULL DEFAULT '#3B82F6',
    `sla_horas` DECIMAL(8,2) NOT NULL DEFAULT 24.00 COMMENT 'SLA padrão em horas',
    `ativo`     TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Prioridades
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `prioridades` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nome`             VARCHAR(50)  NOT NULL,
    `nivel`            TINYINT      NOT NULL DEFAULT 3 COMMENT '1=Urgente 2=Alta 3=Normal 4=Baixa 5=Mínima',
    `cor`              VARCHAR(7)   NOT NULL DEFAULT '#6B7280',
    `sla_multiplicador` DECIMAL(4,2) NOT NULL DEFAULT 1.00 COMMENT 'Multiplicador sobre sla_horas da categoria',
    `ativo`            TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `idx_nivel` (`nivel`),
    KEY `idx_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Clientes (cadastro para vínculo com OS)
-- ----------------------------------------------------------
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
    `usuario_cadastro_id` INT UNSIGNED  NOT NULL,
    `created_at`          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_nome` (`nome_razao_social`),
    KEY `idx_ativo` (`ativo`),
    KEY `idx_documento` (`documento`),
    KEY `idx_cliente_cadastro_user` (`usuario_cadastro_id`),
    CONSTRAINT `fk_cliente_usuario_cadastro` FOREIGN KEY (`usuario_cadastro_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Ordens de Serviço
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ordens_servico` (
    `id`                   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `numero`               VARCHAR(20)   NOT NULL COMMENT 'OS-YYYY-NNNN',
    `titulo`               VARCHAR(200)  NOT NULL,
    `descricao`            TEXT          NOT NULL,
    `status`               ENUM('aberta','em_andamento','aguardando','finalizada','cancelada') NOT NULL DEFAULT 'aberta',
    `prioridade_id`        INT UNSIGNED  NOT NULL,
    `categoria_id`         INT UNSIGNED  NOT NULL,
    `usuario_criador_id`   INT UNSIGNED  NOT NULL,
    `usuario_responsavel_id` INT UNSIGNED NULL,
    `cliente_id`           INT UNSIGNED  NULL,
    `sla_prazo`            DATETIME      NULL,
    `sla_horas_previstas`  DECIMAL(8,2)  NULL,
    `data_abertura`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `data_inicio`          DATETIME      NULL,
    `data_finalizacao`     DATETIME      NULL,
    `observacoes`          TEXT          NULL,
    `motivo_finalizacao`   VARCHAR(500)  NULL,
    `valor_servico`        DECIMAL(12,2) NULL COMMENT 'Valor cobrado/orçamento',
    `valor_pago`           DECIMAL(12,2) NULL,
    `forma_pagamento`      VARCHAR(40)   NULL,
    `detalhe_financeiro`   TEXT          NULL COMMENT 'Peças, materiais, custos',
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
    KEY `idx_cliente` (`cliente_id`),
    KEY `idx_sla_prazo` (`sla_prazo`),
    KEY `idx_os_criador_data` (`usuario_criador_id`, `data_abertura`),
    CONSTRAINT `fk_os_prioridade`  FOREIGN KEY (`prioridade_id`)        REFERENCES `prioridades` (`id`),
    CONSTRAINT `fk_os_categoria`   FOREIGN KEY (`categoria_id`)         REFERENCES `categorias_os` (`id`),
    CONSTRAINT `fk_os_criador`     FOREIGN KEY (`usuario_criador_id`)   REFERENCES `usuarios` (`id`),
    CONSTRAINT `fk_os_responsavel` FOREIGN KEY (`usuario_responsavel_id`) REFERENCES `usuarios` (`id`),
    CONSTRAINT `fk_os_cliente`     FOREIGN KEY (`cliente_id`)           REFERENCES `clientes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Imagens das Ordens
-- ----------------------------------------------------------
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

-- ----------------------------------------------------------
-- Configurações do sistema (chave/valor)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sistema_config` (
    `chave`      VARCHAR(64)  NOT NULL,
    `valor`      VARCHAR(255) NOT NULL,
    `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sistema_config` (`chave`, `valor`) VALUES ('max_imagens_por_os', '5')
ON DUPLICATE KEY UPDATE `chave` = `chave`;

-- ----------------------------------------------------------
-- Histórico / Auditoria das Ordens
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ordens_historico` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ordem_id`    INT UNSIGNED NOT NULL,
    `usuario_id`  INT UNSIGNED NOT NULL,
    `acao`        VARCHAR(50)  NOT NULL COMMENT 'criacao, alteracao, status, comentario, imagem, finalizacao',
    `descricao`   VARCHAR(500) NOT NULL,
    `dados_json`  JSON         NULL,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ordem` (`ordem_id`),
    CONSTRAINT `fk_hist_ordem`   FOREIGN KEY (`ordem_id`)   REFERENCES `ordens_servico` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_hist_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Comentários das Ordens
-- ----------------------------------------------------------
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

-- ----------------------------------------------------------
-- Fila de Notificações
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notificacoes_fila` (
    `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `tipo`           VARCHAR(50)   NOT NULL COMMENT 'nova_os, status_alterado, sla_alerta, comentario',
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

-- ----------------------------------------------------------
-- Controle de Updates do Telegram
-- ----------------------------------------------------------
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

-- ----------------------------------------------------------
-- Estoque — categorias, catálogo, saldo por usuário, movimentações, itens na OS
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `estoque_categorias` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nome`       VARCHAR(120) NOT NULL,
    `descricao`  VARCHAR(500) NULL DEFAULT NULL,
    `ativo`      TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_estoque_cat_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `estoque_itens` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `categoria_id` INT UNSIGNED NULL DEFAULT NULL,
    `codigo`       VARCHAR(64)  NOT NULL,
    `nome`         VARCHAR(200) NOT NULL,
    `descricao`    VARCHAR(500) NULL DEFAULT NULL,
    `nf_numero`          VARCHAR(64)  NULL DEFAULT NULL COMMENT 'Número NF-e / nota fiscal',
    `nf_emissao`         DATE         NULL DEFAULT NULL COMMENT 'Data emissão NF (opcional)',
    `nf_valor_total`     DECIMAL(12,2) NULL DEFAULT NULL COMMENT 'Valor total da nota fiscal',
    `fornecedor`         VARCHAR(200) NULL DEFAULT NULL COMMENT 'Nome ou razão social do fornecedor',
    `fornecedor_cnpj`    VARCHAR(18)  NULL DEFAULT NULL COMMENT 'CNPJ do fornecedor (opcional)',
    `compra_observacoes` VARCHAR(600) NULL DEFAULT NULL COMMENT 'Lote, pedido, serial, demais informações da compra',
    `unidade`      VARCHAR(16)  NOT NULL DEFAULT 'un',
    `ativo`        TINYINT(1)   NOT NULL DEFAULT 1,
    `criado_por`   INT UNSIGNED NOT NULL,
    `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_estoque_item_codigo` (`codigo`),
    KEY `idx_estoque_item_cat` (`categoria_id`),
    KEY `idx_estoque_item_ativo` (`ativo`),
    CONSTRAINT `fk_estoque_item_cat` FOREIGN KEY (`categoria_id`) REFERENCES `estoque_categorias` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_estoque_item_user` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `estoque_saldo` (
    `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id`         INT UNSIGNED NOT NULL,
    `item_id`            INT UNSIGNED NOT NULL,
    `quantidade`         DECIMAL(10,3) NOT NULL DEFAULT 0.000,
    `quantidade_minima`  DECIMAL(10,3) NOT NULL DEFAULT 0.000,
    `created_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_estoque_saldo_user_item` (`usuario_id`, `item_id`),
    KEY `idx_estoque_saldo_item` (`item_id`),
    CONSTRAINT `fk_estoque_saldo_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_estoque_saldo_item` FOREIGN KEY (`item_id`)    REFERENCES `estoque_itens` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `estoque_movimentacoes` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id`       INT UNSIGNED NOT NULL,
    `item_id`          INT UNSIGNED NOT NULL,
    `tipo`             ENUM('entrada','saida','ajuste') NOT NULL,
    `quantidade`       DECIMAL(10,3) NOT NULL,
    `saldo_anterior`   DECIMAL(10,3) NOT NULL,
    `saldo_posterior`  DECIMAL(10,3) NOT NULL,
    `referencia_tipo`  ENUM('os','manual','ajuste') NOT NULL DEFAULT 'manual',
    `referencia_id`    INT UNSIGNED NULL DEFAULT NULL,
    `observacao`       VARCHAR(500) NULL DEFAULT NULL,
    `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_estoque_mov_user` (`usuario_id`, `created_at`),
    KEY `idx_estoque_mov_item` (`item_id`, `created_at`),
    KEY `idx_estoque_mov_ref` (`referencia_tipo`, `referencia_id`),
    CONSTRAINT `fk_estoque_mov_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
    CONSTRAINT `fk_estoque_mov_item` FOREIGN KEY (`item_id`)    REFERENCES `estoque_itens` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `estoque_os_itens` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ordem_id`          INT UNSIGNED NOT NULL,
    `item_id`           INT UNSIGNED NOT NULL,
    `usuario_id`        INT UNSIGNED NOT NULL,
    `quantidade`        DECIMAL(10,3) NOT NULL,
    `movimentacao_id`   INT UNSIGNED NOT NULL,
    `observacao`        VARCHAR(500) NULL DEFAULT NULL,
    `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_estoque_os_ordem` (`ordem_id`),
    KEY `idx_estoque_os_item` (`item_id`),
    CONSTRAINT `fk_estoque_os_ordem` FOREIGN KEY (`ordem_id`) REFERENCES `ordens_servico` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_estoque_os_item` FOREIGN KEY (`item_id`) REFERENCES `estoque_itens` (`id`),
    CONSTRAINT `fk_estoque_os_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
    CONSTRAINT `fk_estoque_os_mov` FOREIGN KEY (`movimentacao_id`) REFERENCES `estoque_movimentacoes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `estoque_categorias` (`id`, `nome`, `descricao`, `ativo`) VALUES
(1, 'Geral', 'Itens sem categoria específica', 1);

-- ----------------------------------------------------------
-- Menu lateral (por perfil) — ver patch 01_0010 e NavMenu
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `nav_menu_itens` (
    `id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `section_code`          VARCHAR(32)  NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nav_menu_item_perfis` (
    `menu_item_id` INT UNSIGNED NOT NULL,
    `perfil`       ENUM('admin','tecnico','cliente') NOT NULL,
    PRIMARY KEY (`menu_item_id`, `perfil`),
    CONSTRAINT `fk_nav_perfil_item` FOREIGN KEY (`menu_item_id`) REFERENCES `nav_menu_itens` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `nav_menu_itens` (`id`,`section_code`,`label`,`icon_class`,`url_path`,`sort_order`,`ativo`,`requer_estoque_ativo`,`item_class`,`active_rule`) VALUES
(1,'principal','Dashboard','fas fa-tachometer-alt','/dashboard',10,1,0,NULL,'dashboard'),
(2,'principal','Ordens de Serviço','fas fa-clipboard-list','/ordens',20,1,0,NULL,'ordens_index'),
(3,'principal','Nova OS','fas fa-plus-circle','/ordens/criar',30,1,0,NULL,'ordens_criar'),
(4,'principal','Painel SLA','fas fa-stopwatch','/sla',40,1,0,NULL,'sla_painel'),
(5,'gestao','Cadastro SLA','fas fa-sliders-h','/sla/cadastros',10,1,0,NULL,'sla_cadastro'),
(6,'gestao','Clientes','fas fa-address-book','/clientes',20,1,0,NULL,NULL),
(17,'produtos','Categorias','fas fa-folder','/estoque/categorias',24,1,1,NULL,'estoque_categorias'),
(16,'produtos','Catálogo e cadastros','fas fa-barcode','/estoque/catalogo',25,1,1,NULL,'estoque_catalogo'),
(7,'produtos','Meu estoque','fas fa-boxes','/estoque',30,1,1,NULL,'estoque_tecnico'),
(8,'administracao','Painel admin','fas fa-shield-alt','/admin',10,1,0,NULL,'admin_hub'),
(9,'administracao','Permissões de estoque','fas fa-barcode','/admin/estoque/usuarios',20,1,0,NULL,NULL),
(10,'administracao','Usuários','fas fa-users','/usuarios',30,1,0,NULL,NULL),
(11,'administracao','Planos','fas fa-layer-group','/admin/planos',40,1,0,NULL,NULL),
(12,'administracao','Relatórios','fas fa-chart-bar','/admin/relatorios',50,1,0,NULL,NULL),
(13,'administracao','Configurações','fas fa-cog','/admin/configuracoes',60,1,0,NULL,NULL),
(14,'conta','Meu Perfil','fas fa-user-circle','/perfil',10,1,0,NULL,NULL),
(18,'conta','Configuração','fas fa-sliders-h','/conta/configuracao',20,1,0,NULL,'conta_configuracao'),
(15,'conta','Sair','fas fa-sign-out-alt','/logout',90,1,0,'nav-item nav-logout',NULL);

INSERT IGNORE INTO `nav_menu_item_perfis` (`menu_item_id`, `perfil`) VALUES
(1,'admin'),(1,'tecnico'),(1,'cliente'),
(2,'tecnico'),(2,'cliente'),
(3,'tecnico'),(3,'cliente'),
(4,'tecnico'),(4,'cliente'),
(5,'tecnico'),
(6,'tecnico'),
(16,'tecnico'),
(17,'tecnico'),
(7,'tecnico'),
(8,'admin'),(9,'admin'),(10,'admin'),(11,'admin'),(12,'admin'),(13,'admin'),
(14,'admin'),(14,'tecnico'),(14,'cliente'),
(18,'admin'),(18,'tecnico'),(18,'cliente'),
(15,'admin'),(15,'tecnico'),(15,'cliente');

-- ----------------------------------------------------------
-- Dados iniciais (seed)
-- ----------------------------------------------------------
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

-- Usuário admin padrão  (senha: Admin@123)
INSERT IGNORE INTO `usuarios` (`id`, `nome`, `email`, `senha`, `perfil`, `admin`, `plano_id`) VALUES
(1, 'Administrador', 'admin@orsers.local',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uXkdCopCa', 'admin', 1, 2);
