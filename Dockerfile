FROM php:8.5-cli

# Устанавливаем curl (нужен для Bitrix REST)
RUN apt-get update && apt-get install -y \
    curl \
    && docker-php-ext-install curl

WORKDIR /app

COPY . /app

EXPOSE 10000

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-10000} index.php"]
