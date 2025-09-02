# This SQL queries contained in this file will be executed on every database container startup.

# Required for timezones usage.
# This may be executed before automatic `glpi` user creation, so we have to create it manually.
CREATE USER IF NOT EXISTS 'glpi'@'%' IDENTIFIED BY 'glpi';
SET PASSWORD FOR 'glpi'@'%' = PASSWORD('glpi');
