#!/bin/bash -e

PHP_MAJOR_VERSION="$(echo $PHP_VERSION | cut -d '.' -f 1,2)"

# Validate composer config
composer validate --strict
if ! [[ "$PHP_MAJOR_VERSION" == "8.0" ]]; then
  composer check-platform-reqs;
fi

if [[ "$IGNORE_LOCK_FILES" = true ]]; then
  # Remove composer.lock and unset platform to test with dependencies matching PHP version
  rm composer.lock
  composer config --unset platform
fi

# Install dependencies
if [[ "$PHP_MAJOR_VERSION" == "8.0" ]]; then
  COMPOSER_ADD_OPTS=--ignore-platform-reqs;
fi
bin/console dependencies install --composer-options="$COMPOSER_ADD_OPTS --prefer-dist --no-progress"

# Compile translation files
php bin/console locales:compile
