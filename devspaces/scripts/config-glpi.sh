#!/bin/bash

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
ln -s /data /var/www/html/glpi
echo -e "<VirtualHost *:80>\n\tDocumentRoot /var/www/html/glpi\n\n\t<Directory /var/www/html/glpi>\n\t\tAllowOverride All\n\t\tOrder Allow,Deny\n\t\tAllow from all\n\t</Directory>\n\n\tErrorLog /var/log/apache2/error-glpi.log\n\tLogLevel warn\n\tCustomLog /var/log/apache2/access-glpi.log combined\n</VirtualHost>" > /etc/apache2/sites-available/000-default.conf

# Add scheduled task by cron and enable
echo "*/2 * * * * www-data /usr/bin/php /var/www/html/glpi/front/cron.php &>/dev/null" >> /etc/cron.d/glpi

# Start cron service
service cron start

# rewrite apace module activation
a2enmod rewrite && service apache2 restart

# Give access on files and config
chown -R www-data:www-data /data/config /data/files
