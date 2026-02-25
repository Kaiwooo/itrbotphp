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

# Указываем ServerName, чтобы убрать предупреждения Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Настраиваем Apache, чтобы itr.php запускался по умолчанию
RUN echo "DirectoryIndex itr.php" >> /etc/apache2/apache2.conf

# Копируем проект в рабочую директорию
COPY . /var/www/html/

# Создаем папки для кеша и логов, если их нет
RUN mkdir -p /var/www/html/cache /var/www/html/logs \
    && chmod -R 777 /var/www/html/cache /var/www/html/logs

# Устанавливаем рабочую директорию
WORKDIR /var/www/html/

# expose порт 80
EXPOSE 80

# Запуск Apache в фореграунд
CMD ["apache2-foreground"]
