CREATE TABLE glpi_cartridges (
  ID int(11) NOT NULL auto_increment,
  FK_glpi_cartridges_type int(11) default NULL,
  FK_glpi_printers int(11) default NULL,
  date_in date default NULL,
  date_use date default NULL,
  date_out date default NULL,
  pages varchar(30) default NULL,
  PRIMARY KEY  (ID)
) TYPE=MyISAM;

CREATE TABLE glpi_cartridges_assoc (
  ID int(11) NOT NULL auto_increment,
  FK_glpi_cartridges_type int(11) NOT NULL default '0',
  FK_glpi_type_printer int(11) NOT NULL default '0',
  PRIMARY KEY  (ID),
  UNIQUE KEY FK_glpi_type_printer (FK_glpi_type_printer,FK_glpi_cartridges_type)
) TYPE=MyISAM;

CREATE TABLE glpi_cartridges_type (
  ID int(11) NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  ref varchar(255) NOT NULL default '',
  type tinyint(4) NOT NULL default '0',
  FK_glpi_manufacturer int(11) NOT NULL default '0',
  PRIMARY KEY  (ID)
) TYPE=MyISAM;

ALTER TABLE `glpi_cartridges_type` ADD `deleted` ENUM( 'Y', 'N' ) DEFAULT 'N' NOT NULL ;
ALTER TABLE `glpi_cartridges_type` ADD `comments` TEXT NOT NULL ;

