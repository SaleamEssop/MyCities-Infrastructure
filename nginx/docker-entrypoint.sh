#!/bin/sh
set -e

echo "=== MyCities nginx starting ==="

# Check if SSL certificates exist
SSL_CERT="/etc/letsencrypt/live/www.mycities.co.za/fullchain.pem"
SSL_KEY="/etc/letsencrypt/live/www.mycities.co.za/privkey.pem"

if [ -f "$SSL_CERT" ] && [ -f "$SSL_KEY" ]; then
    echo "SSL certificates found - HTTPS enabled"
else
    echo "No SSL certificates found - HTTP only mode"
    # Remove HTTPS server block from config
    # Keep only the HTTP block (lines before "# HTTPS server")
    sed -i '/^# HTTPS server/,$d' /etc/nginx/conf.d/default.conf
    echo "HTTPS server block removed from config"
fi

# Execute nginx
exec nginx -g "daemon off;"