## Integração com Telegram Bot – MesFee

Este documento resume **como o MesFee integra com o Telegram** usando **webhook** e as principais URLs da API do Telegram.

> Atenção: nunca exponha o *token real* do bot em lugares públicos.  
> Nos exemplos abaixo, substitua `SEU_BOT_TOKEN_AQUI` pelo token correto quando for usar manualmente.

---

### 1. URLs principais do bot atual

Estas URLs já estão preparadas (com o token atual) e foram usadas para configurar o webhook no ambiente de produção:

- **Definir webhook (produção)**  
  `https://api.telegram.org/bot7999839019:AAFM-nGBn98wVuD5BoAxl44Sco_dUiLoHMo/setWebhook?url=https://mesfee.mcmsoftwares.com.br/telegram/webhook`

- **Verificar status do webhook (produção)**  
  `https://api.telegram.org/bot7999839019:AAFM-nGBn98wVuD5BoAxl44Sco_dUiLoHMo/getWebhookInfo`

No uso manual (terminal/navegador), a forma genérica é:

- **SetWebhook genérico**  
  `https://api.telegram.org/botSEU_BOT_TOKEN_AQUI/setWebhook?url=URL_DO_SEU_WEBHOOK`

- **GetWebhookInfo genérico**  
  `https://api.telegram.org/botSEU_BOT_TOKEN_AQUI/getWebhookInfo`

---

### 2. Como o webhook funciona

1. O MesFee expõe um endpoint:  
   `https://mesfee.mcmsoftwares.com.br/telegram/webhook`  
   (arquivo `telegram_webhook.php` aponta para o controller `TelegramWebhookController`).
2. O Telegram **envia todas as mensagens do bot** para esse endpoint em JSON (método `POST`).
3. O sistema processa o conteúdo recebido (mensagem, chat, usuário etc.) e executa as ações necessárias.
4. Opcionalmente, o sistema responde ao usuário usando a API `sendMessage` do Telegram.

---

### 3. Passo a passo – configurando o webhook

1. **Criar/obter o bot**  
   - No Telegram, abra o `@BotFather`.  
   - Use `/newbot` para criar um novo bot ou `/token` para recuperar o token de um bot existente.

2. **Definir o webhook**  
   - Acesse no navegador (ou via `curl`) a URL:
     - Produção (já usada):  
       `https://api.telegram.org/bot7999839019:AAFM-nGBn98wVuD5BoAxl44Sco_dUiLoHMo/setWebhook?url=https://mesfee.mcmsoftwares.com.br/telegram/webhook`
     - Ou no formato genérico:  
       `https://api.telegram.org/botSEU_BOT_TOKEN_AQUI/setWebhook?url=URL_DO_SEU_WEBHOOK`
   - A resposta esperada é algo como:
     - `{"ok":true,"result":true,"description":"Webhook was set"}`.

3. **Confirmar se está tudo certo**  
   - Acesse:
     - Produção:  
       `https://api.telegram.org/bot7999839019:AAFM-nGBn98wVuD5BoAxl44Sco_dUiLoHMo/getWebhookInfo`
     - Genérico:  
       `https://api.telegram.org/botSEU_BOT_TOKEN_AQUI/getWebhookInfo`
   - Verifique:
     - `url` (deve ser `https://mesfee.mcmsoftwares.com.br/telegram/webhook`),  
     - `pending_update_count`,  
     - possíveis mensagens de erro em `last_error_message`.

---

### 4. Testando o webhook rapidamente

1. **Enviar mensagem de teste no Telegram**  
   - Abra o bot no Telegram (link do tipo `https://t.me/NOME_DO_SEU_BOT`).  
   - Envie uma mensagem simples, por exemplo: `teste`.

2. **Verificar log do sistema**  
   - Veja o arquivo `telegram_webhook.log` na raiz do projeto.  
   - Cada chamada do Telegram deve registrar o JSON recebido e o que foi processado.

3. **Conferir resposta ao usuário**  
   - Dependendo da lógica do `TelegramWebhookController`, o usuário deve receber uma mensagem de retorno (confirmação, menu, erro etc.).

---

### 5. Enviando mensagens ativas (do sistema para o Telegram)

Para disparar mensagens sem depender do usuário iniciar a conversa (por exemplo, notificações de mensalidade, agendamentos, etc.), o sistema usa a API do Telegram chamando endpoints como:

- **sendMessage**  
  `https://api.telegram.org/botSEU_BOT_TOKEN_AQUI/sendMessage`

Corpo (exemplo em JSON):

```json
{
  "chat_id": 123456789,
  "text": "Mensagem de teste do MesFee",
  "parse_mode": "HTML"
}
```

No código PHP, isso normalmente é feito pela classe `TelegramSender` chamando `curl` ou `file_get_contents` com `POST`/`GET`, de acordo com a implementação existente.

---

### 6. Cuidados importantes

- **Token do bot**  
  - Nunca comitar o token em repositórios públicos.  
  - Se precisar trocar o token, atualizar onde ele estiver configurado no projeto (por exemplo, em arquivos de configuração específicos de ambiente).

- **Segurança do webhook**  
  - Garantir que apenas o Telegram chame o webhook (pode-se validar IPs, cabeçalhos ou implementar uma camada de autenticação própria se necessário).

- **Ambientes (dev/homolog/prod)**  
  - Cada ambiente deve ter **seu próprio bot** ou pelo menos um webhook separado.  
  - Lembrar que **um bot só pode ter um webhook ativo por vez**. Se setar um novo webhook (por exemplo em ambiente de teste), o de produção será sobrescrito.

---

### 7. Referências rápidas da API Telegram

- **Documentação oficial**: `https://core.telegram.org/bots/api`  
- Endpoints básicos (sempre começando com `https://api.telegram.org/botSEU_BOT_TOKEN_AQUI/`):
  - `setWebhook`
  - `getWebhookInfo`
  - `deleteWebhook`
  - `sendMessage`
  - `sendPhoto`
  - `sendDocument`
  - `editMessageText`

Com isso, este arquivo serve como um **guia rápido** para configurar, testar e manter a integração do MesFee com o Telegram.