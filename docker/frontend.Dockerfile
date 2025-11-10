FROM php:8.2-apache

RUN apt-get update && apt-get install -y openssl

RUN docker-php-ext-install mysqli

RUN mkdir -p /etc/apache2/ssl

RUN openssl req -x509 -nodes -days 365 \
    -subj "/C=ES/ST=Catalunya/L=Barcelona/O=Projecte/CN=localhost" \
    -newkey rsa:2048 \
    -keyout /etc/apache2/ssl/apache-selfsigned.key \
    -out /etc/apache2/ssl/apache-selfsigned.crt

RUN a2enmod ssl headers
RUN a2dismod -f deflate

COPY docker/apache-ssl.conf /etc/apache2/sites-available/000-default.conf

COPY ../frontend/public/ /var/www/html/
COPY ../frontend/src/ /var/www/html/src/

RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

EXPOSE 443

CMD ["apache2-foreground"]