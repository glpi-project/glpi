#!/bin/bash -e

php -S localhost:8088 tests/router.php &>/dev/null &
vendor/bin/atoum \
  -p 'php -d memory_limit=512M' \
  --debug \
  --force-terminal \
  --use-dot-report \
  --bootstrap-file tests/bootstrap.php \
  --no-code-coverage \
  --fail-if-skipped-methods \
  --max-children-number 1 \
  -d tests/web
