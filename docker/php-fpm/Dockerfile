FROM php:8.3-fpm

# Sistem bağımlılıkları kurulumu
RUN apt-get update && apt-get install -y \
    curl \
    git \
    zip \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip pdo_mysql

# Node.js kurulumu (LTS versiyon)
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

RUN npm install axios @inertiajs/inertia

#npm install vite
# Composer kurulumu
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /application

# Opsiyonel: composer cache temizleme ve yükleme, burayı build aşamasında kullanabilirsin
# COPY composer.json composer.lock ./
# RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Node ve npm versiyon kontrol (opsiyonel)
RUN node -v && npm -v
