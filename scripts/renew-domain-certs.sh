#!/usr/bin/env sh
set -eu

docker compose run --rm certbot renew --webroot --webroot-path /var/www/certbot
docker compose exec nginx nginx -t
docker compose exec nginx nginx -s reload
