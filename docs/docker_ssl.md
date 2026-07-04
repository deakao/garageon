# Docker E SSL

Documentacao do ambiente em containers e emissao de SSL por dominio com Let's Encrypt.

## Objetivo

Rodar o GarageON em containers e permitir que cada dominio configurado na loja tenha certificado SSL proprio.

Stack do compose:

- `app`: PHP 8.3 FPM com extensoes Laravel;
- `queue`: worker de filas Laravel;
- `nginx`: borda HTTP/HTTPS;
- `mysql`: banco MySQL 8.4;
- `certbot`: emissao e renovacao Let's Encrypt.

## Variaveis Importantes

No `.env`:

```bash
APP_URL=http://localhost:8001
GARAGEON_CNAME_TARGET=www.garageon.con.br
HTTP_PORT=8001
HTTPS_PORT=443

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=garageon
DB_USERNAME=garageon
DB_PASSWORD=garageon
DB_ROOT_PASSWORD=garageon_root
MYSQL_DATABASE=garageon
MYSQL_USER=garageon
MYSQL_PASSWORD=garageon
```

Regras:

- `APP_URL=http://localhost:8001` e util para desenvolvimento local.
- `HTTP_PORT=8001` publica o Nginx local em `localhost:8001`.
- Em producao, use `HTTP_PORT=80` para o Let's Encrypt conseguir validar o dominio.
- `GARAGEON_CNAME_TARGET` e o destino que aparece no passo a passo de CNAME para clientes.
- Em producao, `GARAGEON_CNAME_TARGET` deve ser o host publico da plataforma, por exemplo `www.garageon.con.br`.
- O dominio do cliente deve apontar CNAME para `GARAGEON_CNAME_TARGET` antes de emitir SSL.

## Subir Ambiente

```bash
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
npm install
npm run build
```

Para producao, ajuste antes:

```bash
APP_URL=https://www.garageon.con.br
GARAGEON_CNAME_TARGET=www.garageon.con.br
HTTP_PORT=80
HTTPS_PORT=443
```

Se preferir rodar o build de assets fora do host, adicione depois um container Node proprio. O compose atual foca Laravel, Nginx, MySQL e Certbot.

## Fluxo De SSL Por Dominio

Antes de emitir certificado, confirme:

- o dominio esta salvo em `Configurações > Domínio` na loja;
- o DNS do cliente possui CNAME apontando para `GARAGEON_CNAME_TARGET`;
- as portas `80` e `443` do servidor estao liberadas;
- o Nginx esta rodando com `docker compose up -d nginx`.

Emitir certificado:

```bash
LETSENCRYPT_EMAIL=admin@garageon.com.br sh scripts/issue-domain-cert.sh www.loja.com.br
```

O script faz:

1. chama o Certbot com webroot em `/var/www/certbot`;
2. cria `docker/nginx/conf.d/custom-domains/www.loja.com.br.conf`;
3. valida a configuracao do Nginx;
4. recarrega o Nginx;
5. a partir desse ponto, `https://www.loja.com.br` cai direto na landing da loja.

## Renovacao

Rodar manualmente:

```bash
sh scripts/renew-domain-certs.sh
```

Em producao, configurar cron no host:

```cron
15 3 * * * cd /caminho/garageon && sh scripts/renew-domain-certs.sh >> storage/logs/certbot-renew.log 2>&1
```

## Dominio Principal Da Plataforma

O mesmo script tambem pode emitir certificado para o dominio principal:

```bash
LETSENCRYPT_EMAIL=admin@garageon.com.br sh scripts/issue-domain-cert.sh www.garageon.con.br
```

Se o dominio correto for `www.garageon.com.br`, use `.com.br` no `.env` e no script.

## Observacoes Sobre CNAME

- Recomendado para clientes: `www.cliente.com.br` como CNAME.
- Dominio raiz, como `cliente.com.br`, nem sempre aceita CNAME.
- Quando o provedor nao aceitar CNAME no raiz, configure redirecionamento do raiz para `www`.
- O SSL so sera emitido depois que o DNS estiver apontando para a plataforma.

## Arquivos

- `docker-compose.yml`
- `Dockerfile`
- `docker/nginx/conf.d/default.conf`
- `docker/nginx/templates/domain-ssl.conf.template`
- `docker/nginx/snippets/acme.conf`
- `docker/nginx/snippets/laravel.conf`
- `scripts/issue-domain-cert.sh`
- `scripts/renew-domain-certs.sh`
