#!/bin/bash
set -e -u -x -o pipefail

echo "Check for coding standards violations"
node_modules/.bin/eslint . && echo "ESLint found no errors"
