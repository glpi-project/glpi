#!/bin/bash
set -e -u -x -o pipefail

php -S localhost:8088 phpunit/router.php &>/dev/null &
vendor/bin/phpunit phpunit/web
