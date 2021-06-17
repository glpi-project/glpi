#!/bin/bash -e
# /**
#  * ---------------------------------------------------------------------
#  * GLPI - Gestionnaire Libre de Parc Informatique
#  * Copyright (C) 2015-2021 Teclib' and contributors.
#  *
#  * http://glpi-project.org
#  *
#  * based on GLPI - Gestionnaire Libre de Parc Informatique
#  * Copyright (C) 2003-2014 by the INDEPNET Development Team.
#  *
#  * ---------------------------------------------------------------------
#  *
#  * LICENSE
#  *
#  * This file is part of GLPI.
#  *
#  * GLPI is free software; you can redistribute it and/or modify
#  * it under the terms of the GNU General Public License as published by
#  * the Free Software Foundation; either version 2 of the License, or
#  * (at your option) any later version.
#  *
#  * GLPI is distributed in the hope that it will be useful,
#  * but WITHOUT ANY WARRANTY; without even the implied warranty of
#  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  * GNU General Public License for more details.
#  *
#  * You should have received a copy of the GNU General Public License
#  * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
#  * ---------------------------------------------------------------------
# */

WORKING_DIR=$(readlink -f "$(dirname $0)")

# Declaration order in $TESTS_SUITES corresponds to the execution order
TESTS_SUITES=(
  "lint"
  "install"
  "update"
  "units"
  "functionnal"
  "ldap"
  "imap"
  "web"
)

# Extract named options
while [[ $# -gt 0 ]]; do
  if [[ $1 == "--"* ]]; then
    ## Remove -- prefix, replace - by _ and uppercase all
    declare $(echo $1 | sed -e 's/^--//g' | sed -e 's/-/_/g' -e 's/\(.*\)/\U\1/')=true
    shift
  else
    break
  fi
done

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

  # Ensure install test is executed if something else than "lint" is executed
  # This is mandatory as database is initialized by this test suite
  if [[ !${#TESTS_TO_RUN[@]} -eq 0 && "${TESTS_TO_RUN[@]}" != "lint" && ! "${TESTS_TO_RUN[@]}" =~ "install" ]]; then
    TESTS_TO_RUN=("install" "${TESTS_TO_RUN[@]}")
  fi
elif [[ "$ALL" = true ]]; then
  TESTS_TO_RUN=("${TESTS_SUITES[@]}")
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
 - install
 - update
 - units
 - functionnal
 - ldap
 - imap
 - web
EOF

  exit 0
fi

# Check for system dependencies
if [[ ! -x "$(command -v docker)" || ! -x "$(command -v docker-compose)" ]]; then
  echo "This scripts requires both \"docker\" and \"docker-compose\" utilities to be installed"
  exit 1
fi

# Import variables from .env file this file exists
if [[ -f "$WORKING_DIR/.env" ]]; then
  source $WORKING_DIR/.env
fi

# Define variables (some may be defined in .env file)
APPLICATION_ROOT=$(readlink -f "$WORKING_DIR/..")
[[ ! -z "$APP_CONTAINER_HOME" ]] || APP_CONTAINER_HOME=$(mktemp -d -t glpi-tests-home-XXXXXXXXXX)
[[ ! -z "$DB_IMAGE" ]] || DB_IMAGE=githubactions-mysql:8.0
[[ ! -z "$PHP_IMAGE" ]] || PHP_IMAGE=githubactions-php:7.2

# Backup configuration files
BACKUP_DIR=$(mktemp -d -t glpi-tests-backup-XXXXXXXXXX)
find "$APPLICATION_ROOT/tests/config" -mindepth 1 ! -iname ".gitignore" -exec mv {} $BACKUP_DIR \;

# Export variables to env (required for docker-compose) and start containers
export COMPOSE_FILE="$APPLICATION_ROOT/.github/actions/docker-compose-app.yml"
[[ "${TESTS_TO_RUN[@]}" == "lint" ]] || export COMPOSE_FILE="$COMPOSE_FILE:$APPLICATION_ROOT/.github/actions/docker-compose-services.yml"
export APPLICATION_ROOT
export APP_CONTAINER_HOME
export DB_IMAGE
export PHP_IMAGE
cd $WORKING_DIR # Ensure docker-compose will look for .env in current directory
$APPLICATION_ROOT/.github/actions/init_containers-start.sh
$APPLICATION_ROOT/.github/actions/init_show-versions.sh

# Install dependencies if required
[[ -z "$BUILD" ]] || docker-compose exec -T app .github/actions/init_install-dependencies.sh

# Run tests
for TEST_SUITE in "${TESTS_TO_RUN[@]}";
do
  echo -e "\n\e[1;30;43m Running \"$TEST_SUITE\" test suite \e[0m"
  LAST_EXIT_CODE=0
  case $TEST_SUITE in
    "lint")
      # Misc lint (locales and SCSS) is not executed here as their output is not configurable yet
      # and it would be a pain to handle rolling back of their changes.
      # TODO Add ability to simulate locales extact and SCSS compilation without actually modifying locale files.
         docker-compose exec -T app .github/actions/lint_php-lint.sh \
      && docker-compose exec -T app .github/actions/lint_js-lint.sh \
      || LAST_EXIT_CODE=$?
      ;;
    "install")
         docker-compose exec -T app .github/actions/test_install.sh \
      || LAST_EXIT_CODE=$?
      ;;
    "update")
         $APPLICATION_ROOT/.github/actions/init_initialize-old-dbs.sh \
      && docker-compose exec -T app .github/actions/test_update-from-older-version.sh \
      || LAST_EXIT_CODE=$?
      ;;
    "units")
         docker-compose exec -T app .github/actions/test_tests-units.sh \
      || LAST_EXIT_CODE=$?
      ;;
    "functionnal")
         docker-compose exec -T app .github/actions/test_tests-functionnal.sh \
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
