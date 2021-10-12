#!/bin/bash -e

# Stable versions contains only 3 groups of digits separated by a dot,
# i.e. no "dev", "alpha", "beta, "rc", ... keyword.
STABLE_REGEX="^[0-9]+\.[0-9]+\.[0-9]+$"

# Validate composer config
# Cannot use `--strict` mode due to following warning: "Defining autoload.psr-4 with an empty namespace prefix is a bad idea for performance"
composer validate
if [[ "$PHP_VERSION" =~ $STABLE_REGEX ]]; then
  composer check-platform-reqs;
fi

# Install dependencies
if ! [[ "$PHP_VERSION" =~ $STABLE_REGEX ]]; then
  COMPOSER_ADD_OPTS=--ignore-platform-reqs;
fi
bin/console dependencies install --composer-options="$COMPOSER_ADD_OPTS --prefer-dist --no-progress"

# Compile translation files
php bin/console locales:compile
