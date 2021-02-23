#!/bin/bash -e

ROOT_DIR=$(readlink -f "$(dirname $0)/../..")
echo $ROOT_DIR

echo "Check for syntax errors"
vendor/bin/parallel-lint \
  --exclude ./files/ \
  --exclude ./marketplace/ \
  --exclude ./plugins/ \
  --exclude ./tools/vendor/ \
  --exclude ./vendor/ \
  .

echo "Check for missing dependencies / bad symbols"
php -d memory_limit=1G vendor/bin/composer-require-checker check --config-file=.composer-require-checker.config.json

echo "Check for coding standards violations"
touch ~/phpcs.cache
vendor/bin/phpcs \
  --cache ~/phpcs.cache \
  -d memory_limit=512M \
  -p \
  --extensions=php \
  --standard=vendor/glpi-project/coding-standard/GlpiStandard/ \
  --ignore="/.git/,^$ROOT_DIR/(config|files|lib|marketplace|node_modules|plugins|tests/config|vendor)/" \
  .
