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
# Alpine linux does not implement GLOB_BRACE.
# We have to define it to 0 to prevent "Warning: Use of undefined constant GLOB_BRACE - assumed 'GLOB_BRACE'" error.
# This is not a problem as long as we do not use braces in "scan-files" section of the config file.
php -d memory_limit=1G \
  -r 'define("GLOB_BRACE", 0); include "./vendor/maglnet/composer-require-checker/bin/composer-require-checker.php";' \
  check --config-file=.composer-require-checker.config.json

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
