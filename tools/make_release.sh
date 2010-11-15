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
 echo "Usage $0 glpi_svn_dir release";
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

#if [ ! "$UID" -eq 0 ]
#then
# echo "You are not root user";
# exit;
#fi

INIT_PWD=$PWD;


if [ -e /tmp/glpi ]
then
 echo "Delete existing temp directory";
\rm -rf /tmp/glpi;
fi
echo "Copy to  /tmp directory";

cp -rf $INIT_DIR /tmp/glpi;

echo "Move to this directory";
cd /tmp/glpi;

echo "Delete SVN directories";
find . -name .svn -type d -exec  \rm -rf {} \;

echo "Delete bigdumps and older sql files";
\rm install/mysql/*bigdump*;
\rm install/mysql/updatedb.back;
\rm install/mysql/glpi-0.3*;
\rm install/mysql/glpi-0.4*;
\rm install/mysql/glpi-0.5*;
\rm install/mysql/glpi-0.6*;
\rm install/mysql/glpi-0.7-*;
\rm install/mysql/glpi-0.71*;
\rm install/mysql/glpi-0.72*;
\rm install/mysql/glpi-0.78-*;
\rm install/mysql/glpi-*-default*;
\rm install/mysql/irm*;

echo "Delete various scripts and directories"
\rm -rf tools;
\rm -rf phpunit;

echo "Must be root to generate a clean tarball - Please login"
echo "cd /tmp; chown -R root.root /tmp/glpi; tar czvf /tmp/glpi-$RELEASE.tar.gz glpi; \rm -rf /tmp/glpi" | sudo -s

echo "Creating tarball";
#cd ..;
#tar czvf "$INIT_PWD/glpi-$RELEASE.tgz" glpi-$RELEASE

echo "Logout root user";

#exit

#cd $INIT_PWD;


echo "Deleting temp directory";
#\rm -rf /tmp/glpi;

echo "The Tarball is in the /tmp directory";
