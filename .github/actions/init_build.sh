#!/bin/bash
set -e -u -x -o pipefail

# Stable versions contains only 3 groups of digits separated by a dot,
# i.e. no "dev", "alpha", "beta, "rc", ... keyword.
STABLE_REGEX="^[0-9]+\.[0-9]+\.[0-9]+$"
PHP_MAJOR_VERSION="$(echo $PHP_VERSION | cut -d '.' -f 1,2 | sed 's/\.//')"
CHECK_PLATFORM_REQS="false"
if [[ "$PHP_VERSION" =~ $STABLE_REGEX && "$PHP_MAJOR_VERSION" -lt "82" ]]; then
    CHECK_PLATFORM_REQS="true"
fi

# Validate composer config
composer validate --strict
if [[ "$CHECK_PLATFORM_REQS" == "true" ]]; then
  composer check-platform-reqs;
fi

# Install dependencies
COMPOSER_ADD_OPTS=""
if [[ "$CHECK_PLATFORM_REQS" == "false" ]]; then
  COMPOSER_ADD_OPTS="--ignore-platform-reqs";
fi
bin/console dependencies install --composer-options="$COMPOSER_ADD_OPTS --prefer-dist --no-progress"

# Compile translation files
php bin/console locales:compile
