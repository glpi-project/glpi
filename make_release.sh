#!/bin/bash

if [ ! "$#" -eq 2 ]
then
 echo "Usage $0 glpi_cvs_dir release";
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

if [ ! "$UID" -eq 0 ]
then
 echo "You are not root user";
 exit;
fi

INIT_PWD=$PWD;


if [ -e /tmp/glpi-$RELEASE ]
then
 echo "Delete existing temp directory";
\rm -rf /tmp/glpi-$RELEASE;
fi
echo "Copy to  /tmp directory";

cp -rf $INIT_DIR /tmp/glpi-$RELEASE;

echo "Move to this directory";
cd /tmp/glpi-$RELEASE;

echo "Delete CVS directories";
\rm -rf CVS;
\rm -rf */CVS;
\rm -rf */*/CVS;
\rm -rf */*/*/CVS;
\rm -rf */*/*/*/CVS;
\rm -rf */*/*/*/*/CVS;

echo "Delete template Headers"
\rm HEADER;

echo "Delete bigdumps and older sql files";
\rm mysql/*bigdump*;
\rm mysql/updatedb.back;
\rm mysql/glpi-0.3-*;
\rm mysql/glpi-0.4-*;

echo "Delete LDAP directories";
\rm -rf ldap;
\rm -rf glpi/ldap;

echo "Delete various scripts and directories"
\rm make_release.sh;
\rm modify_headers.pl;
\rm -rf reports/reports/phpexcel
\rm -rf conffiles

echo "Creating tarball";
cd ..;
tar czvf "$INIT_PWD/glpi-$RELEASE.tar.gz" glpi-$RELEASE

echo "Deleting temp directory";
\rm -rf /tmp/glpi-$RELEASE;