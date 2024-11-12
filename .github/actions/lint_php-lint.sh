#!/bin/bash
set -e -u -x -o pipefail

ROOT_DIR=$(readlink -f "$(dirname $0)/../..")

vendor/bin/parallel-lint \
  --exclude ./files/ \
  --exclude ./marketplace/ \
  --exclude ./plugins/ \
  --exclude ./tools/vendor/ \
  --exclude ./vendor/ \
  .

php -d memory_limit=1G vendor/bin/composer-require-checker check --config-file=.composer-require-checker.config.json

touch ~/phpcs.cache
vendor/bin/phpcs \
  --cache ~/phpcs.cache \
  .

echo "Run code static analysis"
vendor/bin/phpstan analyze \
  --ansi \
  --memory-limit=768M \
  --no-interaction
