FROM nginx:alpine

# Install curl for healthcheck
RUN apk add --no-cache curl

# Create certbot webroot directory structure (with .well-known subdirectory)
RUN mkdir -p /var/www/certbot/.well-known/acme-challenge

COPY default.conf /etc/nginx/conf.d/default.conf

EXPOSE 80 443

CMD ["nginx", "-g", "daemon off;"]
