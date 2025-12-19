FROM php:8.0-fpm

# Install system dependencies including Node.js
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    zip \
    unzip \
    default-mysql-client \
    gnupg \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd \
    && rm -rf /var/lib/apt/lists/*

# Install Node.js 18 for asset compilation
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Create .env file from example if it doesn't exist
RUN if [ -f ".env.example" ] && [ ! -f ".env" ]; then \
        cp .env.example .env; \
    fi

# Install PHP dependencies (prod)
RUN if [ -f "composer.json" ]; then \
        composer install --no-dev --optimize-autoloader --no-interaction --no-scripts; \
    fi

# Install Node dependencies and build assets
RUN if [ -f "package.json" ]; then \
        npm install && \
        npm run production && \
        rm -rf node_modules; \
    fi

# Prepare writable directories and set permissions
RUN mkdir -p storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Backup public directory (will be copied to volume on startup)
RUN cp -r public /tmp/public-backup || true

# Create entrypoint script
RUN printf '#!/bin/sh\n\
set -e\n\
\n\
echo "=== MyCities Laravel Container Starting ==="\n\
\n\
# Create .env if it does not exist\n\
if [ ! -f /var/www/html/.env ]; then\n\
    echo "Creating .env file..."\n\
    cat > /var/www/html/.env << ENVFILE\n\
APP_NAME=${APP_NAME:-MyCities}\n\
APP_ENV=${APP_ENV:-production}\n\
APP_KEY=\n\
APP_DEBUG=${APP_DEBUG:-false}\n\
APP_URL=${APP_URL:-http://localhost}\n\
\n\
DB_CONNECTION=${DB_CONNECTION:-mysql}\n\
DB_HOST=${DB_HOST:-mysql}\n\
DB_PORT=${DB_PORT:-3306}\n\
DB_DATABASE=${DB_DATABASE:-mycities}\n\
DB_USERNAME=${DB_USERNAME:-mycities_user}\n\
DB_PASSWORD=${DB_PASSWORD:-mycities_password}\n\
\n\
SESSION_DRIVER=${SESSION_DRIVER:-file}\n\
CACHE_DRIVER=${CACHE_DRIVER:-file}\n\
QUEUE_CONNECTION=${QUEUE_CONNECTION:-sync}\n\
ENVFILE\n\
fi\n\
\n\
# Ensure storage directories exist (especially when volume is empty)\n\
mkdir -p /var/www/html/storage/app/public \\\n\
    /var/www/html/storage/framework/cache/data \\\n\
    /var/www/html/storage/framework/sessions \\\n\
    /var/www/html/storage/framework/views\n\
# Fix permissions\n\
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true\n\
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache\n\
# Ensure storage symlink exists for public assets\n\
if [ ! -L /var/www/html/public/storage ]; then\n\
    ln -s /var/www/html/storage/app/public /var/www/html/public/storage\n\
fi\n\
\n\
# Copy public files to volume if empty\n\
if [ ! -f /var/www/html/public/index.php ]; then\n\
    echo "Copying public files to volume..."\n\
    cp -r /tmp/public-backup/* /var/www/html/public/ 2>/dev/null || true\n\
    chown -R www-data:www-data /var/www/html/public\n\
fi\n\
\n\
# Wait for MySQL to be ready\n\
echo "Waiting for MySQL..."\n\
MAX_TRIES=30\n\
TRIES=0\n\
while ! mysqladmin ping -h"$DB_HOST" --silent 2>/dev/null; do\n\
    TRIES=$((TRIES + 1))\n\
    if [ $TRIES -ge $MAX_TRIES ]; then\n\
        echo "MySQL not available after $MAX_TRIES attempts, continuing anyway..."\n\
        break\n\
    fi\n\
    echo "  Waiting for MySQL... ($TRIES/$MAX_TRIES)"\n\
    sleep 2\n\
done\n\
echo "MySQL is ready!"\n\
\n\
# Generate app key if not set\n\
php artisan key:generate --force 2>/dev/null || true\n\
\n\
# Run migrations\n\
echo "Running migrations..."\n\
php artisan migrate --force\n\
\n\
# Auto-sync external database schema if configured (deployment-triggered)\n\
echo "Checking for external database schema sync..."\n\
if php artisan tinker --execute="\n\
    try {\n\
        \$settings = DB::table('settings')->first();\n\
        echo (\$settings && \$settings->db_mode === 'external' && \$settings->external_db_host) ? 'sync-needed' : 'no-sync';\n\
    } catch (Exception \$e) {\n\
        echo 'no-sync';\n\
    }\n\
" 2>/dev/null | grep -q 'sync-needed'; then\n\
    echo "External DB configured - syncing schema from internal to external..."\n\
    php artisan db:sync-schema --from=internal --to=external --quiet || echo "Schema sync failed - check external DB configuration"\n\
else\n\
    echo "No external DB configured - skipping schema sync"\n\
fi\n\
\n\
# Run seeders (idempotent - safe to run multiple times)\n\
echo "Running seeders..."\n\
php artisan db:seed --force 2>/dev/null || true\n\
\n\
# Clear and cache config\n\
echo "Clearing caches..."\n\
php artisan config:clear\n\
php artisan cache:clear\n\
\n\
echo "=== Laravel container ready ==="\n\
\n\
# Start PHP-FPM\n\
exec "$@"\n\
' > /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]