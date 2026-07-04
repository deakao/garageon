# GarageON Landing Page

Documentacao da feature de landing page publica da loja/oficina.

## Objetivo

A landing page transforma o catalogo operacional da loja em uma pagina publica de venda e agendamento.

Ela deve permitir que cada tenant publique uma vitrine com:

- identidade da loja;
- chamada principal editavel;
- SEO basico;
- scripts de analytics, pixel e JavaScript personalizado;
- categorias e servicos cadastrados no cockpit;
- agendamento publico com calendario e horarios disponiveis.

## Rotas

- Configuracao interna: `GET /configuracoes/landing-page`
- Atualizacao interna: `PUT /configuracoes/landing-page`
- Landing publica: `GET /loja/{tenant:slug}`
- Agendamento publico pela landing: `POST /loja/{tenant:slug}/agendar`
- Landing por dominio proprio: `GET /` quando o host corresponde a `tenants.primary_domain`
- Agendamento por dominio proprio: `POST /agendar` quando o host corresponde a `tenants.primary_domain`

Nomes de rota principais:

- `settings.landing`
- `settings.landing.update`
- `storefront`
- `storefront.booking.store`
- `storefront.custom.booking.store`

## Arquivos Principais

- `resources/views/garageon/settings/landing.blade.php`
- `resources/views/garageon/storefront.blade.php`
- `routes/web.php`
- `app/Models/LandingPage.php`
- `app/Models/Service.php`
- `app/Models/Tenant.php`
- `tests/Feature/LandingPageSettingsTest.php`

## Dados Editaveis Da Landing

Os dados de conteudo e rastreamento ficam em `landing_pages`.

Campos principais:

- `eyebrow`: texto pequeno acima da headline.
- `headline`: titulo principal do hero.
- `subheadline`: texto de apoio do hero.
- `hero_image`: URL da imagem principal.
- `hero_badge_title`: titulo do card/selo sobre a imagem.
- `hero_badge_body`: descricao do card/selo.
- `cta_label`: texto do CTA principal.
- `seo_title`: titulo usado em `<title>` e Open Graph.
- `seo_description`: meta description e Open Graph description.
- `seo_keywords`: keywords.
- `analytics_head`: tags inseridas no `<head>`.
- `conversion_pixel`: tags inseridas apos abrir o `<body>`.
- `custom_javascript`: scripts inseridos antes de fechar o `<body>`.
- `published_at`: indica publicacao.

## Fonte Das Secoes Da Vitrine

A landing nao possui CRUD proprio de pacotes.

As secoes publicas sao geradas automaticamente a partir das categorias de servicos da loja:

- categorias cadastradas em `TenantServiceCategory`;
- servicos ativos em `services`;
- campo `services.category` define em qual secao o servico aparece.

Regras:

- Cada categoria com servicos ativos vira uma secao propria.
- Servicos inativos nao aparecem.
- O menu superior aponta para as primeiras categorias publicadas.
- O CTA `Confira nossos servicos` aponta para a primeira categoria disponivel.
- Se nao houver servicos ativos, a pagina mostra um estado vazio orientando cadastrar categorias e servicos.

## Layout Publico

O visual segue a referencia Box Detail:

- barra superior amarela;
- hero preto em duas colunas;
- imagem grande no topo;
- titulos das secoes em amarelo;
- cards escuros;
- footer amarelo.

### Categoria Tipo Pacotes

Quando o nome da categoria contem `pacote`, a secao usa layout de pacotes:

- titulo central em uppercase;
- cards pretos sem thumbnail;
- descricao do servico quebrada em checklist;
- preco do servico;
- botao `Saber mais`.

A descricao aceita itens separados por nova linha ou ponto e virgula.

### Demais Categorias

Categorias comuns usam layout de servicos:

- grid responsivo;
- thumbnail do servico quando cadastrada;
- fallback visual quando nao houver thumbnail;
- nome, descricao, preco e CTA `Agendar Servico`.

## Thumbnail De Servicos

O upload de thumbnail fica no CRUD de `Servicos`, nao na landing.

Campo no banco:

- `services.thumbnail_path`

Regras:

- aceita `jpg`, `jpeg`, `png`, `webp`;
- limite de 2 MB;
- salva em `storage/app/public/tenants/{tenant_id}/services`;
- ao substituir a thumbnail, remove o arquivo antigo;
- a landing usa `Service::thumbnailUrl()` quando existir.

## Agendamento Publico Pela Landing

Os botoes de agendamento da landing abrem um modal centralizado.

Elementos do modal:

- coluna com dados da loja e do servico selecionado;
- calendario mensal;
- lista de horarios disponiveis;
- submodal de confirmacao com dados do cliente.

Campos exigidos na confirmacao:

- nome do cliente;
- WhatsApp;
- email.

Campos opcionais:

- placa;
- marca;
- modelo;
- observacoes.

## Disponibilidade De Horarios

A disponibilidade publica e calculada em `routes/web.php` pela closure `$buildPublicBookingAvailability`.

Regras atuais:

- janela de 30 dias;
- intervalo de 30 minutos;
- considera `tenant_operating_hours`;
- domingo e fechado por padrao quando nao houver configuracao;
- horario padrao quando nao houver configuracao: `08:00` ate `18:00`;
- ignora feriados em `tenant_holidays`;
- ignora horarios que conflitam com agendamentos existentes;
- ignora agendamentos com status `cancelled` ou `canceled`;
- nao oferece horarios com menos de 30 minutos de antecedencia;
- usa a duracao real do servico para calcular conflito e fim do atendimento.

## Confirmacao Server-Side

O frontend mostra apenas sugestoes. A regra definitiva fica no backend.

Ao enviar `POST /loja/{tenant:slug}/agendar`, o backend:

1. valida tenant, servico ativo, data e horario;
2. recalcula disponibilidade;
3. rejeita se o horario deixou de estar disponivel;
4. cria ou atualiza `Customer` pelo telefone;
5. salva `name`, `phone` e `email` do cliente;
6. cria ou atualiza `Vehicle` quando placa, marca e modelo forem informados;
7. cria `Appointment` com `source = landing-page`.

## SEO E Scripts

A landing renderiza:

- `<title>` com `seo_title` ou nome da loja;
- `meta description` com `seo_description` ou subtitulo;
- `meta keywords` quando informado;
- Open Graph basico;
- `analytics_head` dentro do `<head>`;
- `conversion_pixel` logo apos abrir o `<body>`;
- `custom_javascript` antes de fechar o `<body>`.

Importante:

- esses campos podem executar scripts na pagina publica;
- nao inserir credenciais hardcoded;
- scripts devem ser usados apenas no contexto publico da loja.

## Dominio Proprio

Cada loja pode configurar um dominio proprio para abrir a landing diretamente.

Tela interna:

- `GET /configuracoes/dominio`
- `PUT /configuracoes/dominio`

Nome de rota:

- `settings.domain`
- `settings.domain.update`

Campo usado:

- `tenants.primary_domain`

Regras atuais:

- o dominio e normalizado antes de salvar;
- `https://`, caminhos e portas sao removidos;
- o destino do CNAME vem de `GARAGEON_CNAME_TARGET` quando configurado;
- se `GARAGEON_CNAME_TARGET` estiver vazio, o destino usa o host de `APP_URL`;
- o dominio deve ter formato valido, como `www.sualoja.com.br`;
- o dominio da plataforma nao pode ser usado como dominio proprio;
- o mesmo dominio, com ou sem `www`, nao pode ser usado por outra loja;
- quando o host da requisicao bate com `primary_domain`, `GET /` renderiza a landing da loja;
- quando o host da requisicao bate com `primary_domain`, `POST /agendar` usa o mesmo fluxo de agendamento publico da landing por slug.

Passo a passo mostrado para o cliente:

1. entrar no painel DNS do provedor do dominio;
2. criar um registro `CNAME`;
3. preencher `Nome` com `www` ou o subdominio escolhido;
4. preencher `Destino` com o host da plataforma em `APP_URL`;
5. aguardar a propagacao;
6. testar o dominio no navegador.

Observacao operacional:

- para dominio raiz sem `www`, alguns provedores nao aceitam CNAME diretamente;
- nesses casos, orientar o cliente a usar `www` e configurar redirecionamento do dominio raiz para `www` no provedor.
- em desenvolvimento local, use `APP_URL=http://localhost:8001`; esse host serve para teste local, nao como destino real de CNAME.
- para emitir SSL do dominio, siga `docs/docker_ssl.md` e use `scripts/issue-domain-cert.sh` depois que o CNAME estiver propagado.

## Multi-Tenant

Todas as consultas da landing publica devem ser derivadas do tenant resolvido por slug.

Regras obrigatorias:

- carregar somente `landingPage`, `services` e `serviceCategories` do tenant atual;
- salvar agendamentos com `tenant_id` do tenant da URL;
- ao usar dominio proprio, resolver tenant pelo `primary_domain` do host;
- validar `service_id` usando `Rule::exists(...)->where('tenant_id', $tenant->id)`;
- nunca buscar servicos ou agendamentos sem filtro por tenant.

## Testes

Teste principal:

- `tests/Feature/LandingPageSettingsTest.php`

Coberturas esperadas:

- link para editar landing no dashboard;
- atualizacao da configuracao da landing;
- renderizacao de SEO e scripts;
- renderizacao de categorias e servicos ativos;
- omissao de servicos inativos;
- exibicao do modal de agendamento;
- criacao de agendamento publico pela landing;
- bloqueio de horario ja ocupado.

Comandos recomendados apos alterar esta feature:

```bash
XDEBUG_MODE=off ./vendor/bin/pint --dirty
XDEBUG_MODE=off php artisan test tests/Feature/LandingPageSettingsTest.php
npm run build
```

Quando alterar regra de agendamento, rode tambem:

```bash
XDEBUG_MODE=off php artisan test tests/Feature/VehicleAppointmentTest.php
```

## Checklist De Manutencao

- A landing continua carregando em desktop e mobile?
- O menu aponta para categorias existentes?
- Servicos inativos continuam ocultos?
- O modal nao oferece horario fora do expediente?
- O backend rejeita conflito mesmo se o frontend estiver desatualizado?
- O email do cliente e salvo no agendamento publico?
- Scripts personalizados continuam restritos a pagina publica da loja?
- `npm run build` passa apos alterar classes Tailwind?
