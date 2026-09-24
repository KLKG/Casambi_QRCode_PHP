#!/bin/sh
set -e
: "${HTTP_PORT:=8080}"; : "${HTTPS_PORT:=8443}"
export HTTP_PORT HTTPS_PORT
# Apache soll nur auf den gewünschten Ports lauschen (Host-Netzwerk!)
echo "Listen ${HTTP_PORT}" > /etc/apache2/ports.conf
# Selbstsigniertes Zertifikat erzeugen, falls keins vorhanden (eigenes einfach nach config/ssl/ legen)
if [ ! -f /var/www/app/config/ssl/cert.pem ]; then
  mkdir -p /var/www/app/config/ssl
  openssl req -x509 -nodes -newkey rsa:2048 -days 3650 \
    -subj "/CN=${SSL_CN:-casambi-qrcode}" \
    -keyout /var/www/app/config/ssl/key.pem -out /var/www/app/config/ssl/cert.pem
fi
chown -R www-data:www-data /var/www/app/config /var/www/app/logs
exec docker-php-entrypoint "$@"
