FROM php:apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite headers

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-lab.ini
COPY docker/php/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

COPY app/ /var/www/html/
COPY docker/php/entrypoint.sh /usr/local/bin/lab-entrypoint

RUN chmod 0755 /usr/local/bin/lab-entrypoint \
    && mkdir -p /var/www/html/storage/logs /var/www/html/storage/uploads /var/www/html/storage/private /var/www/html/storage/sessions \
    && chown -R www-data:www-data /var/www/html/storage

ENTRYPOINT ["lab-entrypoint"]
CMD ["apache2-foreground"]
