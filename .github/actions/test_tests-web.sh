#!/bin/bash
set -e -u -x -o pipefail

php -S localhost:8088 phpunit/router.php &>/dev/null &
bin/console config:set --config-dir=./tests/config --context=inventory enabled_inventory 1
vendor/bin/phpunit phpunit/web
bin/console config:set --config-dir=./tests/config --context=inventory enabled_inventory 0
