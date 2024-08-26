#!/bin/bash
set -e -u -x -o pipefail

ROOT_DIR=$(readlink -f "$(dirname $0)/../..")

composer run lint

curl https://github.com/maglnet/ComposerRequireChecker/releases/latest/download/composer-require-checker.phar --output composer-require-checker.phar
php -d memory_limit=1G composer-require-checker.phar check --config-file=.composer-require-checker.config.json

touch ~/phpcs.cache
vendor/bin/phpcs \
  --cache ~/phpcs.cache \
  .

echo "Run code static analysis"
vendor/bin/phpstan analyze \
  --ansi \
  --memory-limit=2500M \
  --no-interaction
