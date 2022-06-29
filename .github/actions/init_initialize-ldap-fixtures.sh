#!/bin/bash
set -e -u -x -o pipefail

ROOT_DIR=$(readlink -f "$(dirname $0)/../..")

for f in `ls $ROOT_DIR/tests/LDAP/ldif/*.ldif`; do
  # Delete all LDAP entries (in reverse ordre compared to creation)
  # but ignore errors as these entries may not exists.
  (tac $f | grep -E '^dn:' | sed -E "s/^dn: (.*)$/\\1/" | docker-compose exec -T openldap ldapdelete -x -H ldap://127.0.0.1:3890/ -D "cn=Manager,dc=glpi,dc=org" -w insecure -c) \
  || true
done
for f in `ls $ROOT_DIR/tests/LDAP/ldif/*.ldif`; do
  cat $f | docker-compose exec -T openldap ldapadd -x -H ldap://127.0.0.1:3890/ -D "cn=Manager,dc=glpi,dc=org" -w insecure
done
