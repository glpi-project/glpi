#!/bin/bash
set -e -u -x -o pipefail

vendor/bin/atoum \
  -p 'php -d memory_limit=512M' \
  --debug \
  --force-terminal \
  --use-dot-report \
  --bootstrap-file tests/bootstrap.php \
  --fail-if-void-methods \
  --fail-if-skipped-methods \
  --no-code-coverage \
  --max-children-number 1 \
  -d tests/LDAP
