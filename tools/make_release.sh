#!/bin/bash
# /**
#  * ---------------------------------------------------------------------
#  * GLPI - Gestionnaire Libre de Parc Informatique
#  * Copyright (C) 2015-2018 Teclib' and contributors.
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
if [ ! "$#" -eq 2 ]
then
 echo "Usage $0 glpi_git_dir release";
 exit ;
fi

read -p "Are translations up to date? [Y/n] " -n 1 -r
echo    # (optional) move to a new line
if [[ ! $REPLY =~ ^[Yy]$ ]]
then
    [[ "$0" = "$BASH_SOURCE" ]] && exit 1 || return 1 # handle exits from shell or function but don't exit interactive shell
fi

INIT_DIR=$1;
RELEASE=$2;

# test glpi_cvs_dir
if [ ! -e $INIT_DIR ]
then
 echo "$1 does not exist";
 exit ;
fi

INIT_PWD=$PWD;
TMP_DIR=/tmp/$RELEASE
EXTRACT_DIR=$TMP_DIR/extract
BUILD_DIR=$TMP_DIR/build

echo "Extract source from git index";
# Extracting from git index prevent any unwanted divergence with git index to be packaged inside release
git checkout-index -a -f --prefix="$EXTRACT_DIR/"

echo "Building application";
php -d memory_limit=2G $INIT_DIR/vendor/bin/robo build:app $EXTRACT_DIR $BUILD_DIR/glpi --load-from $INIT_DIR/tools

echo "Creating tarball";
cd $BUILD_DIR
tar czf "/tmp/glpi-$RELEASE.tgz" glpi

cd $INIT_PWD;

echo "Deleting temp directory";
rm -rf $TMP_DIR;

echo "The Tarball is in the /tmp directory";
