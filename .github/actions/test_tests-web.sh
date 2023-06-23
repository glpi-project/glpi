#!/bin/bash
set -e -u -x -o pipefail

php -S localhost:8088 tests/router.php &>/dev/null &
bin/console config:set --config-dir=./tests/config --context=inventory enabled_inventory 1
vendor/bin/atoum \
  -p 'php -d memory_limit=512M' \
  --debug \
  --force-terminal \
  --use-dot-report \
  --bootstrap-file tests/bootstrap.php \
  --no-code-coverage \
  --fail-if-void-methods \
  --fail-if-skipped-methods \
  --max-children-number 1 \
  -d tests/web
bin/console config:set --config-dir=./tests/config --context=inventory enabled_inventory 0
