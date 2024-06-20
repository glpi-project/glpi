# This SQL queries contained in this file will be executed on every database container startup.

# Required for timezones usage
GRANT SELECT ON `mysql`.`time_zone_name` TO 'glpi'@'%';
