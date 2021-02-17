#!/bin/bash -e

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
touch /home/glpi/phpcs.cache
vendor/bin/phpcs \
  --cache /home/glpi/phpcs.cache \
  -d memory_limit=512M \
  -p \
  --extensions=php \
  --standard=vendor/glpi-project/coding-standard/GlpiStandard/ \
  --ignore="/.git/,^/var/glpi/(config|files|lib|marketplace|node_modules|plugins|tests/config|vendor)/" \
  .
