# Sistema de Patches - MESFEE

Este documento define o padrao oficial de patches de banco para o projeto.
Objetivo: garantir que IA e desenvolvedor criem patches sempre no formato correto.

## Regras obrigatorias

1. Patch e somente para alteracao de banco de dados:
   - tabelas
   - colunas
   - indices
   - constraints
   - triggers
   - views
   - procedures/functions
2. Nao criar patch para alteracoes de pagina, layout, controller, model, js, css ou rotas.
3. Cada novo patch deve gerar exatamente 2 arquivos:
   - `@config/patch/version_01/patch_01_NNNN.php`
   - `@config/patch/sql/version_NN.sql`
4. Todo patch novo deve usar o patch anterior como referencia de estrutura.
5. Sempre atualizar `@config/main_db.sql` junto com o patch.
6. Nao alterar `DB_VERSION` automaticamente. Esse ajuste e manual.

## Estado atual da estrutura

- `DB_VERSION` atual em `@config/config.php`: `1`
- Pasta ativa de patches PHP: `@config/patch/version_01/`
- Padrao de nome PHP: `patch_01_NNNN.php`
- Sequencia PHP existente: `patch_01_0001.php` ate `patch_01_0040.php`
- Pasta SQL: `@config/patch/sql/`
- Padrao de nome SQL: `version_NN.sql`
- Maior SQL atual: `version_40.sql`

Observacao: podem existir lacunas de numeracao em SQL por historico do projeto. Antes de criar novo patch, sempre confira a ultima numeracao existente.

## Aplicacao de patches (UI com senha)

O projeto possui um executor web em `patch.php`:

- URL: `http://localhost/mesfee/patch.php`
- Acesso protegido por senha (constante `PATCH_PASSWORD` em `patch.php`)
- A sessao de autenticacao usa `$_SESSION['patch_authenticated']`
- Permite aplicar patch unico, aplicar todos os pendentes e migrar legados

Recomendacao de seguranca:

1. Alterar `PATCH_PASSWORD` para um valor forte em ambiente real.
2. Restringir acesso ao `patch.php` por rede/IP quando possivel.
3. Nunca expor senha de patch em documentacao publica.

## Tabela de controle de aplicacao

O `patch.php` cria automaticamente a tabela `patches_applied` para rastreamento:

- `patch_file` (UNIQUE): nome do arquivo do patch (`patch_01_NNNN.php`)
- `patch_number`: numero sequencial extraido do arquivo
- `status`: `success` ou `error`
- `output`: log de execucao
- `applied_at`: data/hora de aplicacao

Importante:

- O rastreamento em `patches_applied` **nao substitui** idempotencia no patch.
- Cada patch deve continuar seguro para reexecucao (checando existencia antes de alterar).

## Tabelas de autenticacao/senha impactadas por patches

Campos principais ja adicionados por patches anteriores:

- `usuarios`: `senha`, `token`, `token_expira`
- `alunos`: `senha`, `token`, `token_expira`, `login_ativo`, `codigo_recuperacao`, `codigo_recuperacao_expira`

Ao criar novo patch de autenticacao:

1. Atualizar o patch PHP com verificacoes de existencia em `INFORMATION_SCHEMA`.
2. Criar SQL correspondente em `@config/patch/sql/version_NN.sql`.
3. Refletir alteracoes no `@config/main_db.sql`.

## Estrutura de diretorios

```
@config/patch/
├── README.md
├── sql/
│   ├── version_01.sql
│   ├── ...
│   └── version_40.sql
└── version_01/
    ├── patch_01_0001.php
    ├── ...
    └── patch_01_0040.php
```

## Como criar um novo patch corretamente

Exemplo: ultimo patch atual = `patch_01_0040.php`.
Proximo patch obrigatorio:

- PHP: `@config/patch/version_01/patch_01_0041.php`
- SQL: `@config/patch/sql/version_41.sql`

### Passo a passo

1. Identificar o ultimo patch PHP em `version_01/`.
2. Criar o proximo arquivo com sequencia `NNNN` (4 digitos).
3. Copiar a estrutura do patch anterior (cabecalho, require, try/catch, idempotencia).
4. Implementar SQL de forma idempotente (verificar existencia antes de criar/alterar quando aplicavel).
5. Criar o SQL correspondente em `sql/version_NN.sql`.
6. Atualizar `@config/main_db.sql` para refletir o estado final do schema.
7. Executar o patch em ambiente local e validar.
8. Registrar aplicacao via `patch.php` quando o fluxo usado for web (tabela `patches_applied`).

## Template recomendado - Patch PHP

```php
<?php
/**
 * Patch 01_00XX - Descricao
 * Data: YYYY-MM-DD
 * Versao: 1
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
        echo "Executando Patch 01_00XX...\n";
    }

    // Verificacao de idempotencia antes de aplicar alteracao
    // Exemplo: checar tabela/coluna/indice/view existente

    if (!$isCheckMode) {
        echo "\nPatch 01_00XX executado com sucesso!\n";
    }
} catch (Exception $e) {
    echo "Erro ao executar Patch 01_00XX: " . $e->getMessage() . "\n";
    error_log("Erro Patch 01_00XX: " . $e->getMessage());
    exit(1);
}
```

## Template recomendado - SQL

```sql
-- ============================================================
-- Patch 01_00XX - Descricao
-- Data: YYYY-MM-DD
-- Versao: 1
-- ============================================================

-- Instrucoes SQL correspondentes ao patch PHP
-- Preferir IF NOT EXISTS quando suportado
```

## Execucao manual de um patch

Via terminal (Windows):

```bash
cd C:\xampp\htdocs\mesfee
C:\xampp\php\php.exe @config/patch/version_01/patch_01_0041.php
```

## Checklist rapido antes de finalizar

- [ ] Alteracao e realmente de banco de dados.
- [ ] Arquivo PHP criado com proxima sequencia `patch_01_NNNN.php`.
- [ ] Arquivo SQL criado com proxima sequencia `version_NN.sql`.
- [ ] Estrutura baseada no patch anterior.
- [ ] `@config/main_db.sql` atualizado.
- [ ] Execucao local validada sem erro.
