#GLPI Dump database on 2010-05-06 09:31

### Dump table glpi_alerts

DROP TABLE IF EXISTS `glpi_alerts`;
CREATE TABLE `glpi_alerts` (
  `id` int(11) NOT NULL auto_increment,
  `itemtype` varchar(100) collate utf8_unicode_ci NOT NULL,
  `items_id` int(11) NOT NULL default '0',
  `type` int(11) NOT NULL default '0' COMMENT 'see define.php ALERT_* constant',
  `date` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`itemtype`,`items_id`,`type`),
  KEY `type` (`type`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_authldapreplicates

DROP TABLE IF EXISTS `glpi_authldapreplicates`;
CREATE TABLE `glpi_authldapreplicates` (
  `id` int(11) NOT NULL auto_increment,
  `authldaps_id` int(11) NOT NULL default '0',
  `host` varchar(255) collate utf8_unicode_ci default NULL,
  `port` int(11) NOT NULL default '389',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `authldaps_id` (`authldaps_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_authldaps

DROP TABLE IF EXISTS `glpi_authldaps`;
CREATE TABLE `glpi_authldaps` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `host` varchar(255) collate utf8_unicode_ci default NULL,
  `basedn` varchar(255) collate utf8_unicode_ci default NULL,
  `rootdn` varchar(255) collate utf8_unicode_ci default NULL,
  `rootdn_password` varchar(255) collate utf8_unicode_ci default NULL,
  `port` int(11) NOT NULL default '389',
  `condition` text collate utf8_unicode_ci,
  `login_field` varchar(255) collate utf8_unicode_ci default 'uid',
  `use_tls` tinyint(1) NOT NULL default '0',
  `group_field` varchar(255) collate utf8_unicode_ci default NULL,
  `group_condition` varchar(255) collate utf8_unicode_ci default NULL,
  `group_search_type` int(11) NOT NULL default '0',
  `group_member_field` varchar(255) collate utf8_unicode_ci default NULL,
  `email_field` varchar(255) collate utf8_unicode_ci default NULL,
  `realname_field` varchar(255) collate utf8_unicode_ci default NULL,
  `firstname_field` varchar(255) collate utf8_unicode_ci default NULL,
  `phone_field` varchar(255) collate utf8_unicode_ci default NULL,
  `phone2_field` varchar(255) collate utf8_unicode_ci default NULL,
  `mobile_field` varchar(255) collate utf8_unicode_ci default NULL,
  `comment_field` varchar(255) collate utf8_unicode_ci default NULL,
  `use_dn` tinyint(1) NOT NULL default '1',
  `time_offset` int(11) NOT NULL default '0' COMMENT 'in seconds',
  `deref_option` int(11) NOT NULL default '0',
  `title_field` varchar(255) collate utf8_unicode_ci default NULL,
  `category_field` varchar(255) collate utf8_unicode_ci default NULL,
  `language_field` varchar(255) collate utf8_unicode_ci default NULL,
  `entity_field` varchar(255) collate utf8_unicode_ci default NULL,
  `entity_condition` text collate utf8_unicode_ci,
  `date_mod` datetime default NULL,
  `comment` text collate utf8_unicode_ci,
  `is_default` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `is_default` (`is_default`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_authmails

DROP TABLE IF EXISTS `glpi_authmails`;
CREATE TABLE `glpi_authmails` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `connect_string` varchar(255) collate utf8_unicode_ci default NULL,
  `host` varchar(255) collate utf8_unicode_ci default NULL,
  `date_mod` datetime default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `date_mod` (`date_mod`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_autoupdatesystems

DROP TABLE IF EXISTS `glpi_autoupdatesystems`;
CREATE TABLE `glpi_autoupdatesystems` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_bookmarks

DROP TABLE IF EXISTS `glpi_bookmarks`;
CREATE TABLE `glpi_bookmarks` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `type` int(11) NOT NULL default '0' COMMENT 'see define.php BOOKMARK_* constant',
  `itemtype` varchar(100) collate utf8_unicode_ci NOT NULL,
  `users_id` int(11) NOT NULL default '0',
  `is_private` tinyint(1) NOT NULL default '1',
  `entities_id` int(11) NOT NULL default '-1',
  `is_recursive` tinyint(1) NOT NULL default '0',
  `path` varchar(255) collate utf8_unicode_ci default NULL,
  `query` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `type` (`type`),
  KEY `itemtype` (`itemtype`),
  KEY `entities_id` (`entities_id`),
  KEY `users_id` (`users_id`),
  KEY `is_private` (`is_private`),
  KEY `is_recursive` (`is_recursive`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_bookmarks_users

DROP TABLE IF EXISTS `glpi_bookmarks_users`;
CREATE TABLE `glpi_bookmarks_users` (
  `id` int(11) NOT NULL auto_increment,
  `users_id` int(11) NOT NULL default '0',
  `itemtype` varchar(100) collate utf8_unicode_ci NOT NULL,
  `bookmarks_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`users_id`,`itemtype`),
  KEY `bookmarks_id` (`bookmarks_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_budgets

DROP TABLE IF EXISTS `glpi_budgets`;
CREATE TABLE `glpi_budgets` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `entities_id` int(11) NOT NULL default '0',
  `is_recursive` tinyint(1) NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  `is_deleted` tinyint(1) NOT NULL default '0',
  `begin_date` date default NULL,
  `end_date` date default NULL,
  `value` decimal(20,4) NOT NULL default '0.0000',
  `is_template` tinyint(1) NOT NULL default '0',
  `template_name` varchar(255) collate utf8_unicode_ci default NULL,
  `date_mod` datetime default NULL,
  `notepad` longtext collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `is_recursive` (`is_recursive`),
  KEY `entities_id` (`entities_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `begin_date` (`begin_date`),
  KEY `end_date` (`begin_date`),
  KEY `is_template` (`is_template`),
  KEY `date_mod` (`date_mod`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_cartridgeitems

DROP TABLE IF EXISTS `glpi_cartridgeitems`;
CREATE TABLE `glpi_cartridgeitems` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `ref` varchar(255) collate utf8_unicode_ci default NULL,
  `locations_id` int(11) NOT NULL default '0',
  `cartridgeitemtypes_id` int(11) NOT NULL default '0',
  `manufacturers_id` int(11) NOT NULL default '0',
  `users_id_tech` int(11) NOT NULL default '0',
  `is_deleted` tinyint(1) NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  `alarm_threshold` int(11) NOT NULL default '10',
  `notepad` longtext collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `locations_id` (`locations_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `cartridgeitemtypes_id` (`cartridgeitemtypes_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `alarm_threshold` (`alarm_threshold`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_cartridgeitemtypes

DROP TABLE IF EXISTS `glpi_cartridgeitemtypes`;
CREATE TABLE `glpi_cartridgeitemtypes` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_cartridges

DROP TABLE IF EXISTS `glpi_cartridges`;
CREATE TABLE `glpi_cartridges` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `cartridgeitems_id` int(11) NOT NULL default '0',
  `printers_id` int(11) NOT NULL default '0',
  `date_in` date default NULL,
  `date_use` date default NULL,
  `date_out` date default NULL,
  `pages` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `cartridgeitems_id` (`cartridgeitems_id`),
  KEY `printers_id` (`printers_id`),
  KEY `entities_id` (`entities_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_cartridges_printermodels

DROP TABLE IF EXISTS `glpi_cartridges_printermodels`;
CREATE TABLE `glpi_cartridges_printermodels` (
  `id` int(11) NOT NULL auto_increment,
  `cartridgeitems_id` int(11) NOT NULL default '0',
  `printermodels_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`printermodels_id`,`cartridgeitems_id`),
  KEY `cartridgeitems_id` (`cartridgeitems_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_computerdisks

DROP TABLE IF EXISTS `glpi_computerdisks`;
CREATE TABLE `glpi_computerdisks` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `computers_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `device` varchar(255) collate utf8_unicode_ci default NULL,
  `mountpoint` varchar(255) collate utf8_unicode_ci default NULL,
  `filesystems_id` int(11) NOT NULL default '0',
  `totalsize` int(11) NOT NULL default '0',
  `freesize` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `device` (`device`),
  KEY `mountpoint` (`mountpoint`),
  KEY `totalsize` (`totalsize`),
  KEY `freesize` (`freesize`),
  KEY `computers_id` (`computers_id`),
  KEY `filesystems_id` (`filesystems_id`),
  KEY `entities_id` (`entities_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_computermodels

DROP TABLE IF EXISTS `glpi_computermodels`;
CREATE TABLE `glpi_computermodels` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_computers

DROP TABLE IF EXISTS `glpi_computers`;
CREATE TABLE `glpi_computers` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `serial` varchar(255) collate utf8_unicode_ci default NULL,
  `otherserial` varchar(255) collate utf8_unicode_ci default NULL,
  `contact` varchar(255) collate utf8_unicode_ci default NULL,
  `contact_num` varchar(255) collate utf8_unicode_ci default NULL,
  `users_id_tech` int(11) NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  `date_mod` datetime default NULL,
  `operatingsystems_id` int(11) NOT NULL default '0',
  `operatingsystemversions_id` int(11) NOT NULL default '0',
  `operatingsystemservicepacks_id` int(11) NOT NULL default '0',
  `os_license_number` varchar(255) collate utf8_unicode_ci default NULL,
  `os_licenseid` varchar(255) collate utf8_unicode_ci default NULL,
  `autoupdatesystems_id` int(11) NOT NULL default '0',
  `locations_id` int(11) NOT NULL default '0',
  `domains_id` int(11) NOT NULL default '0',
  `networks_id` int(11) NOT NULL default '0',
  `computermodels_id` int(11) NOT NULL default '0',
  `computertypes_id` int(11) NOT NULL default '0',
  `is_template` tinyint(1) NOT NULL default '0',
  `template_name` varchar(255) collate utf8_unicode_ci default NULL,
  `manufacturers_id` int(11) NOT NULL default '0',
  `is_deleted` tinyint(1) NOT NULL default '0',
  `notepad` longtext collate utf8_unicode_ci,
  `is_ocs_import` tinyint(1) NOT NULL default '0',
  `users_id` int(11) NOT NULL default '0',
  `groups_id` int(11) NOT NULL default '0',
  `states_id` int(11) NOT NULL default '0',
  `ticket_tco` decimal(20,4) default '0.0000',
  PRIMARY KEY  (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `name` (`name`),
  KEY `is_template` (`is_template`),
  KEY `autoupdatesystems_id` (`autoupdatesystems_id`),
  KEY `domains_id` (`domains_id`),
  KEY `entities_id` (`entities_id`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `groups_id` (`groups_id`),
  KEY `users_id` (`users_id`),
  KEY `locations_id` (`locations_id`),
  KEY `computermodels_id` (`computermodels_id`),
  KEY `networks_id` (`networks_id`),
  KEY `operatingsystems_id` (`operatingsystems_id`),
  KEY `operatingsystemservicepacks_id` (`operatingsystemservicepacks_id`),
  KEY `operatingsystemversions_id` (`operatingsystemversions_id`),
  KEY `states_id` (`states_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `computertypes_id` (`computertypes_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_ocs_import` (`is_ocs_import`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_computers_devicecases

DROP TABLE IF EXISTS `glpi_computers_devicecases`;
CREATE TABLE `glpi_computers_devicecases` (
  `id` int(11) NOT NULL auto_increment,
  `computers_id` int(11) NOT NULL default '0',
  `devicecases_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `computers_id` (`computers_id`),
  KEY `devicecases_id` (`devicecases_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_computers_devicecontrols

DROP TABLE IF EXISTS `glpi_computers_devicecontrols`;
CREATE TABLE `glpi_computers_devicecontrols` (
  `id` int(11) NOT NULL auto_increment,
  `computers_id` int(11) NOT NULL default '0',
  `devicecontrols_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `computers_id` (`computers_id`),
  KEY `devicecontrols_id` (`devicecontrols_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_computers_devicedrives

DROP TABLE IF EXISTS `glpi_computers_devicedrives`;
CREATE TABLE `glpi_computers_devicedrives` (
  `id` int(11) NOT NULL auto_increment,
  `computers_id` int(11) NOT NULL default '0',
  `devicedrives_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `computers_id` (`computers_id`),
  KEY `devicedrives_id` (`devicedrives_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_computers_devicegraphiccards

DROP TABLE IF EXISTS `glpi_computers_devicegraphiccards`;
CREATE TABLE `glpi_computers_devicegraphiccards` (
  `id` int(11) NOT NULL auto_increment,
  `computers_id` int(11) NOT NULL default '0',
  `devicegraphiccards_id` int(11) NOT NULL default '0',
  `specificity` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `computers_id` (`computers_id`),
  KEY `devicegraphiccards_id` (`devicegraphiccards_id`),
  KEY `specificity` (`specificity`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_computers_deviceharddrives

DROP TABLE IF EXISTS `glpi_computers_deviceharddrives`;
CREATE TABLE `glpi_computers_deviceharddrives` (
  `id` int(11) NOT NULL auto_increment,
  `computers_id` int(11) NOT NULL default '0',
  `deviceharddrives_id` int(11) NOT NULL default '0',
  `specificity` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `computers_id` (`computers_id`),
  KEY `deviceharddrives_id` (`deviceharddrives_id`),
  KEY `specificity` (`specificity`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_computers_devicememories

DROP TABLE IF EXISTS `glpi_computers_devicememories`;
CREATE TABLE `glpi_computers_devicememories` (
  `id` int(11) NOT NULL auto_increment,
  `computers_id` int(11) NOT NULL default '0',
  `devicememories_id` int(11) NOT NULL default '0',
  `specificity` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `computers_id` (`computers_id`),
  KEY `devicememories_id` (`devicememories_id`),
  KEY `specificity` (`specificity`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_computers_devicemotherboards

DROP TABLE IF EXISTS `glpi_computers_devicemotherboards`;
CREATE TABLE `glpi_computers_devicemotherboards` (
  `id` int(11) NOT NULL auto_increment,
  `computers_id` int(11) NOT NULL default '0',
  `devicemotherboards_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `computers_id` (`computers_id`),
  KEY `devicemotherboards_id` (`devicemotherboards_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_computers_devicenetworkcards

DROP TABLE IF EXISTS `glpi_computers_devicenetworkcards`;
CREATE TABLE `glpi_computers_devicenetworkcards` (
  `id` int(11) NOT NULL auto_increment,
  `computers_id` int(11) NOT NULL default '0',
  `devicenetworkcards_id` int(11) NOT NULL default '0',
  `specificity` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `computers_id` (`computers_id`),
  KEY `devicenetworkcards_id` (`devicenetworkcards_id`),
  KEY `specificity` (`specificity`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_computers_devicepcis

DROP TABLE IF EXISTS `glpi_computers_devicepcis`;
CREATE TABLE `glpi_computers_devicepcis` (
  `id` int(11) NOT NULL auto_increment,
  `computers_id` int(11) NOT NULL default '0',
  `devicepcis_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `computers_id` (`computers_id`),
  KEY `devicepcis_id` (`devicepcis_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_computers_devicepowersupplies

DROP TABLE IF EXISTS `glpi_computers_devicepowersupplies`;
CREATE TABLE `glpi_computers_devicepowersupplies` (
  `id` int(11) NOT NULL auto_increment,
  `computers_id` int(11) NOT NULL default '0',
  `devicepowersupplies_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `computers_id` (`computers_id`),
  KEY `devicepowersupplies_id` (`devicepowersupplies_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_computers_deviceprocessors

DROP TABLE IF EXISTS `glpi_computers_deviceprocessors`;
CREATE TABLE `glpi_computers_deviceprocessors` (
  `id` int(11) NOT NULL auto_increment,
  `computers_id` int(11) NOT NULL default '0',
  `deviceprocessors_id` int(11) NOT NULL default '0',
  `specificity` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `computers_id` (`computers_id`),
  KEY `deviceprocessors_id` (`deviceprocessors_id`),
  KEY `specificity` (`specificity`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_computers_devicesoundcards

DROP TABLE IF EXISTS `glpi_computers_devicesoundcards`;
CREATE TABLE `glpi_computers_devicesoundcards` (
  `id` int(11) NOT NULL auto_increment,
  `computers_id` int(11) NOT NULL default '0',
  `devicesoundcards_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `computers_id` (`computers_id`),
  KEY `devicesoundcards_id` (`devicesoundcards_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_computers_items

DROP TABLE IF EXISTS `glpi_computers_items`;
CREATE TABLE `glpi_computers_items` (
  `id` int(11) NOT NULL auto_increment,
  `items_id` int(11) NOT NULL default '0' COMMENT 'RELATION to various table, according to itemtype (ID)',
  `computers_id` int(11) NOT NULL default '0',
  `itemtype` varchar(100) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`itemtype`,`items_id`,`computers_id`),
  KEY `computers_id` (`computers_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_computers_softwareversions

DROP TABLE IF EXISTS `glpi_computers_softwareversions`;
CREATE TABLE `glpi_computers_softwareversions` (
  `id` int(11) NOT NULL auto_increment,
  `computers_id` int(11) NOT NULL default '0',
  `softwareversions_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `computers_id` (`computers_id`),
  KEY `softwareversions_id` (`softwareversions_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_computertypes

DROP TABLE IF EXISTS `glpi_computertypes`;
CREATE TABLE `glpi_computertypes` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

### Dump table glpi_configs

DROP TABLE IF EXISTS `glpi_configs`;
CREATE TABLE `glpi_configs` (
  `id` int(11) NOT NULL auto_increment,
  `show_jobs_at_login` tinyint(1) NOT NULL default '0',
  `cut` int(11) NOT NULL default '255',
  `list_limit` int(11) NOT NULL default '20',
  `list_limit_max` int(11) NOT NULL default '50',
  `version` char(10) collate utf8_unicode_ci default NULL,
  `event_loglevel` int(11) NOT NULL default '5',
  `use_mailing` tinyint(1) NOT NULL default '0',
  `admin_email` varchar(255) collate utf8_unicode_ci default NULL,
  `admin_reply` varchar(255) collate utf8_unicode_ci default NULL,
  `mailing_signature` text collate utf8_unicode_ci,
  `use_anonymous_helpdesk` tinyint(1) NOT NULL default '0',
  `language` char(10) collate utf8_unicode_ci default 'en_GB' COMMENT 'see define.php CFG_GLPI[language] array',
  `priority_1` char(20) collate utf8_unicode_ci default '#fff2f2',
  `priority_2` char(20) collate utf8_unicode_ci default '#ffe0e0',
  `priority_3` char(20) collate utf8_unicode_ci default '#ffcece',
  `priority_4` char(20) collate utf8_unicode_ci default '#ffbfbf',
  `priority_5` char(20) collate utf8_unicode_ci default '#ffadad',
  `priority_6` char(20) collate utf8_unicode_ci NOT NULL default '#ff5555',
  `date_tax` date NOT NULL default '2005-12-31',
  `default_alarm_threshold` int(11) NOT NULL default '10',
  `cas_host` varchar(255) collate utf8_unicode_ci default NULL,
  `cas_port` int(11) NOT NULL default '443',
  `cas_uri` varchar(255) collate utf8_unicode_ci default NULL,
  `cas_logout` varchar(255) collate utf8_unicode_ci default NULL,
  `authldaps_id_extra` int(11) NOT NULL default '0' COMMENT 'extra server',
  `existing_auth_server_field` varchar(255) collate utf8_unicode_ci default NULL,
  `existing_auth_server_field_clean_domain` tinyint(1) NOT NULL default '0',
  `planning_begin` time NOT NULL default '08:00:00',
  `planning_end` time NOT NULL default '20:00:00',
  `utf8_conv` int(11) NOT NULL default '0',
  `use_auto_assign_to_tech` tinyint(1) NOT NULL default '0',
  `use_public_faq` tinyint(1) NOT NULL default '0',
  `url_base` varchar(255) collate utf8_unicode_ci default NULL,
  `show_link_in_mail` tinyint(1) NOT NULL default '0',
  `text_login` text collate utf8_unicode_ci,
  `founded_new_version` char(10) collate utf8_unicode_ci default NULL,
  `dropdown_max` int(11) NOT NULL default '100',
  `ajax_wildcard` char(1) collate utf8_unicode_ci default '*',
  `use_ajax` tinyint(1) NOT NULL default '0',
  `ajax_limit_count` int(11) NOT NULL default '50',
  `use_ajax_autocompletion` tinyint(1) NOT NULL default '1',
  `is_users_auto_add` tinyint(1) NOT NULL default '1',
  `date_format` int(11) NOT NULL default '0',
  `number_format` int(11) NOT NULL default '0',
  `is_ids_visible` tinyint(1) NOT NULL default '0',
  `dropdown_chars_limit` int(11) NOT NULL default '50',
  `use_ocs_mode` tinyint(1) NOT NULL default '0',
  `smtp_mode` int(11) NOT NULL default '0' COMMENT 'see define.php MAIL_* constant',
  `smtp_host` varchar(255) collate utf8_unicode_ci default NULL,
  `smtp_port` int(11) NOT NULL default '25',
  `smtp_username` varchar(255) collate utf8_unicode_ci default NULL,
  `smtp_password` varchar(255) collate utf8_unicode_ci default NULL,
  `proxy_name` varchar(255) collate utf8_unicode_ci default NULL,
  `proxy_port` int(11) NOT NULL default '8080',
  `proxy_user` varchar(255) collate utf8_unicode_ci default NULL,
  `proxy_password` varchar(255) collate utf8_unicode_ci default NULL,
  `add_followup_on_update_ticket` tinyint(1) NOT NULL default '1',
  `default_contract_alert` int(11) NOT NULL default '0',
  `default_infocom_alert` int(11) NOT NULL default '0',
  `use_licenses_alert` tinyint(1) NOT NULL default '0',
  `cartridges_alert_repeat` int(11) NOT NULL default '0' COMMENT 'in seconds',
  `consumables_alert_repeat` int(11) NOT NULL default '0' COMMENT 'in seconds',
  `keep_tickets_on_delete` tinyint(1) NOT NULL default '1',
  `time_step` int(11) default '5',
  `decimal_number` int(11) default '2',
  `helpdesk_doc_url` varchar(255) collate utf8_unicode_ci default NULL,
  `central_doc_url` varchar(255) collate utf8_unicode_ci default NULL,
  `documentcategories_id_forticket` int(11) NOT NULL default '0' COMMENT 'default category for documents added with a ticket',
  `monitors_management_restrict` int(11) NOT NULL default '2',
  `phones_management_restrict` int(11) NOT NULL default '2',
  `peripherals_management_restrict` int(11) NOT NULL default '2',
  `printers_management_restrict` int(11) NOT NULL default '2',
  `use_log_in_files` tinyint(1) NOT NULL default '0',
  `time_offset` int(11) NOT NULL default '0' COMMENT 'in seconds',
  `is_contact_autoupdate` tinyint(1) NOT NULL default '1',
  `is_user_autoupdate` tinyint(1) NOT NULL default '1',
  `is_group_autoupdate` tinyint(1) NOT NULL default '1',
  `is_location_autoupdate` tinyint(1) NOT NULL default '1',
  `state_autoupdate_mode` int(11) NOT NULL default '0',
  `is_contact_autoclean` tinyint(1) NOT NULL default '0',
  `is_user_autoclean` tinyint(1) NOT NULL default '0',
  `is_group_autoclean` tinyint(1) NOT NULL default '0',
  `is_location_autoclean` tinyint(1) NOT NULL default '0',
  `state_autoclean_mode` int(11) NOT NULL default '0',
  `use_flat_dropdowntree` tinyint(1) NOT NULL default '0',
  `use_autoname_by_entity` tinyint(1) NOT NULL default '1',
  `is_categorized_soft_expanded` tinyint(1) NOT NULL default '1',
  `is_not_categorized_soft_expanded` tinyint(1) NOT NULL default '1',
  `dbreplicate_email` varchar(255) collate utf8_unicode_ci default NULL,
  `softwarecategories_id_ondelete` int(11) NOT NULL default '0' COMMENT 'category applyed when a software is deleted',
  `x509_email_field` varchar(255) collate utf8_unicode_ci default NULL,
  `is_ticket_title_mandatory` tinyint(1) NOT NULL default '0',
  `is_ticket_content_mandatory` tinyint(1) NOT NULL default '1',
  `is_ticket_category_mandatory` tinyint(1) NOT NULL default '0',
  `default_mailcollector_filesize_max` int(11) NOT NULL default '2097152',
  `followup_private` tinyint(1) NOT NULL default '0',
  `task_private` tinyint(1) NOT NULL default '0',
  `default_software_helpdesk_visible` tinyint(1) NOT NULL default '1',
  `names_format` int(11) NOT NULL default '0' COMMENT 'see *NAME_BEFORE constant in define.php',
  `default_graphtype` char(3) collate utf8_unicode_ci NOT NULL default 'svg',
  `default_requesttypes_id` int(11) NOT NULL default '1',
  `use_noright_users_add` tinyint(1) NOT NULL default '1',
  `cron_limit` tinyint(4) NOT NULL default '1' COMMENT 'Number of tasks execute by external cron',
  `priority_matrix` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'json encoded array for Urgence / Impact to Protority',
  `urgency_mask` int(11) NOT NULL default '62',
  `impact_mask` int(11) NOT NULL default '62',
  `use_infocoms_alert` tinyint(1) NOT NULL default '0',
  `use_contracts_alert` tinyint(1) NOT NULL default '0',
  `use_reservations_alert` tinyint(1) NOT NULL default '0',
  `autoclose_delay` int(11) NOT NULL default '0',
  `notclosed_delay` int(11) NOT NULL default '0',
  `user_deleted_ldap` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_configs` VALUES ('1','0','250','15','50',' 0.78','5','0','admsys@xxxxx.fr',NULL,'SIGNATURE','0','en_GB','#fff2f2','#ffe0e0','#ffcece','#ffbfbf','#ffadad','#ff5555','2005-12-31','10','','443','',NULL,'1',NULL,'0','08:00:00','20:00:00','1','0','0','http://localhost/glpi/','0','','','100','*','0','50','1','1','0','0','0','50','0','0',NULL,'25',NULL,NULL,NULL,'8080',NULL,NULL,'1','0','0','0','0','0','0','5','2',NULL,NULL,'0','2','2','2','2','1','0','1','1','1','1','0','0','0','0','0','0','0','1','1','1',NULL,'1',NULL,'0','1','0','2097152','0','0','1','0','svg','1','1','1','{\"1\":{\"1\":1,\"2\":1,\"3\":2,\"4\":2,\"5\":2},\"2\":{\"1\":1,\"2\":2,\"3\":2,\"4\":3,\"5\":3},\"3\":{\"1\":2,\"2\":2,\"3\":3,\"4\":4,\"5\":4},\"4\":{\"1\":2,\"2\":3,\"3\":4,\"4\":4,\"5\":5},\"5\":{\"1\":2,\"2\":3,\"3\":4,\"4\":5,\"5\":5}}','62','62','0','0','0','0','0','0');

### Dump table glpi_consumableitems

DROP TABLE IF EXISTS `glpi_consumableitems`;
CREATE TABLE `glpi_consumableitems` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `ref` varchar(255) collate utf8_unicode_ci default NULL,
  `locations_id` int(11) NOT NULL default '0',
  `consumableitemtypes_id` int(11) NOT NULL default '0',
  `manufacturers_id` int(11) NOT NULL default '0',
  `users_id_tech` int(11) NOT NULL default '0',
  `is_deleted` tinyint(1) NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  `alarm_threshold` int(11) NOT NULL default '10',
  `notepad` longtext collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `locations_id` (`locations_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `consumableitemtypes_id` (`consumableitemtypes_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `alarm_threshold` (`alarm_threshold`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_consumableitemtypes

DROP TABLE IF EXISTS `glpi_consumableitemtypes`;
CREATE TABLE `glpi_consumableitemtypes` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_consumables

DROP TABLE IF EXISTS `glpi_consumables`;
CREATE TABLE `glpi_consumables` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `consumableitems_id` int(11) NOT NULL default '0',
  `date_in` date default NULL,
  `date_out` date default NULL,
  `users_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `date_in` (`date_in`),
  KEY `date_out` (`date_out`),
  KEY `consumableitems_id` (`consumableitems_id`),
  KEY `users_id` (`users_id`),
  KEY `entities_id` (`entities_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_contacts

DROP TABLE IF EXISTS `glpi_contacts`;
CREATE TABLE `glpi_contacts` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `is_recursive` tinyint(1) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `firstname` varchar(255) collate utf8_unicode_ci default NULL,
  `phone` varchar(255) collate utf8_unicode_ci default NULL,
  `phone2` varchar(255) collate utf8_unicode_ci default NULL,
  `mobile` varchar(255) collate utf8_unicode_ci default NULL,
  `fax` varchar(255) collate utf8_unicode_ci default NULL,
  `email` varchar(255) collate utf8_unicode_ci default NULL,
  `contacttypes_id` int(11) NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  `is_deleted` tinyint(1) NOT NULL default '0',
  `notepad` longtext collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`),
  KEY `contacttypes_id` (`contacttypes_id`),
  KEY `is_deleted` (`is_deleted`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_contacts_suppliers

DROP TABLE IF EXISTS `glpi_contacts_suppliers`;
CREATE TABLE `glpi_contacts_suppliers` (
  `id` int(11) NOT NULL auto_increment,
  `suppliers_id` int(11) NOT NULL default '0',
  `contacts_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`suppliers_id`,`contacts_id`),
  KEY `contacts_id` (`contacts_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_contacttypes

DROP TABLE IF EXISTS `glpi_contacttypes`;
CREATE TABLE `glpi_contacttypes` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_contracts

DROP TABLE IF EXISTS `glpi_contracts`;
CREATE TABLE `glpi_contracts` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `is_recursive` tinyint(1) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `num` varchar(255) collate utf8_unicode_ci default NULL,
  `cost` decimal(20,4) NOT NULL default '0.0000',
  `contracttypes_id` int(11) NOT NULL default '0',
  `begin_date` date default NULL,
  `duration` int(11) NOT NULL default '0',
  `notice` int(11) NOT NULL default '0',
  `periodicity` int(11) NOT NULL default '0',
  `billing` int(11) NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  `accounting_number` varchar(255) collate utf8_unicode_ci default NULL,
  `is_deleted` tinyint(1) NOT NULL default '0',
  `week_begin_hour` time NOT NULL default '00:00:00',
  `week_end_hour` time NOT NULL default '00:00:00',
  `saturday_begin_hour` time NOT NULL default '00:00:00',
  `saturday_end_hour` time NOT NULL default '00:00:00',
  `use_saturday` tinyint(1) NOT NULL default '0',
  `monday_begin_hour` time NOT NULL default '00:00:00',
  `monday_end_hour` time NOT NULL default '00:00:00',
  `use_monday` tinyint(1) NOT NULL default '0',
  `max_links_allowed` int(11) NOT NULL default '0',
  `notepad` longtext collate utf8_unicode_ci,
  `alert` int(11) NOT NULL default '0',
  `renewal` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `begin_date` (`begin_date`),
  KEY `name` (`name`),
  KEY `contracttypes_id` (`contracttypes_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `use_monday` (`use_monday`),
  KEY `use_saturday` (`use_saturday`),
  KEY `alert` (`alert`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_contracts_items

DROP TABLE IF EXISTS `glpi_contracts_items`;
CREATE TABLE `glpi_contracts_items` (
  `id` int(11) NOT NULL auto_increment,
  `contracts_id` int(11) NOT NULL default '0',
  `items_id` int(11) NOT NULL default '0',
  `itemtype` varchar(100) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`contracts_id`,`itemtype`,`items_id`),
  KEY `FK_device` (`items_id`,`itemtype`),
  KEY `item` (`itemtype`,`items_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_contracts_suppliers

DROP TABLE IF EXISTS `glpi_contracts_suppliers`;
CREATE TABLE `glpi_contracts_suppliers` (
  `id` int(11) NOT NULL auto_increment,
  `suppliers_id` int(11) NOT NULL default '0',
  `contracts_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`suppliers_id`,`contracts_id`),
  KEY `contracts_id` (`contracts_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_contracttypes

DROP TABLE IF EXISTS `glpi_contracttypes`;
CREATE TABLE `glpi_contracttypes` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_crontasklogs

DROP TABLE IF EXISTS `glpi_crontasklogs`;
CREATE TABLE `glpi_crontasklogs` (
  `id` int(11) NOT NULL auto_increment,
  `crontasks_id` int(11) NOT NULL,
  `crontasklogs_id` int(11) NOT NULL COMMENT 'id of ''start'' event',
  `date` datetime NOT NULL,
  `state` int(11) NOT NULL COMMENT '0:start, 1:run, 2:stop',
  `elapsed` float NOT NULL COMMENT 'time elapsed since start',
  `volume` int(11) NOT NULL COMMENT 'for statistics',
  `content` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'message',
  PRIMARY KEY  (`id`),
  KEY `date` (`date`),
  KEY `crontasks_id` (`crontasks_id`),
  KEY `crontasklogs_id_state` (`crontasklogs_id`,`state`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

### Dump table glpi_crontasks

DROP TABLE IF EXISTS `glpi_crontasks`;
CREATE TABLE `glpi_crontasks` (
  `id` int(11) NOT NULL auto_increment,
  `itemtype` varchar(100) collate utf8_unicode_ci NOT NULL,
  `name` varchar(150) collate utf8_unicode_ci NOT NULL COMMENT 'task name',
  `frequency` int(11) NOT NULL COMMENT 'second between launch',
  `param` int(11) default NULL COMMENT 'task specify parameter',
  `state` int(11) NOT NULL default '1' COMMENT '0:disabled, 1:waiting, 2:running',
  `mode` int(11) NOT NULL default '1' COMMENT '1:internal, 2:external',
  `allowmode` int(11) NOT NULL default '3' COMMENT '1:internal, 2:external, 3:both',
  `hourmin` int(11) NOT NULL default '0',
  `hourmax` int(11) NOT NULL default '24',
  `logs_lifetime` int(11) NOT NULL default '30' COMMENT 'number of days',
  `lastrun` datetime default NULL COMMENT 'last run date',
  `lastcode` int(11) default NULL COMMENT 'last run return code',
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`itemtype`,`name`),
  KEY `mode` (`mode`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Task run by internal / external cron.';

INSERT INTO `glpi_crontasks` VALUES ('1','OcsServer','ocsng','300',NULL,'0','1','3','0','24','30',NULL,NULL,NULL);
INSERT INTO `glpi_crontasks` VALUES ('2','CartridgeItem','cartridge','86400','10','0','1','3','0','24','30',NULL,NULL,NULL);
INSERT INTO `glpi_crontasks` VALUES ('3','ConsumableItem','consumable','86400','10','0','1','3','0','24','30',NULL,NULL,NULL);
INSERT INTO `glpi_crontasks` VALUES ('4','SoftwareLicense','software','86400',NULL,'0','1','3','0','24','30',NULL,NULL,NULL);
INSERT INTO `glpi_crontasks` VALUES ('5','Contract','contract','86400',NULL,'1','1','3','0','24','30','2010-05-06 09:31:02',NULL,NULL);
INSERT INTO `glpi_crontasks` VALUES ('6','InfoCom','infocom','86400',NULL,'1','1','3','0','24','30',NULL,NULL,NULL);
INSERT INTO `glpi_crontasks` VALUES ('7','CronTask','logs','86400','30','0','1','3','0','24','30',NULL,NULL,NULL);
INSERT INTO `glpi_crontasks` VALUES ('8','CronTask','optimize','604800',NULL,'1','1','3','0','24','30',NULL,NULL,NULL);
INSERT INTO `glpi_crontasks` VALUES ('9','MailCollector','mailgate','600','10','1','1','3','0','24','30',NULL,NULL,NULL);
INSERT INTO `glpi_crontasks` VALUES ('10','DBconnection','checkdbreplicate','300',NULL,'0','1','3','0','24','30',NULL,NULL,NULL);
INSERT INTO `glpi_crontasks` VALUES ('11','CronTask','checkupdate','604800',NULL,'0','1','3','0','24','30',NULL,NULL,NULL);
INSERT INTO `glpi_crontasks` VALUES ('12','CronTask','session','86400',NULL,'1','1','3','0','24','30',NULL,NULL,NULL);
INSERT INTO `glpi_crontasks` VALUES ('13','CronTask','graph','3600',NULL,'1','1','3','0','24','30',NULL,NULL,NULL);
INSERT INTO `glpi_crontasks` VALUES ('14','ReservationItem','reservation','3600',NULL,'1','1','3','0','24','30',NULL,NULL,NULL);
INSERT INTO `glpi_crontasks` VALUES ('15','Ticket','closeticket','43200',NULL,'1','1','3','0','24','30',NULL,NULL,NULL);
INSERT INTO `glpi_crontasks` VALUES ('16','Ticket','alertnotclosed','43200',NULL,'1','1','3','0','24','30',NULL,NULL,NULL);

### Dump table glpi_devicecases

DROP TABLE IF EXISTS `glpi_devicecases`;
CREATE TABLE `glpi_devicecases` (
  `id` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `devicecasetypes_id` int(11) NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  `manufacturers_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `devicecasetypes_id` (`devicecasetypes_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_devicecasetypes

DROP TABLE IF EXISTS `glpi_devicecasetypes`;
CREATE TABLE `glpi_devicecasetypes` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_devicecontrols

DROP TABLE IF EXISTS `glpi_devicecontrols`;
CREATE TABLE `glpi_devicecontrols` (
  `id` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `is_raid` tinyint(1) NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  `manufacturers_id` int(11) NOT NULL default '0',
  `interfacetypes_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `interfacetypes_id` (`interfacetypes_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_devicedrives

DROP TABLE IF EXISTS `glpi_devicedrives`;
CREATE TABLE `glpi_devicedrives` (
  `id` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `is_writer` tinyint(1) NOT NULL default '1',
  `speed` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  `manufacturers_id` int(11) NOT NULL default '0',
  `interfacetypes_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `interfacetypes_id` (`interfacetypes_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_devicegraphiccards

DROP TABLE IF EXISTS `glpi_devicegraphiccards`;
CREATE TABLE `glpi_devicegraphiccards` (
  `id` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `interfacetypes_id` int(11) NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  `manufacturers_id` int(11) NOT NULL default '0',
  `specif_default` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `interfacetypes_id` (`interfacetypes_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_deviceharddrives

DROP TABLE IF EXISTS `glpi_deviceharddrives`;
CREATE TABLE `glpi_deviceharddrives` (
  `id` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `rpm` varchar(255) collate utf8_unicode_ci default NULL,
  `interfacetypes_id` int(11) NOT NULL default '0',
  `cache` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  `manufacturers_id` int(11) NOT NULL default '0',
  `specif_default` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `interfacetypes_id` (`interfacetypes_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_devicememories

DROP TABLE IF EXISTS `glpi_devicememories`;
CREATE TABLE `glpi_devicememories` (
  `id` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `frequence` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  `manufacturers_id` int(11) NOT NULL default '0',
  `specif_default` int(11) NOT NULL,
  `devicememorytypes_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `devicememorytypes_id` (`devicememorytypes_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_devicememorytypes

DROP TABLE IF EXISTS `glpi_devicememorytypes`;
CREATE TABLE `glpi_devicememorytypes` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_devicememorytypes` VALUES ('1','EDO',NULL);
INSERT INTO `glpi_devicememorytypes` VALUES ('2','DDR',NULL);
INSERT INTO `glpi_devicememorytypes` VALUES ('3','SDRAM',NULL);
INSERT INTO `glpi_devicememorytypes` VALUES ('4','SDRAM-2',NULL);

### Dump table glpi_devicemotherboards

DROP TABLE IF EXISTS `glpi_devicemotherboards`;
CREATE TABLE `glpi_devicemotherboards` (
  `id` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `chipset` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  `manufacturers_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_devicenetworkcards

DROP TABLE IF EXISTS `glpi_devicenetworkcards`;
CREATE TABLE `glpi_devicenetworkcards` (
  `id` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `bandwidth` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  `manufacturers_id` int(11) NOT NULL default '0',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_devicepcis

DROP TABLE IF EXISTS `glpi_devicepcis`;
CREATE TABLE `glpi_devicepcis` (
  `id` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  `manufacturers_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_devicepowersupplies

DROP TABLE IF EXISTS `glpi_devicepowersupplies`;
CREATE TABLE `glpi_devicepowersupplies` (
  `id` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `power` varchar(255) collate utf8_unicode_ci default NULL,
  `is_atx` tinyint(1) NOT NULL default '1',
  `comment` text collate utf8_unicode_ci,
  `manufacturers_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_deviceprocessors

DROP TABLE IF EXISTS `glpi_deviceprocessors`;
CREATE TABLE `glpi_deviceprocessors` (
  `id` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `frequence` int(11) NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  `manufacturers_id` int(11) NOT NULL default '0',
  `specif_default` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_devicesoundcards

DROP TABLE IF EXISTS `glpi_devicesoundcards`;
CREATE TABLE `glpi_devicesoundcards` (
  `id` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `type` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  `manufacturers_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_displaypreferences

DROP TABLE IF EXISTS `glpi_displaypreferences`;
CREATE TABLE `glpi_displaypreferences` (
  `id` int(11) NOT NULL auto_increment,
  `itemtype` varchar(100) collate utf8_unicode_ci NOT NULL,
  `num` int(11) NOT NULL default '0',
  `rank` int(11) NOT NULL default '0',
  `users_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`users_id`,`itemtype`,`num`),
  KEY `rank` (`rank`),
  KEY `num` (`num`),
  KEY `itemtype` (`itemtype`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_displaypreferences` VALUES ('32','Computer','4','4','0');
INSERT INTO `glpi_displaypreferences` VALUES ('34','Computer','45','6','0');
INSERT INTO `glpi_displaypreferences` VALUES ('33','Computer','40','5','0');
INSERT INTO `glpi_displaypreferences` VALUES ('31','Computer','5','3','0');
INSERT INTO `glpi_displaypreferences` VALUES ('30','Computer','23','2','0');
INSERT INTO `glpi_displaypreferences` VALUES ('86','DocumentType','3','1','0');
INSERT INTO `glpi_displaypreferences` VALUES ('49','Monitor','31','1','0');
INSERT INTO `glpi_displaypreferences` VALUES ('50','Monitor','23','2','0');
INSERT INTO `glpi_displaypreferences` VALUES ('51','Monitor','3','3','0');
INSERT INTO `glpi_displaypreferences` VALUES ('52','Monitor','4','4','0');
INSERT INTO `glpi_displaypreferences` VALUES ('44','Printer','31','1','0');
INSERT INTO `glpi_displaypreferences` VALUES ('38','NetworkEquipment','31','1','0');
INSERT INTO `glpi_displaypreferences` VALUES ('39','NetworkEquipment','23','2','0');
INSERT INTO `glpi_displaypreferences` VALUES ('45','Printer','23','2','0');
INSERT INTO `glpi_displaypreferences` VALUES ('46','Printer','3','3','0');
INSERT INTO `glpi_displaypreferences` VALUES ('63','Software','4','3','0');
INSERT INTO `glpi_displaypreferences` VALUES ('62','Software','5','2','0');
INSERT INTO `glpi_displaypreferences` VALUES ('61','Software','23','1','0');
INSERT INTO `glpi_displaypreferences` VALUES ('83','CartridgeItem','4','2','0');
INSERT INTO `glpi_displaypreferences` VALUES ('82','CartridgeItem','34','1','0');
INSERT INTO `glpi_displaypreferences` VALUES ('57','Peripheral','3','3','0');
INSERT INTO `glpi_displaypreferences` VALUES ('56','Peripheral','23','2','0');
INSERT INTO `glpi_displaypreferences` VALUES ('55','Peripheral','31','1','0');
INSERT INTO `glpi_displaypreferences` VALUES ('29','Computer','31','1','0');
INSERT INTO `glpi_displaypreferences` VALUES ('35','Computer','3','7','0');
INSERT INTO `glpi_displaypreferences` VALUES ('36','Computer','19','8','0');
INSERT INTO `glpi_displaypreferences` VALUES ('37','Computer','17','9','0');
INSERT INTO `glpi_displaypreferences` VALUES ('40','NetworkEquipment','3','3','0');
INSERT INTO `glpi_displaypreferences` VALUES ('41','NetworkEquipment','4','4','0');
INSERT INTO `glpi_displaypreferences` VALUES ('42','NetworkEquipment','11','6','0');
INSERT INTO `glpi_displaypreferences` VALUES ('43','NetworkEquipment','19','7','0');
INSERT INTO `glpi_displaypreferences` VALUES ('47','Printer','4','4','0');
INSERT INTO `glpi_displaypreferences` VALUES ('48','Printer','19','6','0');
INSERT INTO `glpi_displaypreferences` VALUES ('53','Monitor','19','6','0');
INSERT INTO `glpi_displaypreferences` VALUES ('54','Monitor','7','7','0');
INSERT INTO `glpi_displaypreferences` VALUES ('58','Peripheral','4','4','0');
INSERT INTO `glpi_displaypreferences` VALUES ('59','Peripheral','19','6','0');
INSERT INTO `glpi_displaypreferences` VALUES ('60','Peripheral','7','7','0');
INSERT INTO `glpi_displaypreferences` VALUES ('64','Contact','3','1','0');
INSERT INTO `glpi_displaypreferences` VALUES ('65','Contact','4','2','0');
INSERT INTO `glpi_displaypreferences` VALUES ('66','Contact','5','3','0');
INSERT INTO `glpi_displaypreferences` VALUES ('67','Contact','6','4','0');
INSERT INTO `glpi_displaypreferences` VALUES ('68','Contact','9','5','0');
INSERT INTO `glpi_displaypreferences` VALUES ('69','Supplier','9','1','0');
INSERT INTO `glpi_displaypreferences` VALUES ('70','Supplier','3','2','0');
INSERT INTO `glpi_displaypreferences` VALUES ('71','Supplier','4','3','0');
INSERT INTO `glpi_displaypreferences` VALUES ('72','Supplier','5','4','0');
INSERT INTO `glpi_displaypreferences` VALUES ('73','Supplier','10','5','0');
INSERT INTO `glpi_displaypreferences` VALUES ('74','Supplier','6','6','0');
INSERT INTO `glpi_displaypreferences` VALUES ('75','Contract','4','1','0');
INSERT INTO `glpi_displaypreferences` VALUES ('76','Contract','3','2','0');
INSERT INTO `glpi_displaypreferences` VALUES ('77','Contract','5','3','0');
INSERT INTO `glpi_displaypreferences` VALUES ('78','Contract','6','4','0');
INSERT INTO `glpi_displaypreferences` VALUES ('79','Contract','7','5','0');
INSERT INTO `glpi_displaypreferences` VALUES ('80','Contract','11','6','0');
INSERT INTO `glpi_displaypreferences` VALUES ('84','CartridgeItem','23','3','0');
INSERT INTO `glpi_displaypreferences` VALUES ('85','CartridgeItem','3','4','0');
INSERT INTO `glpi_displaypreferences` VALUES ('88','DocumentType','6','2','0');
INSERT INTO `glpi_displaypreferences` VALUES ('89','DocumentType','4','3','0');
INSERT INTO `glpi_displaypreferences` VALUES ('90','DocumentType','5','4','0');
INSERT INTO `glpi_displaypreferences` VALUES ('91','Document','3','1','0');
INSERT INTO `glpi_displaypreferences` VALUES ('92','Document','4','2','0');
INSERT INTO `glpi_displaypreferences` VALUES ('93','Document','7','3','0');
INSERT INTO `glpi_displaypreferences` VALUES ('94','Document','5','4','0');
INSERT INTO `glpi_displaypreferences` VALUES ('95','Document','16','5','0');
INSERT INTO `glpi_displaypreferences` VALUES ('96','User','34','1','0');
INSERT INTO `glpi_displaypreferences` VALUES ('98','User','5','3','0');
INSERT INTO `glpi_displaypreferences` VALUES ('99','User','6','4','0');
INSERT INTO `glpi_displaypreferences` VALUES ('100','User','3','5','0');
INSERT INTO `glpi_displaypreferences` VALUES ('101','ConsumableItem','34','1','0');
INSERT INTO `glpi_displaypreferences` VALUES ('102','ConsumableItem','4','2','0');
INSERT INTO `glpi_displaypreferences` VALUES ('103','ConsumableItem','23','3','0');
INSERT INTO `glpi_displaypreferences` VALUES ('104','ConsumableItem','3','4','0');
INSERT INTO `glpi_displaypreferences` VALUES ('105','NetworkEquipment','40','5','0');
INSERT INTO `glpi_displaypreferences` VALUES ('106','Printer','40','5','0');
INSERT INTO `glpi_displaypreferences` VALUES ('107','Monitor','40','5','0');
INSERT INTO `glpi_displaypreferences` VALUES ('108','Peripheral','40','5','0');
INSERT INTO `glpi_displaypreferences` VALUES ('109','User','8','6','0');
INSERT INTO `glpi_displaypreferences` VALUES ('110','Phone','31','1','0');
INSERT INTO `glpi_displaypreferences` VALUES ('111','Phone','23','2','0');
INSERT INTO `glpi_displaypreferences` VALUES ('112','Phone','3','3','0');
INSERT INTO `glpi_displaypreferences` VALUES ('113','Phone','4','4','0');
INSERT INTO `glpi_displaypreferences` VALUES ('114','Phone','40','5','0');
INSERT INTO `glpi_displaypreferences` VALUES ('115','Phone','19','6','0');
INSERT INTO `glpi_displaypreferences` VALUES ('116','Phone','7','7','0');
INSERT INTO `glpi_displaypreferences` VALUES ('117','Group','16','1','0');
INSERT INTO `glpi_displaypreferences` VALUES ('118','States','31','1','0');
INSERT INTO `glpi_displaypreferences` VALUES ('119','ReservationItem','4','1','0');
INSERT INTO `glpi_displaypreferences` VALUES ('120','ReservationItem','3','2','0');
INSERT INTO `glpi_displaypreferences` VALUES ('125','Budget','3','2','0');
INSERT INTO `glpi_displaypreferences` VALUES ('122','Software','72','4','0');
INSERT INTO `glpi_displaypreferences` VALUES ('123','Software','163','5','0');
INSERT INTO `glpi_displaypreferences` VALUES ('124','Budget','2','1','0');
INSERT INTO `glpi_displaypreferences` VALUES ('126','Budget','4','3','0');
INSERT INTO `glpi_displaypreferences` VALUES ('127','Budget','19','4','0');
INSERT INTO `glpi_displaypreferences` VALUES ('128','Crontask','8','1','0');
INSERT INTO `glpi_displaypreferences` VALUES ('129','Crontask','3','2','0');
INSERT INTO `glpi_displaypreferences` VALUES ('130','Crontask','4','3','0');
INSERT INTO `glpi_displaypreferences` VALUES ('131','Crontask','7','4','0');
INSERT INTO `glpi_displaypreferences` VALUES ('132','RequestType','14','1','0');
INSERT INTO `glpi_displaypreferences` VALUES ('133','RequestType','15','2','0');
INSERT INTO `glpi_displaypreferences` VALUES ('134','NotificationTemplate','4','1','0');
INSERT INTO `glpi_displaypreferences` VALUES ('135','NotificationTemplate','16','2','0');
INSERT INTO `glpi_displaypreferences` VALUES ('136','Notification','5','1','0');
INSERT INTO `glpi_displaypreferences` VALUES ('137','Notification','6','2','0');
INSERT INTO `glpi_displaypreferences` VALUES ('138','Notification','2','3','0');
INSERT INTO `glpi_displaypreferences` VALUES ('139','Notification','4','4','0');
INSERT INTO `glpi_displaypreferences` VALUES ('140','Notification','80','5','0');
INSERT INTO `glpi_displaypreferences` VALUES ('141','Notification','86','6','0');
INSERT INTO `glpi_displaypreferences` VALUES ('142','MailCollector','2','1','0');
INSERT INTO `glpi_displaypreferences` VALUES ('143','MailCollector','19','2','0');
INSERT INTO `glpi_displaypreferences` VALUES ('144','AuthLDAP','3','1','0');
INSERT INTO `glpi_displaypreferences` VALUES ('145','AuthLDAP','19','2','0');
INSERT INTO `glpi_displaypreferences` VALUES ('146','AuthMail','3','1','0');
INSERT INTO `glpi_displaypreferences` VALUES ('147','AuthMail','19','2','0');
INSERT INTO `glpi_displaypreferences` VALUES ('148','OcsServer','3','1','0');
INSERT INTO `glpi_displaypreferences` VALUES ('149','OcsServer','19','2','0');
INSERT INTO `glpi_displaypreferences` VALUES ('150','Profile','2','1','0');
INSERT INTO `glpi_displaypreferences` VALUES ('151','Profile','3','2','0');
INSERT INTO `glpi_displaypreferences` VALUES ('152','Profile','19','3','0');
INSERT INTO `glpi_displaypreferences` VALUES ('153','Transfer','19','1','0');
INSERT INTO `glpi_displaypreferences` VALUES ('154','TicketValidation','3','1','0');
INSERT INTO `glpi_displaypreferences` VALUES ('155','TicketValidation','2','2','0');
INSERT INTO `glpi_displaypreferences` VALUES ('156','TicketValidation','8','3','0');
INSERT INTO `glpi_displaypreferences` VALUES ('157','TicketValidation','4','4','0');
INSERT INTO `glpi_displaypreferences` VALUES ('158','TicketValidation','9','5','0');
INSERT INTO `glpi_displaypreferences` VALUES ('159','TicketValidation','7','6','0');
INSERT INTO `glpi_displaypreferences` VALUES ('160','NotImportedEmail','2','1','0');
INSERT INTO `glpi_displaypreferences` VALUES ('161','NotImportedEmail','5','2','0');
INSERT INTO `glpi_displaypreferences` VALUES ('162','NotImportedEmail','4','3','0');
INSERT INTO `glpi_displaypreferences` VALUES ('163','NotImportedEmail','6','4','0');
INSERT INTO `glpi_displaypreferences` VALUES ('164','NotImportedEmail','16','5','0');
INSERT INTO `glpi_displaypreferences` VALUES ('165','NotImportedEmail','19','6','0');
INSERT INTO `glpi_displaypreferences` VALUES ('166','RuleRightParameter','11','1','0');
INSERT INTO `glpi_displaypreferences` VALUES ('167','Ticket','12','1','0');
INSERT INTO `glpi_displaypreferences` VALUES ('168','Ticket','19','2','0');
INSERT INTO `glpi_displaypreferences` VALUES ('169','Ticket','15','3','0');
INSERT INTO `glpi_displaypreferences` VALUES ('170','Ticket','3','4','0');
INSERT INTO `glpi_displaypreferences` VALUES ('171','Ticket','4','5','0');
INSERT INTO `glpi_displaypreferences` VALUES ('172','Ticket','5','6','0');
INSERT INTO `glpi_displaypreferences` VALUES ('173','Ticket','7','7','0');

### Dump table glpi_documentcategories

DROP TABLE IF EXISTS `glpi_documentcategories`;
CREATE TABLE `glpi_documentcategories` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_documents

DROP TABLE IF EXISTS `glpi_documents`;
CREATE TABLE `glpi_documents` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `is_recursive` tinyint(1) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `filename` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'for display and transfert',
  `filepath` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'file storage path',
  `documentcategories_id` int(11) NOT NULL default '0',
  `mime` varchar(255) collate utf8_unicode_ci default NULL,
  `date_mod` datetime default NULL,
  `comment` text collate utf8_unicode_ci,
  `is_deleted` tinyint(1) NOT NULL default '0',
  `link` varchar(255) collate utf8_unicode_ci default NULL,
  `notepad` longtext collate utf8_unicode_ci,
  `users_id` int(11) NOT NULL default '0',
  `tickets_id` int(11) NOT NULL default '0',
  `sha1sum` char(40) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`),
  KEY `tickets_id` (`tickets_id`),
  KEY `users_id` (`users_id`),
  KEY `documentcategories_id` (`documentcategories_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `sha1sum` (`sha1sum`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_documents_items

DROP TABLE IF EXISTS `glpi_documents_items`;
CREATE TABLE `glpi_documents_items` (
  `id` int(11) NOT NULL auto_increment,
  `documents_id` int(11) NOT NULL default '0',
  `items_id` int(11) NOT NULL default '0',
  `itemtype` varchar(100) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`documents_id`,`itemtype`,`items_id`),
  KEY `item` (`itemtype`,`items_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_documenttypes

DROP TABLE IF EXISTS `glpi_documenttypes`;
CREATE TABLE `glpi_documenttypes` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `ext` varchar(255) collate utf8_unicode_ci default NULL,
  `icon` varchar(255) collate utf8_unicode_ci default NULL,
  `mime` varchar(255) collate utf8_unicode_ci default NULL,
  `is_uploadable` tinyint(1) NOT NULL default '1',
  `date_mod` datetime default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`ext`),
  KEY `name` (`name`),
  KEY `is_uploadable` (`is_uploadable`),
  KEY `date_mod` (`date_mod`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_documenttypes` VALUES ('1','JPEG','jpg','jpg-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('2','PNG','png','png-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('3','GIF','gif','gif-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('4','BMP','bmp','bmp-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('5','Photoshop','psd','psd-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('6','TIFF','tif','tif-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('7','AIFF','aiff','aiff-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('8','Windows Media','asf','asf-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('9','Windows Media','avi','avi-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('44','C source','c','','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('27','RealAudio','rm','rm-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('16','Midi','mid','mid-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('17','QuickTime','mov','mov-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('18','MP3','mp3','mp3-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('19','MPEG','mpg','mpg-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('20','Ogg Vorbis','ogg','ogg-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('24','QuickTime','qt','qt-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('10','BZip','bz2','bz2-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('25','RealAudio','ra','ra-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('26','RealAudio','ram','ram-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('11','Word','doc','doc-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('12','DjVu','djvu','','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('42','MNG','mng','','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('13','PostScript','eps','ps-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('14','GZ','gz','gz-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('37','WAV','wav','wav-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('15','HTML','html','html-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('34','Flash','swf','','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('21','PDF','pdf','pdf-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('22','PowerPoint','ppt','ppt-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('23','PostScript','ps','ps-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('40','Windows Media','wmv','','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('28','RTF','rtf','rtf-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('29','StarOffice','sdd','sdd-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('30','StarOffice','sdw','sdw-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('31','Stuffit','sit','sit-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('43','Adobe Illustrator','ai','ai-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('32','OpenOffice Impress','sxi','sxi-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('33','OpenOffice','sxw','sxw-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('46','DVI','dvi','dvi-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('35','TGZ','tgz','tgz-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('36','texte','txt','txt-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('49','RedHat/Mandrake/SuSE','rpm','rpm-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('38','Excel','xls','xls-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('39','XML','xml','xml-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('41','Zip','zip','zip-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('45','Debian','deb','deb-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('47','C header','h','','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('48','Pascal','pas','','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('50','OpenOffice Calc','sxc','sxc-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('51','LaTeX','tex','tex-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('52','GIMP multi-layer','xcf','xcf-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('53','JPEG','jpeg','jpg-dist.png','','1','2005-03-07 22:23:17',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('54','Oasis Open Office Writer','odt','odt-dist.png','','1','2006-01-21 17:41:13',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('55','Oasis Open Office Calc','ods','ods-dist.png','','1','2006-01-21 17:41:31',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('56','Oasis Open Office Impress','odp','odp-dist.png','','1','2006-01-21 17:42:54',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('57','Oasis Open Office Impress Template','otp','odp-dist.png','','1','2006-01-21 17:43:58',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('58','Oasis Open Office Writer Template','ott','odt-dist.png','','1','2006-01-21 17:44:41',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('59','Oasis Open Office Calc Template','ots','ods-dist.png','','1','2006-01-21 17:45:30',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('60','Oasis Open Office Math','odf','odf-dist.png','','1','2006-01-21 17:48:05',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('61','Oasis Open Office Draw','odg','odg-dist.png','','1','2006-01-21 17:48:31',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('62','Oasis Open Office Draw Template','otg','odg-dist.png','','1','2006-01-21 17:49:46',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('63','Oasis Open Office Base','odb','odb-dist.png','','1','2006-01-21 18:03:34',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('64','Oasis Open Office HTML','oth','oth-dist.png','','1','2006-01-21 18:05:27',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('65','Oasis Open Office Writer Master','odm','odm-dist.png','','1','2006-01-21 18:06:34',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('66','Oasis Open Office Chart','odc','','','1','2006-01-21 18:07:48',NULL);
INSERT INTO `glpi_documenttypes` VALUES ('67','Oasis Open Office Image','odi','','','1','2006-01-21 18:08:18',NULL);

### Dump table glpi_domains

DROP TABLE IF EXISTS `glpi_domains`;
CREATE TABLE `glpi_domains` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_entities

DROP TABLE IF EXISTS `glpi_entities`;
CREATE TABLE `glpi_entities` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `entities_id` int(11) NOT NULL default '0',
  `completename` text collate utf8_unicode_ci,
  `comment` text collate utf8_unicode_ci,
  `level` int(11) NOT NULL default '0',
  `sons_cache` longtext collate utf8_unicode_ci,
  `ancestors_cache` longtext collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`entities_id`,`name`),
  KEY `entities_id` (`entities_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_entitydatas

DROP TABLE IF EXISTS `glpi_entitydatas`;
CREATE TABLE `glpi_entitydatas` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `address` text collate utf8_unicode_ci,
  `postcode` varchar(255) collate utf8_unicode_ci default NULL,
  `town` varchar(255) collate utf8_unicode_ci default NULL,
  `state` varchar(255) collate utf8_unicode_ci default NULL,
  `country` varchar(255) collate utf8_unicode_ci default NULL,
  `website` varchar(255) collate utf8_unicode_ci default NULL,
  `phonenumber` varchar(255) collate utf8_unicode_ci default NULL,
  `fax` varchar(255) collate utf8_unicode_ci default NULL,
  `email` varchar(255) collate utf8_unicode_ci default NULL,
  `admin_email` varchar(255) collate utf8_unicode_ci default NULL,
  `admin_reply` varchar(255) collate utf8_unicode_ci default NULL,
  `notepad` longtext collate utf8_unicode_ci,
  `ldap_dn` varchar(255) collate utf8_unicode_ci default NULL,
  `tag` varchar(255) collate utf8_unicode_ci default NULL,
  `ldapservers_id` int(11) NOT NULL default '0',
  `mail_domain` varchar(255) collate utf8_unicode_ci default NULL,
  `entity_ldapfilter` text collate utf8_unicode_ci,
  `mailing_signature` text collate utf8_unicode_ci,
  `cartridges_alert_repeat` int(11) NOT NULL default '-1',
  `consumables_alert_repeat` int(11) NOT NULL default '-1',
  `use_licenses_alert` tinyint(1) NOT NULL default '-1',
  `use_contracts_alert` tinyint(1) NOT NULL default '-1',
  `use_infocoms_alert` tinyint(1) NOT NULL default '-1',
  `use_reservations_alert` int(11) NOT NULL default '-1',
  `autoclose_delay` int(11) NOT NULL default '-1',
  `notclosed_delay` int(11) NOT NULL default '-1',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`entities_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_events

DROP TABLE IF EXISTS `glpi_events`;
CREATE TABLE `glpi_events` (
  `id` int(11) NOT NULL auto_increment,
  `items_id` int(11) NOT NULL default '0',
  `type` varchar(255) collate utf8_unicode_ci default NULL,
  `date` datetime default NULL,
  `service` varchar(255) collate utf8_unicode_ci default NULL,
  `level` int(11) NOT NULL default '0',
  `message` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `date` (`date`),
  KEY `level` (`level`),
  KEY `item` (`type`,`items_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

### Dump table glpi_filesystems

DROP TABLE IF EXISTS `glpi_filesystems`;
CREATE TABLE `glpi_filesystems` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_filesystems` VALUES ('1','ext',NULL);
INSERT INTO `glpi_filesystems` VALUES ('2','ext2',NULL);
INSERT INTO `glpi_filesystems` VALUES ('3','ext3',NULL);
INSERT INTO `glpi_filesystems` VALUES ('4','ext4',NULL);
INSERT INTO `glpi_filesystems` VALUES ('5','FAT',NULL);
INSERT INTO `glpi_filesystems` VALUES ('6','FAT32',NULL);
INSERT INTO `glpi_filesystems` VALUES ('7','VFAT',NULL);
INSERT INTO `glpi_filesystems` VALUES ('8','HFS',NULL);
INSERT INTO `glpi_filesystems` VALUES ('9','HPFS',NULL);
INSERT INTO `glpi_filesystems` VALUES ('10','HTFS',NULL);
INSERT INTO `glpi_filesystems` VALUES ('11','JFS',NULL);
INSERT INTO `glpi_filesystems` VALUES ('12','JFS2',NULL);
INSERT INTO `glpi_filesystems` VALUES ('13','NFS',NULL);
INSERT INTO `glpi_filesystems` VALUES ('14','NTFS',NULL);
INSERT INTO `glpi_filesystems` VALUES ('15','ReiserFS',NULL);
INSERT INTO `glpi_filesystems` VALUES ('16','SMBFS',NULL);
INSERT INTO `glpi_filesystems` VALUES ('17','UDF',NULL);
INSERT INTO `glpi_filesystems` VALUES ('18','UFS',NULL);
INSERT INTO `glpi_filesystems` VALUES ('19','XFS',NULL);
INSERT INTO `glpi_filesystems` VALUES ('20','ZFS',NULL);

### Dump table glpi_groups

DROP TABLE IF EXISTS `glpi_groups`;
CREATE TABLE `glpi_groups` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `is_recursive` tinyint(1) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  `users_id` int(11) NOT NULL default '0',
  `ldap_field` varchar(255) collate utf8_unicode_ci default NULL,
  `ldap_value` varchar(255) collate utf8_unicode_ci default NULL,
  `ldap_group_dn` varchar(255) collate utf8_unicode_ci default NULL,
  `date_mod` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `ldap_field` (`ldap_field`),
  KEY `ldap_group_dn` (`ldap_group_dn`),
  KEY `ldap_value` (`ldap_value`),
  KEY `entities_id` (`entities_id`),
  KEY `users_id` (`users_id`),
  KEY `date_mod` (`date_mod`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_groups_users

DROP TABLE IF EXISTS `glpi_groups_users`;
CREATE TABLE `glpi_groups_users` (
  `id` int(11) NOT NULL auto_increment,
  `users_id` int(11) NOT NULL default '0',
  `groups_id` int(11) NOT NULL default '0',
  `is_dynamic` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`users_id`,`groups_id`),
  KEY `groups_id` (`groups_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_infocoms

DROP TABLE IF EXISTS `glpi_infocoms`;
CREATE TABLE `glpi_infocoms` (
  `id` int(11) NOT NULL auto_increment,
  `items_id` int(11) NOT NULL default '0',
  `itemtype` varchar(100) collate utf8_unicode_ci NOT NULL,
  `entities_id` int(11) NOT NULL default '0',
  `is_recursive` tinyint(1) NOT NULL default '0',
  `buy_date` date default NULL,
  `use_date` date default NULL,
  `warranty_duration` int(11) NOT NULL default '0',
  `warranty_info` varchar(255) collate utf8_unicode_ci default NULL,
  `suppliers_id` int(11) NOT NULL default '0',
  `order_number` varchar(255) collate utf8_unicode_ci default NULL,
  `delivery_number` varchar(255) collate utf8_unicode_ci default NULL,
  `immo_number` varchar(255) collate utf8_unicode_ci default NULL,
  `value` decimal(20,4) NOT NULL default '0.0000',
  `warranty_value` decimal(20,4) NOT NULL default '0.0000',
  `sink_time` int(11) NOT NULL default '0',
  `sink_type` int(11) NOT NULL default '0',
  `sink_coeff` float NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  `bill` varchar(255) collate utf8_unicode_ci default NULL,
  `budgets_id` int(11) NOT NULL default '0',
  `alert` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`itemtype`,`items_id`),
  KEY `buy_date` (`buy_date`),
  KEY `alert` (`alert`),
  KEY `budgets_id` (`budgets_id`),
  KEY `suppliers_id` (`suppliers_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_interfacetypes

DROP TABLE IF EXISTS `glpi_interfacetypes`;
CREATE TABLE `glpi_interfacetypes` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_interfacetypes` VALUES ('1','IDE',NULL);
INSERT INTO `glpi_interfacetypes` VALUES ('2','SATA',NULL);
INSERT INTO `glpi_interfacetypes` VALUES ('3','SCSI',NULL);
INSERT INTO `glpi_interfacetypes` VALUES ('4','USB',NULL);
INSERT INTO `glpi_interfacetypes` VALUES ('5','AGP','');
INSERT INTO `glpi_interfacetypes` VALUES ('6','PCI','');
INSERT INTO `glpi_interfacetypes` VALUES ('7','PCIe','');
INSERT INTO `glpi_interfacetypes` VALUES ('8','PCI-X','');

### Dump table glpi_knowbaseitemcategories

DROP TABLE IF EXISTS `glpi_knowbaseitemcategories`;
CREATE TABLE `glpi_knowbaseitemcategories` (
  `id` int(11) NOT NULL auto_increment,
  `knowbaseitemcategories_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `completename` text collate utf8_unicode_ci,
  `comment` text collate utf8_unicode_ci,
  `level` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`knowbaseitemcategories_id`,`name`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_knowbaseitems

DROP TABLE IF EXISTS `glpi_knowbaseitems`;
CREATE TABLE `glpi_knowbaseitems` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `is_recursive` tinyint(1) NOT NULL default '1',
  `knowbaseitemcategories_id` int(11) NOT NULL default '0',
  `question` text collate utf8_unicode_ci,
  `answer` longtext collate utf8_unicode_ci,
  `is_faq` tinyint(1) NOT NULL default '0',
  `users_id` int(11) NOT NULL default '0',
  `view` int(11) NOT NULL default '0',
  `date` datetime default NULL,
  `date_mod` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `users_id` (`users_id`),
  KEY `knowbaseitemcategories_id` (`knowbaseitemcategories_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_faq` (`is_faq`),
  KEY `date_mod` (`date_mod`),
  FULLTEXT KEY `fulltext` (`question`,`answer`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_links

DROP TABLE IF EXISTS `glpi_links`;
CREATE TABLE `glpi_links` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `is_recursive` tinyint(1) NOT NULL default '1',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `link` varchar(255) collate utf8_unicode_ci default NULL,
  `data` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `entities_id` (`entities_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_links_itemtypes

DROP TABLE IF EXISTS `glpi_links_itemtypes`;
CREATE TABLE `glpi_links_itemtypes` (
  `id` int(11) NOT NULL auto_increment,
  `links_id` int(11) NOT NULL default '0',
  `itemtype` varchar(100) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`itemtype`,`links_id`),
  KEY `links_id` (`links_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_locations

DROP TABLE IF EXISTS `glpi_locations`;
CREATE TABLE `glpi_locations` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `is_recursive` tinyint(1) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `locations_id` int(11) NOT NULL default '0',
  `completename` text collate utf8_unicode_ci,
  `comment` text collate utf8_unicode_ci,
  `level` int(11) NOT NULL default '0',
  `ancestors_cache` longtext collate utf8_unicode_ci,
  `sons_cache` longtext collate utf8_unicode_ci,
  `building` varchar(255) collate utf8_unicode_ci default NULL,
  `room` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`entities_id`,`locations_id`,`name`),
  KEY `locations_id` (`locations_id`),
  KEY `name` (`name`),
  KEY `is_recursive` (`is_recursive`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_logs

DROP TABLE IF EXISTS `glpi_logs`;
CREATE TABLE `glpi_logs` (
  `id` int(11) NOT NULL auto_increment,
  `itemtype` varchar(100) collate utf8_unicode_ci NOT NULL default '',
  `items_id` int(11) NOT NULL default '0',
  `itemtype_link` varchar(100) collate utf8_unicode_ci NOT NULL default '',
  `linked_action` int(11) NOT NULL default '0' COMMENT 'see define.php HISTORY_* constant',
  `user_name` varchar(255) collate utf8_unicode_ci default NULL,
  `date_mod` datetime default NULL,
  `id_search_option` int(11) NOT NULL default '0' COMMENT 'see search.constant.php for value',
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `itemtype_link` (`itemtype_link`),
  KEY `item` (`itemtype`,`items_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_mailcollectors

DROP TABLE IF EXISTS `glpi_mailcollectors`;
CREATE TABLE `glpi_mailcollectors` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `host` varchar(255) collate utf8_unicode_ci default NULL,
  `login` varchar(255) collate utf8_unicode_ci default NULL,
  `password` varchar(255) collate utf8_unicode_ci default NULL,
  `filesize_max` int(11) NOT NULL default '2097152',
  `is_active` tinyint(1) NOT NULL default '1',
  `date_mod` datetime default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `is_active` (`is_active`),
  KEY `date_mod` (`date_mod`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_manufacturers

DROP TABLE IF EXISTS `glpi_manufacturers`;
CREATE TABLE `glpi_manufacturers` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_monitormodels

DROP TABLE IF EXISTS `glpi_monitormodels`;
CREATE TABLE `glpi_monitormodels` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_monitors

DROP TABLE IF EXISTS `glpi_monitors`;
CREATE TABLE `glpi_monitors` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `date_mod` datetime default NULL,
  `contact` varchar(255) collate utf8_unicode_ci default NULL,
  `contact_num` varchar(255) collate utf8_unicode_ci default NULL,
  `users_id_tech` int(11) NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  `serial` varchar(255) collate utf8_unicode_ci default NULL,
  `otherserial` varchar(255) collate utf8_unicode_ci default NULL,
  `size` int(11) NOT NULL default '0',
  `have_micro` tinyint(1) NOT NULL default '0',
  `have_speaker` tinyint(1) NOT NULL default '0',
  `have_subd` tinyint(1) NOT NULL default '0',
  `have_bnc` tinyint(1) NOT NULL default '0',
  `have_dvi` tinyint(1) NOT NULL default '0',
  `have_pivot` tinyint(1) NOT NULL default '0',
  `locations_id` int(11) NOT NULL default '0',
  `monitortypes_id` int(11) NOT NULL default '0',
  `monitormodels_id` int(11) NOT NULL default '0',
  `manufacturers_id` int(11) NOT NULL default '0',
  `is_global` tinyint(1) NOT NULL default '0',
  `is_deleted` tinyint(1) NOT NULL default '0',
  `is_template` tinyint(1) NOT NULL default '0',
  `template_name` varchar(255) collate utf8_unicode_ci default NULL,
  `notepad` longtext collate utf8_unicode_ci,
  `users_id` int(11) NOT NULL default '0',
  `groups_id` int(11) NOT NULL default '0',
  `states_id` int(11) NOT NULL default '0',
  `ticket_tco` decimal(20,4) default '0.0000',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `is_template` (`is_template`),
  KEY `is_global` (`is_global`),
  KEY `entities_id` (`entities_id`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `groups_id` (`groups_id`),
  KEY `users_id` (`users_id`),
  KEY `locations_id` (`locations_id`),
  KEY `monitormodels_id` (`monitormodels_id`),
  KEY `states_id` (`states_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `monitortypes_id` (`monitortypes_id`),
  KEY `is_deleted` (`is_deleted`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_monitortypes

DROP TABLE IF EXISTS `glpi_monitortypes`;
CREATE TABLE `glpi_monitortypes` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_netpoints

DROP TABLE IF EXISTS `glpi_netpoints`;
CREATE TABLE `glpi_netpoints` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `locations_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `complete` (`entities_id`,`locations_id`,`name`),
  KEY `location_name` (`locations_id`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_networkequipmentfirmwares

DROP TABLE IF EXISTS `glpi_networkequipmentfirmwares`;
CREATE TABLE `glpi_networkequipmentfirmwares` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_networkequipmentmodels

DROP TABLE IF EXISTS `glpi_networkequipmentmodels`;
CREATE TABLE `glpi_networkequipmentmodels` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_networkequipments

DROP TABLE IF EXISTS `glpi_networkequipments`;
CREATE TABLE `glpi_networkequipments` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `is_recursive` tinyint(1) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `ram` varchar(255) collate utf8_unicode_ci default NULL,
  `serial` varchar(255) collate utf8_unicode_ci default NULL,
  `otherserial` varchar(255) collate utf8_unicode_ci default NULL,
  `contact` varchar(255) collate utf8_unicode_ci default NULL,
  `contact_num` varchar(255) collate utf8_unicode_ci default NULL,
  `users_id_tech` int(11) NOT NULL default '0',
  `date_mod` datetime default NULL,
  `comment` text collate utf8_unicode_ci,
  `locations_id` int(11) NOT NULL default '0',
  `domains_id` int(11) NOT NULL default '0',
  `networks_id` int(11) NOT NULL default '0',
  `networkequipmenttypes_id` int(11) NOT NULL default '0',
  `networkequipmentmodels_id` int(11) NOT NULL default '0',
  `networkequipmentfirmwares_id` int(11) NOT NULL default '0',
  `manufacturers_id` int(11) NOT NULL default '0',
  `is_deleted` tinyint(1) NOT NULL default '0',
  `is_template` tinyint(1) NOT NULL default '0',
  `template_name` varchar(255) collate utf8_unicode_ci default NULL,
  `mac` varchar(255) collate utf8_unicode_ci default NULL,
  `ip` varchar(255) collate utf8_unicode_ci default NULL,
  `notepad` longtext collate utf8_unicode_ci,
  `users_id` int(11) NOT NULL default '0',
  `groups_id` int(11) NOT NULL default '0',
  `states_id` int(11) NOT NULL default '0',
  `ticket_tco` decimal(20,4) default '0.0000',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `is_template` (`is_template`),
  KEY `domains_id` (`domains_id`),
  KEY `networkequipmentfirmwares_id` (`networkequipmentfirmwares_id`),
  KEY `entities_id` (`entities_id`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `groups_id` (`groups_id`),
  KEY `users_id` (`users_id`),
  KEY `locations_id` (`locations_id`),
  KEY `networkequipmentmodels_id` (`networkequipmentmodels_id`),
  KEY `networks_id` (`networks_id`),
  KEY `states_id` (`states_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `networkequipmenttypes_id` (`networkequipmenttypes_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `date_mod` (`date_mod`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_networkequipmenttypes

DROP TABLE IF EXISTS `glpi_networkequipmenttypes`;
CREATE TABLE `glpi_networkequipmenttypes` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_networkinterfaces

DROP TABLE IF EXISTS `glpi_networkinterfaces`;
CREATE TABLE `glpi_networkinterfaces` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_networkports

DROP TABLE IF EXISTS `glpi_networkports`;
CREATE TABLE `glpi_networkports` (
  `id` int(11) NOT NULL auto_increment,
  `items_id` int(11) NOT NULL default '0',
  `itemtype` varchar(100) collate utf8_unicode_ci NOT NULL,
  `entities_id` int(11) NOT NULL default '0',
  `is_recursive` tinyint(1) NOT NULL default '0',
  `logical_number` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `ip` varchar(255) collate utf8_unicode_ci default NULL,
  `mac` varchar(255) collate utf8_unicode_ci default NULL,
  `networkinterfaces_id` int(11) NOT NULL default '0',
  `netpoints_id` int(11) NOT NULL default '0',
  `netmask` varchar(255) collate utf8_unicode_ci default NULL,
  `gateway` varchar(255) collate utf8_unicode_ci default NULL,
  `subnet` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `on_device` (`items_id`,`itemtype`),
  KEY `networkinterfaces_id` (`networkinterfaces_id`),
  KEY `netpoints_id` (`netpoints_id`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_networkports_networkports

DROP TABLE IF EXISTS `glpi_networkports_networkports`;
CREATE TABLE `glpi_networkports_networkports` (
  `id` int(11) NOT NULL auto_increment,
  `networkports_id_1` int(11) NOT NULL default '0',
  `networkports_id_2` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`networkports_id_1`,`networkports_id_2`),
  KEY `networkports_id_2` (`networkports_id_2`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_networkports_vlans

DROP TABLE IF EXISTS `glpi_networkports_vlans`;
CREATE TABLE `glpi_networkports_vlans` (
  `id` int(11) NOT NULL auto_increment,
  `networkports_id` int(11) NOT NULL default '0',
  `vlans_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`networkports_id`,`vlans_id`),
  KEY `vlans_id` (`vlans_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_networks

DROP TABLE IF EXISTS `glpi_networks`;
CREATE TABLE `glpi_networks` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_notifications

DROP TABLE IF EXISTS `glpi_notifications`;
CREATE TABLE `glpi_notifications` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `entities_id` int(11) NOT NULL default '0',
  `itemtype` varchar(100) collate utf8_unicode_ci NOT NULL,
  `event` varchar(255) collate utf8_unicode_ci NOT NULL,
  `mode` varchar(255) collate utf8_unicode_ci NOT NULL,
  `notificationtemplates_id` int(11) NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  `is_recursive` tinyint(1) NOT NULL default '0',
  `is_active` tinyint(1) NOT NULL default '0',
  `date_mod` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `itemtype` (`itemtype`),
  KEY `entities_id` (`entities_id`),
  KEY `is_active` (`is_active`),
  KEY `date_mod` (`date_mod`),
  KEY `is_recursive` (`is_recursive`),
  KEY `notificationtemplates_id` (`notificationtemplates_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_notifications` VALUES ('1','Alert Tickets not closed','0','Ticket','alertnotclosed','mail','6','','1','1','2010-02-16 16:41:39');
INSERT INTO `glpi_notifications` VALUES ('2','New Ticket','0','Ticket','new','mail','4','','1','1','2010-02-16 16:41:39');
INSERT INTO `glpi_notifications` VALUES ('3','Update Ticket','0','Ticket','update','mail','4','','1','1','2010-02-16 16:41:39');
INSERT INTO `glpi_notifications` VALUES ('4','Close Ticket','0','Ticket','closed','mail','4','','1','1','2010-02-16 16:41:39');
INSERT INTO `glpi_notifications` VALUES ('5','Add Followup','0','Ticket','add_followup','mail','4','','1','1','2010-02-16 16:41:39');
INSERT INTO `glpi_notifications` VALUES ('6','Add Task','0','Ticket','add_task','mail','4','','1','1','2010-02-16 16:41:39');
INSERT INTO `glpi_notifications` VALUES ('7','Update Followup','0','Ticket','update_followup','mail','4','','1','1','2010-02-16 16:41:39');
INSERT INTO `glpi_notifications` VALUES ('8','Update Task','0','Ticket','update_task','mail','4','','1','1','2010-02-16 16:41:39');
INSERT INTO `glpi_notifications` VALUES ('9','Delete Followup','0','Ticket','delete_followup','mail','4','','1','1','2010-02-16 16:41:39');
INSERT INTO `glpi_notifications` VALUES ('10','Delete Task','0','Ticket','delete_task','mail','4','','1','1','2010-02-16 16:41:39');
INSERT INTO `glpi_notifications` VALUES ('11','Resolve ticket','0','Ticket','solved','mail','4','','1','1','2010-02-16 16:41:39');
INSERT INTO `glpi_notifications` VALUES ('12','Ticket Validation','0','Ticket','validation','mail','7','','1','1','2010-02-16 16:41:39');
INSERT INTO `glpi_notifications` VALUES ('13','New Reservation','0','Reservation','new','mail','2','','1','1','2010-02-16 16:41:39');
INSERT INTO `glpi_notifications` VALUES ('14','Update Reservation','0','Reservation','update','mail','2','','1','1','2010-02-16 16:41:39');
INSERT INTO `glpi_notifications` VALUES ('15','Delete Reservation','0','Reservation','delete','mail','2','','1','1','2010-02-16 16:41:39');
INSERT INTO `glpi_notifications` VALUES ('16','Alert Reservation','0','Reservation','alert','mail','3','','1','1','2010-02-16 16:41:39');
INSERT INTO `glpi_notifications` VALUES ('17','Contract Notice','0','Contract','notice','mail','12','','1','1','2010-02-16 16:41:39');
INSERT INTO `glpi_notifications` VALUES ('18','Contract End','0','Contract','end','mail','12','','1','1','2010-02-16 16:41:39');
INSERT INTO `glpi_notifications` VALUES ('19','MySQL Synchronization','0','DBConnection','desynchronization','mail','1','','1','1','2010-02-16 16:41:39');
INSERT INTO `glpi_notifications` VALUES ('20','Cartridges','0','Cartridge','alert','mail','8','','1','1','2010-02-16 16:41:39');
INSERT INTO `glpi_notifications` VALUES ('21','Consumables','0','Consumable','alert','mail','9','','1','1','2010-02-16 16:41:39');
INSERT INTO `glpi_notifications` VALUES ('22','Infocoms','0','Infocom','alert','mail','10','','1','1','2010-02-16 16:41:39');
INSERT INTO `glpi_notifications` VALUES ('23','Software Licenses','0','SoftwareLicense','alert','mail','11','','1','1','2010-02-16 16:41:39');

### Dump table glpi_notificationtargets

DROP TABLE IF EXISTS `glpi_notificationtargets`;
CREATE TABLE `glpi_notificationtargets` (
  `id` int(11) NOT NULL auto_increment,
  `items_id` int(11) NOT NULL default '0',
  `type` int(11) NOT NULL default '0',
  `notifications_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `items` (`type`,`items_id`),
  KEY `notifications_id` (`notifications_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_notificationtargets` VALUES ('1','3','1','13');
INSERT INTO `glpi_notificationtargets` VALUES ('2','1','1','13');
INSERT INTO `glpi_notificationtargets` VALUES ('3','3','2','2');
INSERT INTO `glpi_notificationtargets` VALUES ('4','1','1','2');
INSERT INTO `glpi_notificationtargets` VALUES ('5','1','1','3');
INSERT INTO `glpi_notificationtargets` VALUES ('6','1','1','5');
INSERT INTO `glpi_notificationtargets` VALUES ('7','1','1','4');
INSERT INTO `glpi_notificationtargets` VALUES ('8','2','1','3');
INSERT INTO `glpi_notificationtargets` VALUES ('9','4','1','3');
INSERT INTO `glpi_notificationtargets` VALUES ('10','3','1','2');
INSERT INTO `glpi_notificationtargets` VALUES ('11','3','1','3');
INSERT INTO `glpi_notificationtargets` VALUES ('12','3','1','5');
INSERT INTO `glpi_notificationtargets` VALUES ('13','3','1','4');
INSERT INTO `glpi_notificationtargets` VALUES ('14','1','1','19');
INSERT INTO `glpi_notificationtargets` VALUES ('15','14','1','12');
INSERT INTO `glpi_notificationtargets` VALUES ('16','3','1','14');
INSERT INTO `glpi_notificationtargets` VALUES ('17','1','1','14');
INSERT INTO `glpi_notificationtargets` VALUES ('18','3','1','15');
INSERT INTO `glpi_notificationtargets` VALUES ('19','1','1','15');
INSERT INTO `glpi_notificationtargets` VALUES ('20','1','1','6');
INSERT INTO `glpi_notificationtargets` VALUES ('21','3','1','6');
INSERT INTO `glpi_notificationtargets` VALUES ('22','1','1','7');
INSERT INTO `glpi_notificationtargets` VALUES ('23','3','1','7');
INSERT INTO `glpi_notificationtargets` VALUES ('24','1','1','8');
INSERT INTO `glpi_notificationtargets` VALUES ('25','3','1','8');
INSERT INTO `glpi_notificationtargets` VALUES ('26','1','1','9');
INSERT INTO `glpi_notificationtargets` VALUES ('27','3','1','9');
INSERT INTO `glpi_notificationtargets` VALUES ('28','1','1','10');
INSERT INTO `glpi_notificationtargets` VALUES ('29','3','1','10');
INSERT INTO `glpi_notificationtargets` VALUES ('30','1','1','11');
INSERT INTO `glpi_notificationtargets` VALUES ('31','3','1','11');

### Dump table glpi_notificationtemplates

DROP TABLE IF EXISTS `glpi_notificationtemplates`;
CREATE TABLE `glpi_notificationtemplates` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `itemtype` varchar(100) collate utf8_unicode_ci NOT NULL,
  `date_mod` datetime default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `itemtype` (`itemtype`),
  KEY `date_mod` (`date_mod`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_notificationtemplates` VALUES ('1','MySQL Synchronization','DBConnection','2010-02-01 15:51:46','');
INSERT INTO `glpi_notificationtemplates` VALUES ('2','Reservations','Reservation','2010-02-03 14:03:45','');
INSERT INTO `glpi_notificationtemplates` VALUES ('3','Alert Reservation','Reservation','2010-02-03 14:03:45','');
INSERT INTO `glpi_notificationtemplates` VALUES ('4','Tickets','Ticket','2010-02-07 21:39:15','');
INSERT INTO `glpi_notificationtemplates` VALUES ('5','Tickets (Simple)','Ticket','2010-02-07 21:39:15','');
INSERT INTO `glpi_notificationtemplates` VALUES ('6','Alert Tickets not closed','Ticket','2010-02-07 21:39:15','');
INSERT INTO `glpi_notificationtemplates` VALUES ('7','Tickets Validation','Ticket','2010-02-26 21:39:15','');
INSERT INTO `glpi_notificationtemplates` VALUES ('8','Cartridges','Cartridge','2010-02-16 13:17:24','');
INSERT INTO `glpi_notificationtemplates` VALUES ('9','Consumables','Consumable','2010-02-16 13:17:38','');
INSERT INTO `glpi_notificationtemplates` VALUES ('10','Infocoms','Infocom','2010-02-16 13:17:55','');
INSERT INTO `glpi_notificationtemplates` VALUES ('11','Licenses','SoftwareLicense','2010-02-16 13:18:12','');
INSERT INTO `glpi_notificationtemplates` VALUES ('12','Contracts','Contract','2010-02-16 13:18:12','');

### Dump table glpi_notificationtemplatetranslations

DROP TABLE IF EXISTS `glpi_notificationtemplatetranslations`;
CREATE TABLE `glpi_notificationtemplatetranslations` (
  `id` int(11) NOT NULL auto_increment,
  `notificationtemplates_id` int(11) NOT NULL default '0',
  `language` char(5) collate utf8_unicode_ci NOT NULL default '',
  `subject` varchar(255) collate utf8_unicode_ci NOT NULL,
  `content_text` text collate utf8_unicode_ci,
  `content_html` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `notificationtemplates_id` (`notificationtemplates_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_notificationtemplatetranslations` VALUES ('1','1','','##lang.dbconnection.title##','##lang.dbconnection.delay## : ##dbconnection.delay##
','&lt;p&gt;##lang.dbconnection.delay## : ##dbconnection.delay##&lt;/p&gt;');
INSERT INTO `glpi_notificationtemplatetranslations` VALUES ('2','2','','##reservation.action##','======================================================================
##lang.reservation.user##: ##reservation.user##
##lang.reservation.item.name##: ##reservation.itemtype## - ##reservation.item.name##
##IFreservation.tech## ##lang.reservation.tech## ##reservation.tech## ##ENDIFreservation.tech##
##lang.reservation.begin##: ##reservation.begin##
##lang.reservation.end##: ##reservation.end##
##lang.reservation.comment##: ##reservation.comment##
======================================================================
','&lt;!-- description{ color: inherit; background: #ebebeb;border-style: solid;border-color: #8d8d8d; border-width: 0px 1px 1px 0px; } --&gt;
&lt;p&gt;&lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;##lang.reservation.user##:&lt;/span&gt;##reservation.user##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;##lang.reservation.item.name##:&lt;/span&gt;##reservation.itemtype## - ##reservation.item.name##&lt;br /&gt;##IFreservation.tech## ##lang.reservation.tech## ##reservation.tech####ENDIFreservation.tech##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;##lang.reservation.begin##:&lt;/span&gt; ##reservation.begin##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;##lang.reservation.end##:&lt;/span&gt;##reservation.end##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;##lang.reservation.comment##:&lt;/span&gt; ##reservation.comment##&lt;/p&gt;');
INSERT INTO `glpi_notificationtemplatetranslations` VALUES ('3','3','','##reservation.action##  ##reservation.entity##','##lang.reservation.entity## : ##reservation.entity## 

 
##FOREACHreservations## 
##lang.reservation.itemtype## : ##reservation.itemtype##

 ##lang.reservation.item## : ##reservation.item##
 
 ##reservation.url## 

 ##ENDFOREACHreservations##','&lt;p&gt;##lang.reservation.entity## : ##reservation.entity## &lt;br /&gt; &lt;br /&gt;
##FOREACHreservations## &lt;br /&gt;##lang.reservation.itemtype## :  ##reservation.itemtype##&lt;br /&gt;
 ##lang.reservation.item## :  ##reservation.item##&lt;br /&gt; &lt;br /&gt;
 &lt;a href=\"##reservation.url##\"&gt; ##reservation.url##&lt;/a&gt;&lt;br /&gt;
 ##ENDFOREACHreservations##&lt;/p&gt;');
INSERT INTO `glpi_notificationtemplatetranslations` VALUES ('4','4','','##ticket.action## ##ticket.title##',' ##IFticket.storestatus=solved##
 ##lang.ticket.url## : ##ticket.urlapprove##
 ##lang.ticket.autoclosewarning##
 ##lang.ticket.solvedate## : ##ticket.solvedate##
 ##lang.ticket.solution.type## : ##ticket.solution.type##
 ##lang.ticket.solution.description## : ##ticket.solution.description## ##ENDIFticket.storestatus##
 ##ELSEticket.storestatus## ##lang.ticket.url## : ##ticket.url## ##ENDELSEticket.storestatus##

 ##lang.ticket.description##

 ##lang.ticket.title## : ##ticket.title##
 ##lang.ticket.author.name## : ##IFticket.author.name## ##ticket.author.name## ##ENDIFticket.author.name## ##ELSEticket.author.name##--##ENDELSEticket.author.name##
 ##lang.ticket.creationdate## : ##ticket.creationdate##
 ##lang.ticket.closedate## : ##ticket.closedate##
 ##lang.ticket.requesttype## : ##ticket.requesttype##
##IFticket.itemtype## ##lang.ticket.item.name## : ##ticket.itemtype## - ##ticket.item.name## ##IFticket.item.model## - ##ticket.item.model## ##ENDIFticket.item.model## ##IFticket.item.serial## - ##ticket.item.serial## ##ENDIFticket.item.serial##  ##IFticket.item.otherserial## -##ticket.item.otherserial## ##ENDIFticket.item.otherserial## ##ENDIFticket.itemtype##
##IFticket.assigntouser## ##lang.ticket.assigntouser## : ##ticket.assigntouser## ##ENDIFticket.assigntouser##
 ##lang.ticket.status## : ##ticket.status##
##IFticket.assigntogroup## ##lang.ticket.assigntogroup## : ##ticket.assigntogroup## ##ENDIFticket.assigntogroup##
 ##lang.ticket.urgency## : ##ticket.urgency##
 ##lang.ticket.impact## : ##ticket.impact##
 ##lang.ticket.priority## : ##ticket.priority##
##IFticket.user.email## ##lang.ticket.user.email## : ##ticket.user.email ##ENDIFticket.user.email##
##IFticket.category## ##lang.ticket.category## : ##ticket.category## ##ENDIFticket.category## ##ELSEticket.category## ##lang.ticket.nocategoryassigned## ##ENDELSEticket.category##
 ##lang.ticket.content## : ##ticket.content##
 ##IFticket.storestatus=closed##

 ##lang.ticket.solvedate## : ##ticket.solvedate##
 ##lang.ticket.solution.type## : ##ticket.solution.type##
 ##lang.ticket.solution.description## : ##ticket.solution.description##
 ##ENDIFticket.storestatus##
 ##lang.ticket.numberoffollowups## : ##ticket.numberoffollowups##

##FOREACHfollowups##

 [##followup.date##] ##lang.followup.isprivate## : ##followup.isprivate##
 ##lang.followup.author## ##followup.author##
 ##lang.followup.description## ##followup.description##
 ##lang.followup.date## ##followup.date##
 ##lang.followup.requesttype## ##followup.requesttype##

##ENDFOREACHfollowups##
 ##lang.ticket.numberoftasks## : ##ticket.numberoftasks##

##FOREACHtasks##

 [##task.date##] ##lang.task.isprivate## : ##task.isprivate##
 ##lang.task.author## ##task.author##
 ##lang.task.description## ##task.description##
 ##lang.task.time## ##task.time##
 ##lang.task.category## ##task.category##

##ENDFOREACHtasks##','<!-- description{ color: inherit; background: #ebebeb; border-style: solid;border-color: #8d8d8d; border-width: 0px 1px 1px 0px; }    -->
<div>##IFticket.storestatus=solved##</div>
<div>##lang.ticket.url## : <a href=\"##ticket.urlapprove##\">##ticket.urlapprove##</a> <strong>&#160;</strong></div>
<div><strong>##lang.ticket.autoclosewarning##</strong></div>
<div><span style="color: #888888;"><strong><span style="text-decoration: underline;">##lang.ticket.solvedate##</span></strong></span> : ##ticket.solvedate##<br /><span style="text-decoration: underline; color: #888888;"><strong>##lang.ticket.solution.type##</strong></span> : ##ticket.solution.type##<br /><span style="text-decoration: underline; color: #888888;"><strong>##lang.ticket.solution.description##</strong></span> : ##ticket.solution.description## ##ENDIFticket.storestatus##</div>
<div>##ELSEticket.storestatus## ##lang.ticket.url## : <a href=\"##ticket.url##\">##ticket.url##</a> ##ENDELSEticket.storestatus##</div>
<p class="description b"><strong>##lang.ticket.description##</strong></p>
<p><span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.title##</span>&#160;:##ticket.title## <br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.author.name##</span>&#160;:##IFticket.author.name## ##ticket.author.name## ##ENDIFticket.author.name##    ##ELSEticket.author.name##--##ENDELSEticket.author.name## <br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.creationdate##</span>&#160;:##ticket.creationdate## <br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.closedate##</span>&#160;:##ticket.closedate## <br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.requesttype##</span>&#160;:##ticket.requesttype##<br /> ##IFticket.itemtype## <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.item.name##</span>&#160;: ##ticket.itemtype## - ##ticket.item.name##    ##IFticket.item.model## - ##ticket.item.model##    ##ENDIFticket.item.model## ##IFticket.item.serial## -##ticket.item.serial## ##ENDIFticket.item.serial##&#160; ##IFticket.item.otherserial## -##ticket.item.otherserial##  ##ENDIFticket.item.otherserial## ##ENDIFticket.itemtype## <br /> ##IFticket.assigntouser## <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.assigntouser##</span>&#160;: ##ticket.assigntouser## ##ENDIFticket.assigntouser##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\">##lang.ticket.status## </span>&#160;: ##ticket.status##<br /> ##IFticket.assigntogroup## <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.assigntogroup##</span>&#160;: ##ticket.assigntogroup## ##ENDIFticket.assigntogroup##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.urgency##</span>&#160;: ##ticket.urgency##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.impact##</span>&#160;: ##ticket.impact##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.priority##</span>&#160;: ##ticket.priority## <br /> ##IFticket.user.email##<span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.user.email##</span>&#160;: ##ticket.user.email ##ENDIFticket.user.email##    <br /> ##IFticket.category##<span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\">##lang.ticket.category## </span>&#160;:##ticket.category## ##ENDIFticket.category## ##ELSEticket.category## ##lang.ticket.nocategoryassigned## ##ENDELSEticket.category##    <br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.content##</span>&#160;: ##ticket.content##</p>
<br />##IFticket.storestatus=closed##<br /><span style="text-decoration: underline;"><strong><span style="color: #888888;">##lang.ticket.solvedate##</span></strong></span> : ##ticket.solvedate##<br /><span style="color: #888888;"><strong><span style="text-decoration: underline;">##lang.ticket.solution.type##</span></strong></span> : ##ticket.solution.type##<br /><span style="text-decoration: underline; color: #888888;"><strong>##lang.ticket.solution.description##</strong></span> : ##ticket.solution.description##<br />##ENDIFticket.storestatus##</p>
<div class=\"description b\">##lang.ticket.numberoffollowups##&#160;: ##ticket.numberoffollowups##</div>
<p>##FOREACHfollowups##</p>
<div class=\"description b\"><br /> <strong> [##followup.date##] <em>##lang.followup.isprivate## : ##followup.isprivate## </em></strong><br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.followup.author## </span> ##followup.author##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.followup.description## </span> ##followup.description##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.followup.date## </span> ##followup.date##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.followup.requesttype## </span> ##followup.requesttype##</div>
<p>##ENDFOREACHfollowups##</p>
<div class=\"description b\">##lang.ticket.numberoftasks##&#160;: ##ticket.numberoftasks##</div>
<p>##FOREACHtasks##</p>
<div class=\"description b\"><br /> <strong> [##task.date##] <em>##lang.task.isprivate## : ##task.isprivate## </em></strong><br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.task.author##</span> ##task.author##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.task.description##</span> ##task.description##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.task.time##</span> ##task.time##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.task.category##</span> ##task.category##</div>
<p>##ENDFOREACHtasks##</p>');
INSERT INTO `glpi_notificationtemplatetranslations` VALUES ('5','12','','##contract.action##  ##contract.entity##','##lang.contract.entity## : ##contract.entity##

##FOREACHcontracts##
##lang.contract.name## : ##contract.name##
##lang.contract.number## : ##contract.number##
##lang.contract.time## : ##contract.time##
##IFcontract.type####lang.contract.type## : ##contract.type####ENDIFcontract.type##
##contract.url##
##ENDFOREACHcontracts##','&lt;p&gt;##lang.contract.entity## : ##contract.entity##&lt;br /&gt;
&lt;br /&gt;##FOREACHcontracts##&lt;br /&gt;##lang.contract.name## :
##contract.name##&lt;br /&gt;
##lang.contract.number## : ##contract.number##&lt;br /&gt;
##lang.contract.time## : ##contract.time##&lt;br /&gt;
##IFcontract.type####lang.contract.type## : ##contract.type##
##ENDIFcontract.type##&lt;br /&gt;
&lt;a href=\"##contract.url##\"&gt;
##contract.url##&lt;/a&gt;&lt;br /&gt;
##ENDFOREACHcontracts##&lt;/p&gt;');
INSERT INTO `glpi_notificationtemplatetranslations` VALUES ('6','5','','##ticket.action## ##ticket.title##','##lang.ticket.url## : ##ticket.url## 

##lang.ticket.description## 


##lang.ticket.title## &#160;:##ticket.title## 

##lang.ticket.author.name## &#160;:##IFticket.author.name##
##ticket.author.name## ##ENDIFticket.author.name##
##ELSEticket.author.name##--##ENDELSEticket.author.name## &#160; 

##IFticket.category## ##lang.ticket.category## &#160;:##ticket.category##
##ENDIFticket.category## ##ELSEticket.category##
##lang.ticket.nocategoryassigned## ##ENDELSEticket.category##

##lang.ticket.content## &#160;: ##ticket.content##
##IFticket.itemtype##
##lang.ticket.item.name## &#160;: ##ticket.itemtype## - ##ticket.item.name##
##ENDIFticket.itemtype##','&lt;div&gt;##lang.ticket.url## : &lt;a href=\"##ticket.url##\"&gt;
##ticket.url##&lt;/a&gt;&lt;/div&gt;
&lt;div class=\"description b\"&gt;
##lang.ticket.description##&lt;/div&gt;
&lt;p&gt;&lt;span
style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
##lang.ticket.title##&lt;/span&gt;&#160;:##ticket.title##
&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
##lang.ticket.author.name##&lt;/span&gt;
##IFticket.author.name## ##ticket.author.name##
##ENDIFticket.author.name##
##ELSEticket.author.name##--##ENDELSEticket.author.name##
&lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;&#160
;&lt;/span&gt;&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; &lt;/span&gt;
##IFticket.category##&lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
##lang.ticket.category## &lt;/span&gt;&#160;:##ticket.category##
##ENDIFticket.category## ##ELSEticket.category##
##lang.ticket.nocategoryassigned## ##ENDELSEticket.category##
&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
##lang.ticket.content##&lt;/span&gt;&#160;:
##ticket.content##&lt;br /&gt;##IFticket.itemtype##
&lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
##lang.ticket.item.name##&lt;/span&gt;&#160;:
##ticket.itemtype## - ##ticket.item.name##
##ENDIFticket.itemtype##&lt;/p&gt;');
INSERT INTO `glpi_notificationtemplatetranslations` VALUES ('7','7','','##ticket.action## ##ticket.title##','##FOREACHvalidations##

##IFvalidation.storestatus=waiting##
##validation.submission.title##
##lang.validation.commentsubmission## : ##validation.commentsubmission##
##ENDIFvalidation.storestatus##
##ELSEvalidation.storestatus## ##validation.answer.title## ##ENDELSEvalidation.storestatus##

##lang.ticket.url## : ##ticket.urlvalidation##

##IFvalidation.status## ##lang.validation.validationstatus## ##ENDIFvalidation.status##
##IFvalidation.commentvalidation##
##lang.validation.commentvalidation## : ##validation.commentvalidation##
##ENDIFvalidation.commentvalidation##
##ENDFOREACHvalidations##','&lt;div&gt;##FOREACHvalidations##&lt;/div&gt;
&lt;p&gt;##IFvalidation.storestatus=waiting##&lt;/p&gt;
&lt;div&gt;##validation.submission.title##&lt;/div&gt;
&lt;div&gt;##lang.validation.commentsubmission## : ##validation.commentsubmission##&lt;/div&gt;
&lt;div&gt;##ENDIFvalidation.storestatus##&lt;/div&gt;
&lt;div&gt;##ELSEvalidation.storestatus## ##validation.answer.title## ##ENDELSEvalidation.storestatus##&lt;/div&gt;
&lt;div&gt;&lt;/div&gt;
&lt;div&gt;
&lt;div&gt;##lang.ticket.url## : &lt;a href=\"##ticket.urlvalidation##\"&gt; ##ticket.urlvalidation## &lt;/a&gt;&lt;/div&gt;
&lt;/div&gt;
&lt;p&gt;##IFvalidation.status## ##lang.validation.validationstatus## ##ENDIFvalidation.status##
&lt;br /&gt; ##IFvalidation.commentvalidation##&lt;br /&gt; ##lang.validation.commentvalidation## :
&#160; ##validation.commentvalidation##&lt;br /&gt; ##ENDIFvalidation.commentvalidation##
&lt;br /&gt;##ENDFOREACHvalidations##&lt;/p&gt;');
INSERT INTO `glpi_notificationtemplatetranslations` VALUES ('8','6','','##ticket.action## ##ticket.entity##','##lang.ticket.entity## : ##ticket.entity##
 
##FOREACHtickets##

##lang.ticket.title## : ##ticket.title##
 ##lang.ticket.status## : ##ticket.status##

 ##ticket.url## 
 ##ENDFOREACHtickets##','&lt;table class=\"tab_cadre\" border=\"1\" cellspacing=\"2\" cellpadding=\"3\"&gt;
&lt;tbody&gt;
&lt;tr&gt;
&lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##lang.ticket.author.name##&lt;/span&gt;&lt;/td&gt;
&lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##lang.ticket.title##&lt;/span&gt;&lt;/td&gt;
&lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##lang.ticket.priority##&lt;/span&gt;&lt;/td&gt;
&lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##lang.ticket.status##&lt;/span&gt;&lt;/td&gt;
&lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##lang.ticket.attribution##&lt;/span&gt;&lt;/td&gt;
&lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##lang.ticket.creationdate##&lt;/span&gt;&lt;/td&gt;
&lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##lang.ticket.content##&lt;/span&gt;&lt;/td&gt;
&lt;/tr&gt;
##FOREACHtickets##                   
&lt;tr&gt;
&lt;td width=\"auto\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##ticket.author.name##&lt;/span&gt;&lt;/td&gt;
&lt;td width=\"auto\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;&lt;a href=\"##ticket.url##\"&gt;##ticket.title##&lt;/a&gt;&lt;/span&gt;&lt;/td&gt;
&lt;td width=\"auto\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##ticket.priority##&lt;/span&gt;&lt;/td&gt;
&lt;td width=\"auto\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##ticket.status##&lt;/span&gt;&lt;/td&gt;
&lt;td width=\"auto\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##IFticket.assigntouser####ticket.assigntouser##&lt;br /&gt;##ENDIFticket.assigntouser####IFticket.assigntogroup##&lt;br /&gt;##ticket.assigntogroup## ##ENDIFticket.assigntogroup####IFticket.assigntosupplier##&lt;br /&gt;##ticket.assigntosupplier## ##ENDIFticket.assigntosupplier##&lt;/span&gt;&lt;/td&gt;
&lt;td width=\"auto\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##ticket.creationdate##&lt;/span&gt;&lt;/td&gt;
&lt;td width=\"auto\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##ticket.content##&lt;/span&gt;&lt;/td&gt;
&lt;/tr&gt;
##ENDFOREACHtickets##
&lt;/tbody&gt;
&lt;/table&gt;');
INSERT INTO `glpi_notificationtemplatetranslations` VALUES ('9','9','','##consumable.action##  ##consumable.entity##','##lang.consumable.entity## : ##consumable.entity##
 

##FOREACHconsumables##
##lang.consumable.item## : ##consumable.item##
 

##lang.consumable.reference## : ##consumable.reference##

##lang.consumable.remaining## : ##consumable.remaining##

##consumable.url## 

##ENDFOREACHconsumables##','&lt;p&gt;
##lang.consumable.entity## : ##consumable.entity##
&lt;br /&gt; &lt;br /&gt;##FOREACHconsumables##
&lt;br /&gt;##lang.consumable.item## : ##consumable.item##&lt;br /&gt;
&lt;br /&gt;##lang.consumable.reference## : ##consumable.reference##&lt;br /&gt;
##lang.consumable.remaining## : ##consumable.remaining##&lt;br /&gt;
&lt;a href=\"##consumable.url##\"&gt; ##consumable.url##&lt;/a&gt;&lt;br /&gt;
   ##ENDFOREACHconsumables##&lt;/p&gt;');
INSERT INTO `glpi_notificationtemplatetranslations` VALUES ('10','8','','##cartridge.action##  ##cartridge.entity##','##lang.cartridge.entity## : ##cartridge.entity##
 

##FOREACHcartridges##
##lang.cartridge.item## : ##cartridge.item##
 

##lang.cartridge.reference## : ##cartridge.reference##

##lang.cartridge.remaining## : ##cartridge.remaining##

##cartridge.url## 
 ##ENDFOREACHcartridges##','&lt;p&gt;##lang.cartridge.entity## : ##cartridge.entity##
&lt;br /&gt; &lt;br /&gt;##FOREACHcartridges##
&lt;br /&gt;##lang.cartridge.item## :
##cartridge.item##&lt;br /&gt; &lt;br /&gt;
##lang.cartridge.reference## :
##cartridge.reference##&lt;br /&gt;
##lang.cartridge.remaining## :
##cartridge.remaining##&lt;br /&gt;
&lt;a href=\"##cartridge.url##\"&gt;
##cartridge.url##&lt;/a&gt;&lt;br /&gt;
##ENDFOREACHcartridges##&lt;/p&gt;');
INSERT INTO `glpi_notificationtemplatetranslations` VALUES ('11','10','','##infocom.action##  ##infocom.entity##','##lang.infocom.entity## : ##infocom.entity## 
 

##FOREACHinfocoms## 

##lang.infocom.itemtype## : ##infocom.itemtype##

##lang.infocom.item## : ##infocom.item##
 

##lang.infocom.expirationdate## : ##infocom.expirationdate##

##infocom.url## 
 ##ENDFOREACHinfocoms##','&lt;p&gt;##lang.infocom.entity## : ##infocom.entity##
&lt;br /&gt; &lt;br /&gt;##FOREACHinfocoms##
&lt;br /&gt;##lang.infocom.itemtype## : ##infocom.itemtype##&lt;br /&gt;
##lang.infocom.item## : ##infocom.item##&lt;br /&gt; &lt;br /&gt;
##lang.infocom.expirationdate## : ##infocom.expirationdate##
&lt;br /&gt; &lt;a href=\"##infocom.url##\"&gt;
##infocom.url##&lt;/a&gt;&lt;br /&gt;
##ENDFOREACHinfocoms##&lt;/p&gt;');
INSERT INTO `glpi_notificationtemplatetranslations` VALUES ('12','11','','##license.action##  ##license.entity##','##lang.license.entity## : ##license.entity##

##FOREACHlicenses## 

##lang.license.item## : ##license.item##

##lang.license.serial## : ##license.serial##

##lang.license.expirationdate## : ##license.expirationdate##

##license.url## 
 ##ENDFOREACHlicenses##','&lt;p&gt;
##lang.license.entity## : ##license.entity##&lt;br /&gt;
##FOREACHlicenses##
&lt;br /&gt;##lang.license.item## : ##license.item##&lt;br /&gt;
##lang.license.serial## : ##license.serial##&lt;br /&gt;
##lang.license.expirationdate## : ##license.expirationdate##
&lt;br /&gt; &lt;a href=\"##license.url##\"&gt; ##license.url##
&lt;/a&gt;&lt;br /&gt; ##ENDFOREACHlicenses##&lt;/p&gt;');

### Dump table glpi_notimportedemails

DROP TABLE IF EXISTS `glpi_notimportedemails`;
CREATE TABLE `glpi_notimportedemails` (
  `id` int(11) NOT NULL auto_increment,
  `from` varchar(255) NOT NULL,
  `to` varchar(255) NOT NULL,
  `mailcollectors_id` int(11) NOT NULL default '0',
  `date` datetime NOT NULL,
  `subject` text,
  `messageid` varchar(255) NOT NULL,
  `reason` int(11) NOT NULL default '0',
  `users_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `users_id` (`users_id`),
  KEY `mailcollectors_id` (`mailcollectors_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


### Dump table glpi_ocsadmininfoslinks

DROP TABLE IF EXISTS `glpi_ocsadmininfoslinks`;
CREATE TABLE `glpi_ocsadmininfoslinks` (
  `id` int(11) NOT NULL auto_increment,
  `glpi_column` varchar(255) collate utf8_unicode_ci default NULL,
  `ocs_column` varchar(255) collate utf8_unicode_ci default NULL,
  `ocsservers_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `ocsservers_id` (`ocsservers_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_ocslinks

DROP TABLE IF EXISTS `glpi_ocslinks`;
CREATE TABLE `glpi_ocslinks` (
  `id` int(11) NOT NULL auto_increment,
  `computers_id` int(11) NOT NULL default '0',
  `ocsid` int(11) NOT NULL default '0',
  `ocs_deviceid` varchar(255) collate utf8_unicode_ci default NULL,
  `use_auto_update` tinyint(1) NOT NULL default '1',
  `last_update` datetime default NULL,
  `last_ocs_update` datetime default NULL,
  `computer_update` longtext collate utf8_unicode_ci,
  `import_device` longtext collate utf8_unicode_ci,
  `import_disk` longtext collate utf8_unicode_ci,
  `import_software` longtext collate utf8_unicode_ci,
  `import_monitor` longtext collate utf8_unicode_ci,
  `import_peripheral` longtext collate utf8_unicode_ci,
  `import_printer` longtext collate utf8_unicode_ci,
  `ocsservers_id` int(11) NOT NULL default '0',
  `import_ip` longtext collate utf8_unicode_ci,
  `ocs_agent_version` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`ocsservers_id`,`ocsid`),
  KEY `last_update` (`last_update`),
  KEY `ocs_deviceid` (`ocs_deviceid`),
  KEY `last_ocs_update` (`ocsservers_id`,`last_ocs_update`),
  KEY `computers_id` (`computers_id`),
  KEY `use_auto_update` (`use_auto_update`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_ocsservers

DROP TABLE IF EXISTS `glpi_ocsservers`;
CREATE TABLE `glpi_ocsservers` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `ocs_db_user` varchar(255) collate utf8_unicode_ci default NULL,
  `ocs_db_passwd` varchar(255) collate utf8_unicode_ci default NULL,
  `ocs_db_host` varchar(255) collate utf8_unicode_ci default NULL,
  `ocs_db_name` varchar(255) collate utf8_unicode_ci default NULL,
  `checksum` int(11) NOT NULL default '0',
  `import_periph` tinyint(1) NOT NULL default '0',
  `import_monitor` tinyint(1) NOT NULL default '0',
  `import_software` tinyint(1) NOT NULL default '0',
  `import_printer` tinyint(1) NOT NULL default '0',
  `import_general_name` tinyint(1) NOT NULL default '0',
  `import_general_os` tinyint(1) NOT NULL default '0',
  `import_general_serial` tinyint(1) NOT NULL default '0',
  `import_general_model` tinyint(1) NOT NULL default '0',
  `import_general_manufacturer` tinyint(1) NOT NULL default '0',
  `import_general_type` tinyint(1) NOT NULL default '0',
  `import_general_domain` tinyint(1) NOT NULL default '0',
  `import_general_contact` tinyint(1) NOT NULL default '0',
  `import_general_comment` tinyint(1) NOT NULL default '0',
  `import_device_processor` tinyint(1) NOT NULL default '0',
  `import_device_memory` tinyint(1) NOT NULL default '0',
  `import_device_hdd` tinyint(1) NOT NULL default '0',
  `import_device_iface` tinyint(1) NOT NULL default '0',
  `import_device_gfxcard` tinyint(1) NOT NULL default '0',
  `import_device_sound` tinyint(1) NOT NULL default '0',
  `import_device_drive` tinyint(1) NOT NULL default '0',
  `import_device_port` tinyint(1) NOT NULL default '0',
  `import_device_modem` tinyint(1) NOT NULL default '0',
  `import_registry` tinyint(1) NOT NULL default '0',
  `import_os_serial` tinyint(1) NOT NULL default '0',
  `import_ip` tinyint(1) NOT NULL default '0',
  `import_disk` tinyint(1) NOT NULL default '0',
  `import_monitor_comment` tinyint(1) NOT NULL default '0',
  `states_id_default` int(11) NOT NULL default '0',
  `tag_limit` varchar(255) collate utf8_unicode_ci default NULL,
  `tag_exclude` varchar(255) collate utf8_unicode_ci default NULL,
  `use_soft_dict` tinyint(1) NOT NULL default '0',
  `cron_sync_number` int(11) default '1',
  `deconnection_behavior` varchar(255) collate utf8_unicode_ci default NULL,
  `is_glpi_link_enabled` tinyint(1) NOT NULL default '0',
  `use_ip_to_link` tinyint(1) NOT NULL default '0',
  `use_name_to_link` tinyint(1) NOT NULL default '0',
  `use_mac_to_link` tinyint(1) NOT NULL default '0',
  `use_serial_to_link` tinyint(1) NOT NULL default '0',
  `states_id_linkif` int(11) NOT NULL default '0',
  `ocs_url` varchar(255) collate utf8_unicode_ci default NULL,
  `date_mod` datetime default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `date_mod` (`date_mod`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_ocsservers` VALUES ('1','localhost','ocs','ocs','localhost','ocsweb','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','',NULL,'0','1',NULL,'0','0','0','0','0','0','',NULL,NULL);

### Dump table glpi_operatingsystems

DROP TABLE IF EXISTS `glpi_operatingsystems`;
CREATE TABLE `glpi_operatingsystems` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_operatingsystemservicepacks

DROP TABLE IF EXISTS `glpi_operatingsystemservicepacks`;
CREATE TABLE `glpi_operatingsystemservicepacks` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_operatingsystemversions

DROP TABLE IF EXISTS `glpi_operatingsystemversions`;
CREATE TABLE `glpi_operatingsystemversions` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_peripheralmodels

DROP TABLE IF EXISTS `glpi_peripheralmodels`;
CREATE TABLE `glpi_peripheralmodels` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_peripherals

DROP TABLE IF EXISTS `glpi_peripherals`;
CREATE TABLE `glpi_peripherals` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `date_mod` datetime default NULL,
  `contact` varchar(255) collate utf8_unicode_ci default NULL,
  `contact_num` varchar(255) collate utf8_unicode_ci default NULL,
  `users_id_tech` int(11) NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  `serial` varchar(255) collate utf8_unicode_ci default NULL,
  `otherserial` varchar(255) collate utf8_unicode_ci default NULL,
  `locations_id` int(11) NOT NULL default '0',
  `peripheraltypes_id` int(11) NOT NULL default '0',
  `peripheralmodels_id` int(11) NOT NULL default '0',
  `brand` varchar(255) collate utf8_unicode_ci default NULL,
  `manufacturers_id` int(11) NOT NULL default '0',
  `is_global` tinyint(1) NOT NULL default '0',
  `is_deleted` tinyint(1) NOT NULL default '0',
  `is_template` tinyint(1) NOT NULL default '0',
  `template_name` varchar(255) collate utf8_unicode_ci default NULL,
  `notepad` longtext collate utf8_unicode_ci,
  `users_id` int(11) NOT NULL default '0',
  `groups_id` int(11) NOT NULL default '0',
  `states_id` int(11) NOT NULL default '0',
  `ticket_tco` decimal(20,4) default '0.0000',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `is_template` (`is_template`),
  KEY `is_global` (`is_global`),
  KEY `entities_id` (`entities_id`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `groups_id` (`groups_id`),
  KEY `users_id` (`users_id`),
  KEY `locations_id` (`locations_id`),
  KEY `peripheralmodels_id` (`peripheralmodels_id`),
  KEY `states_id` (`states_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `peripheraltypes_id` (`peripheraltypes_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `date_mod` (`date_mod`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_peripheraltypes

DROP TABLE IF EXISTS `glpi_peripheraltypes`;
CREATE TABLE `glpi_peripheraltypes` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_phonemodels

DROP TABLE IF EXISTS `glpi_phonemodels`;
CREATE TABLE `glpi_phonemodels` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_phonepowersupplies

DROP TABLE IF EXISTS `glpi_phonepowersupplies`;
CREATE TABLE `glpi_phonepowersupplies` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_phones

DROP TABLE IF EXISTS `glpi_phones`;
CREATE TABLE `glpi_phones` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `date_mod` datetime default NULL,
  `contact` varchar(255) collate utf8_unicode_ci default NULL,
  `contact_num` varchar(255) collate utf8_unicode_ci default NULL,
  `users_id_tech` int(11) NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  `serial` varchar(255) collate utf8_unicode_ci default NULL,
  `otherserial` varchar(255) collate utf8_unicode_ci default NULL,
  `firmware` varchar(255) collate utf8_unicode_ci default NULL,
  `locations_id` int(11) NOT NULL default '0',
  `phonetypes_id` int(11) NOT NULL default '0',
  `phonemodels_id` int(11) NOT NULL default '0',
  `brand` varchar(255) collate utf8_unicode_ci default NULL,
  `phonepowersupplies_id` int(11) NOT NULL default '0',
  `number_line` varchar(255) collate utf8_unicode_ci default NULL,
  `have_headset` tinyint(1) NOT NULL default '0',
  `have_hp` tinyint(1) NOT NULL default '0',
  `manufacturers_id` int(11) NOT NULL default '0',
  `is_global` tinyint(1) NOT NULL default '0',
  `is_deleted` tinyint(1) NOT NULL default '0',
  `is_template` tinyint(1) NOT NULL default '0',
  `template_name` varchar(255) collate utf8_unicode_ci default NULL,
  `notepad` longtext collate utf8_unicode_ci,
  `users_id` int(11) NOT NULL default '0',
  `groups_id` int(11) NOT NULL default '0',
  `states_id` int(11) NOT NULL default '0',
  `ticket_tco` decimal(20,4) default '0.0000',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `is_template` (`is_template`),
  KEY `is_global` (`is_global`),
  KEY `entities_id` (`entities_id`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `groups_id` (`groups_id`),
  KEY `users_id` (`users_id`),
  KEY `locations_id` (`locations_id`),
  KEY `phonemodels_id` (`phonemodels_id`),
  KEY `phonepowersupplies_id` (`phonepowersupplies_id`),
  KEY `states_id` (`states_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `phonetypes_id` (`phonetypes_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `date_mod` (`date_mod`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_phonetypes

DROP TABLE IF EXISTS `glpi_phonetypes`;
CREATE TABLE `glpi_phonetypes` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_plugins

DROP TABLE IF EXISTS `glpi_plugins`;
CREATE TABLE `glpi_plugins` (
  `id` int(11) NOT NULL auto_increment,
  `directory` varchar(255) collate utf8_unicode_ci NOT NULL,
  `name` varchar(255) collate utf8_unicode_ci NOT NULL,
  `version` varchar(255) collate utf8_unicode_ci NOT NULL,
  `state` int(11) NOT NULL default '0' COMMENT 'see define.php PLUGIN_* constant',
  `author` varchar(255) collate utf8_unicode_ci default NULL,
  `homepage` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`directory`),
  KEY `state` (`state`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_printermodels

DROP TABLE IF EXISTS `glpi_printermodels`;
CREATE TABLE `glpi_printermodels` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_printers

DROP TABLE IF EXISTS `glpi_printers`;
CREATE TABLE `glpi_printers` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `is_recursive` tinyint(1) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `date_mod` datetime default NULL,
  `contact` varchar(255) collate utf8_unicode_ci default NULL,
  `contact_num` varchar(255) collate utf8_unicode_ci default NULL,
  `users_id_tech` int(11) NOT NULL default '0',
  `serial` varchar(255) collate utf8_unicode_ci default NULL,
  `otherserial` varchar(255) collate utf8_unicode_ci default NULL,
  `have_serial` tinyint(1) NOT NULL default '0',
  `have_parallel` tinyint(1) NOT NULL default '0',
  `have_usb` tinyint(1) NOT NULL default '0',
  `have_wifi` tinyint(1) NOT NULL default '0',
  `have_ethernet` tinyint(1) NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  `memory_size` varchar(255) collate utf8_unicode_ci default NULL,
  `locations_id` int(11) NOT NULL default '0',
  `domains_id` int(11) NOT NULL default '0',
  `networks_id` int(11) NOT NULL default '0',
  `printertypes_id` int(11) NOT NULL default '0',
  `printermodels_id` int(11) NOT NULL default '0',
  `manufacturers_id` int(11) NOT NULL default '0',
  `is_global` tinyint(1) NOT NULL default '0',
  `is_deleted` tinyint(1) NOT NULL default '0',
  `is_template` tinyint(1) NOT NULL default '0',
  `template_name` varchar(255) collate utf8_unicode_ci default NULL,
  `init_pages_counter` int(11) NOT NULL default '0',
  `notepad` longtext collate utf8_unicode_ci,
  `users_id` int(11) NOT NULL default '0',
  `groups_id` int(11) NOT NULL default '0',
  `states_id` int(11) NOT NULL default '0',
  `ticket_tco` decimal(20,4) default '0.0000',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `is_template` (`is_template`),
  KEY `is_global` (`is_global`),
  KEY `domains_id` (`domains_id`),
  KEY `entities_id` (`entities_id`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `groups_id` (`groups_id`),
  KEY `users_id` (`users_id`),
  KEY `locations_id` (`locations_id`),
  KEY `printermodels_id` (`printermodels_id`),
  KEY `networks_id` (`networks_id`),
  KEY `states_id` (`states_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `printertypes_id` (`printertypes_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `date_mod` (`date_mod`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_printertypes

DROP TABLE IF EXISTS `glpi_printertypes`;
CREATE TABLE `glpi_printertypes` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_profiles

DROP TABLE IF EXISTS `glpi_profiles`;
CREATE TABLE `glpi_profiles` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `interface` varchar(255) collate utf8_unicode_ci default 'helpdesk',
  `is_default` tinyint(1) NOT NULL default '0',
  `computer` char(1) collate utf8_unicode_ci default NULL,
  `monitor` char(1) collate utf8_unicode_ci default NULL,
  `software` char(1) collate utf8_unicode_ci default NULL,
  `networking` char(1) collate utf8_unicode_ci default NULL,
  `printer` char(1) collate utf8_unicode_ci default NULL,
  `peripheral` char(1) collate utf8_unicode_ci default NULL,
  `cartridge` char(1) collate utf8_unicode_ci default NULL,
  `consumable` char(1) collate utf8_unicode_ci default NULL,
  `phone` char(1) collate utf8_unicode_ci default NULL,
  `notes` char(1) collate utf8_unicode_ci default NULL,
  `contact_enterprise` char(1) collate utf8_unicode_ci default NULL,
  `document` char(1) collate utf8_unicode_ci default NULL,
  `contract` char(1) collate utf8_unicode_ci default NULL,
  `infocom` char(1) collate utf8_unicode_ci default NULL,
  `knowbase` char(1) collate utf8_unicode_ci default NULL,
  `faq` char(1) collate utf8_unicode_ci default NULL,
  `reservation_helpdesk` char(1) collate utf8_unicode_ci default NULL,
  `reservation_central` char(1) collate utf8_unicode_ci default NULL,
  `reports` char(1) collate utf8_unicode_ci default NULL,
  `ocsng` char(1) collate utf8_unicode_ci default NULL,
  `view_ocsng` char(1) collate utf8_unicode_ci default NULL,
  `sync_ocsng` char(1) collate utf8_unicode_ci default NULL,
  `dropdown` char(1) collate utf8_unicode_ci default NULL,
  `entity_dropdown` char(1) collate utf8_unicode_ci default NULL,
  `device` char(1) collate utf8_unicode_ci default NULL,
  `typedoc` char(1) collate utf8_unicode_ci default NULL,
  `link` char(1) collate utf8_unicode_ci default NULL,
  `config` char(1) collate utf8_unicode_ci default NULL,
  `rule_ticket` char(1) collate utf8_unicode_ci default NULL,
  `entity_rule_ticket` char(1) collate utf8_unicode_ci default NULL,
  `rule_ocs` char(1) collate utf8_unicode_ci default NULL,
  `rule_ldap` char(1) collate utf8_unicode_ci default NULL,
  `rule_softwarecategories` char(1) collate utf8_unicode_ci default NULL,
  `search_config` char(1) collate utf8_unicode_ci default NULL,
  `search_config_global` char(1) collate utf8_unicode_ci default NULL,
  `check_update` char(1) collate utf8_unicode_ci default NULL,
  `profile` char(1) collate utf8_unicode_ci default NULL,
  `user` char(1) collate utf8_unicode_ci default NULL,
  `user_authtype` char(1) collate utf8_unicode_ci default NULL,
  `group` char(1) collate utf8_unicode_ci default NULL,
  `entity` char(1) collate utf8_unicode_ci default NULL,
  `transfer` char(1) collate utf8_unicode_ci default NULL,
  `logs` char(1) collate utf8_unicode_ci default NULL,
  `reminder_public` char(1) collate utf8_unicode_ci default NULL,
  `bookmark_public` char(1) collate utf8_unicode_ci default NULL,
  `backup` char(1) collate utf8_unicode_ci default NULL,
  `create_ticket` char(1) collate utf8_unicode_ci default NULL,
  `delete_ticket` char(1) collate utf8_unicode_ci default NULL,
  `add_followups` char(1) collate utf8_unicode_ci default NULL,
  `group_add_followups` char(1) collate utf8_unicode_ci default NULL,
  `global_add_followups` char(1) collate utf8_unicode_ci default NULL,
  `global_add_tasks` char(1) collate utf8_unicode_ci default NULL,
  `update_ticket` char(1) collate utf8_unicode_ci default NULL,
  `update_priority` char(1) collate utf8_unicode_ci default NULL,
  `own_ticket` char(1) collate utf8_unicode_ci default NULL,
  `steal_ticket` char(1) collate utf8_unicode_ci default NULL,
  `assign_ticket` char(1) collate utf8_unicode_ci default NULL,
  `show_all_ticket` char(1) collate utf8_unicode_ci default NULL,
  `show_assign_ticket` char(1) collate utf8_unicode_ci default NULL,
  `show_full_ticket` char(1) collate utf8_unicode_ci default NULL,
  `observe_ticket` char(1) collate utf8_unicode_ci default NULL,
  `update_followups` char(1) collate utf8_unicode_ci default NULL,
  `update_tasks` char(1) collate utf8_unicode_ci default NULL,
  `show_planning` char(1) collate utf8_unicode_ci default NULL,
  `show_group_planning` char(1) collate utf8_unicode_ci default NULL,
  `show_all_planning` char(1) collate utf8_unicode_ci default NULL,
  `statistic` char(1) collate utf8_unicode_ci default NULL,
  `password_update` char(1) collate utf8_unicode_ci default NULL,
  `helpdesk_hardware` int(11) NOT NULL default '0',
  `helpdesk_item_type` text collate utf8_unicode_ci,
  `helpdesk_status` text collate utf8_unicode_ci COMMENT 'json encoded array of from/dest allowed status change',
  `show_group_ticket` char(1) collate utf8_unicode_ci default NULL,
  `show_group_hardware` char(1) collate utf8_unicode_ci default NULL,
  `rule_dictionnary_software` char(1) collate utf8_unicode_ci default NULL,
  `rule_dictionnary_dropdown` char(1) collate utf8_unicode_ci default NULL,
  `budget` char(1) collate utf8_unicode_ci default NULL,
  `import_externalauth_users` char(1) collate utf8_unicode_ci default NULL,
  `notification` char(1) collate utf8_unicode_ci default NULL,
  `rule_mailcollector` char(1) collate utf8_unicode_ci default NULL,
  `date_mod` datetime default NULL,
  `comment` text collate utf8_unicode_ci,
  `validate_ticket` char(1) collate utf8_unicode_ci default NULL,
  `create_validation` char(1) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `interface` (`interface`),
  KEY `is_default` (`is_default`),
  KEY `date_mod` (`date_mod`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_profiles` VALUES ('1','post-only','helpdesk','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'r','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1',NULL,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1',NULL,NULL,NULL,NULL,NULL,NULL,'1','1','[\"Computer\",\"Software\",\"Phone\"]',NULL,'0','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `glpi_profiles` VALUES ('2','normal','central','0','r','r','r','r','r','r','r','r','r','r','r','r','r','r','r','r','1','r','r',NULL,'r',NULL,NULL,NULL,NULL,'r','r',NULL,NULL,NULL,NULL,NULL,NULL,'w',NULL,'r',NULL,'r','r','r',NULL,NULL,NULL,NULL,NULL,NULL,'1','1','1','0','0','0','0','0','1','0','0','1','1','0','1','0','0','1','0','0','1','1','1','[\"Computer\",\"Software\",\"Phone\"]',NULL,'0','0',NULL,NULL,'r',NULL,NULL,NULL,NULL,NULL,'1','1');
INSERT INTO `glpi_profiles` VALUES ('3','admin','central','0','w','w','w','w','w','w','w','w','w','w','w','w','w','w','w','w','1','w','r','w','r','w','w','w','w','w','w',NULL,NULL,NULL,NULL,NULL,NULL,'w','w','r','r','w','w','w',NULL,NULL,NULL,NULL,NULL,NULL,'1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','3','[\"Computer\",\"Software\",\"Phone\"]',NULL,'0','0',NULL,NULL,'w','w',NULL,NULL,NULL,NULL,'1','1');
INSERT INTO `glpi_profiles` VALUES ('4','super-admin','central','0','w','w','w','w','w','w','w','w','w','w','w','w','w','w','w','w','1','w','r','w','r','w','w','w','w','w','w','w','r','w','w','w','w','w','w','r','w','w','w','w','w','w','r','w','w','w','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','3','[\"Computer\",\"Software\",\"Phone\"]',NULL,'0','0','w','w','w','w','w','w',NULL,NULL,'1','1');

### Dump table glpi_profiles_users

DROP TABLE IF EXISTS `glpi_profiles_users`;
CREATE TABLE `glpi_profiles_users` (
  `id` int(11) NOT NULL auto_increment,
  `users_id` int(11) NOT NULL default '0',
  `profiles_id` int(11) NOT NULL default '0',
  `entities_id` int(11) NOT NULL default '0',
  `is_recursive` tinyint(1) NOT NULL default '1',
  `is_dynamic` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `entities_id` (`entities_id`),
  KEY `profiles_id` (`profiles_id`),
  KEY `users_id` (`users_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `is_dynamic` (`is_dynamic`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_profiles_users` VALUES ('2','2','4','0','1','0');
INSERT INTO `glpi_profiles_users` VALUES ('3','3','1','0','1','0');
INSERT INTO `glpi_profiles_users` VALUES ('4','4','4','0','1','0');
INSERT INTO `glpi_profiles_users` VALUES ('5','5','2','0','1','0');

### Dump table glpi_registrykeys

DROP TABLE IF EXISTS `glpi_registrykeys`;
CREATE TABLE `glpi_registrykeys` (
  `id` int(11) NOT NULL auto_increment,
  `computers_id` int(11) NOT NULL default '0',
  `hive` varchar(255) collate utf8_unicode_ci default NULL,
  `path` varchar(255) collate utf8_unicode_ci default NULL,
  `value` varchar(255) collate utf8_unicode_ci default NULL,
  `ocs_name` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `computers_id` (`computers_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_reminders

DROP TABLE IF EXISTS `glpi_reminders`;
CREATE TABLE `glpi_reminders` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `date` datetime default NULL,
  `users_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `text` text collate utf8_unicode_ci,
  `is_private` tinyint(1) NOT NULL default '1',
  `is_recursive` tinyint(1) NOT NULL default '0',
  `begin` datetime default NULL,
  `end` datetime default NULL,
  `is_planned` tinyint(1) NOT NULL default '0',
  `date_mod` datetime default NULL,
  `state` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `date` (`date`),
  KEY `begin` (`begin`),
  KEY `end` (`end`),
  KEY `entities_id` (`entities_id`),
  KEY `users_id` (`users_id`),
  KEY `is_private` (`is_private`),
  KEY `is_recursive` (`is_recursive`),
  KEY `is_planned` (`is_planned`),
  KEY `state` (`state`),
  KEY `date_mod` (`date_mod`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_requesttypes

DROP TABLE IF EXISTS `glpi_requesttypes`;
CREATE TABLE `glpi_requesttypes` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `is_helpdesk_default` tinyint(1) NOT NULL default '0',
  `is_mail_default` tinyint(1) NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `is_helpdesk_default` (`is_helpdesk_default`),
  KEY `is_mail_default` (`is_mail_default`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_requesttypes` VALUES ('1','Helpdesk','1','0',NULL);
INSERT INTO `glpi_requesttypes` VALUES ('2','E-Mail','0','1',NULL);
INSERT INTO `glpi_requesttypes` VALUES ('3','Phone','0','0',NULL);
INSERT INTO `glpi_requesttypes` VALUES ('4','Direct','0','0',NULL);
INSERT INTO `glpi_requesttypes` VALUES ('5','Written','0','0',NULL);
INSERT INTO `glpi_requesttypes` VALUES ('6','Other','0','0',NULL);

### Dump table glpi_reservationitems

DROP TABLE IF EXISTS `glpi_reservationitems`;
CREATE TABLE `glpi_reservationitems` (
  `id` int(11) NOT NULL auto_increment,
  `itemtype` varchar(100) collate utf8_unicode_ci NOT NULL,
  `entities_id` int(11) NOT NULL default '0',
  `is_recursive` tinyint(1) NOT NULL default '0',
  `items_id` int(11) NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  `is_active` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`),
  KEY `is_active` (`is_active`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_reservations

DROP TABLE IF EXISTS `glpi_reservations`;
CREATE TABLE `glpi_reservations` (
  `id` int(11) NOT NULL auto_increment,
  `reservationitems_id` int(11) NOT NULL default '0',
  `begin` datetime default NULL,
  `end` datetime default NULL,
  `users_id` int(11) NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `begin` (`begin`),
  KEY `end` (`end`),
  KEY `reservationitems_id` (`reservationitems_id`),
  KEY `users_id` (`users_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_ruleactions

DROP TABLE IF EXISTS `glpi_ruleactions`;
CREATE TABLE `glpi_ruleactions` (
  `id` int(11) NOT NULL auto_increment,
  `rules_id` int(11) NOT NULL default '0',
  `action_type` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'VALUE IN (assign, regex_result, append_regex_result, affectbyip, affectbyfqdn, affectbymac)',
  `field` varchar(255) collate utf8_unicode_ci default NULL,
  `value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_ruleactions` VALUES ('1','1','assign','entities_id','0');
INSERT INTO `glpi_ruleactions` VALUES ('2','2','assign','entities_id','0');
INSERT INTO `glpi_ruleactions` VALUES ('3','3','assign','entities_id','0');

### Dump table glpi_rulecachecomputermodels

DROP TABLE IF EXISTS `glpi_rulecachecomputermodels`;
CREATE TABLE `glpi_rulecachecomputermodels` (
  `id` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rules_id` int(11) NOT NULL default '0',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  `manufacturer` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `old_value` (`old_value`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulecachecomputertypes

DROP TABLE IF EXISTS `glpi_rulecachecomputertypes`;
CREATE TABLE `glpi_rulecachecomputertypes` (
  `id` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rules_id` int(11) NOT NULL default '0',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `old_value` (`old_value`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulecachemanufacturers

DROP TABLE IF EXISTS `glpi_rulecachemanufacturers`;
CREATE TABLE `glpi_rulecachemanufacturers` (
  `id` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rules_id` int(11) NOT NULL default '0',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `old_value` (`old_value`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulecachemonitormodels

DROP TABLE IF EXISTS `glpi_rulecachemonitormodels`;
CREATE TABLE `glpi_rulecachemonitormodels` (
  `id` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rules_id` int(11) NOT NULL default '0',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  `manufacturer` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `old_value` (`old_value`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulecachemonitortypes

DROP TABLE IF EXISTS `glpi_rulecachemonitortypes`;
CREATE TABLE `glpi_rulecachemonitortypes` (
  `id` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rules_id` int(11) NOT NULL default '0',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `old_value` (`old_value`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulecachenetworkequipmentmodels

DROP TABLE IF EXISTS `glpi_rulecachenetworkequipmentmodels`;
CREATE TABLE `glpi_rulecachenetworkequipmentmodels` (
  `id` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rules_id` int(11) NOT NULL default '0',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  `manufacturer` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `old_value` (`old_value`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulecachenetworkequipmenttypes

DROP TABLE IF EXISTS `glpi_rulecachenetworkequipmenttypes`;
CREATE TABLE `glpi_rulecachenetworkequipmenttypes` (
  `id` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rules_id` int(11) NOT NULL default '0',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `old_value` (`old_value`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulecacheoperatingsystems

DROP TABLE IF EXISTS `glpi_rulecacheoperatingsystems`;
CREATE TABLE `glpi_rulecacheoperatingsystems` (
  `id` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rules_id` int(11) NOT NULL default '0',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `old_value` (`old_value`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulecacheoperatingsystemservicepacks

DROP TABLE IF EXISTS `glpi_rulecacheoperatingsystemservicepacks`;
CREATE TABLE `glpi_rulecacheoperatingsystemservicepacks` (
  `id` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rules_id` int(11) NOT NULL default '0',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `old_value` (`old_value`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulecacheoperatingsystemversions

DROP TABLE IF EXISTS `glpi_rulecacheoperatingsystemversions`;
CREATE TABLE `glpi_rulecacheoperatingsystemversions` (
  `id` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rules_id` int(11) NOT NULL default '0',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `old_value` (`old_value`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulecacheperipheralmodels

DROP TABLE IF EXISTS `glpi_rulecacheperipheralmodels`;
CREATE TABLE `glpi_rulecacheperipheralmodels` (
  `id` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rules_id` int(11) NOT NULL default '0',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  `manufacturer` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `old_value` (`old_value`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulecacheperipheraltypes

DROP TABLE IF EXISTS `glpi_rulecacheperipheraltypes`;
CREATE TABLE `glpi_rulecacheperipheraltypes` (
  `id` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rules_id` int(11) NOT NULL default '0',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `old_value` (`old_value`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulecachephonemodels

DROP TABLE IF EXISTS `glpi_rulecachephonemodels`;
CREATE TABLE `glpi_rulecachephonemodels` (
  `id` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rules_id` int(11) NOT NULL default '0',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  `manufacturer` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `old_value` (`old_value`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulecachephonetypes

DROP TABLE IF EXISTS `glpi_rulecachephonetypes`;
CREATE TABLE `glpi_rulecachephonetypes` (
  `id` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rules_id` int(11) NOT NULL default '0',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `old_value` (`old_value`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulecacheprintermodels

DROP TABLE IF EXISTS `glpi_rulecacheprintermodels`;
CREATE TABLE `glpi_rulecacheprintermodels` (
  `id` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rules_id` int(11) NOT NULL default '0',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  `manufacturer` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `old_value` (`old_value`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulecacheprintertypes

DROP TABLE IF EXISTS `glpi_rulecacheprintertypes`;
CREATE TABLE `glpi_rulecacheprintertypes` (
  `id` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rules_id` int(11) NOT NULL default '0',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `old_value` (`old_value`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulecachesoftwares

DROP TABLE IF EXISTS `glpi_rulecachesoftwares`;
CREATE TABLE `glpi_rulecachesoftwares` (
  `id` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `manufacturer` varchar(255) collate utf8_unicode_ci NOT NULL,
  `rules_id` int(11) NOT NULL default '0',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  `version` varchar(255) collate utf8_unicode_ci default NULL,
  `new_manufacturer` varchar(255) collate utf8_unicode_ci NOT NULL,
  `ignore_ocs_import` char(1) collate utf8_unicode_ci default NULL,
  `is_helpdesk_visible` char(1) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `old_value` (`old_value`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulecriterias

DROP TABLE IF EXISTS `glpi_rulecriterias`;
CREATE TABLE `glpi_rulecriterias` (
  `id` int(11) NOT NULL auto_increment,
  `rules_id` int(11) NOT NULL default '0',
  `criteria` varchar(255) collate utf8_unicode_ci default NULL,
  `condition` int(11) NOT NULL default '0' COMMENT 'see define.php PATTERN_* and REGEX_* constant',
  `pattern` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `rules_id` (`rules_id`),
  KEY `condition` (`condition`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_rulecriterias` VALUES ('1','1','TAG','0','*');
INSERT INTO `glpi_rulecriterias` VALUES ('2','2','uid','0','*');
INSERT INTO `glpi_rulecriterias` VALUES ('3','2','samaccountname','0','*');
INSERT INTO `glpi_rulecriterias` VALUES ('4','2','MAIL_EMAIL','0','*');
INSERT INTO `glpi_rulecriterias` VALUES ('5','3','subject','6','/.*/');

### Dump table glpi_rulerightparameters

DROP TABLE IF EXISTS `glpi_rulerightparameters`;
CREATE TABLE `glpi_rulerightparameters` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `value` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_rulerightparameters` VALUES ('1','(LDAP)Organization','o','');
INSERT INTO `glpi_rulerightparameters` VALUES ('2','(LDAP)Common Name','cn','');
INSERT INTO `glpi_rulerightparameters` VALUES ('3','(LDAP)Department Number','departmentnumber','');
INSERT INTO `glpi_rulerightparameters` VALUES ('4','(LDAP)Email','mail','');
INSERT INTO `glpi_rulerightparameters` VALUES ('5','Object Class','objectclass','');
INSERT INTO `glpi_rulerightparameters` VALUES ('6','(LDAP)User ID','uid','');
INSERT INTO `glpi_rulerightparameters` VALUES ('7','(LDAP)Telephone Number','phone','');
INSERT INTO `glpi_rulerightparameters` VALUES ('8','(LDAP)Employee Number','employeenumber','');
INSERT INTO `glpi_rulerightparameters` VALUES ('9','(LDAP)Manager','manager','');
INSERT INTO `glpi_rulerightparameters` VALUES ('10','(LDAP)DistinguishedName','dn','');
INSERT INTO `glpi_rulerightparameters` VALUES ('12','(AD)User ID','samaccountname','');
INSERT INTO `glpi_rulerightparameters` VALUES ('13','(LDAP) Title','title','');

### Dump table glpi_rules

DROP TABLE IF EXISTS `glpi_rules`;
CREATE TABLE `glpi_rules` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `sub_type` varchar(255) collate utf8_unicode_ci NOT NULL default '',
  `ranking` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `description` text collate utf8_unicode_ci,
  `match` char(10) collate utf8_unicode_ci default NULL COMMENT 'see define.php *_MATCHING constant',
  `is_active` tinyint(1) NOT NULL default '1',
  `comment` text collate utf8_unicode_ci,
  `date_mod` datetime default NULL,
  `is_recursive` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_active` (`is_active`),
  KEY `sub_type` (`sub_type`),
  KEY `date_mod` (`date_mod`),
  KEY `is_recursive` (`is_recursive`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_rules` VALUES ('1','0','RuleOcs','0','Root','','AND','1',NULL,NULL,'0');
INSERT INTO `glpi_rules` VALUES ('2','0','RuleRight','1','Root','','OR','1',NULL,NULL,'0');
INSERT INTO `glpi_rules` VALUES ('3','0','RuleMailCollector','1','Root','','OR','1',NULL,NULL,'0');

### Dump table glpi_softwarecategories

DROP TABLE IF EXISTS `glpi_softwarecategories`;
CREATE TABLE `glpi_softwarecategories` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_softwarecategories` VALUES ('1','FUSION',NULL);

### Dump table glpi_softwarelicenses

DROP TABLE IF EXISTS `glpi_softwarelicenses`;
CREATE TABLE `glpi_softwarelicenses` (
  `id` int(11) NOT NULL auto_increment,
  `softwares_id` int(11) NOT NULL default '0',
  `entities_id` int(11) NOT NULL default '0',
  `is_recursive` tinyint(1) NOT NULL default '0',
  `number` int(11) NOT NULL default '0',
  `softwarelicensetypes_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `serial` varchar(255) collate utf8_unicode_ci default NULL,
  `otherserial` varchar(255) collate utf8_unicode_ci default NULL,
  `softwareversions_id_buy` int(11) NOT NULL default '0',
  `softwareversions_id_use` int(11) NOT NULL default '0',
  `expire` date default NULL,
  `computers_id` int(11) NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `serial` (`serial`),
  KEY `otherserial` (`otherserial`),
  KEY `expire` (`expire`),
  KEY `softwareversions_id_buy` (`softwareversions_id_buy`),
  KEY `computers_id` (`computers_id`),
  KEY `entities_id` (`entities_id`),
  KEY `softwares_id` (`softwares_id`),
  KEY `softwarelicensetypes_id` (`softwarelicensetypes_id`),
  KEY `softwareversions_id_use` (`softwareversions_id_use`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_softwarelicensetypes

DROP TABLE IF EXISTS `glpi_softwarelicensetypes`;
CREATE TABLE `glpi_softwarelicensetypes` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_softwarelicensetypes` VALUES ('1','OEM','');

### Dump table glpi_softwares

DROP TABLE IF EXISTS `glpi_softwares`;
CREATE TABLE `glpi_softwares` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `is_recursive` tinyint(1) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  `locations_id` int(11) NOT NULL default '0',
  `users_id_tech` int(11) NOT NULL default '0',
  `operatingsystems_id` int(11) NOT NULL default '0',
  `is_update` tinyint(1) NOT NULL default '0',
  `softwares_id` int(11) NOT NULL default '0',
  `manufacturers_id` int(11) NOT NULL default '0',
  `is_deleted` tinyint(1) NOT NULL default '0',
  `is_template` tinyint(1) NOT NULL default '0',
  `template_name` varchar(255) collate utf8_unicode_ci default NULL,
  `date_mod` datetime default NULL,
  `notepad` longtext collate utf8_unicode_ci,
  `users_id` int(11) NOT NULL default '0',
  `groups_id` int(11) NOT NULL default '0',
  `ticket_tco` decimal(20,4) default '0.0000',
  `is_helpdesk_visible` tinyint(1) NOT NULL default '1',
  `softwarecategories_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `name` (`name`),
  KEY `is_template` (`is_template`),
  KEY `is_update` (`is_update`),
  KEY `softwarecategories_id` (`softwarecategories_id`),
  KEY `entities_id` (`entities_id`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `groups_id` (`groups_id`),
  KEY `users_id` (`users_id`),
  KEY `locations_id` (`locations_id`),
  KEY `operatingsystems_id` (`operatingsystems_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `softwares_id` (`softwares_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_helpdesk_visible` (`is_helpdesk_visible`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_softwareversions

DROP TABLE IF EXISTS `glpi_softwareversions`;
CREATE TABLE `glpi_softwareversions` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `is_recursive` tinyint(1) NOT NULL default '0',
  `softwares_id` int(11) NOT NULL default '0',
  `states_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `softwares_id` (`softwares_id`),
  KEY `states_id` (`states_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_states

DROP TABLE IF EXISTS `glpi_states`;
CREATE TABLE `glpi_states` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_suppliers

DROP TABLE IF EXISTS `glpi_suppliers`;
CREATE TABLE `glpi_suppliers` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `is_recursive` tinyint(1) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `suppliertypes_id` int(11) NOT NULL default '0',
  `address` text collate utf8_unicode_ci,
  `postcode` varchar(255) collate utf8_unicode_ci default NULL,
  `town` varchar(255) collate utf8_unicode_ci default NULL,
  `state` varchar(255) collate utf8_unicode_ci default NULL,
  `country` varchar(255) collate utf8_unicode_ci default NULL,
  `website` varchar(255) collate utf8_unicode_ci default NULL,
  `phonenumber` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  `is_deleted` tinyint(1) NOT NULL default '0',
  `fax` varchar(255) collate utf8_unicode_ci default NULL,
  `email` varchar(255) collate utf8_unicode_ci default NULL,
  `notepad` longtext collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`),
  KEY `suppliertypes_id` (`suppliertypes_id`),
  KEY `is_deleted` (`is_deleted`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_suppliertypes

DROP TABLE IF EXISTS `glpi_suppliertypes`;
CREATE TABLE `glpi_suppliertypes` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_taskcategories

DROP TABLE IF EXISTS `glpi_taskcategories`;
CREATE TABLE `glpi_taskcategories` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `is_recursive` tinyint(1) NOT NULL default '0',
  `taskcategories_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `completename` text collate utf8_unicode_ci,
  `comment` text collate utf8_unicode_ci,
  `level` int(11) NOT NULL default '0',
  `ancestors_cache` longtext collate utf8_unicode_ci,
  `sons_cache` longtext collate utf8_unicode_ci,
  `is_helpdeskvisible` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `taskcategories_id` (`taskcategories_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `is_helpdeskvisible` (`is_helpdeskvisible`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_ticketcategories

DROP TABLE IF EXISTS `glpi_ticketcategories`;
CREATE TABLE `glpi_ticketcategories` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `is_recursive` tinyint(1) NOT NULL default '0',
  `ticketcategories_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `completename` text collate utf8_unicode_ci,
  `comment` text collate utf8_unicode_ci,
  `level` int(11) NOT NULL default '0',
  `knowbaseitemcategories_id` int(11) NOT NULL default '0',
  `users_id` int(11) NOT NULL default '0',
  `groups_id` int(11) NOT NULL default '0',
  `ancestors_cache` longtext collate utf8_unicode_ci,
  `sons_cache` longtext collate utf8_unicode_ci,
  `is_helpdeskvisible` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `ticketcategories_id` (`ticketcategories_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_recursive` (`is_recursive`),
  KEY `knowbaseitemcategories_id` (`knowbaseitemcategories_id`),
  KEY `users_id` (`users_id`),
  KEY `groups_id` (`groups_id`),
  KEY `is_helpdeskvisible` (`is_helpdeskvisible`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_ticketfollowups

DROP TABLE IF EXISTS `glpi_ticketfollowups`;
CREATE TABLE `glpi_ticketfollowups` (
  `id` int(11) NOT NULL auto_increment,
  `tickets_id` int(11) NOT NULL default '0',
  `date` datetime default NULL,
  `users_id` int(11) NOT NULL default '0',
  `content` longtext collate utf8_unicode_ci,
  `is_private` tinyint(1) NOT NULL default '0',
  `requesttypes_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `date` (`date`),
  KEY `users_id` (`users_id`),
  KEY `tickets_id` (`tickets_id`),
  KEY `is_private` (`is_private`),
  KEY `requesttypes_id` (`requesttypes_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_ticketplannings

DROP TABLE IF EXISTS `glpi_ticketplannings`;
CREATE TABLE `glpi_ticketplannings` (
  `id` int(11) NOT NULL auto_increment,
  `tickettasks_id` int(11) NOT NULL default '0',
  `users_id` int(11) NOT NULL default '0',
  `begin` datetime default NULL,
  `end` datetime default NULL,
  `state` int(11) NOT NULL default '1',
  PRIMARY KEY  (`id`),
  KEY `begin` (`begin`),
  KEY `end` (`end`),
  KEY `users_id` (`users_id`),
  KEY `ticketfollowups_id` (`tickettasks_id`),
  KEY `state` (`state`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_tickets

DROP TABLE IF EXISTS `glpi_tickets`;
CREATE TABLE `glpi_tickets` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `date` datetime default NULL,
  `closedate` datetime default NULL,
  `solvedate` datetime default NULL,
  `date_mod` datetime default NULL,
  `status` varchar(255) collate utf8_unicode_ci default 'new',
  `users_id` int(11) NOT NULL default '0',
  `users_id_recipient` int(11) NOT NULL default '0',
  `groups_id` int(11) NOT NULL default '0',
  `requesttypes_id` int(11) NOT NULL default '0',
  `users_id_assign` int(11) NOT NULL default '0',
  `suppliers_id_assign` int(11) NOT NULL default '0',
  `groups_id_assign` int(11) NOT NULL default '0',
  `itemtype` varchar(100) collate utf8_unicode_ci NOT NULL,
  `items_id` int(11) NOT NULL default '0',
  `content` longtext collate utf8_unicode_ci,
  `urgency` int(11) NOT NULL default '1',
  `impact` int(11) NOT NULL default '1',
  `priority` int(11) NOT NULL default '1',
  `user_email` varchar(255) collate utf8_unicode_ci default NULL,
  `use_email_notification` tinyint(1) NOT NULL default '0',
  `realtime` float NOT NULL default '0',
  `ticketcategories_id` int(11) NOT NULL default '0',
  `cost_time` decimal(20,4) NOT NULL default '0.0000',
  `cost_fixed` decimal(20,4) NOT NULL default '0.0000',
  `cost_material` decimal(20,4) NOT NULL default '0.0000',
  `ticketsolutiontypes_id` int(11) NOT NULL default '0',
  `solution` text collate utf8_unicode_ci,
  `global_validation` varchar(255) collate utf8_unicode_ci default 'accepted',
  PRIMARY KEY  (`id`),
  KEY `date` (`date`),
  KEY `closedate` (`closedate`),
  KEY `status` (`status`),
  KEY `priority` (`priority`),
  KEY `request_type` (`requesttypes_id`),
  KEY `date_mod` (`date_mod`),
  KEY `users_id_assign` (`users_id_assign`),
  KEY `groups_id_assign` (`groups_id_assign`),
  KEY `suppliers_id_assign` (`suppliers_id_assign`),
  KEY `users_id` (`users_id`),
  KEY `ticketcategories_id` (`ticketcategories_id`),
  KEY `entities_id` (`entities_id`),
  KEY `groups_id` (`groups_id`),
  KEY `users_id_recipient` (`users_id_recipient`),
  KEY `item` (`itemtype`,`items_id`),
  KEY `solvedate` (`solvedate`),
  KEY `ticketsolutiontypes_id` (`ticketsolutiontypes_id`),
  KEY `urgency` (`urgency`),
  KEY `impact` (`impact`),
  KEY `global_validation` (`global_validation`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_ticketsolutiontypes

DROP TABLE IF EXISTS `glpi_ticketsolutiontypes`;
CREATE TABLE `glpi_ticketsolutiontypes` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

### Dump table glpi_tickettasks

DROP TABLE IF EXISTS `glpi_tickettasks`;
CREATE TABLE `glpi_tickettasks` (
  `id` int(11) NOT NULL auto_increment,
  `tickets_id` int(11) NOT NULL default '0',
  `taskcategories_id` int(11) NOT NULL default '0',
  `date` datetime default NULL,
  `users_id` int(11) NOT NULL default '0',
  `content` longtext collate utf8_unicode_ci,
  `is_private` tinyint(1) NOT NULL default '0',
  `realtime` float NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `date` (`date`),
  KEY `users_id` (`users_id`),
  KEY `tickets_id` (`tickets_id`),
  KEY `is_private` (`is_private`),
  KEY `taskcategories_id` (`taskcategories_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_ticketvalidations

DROP TABLE IF EXISTS `glpi_ticketvalidations`;
CREATE TABLE `glpi_ticketvalidations` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `users_id` int(11) NOT NULL default '0',
  `tickets_id` int(11) NOT NULL default '0',
  `users_id_validate` int(11) NOT NULL default '0',
  `comment_submission` text collate utf8_unicode_ci,
  `comment_validation` text collate utf8_unicode_ci,
  `status` varchar(255) collate utf8_unicode_ci default 'waiting',
  `submission_date` datetime default NULL,
  `validation_date` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `entities_id` (`entities_id`),
  KEY `users_id` (`users_id`),
  KEY `users_id_validate` (`users_id_validate`),
  KEY `tickets_id` (`tickets_id`),
  KEY `submission_date` (`submission_date`),
  KEY `validation_date` (`validation_date`),
  KEY `status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_transfers

DROP TABLE IF EXISTS `glpi_transfers`;
CREATE TABLE `glpi_transfers` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `keep_ticket` int(11) NOT NULL default '0',
  `keep_networklink` int(11) NOT NULL default '0',
  `keep_reservation` int(11) NOT NULL default '0',
  `keep_history` int(11) NOT NULL default '0',
  `keep_device` int(11) NOT NULL default '0',
  `keep_infocom` int(11) NOT NULL default '0',
  `keep_dc_monitor` int(11) NOT NULL default '0',
  `clean_dc_monitor` int(11) NOT NULL default '0',
  `keep_dc_phone` int(11) NOT NULL default '0',
  `clean_dc_phone` int(11) NOT NULL default '0',
  `keep_dc_peripheral` int(11) NOT NULL default '0',
  `clean_dc_peripheral` int(11) NOT NULL default '0',
  `keep_dc_printer` int(11) NOT NULL default '0',
  `clean_dc_printer` int(11) NOT NULL default '0',
  `keep_supplier` int(11) NOT NULL default '0',
  `clean_supplier` int(11) NOT NULL default '0',
  `keep_contact` int(11) NOT NULL default '0',
  `clean_contact` int(11) NOT NULL default '0',
  `keep_contract` int(11) NOT NULL default '0',
  `clean_contract` int(11) NOT NULL default '0',
  `keep_software` int(11) NOT NULL default '0',
  `clean_software` int(11) NOT NULL default '0',
  `keep_document` int(11) NOT NULL default '0',
  `clean_document` int(11) NOT NULL default '0',
  `keep_cartridgeitem` int(11) NOT NULL default '0',
  `clean_cartridgeitem` int(11) NOT NULL default '0',
  `keep_cartridge` int(11) NOT NULL default '0',
  `keep_consumable` int(11) NOT NULL default '0',
  `date_mod` datetime default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `date_mod` (`date_mod`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_transfers` VALUES ('1','complete','2','2','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1',NULL,NULL);

### Dump table glpi_usercategories

DROP TABLE IF EXISTS `glpi_usercategories`;
CREATE TABLE `glpi_usercategories` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_users

DROP TABLE IF EXISTS `glpi_users`;
CREATE TABLE `glpi_users` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `password` char(40) collate utf8_unicode_ci default NULL,
  `email` varchar(255) collate utf8_unicode_ci default NULL,
  `phone` varchar(255) collate utf8_unicode_ci default NULL,
  `phone2` varchar(255) collate utf8_unicode_ci default NULL,
  `mobile` varchar(255) collate utf8_unicode_ci default NULL,
  `realname` varchar(255) collate utf8_unicode_ci default NULL,
  `firstname` varchar(255) collate utf8_unicode_ci default NULL,
  `locations_id` int(11) NOT NULL default '0',
  `language` char(10) collate utf8_unicode_ci default NULL COMMENT 'see define.php CFG_GLPI[language] array',
  `use_mode` int(11) NOT NULL default '0',
  `list_limit` int(11) default NULL,
  `is_active` tinyint(1) NOT NULL default '1',
  `comment` text collate utf8_unicode_ci,
  `auths_id` int(11) NOT NULL default '0',
  `authtype` int(11) NOT NULL default '0',
  `last_login` datetime default NULL,
  `date_mod` datetime default NULL,
  `is_deleted` tinyint(1) NOT NULL default '0',
  `profiles_id` int(11) NOT NULL default '0',
  `entities_id` int(11) NOT NULL default '0',
  `usertitles_id` int(11) NOT NULL default '0',
  `usercategories_id` int(11) NOT NULL default '0',
  `date_format` int(11) default NULL,
  `number_format` int(11) default NULL,
  `is_ids_visible` tinyint(1) default NULL,
  `dropdown_chars_limit` int(11) default NULL,
  `use_flat_dropdowntree` tinyint(1) default NULL,
  `show_jobs_at_login` tinyint(1) default NULL,
  `priority_1` char(20) collate utf8_unicode_ci default NULL,
  `priority_2` char(20) collate utf8_unicode_ci default NULL,
  `priority_3` char(20) collate utf8_unicode_ci default NULL,
  `priority_4` char(20) collate utf8_unicode_ci default NULL,
  `priority_5` char(20) collate utf8_unicode_ci default NULL,
  `priority_6` char(20) collate utf8_unicode_ci default NULL,
  `is_categorized_soft_expanded` tinyint(1) default NULL,
  `is_not_categorized_soft_expanded` tinyint(1) default NULL,
  `followup_private` tinyint(1) default NULL,
  `task_private` tinyint(1) default NULL,
  `default_requesttypes_id` int(11) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`name`),
  KEY `firstname` (`firstname`),
  KEY `realname` (`realname`),
  KEY `entities_id` (`entities_id`),
  KEY `profiles_id` (`profiles_id`),
  KEY `locations_id` (`locations_id`),
  KEY `usertitles_id` (`usertitles_id`),
  KEY `usercategories_id` (`usercategories_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_active` (`is_active`),
  KEY `date_mod` (`date_mod`),
  KEY `authitem` (`authtype`,`auths_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_users` VALUES ('2','glpi','0915bd0a5c6e56d8f38ca2b390857d4949073f41','','','','','',NULL,'0',NULL,'0','20','1',NULL,'0','1','2010-05-06 09:31:04','2010-05-06 09:31:04','0','0','0','0','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `glpi_users` VALUES ('3','post-only','3177926a7314de24680a9938aaa97703','','','','','',NULL,'0','en_GB','0','20','1',NULL,'0','0',NULL,NULL,'0','0','0','0','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `glpi_users` VALUES ('4','tech','d9f9133fb120cd6096870bc2b496805b','','','','','',NULL,'0','fr_FR','0','20','1',NULL,'0','0',NULL,NULL,'0','0','0','0','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `glpi_users` VALUES ('5','normal','fea087517c26fadd409bd4b9dc642555','','','','','',NULL,'0','en_GB','0','20','1',NULL,'0','0',NULL,NULL,'0','0','0','0','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);

### Dump table glpi_usertitles

DROP TABLE IF EXISTS `glpi_usertitles`;
CREATE TABLE `glpi_usertitles` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_vlans

DROP TABLE IF EXISTS `glpi_vlans`;
CREATE TABLE `glpi_vlans` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

