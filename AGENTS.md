# AGENTS.md

## Projeto

BoxDetail é um SaaS multi-tenant para oficinas de estética automotiva. Cada tenant representa uma loja/oficina e deve isolar dados operacionais como clientes, veículos, serviços, agenda, orçamentos, assinaturas, fidelidade, automações e landing pages.

## Stack

- Backend: PHP 8.3+ com Laravel 13.
- Banco: MySQL em produção/desenvolvimento real; SQLite em memória apenas nos testes.
- Frontend: Blade, Vite e Tailwind CSS 4.
- Identidade visual: automotiva premium, preto/amarelo/branco, fonte Orbitron.

## Comandos Úteis

```bash
composer install
npm install
php artisan migrate:fresh --seed
npm run build
php artisan test
./vendor/bin/pint --dirty
```

Para desenvolvimento local:

```bash
composer run dev
```

## Convenções de Domínio

- Toda entidade operacional de loja deve ter `tenant_id`.
- Não misture dados entre tenants em queries, controllers ou views.
- Use route model binding por `slug` para páginas públicas de loja quando possível.
- Planos e administração da plataforma ficam no nível global.
- Clientes, veículos, serviços, agenda, orçamentos, assinaturas, fidelidade, chatbot, landing pages e alertas de venda ficam no nível do tenant.

## Padrões Laravel

- Prefira Eloquent relationships explícitos nos models.
- Migrations devem ser reversíveis e manter chaves estrangeiras com `cascadeOnDelete` ou `nullOnDelete` conforme o impacto do dado.
- Seeders devem manter dados demonstrativos realistas para validar o fluxo visual.
- Rode `./vendor/bin/pint --dirty` após editar PHP.
- Rode `php artisan test` após mudanças de domínio, rotas ou migrations.

## Frontend

- Use Blade + Tailwind, sem adicionar framework JS pesado sem necessidade.
- Preserve a direção visual premium/automotiva.
- Use `font-orbitron` em marca, títulos e elementos de destaque.
- Evite layouts genéricos; prefira cards escuros, contrastes fortes, amarelo como acento e linguagem de cockpit/performance.

## Segurança e Produto

- Não implemente integrações reais de WhatsApp ou cartão com credenciais hardcoded.
- Gateways de pagamento e mensageria devem ser abstraídos antes de produção.
- Recursos de IA/vendedor digital podem começar como regras/alertas determinísticos antes de integrar LLMs.
- Não exponha dados administrativos nas páginas públicas de loja.
