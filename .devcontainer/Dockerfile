# Dockerfile for Laravel Dev Container (GitHub Codespaces)
FROM mcr.microsoft.com/devcontainers/php:8.2

# Install system dependencies and PHP extensions
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
    git \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libcurl4-openssl-dev \
    libsqlite3-dev \
    libreadline-dev \
    libxslt1-dev \
    libpq-dev \
    libicu-dev \
    curl \
    zip \
    default-mysql-client && \
    docker-php-ext-install pdo_mysql mbstring xml zip bcmath intl && \
    rm -rf /var/lib/apt/lists/*

# Install latest Node.js (LTS version) and npm
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - && \
    apt-get install -y nodejs

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /workspaces/codespaces-template-laravel-priv

# Expose port 8000 for Laravel
EXPOSE 8000
