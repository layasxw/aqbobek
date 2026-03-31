FROM php:8.2-cli

# Устанавливаем расширения для MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Рабочая папка
WORKDIR /app

# Копируем проект
COPY . /app

# Railway требует слушать $PORT
CMD sh -c "php -S 0.0.0.0:${PORT:-8080} -t /app"