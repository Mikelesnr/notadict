# =========================================================
# Stage 1: Build Frontend Assets (Patched Alpine 3.21)
# =========================================================
FROM node:22-alpine AS node-builder
WORKDIR /app

# Patch all OS packages to clear Alpine CVEs
RUN apk update && apk upgrade --no-cache

COPY package*.json ./
RUN npm ci

COPY . .
RUN npm run build

# =========================================================
# Stage 2: Compilation & Dependency Resolution
# =========================================================
FROM php:8.4-fpm AS php-builder

# Install build tools temporarily
RUN apt-get update && apt-get install -y \
    git \
    curl \
    cmake \
    build-essential \
    zip \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install pdo_mysql mbstring zip pcntl bcmath gd xml

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install PHP dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --no-dev

# Compile whisper-cli
RUN git clone https://github.com/ggerganov/whisper.cpp.git /tmp/whisper.cpp \
    && cd /tmp/whisper.cpp \
    && cmake -B build \
    && cmake --build build --config Release \
    && mkdir -p /app/bin \
    && cp build/bin/whisper-cli /app/bin/whisper-cli \
    && chmod +x /app/bin/whisper-cli \
    && rm -rf /tmp/whisper.cpp

# Download Whisper Model
RUN mkdir -p /app/models \
    && curl -L -o /app/models/ggml-small.en.bin https://huggingface.co/ggerganov/whisper.cpp/resolve/main/ggml-small.en.bin

# =========================================================
# Stage 3: Minimal Production Runtime
# =========================================================
FROM php:8.4-fpm

# Install ONLY runtime dependencies (Nginx, Supervisor, FFmpeg)
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    supervisor \
    ffmpeg \
    libpng16-16 \
    libzip4 \
    libxml2 \
    && docker-php-ext-install pdo_mysql mbstring zip pcntl bcmath gd opcache xml \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copy app code & vendor from Builder
COPY --from=php-builder /app /var/www/html
# Copy compiled whisper binary & model
COPY --from=php-builder /app/bin/whisper-cli /var/www/html/whisper-cli
COPY --from=php-builder /app/models/ggml-small.en.bin /var/www/html/models/ggml-small.en.bin
# Copy built frontend assets
COPY --from=node-builder /app/public/build /var/www/html/public/build

# Copy server configs
COPY docker/nginx.conf /etc/nginx/sites-available/default
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Configure PHP-FPM Unix socket
RUN sed -i 's/listen = 9000/listen = \/var\/run\/php-fpm.sock/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/;listen.owner = www-data/listen.owner = www-data/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/;listen.group = www-data/listen.group = www-data/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/;listen.mode = 0660/listen.mode = 0660/' /usr/local/etc/php-fpm.d/www.conf

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]