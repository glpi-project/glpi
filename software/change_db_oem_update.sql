ALTER TABLE `glpi_licenses` ADD `oem` ENUM( 'N', 'Y' ) DEFAULT 'N' NOT NULL ,
ADD `oem_computer` INT( 11 ) NOT NULL ;


ALTER TABLE `glpi_software` ADD `is_update` ENUM( 'N', 'Y' ) DEFAULT 'N' NOT NULL , ADD `update_software` INT( 11 ) NOT NULL DEFAULT '-1';

ALTER TABLE `glpi_licenses` ADD `buy` ENUM( 'Y', 'N' ) DEFAULT 'Y' NOT NULL ;
