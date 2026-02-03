#!/bin/bash
set -e -u -x -o pipefail

bin/console cache:clear
vendor/bin/phpunit tests/web $@
