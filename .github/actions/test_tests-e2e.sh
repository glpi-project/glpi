#!/bin/bash
set -e -u -x -o pipefail

# Set baseUrl for Cypress in environment variable
export CYPRESS_BASE_URL="http://localhost:80"

# Install Cypress
node_modules/.bin/cypress install

# Run Cypress tests
node_modules/.bin/cypress run --project tests
