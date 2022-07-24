#!/bin/bash
set -e -u -x -o pipefail

node_modules/.bin/stylelint --color css

bin/console build:compile_scss --ansi --no-interaction --dry-run
