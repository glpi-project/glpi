FROM php:8.2-fpm-alpine

RUN apk update && \
    # Install tools
    apk add nodejs-current npm mysql-client curl patch gettext perl git \
    zsh sudo vim htop && \
    # Install Python, pip and Python modules required by "plugin-release" script.
    apk add python3-dev py3-pip && \
    apk add libffi-dev libxml2-dev libxslt-dev && pip install lxml && \
    apk add git && pip install gitpython && \
    pip install PyGithub && \
    pip install termcolor && \
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
    # Clean cache and dependencies
    apk del -f .build-deps && \
    rm -rf /var/cache/apk/*

# Add transifex client
RUN cd /usr/local/bin/ && curl --silent --location https://raw.githubusercontent.com/transifex/cli/master/install.sh | sh

# Create glpi user with uid=1000 and gid=1000 (will share same uid/gid as main user of host machine to prevent files ownage issues)
RUN addgroup -g 1000 glpi \
  && adduser -D -h /home/glpi -G glpi -u 1000 glpi
VOLUME /home/glpi
RUN echo "glpi ALL=(ALL) NOPASSWD:ALL" >> /etc/sudoers
USER glpi

COPY --from=composer /usr/bin/composer /usr/bin/composer
WORKDIR /var/www
EXPOSE 9000
