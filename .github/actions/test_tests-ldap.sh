#!/bin/bash -e

ATOUM_ADDITIONNAL_OPTIONS=""
if [[ -z "$COVERAGE_DIR" ]];
  then ATOUM_ADDITIONNAL_OPTIONS="--no-code-coverage";
fi

vendor/bin/atoum \
  -p 'php -d memory_limit=512M' \
  --debug \
  --force-terminal \
  --use-dot-report \
  --bootstrap-file tests/bootstrap.php \
  --fail-if-skipped-methods \
  $ATOUM_ADDITIONNAL_OPTIONS \
  --max-children-number 1 \
  -d tests/LDAP
