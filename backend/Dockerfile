FROM dunglas/frankenphp:1-php8.4 AS builder
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
RUN install-php-extensions \
      pdo_pgsql \
      intl \
      opcache \
      zip

WORKDIR /app
COPY composer.json composer.lock symfony.lock ./
RUN composer install \
      --no-dev \
      --no-scripts \
      --optimize-autoloader \
      --prefer-dist \
      --no-progress \
      --no-interaction

COPY . .

ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV APP_SECRET=placeholder
ENV DATABASE_URL="postgresql://placeholder:placeholder@localhost:5432/placeholder?serverVersion=16&charset=utf8"
ENV GOOGLE_CLIENT_ID=placeholder
ENV GOOGLE_CLIENT_SECRET=placeholder
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative \
      && php bin/console cache:warmup --no-debug

FROM dunglas/frankenphp:1-php8.4

RUN install-php-extensions \
      pdo_pgsql \
      intl \
      opcache \
      zip
RUN { \
          echo 'opcache.enable=1'; \
          echo 'opcache.memory_consumption=256'; \
          echo 'opcache.max_accelerated_files=20000'; \
          echo 'opcache.validate_timestamps=0'; \
          echo 'opcache.interned_strings_buffer=16'; \
      } > /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /app
COPY --from=builder /app /app

ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV SERVER_NAME=:80

RUN chown -R www-data:www-data /app/var

EXPOSE 80
