#!/bin/bash
set -e -u -x -o pipefail

PHPUNIT_ADDITIONNAL_OPTIONS=""
if [[ "${CODE_COVERAGE:-}" = true ]]; then
  PHPUNIT_ADDITIONNAL_OPTIONS="--coverage-clover phpunit/coverage/clover.xml"
fi

vendor/bin/phpunit $PHPUNIT_ADDITIONNAL_OPTIONS $@
