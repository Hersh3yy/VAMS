FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    gnupg \
    nodejs \
    npm

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions including PostgreSQL support
RUN docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd
RUN pecl install redis && docker-php-ext-enable redis

# Install Node.js and Yarn
# Install Node.js using n version manager for better version control
RUN npm install -g n
RUN n lts
# Install Yarn through npm
RUN npm install -g yarn

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u 1000 -d /home/dev dev
RUN mkdir -p /home/dev/.composer && \
    chown -R dev:dev /home/dev

# Set working directory
WORKDIR /var/www

# Switch to non-root user
USER dev

# Verify installations
RUN node --version && \
    npm --version && \
    yarn --version