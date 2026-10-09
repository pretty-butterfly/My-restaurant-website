FROM php:8.2-apache
RUN docker-php-ext-install mysqli
pdo_mysql
#Configure Apache to listen on Render's port
RUN sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf\
&& sed -i 's/:80>/:10000>/' /etc/
apache2/sites-available/000-default.conf
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www
html
EXPOSE 10000
CMD ["apache2-foreground"]
