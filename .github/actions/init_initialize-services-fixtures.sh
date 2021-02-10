#!/bin/bash -e

echo "Initialize LDAP fixtures"
for f in `ls tests/LDAP/ldif/*.ldif`;
  do cat $f | docker exec --interactive openldap ldapadd -x -H ldap://127.0.0.1:3890/ -D "cn=Manager,dc=glpi,dc=org" -w insecure ;
done

echo "Initialize email fixtures"
for f in `ls tests/emails-tests/*.eml`;
  do cat $f | docker exec --user glpi --interactive dovecot getmail_maildir /home/glpi/Maildir/ ;
done
