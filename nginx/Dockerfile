FROM nginx:alpine

# Install curl for healthcheck
RUN apk add --no-cache curl

# Create certbot webroot directory structure
RUN mkdir -p /var/www/certbot/.well-known/acme-challenge

# Copy nginx config
COPY default.conf /etc/nginx/conf.d/default.conf

# Copy custom entrypoint
COPY docker-entrypoint.sh /docker-entrypoint-custom.sh
RUN chmod +x /docker-entrypoint-custom.sh

EXPOSE 80 443

# Use custom entrypoint that handles missing SSL certs
ENTRYPOINT ["/docker-entrypoint-custom.sh"]