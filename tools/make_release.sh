#!/bin/bash -eu

#
# ---------------------------------------------------------------------
#
# GLPI - Gestionnaire Libre de Parc Informatique
#
# http://glpi-project.org
#
# @copyright 2015-2025 Teclib' and contributors.
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

# Extract options and args (see https://stackoverflow.com/a/14203146)
ASSUME_YES=0;
POSITIONAL_ARGS=()
while [[ $# -gt 0 ]]; do
    case $1 in
        -y)
            ASSUME_YES=1;
            shift
            ;;
        -*|--*)
            echo "Unknown option $1"
            exit 1
            ;;
        *)
            POSITIONAL_ARGS+=("$1")
            shift
            ;;
    esac
done
set -- "${POSITIONAL_ARGS[@]}"

# Check arguments
if [ ! "$#" -eq 2 ]
then
    echo "This script builds a release archive based on Git index of given directory."
    echo ""
    echo "Usage $0 [-y] /path/to/glpi-git-dir release-name"
    echo ""
    echo "Options:"
    echo "y     Automatic yes to prompts; assume "yes" as answer to all prompts and run non-interactively."
    exit
fi

SOURCE_DIR=$(readlink -f $1)
RELEASE=$2
WORKING_DIR=/tmp/glpi-$RELEASE
TARBALL_PATH=/tmp/glpi-$RELEASE.tgz

if [ ! -e $SOURCE_DIR ] || [ ! -e $SOURCE_DIR/.git ]
then
    echo "$SOURCE_DIR is not a valid Git repository"
    exit
fi

if [[ ! $ASSUME_YES = 1 ]]
then
    read -p "Are translations up to date? [Y/n] " -n 1 -r
    echo # (optional) move to a new line
    if [[ ! $REPLY =~ ^[Yy]$ ]]
    then
        [[ "$0" = "$BASH_SOURCE" ]] && exit 1 || return 1 # handle exits from shell or function but don't exit interactive shell
    fi
fi

echo "Copying to $WORKING_DIR directory..."
if [ -e $WORKING_DIR ]
then
    rm -rf $WORKING_DIR
fi
git --git-dir="$SOURCE_DIR/.git" checkout-index --all --force --prefix="$WORKING_DIR/glpi/"

if [[ ! $ASSUME_YES = 1 ]]
then
    FOUND_VERSION=$(grep -Eo "define\('GLPI_VERSION', '[^']+'\);" $WORKING_DIR/glpi/src/autoload/constants.php | sed "s/define('GLPI_VERSION', '\([^)]*\)');/\1/")
    if [[ ! "$RELEASE" = "$FOUND_VERSION" ]]
    then
        read -p "$RELEASE does not match version $FOUND_VERSION declared in src/autoload/constants.php. Do you want to continue? [Y/n] " -n 1 -r
        echo # (optional) move to a new line
        if [[ ! $REPLY =~ ^[Yy]$ ]]
        then
            [[ "$0" = "$BASH_SOURCE" ]] && exit 1 || return 1 # handle exits from shell or function but don't exit interactive shell
        fi
    fi
fi

echo "Building application..."
$WORKING_DIR/glpi/tools/build_glpi.sh

echo "Creating tarball..."
tar -c -z -f $TARBALL_PATH -C $WORKING_DIR glpi

echo "Deleting temp directory..."
rm -rf $WORKING_DIR

echo "The Tarball path is $TARBALL_PATH"
