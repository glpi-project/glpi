FROM php:8.2-fpm-alpine

# Install tools
RUN apk add --update --no-cache \
    nodejs-current npm mysql-client curl patch gettext perl git \
    zsh sudo vim && \
    # Install PHP extensions dependencies
    apk add --virtual .build-deps openldap-dev linux-headers $PHPIZE_DEPS && \
    # Install PHP extensions
    ## BZ2 extension
    apk add bzip2-dev && \
    docker-php-ext-install bz2 && \
    ## Exif extension
    docker-php-ext-install exif && \
    ## GD extension
    apk add freetype-dev libjpeg-turbo-dev libpng-dev libwebp-dev && \
    docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp && \
    docker-php-ext-install gd && \
    ## Intl extension
    apk add icu-dev icu-data-full && \
    docker-php-ext-install intl && \
    ## LDAP extension
    apk add libldap && \
    docker-php-ext-install ldap && \
    ## Mysqli extension
    docker-php-ext-install mysqli && \
    ## Opcache extension
    docker-php-ext-install opcache && \
    ## Zip extension
    apk add libzip-dev && \
    docker-php-ext-configure zip && docker-php-ext-install zip && \
    ## Xdebug extension
    pecl install xdebug && docker-php-ext-enable xdebug && \
    # clean cache and dependencies
    rm -rf /var/cache/apk/* && \
    apk del -f .build-deps

# Add transifex client
RUN cd /usr/local/bin/ && curl --silent --location https://raw.githubusercontent.com/transifex/cli/master/install.sh | sh

# Create application volume (used to share codesource with host),
# give its ownage to glpi user (1000:1000) and define it as base working dir
RUN addgroup -g 1000 glpi \
  && adduser -D -h /home/glpi -G glpi -u 1000 glpi \
  && mkdir -p /var/glpi \
  && chown glpi:glpi /var/glpi
RUN echo "glpi ALL=(ALL) NOPASSWD:ALL" >> /etc/sudoers
USER glpi

COPY --from=composer /usr/bin/composer /usr/bin/composer
WORKDIR /var/www
EXPOSE 9000
