FROM php:8.3.8-fpm-alpine3.20

# Instalação de pacotes
RUN apk add --no-cache \
    python3 \
    py3-virtualenv \
    supervisor \
    nginx \
    micro \
    autoconf \
    g++ \
    make \
    libpq-dev \
    postgresql-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip \
    && apk del autoconf g++ make \
    && rm -rf /var/cache/apk/* /tmp/*

# Copiar arquivos de configuração
COPY /conf/php.ini-production /usr/local/etc/php/php.ini
COPY /conf/default.conf /etc/nginx/http.d/default.conf
COPY /conf/nginx.conf /etc/nginx/nginx.conf
COPY /conf/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copiar arquivos da aplicação
COPY /src/html /var/www/html
COPY /src/services /var/www/services

# Ajustar permissões após o COPY
RUN chown -R www-data:www-data /var/www \
    && chown -R www-data:www-data /var/lib/nginx \
    && chown -R www-data:www-data /var/run \
    && chown -R www-data:www-data /var/log/nginx \
    && chown -R www-data:www-data /usr/lib/nginx \
    && chown -R www-data:www-data /run

# Configuração do ambiente Python
RUN python3 -m venv /venv
RUN . /venv/bin/activate \
    && pip install psycopg2-binary requests

ENV PATH="/venv/bin:$PATH"

USER www-data

WORKDIR /var/www

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
