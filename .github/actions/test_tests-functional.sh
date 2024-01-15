#!/bin/bash
set -e -u -x -o pipefail

ATOUM_ADDITIONNAL_OPTIONS=""
if [[ "$CODE_COVERAGE" = true ]]; then
  export COVERAGE_DIR="coverage-functional"
else
  ATOUM_ADDITIONNAL_OPTIONS="--no-code-coverage";
fi

# Get additional test arguments from -f, -d and -m options.
SCOPE="-d tests/functional"
METHODS=""
while getopts ":d:f:m:" OPTNAME; do
  case $OPTNAME in
    d|f)
      SCOPE="-${OPTNAME} ${OPTARG}"
      ;;
    m)
      METHODS="-m ${OPTARG}"
      ;;
    \?)
      echo "Invalid option: -$OPTARG" >&2
      exit 1
      ;;
  esac
done

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
  $SCOPE \
  $METHODS

unset COVERAGE_DIR
