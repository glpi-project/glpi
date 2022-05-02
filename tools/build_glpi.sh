#!/bin/bash -e

#
# ---------------------------------------------------------------------
#
# GLPI - Gestionnaire Libre de Parc Informatique
#
# http://glpi-project.org
#
# @copyright 2015-2022 Teclib' and contributors.
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

SCRIPT_DIR=$(dirname $0)
WORKING_DIR=$(readlink -f "$SCRIPT_DIR/..")

if [ -e "$WORKING_DIR/.git" ]
then
    # Prevent script to corrupt user local Git repository
    echo "$WORKING_DIR/.git directory found!"
    echo "This script alters targetted directory and must not be run on a Git repository."
    exit 1
fi

echo "Install dependencies"
# PHP dev dependencies are usefull at this point as they are used by some build operations
$WORKING_DIR/bin/console dependencies install --composer-options="--ignore-platform-reqs --prefer-dist --no-progress"

echo "Compile locale files"
$WORKING_DIR/bin/console locales:compile

echo "Minify stylesheets and javascripts"
find $WORKING_DIR/css $WORKING_DIR/lib $WORKING_DIR/public/lib \( -iname "*.css" ! -iname "*.min.css" \) \
    -exec sh -c 'echo "> {}" && '"$WORKING_DIR"'/node_modules/.bin/csso {} --output $(dirname {})/$(basename {} ".css").min.css' \;

echo "Minify javascripts"
find $WORKING_DIR/js $WORKING_DIR/lib $WORKING_DIR/public/lib \( -iname "*.js" ! -iname "*.min.js" \) \
    -exec sh -c 'echo "> {}" && '"$WORKING_DIR"'/node_modules/.bin/terser {} --mangle --output $(dirname {})/$(basename {} ".js").min.js' \;

echo "Compile SCSS"
$WORKING_DIR/bin/console build:compile_scss

echo "Remove dev files and directories"
# Remove PHP dev dependencies that are not anymore used
composer update nothing --ansi --no-interaction --ignore-platform-reqs --no-dev --no-scripts --working-dir=$WORKING_DIR

# Remove user generated files (i.e. cache and log from CLI commands ran during release)
find $WORKING_DIR/files -depth -mindepth 2 ! -iname "remove.txt" -exec rm -rf {} \;

# Remove hidden files and directory, except .htaccess files
find $WORKING_DIR -depth \( -iname ".*" ! -iname ".htaccess" \) -exec rm -rf {} \;

# Remove useless dev files and directories
dev_nodes=(
    "composer.json"
    "composer.lock"
    "ISSUE_TEMPLATE.md"
    "locales/glpi.pot"
    "node_modules"
    "package.json"
    "package-lock.json"
    "PULL_REQUEST_TEMPLATE.md"
    "tests"
    "tools"
    "vendor/bin"
    "webpack.config.js"
)
for node in "${dev_nodes[@]}"
do
    rm -rf $WORKING_DIR/$node
done
find $WORKING_DIR/pics/ -depth -type f -iname "*.eps" -exec rm -rf {} \;
