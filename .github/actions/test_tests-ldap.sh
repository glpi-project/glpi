#!/bin/bash
set -e -u -x -o pipefail

ATOUM_ADDITIONNAL_OPTIONS=""
if [[ "$CODE_COVERAGE" = true ]]; then
  export COVERAGE_DIR="coverage-ldap"
else
  ATOUM_ADDITIONNAL_OPTIONS="--no-code-coverage";
fi

vendor/bin/atoum \
  -p 'php -d memory_limit=512M' \
  --debug \
  --force-terminal \
  --use-dot-report \
  --bootstrap-file tests/bootstrap.php \
  --fail-if-void-methods \
  --fail-if-skipped-methods \
  $ATOUM_ADDITIONNAL_OPTIONS \
  --max-children-number 1 \
  -d tests/LDAP

unset COVERAGE_DIR
