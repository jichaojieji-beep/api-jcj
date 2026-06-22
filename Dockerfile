FROM php:8.2-cli

WORKDIR /app

COPY . .

RUN docker-php-ext-install pdo_mysql mysqli

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "."]
