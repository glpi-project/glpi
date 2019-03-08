#!/bin/bash

# Check if TLS is present
if !(grep -q "TLS_REQCERT" /etc/ldap/ldap.conf)
then
	echo "TLS_REQCERT isn't present"
    echo -e "TLS_REQCERT\tnever" >> /etc/ldap/ldap.conf
fi

# Add database configuration
echo -e "<?php\n \
class DB extends DBmysql {\n \
   public \$dbhost     = 'localhost';\n \
   public \$dbuser     = '${MYSQL_USER}';\n \
   public \$dbpassword = '${MYSQL_PASSWORD}';\n \
   public \$dbdefault  = '${MYSQL_DATABASE}';\n \
}\n" > /data/config/config_db.php
cp /data/config/config_db.php /data/tests

# Add configuration to set /data as the www folder
echo -e "<VirtualHost *:80>\n\tDocumentRoot /data\n\n\t<Directory /data>\n\t\tAllowOverride All\n\t\tOrder Allow,Deny\n\t\tAllow from all\n\t</Directory>\n\n\tErrorLog /var/log/apache2/error-glpi.log\n\tLogLevel warn\n\tCustomLog /var/log/apache2/access-glpi.log combined\n</VirtualHost>" > /etc/apache2/sites-available/000-default.conf

# Add scheduled task by cron and enable
echo "*/2 * * * * www-data /usr/bin/php /data/front/cron.php &>/dev/null" >> /etc/cron.d/glpi
# Start cron service
service cron start

# rewrite apace module activation
a2enmod rewrite && service apache2 restart
