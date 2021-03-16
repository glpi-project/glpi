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

# Check arguments
if [ ! "$#" -eq 2 ]
then
    echo "This script builds a release archive based on Git index of given directory."
    echo ""
    echo "Usage $0 /path/to/glpi-git-dir release-name"
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

read -p "Are translations up to date? [Y/n] " -n 1 -r
echo # (optional) move to a new line
if [[ ! $REPLY =~ ^[Yy]$ ]]
then
    [[ "$0" = "$BASH_SOURCE" ]] && exit 1 || return 1 # handle exits from shell or function but don't exit interactive shell
fi

echo "Copying to $WORKING_DIR directory"
if [ -e $WORKING_DIR ]
then
    rm -rf $WORKING_DIR
fi
git --git-dir="$SOURCE_DIR/.git" checkout-index --all --force --prefix="$WORKING_DIR/glpi/"

echo "Building application"
$WORKING_DIR/glpi/tools/build_glpi.sh

echo "Creating tarball";
tar -c -z -f $TARBALL_PATH -C $WORKING_DIR glpi

echo "Deleting temp directory"
rm -rf $WORKING_DIR

echo "The Tarball path is $TARBALL_PATH"
