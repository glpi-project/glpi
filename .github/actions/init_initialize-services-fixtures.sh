#!/bin/bash -e

ROOT_DIR=$(readlink -f "$(dirname $0)/../..")

echo "Initialize LDAP fixtures"
for f in `ls $ROOT_DIR/tests/LDAP/ldif/*.ldif`; do
  # Delete all LDAP entries (in reverse ordre compared to creation)
  # but ignore errors as these entries may not exists.
  (tac $f | grep -E '^dn:' | sed -E "s/^dn: (.*)$/\\1/" | docker-compose exec -T openldap ldapdelete -x -H ldap://127.0.0.1:3890/ -D "cn=Manager,dc=glpi,dc=org" -w insecure -c) \
  || true
done
for f in `ls $ROOT_DIR/tests/LDAP/ldif/*.ldif`; do
  cat $f | docker-compose exec -T openldap ldapadd -x -H ldap://127.0.0.1:3890/ -D "cn=Manager,dc=glpi,dc=org" -w insecure
done

echo "Initialize email fixtures"
docker-compose exec -T --user root dovecot doveadm expunge -u glpi mailbox 'INBOX' all
docker-compose exec -T --user root dovecot doveadm purge -u glpi
for f in `ls $ROOT_DIR/tests/emails-tests/*.eml`; do
  cat $f | docker-compose exec -T --user glpi dovecot getmail_maildir /home/glpi/Maildir/
done
