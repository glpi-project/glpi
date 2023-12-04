#!/bin/bash
set -e -u -x -o pipefail

ATOUM_ADDITIONNAL_OPTIONS=""
if [[ "$CODE_COVERAGE" = true ]]; then
  export COVERAGE_DIR="coverage-functional"
else
  ATOUM_ADDITIONNAL_OPTIONS="--no-code-coverage";
fi

# Get scope from -s option or default to 'tests/functional'
SCOPE="tests/functional"
while getopts ":s:" opt; do
  case $opt in
    s)
      SCOPE=$OPTARG
      ;;
    \?)
      echo "Invalid option: -$OPTARG" >&2
      exit 1
      ;;
  esac
done
if [[ "$SCOPE" == "" || "$SCOPE" == "default" ]]; then
  SCOPE="tests/functional"
fi

# Determine if scope is a file or a directory and set a scope type variable to 'd' or 'f' accordingly
# scopes ending with a slash are considered as directories
# Without a slash, if the file exists, it is considered as a file, otherwise as a directory
if [[ "$SCOPE" == *"/" ]]; then
  SCOPE_TYPE="d"
else
  if [[ -f "$SCOPE" ]]; then
    SCOPE_TYPE="f"
  else
    SCOPE_TYPE="d"
  fi
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
  -$SCOPE_TYPE $SCOPE \

unset COVERAGE_DIR
