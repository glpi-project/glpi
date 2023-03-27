#!/bin/bash

#
# ---------------------------------------------------------------------
#
# GLPI - Gestionnaire Libre de Parc Informatique
#
# http://glpi-project.org
#
# @copyright 2015-2023 Teclib' and contributors.
# @copyright 2003-2014 by the INDEPNET Development Team.
# @licence   https://www.gnu.org/licenses/gpl-3.0.html
#
# ---------------------------------------------------------------------
#
# LICENSE
#
# This file is part of GLPI.
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <https://www.gnu.org/licenses/>.
#
# ---------------------------------------------------------------------
#

set -e -u -o pipefail

WORKING_DIR=$(readlink -f "$(dirname $0)")

# Declaration order in $TESTS_SUITES corresponds to the execution order
TESTS_SUITES=(
  "lint"
  "lint_php"
  "lint_js"
  "lint_scss"
  "lint_twig"
  "install"
  "update"
  "units"
  "functional"
  "cache"
  "ldap"
  "imap"
  "web"
  "javascript"
)

# Extract named options
ALL=false
HELP=false
BUILD=false
while [[ $# -gt 0 ]]; do
  if [[ $1 == "--"* ]]; then
    ## Remove -- prefix, replace - by _ and uppercase all
    declare $(echo $1 | sed -e 's/^--//g' | sed -e 's/-/_/g' -e 's/\(.*\)/\U\1/')=true
    shift
  else
    break
  fi
done

# Flag to indicate wether services containers are usefull
USE_SERVICES_CONTAINERS=0

# Extract list of tests suites to run
TESTS_TO_RUN=()
if [[ $# -gt 0 ]]; then
  ARGS=("$@")

  for KEY in "${ARGS[@]}"; do
    INDEX=0
    for VALID_KEY in "${TESTS_SUITES[@]}"; do
      if [[ "$VALID_KEY" == "$KEY" ]]; then
        TESTS_TO_RUN[$INDEX]=$KEY
        continue 2 # Go to next arg
      fi
      INDEX+=1
    done
    echo -e "\e[1;30;43m/!\ Invalid \"$KEY\" test suite \e[0m"
  done

  # Ensure install test is executed if something else than "lint" or "javascript" is executed.
  # This is mandatory as database is initialized by this test suite.
  # Also, check wether services containes are usefull.
  for TEST_SUITE in "${TESTS_TO_RUN[@]}"; do
    if [[ ! " lint javascript " =~ " ${TEST_SUITE} " ]]; then
      if [[ ! "${TESTS_TO_RUN[@]}" =~ "install" ]]; then
        TESTS_TO_RUN=("install" "${TESTS_TO_RUN[@]}")
      fi
      USE_SERVICES_CONTAINERS=1
      break
    fi
  done
elif [[ "$ALL" = true ]]; then
  TESTS_TO_RUN=("${TESTS_SUITES[@]}")

  # Remove specific lint test, because of global "lint" test suite
  for TEST in "${!TESTS_TO_RUN[@]}"; do
    if [[ "${TESTS_TO_RUN[TEST]}" =~ ^lint_.+ ]]; then
      unset 'TESTS_TO_RUN[TEST]'
    fi
  done

  USE_SERVICES_CONTAINERS=1
fi

# Display help if user asks for it, or if it does not provide which test suite has to be executed
if [[ "$HELP" = true || ${#TESTS_TO_RUN[@]} -eq 0 ]]; then
  cat << EOF
This command runs the tests in an environment similar to what is done by CI.

Usage: run_tests.sh [options] [tests-suites]

Examples:
 - run_tests.sh --all
 - run_tests.sh --build ldap imap
 - run_tests.sh lint

Available options:
 --all      run all tests suites
 --build    build dependencies and translation files before running test suites

Available tests suites:
 - lint
 - lint_php
 - lint_js
 - lint_scss
 - lint_twig
 - install
 - update
 - units
 - functional
 - cache
 - ldap
 - imap
 - web
 - javascript
EOF

  exit 0
fi

# Check for system dependencies
if [[ ! -x "$(command -v docker)" || ! -x "$(command -v docker-compose)" ]]; then
  echo "This scripts requires both \"docker\" and \"docker-compose\" utilities to be installed"
  exit 1
fi

# Import variables from .env file this file exists
APP_CONTAINER_HOME=""
DB_IMAGE=""
PHP_IMAGE=""
UPDATE_FILES_ACL=false
if [[ -f "$WORKING_DIR/.env" ]]; then
  source $WORKING_DIR/.env
fi

# Define variables (some may be defined in .env file)
APPLICATION_ROOT=$(readlink -f "$WORKING_DIR/..")
[[ ! -z "$APP_CONTAINER_HOME" ]] || APP_CONTAINER_HOME=$(mktemp -d -t glpi-tests-home-XXXXXXXXXX)
[[ ! -z "$DB_IMAGE" ]] || DB_IMAGE=githubactions-mysql:8.0
[[ ! -z "$PHP_IMAGE" ]] || PHP_IMAGE=githubactions-php:7.4

# Backup configuration files
BACKUP_DIR=$(mktemp -d -t glpi-tests-backup-XXXXXXXXXX)
find "$APPLICATION_ROOT/tests/config" -mindepth 1 ! -iname ".gitignore" -exec mv {} $BACKUP_DIR \;

# Export variables to env (required for docker-compose) and start containers
export COMPOSE_FILE="$APPLICATION_ROOT/.github/actions/docker-compose-app.yml"
if [[ "$USE_SERVICES_CONTAINERS" ]]; then
  export COMPOSE_FILE="$COMPOSE_FILE:$APPLICATION_ROOT/.github/actions/docker-compose-services.yml"
fi
export APPLICATION_ROOT
export APP_CONTAINER_HOME
export DB_IMAGE
export PHP_IMAGE
export UPDATE_FILES_ACL
cd $WORKING_DIR # Ensure docker-compose will look for .env in current directory
$APPLICATION_ROOT/.github/actions/init_containers-start.sh
$APPLICATION_ROOT/.github/actions/init_show-versions.sh

# Install dependencies if required
[[ "$BUILD" = false ]] || docker-compose exec -T app .github/actions/init_build.sh

# Run tests
LAST_EXIT_CODE=0
for TEST_SUITE in "${TESTS_TO_RUN[@]}";
do
  echo -e "\n\e[1;30;43m Running \"$TEST_SUITE\" test suite \e[0m"
  case $TEST_SUITE in
    "lint")
      # Misc lint (licence headers and locales) is not executed here as their output is not configurable yet
      # and it would be a pain to handle rolling back of their changes.
      # TODO Add ability to simulate locales extact without actually modifying locale files.
         docker-compose exec -T app .github/actions/lint_php-lint.sh \
      && docker-compose exec -T app .github/actions/lint_js-lint.sh \
      && docker-compose exec -T app .github/actions/lint_scss-lint.sh \
      && docker-compose exec -T app .github/actions/lint_twig-lint.sh \
      || LAST_EXIT_CODE=$?
      ;;
    "lint_php")
         docker-compose exec -T app .github/actions/lint_php-lint.sh \
      || LAST_EXIT_CODE=$?
      ;;
    "lint_js")
         docker-compose exec -T app .github/actions/lint_js-lint.sh \
      || LAST_EXIT_CODE=$?
      ;;
    "lint_scss")
         docker-compose exec -T app .github/actions/lint_scss-lint.sh \
      || LAST_EXIT_CODE=$?
      ;;
    "lint_twig")
         docker-compose exec -T app .github/actions/lint_twig-lint.sh \
      || LAST_EXIT_CODE=$?
      ;;
    "install")
         docker-compose exec -T app .github/actions/test_install.sh \
      || LAST_EXIT_CODE=$?
      ;;
    "update")
         $APPLICATION_ROOT/.github/actions/init_initialize-0.80-db.sh \
      && $APPLICATION_ROOT/.github/actions/init_initialize-9.5-db.sh \
      && docker-compose exec -T app .github/actions/test_update-from-older-version.sh \
      && docker-compose exec -T app .github/actions/test_update-from-9.5.sh \
      || LAST_EXIT_CODE=$?
      ;;
    "units")
         docker-compose exec -T app .github/actions/test_tests-units.sh \
      || LAST_EXIT_CODE=$?
      ;;
    "functional")
         docker-compose exec -T app .github/actions/test_tests-functional.sh \
      || LAST_EXIT_CODE=$?
      ;;
    "cache")
         docker-compose exec -T app .github/actions/test_tests-cache.sh \
      || LAST_EXIT_CODE=$?
      ;;
    "ldap")
         $APPLICATION_ROOT/.github/actions/init_initialize-ldap-fixtures.sh \
      && docker-compose exec -T app .github/actions/test_tests-ldap.sh \
      || LAST_EXIT_CODE=$?
      ;;
    "imap")
         $APPLICATION_ROOT/.github/actions/init_initialize-imap-fixtures.sh \
      && docker-compose exec -T app .github/actions/test_tests-imap.sh \
      || LAST_EXIT_CODE=$?
      ;;
    "web")
         docker-compose exec -T app .github/actions/test_tests-web.sh \
      || LAST_EXIT_CODE=$?
      ;;
    "javascript")
         docker-compose exec -T app .github/actions/test_javascript.sh \
      || LAST_EXIT_CODE=$?
      ;;
  esac

  if [[ $LAST_EXIT_CODE -ne 0 ]]; then
    echo -e "\e[1;39;41m Tests \"$TEST_SUITE\" failed \e[0m\n"
    break
  else
    echo -e "\e[1;30;42m Tests \"$TEST_SUITE\" passed \e[0m\n"
  fi
done

# Restore configuration files
rm -f $APPLICATION_ROOT/tests/config/*
find "$BACKUP_DIR" -mindepth 1 -exec mv -f {} $APPLICATION_ROOT/tests/config \;

# Stop containers
$APPLICATION_ROOT/.github/actions/teardown_containers-cleanup.sh

exit $LAST_EXIT_CODE
