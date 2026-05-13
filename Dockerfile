FROM docker.io/library/php:8.2-fpm

RUN apt-get update && apt-get install -y \
    apache2 \
    libfcgi-bin \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install mysqli pdo pdo_mysql && \
    pecl install redis && \
    docker-php-ext-enable redis

RUN a2dismod mpm_prefork 2>/dev/null || true && \
    a2enmod mpm_event proxy proxy_fcgi rewrite

COPY config/performance/apache-tuning.conf /etc/apache2/mods-available/mpm_event.conf
COPY config/performance/php-tuning.ini /usr/local/etc/php/conf.d/performance.ini
COPY config/performance/fpm-pool.conf /usr/local/etc/php-fpm.d/zzz-fpm-pool.conf
COPY config/apache/fpm-site.conf /etc/apache2/sites-available/000-default.conf

COPY . /var/www/html/
RUN chmod -R 777 /var/www/html/uploads

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
