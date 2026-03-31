# Configuração Telegram — MESFEE (referência para outro projeto)

Este documento descreve **como o Telegram está configurado no MESFEE** (arquivos, constantes, rotas e fluxos), para você replicar a ideia em outro sistema. **Não** inclui segredos reais: use placeholders e defina tokens apenas em ambiente seguro.

---

## 1. Visão geral da arquitetura

| Peça | Função |
|------|--------|
| `@config/telegram.php` | Token do bot, URL da API, chat padrão opcional, modo de parse (HTML). Carregado pelo `config.php`. |
| `@core/TelegramSender.php` | Envio de mensagens (`sendMessage`), `getBotInfo`, configurar/remover webhook, substituição de variáveis em templates. |
| Webhook | Telegram envia updates em **POST** JSON para o servidor; o sistema associa usuários/alunos ao `chat_id`. |
| `@config/telegram_cron_secure.php` | Configuração **isolada** do endpoint HTTP de cron (não depende de `config.php` completo). |
| `cron_telegram_api.php` | Script CLI (ex.: cron do servidor) que faz **POST** autenticado ao endpoint público de processamento. |
| `index.php` (rotas) | Expõe `telegram/webhook`, `api/cron/telegram`, etc. |
| `telegram_webhook.php` | Entrada alternativa na raiz; em GET mostra teste; em POST carrega MVC e delega ao `TelegramWebhookController`. |

Permissões e dados por usuário (ex.: `telegram_ativo`, `telegram_chat_id`) ficam no **banco** e na aplicação (`PermissionHelper`, telas de configuração), não neste arquivo de constantes.

---

## 2. Carregamento global (`@config/config.php`)

O `config.php` inclui explicitamente:

```php
require_once __DIR__ . '/telegram.php';
```

Assim, qualquer código que carregue `config.php` já tem `TELEGRAM_BOT_TOKEN`, `TELEGRAM_API_URL`, `TELEGRAM_DEFAULT_CHAT_ID` e `TELEGRAM_PARSE_MODE` definidos (se o arquivo existir).

---

## 3. `@config/telegram.php` — constantes do bot

| Constante | Descrição |
|-----------|-----------|
| `TELEGRAM_BOT_TOKEN` | Token do @BotFather (`número:hash`). Obrigatório para envio e webhook. |
| `TELEGRAM_DEFAULT_CHAT_ID` | Opcional; string vazia se cada usuário tiver o próprio `chat_id` no banco. |
| `TELEGRAM_API_URL` | Padrão: `https://api.telegram.org/bot` (prefixo antes do token nas chamadas). |
| `TELEGRAM_PARSE_MODE` | `HTML` ou `Markdown` — alinhado ao que o `TelegramSender` envia nas requisições. |

Comentários no próprio arquivo explicam: criar bot no @BotFather, obter chat id com @userinfobot (pessoas) ou bots em grupo (IDs negativos).

**Para outro projeto:** copie o padrão de constantes ou mova valores para variáveis de ambiente e faça `define()` a partir delas, sem versionar segredos.

---

## 4. `@config/telegram_cron_secure.php` — API de cron (fila / envios)

Arquivo pensado para ser `require` antes do runner, **sem** puxar sessão ou banco só por estar no `config.php`.

| Constante / função | Descrição |
|--------------------|-----------|
| `TELEGRAM_CRON_API_ENABLED` | Liga/desliga o endpoint HTTP. |
| `TELEGRAM_CRON_API_TOKEN` | Token longo (Bearer + header alternativo `X-CRON-TOKEN` no cliente CLI). |
| `TELEGRAM_CRON_API_REQUIRE_HTTPS` | Se `true`, o controller só aceita HTTPS. |
| `TELEGRAM_CRON_API_URL` | URL pública do endpoint, ex.: `https://dominio/app/api/cron/telegram`. |
| `TELEGRAM_CRON_API_MIN_INTERVAL_SECONDS` | Anti-flood entre execuções bem-sucedidas. |
| `TELEGRAM_CRON_API_MAX_BODY_BYTES` | Limite de corpo da requisição. |
| `NOTIFICACAO_FILA_BATCH_LIMIT` | Tamanho do lote na fila. |
| `NOTIFICACAO_FILA_RETENCAO_HORAS` | Retenção de registros na fila. |
| `TELEGRAM_CRON_API_ALLOWED_IPS` | Lista opcional de IPs (vírgula); vazio = sem restrição por IP. |
| `telegramCronApiAllowedIps()` | Normaliza e valida IPs permitidos. |

**Para outro projeto:** replique o mesmo arquivo com novos tokens e URL; agende o `cron_telegram_api.php` (ou equivalente) no servidor.

---

## 5. `cron_telegram_api.php` (raiz do projeto)

- Faz `require` de `@config/telegram_cron_secure.php`.
- Lê `TELEGRAM_CRON_API_URL` e `TELEGRAM_CRON_API_TOKEN`.
- Exige token com **pelo menos 64 caracteres** (validação no script).
- Envia **POST** com corpo `{}` e cabeçalhos `Authorization: Bearer …` e `X-CRON-TOKEN: …`.
- Uso típico no painel de hospedagem:  
  `php /caminho/para/mesfee/cron_telegram_api.php`

---

## 6. Rotas relevantes (`index.php`)

- **`telegram/webhook`** (e variações com `telegram_webhook.php`): processamento do webhook → `TelegramWebhookController::webhook()`.
- **`api/cron/telegram`**: `TelegramCronController::processar()` — processamento seguro (HTTPS, Bearer, intervalo, IP opcional).
- **`api/cron/telegram/ping`**: ping do serviço de cron.
- Outras rotas `api/cron/fila/*` relacionadas à fila de envios.

O front controller trata o webhook **antes** das rotas autenticadas, pois o Telegram não envia cookie de sessão.

---

## 7. Webhook — URLs para a API do Telegram

Formato oficial (substitua placeholders):

- Definir webhook:  
  `https://api.telegram.org/bot<TOKEN>/setWebhook?url=<HTTPS_URL_DO_WEBHOOK>`
- Consultar:  
  `https://api.telegram.org/bot<TOKEN>/getWebhookInfo`

No MESFEE, a URL do webhook costuma ser:

- `{BASE_URL}/telegram/webhook`  
  onde `BASE_URL` é calculado em `config.php` a partir do host e do subdiretório da aplicação.

Alternativa documentada no código: acesso direto a `telegram_webhook.php` na raiz (útil se o rewrite não apontar tudo para `index.php`).

**HTTPS:** a API do Telegram exige URL **https** em produção para `setWebhook`.

Log local (quando usar `telegram_webhook.php`): arquivo `telegram_webhook.log` na raiz do projeto.

---

## 8. `TelegramSender` (`@core/TelegramSender.php`)

Responsabilidades principais:

- Montar chamadas cURL à API (`sendMessage`, etc.).
- Ler `TELEGRAM_BOT_TOKEN`, `TELEGRAM_API_URL`, `TELEGRAM_PARSE_MODE`.
- Métodos úteis: `enviar($chatId, $mensagem)`, `getBotInfo()`, `configurarWebhook($url)`, `getWebhookInfo()`, `removerWebhook()`, tratamento de erros comuns da API (token inválido, usuário bloqueou o bot, etc.).

**Para outro projeto:** pode portar só esta classe + `telegram.php`, ou reimplementar com HTTP client da sua stack.

---

## 9. Interface administrativa (resumo)

Na área de configurações (`ConfigController` + views):

- Usuários com permissão `telegram` configuram ativação, `chat_id`, templates de mensagens e teste de envio.
- Administrador pode configurar/registrar o webhook via ações que usam `TelegramSender::configurarWebhook` com `{BASE_URL}/telegram/webhook`.

Detalhes de regras de negócio e campos de banco não são repetidos aqui; consulte modelos `User` / `Aluno` e permissões.

---

## 10. Segurança — checklist ao levar para outro projeto

1. **Não** commitar `TELEGRAM_BOT_TOKEN` nem `TELEGRAM_CRON_API_TOKEN` em repositório público; rotacione se já vazou.
2. Definir webhook apenas em ambiente com **HTTPS** válido.
3. Manter o token do cron longo e exclusivo; restringir por **IP** se o cron vier de IP fixo.
4. Tratar o endpoint `/api/cron/telegram` como **segredo** (mesmo nível de uma API key).

---

## 11. Documentação adicional no repositório

- `Telegram_docs.md` (raiz): notas operacionais sobre webhook e testes. **Cuidado:** versões antigas podem conter URLs com token embutido — prefira sempre o formato genérico da seção 7 deste arquivo.

---

*Última atualização estrutural: alinhado ao carregamento em `@config/config.php`, `@config/telegram.php`, `@config/telegram_cron_secure.php` e rotas em `index.php`.*
