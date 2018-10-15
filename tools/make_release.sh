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

if [ -e /tmp/glpi ]
then
 echo "Delete existing temp directory";
\rm -rf /tmp/glpi;
fi

echo "Copy to  /tmp directory";
git checkout-index -a -f --prefix=/tmp/glpi/

echo "Move to this directory";
cd /tmp/glpi;

echo "Retrieve PHP vendor"
composer install --no-dev --optimize-autoloader --prefer-dist --quiet

echo "Clean PHP vendor"
\rm -rf vendor/bin;
\find vendor/ -type f -name "build.xml" -exec rm -rf {} \;
\find vendor/ -type f -name "build.properties" -exec rm -rf {} \;
\find vendor/ -type f -name "composer.json" -exec rm -rf {} \;
\find vendor/ -type f -name "composer.lock" -exec rm -rf {} \;
\find vendor/ -type f -name "changelog.md" -exec rm -rf {} \;
\find vendor/ -type f -name "*phpunit.xml.dist" -exec rm -rf {} \;
\find vendor/ -type f -name ".gitignore" -exec rm -rf {} \;
\find vendor/ -type d -name "test*" -prune -exec rm -rf {} \;
\find vendor/ -type d -name "doc*" -prune -exec rm -rf {} \;
\find vendor/ -type d -name "example*" -prune -exec rm -rf {} \;
\find vendor/ -type d -name "design" -prune -exec rm -rf {} \;

echo "Minify stylesheets and javascripts"
$INIT_PWD/vendor/bin/robo minify --load-from tools

echo "Compile SCSS"
$INIT_PWD/scripts/compile_scss

echo "Compile locale files"
./tools/locale/update_mo.pl

echo "Delete various scripts and directories"
\rm -rf tools;
\rm -rf phpunit;
\rm -rf tests;
\rm -rf .gitignore;
\rm -rf .travis.yml;
\rm -rf .atoum.php;
\rm -rf .circleci;
\rm -rf phpunit.xml.dist;
\rm -rf composer.json;
\rm -rf composer.lock;
\rm -rf .composer.hash;
\rm -rf ISSUE_TEMPLATE.md;
\rm -rf PULL_REQUEST_TEMPLATE.md;
\rm -rf .tx;
\find pics/ -type f -name "*.eps" -exec rm -rf {} \;

echo "Creating tarball";
cd ..;
tar czf "glpi-$RELEASE.tgz" glpi


cd $INIT_PWD;


echo "Deleting temp directory";
\rm -rf /tmp/glpi;

echo "The Tarball is in the /tmp directory";
