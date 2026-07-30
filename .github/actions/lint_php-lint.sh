#!/bin/bash
set -u -x -o pipefail

ROOT_DIR=$(readlink -f "$(dirname $0)/../..")

EXIT_CODE=0

vendor/bin/parallel-lint \
  --show-deprecated \
  --colors \
  --exclude ./files/ \
  --exclude ./marketplace/ \
  --exclude ./plugins/ \
  --exclude ./vendor/ \
  . || EXIT_CODE=1

vendor/bin/composer-dependency-analyser || EXIT_CODE=1

vendor/bin/php-cs-fixer check \
  --show-progress=dots \
  --verbose \
  --diff || EXIT_CODE=1

echo "Run code static analysis"
vendor/bin/phpstan analyze \
  --verbose \
  --ansi \
  --memory-limit=1G \
  --no-interaction || EXIT_CODE=1

echo "Run rector"
vendor/bin/rector process \
  --dry-run \
  --ansi || EXIT_CODE=1

exit $EXIT_CODE
