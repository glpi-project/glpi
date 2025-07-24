# This SQL queries contained in this file will be executed on every database container startup.

# Required for timezones usage.
# This may be executed before automatic `glpi` user creation, so we have to create it manually.
CREATE USER IF NOT EXISTS 'glpi'@'%' IDENTIFIED BY 'glpi';
SET PASSWORD FOR 'glpi'@'%' = PASSWORD('glpi');
GRANT SELECT ON `mysql`.`time_zone_name` TO 'glpi'@'%';

CREATE TABLE IF NOT EXISTS `glpi_plugins` ( 
  `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
  `directory` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `version` VARCHAR(255) NOT NULL,
  `state` INT NOT NULL DEFAULT 0 ,
  `author` VARCHAR(255) NULL DEFAULT NULL ,
  `homepage` VARCHAR(255) NULL DEFAULT NULL ,
  `license` VARCHAR(255) NULL DEFAULT NULL ,
   PRIMARY KEY (`id`),
  CONSTRAINT `unicity` UNIQUE (`directory`)
)

ENGINE = InnoDB;

CREATE INDEX IF NOT EXISTS `name` 
ON `glpi_plugins` (
  `name` ASC
);

CREATE INDEX IF NOT EXISTS `state` 
ON `glpi_plugins` (
  `state` ASC
);
