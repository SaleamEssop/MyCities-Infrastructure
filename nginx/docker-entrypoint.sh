#!/bin/sh
# =============================================================================
# MyCities Nginx Entrypoint Script
# =============================================================================
# DO NOT OVERCOMPLICATE THIS SCRIPT!
# 
# Previous attempts to backup/copy multiple config files caused nginx to fail.
# Keep it simple: check for SSL, modify config if needed, start nginx.
#
# If you need to change this script:
#   1. Test locally with Build_Local.ps1 first
#   2. Verify nginx starts and HTTPS works
#   3. Only then deploy to production
# =============================================================================

set -e

echo "=== MyCities nginx starting ==="

CONFIG="/etc/nginx/conf.d/default.conf"
SSL_CERT="/etc/letsencrypt/live/www.mycities.co.za/fullchain.pem"
SSL_KEY="/etc/letsencrypt/live/www.mycities.co.za/privkey.pem"

# Check if SSL certificates exist
if [ -f "$SSL_CERT" ] && [ -f "$SSL_KEY" ]; then
    echo "SSL certificates found - HTTPS enabled"
else
    echo "No SSL certificates found - HTTP only mode"
    echo "To enable HTTPS, run certbot to obtain certificates"
    
    # Remove HTTPS server block from config (everything after "# HTTPS server")
    # This allows nginx to start without SSL certs
    if grep -q "# HTTPS server" "$CONFIG"; then
        sed -i '/^# HTTPS server/,$d' "$CONFIG"
        echo "HTTPS server block removed from config"
    fi
fi

# Start nginx
exec nginx -g "daemon off;"