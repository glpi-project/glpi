CREATE TABLE glpi_dropdown_tracking_category (
  ID int(11) NOT NULL auto_increment,
  name varchar(255) default NULL,
  PRIMARY KEY  (ID)
);

ALTER TABLE `glpi_tracking` ADD `category` INT( 11 ) ;
