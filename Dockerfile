# Используем официальный PHP образ с Apache
FROM php:8.2-apache

# Устанавливаем зависимости для сборки cURL
RUN apt-get update && apt-get install -y \
        libcurl4-openssl-dev \
        pkg-config \
        libssl-dev \
        unzip \
        git \
    && docker-php-ext-install curl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Включаем mod_rewrite
RUN a2enmod rewrite

# Копируем проект в рабочую директорию
COPY . /var/www/html/

# Права для кеша и логов
RUN mkdir -p /var/www/html/cache /var/www/html/logs \
    && chmod -R 777 /var/www/html/cache /var/www/html/logs

# Устанавливаем рабочую директорию
WORKDIR /var/www/html/

# expose порт 80
EXPOSE 80

# Запуск Apache в фореграунд
CMD ["apache2-foreground"]
