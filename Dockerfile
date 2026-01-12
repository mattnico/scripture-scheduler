# =============================================================================
# Scripture Scheduler - Production Dockerfile
# Multi-stage build for Laravel 12 + Livewire + SQLite
# =============================================================================

# -----------------------------------------------------------------------------
# Stage 1: Build frontend assets
# -----------------------------------------------------------------------------
FROM node:20-alpine AS frontend

WORKDIR /app

# Copy package files
COPY package.json package-lock.json ./

# Install dependencies
RUN npm ci

# Copy source files needed for build
COPY resources ./resources
COPY vite.config.js tailwind.config.js postcss.config.js ./

# Build production assets
RUN npm run build

# -----------------------------------------------------------------------------
# Stage 2: Install PHP dependencies
# -----------------------------------------------------------------------------
FROM composer:2 AS composer

WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies without dev packages
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --ignore-platform-reqs

# Copy application code
COPY . .

# Generate optimized autoloader
RUN composer dump-autoload --optimize --no-dev

# -----------------------------------------------------------------------------
# Stage 3: Production image
# -----------------------------------------------------------------------------
FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    sqlite \
    sqlite-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    oniguruma-dev \
    && docker-php-ext-install \
    pdo_sqlite \
    zip \
    bcmath \
    opcache \
    mbstring

# Configure PHP for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# PHP optimizations
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Configure PHP-FPM
COPY docker/php-fpm/www.conf /usr/local/etc/php-fpm.d/www.conf

# Configure Nginx
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Configure Supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Set working directory
WORKDIR /var/www/html

# Copy application from composer stage
COPY --from=composer /app .

# Copy built frontend assets
COPY --from=frontend /app/public/build ./public/build

# Create necessary directories and set permissions
RUN mkdir -p \
    storage/app/public \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    database \
    && chown -R www-data:www-data \
    storage \
    bootstrap/cache \
    database \
    && chmod -R 775 \
    storage \
    bootstrap/cache \
    database

# Copy entrypoint script
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Expose port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/up || exit 1

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
