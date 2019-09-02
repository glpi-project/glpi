#####
# Fetch composer latest build
#####
FROM composer:latest AS composer


#####
# Build main image
####
FROM php:7.3-alpine

# Install PHP Mess Detector - https://github.com/phpmd/phpmd
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN COMPOSER_HOME="/composer" composer global require --prefer-dist --no-progress --dev phpmd/phpmd
ENV PATH /composer/vendor/bin:${PATH}

# Add entrypoint script
ADD entrypoint.sh /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]