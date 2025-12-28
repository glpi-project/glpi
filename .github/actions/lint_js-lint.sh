#!/bin/bash
set -e -u -x -o pipefail

node_modules/.bin/eslint --ext=js,vue . && echo "ESLint found no errors"
