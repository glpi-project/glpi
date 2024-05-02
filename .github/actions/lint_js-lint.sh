#!/bin/bash
set -e -u -x -o pipefail

ESLINT_USE_FLAT_CONFIG=false node_modules/.bin/eslint . && echo "ESLint found no errors"
