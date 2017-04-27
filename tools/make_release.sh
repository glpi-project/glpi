#!/bin/bash

# ----------------------------------------------------------------------
# GLPI - Gestionnaire Libre de Parc Informatique
# Copyright (C) 2003-2006 by the INDEPNET Development Team.
#
# http://indepnet.net/   http://glpi-project.org
# ----------------------------------------------------------------------
#
# LICENSE
#
#	This file is part of GLPI.
#
#    GLPI is free software; you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation; either version 2 of the License, or
#    (at your option) any later version.
#
#    GLPI is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with GLPI; if not, write to the Free Software
#    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
# ------------------------------------------------------------------------

if [ ! "$#" -eq 2 ]
then
 echo "Usage $0 glpi_git_dir release";
 exit ;
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


echo "Delete bigdumps and older sql files";
\rm install/mysql/glpi-0.3*;
\rm install/mysql/glpi-0.4*;
\rm install/mysql/glpi-0.5*;
\rm install/mysql/glpi-0.6*;
\rm install/mysql/glpi-0.7-*;
\rm install/mysql/glpi-0.71*;
\rm install/mysql/glpi-0.72*;
\rm install/mysql/glpi-0.78*;
\rm install/mysql/glpi-0.80*;
\rm install/mysql/glpi-0.83*;
\rm install/mysql/glpi-0.84*;
\rm install/mysql/glpi-0.85*;
\rm install/mysql/glpi-0.90*;
\rm install/mysql/irm*;

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

echo "Delete various scripts and directories"
\rm -rf tools;
\rm -rf phpunit;
\rm -rf tests;
\rm -rf .gitignore;
\rm -rf .travis.yml;
\rm -rf phpunit.xml.dist;
\rm -rf composer.json;
\rm -rf composer.lock;
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
