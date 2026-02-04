#!/bin/bash
set -e -u -x -o pipefail

vendor/bin/phpunit $@
