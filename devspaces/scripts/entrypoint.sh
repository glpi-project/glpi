#!/bin/bash

/entrypoint.sh mysqld &
sleep 10

# Check if TLS is present
if !(grep -q "TLS_REQCERT" /etc/ldap/ldap.conf)
then
	echo "TLS_REQCERT isn't present"
    echo -e "TLS_REQCERT\tnever" >> /etc/ldap/ldap.conf
fi

cd /opt && unzip dump.zip
mysql -p${MYSQL_ROOT_PASSWORD} ${MYSQL_DATABASE} < /opt/dump.sql
rm -rf dump*

tail -f /dev/null
