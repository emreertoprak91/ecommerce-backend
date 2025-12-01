#------------------------------------------------------------------------------
# E-Commerce Backend - Development Dockerfile
# PHP 8.4 FPM with extensions for Laravel
#------------------------------------------------------------------------------

FROM php:8.4-fpm-alpine AS base

LABEL maintainer="E-Commerce Team"
LABEL description="Laravel E-Commerce Backend - Development"

# Install system dependencies
RUN apk add --no-cache \
    bash \
    curl \
    git \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    icu-dev \
    oniguruma-dev \
    libxml2-dev \
    linux-headers \
    $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mysqli \
        gd \
        zip \
        intl \
        mbstring \
        exif \
        pcntl \
        bcmath \
        opcache \
        xml \
        soap

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Xdebug for development
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure PHP
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Custom PHP configuration
COPY docker/php/php-dev.ini $PHP_INI_DIR/conf.d/99-custom.ini
COPY docker/php/xdebug.ini $PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini

# Set working directory
WORKDIR /var/www/html

# Copy application
COPY --chown=www-data:www-data . .

# Install dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port
EXPOSE 9000

# Health check
HEALTHCHECK --interval=30s --timeout=5s --start-period=5s --retries=3 \
    CMD php-fpm -t || exit 1

USER www-data

CMD ["php-fpm"]
