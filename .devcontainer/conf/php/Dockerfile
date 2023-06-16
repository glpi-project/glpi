FROM php:8.1-fpm-alpine

RUN apk add --update --no-cache \
    nodejs-current npm mysql-client curl patch gettext

RUN apk add --update --no-cache \
    icu-dev icu-libs zlib-dev g++ make automake autoconf libzip-dev \
    libpng-dev libwebp-dev libjpeg-turbo-dev freetype-dev \
    ldb-dev libldap openldap-dev

RUN \
    docker-php-ext-install bz2 && \
    docker-php-ext-configure exif && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && docker-php-ext-install gd && \
    docker-php-ext-configure intl && docker-php-ext-install intl && \
    docker-php-ext-install ldap && \
    docker-php-ext-install mysqli && \
    docker-php-ext-install opcache && \
    docker-php-ext-configure zip && docker-php-ext-install zip

# Create application volume (used to share data across jobs),
# give its ownage to glpi user (1000:1000) and define it as base working dir
RUN addgroup -g 1000 glpi \
  && adduser -D -h /home/glpi -G glpi -u 1000 glpi \
  && mkdir -p /var/glpi \
  && chown glpi:glpi /var/glpi
USER glpi

COPY --from=composer /usr/bin/composer /usr/bin/composer
WORKDIR /var/www
EXPOSE 9000
