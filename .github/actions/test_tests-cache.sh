#!/bin/bash
set -e -u -x -o pipefail

for CONFIG in {"--use-default","--dsn=memcached://memcached","--dsn=redis://redis"}; do
  php bin/console cache:configure \
    --ansi --no-interaction \
    $CONFIG
  vendor/bin/phpunit --group cache $@
done
