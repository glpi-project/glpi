#!/bin/bash
set -e -u -x -o pipefail

PHPUNIT_ADDITIONNAL_OPTIONS=""
if [[ "${CODE_COVERAGE:-}" = true ]]; then
  export COVERAGE_DIR="coverage-functional"
  PHPUNIT_ADDITIONNAL_OPTIONS="--coverage-filter src --coverage-clover phpunit/$COVERAGE_DIR/clover.xml"

else
  PHPUNIT_ADDITIONNAL_OPTIONS="--no-coverage";
fi

for CONFIG in {"--use-default","--dsn=memcached://memcached","--dsn=redis://redis"}; do
  php bin/console cache:configure \
    --ansi --no-interaction \
    $CONFIG
  vendor/bin/phpunit --group cache $PHPUNIT_ADDITIONNAL_OPTIONS $@
done

unset COVERAGE_DIR
