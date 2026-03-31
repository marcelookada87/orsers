# OS Manager — Documentação do sistema

Sistema de **Ordens de Serviço** (SAAS) em PHP (MVC), com SLA, clientes, upload de imagens, painel de monitoramento e integração **Telegram**.

---

## Índice

1. [Visão geral](#1-visão-geral)
2. [Requisitos](#2-requisitos)
3. [Estrutura de pastas](#3-estrutura-de-pastas)
4. [Configuração](#4-configuração)
5. [Banco de dados e patches](#5-banco-de-dados-e-patches)
6. [Arquitetura MVC e núcleo](#6-arquitetura-mvc-e-núcleo)
7. [Rotas](#7-rotas)
8. [Funcionalidades principais](#8-funcionalidades-principais)
9. [Frontend e assets](#9-frontend-e-assets)
10. [Telegram](#10-telegram)
11. [Pontos de entrada (entry points)](#11-pontos-de-entrada-entry-points)
12. [Convenções do projeto](#12-convenções-do-projeto)
13. [Menu lateral (banco de dados)](#13-menu-lateral-banco-de-dados)

---

## 1. Visão geral

| Item | Descrição |
|------|-----------|
| **Nome** | OS Manager (`APP_NAME` em `@config/config.php`) |
| **Stack** | PHP ≥ 8.1, MySQL (utf8mb4), Apache com `mod_rewrite` (recomendado) |
| **Padrão** | Front controller (`index.php`), rotas em `Router`, models estendem `Model` |
| **Autenticação** | Sessão (`Auth`), perfis `admin`, `tecnico` e `cliente`; itens do menu lateral vêm do banco por perfil (ver §13) |

---

## 2. Requisitos

- PHP 8.1 ou superior (tipagem, `readonly` onde aplicável)
- Extensões comuns: `pdo_mysql`, `json`, `mbstring` (recomendado)
- MySQL 5.7+ / MariaDB compatível
- Servidor web apontando a raiz do projeto; URLs amigáveis via rewrite para `index.php` (se usar Apache, `.htaccess` na raiz)

---

## 3. Estrutura de pastas

```
orsers/
├── index.php                 # Front controller e registro de rotas
├── patch.php                 # Utilitário/interface de aplicação de patches (se existir)
├── telegram_webhook.php      # Entrada do webhook Telegram (se na raiz)
├── cron_telegram_api.php     # Cron / fila Telegram (se na raiz)
├── @config/                  # Configuração
│   ├── config.php            # Constantes DB, BASE_URL, includes globais
│   ├── telegram.php          # Token bot (opcional)
│   ├── telegram_cron_secure.php
│   ├── main_db.sql           # Referência do schema “atual”
│   └── patch/                # Patches versionados (PHP + SQL de referência)
├── @core/                    # Classes base (Database, Router, Auth, SLAHelper, etc.)
├── @models/                  # Modelos de domínio
├── @controllers/             # Controladores
├── @views/                   # Views PHP (layout, páginas, erros)
├── assets/                   # CSS, JS, vendor (jQuery, DataTables locais)
├── uploads/ordens/           # Arquivos de imagem das OS (gerado em runtime)
├── db/                       # SQL de referência (ex.: main.sql)
└── documentacao/             # Esta documentação
```

---

## 4. Configuração

Arquivo principal: **`@config/config.php`**

| Constante / item | Função |
|------------------|--------|
| `DB_VERSION` | Versão lógica do banco (ajuste **manual** após aplicar patches) |
| `DB_*` | Conexão MySQL |
| `BASE_URL` | URL base da aplicação (derivada do host/script) |
| `ROOT_PATH` | Caminho físico da raiz do projeto |
| `UPLOAD_PATH` / `UPLOAD_URL` | Diretório e URL pública das imagens das OS |
| `MAX_IMAGES_PER_OS`, `MAX_IMAGE_SIZE_KB`, `IMAGE_MAX_DIMENSION` | Limites de upload |
| `SESSION_*` | Nome da sessão; `SESSION_LIFETIME` (2h) sem “manter conectado”; `SESSION_REMEMBER_LIFETIME` (1 ano) com a opção no login |

Telegram: se existir `@config/telegram.php`, é carregado automaticamente (tokens, etc.).

**Novos models/controllers** devem ser registrados com `require_once` em `config.php` para o autoload manual do projeto.

---

## 5. Banco de dados e patches

- **Schema de referência:** `@config/main_db.sql` e, em paralelo, `db/main.sql` (manter alinhados quando houver mudança estrutural).
- **Patches:** pasta `@config/patch/version_XX/` com arquivos `patch_XX_YYYY.php` executando SQL idempotente quando possível.
- **SQL espelho:** `@config/patch/sql/version_XX.sql` para documentação/execução manual.
- **Regra de negócio do projeto:** alterações em tabelas, triggers, views, procedures, índices, etc. exigem **novo patch** na sequência e atualização do `main` correspondente — **não** alterar `DB_VERSION` automaticamente pelo código; é **manual**.

Arquivos de patch existentes (exemplos): `patch_01_0001.php`, `patch_01_0002.php`, `patch_01_0003.php` (ex.: coluna `ativo` em `prioridades`).

**Menu lateral:** o patch **`patch_01_0010`** cria as tabelas `nav_menu_itens` e `nav_menu_item_perfis` e insere o seed dos itens por perfil. Sem esse patch aplicado, o sidebar pode ficar vazio (o layout trata erro de query de forma segura). O **`patch_01_0011`** adiciona o item de menu do catálogo (`/estoque/catalogo`) para o técnico com estoque ativo. O **`patch_01_0012`** ajusta o menu do admin (estoque → só **Permissões de estoque** em `/admin/estoque/usuarios`) e o rótulo do catálogo no técnico. Catálogo, categorias e relatório de consumo em OS ficam com o **técnico**; o **admin** só liga ou desliga o módulo por usuário.

---

## 6. Arquitetura MVC e núcleo

### Core (`@core/`)

| Classe | Papel |
|--------|--------|
| `Database` | PDO singleton, queries |
| `Router` | Rotas GET/POST/ANY, parâmetros `{id}` |
| `Controller` | `render()`, `redirect()`, `json()`, flash, `abort()` |
| `Model` | CRUD básico (`find`, `create`, `update`, …) |
| `Auth` | Login, sessão, `requireLogin`, `requireTecnico`, `requireAdmin` |
| `SLAHelper` | Cálculo de prazo SLA, percentual, labels, tempo restante |
| `ImageCompressor` | Redimensionamento/compressão de imagens |
| `TelegramSender` | Envio de mensagens Telegram |
| `NavMenu` | Monta o menu lateral a partir de `nav_menu_itens` + `nav_menu_item_perfis` para o usuário logado (perfil e regras como `requer_estoque_ativo`, `active_rule`) |

### Models (`@models/`)

Incluem entre outros: `User`, `Cliente`, `CategoriaOS`, `Prioridade`, `Ordem`, `OrdemImagem`, `OrdemHistorico`.

### Controllers (`@controllers/`)

Responsáveis por regras HTTP, chamadas aos models e renderização das views.

### Views (`@views/`)

- `layout/header.php`, `layout/footer.php` — layout padrão; o **sidebar** é gerado só pelo banco via `NavMenu` (ver §13); **scripts** (jQuery, app, extras) ficam no **footer** após o conteúdo (conforme convenção do projeto).
- Pastas por domínio: `dashboard/`, `ordens/`, `clientes/`, `sla/`, `estoque/`, `auth/`, `users/`, `errors/`.

---

## 7. Rotas

Resumo (detalhe em `index.php`):

| Método | Caminho | Controller |
|--------|---------|------------|
| GET/POST | `/login`, `/logout` | `AuthController` |
| GET | `/`, `/dashboard` | `DashboardController` |
| GET/POST | `/clientes`, `/clientes/criar`, `/clientes/{id}/editar`, … | `ClienteController` |
| GET/POST | `/ordens`, criar, ver, editar, comentar, deletar imagem | `OrdemController` |
| GET/POST | `/sla/cadastros`, categorias, prioridades | `SlaCadastroController` |
| POST | `/sla/ordem/{id}/resetar-sla`, `/sla/ordem/{id}/concluir` | `SLAController` |
| GET | `/sla`, `/api/sla` | `SLAController` |
| GET/POST | `/usuarios`, `/perfil`, … | `UserController` |
| GET/POST | `/estoque`, `/estoque/catalogo`, `/estoque/entrada`, `/estoque/historico`, `/estoque/relatorio`, APIs `/api/estoque/…` | `EstoqueController` |
| GET/POST | `/admin/estoque/usuarios` (permissões por usuário) | `EstoqueAdminController` |
| ANY/POST | `/telegram/webhook`, `/api/cron/telegram`, ping | Telegram |

**Ordem:** rotas **mais específicas** (ex.: `/sla/cadastros`) devem estar **antes** de `/sla` no `index.php`.

---

## 8. Funcionalidades principais

### Ordens de serviço (OS)

- CRUD, filtros, paginação na listagem.
- **SLA:** `sla_horas` da categoria × multiplicador da prioridade; prazo em `sla_prazo`.
- **Prioridades:** listagem com ativas/inativas conforme regra (criar OS só com ativas; edição pode incluir a atual se inativa).
- **Imagens:** upload múltiplo, compressão, limite por OS.
- **Histórico** e **comentários** por OS.
- **Sugestões de título** (mais usados, últimos do usuário, lista padrão) em criar/editar.

### Painel SLA (`/sla`)

- Lista OS não finalizadas/canceladas com barras de progresso e indicadores.
- Ações (técnico): **Reset SLA** (recalcula prazo a partir de agora), **Concluir** OS, **Ver**, **Editar**, **Comentar** (atalhos).

### Cadastro SLA (`/sla/cadastros`)

- CRUD de **categorias** (horas base) e **prioridades** (multiplicador, nível, cor, ativo).

### Clientes

- Cadastro completo, listagem, edição; cadastro rápido via modal na criação de OS (AJAX).

### Estoque (técnico)

- Catálogo, entrada, saldo por usuário; na OS, lançamento com validação de saldo. Se a quantidade exceder o disponível, a API responde com `code: saldo_insuficiente` até o usuário confirmar; com `confirmar_saldo_negativo=1` o saldo pode ficar negativo e a movimentação/OS recebem o prefixo `[Saldo negativo autorizado]` na observação. Opcional no catálogo: **quantidade inicial** gera entrada automática no estoque do técnico.

### Usuários e perfil

- Listagem/criação de usuários (conforme permissões), perfil, token Telegram (vinculação).

### DataTables

- Tabelas de listagem usam **DataTables** com arquivos **locais** em `assets/js/vendor/` e `assets/css/vendor/jquery.dataTables.min.css`, mais `assets/js/datatables-init.js` e `assets/css/datatables-custom.css`.
- Classes `table-datatable` e atributos `data-dt-*` nas views; idioma pt-BR no init.

---

## 9. Frontend e assets

| Recurso | Local |
|---------|--------|
| Estilo principal | `assets/css/app.css` |
| jQuery | `assets/js/vendor/jquery-3.7.1.min.js` |
| DataTables | `assets/js/vendor/jquery.dataTables.min.js` + CSS vendor |
| App JS | `assets/js/app.js` |
| Ordem de scripts no footer | jQuery → DataTables → `app.js` → `extraJs` da página → `datatables-init.js` |

Páginas podem definir `$extraCss` / `$extraJs` no PHP antes do layout.

---

## 10. Telegram

- **Webhook:** `TelegramWebhookController` — comandos (ex.: criar OS, consultar), idempotência por `update_id`.
- **Fila:** notificações em tabela de fila; processamento via cron (`TelegramCronController` / endpoint configurado).
- **Configuração:** `@config/telegram.php`, `@config/telegram_cron_secure.php` para chave do cron se aplicável.

Detalhes de URLs exatas dependem dos arquivos na raiz (`telegram_webhook.php`, `cron_telegram_api.php`) apontando para o bootstrap correto.

---

## 11. Pontos de entrada (entry points)

| Arquivo | Uso |
|---------|-----|
| `index.php` | Aplicação web principal (rotas) |
| `patch.php` | Aplicar/migrar banco (conforme implementação) |
| `telegram_webhook.php` | Receber updates do Telegram |
| `cron_telegram_api.php` | Processar fila (cron HTTP) |

---

## 12. Convenções do projeto

1. **PHP:** MVC; views em `@views/`; sem `console.log` em JS (regra do projeto).
2. **Layout:** com `header.php` e `footer.php`, apenas o **footer** deve ficar **antes** de blocos `<script>` externos (scripts já concentrados no footer).
3. **Patches de banco:** nova versão na sequência da pasta `version_XX`; atualizar `main_db.sql` / `db/main.sql` quando o schema mudar.
4. **DataTables / libs:** preferir arquivos físicos em `assets/.../vendor/`, sem CDN para essas dependências.
5. **`DB_VERSION`:** controle manual pelo administrador do projeto.
6. **Menu lateral:** alterar visibilidade por perfil preferencialmente via tabelas `nav_menu_*` (e novo patch se mudar estrutura); não duplicar listas fixas de links no `header.php`.

---

## 13. Menu lateral (banco de dados)

O menu da barra lateral **não** é mais uma lista fixa no PHP: os itens vêm das tabelas **`nav_menu_itens`** e **`nav_menu_item_perfis`**, carregados por `@core/NavMenu.php` e renderizados em `@views/layout/header.php`.

### Objetivo

- **Admin** vê as seções **Administração** (painel admin, catálogo de estoque, usuários, planos, relatórios, configurações) e **Conta** (perfil, sair), além do **Dashboard** em Principal.
- **Técnico** vê **Principal** (dashboard, OS, SLA) e **Gestão** (cadastro SLA, clientes, “Catálogo de peças” e “Meu estoque” quando `estoque_ativo` no usuário), mais **Conta** — **sem** o bloco Administração.
- **Cliente** vê fluxo reduzido (dashboard, OS, SLA, conta), conforme linhas em `nav_menu_item_perfis`.

Quem pode **acessar a URL** continua sendo definido nos **controllers** (`Auth::requireAdmin`, `requirePerfilTecnico`, etc.); o menu só reflete o que o perfil deve ver.

### Tabelas

| Tabela | Função |
|--------|--------|
| `nav_menu_itens` | Cada linha: `section_code` (ex.: `principal`, `gestao`, `administracao`, `conta`), `label`, `icon_class` (Font Awesome), `url_path`, `sort_order`, `ativo`, `requer_estoque_ativo` (1 = só aparece se o técnico tiver estoque ativo), `item_class` (ex.: logout), `active_rule` (chave lógica para destacar o item ativo no PHP). |
| `nav_menu_item_perfis` | Par (`menu_item_id`, `perfil`) — define em quais perfis o item aparece. |

### `active_rule`

Valores usados no código (`NavMenu::itemEstaAtivo`) incluem, entre outros: `dashboard`, `ordens_index`, `ordens_criar`, `sla_painel`, `sla_cadastro`, `admin_hub`, `estoque_tecnico`, `estoque_catalogo` (página `/estoque/catalogo` não deve acender o item “Meu estoque”; a regra `estoque_tecnico` exclui esse caminho). A comparação usa `SCRIPT_NAME` e rotas conhecidas para marcar o item atual. Ao criar nova rota de menu, alinhe `url_path` com as rotas em `index.php` e, se precisar de highlight correto, adicione regra em `NavMenu` ou reutilize uma `active_rule` existente.

### Manutenção (sem mudar estrutura de tabela)

1. Inserir ou atualizar linhas em `nav_menu_itens` (rótulo, URL, ordem, `ativo`).
2. Inserir/remover em `nav_menu_item_perfis` para ligar item ↔ `admin` / `tecnico` / `cliente`.
3. Espelhar seeds em `@config/main_db.sql` e `db/main.sql` **se** quiser manter installs from-scratch idênticos; para só um ambiente, SQL direto no MySQL basta.

### Mudança estrutural (nova coluna, nova tabela)

Seguir a regra de patches: novo `patch_XX_YYYY` + `version_YY.sql` e atualização dos arquivos **main** de schema.

---

## Manutenção desta pasta

- Atualize este documento quando adicionar módulos, rotas relevantes ou mudanças de deploy.
- Para detalhes de um patch específico, consulte também `@config/patch/README.md` (se existir) e os arquivos SQL correspondentes.

---

*Documentação gerada para o projeto **OS Manager** — estrutura e convenções vigentes na árvore de código.*
