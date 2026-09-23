FROM php:8.2-cli

RUN docker-php-ext-install pdo_sqlite sqlite3

WORKDIR /app

COPY . /app

RUN mkdir -p /app/database /app/uploads \
    && chmod -R 777 /app/database /app/uploads

EXPOSE 10000

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-10000} -t /app"]
