FROM php:8.1.26-apache

RUN apt-get update && apt-get install -y libpq-dev \
    python3 \
    python3-requests \
    python3-psycopg2 \
    supervisor \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && rm -rf /var/lib/apt/lists/*

COPY /conf/php.ini-production /usr/local/etc/php/php.ini
COPY /src/ /var/www/html
COPY /conf/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

WORKDIR /var/www/html
RUN chown -R root:root /var/www/html
RUN chmod 777 -R /var/www/html/services/certs
RUN chmod +x /var/www/html/services/pix.py

CMD ["/usr/bin/supervisord"]