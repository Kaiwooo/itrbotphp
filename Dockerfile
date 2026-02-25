# Используем официальный PHP образ с Apache
FROM php:8.2-apache

# Включаем расширение cURL
RUN docker-php-ext-install curl

# Включаем mod_rewrite (часто нужно для PHP приложений)
RUN a2enmod rewrite

# Копируем весь проект в рабочую директорию
COPY . /var/www/html/

# Устанавливаем права для кеша и логов
RUN mkdir -p /var/www/html/cache /var/www/html/logs \
    && chmod -R 777 /var/www/html/cache /var/www/html/logs

# Указываем рабочую директорию
WORKDIR /var/www/html/

# expose порт 80
EXPOSE 80

# Запуск Apache в фореграунд
CMD ["apache2-foreground"]
