#!/bin/bash
set -e -u -x -o pipefail

ROOT_DIR=$(readlink -f "$(dirname $0)/../..")

vendor/bin/parallel-lint \
  --show-deprecated \
  --colors \
  --exclude ./files/ \
  --exclude ./marketplace/ \
  --exclude ./plugins/ \
  --exclude ./vendor/ \
  .

php -d memory_limit=1G vendor/bin/composer-require-checker check --config-file=.composer-require-checker.config.json

vendor/bin/php-cs-fixer check \
  --show-progress=dots \
  --verbose \
  --diff

echo "Run code static analysis"
vendor/bin/phpstan analyze \
  --ansi \
  --memory-limit=1G \
  --no-interaction
