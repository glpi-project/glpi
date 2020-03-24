ARG BASE_APP_IMAGE=php:apache

#####
# Builder image
#####
FROM php:7.4-cli-alpine AS builder

# Copy composer binary from latest composer image.
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install required system packages.
RUN \
  # Install intl PHP extension (required during CSS compilation execution).
  apk add --update --virtual .build-deps $PHPIZE_DEPS \
  && apk add --update icu-dev \
  && docker-php-ext-install intl \
  && apk del -f .build-deps \
  \
  # Install gettext required to compile locales.
  && apk add --update gettext \
  # Install nodejs and npm.
  && apk add --update nodejs npm \
  \
  # Install git and zip used by composer when fetching dependencies.
  && apk add --update git unzip \
  \
  # Clean sources list.
  && rm -rf /var/cache/apk/*

 # Update PHP configuration.
RUN echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/docker-php-memory.ini

# Copy GLPI source.
COPY ./ /usr/src/glpi

# Change working dir.
WORKDIR /usr/src/glpi

# Build GLPI app
RUN \
  # Install dev dependencies (required to run Robo.li tasks)
  composer install --ignore-platform-reqs --no-progress --no-suggest --prefer-dist \
  \
  && ./vendor/bin/robo build:app /usr/src/glpi /tmp/glpi --load-from ./tools


#####
# Application image
#####
FROM $BASE_APP_IMAGE

RUN apt-get update \
  \
  # Install APCU PHP extension.
  && pecl install apcu \
  && docker-php-ext-enable apcu \
  && echo "apc.enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-apcu.ini \
  \
  # Install exif extension.
  && docker-php-ext-install exif \
  \
  # Install gd PHP extension.
  # GD extension configuration parameters changed on PHP 7.4
  # see https://www.php.net/manual/en/image.installation.php#image.installation
  && apt-get install --assume-yes --no-install-recommends --quiet libfreetype6-dev libjpeg-dev libpng-dev \
  && PHP_MAJOR_VERSION="$(echo $PHP_VERSION | cut -d '.' -f 1)" \
  && PHP_MINOR_VERSION="$(echo $PHP_VERSION | cut -d '.' -f 2)" \
  && if [ $PHP_MAJOR_VERSION -le "5" ] || ([ $PHP_MAJOR_VERSION -eq "7" ] && [ $PHP_MINOR_VERSION -le "3" ]); then \
    # For PHP <= 5.x or PHP 7 <= 7.3, use old parameters
    docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
  ; else \
    docker-php-ext-configure gd --with-freetype --with-jpeg \
  ; fi \
  && docker-php-ext-install gd \
  \
  # Install imap PHP extension.
  && apt-get install --assume-yes --no-install-recommends --quiet libc-client-dev libkrb5-dev \
  && PHP_OPENSSL=yes docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
  && docker-php-ext-install imap \
  \
  # Install intl PHP extension.
  && apt-get install --assume-yes --no-install-recommends --quiet libicu-dev \
  && docker-php-ext-configure intl \
  && docker-php-ext-install intl \
  \
  # Install ldap PHP extension.
  && apt-get install --assume-yes --no-install-recommends --quiet libldap2-dev \
  && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ \
  && docker-php-ext-install ldap \
  \
  # Install mysqli PHP extension.
  && docker-php-ext-install mysqli \
  \
  # Install soap PHP extension (required for some plugins).
  && apt-get install --assume-yes --no-install-recommends --quiet libxml2-dev \
  && docker-php-ext-install soap \
  \
  # Install xmlrpc PHP extension.
  && apt-get install --assume-yes --no-install-recommends --quiet libxml2-dev \
  && docker-php-ext-install xmlrpc \
  \
  # Install cron service.
  && apt-get install --assume-yes --no-install-recommends --quiet cron \
  \
  # Install acl to manage acl of writable directories.
  && apt-get install --assume-yes --no-install-recommends --quiet acl \
  \
  # Clean sources list.
  && rm -rf /var/lib/apt/lists/*

# Copy services configuration files to container.
COPY .docker/glpi-app/files /

# Install GLPI crontab.
RUN crontab -u www-data /etc/cron.d/glpi

# Copy GLPI application.
COPY --from=builder --chown=www-data:www-data /tmp/glpi /var/www/glpi

# Declare a volume for "config" and "files" directory
RUN mkdir /var/glpi && chown www-data:www-data /var/glpi
VOLUME /var/glpi

# Define GLPI environment variables
ENV \
  GLPI_INSTALL_MODE=DOCKER \
  GLPI_CONFIG_DIR=/var/glpi/config \
  GLPI_VAR_DIR=/var/glpi/files

# Make startup script executable and executes it as default command.
RUN chmod u+x /opt/startup.sh
CMD /opt/startup.sh

# Define application path as base working dir.
WORKDIR /var/www/glpi
