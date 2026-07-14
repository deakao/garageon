# Atendente Virtual (Piloto Automático)

Atendente virtual por tenant que responde clientes no WhatsApp e agenda serviços
automaticamente, usando LLM (OpenAI, Anthropic ou Gemini) com ferramentas de
agendamento da própria loja.

## Objetivo

Cada loja pode ligar um "piloto automático" que:

- responde mensagens recebidas no WhatsApp em nome da loja;
- consulta serviços e preços da loja;
- consulta horários livres e cria agendamentos reais;
- agenda follow-ups dinâmicos quando o cliente pede para retomar a conversa depois;
- respeita um tom de voz e um contexto configurados pelo lojista;
- opera dentro de um limite diário de respostas (por plano) ou de forma
  ilimitada quando o lojista traz a própria chave de IA.

## Como funciona (fluxo)

```
Cliente manda msg no WhatsApp
      │
      ▼
Evolution → webhook  (POST /api/evolution/webhook/{secret})
      │
      ▼
WhatsappWebhookIngestor::recordMessage()   grava a msg inbound
      │  (se msg de texto, não é da loja, e atendente operacional)
      ▼
dispatch RespondWithAttendant  (fila)
      │
      ▼
RespondWithAttendant::handle()
      ├─ checa limite diário (só se usa chave da plataforma)
      ├─ injeta a API key correta no provider
      ├─ StoreAttendant (Laravel AI SDK) gera a resposta usando as tools
      ├─ EvolutionGoClient::sendText() envia a resposta
      └─ grava a msg outbound + registra consumo do dia
```

O processamento roda em fila (`QUEUE_CONNECTION=database`) para não travar o
webhook nem estourar timeout com a latência do LLM.

## Componentes

| Papel | Arquivo |
|---|---|
| Configuração por tenant | `app/Models/VirtualAttendant.php` |
| Tom de voz | `app/Enums/AttendantTone.php` |
| Provedor de IA | `app/Enums/AttendantProvider.php` |
| System prompt | `app/Services/AttendantPromptBuilder.php` |
| Agent (LLM + tools) | `app/Ai/Agents/StoreAttendant.php` |
| Tool: serviços | `app/Ai/Tools/ListServices.php` |
| Tool: disponibilidade | `app/Ai/Tools/CheckAvailability.php` |
| Tool: agendar | `app/Ai/Tools/BookAppointment.php` |
| Tool: follow-up | `app/Ai/Tools/ScheduleFollowUp.php` |
| Disponibilidade/agenda | `app/Services/BookingAvailability.php` |
| Job de resposta | `app/Jobs/RespondWithAttendant.php` |
| Cota diária | `app/Services/AttendantUsage.php` |
| Gancho no webhook | `app/Services/WhatsappWebhookIngestor.php` |
| Tela de configuração | `resources/views/garageon/settings/attendant.blade.php` |
| Rotas | `routes/web.php` (grupo `settings.`, `settings.attendant*`) |

Depende do pacote oficial `laravel/ai` (v0.8.x, pré-1.0). Config publicada em
`config/ai.php`.

## Configuração do atendente (por tenant)

Tabela `virtual_attendants` (1:1 com `tenant`), editável em
**Configurações → Atendente virtual** (`settings.attendant`).

| Campo | Descrição |
|---|---|
| `name` | Nome do atendente (aparece na conversa). Default `Piloto Automático`. |
| `tone` | Tom de voz. Enum `AttendantTone`. |
| `provider` | Provedor de IA. Enum `AttendantProvider`. |
| `model` | Modelo específico (opcional). Vazio usa o padrão do provider. |
| `api_key` | API key do provedor. **Criptografada** (`cast encrypted`), nunca exibida após salva. Opcional (ver BYOK abaixo). |
| `context` | Texto livre com regras/diferenciais da loja, anexado ao system prompt. |
| `is_active` | Liga/desliga o atendimento automático. |

### Tons de voz (`AttendantTone`)

- `friendly` — Simpático
- `objective` — Objetivo
- `consultative` — Consultivo
- `enthusiastic` — Entusiasmado

Cada tom vira uma instrução de estilo no system prompt.

### Provedores (`AttendantProvider`) e modelos padrão

- `openai` — OpenAI (ChatGPT), padrão `gpt-4o-mini`
- `anthropic` — Anthropic (Claude), padrão `claude-haiku-4-5-20251001`
- `gemini` — Google (Gemini), padrão `gemini-2.0-flash`

## System prompt

Montado por `AttendantPromptBuilder::build()` combinando:

1. Papel do atendente + nome da loja.
2. Instrução do tom de voz escolhido.
3. Descrição das ferramentas (`consultar_servicos`, `consultar_disponibilidade`,
   `criar_agendamento`) e regras de uso.
4. Guardrails: responder só sobre a loja, não inventar preço/horário, oferecer
   atendente humano quando não souber, mensagens curtas em pt-BR.
5. O `context` livre da loja (quando preenchido).

A prévia do prompt gerado fica visível na tela do atendente (bloco
"Ver instruções geradas").

## Ferramentas de agendamento (tools)

O agent tem 3 tools, chamadas nessa ordem lógica:

1. **`ListServices`** (`consultar_servicos`) — lista serviços ativos com nome,
   categoria, preço formatado, duração e descrição.
2. **`CheckAvailability`** (`consultar_disponibilidade`) — lista horários livres
   (próximos dias) por serviço, reaproveitando `BookingAvailability`.
3. **`BookAppointment`** (`criar_agendamento`) — cria o `Appointment` real.
   O cliente é resolvido/criado pelo telefone da conversa; o slot é revalidado
   antes de gravar (retorna erro se o horário caiu).
4. **`ScheduleFollowUp`** — agenda na fila uma nova execução do atendente para a
   data e hora solicitadas. Na execução, o atendente usa o histórico e o resumo
   do assunto para retomar a conversa e envia a mensagem pelo mesmo fluxo.

`BookingAvailability` é a fonte única de disponibilidade/criação de
agendamento, compartilhada entre a agenda pública (landing), o dashboard e o
atendente.

## Planos, limites e "traga sua própria chave" (BYOK)

O limite protege a margem quando o custo de IA é da plataforma. Quando o lojista
usa a **própria chave**, o custo é dele → **sem limite**.

| Plano | Preço | Atendente |
|---|---|---|
| Autonomia | R$ 97 | **Ilimitado** — exige chave própria (`requires_own_key = true`) |
| Starter | R$ 197 | 200 respostas/dia |
| Performance | R$ 497 | 600 respostas/dia |
| Scale | R$ 897 | 1.200 respostas/dia |

Colunas em `plans`: `ai_daily_message_limit` (respostas/dia) e
`requires_own_key` (bool). Tenant sem plano usa fallback de **50/dia**.

### Regras de chave

- **Chave própria (`api_key` preenchida)** → usa a chave do tenant, **sem
  limite**. `VirtualAttendant::usesOwnKey()` = true.
- **Sem chave própria** → usa a chave da plataforma
  (`config('ai.providers.{provider}.key')`), **com o limite do plano**.
- Para **ligar** (`is_active`) é preciso ter alguma chave utilizável (própria ou
  da plataforma). No plano `requires_own_key`, é obrigatório ter a **própria**.

Resolução da chave: `VirtualAttendant::resolveApiKey()` (própria com fallback
para a da plataforma).

### Cota diária (`AttendantUsage`)

- Contada no cache por `tenant_id` + dia; a chave expira ao fim do dia (reseta
  sozinha à meia-noite).
- Métodos: `limitFor()`, `usedToday()`, `remainingToday()`, `hasReachedLimit()`,
  `record()`.
- Ao atingir o limite, o atendente **pausa** as respostas automáticas até o dia
  seguinte; a loja continua podendo responder manualmente pelo chat.

## Configuração de ambiente

As chaves da plataforma são fallback opcional (as reais são por tenant,
criptografadas). No `.env`:

```bash
OPENAI_API_KEY=
ANTHROPIC_API_KEY=
GEMINI_API_KEY=
```

Sem essas envs, um tenant sem chave própria simplesmente não liga (não há chave
utilizável), o que é o comportamento esperado.

A fila precisa estar processando para as respostas saírem:

```bash
QUEUE_CONNECTION=database
php artisan queue:work
```

## Pré-requisitos para funcionar

1. WhatsApp conectado (instância Evolution ativa) — ver `docs/evolution_go_api.md`.
2. Webhook da Evolution alcançável (`EVOLUTION_GO_WEBHOOK_BASE_URL`).
3. Atendente ligado (`is_active`) com chave utilizável.
4. Worker de fila rodando.
5. Serviços ativos e horários de funcionamento cadastrados (para as tools de
   agendamento).

## Segurança

- `api_key` armazenada com cast `encrypted`; nunca é exibida de volta na tela
  (ao editar, campo vazio mantém a chave existente).
- A chave do tenant é injetada na config do provider apenas durante a execução
  do job (processo isolado; sem vazamento entre tenants).
- O atendente é instruído a responder somente sobre a loja e a não inventar
  preços/horários.

## Limitações conhecidas / próximos passos

- `laravel/ai` é pré-1.0; o acoplamento está isolado em `app/Ai/*` e no job.
- Custo por mensagem é estimado (base `gpt-4o-mini`); Claude/Gemini custam mais
  e reduzem a margem sem alterar o limite em nº de mensagens.
- Job com `tries = 1` (não re-tenta, para evitar resposta duplicada).
- Sem confirmação humana obrigatória antes de agendar (o prompt instrui
  confirmar com o cliente; não há trava dura).
- Sem relatório histórico de uso (a cota vive no cache). Migrar para tabela
  `ai_usage_daily` se precisar auditoria.
