# Evolution GO API

Documentacao da API Evolution GO usada para integracao WhatsApp do GarageON.

Fonte: `http://localhost:8080/swagger/index.html` (`/swagger/doc.json`).

## Objetivo

Integrar cada loja com uma instancia WhatsApp isolada para:

- criar e conectar instancia;
- gerar QR Code ou pareamento;
- enviar mensagens;
- receber eventos por webhook;
- manter conversas e mensagens sincronizadas no tenant correto.

## Configuracao

No `.env`:

```bash
EVOLUTION_GO_URL=http://evolution-go:8080
EVOLUTION_GO_API_KEY=
EVOLUTION_GO_TIMEOUT=12
EVOLUTION_GO_PORT=8080
EVOLUTION_GO_WEBHOOK_BASE_URL=http://nginx
```

No `docker-compose.yml`, a API roda no servico `evolution-go` e e publicada localmente em `http://localhost:${EVOLUTION_GO_PORT:-8080}`.

### Webhook precisa de URL interna

O `APP_URL` publico (ex.: `http://localhost:8001`) NAO e alcancavel de dentro do
container `evolution-go`: para ele, `localhost` e o proprio container. Por isso o
GarageON monta a URL de webhook a partir de `EVOLUTION_GO_WEBHOOK_BASE_URL`
(ex.: `http://nginx`, servico HTTP na mesma rede Docker), preservando o path da
rota `evolution.webhook`. Sem essa variavel, a Evolution tentaria o `APP_URL` e a
entrega de eventos (mensagens recebidas, `Connected`, `PairSuccess`) falharia com
`connection refused`.

A identificacao da instancia nas chamadas por instancia e feita enviando o
`token` da instancia no header `apikey` (nao a chave global + `instanceId`). A
chave global so autentica criacao/listagem de instancias.

Telefones sao normalizados para E.164 do Brasil (com DDI `55`) antes de enviar e
ao casar contatos recebidos, para manter envio e `contact_phone` consistentes com
os JIDs `55DDD...` entregues pela Evolution.

## Autenticacao

Todas as chamadas feitas pelo GarageON usam headers HTTP:

```http
apikey: <EVOLUTION_GO_API_KEY>
instanceId: <whatsapp_connections.instance_id>
Accept: application/json
Content-Type: application/json
```

Regras:

- `apikey` e obrigatorio para autenticar na Evolution GO.
- `instanceId` e usado nas chamadas que operam uma instancia especifica.
- Criacao/listagem de instancias pode usar apenas `apikey`.
- Nunca hardcode a chave; use `EVOLUTION_GO_API_KEY`.

## Fluxo Recomendado No GarageON

1. Criar ou reutilizar uma instancia por tenant com `POST /instance/create`.
2. Salvar `id`, `name` e `token` em `whatsapp_connections`.
3. Conectar com `POST /instance/connect`, informando `webhookUrl` e eventos.
4. Buscar QR Code com `GET /instance/qr` quando a instancia nao estiver conectada.
5. Confirmar estado com `GET /instance/status`.
6. Enviar mensagens com `POST /send/text` ou endpoints equivalentes.
7. Receber eventos em `POST /api/evolution/webhook/{secret}`.

Eventos usados hoje:

```json
["MESSAGE", "SEND_MESSAGE", "CONNECTION", "QRCODE", "READ_RECEIPT"]
```

## Webhook GarageON

Rota interna:

```http
POST /api/evolution/webhook/{secret}
```

O `{secret}` vem de `whatsapp_connections.webhook_secret`.

Eventos tratados hoje pelo `WhatsappWebhookIngestor`:

- `QRCode`: status `qrcode` (QR e efemero, nunca persistido).
- `Connected`, `PairSuccess`, `OfflineSyncCompleted`: marca instancia como `connected`.
- `LoggedOut`: marca instancia como `disconnected`.
- `Message`, `SendMessage`: cria/atualiza conversa e mensagem.

O casamento e case-insensitive e tolerante a variacoes (ex.: `CONNECTED`,
`connection.update`, `logged_out`): o ingestor normaliza o nome antes de rotear,
para uma mudanca de formatacao da Evolution nao deixar o status preso em
`connecting`.

Exemplo de payload recebido:

```json
{
  "event": "Message",
  "instanceId": "instance-1",
  "data": {
    "Info": {
      "Chat": "5511977771001@s.whatsapp.net",
      "Sender": "5511977771001:38@s.whatsapp.net",
      "IsFromMe": false,
      "ID": "IN-1",
      "Type": "text",
      "PushName": "Rafael",
      "Timestamp": "2026-07-05T10:00:00-03:00"
    },
    "Message": {
      "conversation": "Quero agendar a manutencao da vitrificacao."
    }
  }
}
```

## Endpoints Principais

### Instance

| Metodo | Rota | Uso |
| --- | --- | --- |
| `GET` | `/instance/all` | Lista todas as instancias. |
| `POST` | `/instance/create` | Cria uma instancia. |
| `POST` | `/instance/connect` | Inicia conexao da instancia. |
| `GET` | `/instance/qr` | Retorna QR Code da instancia atual. |
| `GET` | `/instance/status` | Retorna status da instancia atual. |
| `POST` | `/instance/reconnect` | Reconecta instancia atual. |
| `DELETE` | `/instance/logout` | Faz logout da instancia atual. |
| `POST` | `/instance/disconnect` | Desconecta instancia atual. |
| `GET` | `/instance/info/{instanceId}` | Consulta instancia por ID. |
| `DELETE` | `/instance/delete/{instanceId}` | Remove instancia por ID. |
| `POST` | `/instance/pair` | Solicita codigo de pareamento. |
| `POST` | `/instance/forcereconnect/{instanceId}` | Forca reconexao. |
| `GET` | `/instance/logs/{instanceId}` | Lista logs com filtros. |
| `GET` | `/instance/{instanceId}/advanced-settings` | Consulta configuracoes avancadas. |
| `PUT` | `/instance/{instanceId}/advanced-settings` | Atualiza configuracoes avancadas. |
| `POST` | `/instance/proxy/{instanceId}` | Define proxy. |
| `DELETE` | `/instance/proxy/{instanceId}` | Remove proxy. |

Payload de criacao:

```json
{
  "name": "garageon-carbon-studio",
  "token": "uuid-ou-token-interno",
  "advancedSettings": {
    "alwaysOnline": true,
    "readMessages": false,
    "rejectCall": false,
    "ignoreGroups": true,
    "ignoreStatus": true
  }
}
```

Payload de conexao:

```json
{
  "webhookUrl": "https://app.garageon.com.br/api/evolution/webhook/secret",
  "subscribe": ["MESSAGE", "SEND_MESSAGE", "CONNECTION", "QRCODE", "READ_RECEIPT"],
  "immediate": true,
  "phone": "5511999999999"
}
```

`phone` e opcional. Quando informado, use numero normalizado em E.164 sem `+`.

### Send Message

| Metodo | Rota | Uso |
| --- | --- | --- |
| `POST` | `/send/text` | Envia texto. |
| `POST` | `/send/media` | Envia midia por URL. |
| `POST` | `/send/link` | Envia link com preview. |
| `POST` | `/send/contact` | Envia contato/vCard. |
| `POST` | `/send/location` | Envia localizacao. |
| `POST` | `/send/button` | Envia mensagem com botoes. |
| `POST` | `/send/list` | Envia lista interativa. |
| `POST` | `/send/poll` | Envia enquete. |
| `POST` | `/send/sticker` | Envia sticker. |
| `POST` | `/send/carousel` | Envia carrossel. |
| `POST` | `/send/status/text` | Publica status de texto. |
| `POST` | `/send/status/media` | Publica status de midia por multipart/form-data. |

Texto:

```json
{
  "number": "5511955553003",
  "text": "Bruno, quer reservar um horario para avaliarmos o Q3?",
  "delay": 0
}
```

Midia:

```json
{
  "number": "5511955553003",
  "type": "image",
  "url": "https://cdn.exemplo.com/foto.jpg",
  "caption": "Resultado do polimento",
  "filename": "foto.jpg"
}
```

Link:

```json
{
  "number": "5511955553003",
  "url": "https://garageon.com.br/loja/carbon-studio",
  "title": "Agende seu horario",
  "description": "Veja pacotes e horarios disponiveis",
  "text": "Segue o link da loja"
}
```

Campos comuns de envio:

- `number`: telefone destino.
- `text`: corpo da mensagem de texto.
- `delay`: atraso de digitacao em milissegundos.
- `quoted`: contexto de resposta.
- `formatJid`: quando `false`, pula formatacao automatica do destino.
- `mentionedJid`: lista de JIDs mencionados.
- `mentionAll`: menciona todos em grupos.

### Message

| Metodo | Rota | Uso |
| --- | --- | --- |
| `POST` | `/message/delete` | Apaga mensagem para todos. |
| `POST` | `/message/edit` | Edita mensagem. |
| `POST` | `/message/react` | Reage a mensagem. |
| `POST` | `/message/markread` | Marca mensagens como lidas. |
| `POST` | `/message/markplayed` | Marca audio como reproduzido. |
| `POST` | `/message/presence` | Define presenca no chat. |
| `POST` | `/message/status` | Consulta status de mensagem. |
| `POST` | `/message/downloadmedia` | Baixa midia de uma mensagem. |

Marcar como lida:

```json
{
  "number": "5511955553003",
  "id": ["MSG-ID-1", "MSG-ID-2"]
}
```

Reagir:

```json
{
  "number": "5511955553003",
  "id": "MSG-ID-1",
  "reaction": "+1",
  "fromMe": false
}
```

### User

| Metodo | Rota | Uso |
| --- | --- | --- |
| `POST` | `/user/check` | Verifica numeros no WhatsApp. |
| `POST` | `/user/info` | Consulta usuario. |
| `POST` | `/user/avatar` | Consulta avatar. |
| `GET` | `/user/contacts` | Lista contatos. |
| `GET` | `/user/blocklist` | Lista bloqueados. |
| `POST` | `/user/block` | Bloqueia contato. |
| `POST` | `/user/unblock` | Desbloqueia contato. |
| `GET` | `/user/privacy` | Consulta privacidade. |
| `POST` | `/user/privacy` | Atualiza privacidade. |
| `POST` | `/user/profileName` | Atualiza nome do perfil. |
| `POST` | `/user/profilePicture` | Atualiza foto do perfil. |
| `POST` | `/user/profileStatus` | Atualiza recado/status do perfil. |

Verificar numeros:

```json
{
  "number": ["5511955553003", "5511977771001"],
  "formatJid": true
}
```

### Chat

| Metodo | Rota | Uso |
| --- | --- | --- |
| `POST` | `/chat/archive` | Arquiva chat. |
| `POST` | `/chat/unarchive` | Desarquiva chat. |
| `POST` | `/chat/mute` | Silencia chat. |
| `POST` | `/chat/unmute` | Remove silencio. |
| `POST` | `/chat/pin` | Fixa chat. |
| `POST` | `/chat/unpin` | Desfixa chat. |
| `POST` | `/chat/history-sync` | Solicita sincronizacao de historico. |

Payload basico:

```json
{
  "chat": "5511955553003@s.whatsapp.net"
}
```

## Outros Endpoints Do Swagger

### Group

- `POST /group/create`
- `POST /group/description`
- `POST /group/info`
- `POST /group/invitelink`
- `POST /group/join`
- `POST /group/leave`
- `GET /group/list`
- `GET /group/myall`
- `POST /group/name`
- `POST /group/participant`
- `POST /group/photo`
- `POST /group/settings`

### Label

- `GET /label/list`
- `POST /label/chat`
- `POST /label/message`
- `POST /label/edit`
- `POST /unlabel/chat`
- `POST /unlabel/message`

### Newsletter

- `POST /newsletter/create`
- `POST /newsletter/info`
- `POST /newsletter/link`
- `GET /newsletter/list`
- `POST /newsletter/messages`
- `POST /newsletter/subscribe`

### Community

- `POST /community/create`
- `POST /community/add`
- `POST /community/remove`

### Call

- `POST /call/reject`

### Polls

- `GET /polls/{pollMessageId}/results`

### Passkey

- `GET /passkey-ceremony/{token}`
- `POST /passkey-ceremony/{token}/response`
- `POST /passkey-ceremony/{token}/confirm`

### License

- `GET /license/status`
- `GET /license/register?redirect_uri=...`
- `GET /license/activate?code=...`

## CURLs De Referencia

Criar instancia:

```bash
curl -X POST "$EVOLUTION_GO_URL/instance/create" \
  -H "apikey: $EVOLUTION_GO_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"name":"garageon-carbon-studio","token":"token-interno"}'
```

Conectar instancia:

```bash
curl -X POST "$EVOLUTION_GO_URL/instance/connect" \
  -H "apikey: $EVOLUTION_GO_API_KEY" \
  -H "instanceId: $INSTANCE_ID" \
  -H "Content-Type: application/json" \
  -d '{"webhookUrl":"https://app.garageon.com.br/api/evolution/webhook/secret","subscribe":["MESSAGE","SEND_MESSAGE","CONNECTION","QRCODE","READ_RECEIPT"],"immediate":true}'
```

Enviar texto:

```bash
curl -X POST "$EVOLUTION_GO_URL/send/text" \
  -H "apikey: $EVOLUTION_GO_API_KEY" \
  -H "instanceId: $INSTANCE_ID" \
  -H "Content-Type: application/json" \
  -d '{"number":"5511955553003","text":"Mensagem de teste","delay":0}'
```

Consultar QR Code:

```bash
curl -X GET "$EVOLUTION_GO_URL/instance/qr" \
  -H "apikey: $EVOLUTION_GO_API_KEY" \
  -H "instanceId: $INSTANCE_ID"
```

## Cuidados De Producao

- Uma instancia deve pertencer a um unico tenant.
- O `webhook_secret` precisa ser unico e nao previsivel.
- Nao exponha `EVOLUTION_GO_API_KEY`, `instance_token` ou payloads sensiveis em views publicas.
- Normalize telefones antes de enviar para a API.
- Persista payload bruto de webhook apenas em campos protegidos do tenant.
- Trate falhas da Evolution como indisponibilidade externa, sem bloquear a operacao principal da oficina.
