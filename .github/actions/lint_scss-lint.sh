#!/bin/bash
set -e -u -x -o pipefail

echo "Check for coding standards violations"
node_modules/.bin/stylelint --color css

echo "Check for SCSS compilation errors"
bin/console build:compile_scss --ansi --no-interaction --dry-run
