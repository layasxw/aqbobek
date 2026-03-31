FROM php:8.2-apache

# Оставляем только один MPM
RUN a2dismod mpm_event || true
RUN a2enmod mpm_prefork

# PHP + MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Rewrite если нужен .htaccess
RUN a2enmod rewrite

# Копируем проект
COPY . /var/www/html/

# Права
RUN chown -R www-data:www-data /var/www/html

# Явно указываем порт Apache
EXPOSE 80