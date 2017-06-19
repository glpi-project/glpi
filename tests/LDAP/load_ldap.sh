#!/bin/bash

mkdir -p $TRAVIS_BUILD_DIR/ldap
chmod -R 777 $TRAVIS_BUILD_DIR/ldap

BASEDN='dc=glpi,dc=org'
ROOTDN='cn=admin,dc=glpi,dc=org'
PASSWORD=`slappasswd -h {md5} -s password`

ldapadd -Y EXTERNAL -H ldapi:/// <<EOF
# database
dn: olcDatabase={2}hdb,cn=config
objectClass: olcDatabaseConfig
objectClass: olcHdbConfig
olcDatabase: {2}hdb
olcRootDN: $ROOTDN
olcRootPW: $PASSWORD
olcDbDirectory: $TRAVIS_BUILD_DIR/ldap
olcSuffix: $BASE_DN
olcAccess: {0}to attrs=userPassword,shadowLastChange by self write by dn="cn=$ADMIN_USER,$BASE_DN" write by * auth
olcAccess: {1}to dn.base="" by dn="$ROOTDN" write by * read
olcAccess: {2}to * by self write by dn="$ROOTDN" write by * read
olcRequires: authc
olcLastMod: TRUE
olcDbCheckpoint: 512 30
olcDbConfig: {0}set_cachesize 0 2097152 0
olcDbConfig: {1}set_lk_max_objects 1500
olcDbConfig: {2}set_lk_max_locks 1500
olcDbConfig: {3}set_lk_max_lockers 1500
olcDbIndex: objectClass eq
EOF
ldapadd -h ldap-master:389 -D cn=admin,dc=glpi,dc=org -xv -w password -f tests/LDAP/ldap-glpi.ldif
