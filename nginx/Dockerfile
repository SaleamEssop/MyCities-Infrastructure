FROM nginx:alpine

# Install curl for healthcheck
RUN apk add --no-cache curl

# Create certbot webroot directory
RUN mkdir -p /var/www/certbot

COPY default.conf /etc/nginx/conf.d/default.conf

EXPOSE 80 443

CMD ["nginx", "-g", "daemon off;"]
