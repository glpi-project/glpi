#GLPI Dump database on 2009-07-28 17:28

### Dump table glpi_alerts

DROP TABLE IF EXISTS `glpi_alerts`;
CREATE TABLE `glpi_alerts` (
  `ID` int(11) NOT NULL auto_increment,
  `itemtype` int(11) NOT NULL default '0',
  `items_id` int(11) NOT NULL default '0',
  `type` int(11) NOT NULL default '0' COMMENT 'see define.php ALERT_* constant',
  `date` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `alert` (`itemtype`,`items_id`,`type`),
  KEY `FK_device` (`items_id`),
  KEY `type` (`type`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_authldaps

DROP TABLE IF EXISTS `glpi_authldaps`;
CREATE TABLE `glpi_authldaps` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `ldap_host` varchar(255) collate utf8_unicode_ci default NULL,
  `ldap_basedn` varchar(255) collate utf8_unicode_ci default NULL,
  `ldap_rootdn` varchar(255) collate utf8_unicode_ci default NULL,
  `ldap_pass` varchar(255) collate utf8_unicode_ci default NULL,
  `ldap_port` varchar(255) collate utf8_unicode_ci default '389',
  `ldap_condition` text collate utf8_unicode_ci,
  `ldap_login` varchar(255) collate utf8_unicode_ci default 'uid',
  `ldap_use_tls` varchar(255) collate utf8_unicode_ci default NULL,
  `ldap_field_group` varchar(255) collate utf8_unicode_ci default NULL,
  `ldap_group_condition` varchar(255) collate utf8_unicode_ci default NULL,
  `ldap_search_for_groups` int(11) NOT NULL default '0',
  `ldap_field_group_member` varchar(255) collate utf8_unicode_ci default NULL,
  `ldap_field_email` varchar(255) collate utf8_unicode_ci default NULL,
  `ldap_field_realname` varchar(255) collate utf8_unicode_ci default NULL,
  `ldap_field_firstname` varchar(255) collate utf8_unicode_ci default NULL,
  `ldap_field_phone` varchar(255) collate utf8_unicode_ci default NULL,
  `ldap_field_phone2` varchar(255) collate utf8_unicode_ci default NULL,
  `ldap_field_mobile` varchar(255) collate utf8_unicode_ci default NULL,
  `ldap_field_comments` text collate utf8_unicode_ci,
  `use_dn` int(1) NOT NULL default '1',
  `timezone` varchar(255) collate utf8_unicode_ci default NULL,
  `ldap_opt_deref` int(1) NOT NULL default '0',
  `ldap_field_title` varchar(255) collate utf8_unicode_ci default NULL,
  `ldap_field_type` varchar(255) collate utf8_unicode_ci default NULL,
  `ldap_field_language` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_authldapsreplicates

DROP TABLE IF EXISTS `glpi_authldapsreplicates`;
CREATE TABLE `glpi_authldapsreplicates` (
  `ID` int(11) NOT NULL auto_increment,
  `authldaps_id` int(11) NOT NULL default '0',
  `ldap_host` varchar(255) collate utf8_unicode_ci default NULL,
  `ldap_port` int(11) NOT NULL default '389',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `authldaps_id` (`authldaps_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_authmails

DROP TABLE IF EXISTS `glpi_authmails`;
CREATE TABLE `glpi_authmails` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `imap_auth_server` varchar(255) collate utf8_unicode_ci default NULL,
  `imap_host` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_autoupdatesystems

DROP TABLE IF EXISTS `glpi_autoupdatesystems`;
CREATE TABLE `glpi_autoupdatesystems` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_bookmarks

DROP TABLE IF EXISTS `glpi_bookmarks`;
CREATE TABLE `glpi_bookmarks` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `type` int(11) NOT NULL default '0',
  `itemtype` int(11) NOT NULL default '0',
  `users_id` int(11) NOT NULL default '0',
  `private` smallint(6) NOT NULL default '1',
  `entities_id` int(11) NOT NULL default '0',
  `recursive` smallint(6) NOT NULL default '0',
  `path` varchar(255) collate utf8_unicode_ci default NULL,
  `query` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `private` (`private`),
  KEY `recursive` (`recursive`),
  KEY `type` (`type`),
  KEY `itemtype` (`itemtype`),
  KEY `entities_id` (`entities_id`),
  KEY `users_id` (`users_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_bookmarks_users

DROP TABLE IF EXISTS `glpi_bookmarks_users`;
CREATE TABLE `glpi_bookmarks_users` (
  `ID` int(11) NOT NULL auto_increment,
  `users_id` int(11) NOT NULL default '0',
  `itemtype` int(11) NOT NULL default '0',
  `FK_bookmark` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_bookmark (ID)',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `FK_users` (`users_id`,`itemtype`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_budgets

DROP TABLE IF EXISTS `glpi_budgets`;
CREATE TABLE `glpi_budgets` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `entities_id` int(11) NOT NULL default '0',
  `recursive` tinyint(1) NOT NULL default '0',
  `comments` text collate utf8_unicode_ci,
  `deleted` tinyint(1) NOT NULL default '0',
  `begin_date` date default NULL,
  `end_date` date default NULL,
  `value` decimal(20,4) NOT NULL default '0.0000',
  `is_template` tinyint(1) NOT NULL default '0',
  `tplname` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`),
  KEY `is_template` (`is_template`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_cartridges

DROP TABLE IF EXISTS `glpi_cartridges`;
CREATE TABLE `glpi_cartridges` (
  `ID` int(11) NOT NULL auto_increment,
  `cartridgesitems_id` int(11) NOT NULL default '0',
  `printers_id` int(11) NOT NULL default '0',
  `date_in` date default NULL,
  `date_use` date default NULL,
  `date_out` date default NULL,
  `pages` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY `cartridgesitems_id` (`cartridgesitems_id`),
  KEY `printers_id` (`printers_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_cartridges_printersmodels

DROP TABLE IF EXISTS `glpi_cartridges_printersmodels`;
CREATE TABLE `glpi_cartridges_printersmodels` (
  `ID` int(11) NOT NULL auto_increment,
  `cartridgesitems_id` int(11) NOT NULL default '0',
  `printersmodels_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `FK_glpi_type_printer` (`printersmodels_id`,`cartridgesitems_id`),
  KEY `cartridgesitems_id` (`cartridgesitems_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_cartridgesitems

DROP TABLE IF EXISTS `glpi_cartridgesitems`;
CREATE TABLE `glpi_cartridgesitems` (
  `ID` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `ref` varchar(255) collate utf8_unicode_ci default NULL,
  `locations_id` int(11) NOT NULL default '0',
  `type` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_cartridge_type (ID)',
  `FK_glpi_enterprise` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_manufacturer (ID)',
  `users_id_tech` int(11) NOT NULL default '0',
  `deleted` smallint(6) NOT NULL default '0',
  `comments` text collate utf8_unicode_ci,
  `alarm` smallint(6) NOT NULL default '10',
  `notes` longtext collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `name` (`name`),
  KEY `type` (`type`),
  KEY `alarm` (`alarm`),
  KEY `deleted` (`deleted`),
  KEY `entities_id` (`entities_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `locations_id` (`locations_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_cartridgesitemstypes

DROP TABLE IF EXISTS `glpi_cartridgesitemstypes`;
CREATE TABLE `glpi_cartridgesitemstypes` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_computers

DROP TABLE IF EXISTS `glpi_computers`;
CREATE TABLE `glpi_computers` (
  `ID` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `serial` varchar(255) collate utf8_unicode_ci default NULL,
  `otherserial` varchar(255) collate utf8_unicode_ci default NULL,
  `contact` varchar(255) collate utf8_unicode_ci default NULL,
  `contact_num` varchar(255) collate utf8_unicode_ci default NULL,
  `users_id_tech` int(11) NOT NULL default '0',
  `comments` text collate utf8_unicode_ci,
  `date_mod` datetime default NULL,
  `os` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_os (ID)',
  `os_version` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_os_version (ID)',
  `os_sp` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_os_sp (ID)',
  `os_license_number` varchar(255) collate utf8_unicode_ci default NULL,
  `os_license_id` varchar(255) collate utf8_unicode_ci default NULL,
  `auto_update` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_auto_update (ID)',
  `locations_id` int(11) NOT NULL default '0',
  `domain` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_domain (ID)',
  `network` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_network (ID)',
  `model` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_model (ID)',
  `type` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_type_computers (ID)',
  `is_template` smallint(6) NOT NULL default '0',
  `tplname` varchar(255) collate utf8_unicode_ci default NULL,
  `FK_glpi_enterprise` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_manufacturer (ID)',
  `deleted` smallint(6) NOT NULL default '0',
  `notes` longtext collate utf8_unicode_ci,
  `ocs_import` smallint(6) NOT NULL default '0',
  `users_id` int(11) NOT NULL default '0',
  `FK_groups` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_groups (ID)',
  `state` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_state (ID)',
  `ticket_tco` decimal(20,4) default '0.0000',
  PRIMARY KEY  (`ID`),
  KEY `os` (`os`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `date_mod` (`date_mod`),
  KEY `name` (`name`),
  KEY `type` (`type`),
  KEY `model` (`model`),
  KEY `FK_groups` (`FK_groups`),
  KEY `os_sp` (`os_sp`),
  KEY `os_version` (`os_version`),
  KEY `network` (`network`),
  KEY `domain` (`domain`),
  KEY `auto_update` (`auto_update`),
  KEY `ocs_import` (`ocs_import`),
  KEY `is_template` (`is_template`),
  KEY `deleted` (`deleted`),
  KEY `entities_id` (`entities_id`),
  KEY `users_id` (`users_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `locations_id` (`locations_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_computers_devices

DROP TABLE IF EXISTS `glpi_computers_devices`;
CREATE TABLE `glpi_computers_devices` (
  `ID` int(11) NOT NULL auto_increment,
  `specificity` varchar(255) collate utf8_unicode_ci default NULL,
  `devicetype` int(11) NOT NULL default '0',
  `devices_id` int(11) NOT NULL default '0',
  `FK_computers` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_computers (ID)',
  PRIMARY KEY  (`ID`),
  KEY `FK_computers` (`FK_computers`),
  KEY `FK_device` (`devices_id`),
  KEY `specificity` (`specificity`),
  KEY `device_type` (`devicetype`,`devices_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_computers_items

DROP TABLE IF EXISTS `glpi_computers_items`;
CREATE TABLE `glpi_computers_items` (
  `ID` int(11) NOT NULL auto_increment,
  `end1` int(11) NOT NULL default '0' COMMENT 'RELATION to various table, according to type (ID)',
  `end2` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_computers (ID)',
  `type` smallint(6) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `connect` (`end1`,`end2`,`type`),
  KEY `end2` (`end2`),
  KEY `type` (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_computers_softwaresversions

DROP TABLE IF EXISTS `glpi_computers_softwaresversions`;
CREATE TABLE `glpi_computers_softwaresversions` (
  `ID` int(11) NOT NULL auto_increment,
  `cID` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_computers (ID)',
  `vID` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_softwareversions (ID)',
  PRIMARY KEY  (`ID`),
  KEY `cID` (`cID`),
  KEY `sID` (`vID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_computersdisks

DROP TABLE IF EXISTS `glpi_computersdisks`;
CREATE TABLE `glpi_computersdisks` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_computers` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_computers (ID)',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `device` varchar(255) collate utf8_unicode_ci default NULL,
  `mountpoint` varchar(255) collate utf8_unicode_ci default NULL,
  `FK_filesystems` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_filesystems (ID)',
  `totalsize` int(11) NOT NULL default '0',
  `freesize` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`),
  KEY `FK_filesystems` (`FK_filesystems`),
  KEY `FK_computers` (`FK_computers`),
  KEY `device` (`device`),
  KEY `mountpoint` (`mountpoint`),
  KEY `totalsize` (`totalsize`),
  KEY `freesize` (`freesize`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_computersmodels

DROP TABLE IF EXISTS `glpi_computersmodels`;
CREATE TABLE `glpi_computersmodels` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_computerstypes

DROP TABLE IF EXISTS `glpi_computerstypes`;
CREATE TABLE `glpi_computerstypes` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_computerstypes` VALUES ('1','Serveur',NULL);

### Dump table glpi_configs

DROP TABLE IF EXISTS `glpi_configs`;
CREATE TABLE `glpi_configs` (
  `ID` int(11) NOT NULL auto_increment,
  `num_of_events` int(11) NOT NULL default '10',
  `jobs_at_login` smallint(6) NOT NULL default '0',
  `sendexpire` varchar(255) collate utf8_unicode_ci default NULL,
  `cut` int(11) NOT NULL default '255',
  `expire_events` int(11) NOT NULL default '30',
  `list_limit` int(11) NOT NULL default '20',
  `list_limit_max` int(11) NOT NULL default '50',
  `version` varchar(255) collate utf8_unicode_ci default NULL,
  `logotxt` varchar(255) collate utf8_unicode_ci default NULL,
  `event_loglevel` smallint(6) NOT NULL default '5',
  `mailing` varchar(255) collate utf8_unicode_ci default NULL,
  `admin_email` varchar(255) collate utf8_unicode_ci default NULL,
  `admin_reply` varchar(255) collate utf8_unicode_ci default NULL,
  `mailing_signature` text collate utf8_unicode_ci,
  `permit_helpdesk` smallint(6) NOT NULL default '0',
  `language` varchar(255) collate utf8_unicode_ci default 'en_GB' COMMENT 'see define.php CFG_GLPI[language] array',
  `priority_1` varchar(255) collate utf8_unicode_ci default '#fff2f2',
  `priority_2` varchar(255) collate utf8_unicode_ci default '#ffe0e0',
  `priority_3` varchar(255) collate utf8_unicode_ci default '#ffcece',
  `priority_4` varchar(255) collate utf8_unicode_ci default '#ffbfbf',
  `priority_5` varchar(255) collate utf8_unicode_ci default '#ffadad',
  `date_fiscale` date NOT NULL default '2005-12-31',
  `cartridges_alarm` int(11) NOT NULL default '10',
  `cas_host` varchar(255) collate utf8_unicode_ci default NULL,
  `cas_port` varchar(255) collate utf8_unicode_ci default NULL,
  `cas_uri` varchar(255) collate utf8_unicode_ci default NULL,
  `cas_logout` varchar(255) collate utf8_unicode_ci default NULL,
  `extra_ldap_server` int(11) NOT NULL default '1' COMMENT 'RELATION to glpi_auth_ldap (ID)',
  `existing_auth_server_field` varchar(255) collate utf8_unicode_ci default NULL,
  `existing_auth_server_field_clean_domain` smallint(6) NOT NULL default '0',
  `planning_begin` time NOT NULL default '08:00:00',
  `planning_end` time NOT NULL default '20:00:00',
  `utf8_conv` int(11) NOT NULL default '0',
  `auto_assign` smallint(6) NOT NULL default '0',
  `public_faq` smallint(6) NOT NULL default '0',
  `url_base` varchar(255) collate utf8_unicode_ci default NULL,
  `url_in_mail` smallint(6) NOT NULL default '0',
  `text_login` text collate utf8_unicode_ci,
  `auto_update_check` smallint(6) NOT NULL default '0',
  `founded_new_version` varchar(255) collate utf8_unicode_ci default NULL,
  `dropdown_max` int(11) NOT NULL default '100',
  `ajax_wildcard` char(1) collate utf8_unicode_ci default '*',
  `use_ajax` smallint(6) NOT NULL default '0',
  `ajax_limit_count` int(11) NOT NULL default '50',
  `ajax_autocompletion` smallint(6) NOT NULL default '1',
  `auto_add_users` smallint(6) NOT NULL default '1',
  `dateformat` smallint(6) NOT NULL default '0',
  `numberformat` smallint(6) NOT NULL default '0',
  `nextprev_item` varchar(255) collate utf8_unicode_ci default 'name',
  `view_ID` smallint(6) NOT NULL default '0',
  `dropdown_limit` int(11) NOT NULL default '50',
  `ocs_mode` smallint(6) NOT NULL default '0',
  `smtp_mode` smallint(6) NOT NULL default '0' COMMENT 'see define.php MAIL_* constant',
  `smtp_host` varchar(255) collate utf8_unicode_ci default NULL,
  `smtp_port` int(11) NOT NULL default '25',
  `smtp_username` varchar(255) collate utf8_unicode_ci default NULL,
  `smtp_password` varchar(255) collate utf8_unicode_ci default NULL,
  `proxy_name` varchar(255) collate utf8_unicode_ci default NULL,
  `proxy_port` varchar(255) collate utf8_unicode_ci default '8080',
  `proxy_user` varchar(255) collate utf8_unicode_ci default NULL,
  `proxy_password` varchar(255) collate utf8_unicode_ci default NULL,
  `followup_on_update_ticket` smallint(6) NOT NULL default '1',
  `contract_alerts` smallint(6) NOT NULL default '0',
  `infocom_alerts` smallint(6) NOT NULL default '0',
  `licenses_alert` smallint(6) NOT NULL default '0',
  `cartridges_alert` int(11) NOT NULL default '0',
  `consumables_alert` int(11) NOT NULL default '0',
  `keep_tracking_on_delete` int(11) default '1',
  `show_admin_doc` int(11) default '0',
  `time_step` int(11) default '5',
  `decimal_number` int(11) default '2',
  `helpdeskhelp_url` varchar(255) collate utf8_unicode_ci default NULL,
  `centralhelp_url` varchar(255) collate utf8_unicode_ci default NULL,
  `default_rubdoc_tracking` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_rubdocs (ID)',
  `monitors_management_restrict` int(1) NOT NULL default '2',
  `phones_management_restrict` int(1) NOT NULL default '2',
  `peripherals_management_restrict` int(1) NOT NULL default '2',
  `printers_management_restrict` int(1) NOT NULL default '2',
  `licenses_management_restrict` int(1) NOT NULL default '2',
  `use_errorlog` int(1) NOT NULL default '0',
  `glpi_timezone` varchar(255) collate utf8_unicode_ci default NULL,
  `autoupdate_link_contact` smallint(6) NOT NULL default '1',
  `autoupdate_link_user` smallint(6) NOT NULL default '1',
  `autoupdate_link_group` smallint(6) NOT NULL default '1',
  `autoupdate_link_location` smallint(6) NOT NULL default '1',
  `autoupdate_link_state` smallint(6) NOT NULL default '0',
  `autoclean_link_contact` smallint(6) NOT NULL default '0',
  `autoclean_link_user` smallint(6) NOT NULL default '0',
  `autoclean_link_group` smallint(6) NOT NULL default '0',
  `autoclean_link_location` smallint(6) NOT NULL default '0',
  `autoclean_link_state` smallint(6) NOT NULL default '0',
  `flat_dropdowntree` smallint(6) NOT NULL default '0',
  `autoname_entity` smallint(6) NOT NULL default '1',
  `expand_soft_categorized` int(1) NOT NULL default '1',
  `expand_soft_not_categorized` int(1) NOT NULL default '1',
  `dbreplicate_notify_desynchronization` smallint(6) NOT NULL default '0',
  `dbreplicate_email` varchar(255) collate utf8_unicode_ci default NULL,
  `dbreplicate_maxdelay` int(11) NOT NULL default '3600',
  `category_on_software_delete` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_software_category (ID)',
  `x509_email_field` varchar(255) collate utf8_unicode_ci default NULL,
  `ticket_title_mandatory` int(1) NOT NULL default '0',
  `ticket_content_mandatory` int(1) NOT NULL default '1',
  `ticket_category_mandatory` int(1) NOT NULL default '0',
  `mailgate_filesize_max` int(11) NOT NULL default '2097152',
  `tracking_order` smallint(6) NOT NULL default '0',
  `followup_private` smallint(6) NOT NULL default '0',
  `software_helpdesk_visible` int(1) NOT NULL default '1',
  `name_display_order` tinyint(4) NOT NULL default '0',
  `request_type` int(1) NOT NULL default '1',
  `add_norights_users` int(1) NOT NULL default '1',
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_configs` VALUES ('1','10','0','1','250','30','15','50',' 0.80','GLPI powered by indepnet','5','0','admsys@xxxxx.fr',NULL,'SIGNATURE','0','fr_FR','#fff2f2','#ffe0e0','#ffcece','#ffbfbf','#ffadad','2005-12-31','10','','','',NULL,'1',NULL,'0','08:00:00','20:00:00','1','0','0','http://localhost/glpi/','0','','0','','100','*','0','50','1','1','0','0','name','0','50','0','0',NULL,'25',NULL,NULL,NULL,'8080',NULL,NULL,'1','0','0','0','0','0','0','0','5','2',NULL,NULL,'0','2','2','2','2','2','0','0','1','1','1','1','0','0','0','0','0','0','0','1','1','1','0',NULL,'3600','1',NULL,'0','1','0','2097152','0','0','1','0','1','1');

### Dump table glpi_consumables

DROP TABLE IF EXISTS `glpi_consumables`;
CREATE TABLE `glpi_consumables` (
  `ID` int(11) NOT NULL auto_increment,
  `consumablesitems_id` int(11) NOT NULL default '0',
  `date_in` date default NULL,
  `date_out` date default NULL,
  `users_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_cartridges_type` (`consumablesitems_id`),
  KEY `date_in` (`date_in`),
  KEY `date_out` (`date_out`),
  KEY `users_id` (`users_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_consumablesitems

DROP TABLE IF EXISTS `glpi_consumablesitems`;
CREATE TABLE `glpi_consumablesitems` (
  `ID` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `ref` varchar(255) collate utf8_unicode_ci default NULL,
  `locations_id` int(11) NOT NULL default '0',
  `type` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_consumable_type (ID)',
  `FK_glpi_enterprise` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_manufacturer (ID)',
  `users_id_tech` int(11) NOT NULL default '0',
  `deleted` smallint(6) NOT NULL default '0',
  `comments` text collate utf8_unicode_ci,
  `alarm` int(11) NOT NULL default '10',
  `notes` longtext collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `name` (`name`),
  KEY `type` (`type`),
  KEY `alarm` (`alarm`),
  KEY `deleted` (`deleted`),
  KEY `entities_id` (`entities_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `locations_id` (`locations_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_consumablesitemstypes

DROP TABLE IF EXISTS `glpi_consumablesitemstypes`;
CREATE TABLE `glpi_consumablesitemstypes` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_contacts

DROP TABLE IF EXISTS `glpi_contacts`;
CREATE TABLE `glpi_contacts` (
  `ID` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `recursive` tinyint(1) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `firstname` varchar(255) collate utf8_unicode_ci default NULL,
  `phone` varchar(255) collate utf8_unicode_ci default NULL,
  `phone2` varchar(255) collate utf8_unicode_ci default NULL,
  `mobile` varchar(255) collate utf8_unicode_ci default NULL,
  `fax` varchar(255) collate utf8_unicode_ci default NULL,
  `email` varchar(255) collate utf8_unicode_ci default NULL,
  `type` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_contact_type (ID)',
  `comments` text collate utf8_unicode_ci,
  `deleted` smallint(6) NOT NULL default '0',
  `notes` longtext collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `type` (`type`),
  KEY `name` (`name`),
  KEY `deleted` (`deleted`),
  KEY `entities_id` (`entities_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_contacts_suppliers

DROP TABLE IF EXISTS `glpi_contacts_suppliers`;
CREATE TABLE `glpi_contacts_suppliers` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_enterprise` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_enterprises (ID)',
  `FK_contact` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_contacts (ID)',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `FK_enterprise` (`FK_enterprise`,`FK_contact`),
  KEY `FK_contact` (`FK_contact`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_contactstypes

DROP TABLE IF EXISTS `glpi_contactstypes`;
CREATE TABLE `glpi_contactstypes` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_contracts

DROP TABLE IF EXISTS `glpi_contracts`;
CREATE TABLE `glpi_contracts` (
  `ID` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `recursive` tinyint(1) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `num` varchar(255) collate utf8_unicode_ci default NULL,
  `cost` decimal(20,4) NOT NULL default '0.0000',
  `contract_type` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_contract_type (ID)',
  `begin_date` date default NULL,
  `duration` smallint(6) NOT NULL default '0',
  `notice` smallint(6) NOT NULL default '0',
  `periodicity` smallint(6) NOT NULL default '0',
  `facturation` smallint(6) NOT NULL default '0',
  `bill_type` int(11) NOT NULL default '0',
  `comments` text collate utf8_unicode_ci,
  `compta_num` varchar(255) collate utf8_unicode_ci default NULL,
  `deleted` smallint(6) NOT NULL default '0',
  `week_begin_hour` time NOT NULL default '00:00:00',
  `week_end_hour` time NOT NULL default '00:00:00',
  `saturday_begin_hour` time NOT NULL default '00:00:00',
  `saturday_end_hour` time NOT NULL default '00:00:00',
  `saturday` smallint(6) NOT NULL default '0',
  `monday_begin_hour` time NOT NULL default '00:00:00',
  `monday_end_hour` time NOT NULL default '00:00:00',
  `monday` smallint(6) NOT NULL default '0',
  `device_countmax` int(11) NOT NULL default '0',
  `notes` longtext collate utf8_unicode_ci,
  `alert` smallint(6) NOT NULL default '0',
  `renewal` smallint(6) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY `contract_type` (`contract_type`),
  KEY `begin_date` (`begin_date`),
  KEY `bill_type` (`bill_type`),
  KEY `name` (`name`),
  KEY `deleted` (`deleted`),
  KEY `entities_id` (`entities_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_contracts_items

DROP TABLE IF EXISTS `glpi_contracts_items`;
CREATE TABLE `glpi_contracts_items` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_contract` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_contracts (ID)',
  `items_id` int(11) NOT NULL default '0',
  `itemtype` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `FK_contract_device` (`FK_contract`,`itemtype`,`items_id`),
  KEY `FK_device` (`items_id`,`itemtype`),
  KEY `device_type` (`itemtype`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_contracts_suppliers

DROP TABLE IF EXISTS `glpi_contracts_suppliers`;
CREATE TABLE `glpi_contracts_suppliers` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_enterprise` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_enterprises (ID)',
  `FK_contract` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_contracts (ID)',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `FK_enterprise` (`FK_enterprise`,`FK_contract`),
  KEY `FK_contract` (`FK_contract`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_contractstypes

DROP TABLE IF EXISTS `glpi_contractstypes`;
CREATE TABLE `glpi_contractstypes` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_devicescases

DROP TABLE IF EXISTS `glpi_devicescases`;
CREATE TABLE `glpi_devicescases` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `type` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_case_type (ID)',
  `comment` text collate utf8_unicode_ci,
  `FK_glpi_enterprise` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_manufacturer (ID)',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `designation` (`designation`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_devicescasestypes

DROP TABLE IF EXISTS `glpi_devicescasestypes`;
CREATE TABLE `glpi_devicescasestypes` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_devicescontrols

DROP TABLE IF EXISTS `glpi_devicescontrols`;
CREATE TABLE `glpi_devicescontrols` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `raid` smallint(6) NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  `FK_glpi_enterprise` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_manufacturer (ID)',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  `FK_interface` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `designation` (`designation`),
  KEY `FK_interface` (`FK_interface`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_devicesdrives

DROP TABLE IF EXISTS `glpi_devicesdrives`;
CREATE TABLE `glpi_devicesdrives` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `is_writer` smallint(6) NOT NULL default '1',
  `speed` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  `FK_glpi_enterprise` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_manufacturer (ID)',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  `FK_interface` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `designation` (`designation`),
  KEY `FK_interface` (`FK_interface`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_devicesgraphiccards

DROP TABLE IF EXISTS `glpi_devicesgraphiccards`;
CREATE TABLE `glpi_devicesgraphiccards` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `FK_interface` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_interface (ID)',
  `comment` text collate utf8_unicode_ci,
  `FK_glpi_enterprise` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_manufacturer (ID)',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `designation` (`designation`),
  KEY `FK_interface` (`FK_interface`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_devicesharddrives

DROP TABLE IF EXISTS `glpi_devicesharddrives`;
CREATE TABLE `glpi_devicesharddrives` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `rpm` varchar(255) collate utf8_unicode_ci default NULL,
  `FK_interface` int(11) NOT NULL default '0',
  `cache` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  `FK_glpi_enterprise` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_manufacturer (ID)',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `designation` (`designation`),
  KEY `FK_interface` (`FK_interface`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_devicesmemories

DROP TABLE IF EXISTS `glpi_devicesmemories`;
CREATE TABLE `glpi_devicesmemories` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `frequence` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  `FK_glpi_enterprise` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_manufacturer (ID)',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  `type` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_ram_type (ID)',
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `designation` (`designation`),
  KEY `type` (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_devicesmemoriestypes

DROP TABLE IF EXISTS `glpi_devicesmemoriestypes`;
CREATE TABLE `glpi_devicesmemoriestypes` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_devicesmemoriestypes` VALUES ('1','EDO',NULL);
INSERT INTO `glpi_devicesmemoriestypes` VALUES ('2','DDR',NULL);
INSERT INTO `glpi_devicesmemoriestypes` VALUES ('3','SDRAM',NULL);
INSERT INTO `glpi_devicesmemoriestypes` VALUES ('4','SDRAM-2',NULL);

### Dump table glpi_devicesmotherboards

DROP TABLE IF EXISTS `glpi_devicesmotherboards`;
CREATE TABLE `glpi_devicesmotherboards` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `chipset` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  `FK_glpi_enterprise` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_manufacturer (ID)',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `designation` (`designation`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_devicesnetworkcards

DROP TABLE IF EXISTS `glpi_devicesnetworkcards`;
CREATE TABLE `glpi_devicesnetworkcards` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `bandwidth` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  `FK_glpi_enterprise` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_manufacturer (ID)',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `designation` (`designation`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_devicespcis

DROP TABLE IF EXISTS `glpi_devicespcis`;
CREATE TABLE `glpi_devicespcis` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  `FK_glpi_enterprise` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_manufacturer (ID)',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `designation` (`designation`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_devicespowersupplies

DROP TABLE IF EXISTS `glpi_devicespowersupplies`;
CREATE TABLE `glpi_devicespowersupplies` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `power` varchar(255) collate utf8_unicode_ci default NULL,
  `atx` smallint(6) NOT NULL default '1',
  `comment` text collate utf8_unicode_ci,
  `FK_glpi_enterprise` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_manufacturer (ID)',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `designation` (`designation`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_devicesprocessors

DROP TABLE IF EXISTS `glpi_devicesprocessors`;
CREATE TABLE `glpi_devicesprocessors` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `frequence` int(11) NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  `FK_glpi_enterprise` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_manufacturer (ID)',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `designation` (`designation`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_devicessoundcards

DROP TABLE IF EXISTS `glpi_devicessoundcards`;
CREATE TABLE `glpi_devicessoundcards` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `type` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  `FK_glpi_enterprise` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_manufacturer (ID)',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `designation` (`designation`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_displayprefs

DROP TABLE IF EXISTS `glpi_displayprefs`;
CREATE TABLE `glpi_displayprefs` (
  `ID` int(11) NOT NULL auto_increment,
  `type` smallint(6) NOT NULL default '0',
  `num` smallint(6) NOT NULL default '0',
  `rank` smallint(6) NOT NULL default '0',
  `users_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `display` (`type`,`num`,`users_id`),
  KEY `rank` (`rank`),
  KEY `num` (`num`),
  KEY `FK_users` (`users_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_displayprefs` VALUES ('32','1','4','4','0');
INSERT INTO `glpi_displayprefs` VALUES ('34','1','6','6','0');
INSERT INTO `glpi_displayprefs` VALUES ('33','1','5','5','0');
INSERT INTO `glpi_displayprefs` VALUES ('31','1','8','3','0');
INSERT INTO `glpi_displayprefs` VALUES ('30','1','23','2','0');
INSERT INTO `glpi_displayprefs` VALUES ('86','12','3','1','0');
INSERT INTO `glpi_displayprefs` VALUES ('49','4','31','1','0');
INSERT INTO `glpi_displayprefs` VALUES ('50','4','23','2','0');
INSERT INTO `glpi_displayprefs` VALUES ('51','4','3','3','0');
INSERT INTO `glpi_displayprefs` VALUES ('52','4','4','4','0');
INSERT INTO `glpi_displayprefs` VALUES ('44','3','31','1','0');
INSERT INTO `glpi_displayprefs` VALUES ('38','2','31','1','0');
INSERT INTO `glpi_displayprefs` VALUES ('39','2','23','2','0');
INSERT INTO `glpi_displayprefs` VALUES ('45','3','23','2','0');
INSERT INTO `glpi_displayprefs` VALUES ('46','3','3','3','0');
INSERT INTO `glpi_displayprefs` VALUES ('63','6','4','3','0');
INSERT INTO `glpi_displayprefs` VALUES ('62','6','5','2','0');
INSERT INTO `glpi_displayprefs` VALUES ('61','6','23','1','0');
INSERT INTO `glpi_displayprefs` VALUES ('83','11','4','2','0');
INSERT INTO `glpi_displayprefs` VALUES ('82','11','3','1','0');
INSERT INTO `glpi_displayprefs` VALUES ('57','5','3','3','0');
INSERT INTO `glpi_displayprefs` VALUES ('56','5','23','2','0');
INSERT INTO `glpi_displayprefs` VALUES ('55','5','31','1','0');
INSERT INTO `glpi_displayprefs` VALUES ('29','1','31','1','0');
INSERT INTO `glpi_displayprefs` VALUES ('35','1','3','7','0');
INSERT INTO `glpi_displayprefs` VALUES ('36','1','19','8','0');
INSERT INTO `glpi_displayprefs` VALUES ('37','1','17','9','0');
INSERT INTO `glpi_displayprefs` VALUES ('40','2','3','3','0');
INSERT INTO `glpi_displayprefs` VALUES ('41','2','4','4','0');
INSERT INTO `glpi_displayprefs` VALUES ('42','2','11','6','0');
INSERT INTO `glpi_displayprefs` VALUES ('43','2','9','7','0');
INSERT INTO `glpi_displayprefs` VALUES ('47','3','4','4','0');
INSERT INTO `glpi_displayprefs` VALUES ('48','3','9','6','0');
INSERT INTO `glpi_displayprefs` VALUES ('53','4','9','6','0');
INSERT INTO `glpi_displayprefs` VALUES ('54','4','7','7','0');
INSERT INTO `glpi_displayprefs` VALUES ('58','5','4','4','0');
INSERT INTO `glpi_displayprefs` VALUES ('59','5','9','6','0');
INSERT INTO `glpi_displayprefs` VALUES ('60','5','7','7','0');
INSERT INTO `glpi_displayprefs` VALUES ('64','7','3','1','0');
INSERT INTO `glpi_displayprefs` VALUES ('65','7','4','2','0');
INSERT INTO `glpi_displayprefs` VALUES ('66','7','5','3','0');
INSERT INTO `glpi_displayprefs` VALUES ('67','7','6','4','0');
INSERT INTO `glpi_displayprefs` VALUES ('68','7','9','5','0');
INSERT INTO `glpi_displayprefs` VALUES ('69','8','9','1','0');
INSERT INTO `glpi_displayprefs` VALUES ('70','8','3','2','0');
INSERT INTO `glpi_displayprefs` VALUES ('71','8','4','3','0');
INSERT INTO `glpi_displayprefs` VALUES ('72','8','5','4','0');
INSERT INTO `glpi_displayprefs` VALUES ('73','8','10','5','0');
INSERT INTO `glpi_displayprefs` VALUES ('74','8','6','6','0');
INSERT INTO `glpi_displayprefs` VALUES ('75','10','4','1','0');
INSERT INTO `glpi_displayprefs` VALUES ('76','10','3','2','0');
INSERT INTO `glpi_displayprefs` VALUES ('77','10','5','3','0');
INSERT INTO `glpi_displayprefs` VALUES ('78','10','6','4','0');
INSERT INTO `glpi_displayprefs` VALUES ('79','10','7','5','0');
INSERT INTO `glpi_displayprefs` VALUES ('80','10','11','6','0');
INSERT INTO `glpi_displayprefs` VALUES ('84','11','5','3','0');
INSERT INTO `glpi_displayprefs` VALUES ('85','11','6','4','0');
INSERT INTO `glpi_displayprefs` VALUES ('88','12','6','2','0');
INSERT INTO `glpi_displayprefs` VALUES ('89','12','4','3','0');
INSERT INTO `glpi_displayprefs` VALUES ('90','12','5','4','0');
INSERT INTO `glpi_displayprefs` VALUES ('91','13','3','1','0');
INSERT INTO `glpi_displayprefs` VALUES ('92','13','4','2','0');
INSERT INTO `glpi_displayprefs` VALUES ('93','13','7','3','0');
INSERT INTO `glpi_displayprefs` VALUES ('94','13','5','4','0');
INSERT INTO `glpi_displayprefs` VALUES ('95','13','6','5','0');
INSERT INTO `glpi_displayprefs` VALUES ('96','15','3','1','0');
INSERT INTO `glpi_displayprefs` VALUES ('98','15','5','3','0');
INSERT INTO `glpi_displayprefs` VALUES ('99','15','6','4','0');
INSERT INTO `glpi_displayprefs` VALUES ('100','15','7','5','0');
INSERT INTO `glpi_displayprefs` VALUES ('101','17','3','1','0');
INSERT INTO `glpi_displayprefs` VALUES ('102','17','4','2','0');
INSERT INTO `glpi_displayprefs` VALUES ('103','17','5','3','0');
INSERT INTO `glpi_displayprefs` VALUES ('104','17','6','4','0');
INSERT INTO `glpi_displayprefs` VALUES ('105','2','40','5','0');
INSERT INTO `glpi_displayprefs` VALUES ('106','3','40','5','0');
INSERT INTO `glpi_displayprefs` VALUES ('107','4','40','5','0');
INSERT INTO `glpi_displayprefs` VALUES ('108','5','40','5','0');
INSERT INTO `glpi_displayprefs` VALUES ('109','15','8','6','0');
INSERT INTO `glpi_displayprefs` VALUES ('110','23','31','1','0');
INSERT INTO `glpi_displayprefs` VALUES ('111','23','23','2','0');
INSERT INTO `glpi_displayprefs` VALUES ('112','23','3','3','0');
INSERT INTO `glpi_displayprefs` VALUES ('113','23','4','4','0');
INSERT INTO `glpi_displayprefs` VALUES ('114','23','40','5','0');
INSERT INTO `glpi_displayprefs` VALUES ('115','23','9','6','0');
INSERT INTO `glpi_displayprefs` VALUES ('116','23','7','7','0');
INSERT INTO `glpi_displayprefs` VALUES ('117','27','16','1','0');
INSERT INTO `glpi_displayprefs` VALUES ('118','22','31','1','0');
INSERT INTO `glpi_displayprefs` VALUES ('119','29','4','1','0');
INSERT INTO `glpi_displayprefs` VALUES ('120','29','3','2','0');
INSERT INTO `glpi_displayprefs` VALUES ('121','35','80','1','0');
INSERT INTO `glpi_displayprefs` VALUES ('122','6','72','4','0');
INSERT INTO `glpi_displayprefs` VALUES ('123','6','163','5','0');

### Dump table glpi_documents

DROP TABLE IF EXISTS `glpi_documents`;
CREATE TABLE `glpi_documents` (
  `ID` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `recursive` tinyint(1) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `filename` varchar(255) collate utf8_unicode_ci default NULL,
  `rubrique` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_rubdocs (ID)',
  `mime` varchar(255) collate utf8_unicode_ci default NULL,
  `date_mod` datetime default NULL,
  `comments` text collate utf8_unicode_ci,
  `deleted` smallint(6) NOT NULL default '0',
  `link` varchar(255) collate utf8_unicode_ci default NULL,
  `notes` longtext collate utf8_unicode_ci,
  `users_id` int(11) NOT NULL default '0',
  `FK_tracking` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_tracking (ID)',
  PRIMARY KEY  (`ID`),
  KEY `rubrique` (`rubrique`),
  KEY `date_mod` (`date_mod`),
  KEY `name` (`name`),
  KEY `FK_tracking` (`FK_tracking`),
  KEY `deleted` (`deleted`),
  KEY `entities_id` (`entities_id`),
  KEY `users_id` (`users_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_documents_items

DROP TABLE IF EXISTS `glpi_documents_items`;
CREATE TABLE `glpi_documents_items` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_doc` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_docs (ID)',
  `items_id` int(11) NOT NULL default '0',
  `itemtype` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `FK_doc_device` (`FK_doc`,`itemtype`,`items_id`),
  KEY `FK_device` (`items_id`,`itemtype`),
  KEY `device_type` (`itemtype`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_documentscategories

DROP TABLE IF EXISTS `glpi_documentscategories`;
CREATE TABLE `glpi_documentscategories` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_documentstypes

DROP TABLE IF EXISTS `glpi_documentstypes`;
CREATE TABLE `glpi_documentstypes` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `ext` varchar(255) collate utf8_unicode_ci default NULL,
  `icon` varchar(255) collate utf8_unicode_ci default NULL,
  `mime` varchar(255) collate utf8_unicode_ci default NULL,
  `upload` smallint(6) NOT NULL default '1',
  `date_mod` datetime default NULL,
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `extension` (`ext`),
  KEY `name` (`name`),
  KEY `upload` (`upload`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_documentstypes` VALUES ('1','JPEG','jpg','jpg-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO `glpi_documentstypes` VALUES ('2','PNG','png','png-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO `glpi_documentstypes` VALUES ('3','GIF','gif','gif-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO `glpi_documentstypes` VALUES ('4','BMP','bmp','bmp-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO `glpi_documentstypes` VALUES ('5','Photoshop','psd','psd-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO `glpi_documentstypes` VALUES ('6','TIFF','tif','tif-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO `glpi_documentstypes` VALUES ('7','AIFF','aiff','aiff-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO `glpi_documentstypes` VALUES ('8','Windows Media','asf','asf-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO `glpi_documentstypes` VALUES ('9','Windows Media','avi','avi-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO `glpi_documentstypes` VALUES ('44','C source','c','','','1','2004-12-13 19:47:22');
INSERT INTO `glpi_documentstypes` VALUES ('27','RealAudio','rm','rm-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO `glpi_documentstypes` VALUES ('16','Midi','mid','mid-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO `glpi_documentstypes` VALUES ('17','QuickTime','mov','mov-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO `glpi_documentstypes` VALUES ('18','MP3','mp3','mp3-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO `glpi_documentstypes` VALUES ('19','MPEG','mpg','mpg-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO `glpi_documentstypes` VALUES ('20','Ogg Vorbis','ogg','ogg-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO `glpi_documentstypes` VALUES ('24','QuickTime','qt','qt-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO `glpi_documentstypes` VALUES ('10','BZip','bz2','bz2-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO `glpi_documentstypes` VALUES ('25','RealAudio','ra','ra-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO `glpi_documentstypes` VALUES ('26','RealAudio','ram','ram-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO `glpi_documentstypes` VALUES ('11','Word','doc','doc-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO `glpi_documentstypes` VALUES ('12','DjVu','djvu','','','1','2004-12-13 19:47:21');
INSERT INTO `glpi_documentstypes` VALUES ('42','MNG','mng','','','1','2004-12-13 19:47:22');
INSERT INTO `glpi_documentstypes` VALUES ('13','PostScript','eps','ps-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO `glpi_documentstypes` VALUES ('14','GZ','gz','gz-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO `glpi_documentstypes` VALUES ('37','WAV','wav','wav-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO `glpi_documentstypes` VALUES ('15','HTML','html','html-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO `glpi_documentstypes` VALUES ('34','Flash','swf','','','1','2004-12-13 19:47:22');
INSERT INTO `glpi_documentstypes` VALUES ('21','PDF','pdf','pdf-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO `glpi_documentstypes` VALUES ('22','PowerPoint','ppt','ppt-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO `glpi_documentstypes` VALUES ('23','PostScript','ps','ps-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO `glpi_documentstypes` VALUES ('40','Windows Media','wmv','','','1','2004-12-13 19:47:22');
INSERT INTO `glpi_documentstypes` VALUES ('28','RTF','rtf','rtf-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO `glpi_documentstypes` VALUES ('29','StarOffice','sdd','sdd-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO `glpi_documentstypes` VALUES ('30','StarOffice','sdw','sdw-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO `glpi_documentstypes` VALUES ('31','Stuffit','sit','sit-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO `glpi_documentstypes` VALUES ('43','Adobe Illustrator','ai','ai-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO `glpi_documentstypes` VALUES ('32','OpenOffice Impress','sxi','sxi-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO `glpi_documentstypes` VALUES ('33','OpenOffice','sxw','sxw-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO `glpi_documentstypes` VALUES ('46','DVI','dvi','dvi-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO `glpi_documentstypes` VALUES ('35','TGZ','tgz','tgz-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO `glpi_documentstypes` VALUES ('36','texte','txt','txt-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO `glpi_documentstypes` VALUES ('49','RedHat/Mandrake/SuSE','rpm','rpm-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO `glpi_documentstypes` VALUES ('38','Excel','xls','xls-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO `glpi_documentstypes` VALUES ('39','XML','xml','xml-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO `glpi_documentstypes` VALUES ('41','Zip','zip','zip-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO `glpi_documentstypes` VALUES ('45','Debian','deb','deb-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO `glpi_documentstypes` VALUES ('47','C header','h','','','1','2004-12-13 19:47:22');
INSERT INTO `glpi_documentstypes` VALUES ('48','Pascal','pas','','','1','2004-12-13 19:47:22');
INSERT INTO `glpi_documentstypes` VALUES ('50','OpenOffice Calc','sxc','sxc-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO `glpi_documentstypes` VALUES ('51','LaTeX','tex','tex-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO `glpi_documentstypes` VALUES ('52','GIMP multi-layer','xcf','xcf-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO `glpi_documentstypes` VALUES ('53','JPEG','jpeg','jpg-dist.png','','1','2005-03-07 22:23:17');
INSERT INTO `glpi_documentstypes` VALUES ('54','Oasis Open Office Writer','odt','odt-dist.png','','1','2006-01-21 17:41:13');
INSERT INTO `glpi_documentstypes` VALUES ('55','Oasis Open Office Calc','ods','ods-dist.png','','1','2006-01-21 17:41:31');
INSERT INTO `glpi_documentstypes` VALUES ('56','Oasis Open Office Impress','odp','odp-dist.png','','1','2006-01-21 17:42:54');
INSERT INTO `glpi_documentstypes` VALUES ('57','Oasis Open Office Impress Template','otp','odp-dist.png','','1','2006-01-21 17:43:58');
INSERT INTO `glpi_documentstypes` VALUES ('58','Oasis Open Office Writer Template','ott','odt-dist.png','','1','2006-01-21 17:44:41');
INSERT INTO `glpi_documentstypes` VALUES ('59','Oasis Open Office Calc Template','ots','ods-dist.png','','1','2006-01-21 17:45:30');
INSERT INTO `glpi_documentstypes` VALUES ('60','Oasis Open Office Math','odf','odf-dist.png','','1','2006-01-21 17:48:05');
INSERT INTO `glpi_documentstypes` VALUES ('61','Oasis Open Office Draw','odg','odg-dist.png','','1','2006-01-21 17:48:31');
INSERT INTO `glpi_documentstypes` VALUES ('62','Oasis Open Office Draw Template','otg','odg-dist.png','','1','2006-01-21 17:49:46');
INSERT INTO `glpi_documentstypes` VALUES ('63','Oasis Open Office Base','odb','odb-dist.png','','1','2006-01-21 18:03:34');
INSERT INTO `glpi_documentstypes` VALUES ('64','Oasis Open Office HTML','oth','oth-dist.png','','1','2006-01-21 18:05:27');
INSERT INTO `glpi_documentstypes` VALUES ('65','Oasis Open Office Writer Master','odm','odm-dist.png','','1','2006-01-21 18:06:34');
INSERT INTO `glpi_documentstypes` VALUES ('66','Oasis Open Office Chart','odc','','','1','2006-01-21 18:07:48');
INSERT INTO `glpi_documentstypes` VALUES ('67','Oasis Open Office Image','odi','','','1','2006-01-21 18:08:18');

### Dump table glpi_domains

DROP TABLE IF EXISTS `glpi_domains`;
CREATE TABLE `glpi_domains` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_entities

DROP TABLE IF EXISTS `glpi_entities`;
CREATE TABLE `glpi_entities` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `entities_id` int(11) NOT NULL default '0',
  `completename` text collate utf8_unicode_ci,
  `comments` text collate utf8_unicode_ci,
  `level` int(11) NOT NULL default '0',
  `cache_sons` longtext collate utf8_unicode_ci NOT NULL,
  `cache_ancestors` longtext collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `name` (`name`,`entities_id`),
  KEY `entities_id` (`entities_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_entitiesdatas

DROP TABLE IF EXISTS `glpi_entitiesdatas`;
CREATE TABLE `glpi_entitiesdatas` (
  `ID` int(11) NOT NULL auto_increment,
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
  `notes` longtext collate utf8_unicode_ci,
  `ldap_dn` varchar(255) collate utf8_unicode_ci default NULL,
  `tag` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `FK_entities` (`entities_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_events

DROP TABLE IF EXISTS `glpi_events`;
CREATE TABLE `glpi_events` (
  `ID` int(11) NOT NULL auto_increment,
  `item` int(11) NOT NULL default '0',
  `itemtype` varchar(255) collate utf8_unicode_ci default NULL,
  `date` datetime default NULL,
  `service` varchar(255) collate utf8_unicode_ci default NULL,
  `level` smallint(6) NOT NULL default '0',
  `message` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `comp` (`item`),
  KEY `date` (`date`),
  KEY `itemtype` (`itemtype`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_events` VALUES ('4','-1','system','2009-03-04 18:25:58','login','3','glpi connexion de l\'IP : 127.0.0.1');
INSERT INTO `glpi_events` VALUES ('5','-1','system','2009-07-23 17:50:02','login','3','glpi connexion de l\'IP : 127.0.0.1');
INSERT INTO `glpi_events` VALUES ('6','-1','system','2009-07-28 17:28:35','login','3','glpi connexion de l\'IP : 127.0.0.1');

### Dump table glpi_filesystems

DROP TABLE IF EXISTS `glpi_filesystems`;
CREATE TABLE `glpi_filesystems` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
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
  `ID` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `recursive` tinyint(1) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  `users_id` int(11) NOT NULL default '0',
  `ldap_field` varchar(255) collate utf8_unicode_ci default NULL,
  `ldap_value` varchar(255) collate utf8_unicode_ci default NULL,
  `ldap_group_dn` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`),
  KEY `ldap_field` (`ldap_field`),
  KEY `ldap_group_dn` (`ldap_group_dn`),
  KEY `ldap_value` (`ldap_value`),
  KEY `entities_id` (`entities_id`),
  KEY `users_id` (`users_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_groups_users

DROP TABLE IF EXISTS `glpi_groups_users`;
CREATE TABLE `glpi_groups_users` (
  `ID` int(11) NOT NULL auto_increment,
  `users_id` int(11) NOT NULL default '0',
  `FK_groups` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_groups (ID)',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `usergroup` (`users_id`,`FK_groups`),
  KEY `FK_groups` (`FK_groups`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_infocoms

DROP TABLE IF EXISTS `glpi_infocoms`;
CREATE TABLE `glpi_infocoms` (
  `ID` int(11) NOT NULL auto_increment,
  `items_id` int(11) NOT NULL default '0',
  `itemtype` int(11) NOT NULL default '0',
  `buy_date` date default NULL,
  `use_date` date default NULL,
  `warranty_duration` smallint(6) NOT NULL default '0',
  `warranty_info` varchar(255) collate utf8_unicode_ci default NULL,
  `FK_enterprise` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_enterprises (ID)',
  `num_commande` varchar(255) collate utf8_unicode_ci default NULL,
  `bon_livraison` varchar(255) collate utf8_unicode_ci default NULL,
  `num_immo` varchar(255) collate utf8_unicode_ci default NULL,
  `value` decimal(20,4) NOT NULL default '0.0000',
  `warranty_value` decimal(20,4) NOT NULL default '0.0000',
  `amort_time` smallint(6) NOT NULL default '0',
  `amort_type` smallint(6) NOT NULL default '0',
  `amort_coeff` float NOT NULL default '0',
  `comments` text collate utf8_unicode_ci,
  `facture` varchar(255) collate utf8_unicode_ci default NULL,
  `budget` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_budget (ID)',
  `alert` smallint(6) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `FK_device` (`items_id`,`itemtype`),
  KEY `FK_enterprise` (`FK_enterprise`),
  KEY `buy_date` (`buy_date`),
  KEY `budget` (`budget`),
  KEY `alert` (`alert`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_interfaces

DROP TABLE IF EXISTS `glpi_interfaces`;
CREATE TABLE `glpi_interfaces` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_interfaces` VALUES ('1','IDE',NULL);
INSERT INTO `glpi_interfaces` VALUES ('2','SATA',NULL);
INSERT INTO `glpi_interfaces` VALUES ('3','SCSI',NULL);
INSERT INTO `glpi_interfaces` VALUES ('4','USB',NULL);
INSERT INTO `glpi_interfaces` VALUES ('5','AGP','');
INSERT INTO `glpi_interfaces` VALUES ('6','PCI','');
INSERT INTO `glpi_interfaces` VALUES ('7','PCIe','');
INSERT INTO `glpi_interfaces` VALUES ('8','PCI-X','');

### Dump table glpi_knowbaseitems

DROP TABLE IF EXISTS `glpi_knowbaseitems`;
CREATE TABLE `glpi_knowbaseitems` (
  `ID` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `recursive` tinyint(1) NOT NULL default '1',
  `categoryID` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_kbcategories (ID)',
  `question` text collate utf8_unicode_ci,
  `answer` longtext collate utf8_unicode_ci,
  `faq` smallint(6) NOT NULL default '0',
  `users_id` int(11) NOT NULL default '0',
  `view` int(11) NOT NULL default '0',
  `date` datetime default NULL,
  `date_mod` datetime default NULL,
  PRIMARY KEY  (`ID`),
  KEY `categoryID` (`categoryID`),
  KEY `faq` (`faq`),
  KEY `users_id` (`users_id`),
  KEY `entities_id` (`entities_id`),
  FULLTEXT KEY `fulltext` (`question`,`answer`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_knowbaseitemscategories

DROP TABLE IF EXISTS `glpi_knowbaseitemscategories`;
CREATE TABLE `glpi_knowbaseitemscategories` (
  `ID` int(11) NOT NULL auto_increment,
  `knowbaseitemscategories_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `completename` text collate utf8_unicode_ci,
  `comments` text collate utf8_unicode_ci,
  `level` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `parentID_2` (`knowbaseitemscategories_id`,`name`),
  KEY `parentID` (`knowbaseitemscategories_id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_links

DROP TABLE IF EXISTS `glpi_links`;
CREATE TABLE `glpi_links` (
  `ID` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `recursive` int(1) NOT NULL default '1',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `link` varchar(255) collate utf8_unicode_ci default NULL,
  `data` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `entities_id` (`entities_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_links_itemtypes

DROP TABLE IF EXISTS `glpi_links_itemtypes`;
CREATE TABLE `glpi_links_itemtypes` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_links` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_links (ID)',
  `itemtype` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `link` (`itemtype`,`FK_links`),
  KEY `FK_links` (`FK_links`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_locations

DROP TABLE IF EXISTS `glpi_locations`;
CREATE TABLE `glpi_locations` (
  `ID` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `locations_id` int(11) NOT NULL default '0',
  `completename` text collate utf8_unicode_ci,
  `comments` text collate utf8_unicode_ci,
  `level` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `name` (`name`,`locations_id`,`entities_id`),
  KEY `FK_entities` (`entities_id`),
  KEY `locations_id` (`locations_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_logs

DROP TABLE IF EXISTS `glpi_logs`;
CREATE TABLE `glpi_logs` (
  `ID` int(11) NOT NULL auto_increment,
  `items_id` int(11) NOT NULL default '0',
  `itemtype` int(11) NOT NULL default '0',
  `devicetype` int(11) NOT NULL default '0',
  `linked_action` smallint(6) NOT NULL default '0' COMMENT 'see define.php HISTORY_* constant',
  `user_name` varchar(255) collate utf8_unicode_ci default NULL,
  `date_mod` datetime default NULL,
  `id_search_option` int(11) NOT NULL default '0' COMMENT 'see search.constant.php for value',
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_device` (`items_id`),
  KEY `device_type` (`itemtype`),
  KEY `date_mod` (`date_mod`),
  KEY `devicetype` (`devicetype`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_mailcollectors

DROP TABLE IF EXISTS `glpi_mailcollectors`;
CREATE TABLE `glpi_mailcollectors` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `entities_id` int(11) NOT NULL default '0',
  `host` varchar(255) collate utf8_unicode_ci default NULL,
  `login` varchar(255) collate utf8_unicode_ci default NULL,
  `password` varchar(255) collate utf8_unicode_ci default NULL,
  `active` int(1) NOT NULL default '1',
  PRIMARY KEY  (`ID`),
  KEY `entities_id` (`entities_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_mailingsettings

DROP TABLE IF EXISTS `glpi_mailingsettings`;
CREATE TABLE `glpi_mailingsettings` (
  `ID` int(11) NOT NULL auto_increment,
  `type` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'VALUE in (new, followup, finish, update, resa, alertconsumable, alertcartdridge, alertinfocom, alertlicense)',
  `FK_item` int(11) NOT NULL default '0' COMMENT 'if item_type=USER_MAILING_TYPE see define.php *_MAILING constant, else RELATION to various table',
  `item_type` int(11) NOT NULL default '0' COMMENT 'see define.php *_MAILING_TYPE constant',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `mailings` (`type`,`FK_item`,`item_type`),
  KEY `FK_item` (`FK_item`),
  KEY `items` (`item_type`,`FK_item`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_mailingsettings` VALUES ('1','resa','3','1');
INSERT INTO `glpi_mailingsettings` VALUES ('2','resa','1','1');
INSERT INTO `glpi_mailingsettings` VALUES ('3','new','3','2');
INSERT INTO `glpi_mailingsettings` VALUES ('4','new','1','1');
INSERT INTO `glpi_mailingsettings` VALUES ('5','update','1','1');
INSERT INTO `glpi_mailingsettings` VALUES ('6','followup','1','1');
INSERT INTO `glpi_mailingsettings` VALUES ('7','finish','1','1');
INSERT INTO `glpi_mailingsettings` VALUES ('8','update','2','1');
INSERT INTO `glpi_mailingsettings` VALUES ('9','update','4','1');
INSERT INTO `glpi_mailingsettings` VALUES ('10','new','3','1');
INSERT INTO `glpi_mailingsettings` VALUES ('11','update','3','1');
INSERT INTO `glpi_mailingsettings` VALUES ('12','followup','3','1');
INSERT INTO `glpi_mailingsettings` VALUES ('13','finish','3','1');

### Dump table glpi_manufacturers

DROP TABLE IF EXISTS `glpi_manufacturers`;
CREATE TABLE `glpi_manufacturers` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_monitors

DROP TABLE IF EXISTS `glpi_monitors`;
CREATE TABLE `glpi_monitors` (
  `ID` int(10) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `date_mod` datetime default NULL,
  `contact` varchar(255) collate utf8_unicode_ci default NULL,
  `contact_num` varchar(255) collate utf8_unicode_ci default NULL,
  `users_id_tech` int(11) NOT NULL default '0',
  `comments` text collate utf8_unicode_ci,
  `serial` varchar(255) collate utf8_unicode_ci default NULL,
  `otherserial` varchar(255) collate utf8_unicode_ci default NULL,
  `size` int(3) NOT NULL default '0',
  `flags_micro` smallint(6) NOT NULL default '0',
  `flags_speaker` smallint(6) NOT NULL default '0',
  `flags_subd` smallint(6) NOT NULL default '0',
  `flags_bnc` smallint(6) NOT NULL default '0',
  `flags_dvi` smallint(6) NOT NULL default '0',
  `flags_pivot` smallint(6) NOT NULL default '0',
  `locations_id` int(11) NOT NULL default '0',
  `type` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_type_docs (ID)',
  `model` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_model_monitors (ID)',
  `FK_glpi_enterprise` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_manufacturer (ID)',
  `is_global` smallint(6) NOT NULL default '0',
  `deleted` smallint(6) NOT NULL default '0',
  `is_template` smallint(6) NOT NULL default '0',
  `tplname` varchar(255) collate utf8_unicode_ci default NULL,
  `notes` longtext collate utf8_unicode_ci,
  `users_id` int(11) NOT NULL default '0',
  `FK_groups` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_groups (ID)',
  `state` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_state (ID)',
  `ticket_tco` decimal(20,4) default '0.0000',
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `name` (`name`),
  KEY `type` (`type`),
  KEY `model` (`model`),
  KEY `FK_groups` (`FK_groups`),
  KEY `is_template` (`is_template`),
  KEY `is_global` (`is_global`),
  KEY `deleted` (`deleted`),
  KEY `entities_id` (`entities_id`),
  KEY `users_id` (`users_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `locations_id` (`locations_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_monitorsmodels

DROP TABLE IF EXISTS `glpi_monitorsmodels`;
CREATE TABLE `glpi_monitorsmodels` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_monitorstypes

DROP TABLE IF EXISTS `glpi_monitorstypes`;
CREATE TABLE `glpi_monitorstypes` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_netpoints

DROP TABLE IF EXISTS `glpi_netpoints`;
CREATE TABLE `glpi_netpoints` (
  `ID` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `locations_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `location` (`locations_id`),
  KEY `name` (`name`),
  KEY `FK_entities` (`entities_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_networkequipments

DROP TABLE IF EXISTS `glpi_networkequipments`;
CREATE TABLE `glpi_networkequipments` (
  `ID` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `recursive` tinyint(1) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `ram` varchar(255) collate utf8_unicode_ci default NULL,
  `serial` varchar(255) collate utf8_unicode_ci default NULL,
  `otherserial` varchar(255) collate utf8_unicode_ci default NULL,
  `contact` varchar(255) collate utf8_unicode_ci default NULL,
  `contact_num` varchar(255) collate utf8_unicode_ci default NULL,
  `users_id_tech` int(11) NOT NULL default '0',
  `date_mod` datetime default NULL,
  `comments` text collate utf8_unicode_ci,
  `locations_id` int(11) NOT NULL default '0',
  `domain` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_domain (ID)',
  `network` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_network (ID)',
  `type` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_type_networking (ID)',
  `model` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_model_networking (ID)',
  `firmware` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_firmware (ID)',
  `FK_glpi_enterprise` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_manufacturer (ID)',
  `deleted` smallint(6) NOT NULL default '0',
  `is_template` smallint(6) NOT NULL default '0',
  `tplname` varchar(255) collate utf8_unicode_ci default NULL,
  `ifmac` varchar(255) collate utf8_unicode_ci default NULL,
  `ifaddr` varchar(255) collate utf8_unicode_ci default NULL,
  `notes` longtext collate utf8_unicode_ci,
  `users_id` int(11) NOT NULL default '0',
  `FK_groups` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_groups (ID)',
  `state` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_state (ID)',
  `ticket_tco` decimal(20,4) default '0.0000',
  PRIMARY KEY  (`ID`),
  KEY `firmware` (`firmware`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `name` (`name`),
  KEY `type` (`type`),
  KEY `model` (`model`),
  KEY `FK_groups` (`FK_groups`),
  KEY `network` (`network`),
  KEY `domain` (`domain`),
  KEY `is_template` (`is_template`),
  KEY `deleted` (`deleted`),
  KEY `entities_id` (`entities_id`),
  KEY `users_id` (`users_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `locations_id` (`locations_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_networkequipmentsfirmwares

DROP TABLE IF EXISTS `glpi_networkequipmentsfirmwares`;
CREATE TABLE `glpi_networkequipmentsfirmwares` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_networkequipmentsmodels

DROP TABLE IF EXISTS `glpi_networkequipmentsmodels`;
CREATE TABLE `glpi_networkequipmentsmodels` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_networkequipmentstypes

DROP TABLE IF EXISTS `glpi_networkequipmentstypes`;
CREATE TABLE `glpi_networkequipmentstypes` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_networkinterfaces

DROP TABLE IF EXISTS `glpi_networkinterfaces`;
CREATE TABLE `glpi_networkinterfaces` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_networkports

DROP TABLE IF EXISTS `glpi_networkports`;
CREATE TABLE `glpi_networkports` (
  `ID` int(11) NOT NULL auto_increment,
  `items_id` int(11) NOT NULL default '0',
  `itemtype` int(11) NOT NULL default '0',
  `logical_number` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `ifaddr` varchar(255) collate utf8_unicode_ci default NULL,
  `ifmac` varchar(255) collate utf8_unicode_ci default NULL,
  `iface` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_iface (ID)',
  `netpoint` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_netpoint (ID)',
  `netmask` varchar(255) collate utf8_unicode_ci default NULL,
  `gateway` varchar(255) collate utf8_unicode_ci default NULL,
  `subnet` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `on_device` (`items_id`,`itemtype`),
  KEY `netpoint` (`netpoint`),
  KEY `device_type` (`itemtype`),
  KEY `iface` (`iface`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_networkports_networkports

DROP TABLE IF EXISTS `glpi_networkports_networkports`;
CREATE TABLE `glpi_networkports_networkports` (
  `ID` int(11) NOT NULL auto_increment,
  `end1` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_networking_ports (ID)',
  `end2` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_networking_ports (ID)',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `netwire` (`end1`,`end2`),
  KEY `end2` (`end2`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_networkports_vlans

DROP TABLE IF EXISTS `glpi_networkports_vlans`;
CREATE TABLE `glpi_networkports_vlans` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_port` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_networking_ports (ID)',
  `FK_vlan` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_vlan (ID)',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `portvlan` (`FK_port`,`FK_vlan`),
  KEY `FK_vlan` (`FK_vlan`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_networks

DROP TABLE IF EXISTS `glpi_networks`;
CREATE TABLE `glpi_networks` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_ocsadmininfoslinks

DROP TABLE IF EXISTS `glpi_ocsadmininfoslinks`;
CREATE TABLE `glpi_ocsadmininfoslinks` (
  `ID` int(10) unsigned NOT NULL auto_increment,
  `glpi_column` varchar(255) collate utf8_unicode_ci default NULL,
  `ocs_column` varchar(255) collate utf8_unicode_ci default NULL,
  `ocs_server_id` int(11) NOT NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_ocslinks

DROP TABLE IF EXISTS `glpi_ocslinks`;
CREATE TABLE `glpi_ocslinks` (
  `ID` int(11) NOT NULL auto_increment,
  `glpi_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_computers (ID)',
  `ocs_id` int(11) NOT NULL default '0',
  `ocs_deviceid` varchar(255) collate utf8_unicode_ci default NULL,
  `auto_update` int(2) NOT NULL default '1',
  `last_update` datetime default NULL,
  `last_ocs_update` datetime default NULL,
  `computer_update` longtext collate utf8_unicode_ci,
  `import_device` longtext collate utf8_unicode_ci,
  `import_disk` longtext collate utf8_unicode_ci,
  `import_software` longtext collate utf8_unicode_ci,
  `import_monitor` longtext collate utf8_unicode_ci,
  `import_peripheral` longtext collate utf8_unicode_ci,
  `import_printers` longtext collate utf8_unicode_ci,
  `ocs_server_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_ocs_config (ID)',
  `import_ip` longtext collate utf8_unicode_ci,
  `ocs_agent_version` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `ocs_server_id` (`ocs_server_id`,`ocs_id`),
  KEY `glpi_id` (`glpi_id`),
  KEY `auto_update` (`auto_update`),
  KEY `last_update` (`last_update`),
  KEY `ocs_deviceid` (`ocs_deviceid`),
  KEY `last_ocs_update` (`ocs_server_id`,`last_ocs_update`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_ocsservers

DROP TABLE IF EXISTS `glpi_ocsservers`;
CREATE TABLE `glpi_ocsservers` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `ocs_db_user` varchar(255) collate utf8_unicode_ci default NULL,
  `ocs_db_passwd` varchar(255) collate utf8_unicode_ci default NULL,
  `ocs_db_host` varchar(255) collate utf8_unicode_ci default NULL,
  `ocs_db_name` varchar(255) collate utf8_unicode_ci default NULL,
  `checksum` int(11) NOT NULL default '0',
  `import_periph` int(2) NOT NULL default '0',
  `import_monitor` int(2) NOT NULL default '0',
  `import_software` int(2) NOT NULL default '0',
  `import_printer` int(2) NOT NULL default '0',
  `import_general_name` int(2) NOT NULL default '0',
  `import_general_os` int(2) NOT NULL default '0',
  `import_general_serial` int(2) NOT NULL default '0',
  `import_general_model` int(2) NOT NULL default '0',
  `import_general_enterprise` int(2) NOT NULL default '0',
  `import_general_type` int(2) NOT NULL default '0',
  `import_general_domain` int(2) NOT NULL default '0',
  `import_general_contact` int(2) NOT NULL default '0',
  `import_general_comments` int(2) NOT NULL default '0',
  `import_device_processor` int(2) NOT NULL default '0',
  `import_device_memory` int(2) NOT NULL default '0',
  `import_device_hdd` int(2) NOT NULL default '0',
  `import_device_iface` int(2) NOT NULL default '0',
  `import_device_gfxcard` int(2) NOT NULL default '0',
  `import_device_sound` int(2) NOT NULL default '0',
  `import_device_drives` int(2) NOT NULL default '0',
  `import_device_ports` int(2) NOT NULL default '0',
  `import_device_modems` int(2) NOT NULL default '0',
  `import_registry` int(11) NOT NULL default '0',
  `import_os_serial` int(2) default NULL,
  `import_ip` int(2) NOT NULL default '0',
  `import_disk` int(2) NOT NULL default '0',
  `import_monitor_comments` int(2) NOT NULL default '0',
  `import_software_comments` int(11) NOT NULL default '0',
  `default_state` int(11) NOT NULL default '0',
  `tag_limit` varchar(255) collate utf8_unicode_ci default NULL,
  `tag_exclude` varchar(255) collate utf8_unicode_ci default NULL,
  `use_soft_dict` char(1) collate utf8_unicode_ci default '0',
  `cron_sync_number` int(11) default '1',
  `deconnection_behavior` varchar(255) collate utf8_unicode_ci default NULL,
  `glpi_link_enabled` int(1) NOT NULL default '0',
  `link_ip` int(1) NOT NULL default '0',
  `link_name` int(1) NOT NULL default '0',
  `link_mac_address` int(1) NOT NULL default '0',
  `link_serial` int(1) NOT NULL default '0',
  `link_if_status` int(11) NOT NULL default '0',
  `ocs_url` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_ocsservers` VALUES ('1','localhost','ocs','ocs','localhost','ocsweb','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0',NULL,'0','0','0','0','0','',NULL,'0','1',NULL,'0','0','0','0','0','0','');

### Dump table glpi_operatingsystems

DROP TABLE IF EXISTS `glpi_operatingsystems`;
CREATE TABLE `glpi_operatingsystems` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_operatingsystemsservicepacks

DROP TABLE IF EXISTS `glpi_operatingsystemsservicepacks`;
CREATE TABLE `glpi_operatingsystemsservicepacks` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_operatingsystemsversions

DROP TABLE IF EXISTS `glpi_operatingsystemsversions`;
CREATE TABLE `glpi_operatingsystemsversions` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_peripherals

DROP TABLE IF EXISTS `glpi_peripherals`;
CREATE TABLE `glpi_peripherals` (
  `ID` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `date_mod` datetime default NULL,
  `contact` varchar(255) collate utf8_unicode_ci default NULL,
  `contact_num` varchar(255) collate utf8_unicode_ci default NULL,
  `users_id_tech` int(11) NOT NULL default '0',
  `comments` text collate utf8_unicode_ci,
  `serial` varchar(255) collate utf8_unicode_ci default NULL,
  `otherserial` varchar(255) collate utf8_unicode_ci default NULL,
  `locations_id` int(11) NOT NULL default '0',
  `type` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_type_peripherals (ID)',
  `model` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_model_peripherals (ID)',
  `brand` varchar(255) collate utf8_unicode_ci default NULL,
  `FK_glpi_enterprise` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_manufacturer (ID)',
  `is_global` smallint(6) NOT NULL default '0',
  `deleted` smallint(6) NOT NULL default '0',
  `is_template` smallint(6) NOT NULL default '0',
  `tplname` varchar(255) collate utf8_unicode_ci default NULL,
  `notes` longtext collate utf8_unicode_ci,
  `users_id` int(11) NOT NULL default '0',
  `FK_groups` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_groups (ID)',
  `state` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_state (ID)',
  `ticket_tco` decimal(20,4) default '0.0000',
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `name` (`name`),
  KEY `type` (`type`),
  KEY `model` (`model`),
  KEY `FK_groups` (`FK_groups`),
  KEY `is_template` (`is_template`),
  KEY `is_global` (`is_global`),
  KEY `deleted` (`deleted`),
  KEY `entities_id` (`entities_id`),
  KEY `users_id` (`users_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `locations_id` (`locations_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_peripheralsmodels

DROP TABLE IF EXISTS `glpi_peripheralsmodels`;
CREATE TABLE `glpi_peripheralsmodels` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_peripheralstypes

DROP TABLE IF EXISTS `glpi_peripheralstypes`;
CREATE TABLE `glpi_peripheralstypes` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_phones

DROP TABLE IF EXISTS `glpi_phones`;
CREATE TABLE `glpi_phones` (
  `ID` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `date_mod` datetime default NULL,
  `contact` varchar(255) collate utf8_unicode_ci default NULL,
  `contact_num` varchar(255) collate utf8_unicode_ci default NULL,
  `users_id_tech` int(11) NOT NULL default '0',
  `comments` text collate utf8_unicode_ci,
  `serial` varchar(255) collate utf8_unicode_ci default NULL,
  `otherserial` varchar(255) collate utf8_unicode_ci default NULL,
  `firmware` varchar(255) collate utf8_unicode_ci default NULL,
  `locations_id` int(11) NOT NULL default '0',
  `type` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_type_phones (ID)',
  `model` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_model_phones (ID)',
  `brand` varchar(255) collate utf8_unicode_ci default NULL,
  `power` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_phone_power (ID)',
  `number_line` varchar(255) collate utf8_unicode_ci default NULL,
  `flags_casque` smallint(6) NOT NULL default '0',
  `flags_hp` smallint(6) NOT NULL default '0',
  `FK_glpi_enterprise` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_manufacturer (ID)',
  `is_global` smallint(6) NOT NULL default '0',
  `deleted` smallint(6) NOT NULL default '0',
  `is_template` smallint(6) NOT NULL default '0',
  `tplname` varchar(255) collate utf8_unicode_ci default NULL,
  `notes` longtext collate utf8_unicode_ci,
  `users_id` int(11) NOT NULL default '0',
  `FK_groups` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_groups (ID)',
  `state` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_state (ID)',
  `ticket_tco` decimal(20,4) default '0.0000',
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `type` (`type`),
  KEY `model` (`model`),
  KEY `FK_groups` (`FK_groups`),
  KEY `power` (`power`),
  KEY `is_template` (`is_template`),
  KEY `is_global` (`is_global`),
  KEY `deleted` (`deleted`),
  KEY `entities_id` (`entities_id`),
  KEY `users_id` (`users_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `locations_id` (`locations_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_phonesmodels

DROP TABLE IF EXISTS `glpi_phonesmodels`;
CREATE TABLE `glpi_phonesmodels` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_phonespowersupplies

DROP TABLE IF EXISTS `glpi_phonespowersupplies`;
CREATE TABLE `glpi_phonespowersupplies` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_phonestypes

DROP TABLE IF EXISTS `glpi_phonestypes`;
CREATE TABLE `glpi_phonestypes` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_plugins

DROP TABLE IF EXISTS `glpi_plugins`;
CREATE TABLE `glpi_plugins` (
  `ID` int(11) NOT NULL auto_increment,
  `directory` varchar(255) collate utf8_unicode_ci NOT NULL,
  `name` varchar(255) collate utf8_unicode_ci NOT NULL,
  `version` varchar(255) collate utf8_unicode_ci NOT NULL,
  `state` tinyint(4) NOT NULL default '0' COMMENT 'see define.php PLUGIN_* constant',
  `author` varchar(255) collate utf8_unicode_ci default NULL,
  `homepage` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `name` (`directory`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_printers

DROP TABLE IF EXISTS `glpi_printers`;
CREATE TABLE `glpi_printers` (
  `ID` int(10) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `recursive` tinyint(1) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `date_mod` datetime default NULL,
  `contact` varchar(255) collate utf8_unicode_ci default NULL,
  `contact_num` varchar(255) collate utf8_unicode_ci default NULL,
  `users_id_tech` int(11) NOT NULL default '0',
  `serial` varchar(255) collate utf8_unicode_ci default NULL,
  `otherserial` varchar(255) collate utf8_unicode_ci default NULL,
  `flags_serial` smallint(6) NOT NULL default '0',
  `flags_par` smallint(6) NOT NULL default '0',
  `flags_usb` smallint(6) NOT NULL default '0',
  `comments` text collate utf8_unicode_ci,
  `ramSize` varchar(255) collate utf8_unicode_ci default NULL,
  `locations_id` int(11) NOT NULL default '0',
  `domain` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_domain (ID)',
  `network` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_network (ID)',
  `type` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_type_printers (ID)',
  `model` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_model_printers (ID)',
  `FK_glpi_enterprise` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_manufacturer (ID)',
  `is_global` smallint(6) NOT NULL default '0',
  `deleted` smallint(6) NOT NULL default '0',
  `is_template` smallint(6) NOT NULL default '0',
  `tplname` varchar(255) collate utf8_unicode_ci default NULL,
  `initial_pages` varchar(255) collate utf8_unicode_ci default NULL,
  `notes` longtext collate utf8_unicode_ci,
  `users_id` int(11) NOT NULL default '0',
  `FK_groups` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_groups (ID)',
  `state` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_state (ID)',
  `ticket_tco` decimal(20,4) default '0.0000',
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `name` (`name`),
  KEY `type` (`type`),
  KEY `model` (`model`),
  KEY `FK_groups` (`FK_groups`),
  KEY `network` (`network`),
  KEY `domain` (`domain`),
  KEY `is_template` (`is_template`),
  KEY `is_global` (`is_global`),
  KEY `deleted` (`deleted`),
  KEY `entities_id` (`entities_id`),
  KEY `users_id` (`users_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `locations_id` (`locations_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_printersmodels

DROP TABLE IF EXISTS `glpi_printersmodels`;
CREATE TABLE `glpi_printersmodels` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_printerstypes

DROP TABLE IF EXISTS `glpi_printerstypes`;
CREATE TABLE `glpi_printerstypes` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_profiles

DROP TABLE IF EXISTS `glpi_profiles`;
CREATE TABLE `glpi_profiles` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `interface` varchar(255) collate utf8_unicode_ci default 'helpdesk',
  `is_default` smallint(6) NOT NULL default '0',
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
  `rule_tracking` char(1) collate utf8_unicode_ci default NULL,
  `rule_ocs` char(1) collate utf8_unicode_ci default NULL,
  `rule_ldap` char(1) collate utf8_unicode_ci default NULL,
  `rule_softwarecategories` char(1) collate utf8_unicode_ci default NULL,
  `search_config` char(1) collate utf8_unicode_ci default NULL,
  `search_config_global` char(1) collate utf8_unicode_ci default NULL,
  `check_update` char(1) collate utf8_unicode_ci default NULL,
  `profile` char(1) collate utf8_unicode_ci default NULL,
  `user` char(1) collate utf8_unicode_ci default NULL,
  `user_auth_method` char(1) collate utf8_unicode_ci default NULL,
  `group` char(1) collate utf8_unicode_ci default NULL,
  `entity` char(1) collate utf8_unicode_ci default NULL,
  `transfer` char(1) collate utf8_unicode_ci default NULL,
  `logs` char(1) collate utf8_unicode_ci default NULL,
  `reminder_public` char(1) collate utf8_unicode_ci default NULL,
  `bookmark_public` char(1) collate utf8_unicode_ci default NULL,
  `backup` char(1) collate utf8_unicode_ci default NULL,
  `create_ticket` char(1) collate utf8_unicode_ci default NULL,
  `delete_ticket` char(1) collate utf8_unicode_ci default NULL,
  `comment_ticket` char(1) collate utf8_unicode_ci default NULL,
  `comment_all_ticket` char(1) collate utf8_unicode_ci default NULL,
  `update_ticket` char(1) collate utf8_unicode_ci default NULL,
  `own_ticket` char(1) collate utf8_unicode_ci default NULL,
  `steal_ticket` char(1) collate utf8_unicode_ci default NULL,
  `assign_ticket` char(1) collate utf8_unicode_ci default NULL,
  `show_all_ticket` char(1) collate utf8_unicode_ci default NULL,
  `show_assign_ticket` char(1) collate utf8_unicode_ci default NULL,
  `show_full_ticket` char(1) collate utf8_unicode_ci default NULL,
  `observe_ticket` char(1) collate utf8_unicode_ci default NULL,
  `update_followups` char(1) collate utf8_unicode_ci default NULL,
  `show_planning` char(1) collate utf8_unicode_ci default NULL,
  `show_group_planning` char(1) collate utf8_unicode_ci default NULL,
  `show_all_planning` char(1) collate utf8_unicode_ci default NULL,
  `statistic` char(1) collate utf8_unicode_ci default NULL,
  `password_update` char(1) collate utf8_unicode_ci default NULL,
  `helpdesk_hardware` smallint(6) NOT NULL default '0',
  `helpdesk_hardware_type` int(11) NOT NULL default '0',
  `show_group_ticket` char(1) collate utf8_unicode_ci default NULL,
  `show_group_hardware` char(1) collate utf8_unicode_ci default NULL,
  `rule_dictionnary_software` varchar(1) collate utf8_unicode_ci default NULL,
  `rule_dictionnary_dropdown` varchar(1) collate utf8_unicode_ci default NULL,
  `budget` varchar(1) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `interface` (`interface`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_profiles` VALUES ('1','post-only','helpdesk','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'r','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1',NULL,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1',NULL,NULL,NULL,NULL,NULL,'1','1','8388674','0','0',NULL,NULL,NULL);
INSERT INTO `glpi_profiles` VALUES ('2','normal','central','0','r','r','r','r','r','r','r','r','r','r','r','r','r','r','r','r','1','r','r',NULL,'r',NULL,NULL,NULL,NULL,'r','r',NULL,NULL,NULL,NULL,NULL,'w',NULL,'r',NULL,'r','r','r',NULL,NULL,NULL,NULL,NULL,NULL,'1','1','1','0','0','1','0','0','1','1','0','1','0','1','0','0','1','1','1','8388674','0','0',NULL,NULL,'r');
INSERT INTO `glpi_profiles` VALUES ('3','admin','central','0','w','w','w','w','w','w','w','w','w','w','w','w','w','w','w','w','1','w','r','w','r','w','w','w','w','w','w',NULL,NULL,NULL,NULL,NULL,'w','w','r','r','w','w','w',NULL,NULL,NULL,NULL,NULL,NULL,'1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','3','8388674','0','0',NULL,NULL,'w');
INSERT INTO `glpi_profiles` VALUES ('4','super-admin','central','0','w','w','w','w','w','w','w','w','w','w','w','w','w','w','w','w','1','w','r','w','r','w','w','w','w','w','w','w','w','w','w','w','w','w','r','w','w','w','w','w','w','r','w','w','w','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','3','8388674','0','0','w','w','w');

### Dump table glpi_profiles_users

DROP TABLE IF EXISTS `glpi_profiles_users`;
CREATE TABLE `glpi_profiles_users` (
  `ID` int(11) NOT NULL auto_increment,
  `users_id` int(11) NOT NULL default '0',
  `FK_profiles` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_profiles (ID)',
  `entities_id` int(11) NOT NULL default '0',
  `recursive` smallint(6) NOT NULL default '1',
  `dynamic` smallint(6) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY `FK_profiles` (`FK_profiles`),
  KEY `recursive` (`recursive`),
  KEY `entities_id` (`entities_id`),
  KEY `users_id` (`users_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_profiles_users` VALUES ('2','2','4','0','1','0');
INSERT INTO `glpi_profiles_users` VALUES ('3','3','1','0','1','0');
INSERT INTO `glpi_profiles_users` VALUES ('4','4','4','0','1','0');
INSERT INTO `glpi_profiles_users` VALUES ('5','5','2','0','1','0');

### Dump table glpi_registrykeys

DROP TABLE IF EXISTS `glpi_registrykeys`;
CREATE TABLE `glpi_registrykeys` (
  `ID` int(10) NOT NULL auto_increment,
  `computer_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_computers (ID)',
  `registry_hive` varchar(255) collate utf8_unicode_ci default NULL,
  `registry_path` varchar(255) collate utf8_unicode_ci default NULL,
  `registry_value` varchar(255) collate utf8_unicode_ci default NULL,
  `registry_ocs_name` char(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `computer_id` (`computer_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_reminders

DROP TABLE IF EXISTS `glpi_reminders`;
CREATE TABLE `glpi_reminders` (
  `ID` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `date` datetime default NULL,
  `users_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `text` text collate utf8_unicode_ci,
  `private` tinyint(1) NOT NULL default '1',
  `recursive` tinyint(1) NOT NULL default '0',
  `begin` datetime default NULL,
  `end` datetime default NULL,
  `rv` smallint(6) NOT NULL default '0',
  `date_mod` datetime default NULL,
  `state` smallint(6) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY `date` (`date`),
  KEY `begin` (`begin`),
  KEY `end` (`end`),
  KEY `rv` (`rv`),
  KEY `recursive` (`recursive`),
  KEY `private` (`private`),
  KEY `entities_id` (`entities_id`),
  KEY `users_id` (`users_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_reservations

DROP TABLE IF EXISTS `glpi_reservations`;
CREATE TABLE `glpi_reservations` (
  `ID` bigint(20) NOT NULL auto_increment,
  `id_item` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_reservation_item (ID)',
  `begin` datetime default NULL,
  `end` datetime default NULL,
  `users_id` int(11) NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `id_item` (`id_item`),
  KEY `begin` (`begin`),
  KEY `end` (`end`),
  KEY `users_id` (`users_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_reservationsitems

DROP TABLE IF EXISTS `glpi_reservationsitems`;
CREATE TABLE `glpi_reservationsitems` (
  `ID` int(11) NOT NULL auto_increment,
  `itemtype` int(11) NOT NULL default '0',
  `items_id` int(11) NOT NULL default '0',
  `comments` text collate utf8_unicode_ci,
  `active` smallint(6) NOT NULL default '1',
  PRIMARY KEY  (`ID`),
  KEY `reservationitem` (`itemtype`,`items_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rules

DROP TABLE IF EXISTS `glpi_rules`;
CREATE TABLE `glpi_rules` (
  `ID` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `sub_type` smallint(4) NOT NULL default '0' COMMENT 'see define.php RULE_* constant',
  `ranking` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `description` text collate utf8_unicode_ci,
  `match` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'see define.php *_MATCHING constant',
  `active` int(1) NOT NULL default '1',
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `entities_id` (`entities_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_rules` VALUES ('1','-1','0','0','Root','','AND','1',NULL);
INSERT INTO `glpi_rules` VALUES ('2','-1','1','1','Root','','OR','1',NULL);

### Dump table glpi_rulesactions

DROP TABLE IF EXISTS `glpi_rulesactions`;
CREATE TABLE `glpi_rulesactions` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_rules` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_rules_descriptions (ID)',
  `action_type` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'VALUE IN (assign, regex_result, append_regex_result, affectbyip, affectbyfqdn, affectbymac)',
  `field` varchar(255) collate utf8_unicode_ci default NULL,
  `value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `FK_rules` (`FK_rules`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_rulesactions` VALUES ('1','1','assign','FK_entities','0');
INSERT INTO `glpi_rulesactions` VALUES ('2','2','assign','FK_entities','0');

### Dump table glpi_rulescachecomputersmodels

DROP TABLE IF EXISTS `glpi_rulescachecomputersmodels`;
CREATE TABLE `glpi_rulescachecomputersmodels` (
  `ID` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rule_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_rules_descriptions (ID)',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  `manufacturer` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `rule_id` (`rule_id`),
  KEY `old_value` (`old_value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulescachecomputerstypes

DROP TABLE IF EXISTS `glpi_rulescachecomputerstypes`;
CREATE TABLE `glpi_rulescachecomputerstypes` (
  `ID` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rule_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_rules_descriptions (ID)',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `rule_id` (`rule_id`),
  KEY `old_value` (`old_value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulescachemanufacturers

DROP TABLE IF EXISTS `glpi_rulescachemanufacturers`;
CREATE TABLE `glpi_rulescachemanufacturers` (
  `ID` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rule_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_rules_descriptions (ID)',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `rule_id` (`rule_id`),
  KEY `old_value` (`old_value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulescachemonitorsmodels

DROP TABLE IF EXISTS `glpi_rulescachemonitorsmodels`;
CREATE TABLE `glpi_rulescachemonitorsmodels` (
  `ID` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rule_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_rules_descriptions (ID)',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  `manufacturer` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `rule_id` (`rule_id`),
  KEY `old_value` (`old_value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulescachemonitorstypes

DROP TABLE IF EXISTS `glpi_rulescachemonitorstypes`;
CREATE TABLE `glpi_rulescachemonitorstypes` (
  `ID` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rule_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_rules_descriptions (ID)',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `rule_id` (`rule_id`),
  KEY `old_value` (`old_value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulescachenetworkequipmentsmodels

DROP TABLE IF EXISTS `glpi_rulescachenetworkequipmentsmodels`;
CREATE TABLE `glpi_rulescachenetworkequipmentsmodels` (
  `ID` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rule_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_rules_descriptions (ID)',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  `manufacturer` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `rule_id` (`rule_id`),
  KEY `old_value` (`old_value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulescachenetworkequipmentstypes

DROP TABLE IF EXISTS `glpi_rulescachenetworkequipmentstypes`;
CREATE TABLE `glpi_rulescachenetworkequipmentstypes` (
  `ID` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rule_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_rules_descriptions (ID)',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `rule_id` (`rule_id`),
  KEY `old_value` (`old_value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulescacheoperatingsystems

DROP TABLE IF EXISTS `glpi_rulescacheoperatingsystems`;
CREATE TABLE `glpi_rulescacheoperatingsystems` (
  `ID` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rule_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_rules_descriptions (ID)',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `rule_id` (`rule_id`),
  KEY `old_value` (`old_value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulescacheoperatingsystemsservicepacks

DROP TABLE IF EXISTS `glpi_rulescacheoperatingsystemsservicepacks`;
CREATE TABLE `glpi_rulescacheoperatingsystemsservicepacks` (
  `ID` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rule_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_rules_descriptions (ID)',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `rule_id` (`rule_id`),
  KEY `old_value` (`old_value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulescacheoperatingsystemsversions

DROP TABLE IF EXISTS `glpi_rulescacheoperatingsystemsversions`;
CREATE TABLE `glpi_rulescacheoperatingsystemsversions` (
  `ID` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rule_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_rules_descriptions (ID)',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `rule_id` (`rule_id`),
  KEY `old_value` (`old_value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulescacheperipheralsmodels

DROP TABLE IF EXISTS `glpi_rulescacheperipheralsmodels`;
CREATE TABLE `glpi_rulescacheperipheralsmodels` (
  `ID` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rule_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_rules_descriptions (ID)',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  `manufacturer` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `rule_id` (`rule_id`),
  KEY `old_value` (`old_value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulescacheperipheralstypes

DROP TABLE IF EXISTS `glpi_rulescacheperipheralstypes`;
CREATE TABLE `glpi_rulescacheperipheralstypes` (
  `ID` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rule_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_rules_descriptions (ID)',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `rule_id` (`rule_id`),
  KEY `old_value` (`old_value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulescachephonesmodels

DROP TABLE IF EXISTS `glpi_rulescachephonesmodels`;
CREATE TABLE `glpi_rulescachephonesmodels` (
  `ID` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rule_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_rules_descriptions (ID)',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  `manufacturer` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `rule_id` (`rule_id`),
  KEY `old_value` (`old_value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulescachephonestypes

DROP TABLE IF EXISTS `glpi_rulescachephonestypes`;
CREATE TABLE `glpi_rulescachephonestypes` (
  `ID` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rule_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_rules_descriptions (ID)',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `rule_id` (`rule_id`),
  KEY `old_value` (`old_value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulescacheprintersmodels

DROP TABLE IF EXISTS `glpi_rulescacheprintersmodels`;
CREATE TABLE `glpi_rulescacheprintersmodels` (
  `ID` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rule_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_rules_descriptions (ID)',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  `manufacturer` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `rule_id` (`rule_id`),
  KEY `old_value` (`old_value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulescacheprinterstypes

DROP TABLE IF EXISTS `glpi_rulescacheprinterstypes`;
CREATE TABLE `glpi_rulescacheprinterstypes` (
  `ID` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rule_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_rules_descriptions (ID)',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `rule_id` (`rule_id`),
  KEY `old_value` (`old_value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulescachesoftwares

DROP TABLE IF EXISTS `glpi_rulescachesoftwares`;
CREATE TABLE `glpi_rulescachesoftwares` (
  `ID` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `manufacturer` varchar(255) collate utf8_unicode_ci NOT NULL,
  `rule_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_rules_descriptions (ID)',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  `version` varchar(255) collate utf8_unicode_ci default NULL,
  `new_manufacturer` varchar(255) collate utf8_unicode_ci NOT NULL,
  `ignore_ocs_import` char(1) collate utf8_unicode_ci default NULL,
  `helpdesk_visible` char(1) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `rule_id` (`rule_id`),
  KEY `old_value` (`old_value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulescriterias

DROP TABLE IF EXISTS `glpi_rulescriterias`;
CREATE TABLE `glpi_rulescriterias` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_rules` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_rules_descriptions (ID)',
  `criteria` varchar(255) collate utf8_unicode_ci default NULL,
  `condition` smallint(4) NOT NULL default '0' COMMENT 'see define.php PATTERN_* and REGEX_* constant',
  `pattern` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `FK_rules` (`FK_rules`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_rulescriterias` VALUES ('1','1','TAG','0','*');
INSERT INTO `glpi_rulescriterias` VALUES ('2','2','uid','0','*');
INSERT INTO `glpi_rulescriterias` VALUES ('3','2','samaccountname','0','*');
INSERT INTO `glpi_rulescriterias` VALUES ('4','2','MAIL_EMAIL','0','*');

### Dump table glpi_rulesldapparameters

DROP TABLE IF EXISTS `glpi_rulesldapparameters`;
CREATE TABLE `glpi_rulesldapparameters` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `value` varchar(255) collate utf8_unicode_ci default NULL,
  `sub_type` smallint(6) NOT NULL default '1',
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_rulesldapparameters` VALUES ('1','(LDAP)Organization','o','1');
INSERT INTO `glpi_rulesldapparameters` VALUES ('2','(LDAP)Common Name','cn','1');
INSERT INTO `glpi_rulesldapparameters` VALUES ('3','(LDAP)Department Number','departmentnumber','1');
INSERT INTO `glpi_rulesldapparameters` VALUES ('4','(LDAP)Email','mail','1');
INSERT INTO `glpi_rulesldapparameters` VALUES ('5','Object Class','objectclass','1');
INSERT INTO `glpi_rulesldapparameters` VALUES ('6','(LDAP)User ID','uid','1');
INSERT INTO `glpi_rulesldapparameters` VALUES ('7','(LDAP)Telephone Number','phone','1');
INSERT INTO `glpi_rulesldapparameters` VALUES ('8','(LDAP)Employee Number','employeenumber','1');
INSERT INTO `glpi_rulesldapparameters` VALUES ('9','(LDAP)Manager','manager','1');
INSERT INTO `glpi_rulesldapparameters` VALUES ('10','(LDAP)DistinguishedName','dn','1');
INSERT INTO `glpi_rulesldapparameters` VALUES ('12','(AD)User ID','samaccountname','1');
INSERT INTO `glpi_rulesldapparameters` VALUES ('13','(LDAP) Title','title','1');

### Dump table glpi_softwares

DROP TABLE IF EXISTS `glpi_softwares`;
CREATE TABLE `glpi_softwares` (
  `ID` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `recursive` tinyint(1) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  `location` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_locations (ID)',
  `users_id_tech` int(11) NOT NULL default '0',
  `platform` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_os (ID)',
  `is_update` smallint(6) NOT NULL default '0',
  `update_software` int(11) NOT NULL default '-1' COMMENT 'RELATION to glpi_software (ID)',
  `FK_glpi_enterprise` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_manufacturer (ID)',
  `deleted` smallint(6) NOT NULL default '0',
  `is_template` smallint(6) NOT NULL default '0',
  `tplname` varchar(255) collate utf8_unicode_ci default NULL,
  `date_mod` datetime default NULL,
  `notes` longtext collate utf8_unicode_ci,
  `users_id` int(11) NOT NULL default '0',
  `FK_groups` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_groups (ID)',
  `oldstate` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_state (ID)',
  `ticket_tco` decimal(20,4) default '0.0000',
  `helpdesk_visible` int(11) NOT NULL default '1',
  `category` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_software_category (ID)',
  PRIMARY KEY  (`ID`),
  KEY `platform` (`platform`),
  KEY `location` (`location`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `date_mod` (`date_mod`),
  KEY `name` (`name`),
  KEY `FK_groups` (`FK_groups`),
  KEY `update_software` (`update_software`),
  KEY `is_template` (`is_template`),
  KEY `is_update` (`is_update`),
  KEY `deleted` (`deleted`),
  KEY `entities_id` (`entities_id`),
  KEY `users_id` (`users_id`),
  KEY `users_id_tech` (`users_id_tech`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_softwarescategories

DROP TABLE IF EXISTS `glpi_softwarescategories`;
CREATE TABLE `glpi_softwarescategories` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_softwarescategories` VALUES ('1','FUSION',NULL);

### Dump table glpi_softwareslicenses

DROP TABLE IF EXISTS `glpi_softwareslicenses`;
CREATE TABLE `glpi_softwareslicenses` (
  `ID` int(11) NOT NULL auto_increment,
  `sID` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_software (ID)',
  `entities_id` int(11) NOT NULL default '0',
  `recursive` tinyint(1) NOT NULL default '0',
  `number` int(11) NOT NULL default '0',
  `type` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_licensetypes (ID)',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `serial` varchar(255) collate utf8_unicode_ci default NULL,
  `otherserial` varchar(255) collate utf8_unicode_ci default NULL,
  `buy_version` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_softwareversions (ID)',
  `use_version` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_softwareversions (ID)',
  `expire` date default NULL,
  `FK_computers` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_computers (ID)',
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`),
  KEY `type` (`type`),
  KEY `sID` (`sID`),
  KEY `buy_version` (`buy_version`),
  KEY `use_version` (`use_version`),
  KEY `FK_computers` (`FK_computers`),
  KEY `serial` (`serial`),
  KEY `otherserial` (`otherserial`),
  KEY `expire` (`expire`),
  KEY `entities_id` (`entities_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_softwareslicensestypes

DROP TABLE IF EXISTS `glpi_softwareslicensestypes`;
CREATE TABLE `glpi_softwareslicensestypes` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_softwareslicensestypes` VALUES ('1','OEM','');

### Dump table glpi_softwaresversions

DROP TABLE IF EXISTS `glpi_softwaresversions`;
CREATE TABLE `glpi_softwaresversions` (
  `ID` int(11) NOT NULL auto_increment,
  `sID` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_software (ID)',
  `state` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_state (ID)',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `sID` (`sID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_states

DROP TABLE IF EXISTS `glpi_states`;
CREATE TABLE `glpi_states` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_suppliers

DROP TABLE IF EXISTS `glpi_suppliers`;
CREATE TABLE `glpi_suppliers` (
  `ID` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `recursive` tinyint(1) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `type` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_enttype (ID)',
  `address` text collate utf8_unicode_ci,
  `postcode` varchar(255) collate utf8_unicode_ci default NULL,
  `town` varchar(255) collate utf8_unicode_ci default NULL,
  `state` varchar(255) collate utf8_unicode_ci default NULL,
  `country` varchar(255) collate utf8_unicode_ci default NULL,
  `website` varchar(255) collate utf8_unicode_ci default NULL,
  `phonenumber` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  `deleted` smallint(6) NOT NULL default '0',
  `fax` varchar(255) collate utf8_unicode_ci default NULL,
  `email` varchar(255) collate utf8_unicode_ci default NULL,
  `notes` longtext collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `type` (`type`),
  KEY `name` (`name`),
  KEY `deleted` (`deleted`),
  KEY `entities_id` (`entities_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_supplierstypes

DROP TABLE IF EXISTS `glpi_supplierstypes`;
CREATE TABLE `glpi_supplierstypes` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_tickets

DROP TABLE IF EXISTS `glpi_tickets`;
CREATE TABLE `glpi_tickets` (
  `ID` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `date` datetime default NULL,
  `closedate` datetime default NULL,
  `date_mod` datetime default NULL,
  `status` varchar(255) collate utf8_unicode_ci default 'new',
  `users_id` int(11) NOT NULL default '0',
  `users_id_recipient` int(11) NOT NULL default '0',
  `FK_group` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_groups (ID)',
  `request_type` smallint(6) NOT NULL default '0',
  `users_id_assign` int(11) NOT NULL default '0',
  `assign_ent` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_enterprises (ID)',
  `assign_group` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_groups (ID)',
  `itemtype` int(11) NOT NULL default '0',
  `items_id` int(11) NOT NULL default '0',
  `contents` longtext collate utf8_unicode_ci,
  `priority` smallint(6) NOT NULL default '1',
  `uemail` varchar(255) collate utf8_unicode_ci default NULL,
  `emailupdates` smallint(6) NOT NULL default '0',
  `realtime` float NOT NULL default '0',
  `category` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_tracking_category (ID)',
  `cost_time` decimal(20,4) NOT NULL default '0.0000',
  `cost_fixed` decimal(20,4) NOT NULL default '0.0000',
  `cost_material` decimal(20,4) NOT NULL default '0.0000',
  PRIMARY KEY  (`ID`),
  KEY `computer` (`items_id`),
  KEY `date` (`date`),
  KEY `closedate` (`closedate`),
  KEY `status` (`status`),
  KEY `category` (`category`),
  KEY `FK_group` (`FK_group`),
  KEY `assign_ent` (`assign_ent`),
  KEY `device_type` (`itemtype`),
  KEY `priority` (`priority`),
  KEY `request_type` (`request_type`),
  KEY `assign_group` (`assign_group`),
  KEY `users_id_assign` (`users_id_assign`),
  KEY `users_id` (`users_id`),
  KEY `entities_id` (`entities_id`),
  KEY `users_id_recipient` (`users_id_recipient`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_ticketscategories

DROP TABLE IF EXISTS `glpi_ticketscategories`;
CREATE TABLE `glpi_ticketscategories` (
  `ID` int(11) NOT NULL auto_increment,
  `ticketscategories_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `completename` text collate utf8_unicode_ci,
  `comments` text collate utf8_unicode_ci,
  `level` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`),
  KEY `ticketscategories_id` (`ticketscategories_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_ticketsfollowups

DROP TABLE IF EXISTS `glpi_ticketsfollowups`;
CREATE TABLE `glpi_ticketsfollowups` (
  `ID` int(11) NOT NULL auto_increment,
  `tracking` int(11) default NULL COMMENT 'RELATION to glpi_tracking (ID)',
  `date` datetime default NULL,
  `users_id` int(11) NOT NULL default '0',
  `contents` text collate utf8_unicode_ci,
  `private` int(1) NOT NULL default '0',
  `realtime` float NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY `tracking` (`tracking`),
  KEY `date` (`date`),
  KEY `users_id` (`users_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_ticketsplannings

DROP TABLE IF EXISTS `glpi_ticketsplannings`;
CREATE TABLE `glpi_ticketsplannings` (
  `ID` bigint(20) NOT NULL auto_increment,
  `id_followup` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_followups (ID)',
  `users_id` int(11) NOT NULL default '0',
  `begin` datetime default NULL,
  `end` datetime default NULL,
  `state` smallint(6) NOT NULL default '1',
  PRIMARY KEY  (`ID`),
  KEY `begin` (`begin`),
  KEY `end` (`end`),
  KEY `id_followup` (`id_followup`),
  KEY `users_id` (`users_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_transfers

DROP TABLE IF EXISTS `glpi_transfers`;
CREATE TABLE `glpi_transfers` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `keep_tickets` smallint(6) NOT NULL default '0',
  `keep_networklinks` smallint(6) NOT NULL default '0',
  `keep_reservations` smallint(6) NOT NULL default '0',
  `keep_history` smallint(6) NOT NULL default '0',
  `keep_devices` smallint(6) NOT NULL default '0',
  `keep_infocoms` smallint(6) NOT NULL default '0',
  `keep_dc_monitor` smallint(6) NOT NULL default '0',
  `clean_dc_monitor` smallint(6) NOT NULL default '0',
  `keep_dc_phone` smallint(6) NOT NULL default '0',
  `clean_dc_phone` smallint(6) NOT NULL default '0',
  `keep_dc_peripheral` smallint(6) NOT NULL default '0',
  `clean_dc_peripheral` smallint(6) NOT NULL default '0',
  `keep_dc_printer` smallint(6) NOT NULL default '0',
  `clean_dc_printer` smallint(6) NOT NULL default '0',
  `keep_enterprises` smallint(6) NOT NULL default '0',
  `clean_enterprises` smallint(6) NOT NULL default '0',
  `keep_contacts` smallint(6) NOT NULL default '0',
  `clean_contacts` smallint(6) NOT NULL default '0',
  `keep_contracts` smallint(6) NOT NULL default '0',
  `clean_contracts` smallint(6) NOT NULL default '0',
  `keep_softwares` smallint(6) NOT NULL default '0',
  `clean_softwares` smallint(6) NOT NULL default '0',
  `keep_documents` smallint(6) NOT NULL default '0',
  `clean_documents` smallint(6) NOT NULL default '0',
  `keep_cartridges_type` smallint(6) NOT NULL default '0',
  `clean_cartridges_type` smallint(6) NOT NULL default '0',
  `keep_cartridges` smallint(6) NOT NULL default '0',
  `keep_consumables` smallint(6) NOT NULL default '0',
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_transfers` VALUES ('1','complete','2','2','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1');

### Dump table glpi_users

DROP TABLE IF EXISTS `glpi_users`;
CREATE TABLE `glpi_users` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `password` varchar(255) collate utf8_unicode_ci default NULL,
  `password_md5` varchar(255) collate utf8_unicode_ci default NULL,
  `email` varchar(255) collate utf8_unicode_ci default NULL,
  `phone` varchar(255) collate utf8_unicode_ci default NULL,
  `phone2` varchar(255) collate utf8_unicode_ci default NULL,
  `mobile` varchar(255) collate utf8_unicode_ci default NULL,
  `realname` varchar(255) collate utf8_unicode_ci default NULL,
  `firstname` varchar(255) collate utf8_unicode_ci default NULL,
  `locations_id` int(11) NOT NULL default '0',
  `tracking_order` smallint(6) default NULL,
  `language` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'see define.php CFG_GLPI[language] array',
  `use_mode` smallint(6) NOT NULL default '0' COMMENT 'see define.php *_MODE constant',
  `list_limit` int(11) default NULL,
  `active` int(2) NOT NULL default '1',
  `comments` text collate utf8_unicode_ci,
  `id_auth` int(11) NOT NULL default '-1',
  `auth_method` int(11) NOT NULL default '-1' COMMENT 'see define.php AUTH_* constant',
  `last_login` datetime default NULL,
  `date_mod` datetime default NULL,
  `deleted` smallint(6) NOT NULL default '0',
  `FK_profiles` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_profiles (ID)',
  `entities_id` int(11) NOT NULL default '0',
  `title` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_user_titles (ID)',
  `type` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_dropdown_user_types (ID)',
  `dateformat` smallint(6) default NULL,
  `numberformat` smallint(6) default NULL,
  `view_ID` smallint(6) default NULL,
  `dropdown_limit` int(11) default NULL,
  `flat_dropdowntree` smallint(6) default NULL,
  `num_of_events` int(11) default NULL,
  `nextprev_item` varchar(255) collate utf8_unicode_ci default NULL,
  `jobs_at_login` smallint(6) default NULL,
  `priority_1` varchar(255) collate utf8_unicode_ci default NULL,
  `priority_2` varchar(255) collate utf8_unicode_ci default NULL,
  `priority_3` varchar(255) collate utf8_unicode_ci default NULL,
  `priority_4` varchar(255) collate utf8_unicode_ci default NULL,
  `priority_5` varchar(255) collate utf8_unicode_ci default NULL,
  `expand_soft_categorized` int(1) default NULL,
  `expand_soft_not_categorized` int(1) default NULL,
  `followup_private` smallint(6) default NULL,
  `request_type` int(1) default NULL,
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `name` (`name`),
  KEY `firstname` (`firstname`),
  KEY `realname` (`realname`),
  KEY `deleted` (`deleted`),
  KEY `title` (`title`),
  KEY `type` (`type`),
  KEY `active` (`active`),
  KEY `entities_id` (`entities_id`),
  KEY `locations_id` (`locations_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_users` VALUES ('2','glpi','','41ece51526515624ff89973668497d00','','','','','',NULL,'0','1',NULL,'0','20','1',NULL,'-1','1','2009-07-28 17:28:35','2009-07-28 17:28:35','0','0','0','0','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `glpi_users` VALUES ('3','post-only','*5683D7F638D6598D057638B1957F194E4CA974FB','3177926a7314de24680a9938aaa97703','','','','','',NULL,'0','0','en_GB','0','20','1',NULL,'-1','-1',NULL,NULL,'0','0','0','0','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `glpi_users` VALUES ('4','tech','*B09F1B2C210DEEA69C662977CC69C6C461965B09','d9f9133fb120cd6096870bc2b496805b','','','','','',NULL,'0','1','fr_FR','0','20','1',NULL,'-1','-1',NULL,NULL,'0','0','0','0','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `glpi_users` VALUES ('5','normal','*F3F91B23FC1DB728B49B1F22DEE3D7A839E10F0E','fea087517c26fadd409bd4b9dc642555','','','','','',NULL,'0','0','en_GB','0','20','1',NULL,'-1','-1',NULL,NULL,'0','0','0','0','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);

### Dump table glpi_userstitles

DROP TABLE IF EXISTS `glpi_userstitles`;
CREATE TABLE `glpi_userstitles` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_userstypes

DROP TABLE IF EXISTS `glpi_userstypes`;
CREATE TABLE `glpi_userstypes` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_vlans

DROP TABLE IF EXISTS `glpi_vlans`;
CREATE TABLE `glpi_vlans` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

