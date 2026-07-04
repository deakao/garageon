#!/usr/bin/env sh
set -eu

DOMAIN="${1:-}"
EMAIL="${LETSENCRYPT_EMAIL:-}"

if [ -z "$DOMAIN" ]; then
    printf 'Uso: LETSENCRYPT_EMAIL=admin@garageon.com.br sh scripts/issue-domain-cert.sh www.loja.com.br\n' >&2
    exit 1
fi

if [ -z "$EMAIL" ]; then
    printf 'Defina LETSENCRYPT_EMAIL antes de emitir o certificado.\n' >&2
    exit 1
fi

case "$DOMAIN" in
    http://*|https://*|*/*|*:*)
        printf 'Informe apenas o host do domínio, exemplo: www.loja.com.br\n' >&2
        exit 1
        ;;
esac

docker compose run --rm certbot certonly \
    --webroot \
    --webroot-path /var/www/certbot \
    --email "$EMAIL" \
    --agree-tos \
    --no-eff-email \
    -d "$DOMAIN"

mkdir -p docker/nginx/conf.d/custom-domains
sed "s/__DOMAIN__/$DOMAIN/g" docker/nginx/templates/domain-ssl.conf.template > "docker/nginx/conf.d/custom-domains/$DOMAIN.conf"

docker compose exec nginx nginx -t
docker compose exec nginx nginx -s reload

printf 'Certificado emitido e Nginx recarregado para %s\n' "$DOMAIN"
