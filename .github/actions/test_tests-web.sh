#!/bin/bash
set -e -u -x -o pipefail

bin/console config:set --context=inventory enabled_inventory 1
vendor/bin/phpunit phpunit/web $@
bin/console config:set --context=inventory enabled_inventory 0
