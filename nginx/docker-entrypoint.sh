#!/bin/sh
set -e

echo "=== MyCities nginx starting ==="

# Config files
FULL_CONFIG="/etc/nginx/conf.d/default.conf.full"
HTTP_CONFIG="/etc/nginx/conf.d/default.conf.http"
ACTIVE_CONFIG="/etc/nginx/conf.d/default.conf"

# Backup original config if not already done
if [ ! -f "$FULL_CONFIG" ]; then
    cp "$ACTIVE_CONFIG" "$FULL_CONFIG"
    echo "Backed up full config (with HTTPS)"
fi

# Create HTTP-only config if not exists
if [ ! -f "$HTTP_CONFIG" ]; then
    sed '/^# HTTPS server/,$d' "$FULL_CONFIG" > "$HTTP_CONFIG"
    echo "Created HTTP-only config"
fi

# Check if SSL certificates exist
SSL_CERT="/etc/letsencrypt/live/www.mycities.co.za/fullchain.pem"
SSL_KEY="/etc/letsencrypt/live/www.mycities.co.za/privkey.pem"

if [ -f "$SSL_CERT" ] && [ -f "$SSL_KEY" ]; then
    echo "SSL certificates found - enabling HTTPS"
    cp "$FULL_CONFIG" "$ACTIVE_CONFIG"
else
    echo "No SSL certificates - HTTP only mode"
    echo "(Run certbot to obtain SSL certificates)"
    cp "$HTTP_CONFIG" "$ACTIVE_CONFIG"
fi

# Execute nginx
exec nginx -g "daemon off;"