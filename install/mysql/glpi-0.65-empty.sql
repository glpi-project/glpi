#GLPI Dump database on 2006-02-28 22:04

### Dump table glpi_cartridges

DROP TABLE IF EXISTS `glpi_cartridges`;
CREATE TABLE `glpi_cartridges` (
    `ID` int(11) NOT NULL auto_increment,
    `FK_glpi_cartridges_type` int(11) DEFAULT '0' NOT NULL,
    `FK_glpi_printers` int(11) DEFAULT '0' NOT NULL,
    `date_in` date,
    `date_use` date,
    `date_out` date,
    `pages` int(11) DEFAULT '0' NOT NULL,
   PRIMARY KEY (`ID`),
   KEY FK_glpi_cartridges_type (`FK_glpi_cartridges_type`),
   KEY FK_glpi_printers (`FK_glpi_printers`)
) TYPE=MyISAM;


### Dump table glpi_cartridges_assoc

DROP TABLE IF EXISTS `glpi_cartridges_assoc`;
CREATE TABLE `glpi_cartridges_assoc` (
    `ID` int(11) NOT NULL auto_increment,
    `FK_glpi_cartridges_type` int(11) DEFAULT '0' NOT NULL,
    `FK_glpi_dropdown_model_printers` int(11) DEFAULT '0' NOT NULL,
   PRIMARY KEY (`ID`),
   UNIQUE FK_glpi_type_printer (`FK_glpi_dropdown_model_printers`, `FK_glpi_cartridges_type`),
   KEY FK_glpi_cartridges_type (`FK_glpi_cartridges_type`),
   KEY FK_glpi_type_printer_2 (`FK_glpi_dropdown_model_printers`)
) TYPE=MyISAM;


### Dump table glpi_cartridges_type

DROP TABLE IF EXISTS `glpi_cartridges_type`;
CREATE TABLE `glpi_cartridges_type` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255),
    `ref` varchar(255),
    `location` int(11) DEFAULT '0' NOT NULL,
    `type` tinyint(4) DEFAULT '0' NOT NULL,
    `FK_glpi_enterprise` int(11) DEFAULT '0' NOT NULL,
    `tech_num` int(11) DEFAULT '0',
    `deleted` enum('Y','N') DEFAULT 'N' NOT NULL,
    `comments` text,
    `alarm` tinyint(4) DEFAULT '10' NOT NULL,
    `notes` longtext,
   PRIMARY KEY (`ID`),
   KEY FK_glpi_enterprise (`FK_glpi_enterprise`),
   KEY tech_num (`tech_num`),
   KEY deleted (`deleted`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_computer_device

DROP TABLE IF EXISTS `glpi_computer_device`;
CREATE TABLE `glpi_computer_device` (
    `ID` int(11) NOT NULL auto_increment,
    `specificity` varchar(250),
    `device_type` tinyint(4) DEFAULT '0' NOT NULL,
    `FK_device` int(11) DEFAULT '0' NOT NULL,
    `FK_computers` int(11) DEFAULT '0' NOT NULL,
   PRIMARY KEY (`ID`),
   KEY device_type (`device_type`),
   KEY device_type_2 (`device_type`, `FK_device`),
   KEY FK_computers (`FK_computers`),
   KEY FK_device (`FK_device`)
) TYPE=MyISAM;


### Dump table glpi_computers

DROP TABLE IF EXISTS `glpi_computers`;
CREATE TABLE `glpi_computers` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(200),
    `serial` varchar(200),
    `otherserial` varchar(200),
    `contact` varchar(200),
    `contact_num` varchar(200),
    `tech_num` int(11) DEFAULT '0' NOT NULL,
    `comments` text,
    `date_mod` datetime,
    `os` int(11) DEFAULT '0' NOT NULL,
    `os_version` int(11) DEFAULT '0' NOT NULL,
    `os_sp` int(11) DEFAULT '0' NOT NULL,
    `auto_update` int(11) DEFAULT '0' NOT NULL,
    `location` int(11) DEFAULT '0' NOT NULL,
    `domain` int(11) DEFAULT '0' NOT NULL,
    `network` int(11) DEFAULT '0' NOT NULL,
    `model` int(11) DEFAULT '0' NOT NULL,
    `type` int(11) DEFAULT '0' NOT NULL,
    `is_template` enum('0','1') DEFAULT '0' NOT NULL,
    `tplname` varchar(200),
    `FK_glpi_enterprise` int(11) DEFAULT '0' NOT NULL,
    `deleted` enum('Y','N') DEFAULT 'N' NOT NULL,
    `notes` longtext,
    `ocs_import` tinyint(4) DEFAULT '0' NOT NULL,
   PRIMARY KEY (`ID`),
   KEY location (`location`),
   KEY os (`os`),
   KEY type (`model`),
   KEY FK_glpi_enterprise (`FK_glpi_enterprise`),
   KEY deleted (`deleted`),
   KEY is_template (`is_template`),
   KEY date_mod (`date_mod`),
   KEY tech_num (`tech_num`),
   KEY type_2 (`type`),
   KEY name (`name`)
) TYPE=MyISAM;

INSERT INTO glpi_computers VALUES ('1','','','','','','0','Empty Template',NULL,'0','0','0','0','0','0','0','0','0','1','Blank Template','0','N',NULL,'0');

### Dump table glpi_config

DROP TABLE IF EXISTS `glpi_config`;
CREATE TABLE `glpi_config` (
    `ID` int(11) NOT NULL auto_increment,
    `ldap_port` varchar(10) DEFAULT '389' NOT NULL,
    `num_of_events` varchar(200),
    `jobs_at_login` varchar(200),
    `sendexpire` varchar(200),
    `cut` varchar(200),
    `expire_events` varchar(200),
    `list_limit` varchar(200),
    `version` varchar(200),
    `logotxt` varchar(200),
    `root_doc` varchar(200),
    `event_loglevel` varchar(200),
    `mailing` varchar(200),
    `imap_auth_server` varchar(200),
    `imap_host` varchar(200),
    `ldap_host` varchar(200),
    `ldap_basedn` varchar(200),
    `ldap_rootdn` varchar(200),
    `ldap_pass` varchar(200),
    `admin_email` varchar(200),
    `mailing_resa_all_admin` tinyint(4) DEFAULT '0' NOT NULL,
    `mailing_resa_user` tinyint(4) DEFAULT '1' NOT NULL,
    `mailing_resa_admin` tinyint(4) DEFAULT '1' NOT NULL,
    `mailing_signature` varchar(200) DEFAULT '--' NOT NULL,
    `mailing_new_admin` tinyint(4) DEFAULT '1' NOT NULL,
    `mailing_update_admin` tinyint(4) DEFAULT '1' NOT NULL,
    `mailing_followup_admin` tinyint(4) DEFAULT '1' NOT NULL,
    `mailing_finish_admin` tinyint(4) DEFAULT '1' NOT NULL,
    `mailing_new_all_admin` tinyint(4) DEFAULT '0' NOT NULL,
    `mailing_update_all_admin` tinyint(4) DEFAULT '0' NOT NULL,
    `mailing_followup_all_admin` tinyint(4) DEFAULT '0' NOT NULL,
    `mailing_finish_all_admin` tinyint(4) DEFAULT '0' NOT NULL,
    `mailing_new_all_normal` tinyint(4) DEFAULT '0' NOT NULL,
    `mailing_update_all_normal` tinyint(4) DEFAULT '0' NOT NULL,
    `mailing_followup_all_normal` tinyint(4) DEFAULT '0' NOT NULL,
    `mailing_finish_all_normal` tinyint(4) DEFAULT '0' NOT NULL,
    `mailing_new_attrib` tinyint(4) DEFAULT '1' NOT NULL,
    `mailing_update_attrib` tinyint(4) DEFAULT '1' NOT NULL,
    `mailing_followup_attrib` tinyint(4) DEFAULT '1' NOT NULL,
    `mailing_finish_attrib` tinyint(4) DEFAULT '1' NOT NULL,
    `mailing_new_user` tinyint(4) DEFAULT '1' NOT NULL,
    `mailing_update_user` tinyint(4) DEFAULT '1' NOT NULL,
    `mailing_followup_user` tinyint(4) DEFAULT '1' NOT NULL,
    `mailing_finish_user` tinyint(4) DEFAULT '1' NOT NULL,
    `mailing_attrib_attrib` tinyint(4) DEFAULT '1' NOT NULL,
    `ldap_field_name` varchar(200),
    `ldap_field_email` varchar(200),
    `ldap_field_location` varchar(200),
    `ldap_field_realname` varchar(200),
    `ldap_field_phone` varchar(200),
    `ldap_condition` varchar(255),
    `ldap_login` varchar(200) DEFAULT 'uid' NOT NULL,
    `ldap_use_tls` varchar(200) DEFAULT '0' NOT NULL,
    `permit_helpdesk` varchar(200),
    `default_language` varchar(255) DEFAULT 'french' NOT NULL,
    `priority_1` varchar(200) DEFAULT '#fff2f2' NOT NULL,
    `priority_2` varchar(200) DEFAULT '#ffe0e0' NOT NULL,
    `priority_3` varchar(200) DEFAULT '#ffcece' NOT NULL,
    `priority_4` varchar(200) DEFAULT '#ffbfbf' NOT NULL,
    `priority_5` varchar(200) DEFAULT '#ffadad' NOT NULL,
    `date_fiscale` date DEFAULT '2005-12-31' NOT NULL,
    `cartridges_alarm` int(11) DEFAULT '10' NOT NULL,
    `cas_host` varchar(255),
    `cas_port` varchar(255),
    `cas_uri` varchar(255),
    `planning_begin` time DEFAULT '08:00:00' NOT NULL,
    `planning_end` time DEFAULT '20:00:00' NOT NULL,
    `utf8_conv` int(11) DEFAULT '0' NOT NULL,
    `auto_assign` enum('0','1') DEFAULT '0' NOT NULL,
    `public_faq` enum('0','1') DEFAULT '0' NOT NULL,
    `url_base` varchar(255),
    `url_in_mail` enum('0','1') DEFAULT '0' NOT NULL,
    `text_login` text,
    `auto_update_check` smallint(6) DEFAULT '0' NOT NULL,
    `last_update_check` date DEFAULT '2006-02-28' NOT NULL,
    `founded_new_version` varchar(10),
    `dropdown_max` int(11) DEFAULT '100' NOT NULL,
    `ajax_wildcard` char(1) DEFAULT '*' NOT NULL,
    `use_ajax` smallint(6) DEFAULT '0' NOT NULL,
    `ajax_limit_count` int(11) DEFAULT '50' NOT NULL,
    `ajax_autocompletion` smallint(6) DEFAULT '1' NOT NULL,
    `auto_add_users` smallint(6) DEFAULT '1' NOT NULL,
    `dateformat` smallint(6) DEFAULT '0' NOT NULL,
    `nextprev_item` varchar(200) DEFAULT 'name' NOT NULL,
    `view_ID` smallint(6) DEFAULT '0' NOT NULL,
    `dropdown_limit` int(11) DEFAULT '50' NOT NULL,
    `post_only_followup` tinyint(4) DEFAULT '1' NOT NULL,
    `ocs_mode` tinyint(4) DEFAULT '0' NOT NULL,
    `debug` int(2) DEFAULT '0' NOT NULL,
   PRIMARY KEY (`ID`)
) TYPE=MyISAM;

INSERT INTO glpi_config VALUES ('1','389','10','0','1','255','30','15',' 0.65','GLPI powered by indepnet','/glpi','5','0','','','','','','','admsys@xxxxx.fr','0','1','1','SIGNATURE','1','1','1','1','1','0','0','0','0','0','0','0','0','1','0','0','1','1','1','1','1','uid','mail','physicaldeliveryofficename','cn','telephonenumber','','uid','0','','french','#fff2f2','#ffe0e0','#ffcece','#ffbfbf','#ffadad','2005-12-31','10','','','','08:00:00','20:00:00','0','0','0','http://localhost/glpi/','0','','0','2006-02-28','','100','*','0','50','1','1','0','name','0','50','1','0','0');

### Dump table glpi_connect_wire

DROP TABLE IF EXISTS `glpi_connect_wire`;
CREATE TABLE `glpi_connect_wire` (
    `ID` int(11) NOT NULL auto_increment,
    `end1` int(11) DEFAULT '0' NOT NULL,
    `end2` int(11) DEFAULT '0' NOT NULL,
    `type` tinyint(4) DEFAULT '0' NOT NULL,
   PRIMARY KEY (`ID`),
   UNIQUE end1_1 (`end1`, `end2`, `type`),
   KEY end1 (`end1`),
   KEY end2 (`end2`),
   KEY type (`type`)
) TYPE=MyISAM;


### Dump table glpi_consumables

DROP TABLE IF EXISTS `glpi_consumables`;
CREATE TABLE `glpi_consumables` (
    `ID` int(11) NOT NULL auto_increment,
    `FK_glpi_consumables_type` int(11),
    `date_in` date,
    `date_out` date,
    `id_user` int(11) DEFAULT '0' NOT NULL,
   PRIMARY KEY (`ID`),
   KEY FK_glpi_cartridges_type (`FK_glpi_consumables_type`),
   KEY date_in (`date_in`),
   KEY date_out (`date_out`)
) TYPE=MyISAM;


### Dump table glpi_consumables_type

DROP TABLE IF EXISTS `glpi_consumables_type`;
CREATE TABLE `glpi_consumables_type` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255),
    `ref` varchar(255),
    `location` int(11) DEFAULT '0' NOT NULL,
    `type` int(11) DEFAULT '0' NOT NULL,
    `FK_glpi_enterprise` int(11) DEFAULT '0' NOT NULL,
    `tech_num` int(11) DEFAULT '0',
    `deleted` enum('Y','N') DEFAULT 'N' NOT NULL,
    `comments` text,
    `alarm` int(11) DEFAULT '10' NOT NULL,
    `notes` longtext,
   PRIMARY KEY (`ID`),
   KEY FK_glpi_enterprise (`FK_glpi_enterprise`),
   KEY tech_num (`tech_num`),
   KEY deleted (`deleted`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_contact_enterprise

DROP TABLE IF EXISTS `glpi_contact_enterprise`;
CREATE TABLE `glpi_contact_enterprise` (
    `ID` int(11) NOT NULL auto_increment,
    `FK_enterprise` int(11) DEFAULT '0' NOT NULL,
    `FK_contact` int(11) DEFAULT '0' NOT NULL,
   PRIMARY KEY (`ID`),
   UNIQUE FK_enterprise (`FK_enterprise`, `FK_contact`),
   KEY FK_enterprise_2 (`FK_enterprise`),
   KEY FK_contact (`FK_contact`)
) TYPE=MyISAM;


### Dump table glpi_contacts

DROP TABLE IF EXISTS `glpi_contacts`;
CREATE TABLE `glpi_contacts` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255),
    `phone` varchar(200),
    `phone2` varchar(200),
    `fax` varchar(200),
    `email` varchar(255),
    `type` tinyint(4) DEFAULT '1' NOT NULL,
    `comments` text,
    `deleted` enum('Y','N') DEFAULT 'N' NOT NULL,
    `notes` longtext,
   PRIMARY KEY (`ID`),
   KEY deleted (`deleted`),
   KEY type (`type`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_contract_device

DROP TABLE IF EXISTS `glpi_contract_device`;
CREATE TABLE `glpi_contract_device` (
    `ID` int(11) NOT NULL auto_increment,
    `FK_contract` int(11) DEFAULT '0' NOT NULL,
    `FK_device` int(11) DEFAULT '0' NOT NULL,
    `device_type` tinyint(4) DEFAULT '0' NOT NULL,
    `is_template` enum('0','1') DEFAULT '0' NOT NULL,
   PRIMARY KEY (`ID`),
   UNIQUE FK_contract (`FK_contract`, `FK_device`, `device_type`),
   KEY FK_contract_2 (`FK_contract`),
   KEY FK_device (`FK_device`, `device_type`)
) TYPE=MyISAM;


### Dump table glpi_contract_enterprise

DROP TABLE IF EXISTS `glpi_contract_enterprise`;
CREATE TABLE `glpi_contract_enterprise` (
    `ID` int(11) NOT NULL auto_increment,
    `FK_enterprise` int(11) DEFAULT '0' NOT NULL,
    `FK_contract` int(11) DEFAULT '0' NOT NULL,
   PRIMARY KEY (`ID`),
   UNIQUE FK_enterprise (`FK_enterprise`, `FK_contract`),
   KEY FK_enterprise_2 (`FK_enterprise`),
   KEY FK_contract (`FK_contract`)
) TYPE=MyISAM;


### Dump table glpi_contracts

DROP TABLE IF EXISTS `glpi_contracts`;
CREATE TABLE `glpi_contracts` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255),
    `num` varchar(255),
    `cost` float DEFAULT '0' NOT NULL,
    `contract_type` int(11) DEFAULT '0' NOT NULL,
    `begin_date` date,
    `duration` tinyint(4) DEFAULT '0' NOT NULL,
    `notice` tinyint(4) DEFAULT '0' NOT NULL,
    `periodicity` tinyint(4) DEFAULT '0' NOT NULL,
    `facturation` tinyint(4) DEFAULT '0' NOT NULL,
    `bill_type` int(11) DEFAULT '0' NOT NULL,
    `comments` text,
    `compta_num` varchar(255),
    `deleted` enum('Y','N') DEFAULT 'N' NOT NULL,
    `week_begin_hour` time DEFAULT '00:00:00' NOT NULL,
    `week_end_hour` time DEFAULT '00:00:00' NOT NULL,
    `saturday_begin_hour` time DEFAULT '00:00:00' NOT NULL,
    `saturday_end_hour` time DEFAULT '00:00:00' NOT NULL,
    `saturday` enum('Y','N') DEFAULT 'N' NOT NULL,
    `monday_begin_hour` time DEFAULT '00:00:00' NOT NULL,
    `monday_end_hour` time DEFAULT '00:00:00' NOT NULL,
    `monday` enum('Y','N') DEFAULT 'N' NOT NULL,
    `device_countmax` int(11) DEFAULT '0' NOT NULL,
    `notes` longtext,
   PRIMARY KEY (`ID`),
   KEY contract_type (`contract_type`),
   KEY begin_date (`begin_date`),
   KEY bill_type (`bill_type`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_device_case

DROP TABLE IF EXISTS `glpi_device_case`;
CREATE TABLE `glpi_device_case` (
    `ID` int(11) NOT NULL auto_increment,
    `designation` varchar(255),
    `format` enum('Grand','Moyen','Micro') DEFAULT 'Moyen' NOT NULL,
    `comment` text,
    `FK_glpi_enterprise` int(11) DEFAULT '0' NOT NULL,
    `specif_default` varchar(250),
   PRIMARY KEY (`ID`),
   KEY FK_glpi_enterprise (`FK_glpi_enterprise`),
   KEY designation (`designation`)
) TYPE=MyISAM;


### Dump table glpi_device_control

DROP TABLE IF EXISTS `glpi_device_control`;
CREATE TABLE `glpi_device_control` (
    `ID` int(11) NOT NULL auto_increment,
    `designation` varchar(255),
    `interface` enum('IDE','SATA','SCSI','USB') DEFAULT 'IDE' NOT NULL,
    `raid` enum('Y','N') DEFAULT 'Y' NOT NULL,
    `comment` text,
    `FK_glpi_enterprise` int(11) DEFAULT '0' NOT NULL,
    `specif_default` varchar(250),
   PRIMARY KEY (`ID`),
   KEY FK_glpi_enterprise (`FK_glpi_enterprise`),
   KEY designation (`designation`)
) TYPE=MyISAM;


### Dump table glpi_device_drive

DROP TABLE IF EXISTS `glpi_device_drive`;
CREATE TABLE `glpi_device_drive` (
    `ID` int(11) NOT NULL auto_increment,
    `designation` varchar(255),
    `is_writer` enum('Y','N') DEFAULT 'Y' NOT NULL,
    `speed` varchar(30) NOT NULL,
    `interface` enum('IDE','SATA','SCSI') DEFAULT 'IDE' NOT NULL,
    `comment` text,
    `FK_glpi_enterprise` int(11) DEFAULT '0' NOT NULL,
    `specif_default` varchar(250),
   PRIMARY KEY (`ID`),
   KEY FK_glpi_enterprise (`FK_glpi_enterprise`),
   KEY designation (`designation`)
) TYPE=MyISAM;


### Dump table glpi_device_gfxcard

DROP TABLE IF EXISTS `glpi_device_gfxcard`;
CREATE TABLE `glpi_device_gfxcard` (
    `ID` int(11) NOT NULL auto_increment,
    `designation` varchar(255),
    `ram` varchar(10) NOT NULL,
    `interface` enum('AGP','PCI','PCI-X','Other') DEFAULT 'AGP' NOT NULL,
    `comment` text,
    `FK_glpi_enterprise` int(11) DEFAULT '0' NOT NULL,
    `specif_default` varchar(250),
   PRIMARY KEY (`ID`),
   KEY FK_glpi_enterprise (`FK_glpi_enterprise`),
   KEY designation (`designation`)
) TYPE=MyISAM;


### Dump table glpi_device_hdd

DROP TABLE IF EXISTS `glpi_device_hdd`;
CREATE TABLE `glpi_device_hdd` (
    `ID` int(11) NOT NULL auto_increment,
    `designation` varchar(255),
    `rpm` varchar(20) NOT NULL,
    `interface` int(11) DEFAULT '0' NOT NULL,
    `cache` varchar(20) NOT NULL,
    `comment` text,
    `FK_glpi_enterprise` int(11) DEFAULT '0' NOT NULL,
    `specif_default` varchar(250),
   PRIMARY KEY (`ID`),
   KEY FK_glpi_enterprise (`FK_glpi_enterprise`),
   KEY designation (`designation`)
) TYPE=MyISAM;


### Dump table glpi_device_iface

DROP TABLE IF EXISTS `glpi_device_iface`;
CREATE TABLE `glpi_device_iface` (
    `ID` int(11) NOT NULL auto_increment,
    `designation` varchar(255),
    `bandwidth` varchar(20) NOT NULL,
    `comment` text,
    `FK_glpi_enterprise` int(11) DEFAULT '0' NOT NULL,
    `specif_default` varchar(250),
   PRIMARY KEY (`ID`),
   KEY FK_glpi_enterprise (`FK_glpi_enterprise`),
   KEY designation (`designation`)
) TYPE=MyISAM;


### Dump table glpi_device_moboard

DROP TABLE IF EXISTS `glpi_device_moboard`;
CREATE TABLE `glpi_device_moboard` (
    `ID` int(11) NOT NULL auto_increment,
    `designation` varchar(255),
    `chipset` varchar(120) NOT NULL,
    `comment` text,
    `FK_glpi_enterprise` int(11) DEFAULT '0' NOT NULL,
    `specif_default` varchar(250),
   PRIMARY KEY (`ID`),
   KEY FK_glpi_enterprise (`FK_glpi_enterprise`),
   KEY designation (`designation`)
) TYPE=MyISAM;


### Dump table glpi_device_pci

DROP TABLE IF EXISTS `glpi_device_pci`;
CREATE TABLE `glpi_device_pci` (
    `ID` int(11) NOT NULL auto_increment,
    `designation` varchar(255),
    `comment` text,
    `FK_glpi_enterprise` int(11) DEFAULT '0' NOT NULL,
    `specif_default` varchar(250),
   PRIMARY KEY (`ID`),
   KEY FK_glpi_enterprise (`FK_glpi_enterprise`),
   KEY designation (`designation`)
) TYPE=MyISAM;


### Dump table glpi_device_power

DROP TABLE IF EXISTS `glpi_device_power`;
CREATE TABLE `glpi_device_power` (
    `ID` int(11) NOT NULL auto_increment,
    `designation` varchar(255),
    `power` varchar(20) NOT NULL,
    `atx` enum('Y','N') DEFAULT 'Y' NOT NULL,
    `comment` text,
    `FK_glpi_enterprise` int(11) DEFAULT '0' NOT NULL,
    `specif_default` varchar(250),
   PRIMARY KEY (`ID`),
   KEY FK_glpi_enterprise (`FK_glpi_enterprise`),
   KEY designation (`designation`)
) TYPE=MyISAM;


### Dump table glpi_device_processor

DROP TABLE IF EXISTS `glpi_device_processor`;
CREATE TABLE `glpi_device_processor` (
    `ID` int(11) NOT NULL auto_increment,
    `designation` varchar(255),
    `frequence` int(11) DEFAULT '0' NOT NULL,
    `comment` text,
    `FK_glpi_enterprise` int(11) DEFAULT '0' NOT NULL,
    `specif_default` varchar(250),
   PRIMARY KEY (`ID`),
   KEY FK_glpi_enterprise (`FK_glpi_enterprise`),
   KEY designation (`designation`)
) TYPE=MyISAM;


### Dump table glpi_device_ram

DROP TABLE IF EXISTS `glpi_device_ram`;
CREATE TABLE `glpi_device_ram` (
    `ID` int(11) NOT NULL auto_increment,
    `designation` varchar(255),
    `frequence` varchar(8) NOT NULL,
    `comment` text,
    `FK_glpi_enterprise` int(11) DEFAULT '0' NOT NULL,
    `specif_default` varchar(250),
    `type` int(11) DEFAULT '0' NOT NULL,
   PRIMARY KEY (`ID`),
   KEY FK_glpi_enterprise (`FK_glpi_enterprise`),
   KEY designation (`designation`)
) TYPE=MyISAM;


### Dump table glpi_device_sndcard

DROP TABLE IF EXISTS `glpi_device_sndcard`;
CREATE TABLE `glpi_device_sndcard` (
    `ID` int(11) NOT NULL auto_increment,
    `designation` varchar(255),
    `type` varchar(100) NOT NULL,
    `comment` text,
    `FK_glpi_enterprise` int(11) DEFAULT '0' NOT NULL,
    `specif_default` varchar(250),
   PRIMARY KEY (`ID`),
   KEY FK_glpi_enterprise (`FK_glpi_enterprise`),
   KEY designation (`designation`)
) TYPE=MyISAM;


### Dump table glpi_display

DROP TABLE IF EXISTS `glpi_display`;
CREATE TABLE `glpi_display` (
    `ID` int(11) NOT NULL auto_increment,
    `type` smallint(6) DEFAULT '0' NOT NULL,
    `num` smallint(6) DEFAULT '0' NOT NULL,
    `rank` smallint(6) DEFAULT '0' NOT NULL,
   PRIMARY KEY (`ID`),
   UNIQUE type_2 (`type`, `num`),
   KEY type (`type`),
   KEY rank (`rank`),
   KEY num (`num`)
) TYPE=MyISAM;

INSERT INTO glpi_display VALUES ('32','1','4','4');
INSERT INTO glpi_display VALUES ('34','1','6','6');
INSERT INTO glpi_display VALUES ('33','1','5','5');
INSERT INTO glpi_display VALUES ('31','1','8','3');
INSERT INTO glpi_display VALUES ('30','1','23','2');
INSERT INTO glpi_display VALUES ('86','12','3','1');
INSERT INTO glpi_display VALUES ('49','4','31','1');
INSERT INTO glpi_display VALUES ('50','4','23','2');
INSERT INTO glpi_display VALUES ('51','4','3','3');
INSERT INTO glpi_display VALUES ('52','4','4','4');
INSERT INTO glpi_display VALUES ('44','3','31','1');
INSERT INTO glpi_display VALUES ('38','2','31','1');
INSERT INTO glpi_display VALUES ('39','2','23','2');
INSERT INTO glpi_display VALUES ('45','3','23','2');
INSERT INTO glpi_display VALUES ('46','3','3','3');
INSERT INTO glpi_display VALUES ('63','6','4','3');
INSERT INTO glpi_display VALUES ('62','6','5','2');
INSERT INTO glpi_display VALUES ('61','6','23','1');
INSERT INTO glpi_display VALUES ('83','11','4','2');
INSERT INTO glpi_display VALUES ('82','11','3','1');
INSERT INTO glpi_display VALUES ('57','5','3','3');
INSERT INTO glpi_display VALUES ('56','5','23','2');
INSERT INTO glpi_display VALUES ('55','5','31','1');
INSERT INTO glpi_display VALUES ('29','1','31','1');
INSERT INTO glpi_display VALUES ('35','1','3','7');
INSERT INTO glpi_display VALUES ('36','1','19','8');
INSERT INTO glpi_display VALUES ('37','1','17','9');
INSERT INTO glpi_display VALUES ('40','2','3','3');
INSERT INTO glpi_display VALUES ('41','2','4','4');
INSERT INTO glpi_display VALUES ('42','2','11','6');
INSERT INTO glpi_display VALUES ('43','2','9','7');
INSERT INTO glpi_display VALUES ('47','3','4','4');
INSERT INTO glpi_display VALUES ('48','3','9','6');
INSERT INTO glpi_display VALUES ('53','4','9','6');
INSERT INTO glpi_display VALUES ('54','4','7','7');
INSERT INTO glpi_display VALUES ('58','5','4','4');
INSERT INTO glpi_display VALUES ('59','5','9','6');
INSERT INTO glpi_display VALUES ('60','5','7','7');
INSERT INTO glpi_display VALUES ('64','7','3','1');
INSERT INTO glpi_display VALUES ('65','7','4','2');
INSERT INTO glpi_display VALUES ('66','7','5','3');
INSERT INTO glpi_display VALUES ('67','7','6','4');
INSERT INTO glpi_display VALUES ('68','7','9','5');
INSERT INTO glpi_display VALUES ('69','8','9','1');
INSERT INTO glpi_display VALUES ('70','8','3','2');
INSERT INTO glpi_display VALUES ('71','8','4','3');
INSERT INTO glpi_display VALUES ('72','8','5','4');
INSERT INTO glpi_display VALUES ('73','8','10','5');
INSERT INTO glpi_display VALUES ('74','8','6','6');
INSERT INTO glpi_display VALUES ('75','10','4','1');
INSERT INTO glpi_display VALUES ('76','10','3','2');
INSERT INTO glpi_display VALUES ('77','10','5','3');
INSERT INTO glpi_display VALUES ('78','10','6','4');
INSERT INTO glpi_display VALUES ('79','10','7','5');
INSERT INTO glpi_display VALUES ('80','10','11','6');
INSERT INTO glpi_display VALUES ('84','11','5','3');
INSERT INTO glpi_display VALUES ('85','11','6','4');
INSERT INTO glpi_display VALUES ('88','12','6','2');
INSERT INTO glpi_display VALUES ('89','12','4','3');
INSERT INTO glpi_display VALUES ('90','12','5','4');
INSERT INTO glpi_display VALUES ('91','13','3','1');
INSERT INTO glpi_display VALUES ('92','13','4','2');
INSERT INTO glpi_display VALUES ('93','13','7','3');
INSERT INTO glpi_display VALUES ('94','13','5','4');
INSERT INTO glpi_display VALUES ('95','13','6','5');
INSERT INTO glpi_display VALUES ('96','15','3','1');
INSERT INTO glpi_display VALUES ('97','15','4','2');
INSERT INTO glpi_display VALUES ('98','15','5','3');
INSERT INTO glpi_display VALUES ('99','15','6','4');
INSERT INTO glpi_display VALUES ('100','15','7','5');
INSERT INTO glpi_display VALUES ('101','17','3','1');
INSERT INTO glpi_display VALUES ('102','17','4','2');
INSERT INTO glpi_display VALUES ('103','17','5','3');
INSERT INTO glpi_display VALUES ('104','17','6','4');
INSERT INTO glpi_display VALUES ('105','2','40','5');
INSERT INTO glpi_display VALUES ('106','3','40','5');
INSERT INTO glpi_display VALUES ('107','4','40','5');
INSERT INTO glpi_display VALUES ('108','5','40','5');
INSERT INTO glpi_display VALUES ('109','15','8','6');
INSERT INTO glpi_display VALUES ('110','23','31','1');
INSERT INTO glpi_display VALUES ('111','23','23','2');
INSERT INTO glpi_display VALUES ('112','23','3','3');
INSERT INTO glpi_display VALUES ('113','23','4','4');
INSERT INTO glpi_display VALUES ('114','23','40','5');
INSERT INTO glpi_display VALUES ('115','23','9','6');
INSERT INTO glpi_display VALUES ('116','23','7','7');

### Dump table glpi_doc_device

DROP TABLE IF EXISTS `glpi_doc_device`;
CREATE TABLE `glpi_doc_device` (
    `ID` int(11) NOT NULL auto_increment,
    `FK_doc` int(11) DEFAULT '0' NOT NULL,
    `FK_device` int(11) DEFAULT '0' NOT NULL,
    `device_type` tinyint(4) DEFAULT '0' NOT NULL,
    `is_template` enum('0','1') DEFAULT '0' NOT NULL,
   PRIMARY KEY (`ID`),
   UNIQUE FK_doc (`FK_doc`, `FK_device`, `device_type`),
   KEY FK_doc_2 (`FK_doc`),
   KEY FK_device (`FK_device`, `device_type`),
   KEY is_template (`is_template`)
) TYPE=MyISAM;


### Dump table glpi_docs

DROP TABLE IF EXISTS `glpi_docs`;
CREATE TABLE `glpi_docs` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255),
    `filename` varchar(255),
    `rubrique` int(11) DEFAULT '0' NOT NULL,
    `mime` varchar(30),
    `date_mod` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    `comment` text,
    `deleted` enum('Y','N') DEFAULT 'N' NOT NULL,
    `link` varchar(255),
    `notes` longtext,
   PRIMARY KEY (`ID`),
   KEY rubrique (`rubrique`),
   KEY deleted (`deleted`),
   KEY date_mod (`date_mod`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_dropdown_auto_update

DROP TABLE IF EXISTS `glpi_dropdown_auto_update`;
CREATE TABLE `glpi_dropdown_auto_update` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255) NOT NULL,
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_dropdown_budget

DROP TABLE IF EXISTS `glpi_dropdown_budget`;
CREATE TABLE `glpi_dropdown_budget` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255) NOT NULL,
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_dropdown_cartridge_type

DROP TABLE IF EXISTS `glpi_dropdown_cartridge_type`;
CREATE TABLE `glpi_dropdown_cartridge_type` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255) NOT NULL,
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_dropdown_consumable_type

DROP TABLE IF EXISTS `glpi_dropdown_consumable_type`;
CREATE TABLE `glpi_dropdown_consumable_type` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255) NOT NULL,
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_dropdown_contact_type

DROP TABLE IF EXISTS `glpi_dropdown_contact_type`;
CREATE TABLE `glpi_dropdown_contact_type` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255) NOT NULL,
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_dropdown_contract_type

DROP TABLE IF EXISTS `glpi_dropdown_contract_type`;
CREATE TABLE `glpi_dropdown_contract_type` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255) NOT NULL,
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY name (`name`)
) TYPE=MyISAM;

INSERT INTO glpi_dropdown_contract_type VALUES ('1','Pr&#234;t',NULL);
INSERT INTO glpi_dropdown_contract_type VALUES ('2','Location',NULL);
INSERT INTO glpi_dropdown_contract_type VALUES ('3','Leasing',NULL);
INSERT INTO glpi_dropdown_contract_type VALUES ('4','Assurances',NULL);
INSERT INTO glpi_dropdown_contract_type VALUES ('5','Maintenance Hardware',NULL);
INSERT INTO glpi_dropdown_contract_type VALUES ('6','Maintenance Software',NULL);
INSERT INTO glpi_dropdown_contract_type VALUES ('7','Prestation',NULL);

### Dump table glpi_dropdown_domain

DROP TABLE IF EXISTS `glpi_dropdown_domain`;
CREATE TABLE `glpi_dropdown_domain` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255) NOT NULL,
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_dropdown_enttype

DROP TABLE IF EXISTS `glpi_dropdown_enttype`;
CREATE TABLE `glpi_dropdown_enttype` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255) NOT NULL,
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_dropdown_firmware

DROP TABLE IF EXISTS `glpi_dropdown_firmware`;
CREATE TABLE `glpi_dropdown_firmware` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255) NOT NULL,
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_dropdown_hdd_type

DROP TABLE IF EXISTS `glpi_dropdown_hdd_type`;
CREATE TABLE `glpi_dropdown_hdd_type` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255) NOT NULL,
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY name (`name`)
) TYPE=MyISAM;

INSERT INTO glpi_dropdown_hdd_type VALUES ('1','IDE',NULL);
INSERT INTO glpi_dropdown_hdd_type VALUES ('2','SATA',NULL);
INSERT INTO glpi_dropdown_hdd_type VALUES ('3','SCSI',NULL);

### Dump table glpi_dropdown_iface

DROP TABLE IF EXISTS `glpi_dropdown_iface`;
CREATE TABLE `glpi_dropdown_iface` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255),
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_dropdown_kbcategories

DROP TABLE IF EXISTS `glpi_dropdown_kbcategories`;
CREATE TABLE `glpi_dropdown_kbcategories` (
    `ID` int(11) NOT NULL auto_increment,
    `parentID` int(11) DEFAULT '0' NOT NULL,
    `name` varchar(255) NOT NULL,
    `completename` text NOT NULL,
    `comments` text,
   PRIMARY KEY (`ID`),
   UNIQUE parentID_2 (`parentID`, `name`),
   KEY parentID (`parentID`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_dropdown_locations

DROP TABLE IF EXISTS `glpi_dropdown_locations`;
CREATE TABLE `glpi_dropdown_locations` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255) NOT NULL,
    `parentID` int(11) DEFAULT '0' NOT NULL,
    `completename` text NOT NULL,
    `comments` text,
   PRIMARY KEY (`ID`),
   UNIQUE name (`name`, `parentID`),
   KEY parentID (`parentID`)
) TYPE=MyISAM;


### Dump table glpi_dropdown_model

DROP TABLE IF EXISTS `glpi_dropdown_model`;
CREATE TABLE `glpi_dropdown_model` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255),
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_dropdown_model_monitors

DROP TABLE IF EXISTS `glpi_dropdown_model_monitors`;
CREATE TABLE `glpi_dropdown_model_monitors` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255) NOT NULL,
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_dropdown_model_networking

DROP TABLE IF EXISTS `glpi_dropdown_model_networking`;
CREATE TABLE `glpi_dropdown_model_networking` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255) NOT NULL,
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_dropdown_model_peripherals

DROP TABLE IF EXISTS `glpi_dropdown_model_peripherals`;
CREATE TABLE `glpi_dropdown_model_peripherals` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255) NOT NULL,
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_dropdown_model_phones

DROP TABLE IF EXISTS `glpi_dropdown_model_phones`;
CREATE TABLE `glpi_dropdown_model_phones` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255) NOT NULL,
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_dropdown_model_printers

DROP TABLE IF EXISTS `glpi_dropdown_model_printers`;
CREATE TABLE `glpi_dropdown_model_printers` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255) NOT NULL,
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_dropdown_netpoint

DROP TABLE IF EXISTS `glpi_dropdown_netpoint`;
CREATE TABLE `glpi_dropdown_netpoint` (
    `ID` int(11) NOT NULL auto_increment,
    `location` int(11) DEFAULT '0' NOT NULL,
    `name` varchar(255) NOT NULL,
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY location (`location`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_dropdown_network

DROP TABLE IF EXISTS `glpi_dropdown_network`;
CREATE TABLE `glpi_dropdown_network` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255) NOT NULL,
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_dropdown_os

DROP TABLE IF EXISTS `glpi_dropdown_os`;
CREATE TABLE `glpi_dropdown_os` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255),
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_dropdown_os_sp

DROP TABLE IF EXISTS `glpi_dropdown_os_sp`;
CREATE TABLE `glpi_dropdown_os_sp` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255) NOT NULL,
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_dropdown_os_version

DROP TABLE IF EXISTS `glpi_dropdown_os_version`;
CREATE TABLE `glpi_dropdown_os_version` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255) NOT NULL,
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_dropdown_phone_power

DROP TABLE IF EXISTS `glpi_dropdown_phone_power`;
CREATE TABLE `glpi_dropdown_phone_power` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255) NOT NULL,
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_dropdown_ram_type

DROP TABLE IF EXISTS `glpi_dropdown_ram_type`;
CREATE TABLE `glpi_dropdown_ram_type` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255) NOT NULL,
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY name (`name`)
) TYPE=MyISAM;

INSERT INTO glpi_dropdown_ram_type VALUES ('1','EDO',NULL);
INSERT INTO glpi_dropdown_ram_type VALUES ('2','DDR',NULL);
INSERT INTO glpi_dropdown_ram_type VALUES ('3','SDRAM',NULL);
INSERT INTO glpi_dropdown_ram_type VALUES ('4','SDRAM-2',NULL);

### Dump table glpi_dropdown_rubdocs

DROP TABLE IF EXISTS `glpi_dropdown_rubdocs`;
CREATE TABLE `glpi_dropdown_rubdocs` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255),
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_dropdown_state

DROP TABLE IF EXISTS `glpi_dropdown_state`;
CREATE TABLE `glpi_dropdown_state` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255),
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_dropdown_tracking_category

DROP TABLE IF EXISTS `glpi_dropdown_tracking_category`;
CREATE TABLE `glpi_dropdown_tracking_category` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255),
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_dropdown_vlan

DROP TABLE IF EXISTS `glpi_dropdown_vlan`;
CREATE TABLE `glpi_dropdown_vlan` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255) NOT NULL,
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_enterprises

DROP TABLE IF EXISTS `glpi_enterprises`;
CREATE TABLE `glpi_enterprises` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(200),
    `type` int(11) DEFAULT '0' NOT NULL,
    `address` text,
    `website` varchar(200),
    `phonenumber` varchar(200),
    `comments` text,
    `deleted` enum('Y','N') DEFAULT 'N' NOT NULL,
    `fax` varchar(255),
    `email` varchar(255),
    `notes` longtext,
   PRIMARY KEY (`ID`),
   KEY deleted (`deleted`),
   KEY type (`type`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_event_log

DROP TABLE IF EXISTS `glpi_event_log`;
CREATE TABLE `glpi_event_log` (
    `ID` int(11) NOT NULL auto_increment,
    `item` int(11) DEFAULT '0' NOT NULL,
    `itemtype` varchar(200),
    `date` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    `service` varchar(200),
    `level` tinyint(4) DEFAULT '0' NOT NULL,
    `message` text,
   PRIMARY KEY (`ID`),
   KEY comp (`item`),
   KEY date (`date`),
   KEY itemtype (`itemtype`)
) TYPE=MyISAM;

INSERT INTO glpi_event_log VALUES ('2','-1','system','2006-02-28 22:04:17','login','3','glpi connexion de l\'IP : 127.0.0.1');

### Dump table glpi_followups

DROP TABLE IF EXISTS `glpi_followups`;
CREATE TABLE `glpi_followups` (
    `ID` int(11) NOT NULL auto_increment,
    `tracking` int(11),
    `date` datetime,
    `author` int(11) DEFAULT '0' NOT NULL,
    `contents` text,
    `private` int(1) DEFAULT '0' NOT NULL,
    `realtime` float DEFAULT '0' NOT NULL,
   PRIMARY KEY (`ID`),
   KEY tracking (`tracking`),
   KEY author (`author`),
   KEY date (`date`)
) TYPE=MyISAM;


### Dump table glpi_history

DROP TABLE IF EXISTS `glpi_history`;
CREATE TABLE `glpi_history` (
    `ID` int(11) NOT NULL auto_increment,
    `FK_glpi_device` int(11) DEFAULT '0' NOT NULL,
    `device_type` tinyint(4) DEFAULT '0' NOT NULL,
    `device_internal_type` int(11) DEFAULT '0',
    `device_internal_action` tinyint(4) DEFAULT '0',
    `user_name` varchar(200),
    `date_mod` datetime,
    `id_search_option` int(11) DEFAULT '0' NOT NULL,
    `old_value` varchar(255),
    `new_value` varchar(255),
   PRIMARY KEY (`ID`),
   KEY FK_glpi_device (`FK_glpi_device`)
) TYPE=MyISAM;


### Dump table glpi_infocoms

DROP TABLE IF EXISTS `glpi_infocoms`;
CREATE TABLE `glpi_infocoms` (
    `ID` int(11) NOT NULL auto_increment,
    `FK_device` int(11) DEFAULT '0' NOT NULL,
    `device_type` tinyint(4) DEFAULT '0' NOT NULL,
    `buy_date` date DEFAULT '0000-00-00' NOT NULL,
    `use_date` date DEFAULT '0000-00-00' NOT NULL,
    `warranty_duration` tinyint(4) DEFAULT '0' NOT NULL,
    `warranty_info` varchar(255),
    `FK_enterprise` int(11),
    `num_commande` varchar(200),
    `bon_livraison` varchar(200),
    `num_immo` varchar(200),
    `value` float DEFAULT '0' NOT NULL,
    `warranty_value` float DEFAULT '0' NOT NULL,
    `amort_time` tinyint(4) DEFAULT '0' NOT NULL,
    `amort_type` tinyint(4) DEFAULT '0' NOT NULL,
    `amort_coeff` float DEFAULT '0' NOT NULL,
    `comments` text,
    `facture` varchar(200),
    `budget` int(11) DEFAULT '0',
   PRIMARY KEY (`ID`),
   UNIQUE FK_device (`FK_device`, `device_type`),
   KEY FK_enterprise (`FK_enterprise`),
   KEY buy_date (`buy_date`)
) TYPE=MyISAM;


### Dump table glpi_inst_software

DROP TABLE IF EXISTS `glpi_inst_software`;
CREATE TABLE `glpi_inst_software` (
    `ID` int(11) NOT NULL auto_increment,
    `cID` int(11) DEFAULT '0' NOT NULL,
    `license` int(11) DEFAULT '0' NOT NULL,
   PRIMARY KEY (`ID`),
   KEY cID (`cID`),
   KEY sID (`license`)
) TYPE=MyISAM;


### Dump table glpi_kbitems

DROP TABLE IF EXISTS `glpi_kbitems`;
CREATE TABLE `glpi_kbitems` (
    `ID` int(11) NOT NULL auto_increment,
    `categoryID` int(11) DEFAULT '0' NOT NULL,
    `question` text,
    `answer` text,
    `faq` enum('yes','no') DEFAULT 'no' NOT NULL,
   PRIMARY KEY (`ID`),
   KEY categoryID (`categoryID`)
) TYPE=MyISAM;


### Dump table glpi_licenses

DROP TABLE IF EXISTS `glpi_licenses`;
CREATE TABLE `glpi_licenses` (
    `ID` int(15) NOT NULL auto_increment,
    `sID` int(15) DEFAULT '0' NOT NULL,
    `serial` varchar(255),
    `expire` date,
    `oem` enum('N','Y') DEFAULT 'N' NOT NULL,
    `oem_computer` int(11) DEFAULT '0' NOT NULL,
    `buy` enum('Y','N') DEFAULT 'Y' NOT NULL,
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY sID (`sID`)
) TYPE=MyISAM;


### Dump table glpi_links

DROP TABLE IF EXISTS `glpi_links`;
CREATE TABLE `glpi_links` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255),
    `data` text,
   PRIMARY KEY (`ID`)
) TYPE=MyISAM;


### Dump table glpi_links_device

DROP TABLE IF EXISTS `glpi_links_device`;
CREATE TABLE `glpi_links_device` (
    `ID` int(11) NOT NULL auto_increment,
    `FK_links` int(11) DEFAULT '0' NOT NULL,
    `device_type` int(11) DEFAULT '0' NOT NULL,
   PRIMARY KEY (`ID`),
   UNIQUE device_type_2 (`device_type`, `FK_links`),
   KEY device_type (`device_type`),
   KEY FK_links (`FK_links`)
) TYPE=MyISAM;


### Dump table glpi_monitors

DROP TABLE IF EXISTS `glpi_monitors`;
CREATE TABLE `glpi_monitors` (
    `ID` int(10) NOT NULL auto_increment,
    `name` varchar(200),
    `date_mod` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    `contact` varchar(200),
    `contact_num` varchar(200),
    `tech_num` int(11) DEFAULT '0' NOT NULL,
    `comments` text,
    `serial` varchar(200),
    `otherserial` varchar(200),
    `size` int(3) DEFAULT '0' NOT NULL,
    `flags_micro` tinyint(4) DEFAULT '0' NOT NULL,
    `flags_speaker` tinyint(4) DEFAULT '0' NOT NULL,
    `flags_subd` tinyint(4) DEFAULT '0' NOT NULL,
    `flags_bnc` tinyint(4) DEFAULT '0' NOT NULL,
    `flags_dvi` tinyint(4) DEFAULT '0' NOT NULL,
    `location` int(11) DEFAULT '0' NOT NULL,
    `type` int(11),
    `model` int(11),
    `FK_glpi_enterprise` int(11) DEFAULT '0' NOT NULL,
    `is_global` enum('0','1') DEFAULT '0' NOT NULL,
    `deleted` enum('Y','N') DEFAULT 'N' NOT NULL,
    `is_template` enum('0','1') DEFAULT '0' NOT NULL,
    `tplname` varchar(255),
    `notes` longtext,
   PRIMARY KEY (`ID`),
   KEY ID (`ID`),
   KEY type (`type`),
   KEY location (`location`),
   KEY FK_glpi_enterprise (`FK_glpi_enterprise`),
   KEY deleted (`deleted`),
   KEY is_template (`is_template`),
   KEY tech_num (`tech_num`),
   KEY name (`name`)
) TYPE=MyISAM;

INSERT INTO glpi_monitors VALUES ('1','','0000-00-00 00:00:00','','','0','','','','0','0','0','0','0','0','0',NULL,NULL,'0','0','N','1','Blank Template',NULL);

### Dump table glpi_networking

DROP TABLE IF EXISTS `glpi_networking`;
CREATE TABLE `glpi_networking` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(200),
    `ram` varchar(200),
    `serial` varchar(200),
    `otherserial` varchar(200),
    `contact` varchar(200),
    `contact_num` varchar(200),
    `tech_num` int(11) DEFAULT '0' NOT NULL,
    `date_mod` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    `comments` text,
    `location` int(11) DEFAULT '0' NOT NULL,
    `domain` int(11) DEFAULT '0' NOT NULL,
    `network` int(11) DEFAULT '0' NOT NULL,
    `type` int(11),
    `model` int(11),
    `firmware` int(11),
    `FK_glpi_enterprise` int(11) DEFAULT '0' NOT NULL,
    `deleted` enum('Y','N') DEFAULT 'N' NOT NULL,
    `is_template` enum('0','1') DEFAULT '0' NOT NULL,
    `tplname` varchar(255),
    `ifmac` varchar(200),
    `ifaddr` varchar(200),
    `notes` longtext,
   PRIMARY KEY (`ID`),
   KEY location (`location`),
   KEY type (`type`),
   KEY firmware (`firmware`),
   KEY FK_glpi_enterprise (`FK_glpi_enterprise`),
   KEY deleted (`deleted`),
   KEY is_template (`is_template`),
   KEY tech_num (`tech_num`),
   KEY name (`name`)
) TYPE=MyISAM;

INSERT INTO glpi_networking VALUES ('1','','','','','','','0','0000-00-00 00:00:00','','0','0','0',NULL,NULL,NULL,'0','N','1','Blank Template','','',NULL);

### Dump table glpi_networking_ports

DROP TABLE IF EXISTS `glpi_networking_ports`;
CREATE TABLE `glpi_networking_ports` (
    `ID` int(11) NOT NULL auto_increment,
    `on_device` int(11) DEFAULT '0' NOT NULL,
    `device_type` tinyint(4) DEFAULT '0' NOT NULL,
    `logical_number` int(11) DEFAULT '0' NOT NULL,
    `name` char(200),
    `ifaddr` char(200),
    `ifmac` char(200),
    `iface` int(11),
    `netpoint` int(11),
   PRIMARY KEY (`ID`),
   KEY on_device (`on_device`, `device_type`),
   KEY netpoint (`netpoint`),
   KEY on_device_2 (`on_device`),
   KEY device_type (`device_type`)
) TYPE=MyISAM;


### Dump table glpi_networking_vlan

DROP TABLE IF EXISTS `glpi_networking_vlan`;
CREATE TABLE `glpi_networking_vlan` (
    `ID` int(11) NOT NULL auto_increment,
    `FK_port` int(11) DEFAULT '0' NOT NULL,
    `FK_vlan` int(11) DEFAULT '0' NOT NULL,
   PRIMARY KEY (`ID`),
   UNIQUE FK_port_2 (`FK_port`, `FK_vlan`),
   KEY FK_port (`FK_port`),
   KEY FK_vlan (`FK_vlan`)
) TYPE=MyISAM;


### Dump table glpi_networking_wire

DROP TABLE IF EXISTS `glpi_networking_wire`;
CREATE TABLE `glpi_networking_wire` (
    `ID` int(11) NOT NULL auto_increment,
    `end1` int(11) DEFAULT '0' NOT NULL,
    `end2` int(11) DEFAULT '0' NOT NULL,
   PRIMARY KEY (`ID`),
   UNIQUE end1_1 (`end1`, `end2`),
   KEY end1 (`end1`),
   KEY end2 (`end2`)
) TYPE=MyISAM;


### Dump table glpi_ocs_config

DROP TABLE IF EXISTS `glpi_ocs_config`;
CREATE TABLE `glpi_ocs_config` (
    `ID` int(11) NOT NULL auto_increment,
    `ocs_db_user` varchar(255) NOT NULL,
    `ocs_db_passwd` varchar(255) NOT NULL,
    `ocs_db_host` varchar(255) NOT NULL,
    `ocs_db_name` varchar(255) NOT NULL,
    `checksum` int(11) DEFAULT '0' NOT NULL,
    `import_periph` int(2) DEFAULT '0' NOT NULL,
    `import_monitor` int(2) DEFAULT '0' NOT NULL,
    `import_software` int(2) DEFAULT '0' NOT NULL,
    `import_printer` int(2) DEFAULT '0' NOT NULL,
    `import_general_os` int(2) DEFAULT '0' NOT NULL,
    `import_general_serial` int(2) DEFAULT '0' NOT NULL,
    `import_general_model` int(2) DEFAULT '0' NOT NULL,
    `import_general_enterprise` int(2) DEFAULT '0' NOT NULL,
    `import_general_type` int(2) DEFAULT '0' NOT NULL,
    `import_general_domain` int(2) DEFAULT '0' NOT NULL,
    `import_general_contact` int(2) DEFAULT '0' NOT NULL,
    `import_general_comments` int(2) DEFAULT '0' NOT NULL,
    `import_device_processor` int(2) DEFAULT '0' NOT NULL,
    `import_device_memory` int(2) DEFAULT '0' NOT NULL,
    `import_device_hdd` int(2) DEFAULT '0' NOT NULL,
    `import_device_iface` int(2) DEFAULT '0' NOT NULL,
    `import_device_gfxcard` int(2) DEFAULT '0' NOT NULL,
    `import_device_sound` int(2) DEFAULT '0' NOT NULL,
    `import_device_drives` int(2) DEFAULT '0' NOT NULL,
    `import_device_ports` int(2) DEFAULT '0' NOT NULL,
    `import_device_modems` int(2) DEFAULT '0' NOT NULL,
    `import_ip` int(2) DEFAULT '0' NOT NULL,
    `default_state` int(11) DEFAULT '0' NOT NULL,
    `tag_limit` varchar(255) NOT NULL,
   PRIMARY KEY (`ID`)
) TYPE=MyISAM;

INSERT INTO glpi_ocs_config VALUES ('1','ocs','ocs','localhost','ocsweb','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','');

### Dump table glpi_ocs_link

DROP TABLE IF EXISTS `glpi_ocs_link`;
CREATE TABLE `glpi_ocs_link` (
    `ID` int(11) NOT NULL auto_increment,
    `glpi_id` int(11) DEFAULT '0' NOT NULL,
    `ocs_id` varchar(255) NOT NULL,
    `auto_update` int(2) DEFAULT '1' NOT NULL,
    `last_update` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    `computer_update` longtext,
    `import_device` longtext,
    `import_software` longtext,
    `import_monitor` longtext,
    `import_peripheral` longtext,
    `import_printers` longtext,
   PRIMARY KEY (`ID`),
   UNIQUE ocs_id_2 (`ocs_id`),
   KEY ocs_id (`ocs_id`),
   KEY glpi_id (`glpi_id`),
   KEY auto_update (`auto_update`),
   KEY last_update (`last_update`)
) TYPE=MyISAM;


### Dump table glpi_peripherals

DROP TABLE IF EXISTS `glpi_peripherals`;
CREATE TABLE `glpi_peripherals` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(200),
    `date_mod` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    `contact` varchar(200),
    `contact_num` varchar(200),
    `tech_num` int(11) DEFAULT '0' NOT NULL,
    `comments` text,
    `serial` varchar(200),
    `otherserial` varchar(200),
    `location` int(11) DEFAULT '0' NOT NULL,
    `type` int(11) DEFAULT '0' NOT NULL,
    `model` int(11),
    `brand` varchar(200),
    `FK_glpi_enterprise` int(11) DEFAULT '0' NOT NULL,
    `is_global` enum('0','1') DEFAULT '0' NOT NULL,
    `deleted` enum('Y','N') DEFAULT 'N' NOT NULL,
    `is_template` enum('0','1') DEFAULT '0' NOT NULL,
    `tplname` varchar(255),
    `notes` longtext,
   PRIMARY KEY (`ID`),
   KEY type (`type`),
   KEY location (`location`),
   KEY FK_glpi_enterprise (`FK_glpi_enterprise`),
   KEY deleted (`deleted`),
   KEY is_template (`is_template`),
   KEY tech_num (`tech_num`),
   KEY name (`name`)
) TYPE=MyISAM;

INSERT INTO glpi_peripherals VALUES ('1','','0000-00-00 00:00:00','','','0','','','','0','0','0','','0','0','N','1','Blank Template',NULL);

### Dump table glpi_phones

DROP TABLE IF EXISTS `glpi_phones`;
CREATE TABLE `glpi_phones` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255),
    `date_mod` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    `contact` varchar(255),
    `contact_num` varchar(255),
    `tech_num` int(11) DEFAULT '0' NOT NULL,
    `comments` text,
    `serial` varchar(255),
    `otherserial` varchar(255),
    `firmware` varchar(255),
    `location` int(11) DEFAULT '0' NOT NULL,
    `type` int(11) DEFAULT '0' NOT NULL,
    `model` int(11),
    `brand` varchar(255),
    `power` tinyint(4) DEFAULT '0' NOT NULL,
    `number_line` varchar(255) NOT NULL,
    `flags_casque` tinyint(4) DEFAULT '0' NOT NULL,
    `flags_hp` tinyint(4) DEFAULT '0' NOT NULL,
    `FK_glpi_enterprise` int(11) DEFAULT '0' NOT NULL,
    `is_global` enum('0','1') DEFAULT '0' NOT NULL,
    `deleted` enum('Y','N') DEFAULT 'N' NOT NULL,
    `is_template` enum('0','1') DEFAULT '0' NOT NULL,
    `tplname` varchar(255),
    `notes` longtext,
   PRIMARY KEY (`ID`),
   KEY type (`type`),
   KEY name (`name`),
   KEY location (`location`),
   KEY FK_glpi_enterprise (`FK_glpi_enterprise`),
   KEY deleted (`deleted`),
   KEY is_template (`is_template`),
   KEY tech_num (`tech_num`)
) TYPE=MyISAM;

INSERT INTO glpi_phones VALUES ('1',NULL,'0000-00-00 00:00:00',NULL,NULL,'0',NULL,NULL,NULL,NULL,'0','0',NULL,NULL,'0','','0','0','0','0','N','1','Blank Template',NULL);

### Dump table glpi_printers

DROP TABLE IF EXISTS `glpi_printers`;
CREATE TABLE `glpi_printers` (
    `ID` int(10) NOT NULL auto_increment,
    `name` varchar(200),
    `date_mod` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    `contact` varchar(200),
    `contact_num` varchar(200),
    `tech_num` int(11) DEFAULT '0' NOT NULL,
    `serial` varchar(200),
    `otherserial` varchar(200),
    `flags_serial` tinyint(4) DEFAULT '0' NOT NULL,
    `flags_par` tinyint(4) DEFAULT '0' NOT NULL,
    `flags_usb` tinyint(4) DEFAULT '0' NOT NULL,
    `comments` text,
    `ramSize` varchar(200),
    `location` int(11) DEFAULT '0' NOT NULL,
    `domain` int(11) DEFAULT '0' NOT NULL,
    `network` int(11) DEFAULT '0' NOT NULL,
    `type` int(11),
    `model` int(11),
    `FK_glpi_enterprise` int(11) DEFAULT '0' NOT NULL,
    `is_global` enum('0','1') DEFAULT '0' NOT NULL,
    `deleted` enum('Y','N') DEFAULT 'N' NOT NULL,
    `is_template` enum('0','1') DEFAULT '0' NOT NULL,
    `tplname` varchar(255),
    `initial_pages` varchar(30) DEFAULT '0' NOT NULL,
    `notes` longtext,
   PRIMARY KEY (`ID`),
   KEY id (`ID`),
   KEY location (`location`),
   KEY type (`type`),
   KEY FK_glpi_enterprise (`FK_glpi_enterprise`),
   KEY deleted (`deleted`),
   KEY is_template (`is_template`),
   KEY tech_num (`tech_num`),
   KEY name (`name`)
) TYPE=MyISAM;

INSERT INTO glpi_printers VALUES ('1','','0000-00-00 00:00:00','','','0','','','0','0','0','','','0','0','0',NULL,NULL,'0','0','N','1','Blank Template','0',NULL);

### Dump table glpi_reminder

DROP TABLE IF EXISTS `glpi_reminder`;
CREATE TABLE `glpi_reminder` (
    `ID` int(11) NOT NULL auto_increment,
    `date` datetime,
    `author` int(11) DEFAULT '0' NOT NULL,
    `title` text,
    `text` text,
    `type` varchar(50) DEFAULT 'private' NOT NULL,
    `begin` datetime,
    `end` datetime,
    `rv` enum('0','1') DEFAULT '0' NOT NULL,
    `date_mod` datetime,
   PRIMARY KEY (`ID`),
   KEY date (`date`),
   KEY author (`author`),
   KEY rv (`rv`),
   KEY type (`type`)
) TYPE=MyISAM;


### Dump table glpi_repair_item

DROP TABLE IF EXISTS `glpi_repair_item`;
CREATE TABLE `glpi_repair_item` (
    `ID` int(11) NOT NULL auto_increment,
    `device_type` tinyint(4) DEFAULT '0' NOT NULL,
    `id_device` int(11) DEFAULT '0' NOT NULL,
   PRIMARY KEY (`ID`),
   KEY device_type (`device_type`),
   KEY device_type_2 (`device_type`, `id_device`)
) TYPE=MyISAM;


### Dump table glpi_reservation_item

DROP TABLE IF EXISTS `glpi_reservation_item`;
CREATE TABLE `glpi_reservation_item` (
    `ID` int(11) NOT NULL auto_increment,
    `device_type` tinyint(4) DEFAULT '0' NOT NULL,
    `id_device` int(11) DEFAULT '0' NOT NULL,
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY device_type (`device_type`),
   KEY device_type_2 (`device_type`, `id_device`)
) TYPE=MyISAM;


### Dump table glpi_reservation_resa

DROP TABLE IF EXISTS `glpi_reservation_resa`;
CREATE TABLE `glpi_reservation_resa` (
    `ID` bigint(20) NOT NULL auto_increment,
    `id_item` int(11) DEFAULT '0' NOT NULL,
    `begin` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    `end` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    `id_user` int(11) DEFAULT '0' NOT NULL,
    `comment` text,
   PRIMARY KEY (`ID`),
   KEY id_item (`id_item`),
   KEY id_user (`id_user`),
   KEY begin (`begin`),
   KEY end (`end`)
) TYPE=MyISAM;


### Dump table glpi_software

DROP TABLE IF EXISTS `glpi_software`;
CREATE TABLE `glpi_software` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(200),
    `version` varchar(200),
    `comments` text,
    `location` int(11),
    `tech_num` int(11) DEFAULT '0' NOT NULL,
    `platform` int(11),
    `is_update` enum('N','Y') DEFAULT 'N' NOT NULL,
    `update_software` int(11) DEFAULT '-1' NOT NULL,
    `FK_glpi_enterprise` int(11) DEFAULT '0' NOT NULL,
    `deleted` enum('Y','N') DEFAULT 'N' NOT NULL,
    `is_template` enum('0','1') DEFAULT '0' NOT NULL,
    `tplname` varchar(255),
    `date_mod` datetime,
    `notes` longtext,
   PRIMARY KEY (`ID`),
   KEY platform (`platform`),
   KEY location (`location`),
   KEY FK_glpi_enterprise (`FK_glpi_enterprise`),
   KEY deleted (`deleted`),
   KEY is_template (`is_template`),
   KEY date_mod (`date_mod`),
   KEY tech_num (`tech_num`),
   KEY name (`name`)
) TYPE=MyISAM;

INSERT INTO glpi_software VALUES ('1','','',NULL,NULL,'0',NULL,'N','-1','0','N','1','Blank Template',NULL,NULL);

### Dump table glpi_state_item

DROP TABLE IF EXISTS `glpi_state_item`;
CREATE TABLE `glpi_state_item` (
    `ID` int(11) NOT NULL auto_increment,
    `device_type` tinyint(4) DEFAULT '0' NOT NULL,
    `id_device` int(11) DEFAULT '0' NOT NULL,
    `state` int(11) DEFAULT '1',
    `is_template` enum('0','1') DEFAULT '0' NOT NULL,
   PRIMARY KEY (`ID`),
   KEY device_type (`device_type`),
   KEY device_type_2 (`device_type`, `id_device`)
) TYPE=MyISAM;


### Dump table glpi_tracking

DROP TABLE IF EXISTS `glpi_tracking`;
CREATE TABLE `glpi_tracking` (
    `ID` int(11) NOT NULL auto_increment,
    `date` datetime,
    `closedate` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    `status` enum('new','old_done','assign','plan','old_notdone','waiting') DEFAULT 'new' NOT NULL,
    `author` int(11) DEFAULT '0' NOT NULL,
    `assign` int(11) DEFAULT '0' NOT NULL,
    `assign_ent` int(11) DEFAULT '0' NOT NULL,
    `device_type` int(11) DEFAULT '1' NOT NULL,
    `computer` int(11),
    `contents` text,
    `priority` tinyint(4) DEFAULT '1' NOT NULL,
    `is_group` enum('no','yes') DEFAULT 'no' NOT NULL,
    `uemail` varchar(100),
    `emailupdates` enum('yes','no') DEFAULT 'no' NOT NULL,
    `realtime` float DEFAULT '0' NOT NULL,
    `category` int(11) DEFAULT '0' NOT NULL,
    `cost_time` float DEFAULT '0' NOT NULL,
    `cost_fixed` float DEFAULT '0' NOT NULL,
    `cost_material` float DEFAULT '0' NOT NULL,
   PRIMARY KEY (`ID`),
   KEY computer (`computer`),
   KEY author (`author`),
   KEY assign (`assign`),
   KEY date (`date`),
   KEY closedate (`closedate`),
   KEY status (`status`),
   KEY category (`category`)
) TYPE=MyISAM;


### Dump table glpi_tracking_planning

DROP TABLE IF EXISTS `glpi_tracking_planning`;
CREATE TABLE `glpi_tracking_planning` (
    `ID` bigint(20) NOT NULL auto_increment,
    `id_followup` int(11) DEFAULT '0' NOT NULL,
    `id_assign` int(11) DEFAULT '0' NOT NULL,
    `begin` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    `end` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
   PRIMARY KEY (`ID`),
   KEY begin (`begin`),
   KEY end (`end`),
   KEY id_assign (`id_assign`),
   KEY id_followup (`id_followup`)
) TYPE=MyISAM;


### Dump table glpi_type_computers

DROP TABLE IF EXISTS `glpi_type_computers`;
CREATE TABLE `glpi_type_computers` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255) NOT NULL,
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY name (`name`)
) TYPE=MyISAM;

INSERT INTO glpi_type_computers VALUES ('1','Serveur',NULL);

### Dump table glpi_type_docs

DROP TABLE IF EXISTS `glpi_type_docs`;
CREATE TABLE `glpi_type_docs` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255),
    `ext` varchar(10),
    `icon` varchar(255),
    `mime` varchar(100),
    `upload` enum('Y','N') DEFAULT 'Y' NOT NULL,
    `date_mod` datetime,
   PRIMARY KEY (`ID`),
   UNIQUE extension (`ext`),
   KEY upload (`upload`),
   KEY name (`name`)
) TYPE=MyISAM;

INSERT INTO glpi_type_docs VALUES ('1','JPEG','jpg','jpg-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('2','PNG','png','png-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('3','GIF','gif','gif-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('4','BMP','bmp','bmp-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('5','Photoshop','psd','psd-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('6','TIFF','tif','tif-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('7','AIFF','aiff','aiff-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('8','Windows Media','asf','asf-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('9','Windows Media','avi','avi-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('44','C source','c','','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('27','RealAudio','rm','rm-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('16','Midi','mid','mid-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('17','QuickTime','mov','mov-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('18','MP3','mp3','mp3-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('19','MPEG','mpg','mpg-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('20','Ogg Vorbis','ogg','ogg-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('24','QuickTime','qt','qt-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('10','BZip','bz2','bz2-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('25','RealAudio','ra','ra-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('26','RealAudio','ram','ram-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('11','Word','doc','doc-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('12','DjVu','djvu','','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('42','MNG','mng','','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('13','PostScript','eps','ps-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('14','GZ','gz','gz-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('37','WAV','wav','wav-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('15','HTML','html','html-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('34','Flash','swf','','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('21','PDF','pdf','pdf-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('22','PowerPoint','ppt','ppt-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('23','PostScript','ps','ps-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('40','Windows Media','wmv','','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('28','RTF','rtf','rtf-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('29','StarOffice','sdd','sdd-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('30','StarOffice','sdw','sdw-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('31','Stuffit','sit','sit-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('43','Adobe Illustrator','ai','ai-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('32','OpenOffice Impress','sxi','sxi-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('33','OpenOffice','sxw','sxw-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('46','DVI','dvi','dvi-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('35','TGZ','tgz','tgz-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('36','texte','txt','txt-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('49','RedHat/Mandrake/SuSE','rpm','rpm-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('38','Excel','xls','xls-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('39','XML','xml','xml-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('41','Zip','zip','zip-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('45','Debian','deb','deb-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('47','C header','h','','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('48','Pascal','pas','','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('50','OpenOffice Calc','sxc','sxc-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('51','LaTeX','tex','tex-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('52','GIMP multi-layer','xcf','xcf-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('53','JPEG','jpeg','jpg-dist.png','','Y','2005-03-07 22:23:17');
INSERT INTO glpi_type_docs VALUES ('54','Oasis Open Office Writer','odt','odt-dist.png','','Y','2006-01-21 17:41:13');
INSERT INTO glpi_type_docs VALUES ('55','Oasis Open Office Calc','ods','ods-dist.png','','Y','2006-01-21 17:41:31');
INSERT INTO glpi_type_docs VALUES ('56','Oasis Open Office Impress','odp','odp-dist.png','','Y','2006-01-21 17:42:54');
INSERT INTO glpi_type_docs VALUES ('57','Oasis Open Office Impress Template','otp','odp-dist.png','','Y','2006-01-21 17:43:58');
INSERT INTO glpi_type_docs VALUES ('58','Oasis Open Office Writer Template','ott','odt-dist.png','','Y','2006-01-21 17:44:41');
INSERT INTO glpi_type_docs VALUES ('59','Oasis Open Office Calc Template','ots','ods-dist.png','','Y','2006-01-21 17:45:30');
INSERT INTO glpi_type_docs VALUES ('60','Oasis Open Office Math','odf','odf-dist.png','','Y','2006-01-21 17:48:05');
INSERT INTO glpi_type_docs VALUES ('61','Oasis Open Office Draw','odg','odg-dist.png','','Y','2006-01-21 17:48:31');
INSERT INTO glpi_type_docs VALUES ('62','Oasis Open Office Draw Template','otg','odg-dist.png','','Y','2006-01-21 17:49:46');
INSERT INTO glpi_type_docs VALUES ('63','Oasis Open Office Base','odb','odb-dist.png','','Y','2006-01-21 18:03:34');
INSERT INTO glpi_type_docs VALUES ('64','Oasis Open Office HTML','oth','oth-dist.png','','Y','2006-01-21 18:05:27');
INSERT INTO glpi_type_docs VALUES ('65','Oasis Open Office Writer Master','odm','odm-dist.png','','Y','2006-01-21 18:06:34');
INSERT INTO glpi_type_docs VALUES ('66','Oasis Open Office Chart','odc','','','Y','2006-01-21 18:07:48');
INSERT INTO glpi_type_docs VALUES ('67','Oasis Open Office Image','odi','','','Y','2006-01-21 18:08:18');

### Dump table glpi_type_monitors

DROP TABLE IF EXISTS `glpi_type_monitors`;
CREATE TABLE `glpi_type_monitors` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255),
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_type_networking

DROP TABLE IF EXISTS `glpi_type_networking`;
CREATE TABLE `glpi_type_networking` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255),
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_type_peripherals

DROP TABLE IF EXISTS `glpi_type_peripherals`;
CREATE TABLE `glpi_type_peripherals` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255),
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_type_phones

DROP TABLE IF EXISTS `glpi_type_phones`;
CREATE TABLE `glpi_type_phones` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255) NOT NULL,
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_type_printers

DROP TABLE IF EXISTS `glpi_type_printers`;
CREATE TABLE `glpi_type_printers` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(255),
    `comments` text,
   PRIMARY KEY (`ID`),
   KEY name (`name`)
) TYPE=MyISAM;


### Dump table glpi_users

DROP TABLE IF EXISTS `glpi_users`;
CREATE TABLE `glpi_users` (
    `ID` int(11) NOT NULL auto_increment,
    `name` varchar(80),
    `password` varchar(80),
    `password_md5` varchar(80),
    `email` varchar(200),
    `phone` varchar(100),
    `type` enum('normal','admin','post-only','super-admin') DEFAULT 'normal' NOT NULL,
    `realname` varchar(255),
    `can_assign_job` enum('yes','no') DEFAULT 'no' NOT NULL,
    `location` int(11),
    `tracking_order` enum('yes','no') DEFAULT 'no' NOT NULL,
    `language` varchar(255),
    `active` int(2) DEFAULT '1' NOT NULL,
   PRIMARY KEY (`ID`),
   UNIQUE name (`name`),
   KEY type (`type`),
   KEY name_2 (`name`)
) TYPE=MyISAM;

INSERT INTO glpi_users VALUES ('1','Helpdesk','14e43c2d31dcbdd1','','',NULL,'post-only','Helpdesk Injector','no',NULL,'no','french','1');
INSERT INTO glpi_users VALUES ('2','glpi','*64B4BB8F2A8C2F41C639DBC894D2759330199470','41ece51526515624ff89973668497d00','','','super-admin','','yes','0','yes','french','1');
INSERT INTO glpi_users VALUES ('3','post-only','*5683D7F638D6598D057638B1957F194E4CA974FB','3177926a7314de24680a9938aaa97703','','','post-only','','no','0','no','english','1');
INSERT INTO glpi_users VALUES ('4','tech','*B09F1B2C210DEEA69C662977CC69C6C461965B09','d9f9133fb120cd6096870bc2b496805b','','','super-admin','','yes','0','yes','french','1');
INSERT INTO glpi_users VALUES ('5','normal','*F3F91B23FC1DB728B49B1F22DEE3D7A839E10F0E','fea087517c26fadd409bd4b9dc642555','','','normal','','no','0','no','english','1');
