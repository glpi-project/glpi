#!/bin/bash -e

echo "Check for coding standards violations"
node_modules/.bin/eslint . && echo "ESLint found no errors"
