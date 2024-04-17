#!/bin/bash
set -e -u -x -o pipefail

# Install dependencies
# Ignore the PHP version requirement as some dependencies are explicitely marking themselves as incompatible
# with future PHP versions they were not able to test when they release their own versions
# (see https://github.com/laminas/laminas-diactoros/issues/117#issuecomment-1267306142).
# The `+` suffix on `php+` indicates that we ignore the upper bound of our dependencies, see https://getcomposer.org/doc/03-cli.md#install-i
bin/console dependencies install --composer-options="--ignore-platform-req=php+ --prefer-dist --no-progress"

# Compile translation files
php bin/console locales:compile
