#!/bin/bash
set -e -u -x -o pipefail

node_modules/.bin/eslint . && echo "ESLint found no errors"
