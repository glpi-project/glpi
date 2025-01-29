#!/bin/bash
set -e -u -x -o pipefail


vendor/bin/phpunit --no-coverage phpunit/LDAP/
