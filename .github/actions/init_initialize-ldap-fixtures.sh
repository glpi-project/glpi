#!/bin/bash
set -e -u -x -o pipefail

ROOT_DIR=$(readlink -f "$(dirname $0)/../..")

# Recursively delete all LDAP entries
docker compose exec openldap ldapdelete -x -r -H ldap://127.0.0.1:3890/ -D "cn=Manager,dc=glpi,dc=org" -w insecure -c "dc=glpi,dc=org" || true

for f in `ls $ROOT_DIR/tests/LDAP/ldif/*.ldif`; do
  cat $f | docker compose exec openldap ldapadd -x -H ldap://127.0.0.1:3890/ -D "cn=Manager,dc=glpi,dc=org" -w insecure
done
