# GarageON Clientes

Documentacao da area de clientes do cockpit GarageON.

## Objetivo

A area de clientes centraliza a base operacional da loja/oficina.

Ela deve permitir que cada tenant acompanhe:

- dados de contato do cliente;
- veiculos vinculados;
- origem e data de entrada na base;
- agendamentos vinculados ao cliente;
- orcamentos ainda nao convertidos;
- vendas aprovadas ou pagas;
- oportunidades para retorno, follow-up e campanhas.

## Rotas

- Listagem interna: `GET /dashboard/clientes`
- Criacao interna: `POST /dashboard/clientes`
- Atualizacao interna: `PUT /dashboard/clientes/{customer}`
- Exclusao interna: `DELETE /dashboard/clientes/{customer}`

Nomes de rota principais:

- `customers.index`
- `customers.store`
- `customers.update`
- `customers.destroy`

## Arquivos Principais

- `resources/views/garageon/customers/index.blade.php`
- `resources/views/garageon/customers/form-fields.blade.php`
- `resources/views/garageon/customers/vehicle-fields.blade.php`
- `resources/views/garageon/customers/history.blade.php`
- `routes/web.php`
- `app/Models/Customer.php`
- `app/Models/Vehicle.php`
- `app/Models/Appointment.php`
- `app/Models/Quote.php`
- `app/Models/QuoteItem.php`

## Dados Do Cliente

Os dados principais ficam em `customers`.

Campos principais:

- `tenant_id`: loja dona do cliente.
- `name`: nome do cliente.
- `phone`: WhatsApp/telefone principal.
- `email`: email opcional.
- `last_visit_at`: ultima visita conhecida.
- `tags`: marcadores de origem ou contexto.
- `marketing_consent`: permissao para comunicacoes.

Regras:

- todo cliente pertence a um tenant;
- telefone e usado como identificador pratico em fluxos operacionais;
- email pode ficar vazio no cadastro manual;
- `tags` ajudam a indicar origem como `manual`, `venda`, `orcamento` ou `landing-page`.

## Veiculos

Os veiculos ficam em `vehicles` e pertencem ao cliente e ao tenant.

Campos principais:

- `tenant_id`: loja dona do veiculo.
- `customer_id`: cliente vinculado.
- `plate`: placa normalizada quando informada.
- `brand`: marca.
- `model`: modelo.
- `color`: cor opcional.
- `year`: ano opcional.

Regras:

- a tela de clientes permite cadastrar e editar multiplos veiculos por cliente;
- a listagem mostra o primeiro veiculo como resumo;
- agendamentos, orcamentos e vendas podem apontar para um veiculo especifico;
- a exclusao de cliente remove veiculos vinculados por cascade.

## Tela De Clientes

A tela `GET /dashboard/clientes` segue a estrutura de cockpit:

- header padrao do dashboard;
- KPIs de base de clientes;
- tabela operacional;
- modal de novo cliente;
- modal de edicao/detalhes por cliente.

KPIs exibidos:

- total de clientes;
- clientes novos no mes;
- clientes com veiculo;
- clientes ativos hoje na agenda.

Colunas da tabela:

- cliente;
- veiculo principal;
- WhatsApp;
- email;
- data de entrada;
- acoes.

## Cadastro E Edicao

O cadastro usa modal com abas:

- `Dados`: informacoes do cliente.
- `Veiculos`: lista de veiculos vinculados.

A edicao usa modal com abas:

- `Dados`: atualiza nome, telefone, email e consentimento.
- `Veiculos`: cria, altera ou remove veiculos do cliente.
- `Historico`: mostra relacoes operacionais do cliente.

Ao atualizar veiculos:

1. veiculos enviados com `id` sao atualizados;
2. veiculos sem `id` sao criados;
3. veiculos existentes que nao vierem no payload sao removidos;
4. todos recebem `tenant_id` do tenant autenticado.

## Historico Do Cliente

A aba `Historico` fica em `resources/views/garageon/customers/history.blade.php`.

Ela mostra uma sintese em cards:

- quantidade de agendamentos;
- quantidade de orcamentos ainda nao vendidos;
- valor total e quantidade de vendas.

Depois mostra tres colunas:

- `Agendamentos`: servico, data/hora, status e veiculo.
- `Orcamentos`: orcamento, data/hora, valor, itens e status.
- `Vendas`: venda, data/hora, valor, itens e forma de pagamento.

Regras de classificacao:

- orcamentos sao `quotes` sem `status = approved` e sem `paid_at`;
- vendas sao `quotes` com `status = approved` ou com `paid_at` preenchido;
- agendamentos usam a relacao `appointments` do cliente.

Links:

- orcamentos e vendas apontam para `quotes.show`.

Estados vazios:

- cada coluna tem mensagem propria explicando o que aparecera ali;
- nao usar apenas `Sem dados`.

## Carregamento De Dados

Na listagem, os clientes devem ser carregados com relacoes para evitar N+1.

Relacoes carregadas atualmente:

- `vehicles:id,customer_id,plate,brand,model,year,color`;
- `appointments.service`;
- `appointments.vehicle`;
- `quotes.vehicle`;
- `quotes.items`.

Contadores carregados:

- `appointments_count`;
- `quotes_count`;
- `vehicles_count`.

Ordenacao atual:

- clientes mais recentes primeiro;
- agendamentos por `scheduled_at` decrescente;
- quotes por `paid_at`, `quoted_at` e criacao decrescente.

## Multi-Tenant

Todas as operacoes internas de clientes devem usar o tenant do usuario autenticado.

Regras obrigatorias:

- buscar clientes por `$tenant->customers()` sempre que possivel;
- validar `tenant_id` antes de atualizar ou excluir cliente;
- criar veiculos sempre com `tenant_id` do tenant autenticado;
- nao carregar historico de outro tenant;
- nao usar `Customer::query()` sem filtro por tenant em fluxos internos.

## Exclusao

A exclusao usa `DELETE /dashboard/clientes/{customer}`.

Regras atuais:

- exige usuario autenticado de tenant;
- bloqueia platform admin;
- valida que o cliente pertence ao tenant atual;
- remove o cliente;
- dados vinculados com foreign key cascade tambem podem ser removidos.

Copy de confirmacao atual:

- `Excluir este cliente? Isso tambem remove historicos vinculados a ele.`

Importante:

- se o historico operacional precisar ser preservado, avaliar trocar exclusao fisica por arquivamento/inativacao.

## Padroes De UX

A area de clientes deve seguir os documentos:

- `docs/design_system.md`
- `docs/frontend_rules.md`
- `docs/ux_patterns.md`

Regras especificas:

- mostrar resumo antes da tabela;
- manter o visual escuro premium do cockpit;
- usar amarelo como acento, nao como preenchimento excessivo;
- manter botoes e abas com foco visivel;
- em mobile, abas devem quebrar sem estourar largura;
- historico deve ser escaneavel, com cards curtos e status claros;
- estados vazios devem explicar o valor do proximo evento.

## Testes E Verificacoes

Testes relacionados existentes:

- `tests/Feature/VehicleAppointmentTest.php`
- `tests/Feature/QuoteCrudTest.php`
- `tests/Feature/QuoteRegistrationTest.php`
- `tests/Feature/SaleRegistrationTest.php`

Comandos recomendados apos alterar clientes:

```bash
./vendor/bin/pint --dirty
php artisan test --filter=VehicleAppointmentTest
php artisan test --filter=QuoteCrudTest
php artisan test --filter=SaleRegistrationTest
php artisan view:cache
php artisan view:clear
```

## Pontos De Evolucao

- Criar uma pagina show propria de cliente quando o historico crescer demais para modal.
- Adicionar busca/filtro na tabela de clientes.
- Adicionar paginacao quando a base crescer.
- Adicionar status de cliente ativo, inativo ou arquivado.
- Adicionar eventos de fidelidade e automacoes na aba historico.
- Separar venda em entidade propria caso o fluxo financeiro evolua alem de `quotes.approved`.
