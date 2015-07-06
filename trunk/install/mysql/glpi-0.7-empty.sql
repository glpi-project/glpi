#GLPI Dump database on 2007-09-29 15:51

### Dump table glpi_alerts

DROP TABLE IF EXISTS `glpi_alerts`;
CREATE TABLE `glpi_alerts` (
  `ID` int(11) NOT NULL auto_increment,
  `device_type` int(11) NOT NULL default '0',
  `FK_device` int(11) NOT NULL default '0',
  `type` int(11) NOT NULL default '0',
  `date` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `alert` (`device_type`,`FK_device`,`type`),
  KEY `item` (`device_type`,`FK_device`),
  KEY `device_type` (`device_type`),
  KEY `FK_device` (`FK_device`),
  KEY `type` (`type`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_auth_ldap

DROP TABLE IF EXISTS `glpi_auth_ldap`;
CREATE TABLE `glpi_auth_ldap` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `ldap_host` varchar(255) collate utf8_unicode_ci default NULL,
  `ldap_basedn` varchar(255) collate utf8_unicode_ci default NULL,
  `ldap_rootdn` varchar(255) collate utf8_unicode_ci default NULL,
  `ldap_pass` varchar(255) collate utf8_unicode_ci default NULL,
  `ldap_port` varchar(255) collate utf8_unicode_ci default '389',
  `ldap_condition` varchar(255) collate utf8_unicode_ci default NULL,
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
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_auth_mail

DROP TABLE IF EXISTS `glpi_auth_mail`;
CREATE TABLE `glpi_auth_mail` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `imap_auth_server` varchar(255) collate utf8_unicode_ci default NULL,
  `imap_host` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_cartridges

DROP TABLE IF EXISTS `glpi_cartridges`;
CREATE TABLE `glpi_cartridges` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_glpi_cartridges_type` int(11) NOT NULL default '0',
  `FK_glpi_printers` int(11) NOT NULL default '0',
  `date_in` date default NULL,
  `date_use` date default NULL,
  `date_out` date default NULL,
  `pages` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_cartridges_type` (`FK_glpi_cartridges_type`),
  KEY `FK_glpi_printers` (`FK_glpi_printers`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_cartridges_assoc

DROP TABLE IF EXISTS `glpi_cartridges_assoc`;
CREATE TABLE `glpi_cartridges_assoc` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_glpi_cartridges_type` int(11) NOT NULL default '0',
  `FK_glpi_dropdown_model_printers` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `FK_glpi_type_printer` (`FK_glpi_dropdown_model_printers`,`FK_glpi_cartridges_type`),
  KEY `FK_glpi_cartridges_type` (`FK_glpi_cartridges_type`),
  KEY `FK_glpi_type_printer_2` (`FK_glpi_dropdown_model_printers`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_cartridges_type

DROP TABLE IF EXISTS `glpi_cartridges_type`;
CREATE TABLE `glpi_cartridges_type` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_entities` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `ref` varchar(255) collate utf8_unicode_ci default NULL,
  `location` int(11) NOT NULL default '0',
  `type` int(11) NOT NULL default '0',
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `tech_num` int(11) NOT NULL default '0',
  `deleted` smallint(6) NOT NULL default '0',
  `comments` text collate utf8_unicode_ci,
  `alarm` smallint(6) NOT NULL default '10',
  `notes` longtext collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `tech_num` (`tech_num`),
  KEY `name` (`name`),
  KEY `location` (`location`),
  KEY `type` (`type`),
  KEY `alarm` (`alarm`),
  KEY `FK_entities` (`FK_entities`),
  KEY `deleted` (`deleted`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_computer_device

DROP TABLE IF EXISTS `glpi_computer_device`;
CREATE TABLE `glpi_computer_device` (
  `ID` int(11) NOT NULL auto_increment,
  `specificity` varchar(255) collate utf8_unicode_ci default NULL,
  `device_type` smallint(6) NOT NULL default '0',
  `FK_device` int(11) NOT NULL default '0',
  `FK_computers` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY `device_type` (`device_type`),
  KEY `device_type_2` (`device_type`,`FK_device`),
  KEY `FK_computers` (`FK_computers`),
  KEY `FK_device` (`FK_device`),
  KEY `specificity` (`specificity`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_computers

DROP TABLE IF EXISTS `glpi_computers`;
CREATE TABLE `glpi_computers` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_entities` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `serial` varchar(255) collate utf8_unicode_ci default NULL,
  `otherserial` varchar(255) collate utf8_unicode_ci default NULL,
  `contact` varchar(255) collate utf8_unicode_ci default NULL,
  `contact_num` varchar(255) collate utf8_unicode_ci default NULL,
  `tech_num` int(11) NOT NULL default '0',
  `comments` text collate utf8_unicode_ci,
  `date_mod` datetime default NULL,
  `os` int(11) NOT NULL default '0',
  `os_version` int(11) NOT NULL default '0',
  `os_sp` int(11) NOT NULL default '0',
  `os_license_number` varchar(255) collate utf8_unicode_ci default NULL,
  `os_license_id` varchar(255) collate utf8_unicode_ci default NULL,
  `auto_update` int(11) NOT NULL default '0',
  `location` int(11) NOT NULL default '0',
  `domain` int(11) NOT NULL default '0',
  `network` int(11) NOT NULL default '0',
  `model` int(11) NOT NULL default '0',
  `type` int(11) NOT NULL default '0',
  `is_template` smallint(6) NOT NULL default '0',
  `tplname` varchar(255) collate utf8_unicode_ci default NULL,
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `deleted` smallint(6) NOT NULL default '0',
  `notes` longtext collate utf8_unicode_ci,
  `ocs_import` smallint(6) NOT NULL default '0',
  `FK_users` int(11) NOT NULL default '0',
  `FK_groups` int(11) NOT NULL default '0',
  `state` int(11) NOT NULL default '0',
  `ticket_tco` decimal(20,4) default '0.0000',
  PRIMARY KEY  (`ID`),
  KEY `location` (`location`),
  KEY `os` (`os`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `date_mod` (`date_mod`),
  KEY `tech_num` (`tech_num`),
  KEY `name` (`name`),
  KEY `type` (`type`),
  KEY `model` (`model`),
  KEY `FK_groups` (`FK_groups`),
  KEY `FK_users` (`FK_users`),
  KEY `os_sp` (`os_sp`),
  KEY `os_version` (`os_version`),
  KEY `network` (`network`),
  KEY `domain` (`domain`),
  KEY `auto_update` (`auto_update`),
  KEY `ocs_import` (`ocs_import`),
  KEY `FK_entities` (`FK_entities`),
  KEY `is_template` (`is_template`),
  KEY `deleted` (`deleted`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_config

DROP TABLE IF EXISTS `glpi_config`;
CREATE TABLE `glpi_config` (
  `ID` int(11) NOT NULL auto_increment,
  `num_of_events` varchar(255) collate utf8_unicode_ci default NULL,
  `jobs_at_login` varchar(255) collate utf8_unicode_ci default NULL,
  `sendexpire` varchar(255) collate utf8_unicode_ci default NULL,
  `cut` varchar(255) collate utf8_unicode_ci default NULL,
  `expire_events` varchar(255) collate utf8_unicode_ci default NULL,
  `list_limit` int(11) default '20',
  `list_limit_max` int(11) NOT NULL default '50',
  `version` varchar(255) collate utf8_unicode_ci default NULL,
  `logotxt` varchar(255) collate utf8_unicode_ci default NULL,
  `event_loglevel` varchar(255) collate utf8_unicode_ci default NULL,
  `mailing` varchar(255) collate utf8_unicode_ci default NULL,
  `admin_email` varchar(255) collate utf8_unicode_ci default NULL,
  `mailing_signature` text collate utf8_unicode_ci,
  `permit_helpdesk` varchar(255) collate utf8_unicode_ci default NULL,
  `default_language` varchar(255) collate utf8_unicode_ci default 'en_GB',
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
  `nextprev_item` varchar(255) collate utf8_unicode_ci default 'name',
  `view_ID` smallint(6) NOT NULL default '0',
  `dropdown_limit` int(11) NOT NULL default '50',
  `ocs_mode` smallint(6) NOT NULL default '0',
  `debug` int(2) NOT NULL default '0',
  `use_cache` smallint(6) NOT NULL default '1',
  `smtp_mode` smallint(6) NOT NULL default '0',
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
  `cartridges_alert` int(11) NOT NULL default '0',
  `consumables_alert` int(11) NOT NULL default '0',
  `keep_tracking_on_delete` int(11) default '1',
  `show_admin_doc` int(11) default '0',
  `time_step` int(11) default '5',
  `decimal_number` int(11) default '2',
  `helpdeskhelp_url` varchar(255) collate utf8_unicode_ci default NULL,
  `centralhelp_url` varchar(255) collate utf8_unicode_ci default NULL,
  `default_rubdoc_tracking` int(11) default '0',
  `monitors_management_restrict` int(1) NOT NULL default '2',
  `phones_management_restrict` int(1) NOT NULL default '2',
  `peripherals_management_restrict` int(1) NOT NULL default '2',
  `printers_management_restrict` int(1) NOT NULL default '2',
  `licenses_management_restrict` int(1) NOT NULL default '2',
  `license_deglobalisation` int(1) NOT NULL default '1',
  `use_errorlog` int(1) NOT NULL default '0',
  `glpi_timezone` varchar(255) collate utf8_unicode_ci default NULL,
  `autoupdate_link_contact` smallint(6) NOT NULL default '1',
  `autoupdate_link_user` smallint(6) NOT NULL default '1',
  `autoupdate_link_group` smallint(6) NOT NULL default '1',
  `autoupdate_link_location` smallint(6) NOT NULL default '1',
  `flat_dropdowntree` smallint(6) NOT NULL default '0',
  `autoname_entity` smallint(6) NOT NULL default '1',
  `expand_soft_categorized` int(1) NOT NULL default '1',
  `expand_soft_not_categorized` int(1) NOT NULL default '1',
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO glpi_config VALUES ('1','10','0','1','255','30','15','50',' 0.7','GLPI powered by indepnet','5','0','admsys@xxxxx.fr','SIGNATURE','','fr_FR','#fff2f2','#ffe0e0','#ffcece','#ffbfbf','#ffadad','2005-12-31','10','','','',NULL,'08:00:00','20:00:00','1','0','0','http://localhost/glpi/','0','','0','','100','*','0','50','1','1','0','name','0','50','0','0','1','0',NULL,'25',NULL,NULL,NULL,'8080',NULL,NULL,'1','0','0','0','0','0','0','5','2',NULL,NULL,'0','2','2','2','2','2','1','0','0','1','1','1','1','0','1','1','1');

### Dump table glpi_connect_wire

DROP TABLE IF EXISTS `glpi_connect_wire`;
CREATE TABLE `glpi_connect_wire` (
  `ID` int(11) NOT NULL auto_increment,
  `end1` int(11) NOT NULL default '0',
  `end2` int(11) NOT NULL default '0',
  `type` smallint(6) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `end1_1` (`end1`,`end2`,`type`),
  KEY `end1` (`end1`),
  KEY `end2` (`end2`),
  KEY `type` (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_consumables

DROP TABLE IF EXISTS `glpi_consumables`;
CREATE TABLE `glpi_consumables` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_glpi_consumables_type` int(11) default NULL,
  `date_in` date default NULL,
  `date_out` date default NULL,
  `id_user` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_cartridges_type` (`FK_glpi_consumables_type`),
  KEY `date_in` (`date_in`),
  KEY `date_out` (`date_out`),
  KEY `id_user` (`id_user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_consumables_type

DROP TABLE IF EXISTS `glpi_consumables_type`;
CREATE TABLE `glpi_consumables_type` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_entities` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `ref` varchar(255) collate utf8_unicode_ci default NULL,
  `location` int(11) NOT NULL default '0',
  `type` int(11) NOT NULL default '0',
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `tech_num` int(11) NOT NULL default '0',
  `deleted` smallint(6) NOT NULL default '0',
  `comments` text collate utf8_unicode_ci,
  `alarm` int(11) NOT NULL default '10',
  `notes` longtext collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `tech_num` (`tech_num`),
  KEY `name` (`name`),
  KEY `location` (`location`),
  KEY `type` (`type`),
  KEY `alarm` (`alarm`),
  KEY `FK_entities` (`FK_entities`),
  KEY `deleted` (`deleted`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_contact_enterprise

DROP TABLE IF EXISTS `glpi_contact_enterprise`;
CREATE TABLE `glpi_contact_enterprise` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_enterprise` int(11) NOT NULL default '0',
  `FK_contact` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `FK_enterprise` (`FK_enterprise`,`FK_contact`),
  KEY `FK_enterprise_2` (`FK_enterprise`),
  KEY `FK_contact` (`FK_contact`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_contacts

DROP TABLE IF EXISTS `glpi_contacts`;
CREATE TABLE `glpi_contacts` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_entities` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `firstname` varchar(255) collate utf8_unicode_ci default NULL,
  `phone` varchar(255) collate utf8_unicode_ci default NULL,
  `phone2` varchar(255) collate utf8_unicode_ci default NULL,
  `mobile` varchar(255) collate utf8_unicode_ci default NULL,
  `fax` varchar(255) collate utf8_unicode_ci default NULL,
  `email` varchar(255) collate utf8_unicode_ci default NULL,
  `type` int(11) NOT NULL default '0',
  `comments` text collate utf8_unicode_ci,
  `deleted` smallint(6) NOT NULL default '0',
  `notes` longtext collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `type` (`type`),
  KEY `name` (`name`),
  KEY `FK_entities` (`FK_entities`),
  KEY `deleted` (`deleted`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_contract_device

DROP TABLE IF EXISTS `glpi_contract_device`;
CREATE TABLE `glpi_contract_device` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_contract` int(11) NOT NULL default '0',
  `FK_device` int(11) NOT NULL default '0',
  `device_type` smallint(6) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `FK_contract` (`FK_contract`,`FK_device`,`device_type`),
  KEY `FK_contract_2` (`FK_contract`),
  KEY `FK_device` (`FK_device`,`device_type`),
  KEY `device_type` (`device_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_contract_enterprise

DROP TABLE IF EXISTS `glpi_contract_enterprise`;
CREATE TABLE `glpi_contract_enterprise` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_enterprise` int(11) NOT NULL default '0',
  `FK_contract` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `FK_enterprise` (`FK_enterprise`,`FK_contract`),
  KEY `FK_enterprise_2` (`FK_enterprise`),
  KEY `FK_contract` (`FK_contract`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_contracts

DROP TABLE IF EXISTS `glpi_contracts`;
CREATE TABLE `glpi_contracts` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_entities` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `num` varchar(255) collate utf8_unicode_ci default NULL,
  `cost` float NOT NULL default '0',
  `contract_type` int(11) NOT NULL default '0',
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
  KEY `FK_entities` (`FK_entities`),
  KEY `deleted` (`deleted`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_device_case

DROP TABLE IF EXISTS `glpi_device_case`;
CREATE TABLE `glpi_device_case` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `type` int(11) NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `designation` (`designation`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_device_control

DROP TABLE IF EXISTS `glpi_device_control`;
CREATE TABLE `glpi_device_control` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `raid` smallint(6) NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  `interface` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `designation` (`designation`),
  KEY `interface` (`interface`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_device_drive

DROP TABLE IF EXISTS `glpi_device_drive`;
CREATE TABLE `glpi_device_drive` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `is_writer` smallint(6) NOT NULL default '1',
  `speed` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  `interface` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `designation` (`designation`),
  KEY `interface` (`interface`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_device_gfxcard

DROP TABLE IF EXISTS `glpi_device_gfxcard`;
CREATE TABLE `glpi_device_gfxcard` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `interface` varchar(255) collate utf8_unicode_ci default 'PCI-X',
  `comment` text collate utf8_unicode_ci,
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `designation` (`designation`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_device_hdd

DROP TABLE IF EXISTS `glpi_device_hdd`;
CREATE TABLE `glpi_device_hdd` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `rpm` varchar(255) collate utf8_unicode_ci default NULL,
  `interface` int(11) NOT NULL default '0',
  `cache` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `designation` (`designation`),
  KEY `interface` (`interface`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_device_iface

DROP TABLE IF EXISTS `glpi_device_iface`;
CREATE TABLE `glpi_device_iface` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `bandwidth` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `designation` (`designation`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_device_moboard

DROP TABLE IF EXISTS `glpi_device_moboard`;
CREATE TABLE `glpi_device_moboard` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `chipset` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `designation` (`designation`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_device_pci

DROP TABLE IF EXISTS `glpi_device_pci`;
CREATE TABLE `glpi_device_pci` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `designation` (`designation`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_device_power

DROP TABLE IF EXISTS `glpi_device_power`;
CREATE TABLE `glpi_device_power` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `power` varchar(255) collate utf8_unicode_ci default NULL,
  `atx` smallint(6) NOT NULL default '1',
  `comment` text collate utf8_unicode_ci,
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `designation` (`designation`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_device_processor

DROP TABLE IF EXISTS `glpi_device_processor`;
CREATE TABLE `glpi_device_processor` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `frequence` int(11) NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `designation` (`designation`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_device_ram

DROP TABLE IF EXISTS `glpi_device_ram`;
CREATE TABLE `glpi_device_ram` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `frequence` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  `type` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `designation` (`designation`),
  KEY `type` (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_device_sndcard

DROP TABLE IF EXISTS `glpi_device_sndcard`;
CREATE TABLE `glpi_device_sndcard` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `type` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `designation` (`designation`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_display

DROP TABLE IF EXISTS `glpi_display`;
CREATE TABLE `glpi_display` (
  `ID` int(11) NOT NULL auto_increment,
  `type` smallint(6) NOT NULL default '0',
  `num` smallint(6) NOT NULL default '0',
  `rank` smallint(6) NOT NULL default '0',
  `FK_users` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `type_2` (`type`,`num`,`FK_users`),
  KEY `type` (`type`),
  KEY `rank` (`rank`),
  KEY `num` (`num`),
  KEY `FK_users` (`FK_users`)
) ENGINE=MyISAM AUTO_INCREMENT=122 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO glpi_display VALUES ('32','1','4','4','0');
INSERT INTO glpi_display VALUES ('34','1','6','6','0');
INSERT INTO glpi_display VALUES ('33','1','5','5','0');
INSERT INTO glpi_display VALUES ('31','1','8','3','0');
INSERT INTO glpi_display VALUES ('30','1','23','2','0');
INSERT INTO glpi_display VALUES ('86','12','3','1','0');
INSERT INTO glpi_display VALUES ('49','4','31','1','0');
INSERT INTO glpi_display VALUES ('50','4','23','2','0');
INSERT INTO glpi_display VALUES ('51','4','3','3','0');
INSERT INTO glpi_display VALUES ('52','4','4','4','0');
INSERT INTO glpi_display VALUES ('44','3','31','1','0');
INSERT INTO glpi_display VALUES ('38','2','31','1','0');
INSERT INTO glpi_display VALUES ('39','2','23','2','0');
INSERT INTO glpi_display VALUES ('45','3','23','2','0');
INSERT INTO glpi_display VALUES ('46','3','3','3','0');
INSERT INTO glpi_display VALUES ('63','6','4','3','0');
INSERT INTO glpi_display VALUES ('62','6','5','2','0');
INSERT INTO glpi_display VALUES ('61','6','23','1','0');
INSERT INTO glpi_display VALUES ('83','11','4','2','0');
INSERT INTO glpi_display VALUES ('82','11','3','1','0');
INSERT INTO glpi_display VALUES ('57','5','3','3','0');
INSERT INTO glpi_display VALUES ('56','5','23','2','0');
INSERT INTO glpi_display VALUES ('55','5','31','1','0');
INSERT INTO glpi_display VALUES ('29','1','31','1','0');
INSERT INTO glpi_display VALUES ('35','1','3','7','0');
INSERT INTO glpi_display VALUES ('36','1','19','8','0');
INSERT INTO glpi_display VALUES ('37','1','17','9','0');
INSERT INTO glpi_display VALUES ('40','2','3','3','0');
INSERT INTO glpi_display VALUES ('41','2','4','4','0');
INSERT INTO glpi_display VALUES ('42','2','11','6','0');
INSERT INTO glpi_display VALUES ('43','2','9','7','0');
INSERT INTO glpi_display VALUES ('47','3','4','4','0');
INSERT INTO glpi_display VALUES ('48','3','9','6','0');
INSERT INTO glpi_display VALUES ('53','4','9','6','0');
INSERT INTO glpi_display VALUES ('54','4','7','7','0');
INSERT INTO glpi_display VALUES ('58','5','4','4','0');
INSERT INTO glpi_display VALUES ('59','5','9','6','0');
INSERT INTO glpi_display VALUES ('60','5','7','7','0');
INSERT INTO glpi_display VALUES ('64','7','3','1','0');
INSERT INTO glpi_display VALUES ('65','7','4','2','0');
INSERT INTO glpi_display VALUES ('66','7','5','3','0');
INSERT INTO glpi_display VALUES ('67','7','6','4','0');
INSERT INTO glpi_display VALUES ('68','7','9','5','0');
INSERT INTO glpi_display VALUES ('69','8','9','1','0');
INSERT INTO glpi_display VALUES ('70','8','3','2','0');
INSERT INTO glpi_display VALUES ('71','8','4','3','0');
INSERT INTO glpi_display VALUES ('72','8','5','4','0');
INSERT INTO glpi_display VALUES ('73','8','10','5','0');
INSERT INTO glpi_display VALUES ('74','8','6','6','0');
INSERT INTO glpi_display VALUES ('75','10','4','1','0');
INSERT INTO glpi_display VALUES ('76','10','3','2','0');
INSERT INTO glpi_display VALUES ('77','10','5','3','0');
INSERT INTO glpi_display VALUES ('78','10','6','4','0');
INSERT INTO glpi_display VALUES ('79','10','7','5','0');
INSERT INTO glpi_display VALUES ('80','10','11','6','0');
INSERT INTO glpi_display VALUES ('84','11','5','3','0');
INSERT INTO glpi_display VALUES ('85','11','6','4','0');
INSERT INTO glpi_display VALUES ('88','12','6','2','0');
INSERT INTO glpi_display VALUES ('89','12','4','3','0');
INSERT INTO glpi_display VALUES ('90','12','5','4','0');
INSERT INTO glpi_display VALUES ('91','13','3','1','0');
INSERT INTO glpi_display VALUES ('92','13','4','2','0');
INSERT INTO glpi_display VALUES ('93','13','7','3','0');
INSERT INTO glpi_display VALUES ('94','13','5','4','0');
INSERT INTO glpi_display VALUES ('95','13','6','5','0');
INSERT INTO glpi_display VALUES ('96','15','3','1','0');
INSERT INTO glpi_display VALUES ('98','15','5','3','0');
INSERT INTO glpi_display VALUES ('99','15','6','4','0');
INSERT INTO glpi_display VALUES ('100','15','7','5','0');
INSERT INTO glpi_display VALUES ('101','17','3','1','0');
INSERT INTO glpi_display VALUES ('102','17','4','2','0');
INSERT INTO glpi_display VALUES ('103','17','5','3','0');
INSERT INTO glpi_display VALUES ('104','17','6','4','0');
INSERT INTO glpi_display VALUES ('105','2','40','5','0');
INSERT INTO glpi_display VALUES ('106','3','40','5','0');
INSERT INTO glpi_display VALUES ('107','4','40','5','0');
INSERT INTO glpi_display VALUES ('108','5','40','5','0');
INSERT INTO glpi_display VALUES ('109','15','8','6','0');
INSERT INTO glpi_display VALUES ('110','23','31','1','0');
INSERT INTO glpi_display VALUES ('111','23','23','2','0');
INSERT INTO glpi_display VALUES ('112','23','3','3','0');
INSERT INTO glpi_display VALUES ('113','23','4','4','0');
INSERT INTO glpi_display VALUES ('114','23','40','5','0');
INSERT INTO glpi_display VALUES ('115','23','9','6','0');
INSERT INTO glpi_display VALUES ('116','23','7','7','0');
INSERT INTO glpi_display VALUES ('117','27','16','1','0');
INSERT INTO glpi_display VALUES ('118','22','31','1','0');
INSERT INTO glpi_display VALUES ('119','29','4','1','0');
INSERT INTO glpi_display VALUES ('120','29','3','2','0');
INSERT INTO glpi_display VALUES ('121','35','80','1','0');

### Dump table glpi_doc_device

DROP TABLE IF EXISTS `glpi_doc_device`;
CREATE TABLE `glpi_doc_device` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_doc` int(11) NOT NULL default '0',
  `FK_device` int(11) NOT NULL default '0',
  `device_type` smallint(6) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `FK_doc` (`FK_doc`,`FK_device`,`device_type`),
  KEY `FK_doc_2` (`FK_doc`),
  KEY `FK_device` (`FK_device`,`device_type`),
  KEY `device_type` (`device_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_docs

DROP TABLE IF EXISTS `glpi_docs`;
CREATE TABLE `glpi_docs` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_entities` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `filename` varchar(255) collate utf8_unicode_ci default NULL,
  `rubrique` int(11) NOT NULL default '0',
  `mime` varchar(255) collate utf8_unicode_ci default NULL,
  `date_mod` datetime NOT NULL default '0000-00-00 00:00:00',
  `comments` text collate utf8_unicode_ci,
  `deleted` smallint(6) NOT NULL default '0',
  `link` varchar(255) collate utf8_unicode_ci default NULL,
  `notes` longtext collate utf8_unicode_ci,
  `FK_users` int(11) NOT NULL default '0',
  `FK_tracking` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY `rubrique` (`rubrique`),
  KEY `date_mod` (`date_mod`),
  KEY `name` (`name`),
  KEY `FK_users` (`FK_users`),
  KEY `FK_tracking` (`FK_tracking`),
  KEY `FK_entities` (`FK_entities`),
  KEY `deleted` (`deleted`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_dropdown_auto_update

DROP TABLE IF EXISTS `glpi_dropdown_auto_update`;
CREATE TABLE `glpi_dropdown_auto_update` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_dropdown_budget

DROP TABLE IF EXISTS `glpi_dropdown_budget`;
CREATE TABLE `glpi_dropdown_budget` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_dropdown_cartridge_type

DROP TABLE IF EXISTS `glpi_dropdown_cartridge_type`;
CREATE TABLE `glpi_dropdown_cartridge_type` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_dropdown_case_type

DROP TABLE IF EXISTS `glpi_dropdown_case_type`;
CREATE TABLE `glpi_dropdown_case_type` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO glpi_dropdown_case_type VALUES ('1','Grand',NULL);
INSERT INTO glpi_dropdown_case_type VALUES ('2','Moyen',NULL);
INSERT INTO glpi_dropdown_case_type VALUES ('3','Micro',NULL);

### Dump table glpi_dropdown_consumable_type

DROP TABLE IF EXISTS `glpi_dropdown_consumable_type`;
CREATE TABLE `glpi_dropdown_consumable_type` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_dropdown_contact_type

DROP TABLE IF EXISTS `glpi_dropdown_contact_type`;
CREATE TABLE `glpi_dropdown_contact_type` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_dropdown_contract_type

DROP TABLE IF EXISTS `glpi_dropdown_contract_type`;
CREATE TABLE `glpi_dropdown_contract_type` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

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
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_dropdown_enttype

DROP TABLE IF EXISTS `glpi_dropdown_enttype`;
CREATE TABLE `glpi_dropdown_enttype` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_dropdown_firmware

DROP TABLE IF EXISTS `glpi_dropdown_firmware`;
CREATE TABLE `glpi_dropdown_firmware` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_dropdown_iface

DROP TABLE IF EXISTS `glpi_dropdown_iface`;
CREATE TABLE `glpi_dropdown_iface` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_dropdown_interface

DROP TABLE IF EXISTS `glpi_dropdown_interface`;
CREATE TABLE `glpi_dropdown_interface` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO glpi_dropdown_interface VALUES ('1','IDE',NULL);
INSERT INTO glpi_dropdown_interface VALUES ('2','SATA',NULL);
INSERT INTO glpi_dropdown_interface VALUES ('3','SCSI',NULL);
INSERT INTO glpi_dropdown_interface VALUES ('4','USB',NULL);

### Dump table glpi_dropdown_kbcategories

DROP TABLE IF EXISTS `glpi_dropdown_kbcategories`;
CREATE TABLE `glpi_dropdown_kbcategories` (
  `ID` int(11) NOT NULL auto_increment,
  `parentID` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `completename` text collate utf8_unicode_ci,
  `comments` text collate utf8_unicode_ci,
  `level` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `parentID_2` (`parentID`,`name`),
  KEY `parentID` (`parentID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_dropdown_locations

DROP TABLE IF EXISTS `glpi_dropdown_locations`;
CREATE TABLE `glpi_dropdown_locations` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_entities` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `parentID` int(11) NOT NULL default '0',
  `completename` text collate utf8_unicode_ci,
  `comments` text collate utf8_unicode_ci,
  `level` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `name` (`name`,`parentID`,`FK_entities`),
  KEY `parentID` (`parentID`),
  KEY `FK_entities` (`FK_entities`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_dropdown_manufacturer

DROP TABLE IF EXISTS `glpi_dropdown_manufacturer`;
CREATE TABLE `glpi_dropdown_manufacturer` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_dropdown_model

DROP TABLE IF EXISTS `glpi_dropdown_model`;
CREATE TABLE `glpi_dropdown_model` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_dropdown_model_monitors

DROP TABLE IF EXISTS `glpi_dropdown_model_monitors`;
CREATE TABLE `glpi_dropdown_model_monitors` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_dropdown_model_networking

DROP TABLE IF EXISTS `glpi_dropdown_model_networking`;
CREATE TABLE `glpi_dropdown_model_networking` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_dropdown_model_peripherals

DROP TABLE IF EXISTS `glpi_dropdown_model_peripherals`;
CREATE TABLE `glpi_dropdown_model_peripherals` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_dropdown_model_phones

DROP TABLE IF EXISTS `glpi_dropdown_model_phones`;
CREATE TABLE `glpi_dropdown_model_phones` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_dropdown_model_printers

DROP TABLE IF EXISTS `glpi_dropdown_model_printers`;
CREATE TABLE `glpi_dropdown_model_printers` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_dropdown_netpoint

DROP TABLE IF EXISTS `glpi_dropdown_netpoint`;
CREATE TABLE `glpi_dropdown_netpoint` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_entities` int(11) NOT NULL default '0',
  `location` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `location` (`location`),
  KEY `name` (`name`),
  KEY `FK_entities` (`FK_entities`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_dropdown_network

DROP TABLE IF EXISTS `glpi_dropdown_network`;
CREATE TABLE `glpi_dropdown_network` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_dropdown_os

DROP TABLE IF EXISTS `glpi_dropdown_os`;
CREATE TABLE `glpi_dropdown_os` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_dropdown_os_sp

DROP TABLE IF EXISTS `glpi_dropdown_os_sp`;
CREATE TABLE `glpi_dropdown_os_sp` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_dropdown_os_version

DROP TABLE IF EXISTS `glpi_dropdown_os_version`;
CREATE TABLE `glpi_dropdown_os_version` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_dropdown_phone_power

DROP TABLE IF EXISTS `glpi_dropdown_phone_power`;
CREATE TABLE `glpi_dropdown_phone_power` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_dropdown_ram_type

DROP TABLE IF EXISTS `glpi_dropdown_ram_type`;
CREATE TABLE `glpi_dropdown_ram_type` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO glpi_dropdown_ram_type VALUES ('1','EDO',NULL);
INSERT INTO glpi_dropdown_ram_type VALUES ('2','DDR',NULL);
INSERT INTO glpi_dropdown_ram_type VALUES ('3','SDRAM',NULL);
INSERT INTO glpi_dropdown_ram_type VALUES ('4','SDRAM-2',NULL);

### Dump table glpi_dropdown_rubdocs

DROP TABLE IF EXISTS `glpi_dropdown_rubdocs`;
CREATE TABLE `glpi_dropdown_rubdocs` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_dropdown_software_category

DROP TABLE IF EXISTS `glpi_dropdown_software_category`;
CREATE TABLE `glpi_dropdown_software_category` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_dropdown_state

DROP TABLE IF EXISTS `glpi_dropdown_state`;
CREATE TABLE `glpi_dropdown_state` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_dropdown_tracking_category

DROP TABLE IF EXISTS `glpi_dropdown_tracking_category`;
CREATE TABLE `glpi_dropdown_tracking_category` (
  `ID` int(11) NOT NULL auto_increment,
  `parentID` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `completename` text collate utf8_unicode_ci,
  `comments` text collate utf8_unicode_ci,
  `level` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`),
  KEY `parentID` (`parentID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_dropdown_vlan

DROP TABLE IF EXISTS `glpi_dropdown_vlan`;
CREATE TABLE `glpi_dropdown_vlan` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_enterprises

DROP TABLE IF EXISTS `glpi_enterprises`;
CREATE TABLE `glpi_enterprises` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_entities` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `type` int(11) NOT NULL default '0',
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
  KEY `FK_entities` (`FK_entities`),
  KEY `deleted` (`deleted`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_entities

DROP TABLE IF EXISTS `glpi_entities`;
CREATE TABLE `glpi_entities` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `parentID` int(11) NOT NULL default '0',
  `completename` text collate utf8_unicode_ci,
  `comments` text collate utf8_unicode_ci,
  `level` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `name` (`name`,`parentID`),
  KEY `parentID` (`parentID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_entities_data

DROP TABLE IF EXISTS `glpi_entities_data`;
CREATE TABLE `glpi_entities_data` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_entities` int(11) NOT NULL default '0',
  `address` text collate utf8_unicode_ci,
  `postcode` varchar(255) collate utf8_unicode_ci default NULL,
  `town` varchar(255) collate utf8_unicode_ci default NULL,
  `state` varchar(255) collate utf8_unicode_ci default NULL,
  `country` varchar(255) collate utf8_unicode_ci default NULL,
  `website` varchar(255) collate utf8_unicode_ci default NULL,
  `phonenumber` varchar(255) collate utf8_unicode_ci default NULL,
  `fax` varchar(255) collate utf8_unicode_ci default NULL,
  `email` varchar(255) collate utf8_unicode_ci default NULL,
  `notes` longtext collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `FK_entities` (`FK_entities`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_event_log

DROP TABLE IF EXISTS `glpi_event_log`;
CREATE TABLE `glpi_event_log` (
  `ID` int(11) NOT NULL auto_increment,
  `item` int(11) NOT NULL default '0',
  `itemtype` varchar(255) collate utf8_unicode_ci default NULL,
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `service` varchar(255) collate utf8_unicode_ci default NULL,
  `level` smallint(6) NOT NULL default '0',
  `message` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `comp` (`item`),
  KEY `date` (`date`),
  KEY `itemtype` (`itemtype`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO glpi_event_log VALUES ('1','-1','system','2007-09-29 15:51:43','login','3','glpi connexion de l\'IP : 127.0.0.1');

### Dump table glpi_followups

DROP TABLE IF EXISTS `glpi_followups`;
CREATE TABLE `glpi_followups` (
  `ID` int(11) NOT NULL auto_increment,
  `tracking` int(11) default NULL,
  `date` datetime default NULL,
  `author` int(11) NOT NULL default '0',
  `contents` text collate utf8_unicode_ci,
  `private` int(1) NOT NULL default '0',
  `realtime` float NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY `tracking` (`tracking`),
  KEY `author` (`author`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_groups

DROP TABLE IF EXISTS `glpi_groups`;
CREATE TABLE `glpi_groups` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_entities` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  `ldap_field` varchar(255) collate utf8_unicode_ci default NULL,
  `ldap_value` varchar(255) collate utf8_unicode_ci default NULL,
  `ldap_group_dn` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`),
  KEY `ldap_field` (`ldap_field`),
  KEY `FK_entities` (`FK_entities`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_history

DROP TABLE IF EXISTS `glpi_history`;
CREATE TABLE `glpi_history` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_glpi_device` int(11) NOT NULL default '0',
  `device_type` smallint(6) NOT NULL default '0',
  `device_internal_type` int(11) default '0',
  `linked_action` smallint(6) NOT NULL default '0',
  `user_name` varchar(255) collate utf8_unicode_ci default NULL,
  `date_mod` datetime default NULL,
  `id_search_option` int(11) NOT NULL default '0',
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `FK_glpi_device` (`FK_glpi_device`),
  KEY `device_type` (`device_type`),
  KEY `device_internal_type` (`device_internal_type`),
  KEY `date_mod` (`date_mod`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_infocoms

DROP TABLE IF EXISTS `glpi_infocoms`;
CREATE TABLE `glpi_infocoms` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_device` int(11) NOT NULL default '0',
  `device_type` smallint(6) NOT NULL default '0',
  `buy_date` date NOT NULL default '0000-00-00',
  `use_date` date NOT NULL default '0000-00-00',
  `warranty_duration` smallint(6) NOT NULL default '0',
  `warranty_info` varchar(255) collate utf8_unicode_ci default NULL,
  `FK_enterprise` int(11) NOT NULL default '0',
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
  `budget` int(11) NOT NULL default '0',
  `alert` smallint(6) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `FK_device` (`FK_device`,`device_type`),
  KEY `FK_enterprise` (`FK_enterprise`),
  KEY `buy_date` (`buy_date`),
  KEY `budget` (`budget`),
  KEY `alert` (`alert`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_inst_software

DROP TABLE IF EXISTS `glpi_inst_software`;
CREATE TABLE `glpi_inst_software` (
  `ID` int(11) NOT NULL auto_increment,
  `cID` int(11) NOT NULL default '0',
  `license` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY `cID` (`cID`),
  KEY `sID` (`license`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_kbitems

DROP TABLE IF EXISTS `glpi_kbitems`;
CREATE TABLE `glpi_kbitems` (
  `ID` int(11) NOT NULL auto_increment,
  `categoryID` int(11) NOT NULL default '0',
  `question` text collate utf8_unicode_ci,
  `answer` text collate utf8_unicode_ci,
  `faq` smallint(6) NOT NULL default '0',
  `author` int(11) NOT NULL default '0',
  `view` int(11) NOT NULL default '0',
  `date` datetime default NULL,
  `date_mod` datetime default NULL,
  PRIMARY KEY  (`ID`),
  KEY `categoryID` (`categoryID`),
  KEY `author` (`author`),
  KEY `faq` (`faq`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_licenses

DROP TABLE IF EXISTS `glpi_licenses`;
CREATE TABLE `glpi_licenses` (
  `ID` int(15) NOT NULL auto_increment,
  `sID` int(15) NOT NULL default '0',
  `version` varchar(255) collate utf8_unicode_ci default NULL,
  `serial` varchar(255) collate utf8_unicode_ci default NULL,
  `expire` date default NULL,
  `oem` smallint(6) NOT NULL default '0',
  `oem_computer` int(11) NOT NULL default '0',
  `buy` smallint(6) NOT NULL default '1',
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `sID` (`sID`),
  KEY `oem_computer` (`oem_computer`),
  KEY `serial` (`serial`),
  KEY `expire` (`expire`),
  KEY `oem` (`oem`),
  KEY `buy` (`buy`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_links

DROP TABLE IF EXISTS `glpi_links`;
CREATE TABLE `glpi_links` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `link` varchar(255) collate utf8_unicode_ci default NULL,
  `data` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_links_device

DROP TABLE IF EXISTS `glpi_links_device`;
CREATE TABLE `glpi_links_device` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_links` int(11) NOT NULL default '0',
  `device_type` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `device_type_2` (`device_type`,`FK_links`),
  KEY `device_type` (`device_type`),
  KEY `FK_links` (`FK_links`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_mailgate

DROP TABLE IF EXISTS `glpi_mailgate`;
CREATE TABLE `glpi_mailgate` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `FK_entities` int(11) NOT NULL default '0',
  `host` varchar(255) collate utf8_unicode_ci default NULL,
  `login` varchar(255) collate utf8_unicode_ci default NULL,
  `password` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_mailing

DROP TABLE IF EXISTS `glpi_mailing`;
CREATE TABLE `glpi_mailing` (
  `ID` int(11) NOT NULL auto_increment,
  `type` varchar(255) collate utf8_unicode_ci default NULL,
  `FK_item` int(11) NOT NULL default '0',
  `item_type` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `mailings` (`type`,`FK_item`,`item_type`),
  KEY `type` (`type`),
  KEY `FK_item` (`FK_item`),
  KEY `item_type` (`item_type`),
  KEY `items` (`item_type`,`FK_item`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO glpi_mailing VALUES ('1','resa','3','1');
INSERT INTO glpi_mailing VALUES ('2','resa','1','1');
INSERT INTO glpi_mailing VALUES ('3','new','3','2');
INSERT INTO glpi_mailing VALUES ('4','new','1','1');
INSERT INTO glpi_mailing VALUES ('5','update','1','1');
INSERT INTO glpi_mailing VALUES ('6','followup','1','1');
INSERT INTO glpi_mailing VALUES ('7','finish','1','1');
INSERT INTO glpi_mailing VALUES ('8','update','2','1');
INSERT INTO glpi_mailing VALUES ('9','update','4','1');
INSERT INTO glpi_mailing VALUES ('10','new','3','1');
INSERT INTO glpi_mailing VALUES ('11','update','3','1');
INSERT INTO glpi_mailing VALUES ('12','followup','3','1');
INSERT INTO glpi_mailing VALUES ('13','finish','3','1');

### Dump table glpi_monitors

DROP TABLE IF EXISTS `glpi_monitors`;
CREATE TABLE `glpi_monitors` (
  `ID` int(10) NOT NULL auto_increment,
  `FK_entities` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `date_mod` datetime NOT NULL default '0000-00-00 00:00:00',
  `contact` varchar(255) collate utf8_unicode_ci default NULL,
  `contact_num` varchar(255) collate utf8_unicode_ci default NULL,
  `tech_num` int(11) NOT NULL default '0',
  `comments` text collate utf8_unicode_ci,
  `serial` varchar(255) collate utf8_unicode_ci default NULL,
  `otherserial` varchar(255) collate utf8_unicode_ci default NULL,
  `size` int(3) NOT NULL default '0',
  `flags_micro` smallint(6) NOT NULL default '0',
  `flags_speaker` smallint(6) NOT NULL default '0',
  `flags_subd` smallint(6) NOT NULL default '0',
  `flags_bnc` smallint(6) NOT NULL default '0',
  `flags_dvi` smallint(6) NOT NULL default '0',
  `location` int(11) NOT NULL default '0',
  `type` int(11) NOT NULL default '0',
  `model` int(11) NOT NULL default '0',
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `is_global` smallint(6) NOT NULL default '0',
  `deleted` smallint(6) NOT NULL default '0',
  `is_template` smallint(6) NOT NULL default '0',
  `tplname` varchar(255) collate utf8_unicode_ci default NULL,
  `notes` longtext collate utf8_unicode_ci,
  `FK_users` int(11) NOT NULL default '0',
  `FK_groups` int(11) NOT NULL default '0',
  `state` int(11) NOT NULL default '0',
  `ticket_tco` decimal(20,4) default '0.0000',
  PRIMARY KEY  (`ID`),
  KEY `ID` (`ID`),
  KEY `location` (`location`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `tech_num` (`tech_num`),
  KEY `name` (`name`),
  KEY `type` (`type`),
  KEY `model` (`model`),
  KEY `FK_groups` (`FK_groups`),
  KEY `FK_users` (`FK_users`),
  KEY `FK_entities` (`FK_entities`),
  KEY `is_template` (`is_template`),
  KEY `is_global` (`is_global`),
  KEY `deleted` (`deleted`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_networking

DROP TABLE IF EXISTS `glpi_networking`;
CREATE TABLE `glpi_networking` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_entities` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `ram` varchar(255) collate utf8_unicode_ci default NULL,
  `serial` varchar(255) collate utf8_unicode_ci default NULL,
  `otherserial` varchar(255) collate utf8_unicode_ci default NULL,
  `contact` varchar(255) collate utf8_unicode_ci default NULL,
  `contact_num` varchar(255) collate utf8_unicode_ci default NULL,
  `tech_num` int(11) NOT NULL default '0',
  `date_mod` datetime NOT NULL default '0000-00-00 00:00:00',
  `comments` text collate utf8_unicode_ci,
  `location` int(11) NOT NULL default '0',
  `domain` int(11) NOT NULL default '0',
  `network` int(11) NOT NULL default '0',
  `type` int(11) NOT NULL default '0',
  `model` int(11) NOT NULL default '0',
  `firmware` int(11) NOT NULL default '0',
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `deleted` smallint(6) NOT NULL default '0',
  `is_template` smallint(6) NOT NULL default '0',
  `tplname` varchar(255) collate utf8_unicode_ci default NULL,
  `ifmac` varchar(255) collate utf8_unicode_ci default NULL,
  `ifaddr` varchar(255) collate utf8_unicode_ci default NULL,
  `notes` longtext collate utf8_unicode_ci,
  `FK_users` int(11) NOT NULL default '0',
  `FK_groups` int(11) NOT NULL default '0',
  `state` int(11) NOT NULL default '0',
  `ticket_tco` decimal(20,4) default '0.0000',
  PRIMARY KEY  (`ID`),
  KEY `location` (`location`),
  KEY `firmware` (`firmware`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `tech_num` (`tech_num`),
  KEY `name` (`name`),
  KEY `type` (`type`),
  KEY `model` (`model`),
  KEY `FK_groups` (`FK_groups`),
  KEY `FK_users` (`FK_users`),
  KEY `network` (`network`),
  KEY `domain` (`domain`),
  KEY `FK_entities` (`FK_entities`),
  KEY `is_template` (`is_template`),
  KEY `deleted` (`deleted`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_networking_ports

DROP TABLE IF EXISTS `glpi_networking_ports`;
CREATE TABLE `glpi_networking_ports` (
  `ID` int(11) NOT NULL auto_increment,
  `on_device` int(11) NOT NULL default '0',
  `device_type` smallint(6) NOT NULL default '0',
  `logical_number` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `ifaddr` varchar(255) collate utf8_unicode_ci default NULL,
  `ifmac` varchar(255) collate utf8_unicode_ci default NULL,
  `iface` int(11) NOT NULL default '0',
  `netpoint` int(11) NOT NULL default '0',
  `netmask` varchar(255) collate utf8_unicode_ci default NULL,
  `gateway` varchar(255) collate utf8_unicode_ci default NULL,
  `subnet` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `on_device` (`on_device`,`device_type`),
  KEY `netpoint` (`netpoint`),
  KEY `on_device_2` (`on_device`),
  KEY `device_type` (`device_type`),
  KEY `iface` (`iface`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_networking_vlan

DROP TABLE IF EXISTS `glpi_networking_vlan`;
CREATE TABLE `glpi_networking_vlan` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_port` int(11) NOT NULL default '0',
  `FK_vlan` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `FK_port_2` (`FK_port`,`FK_vlan`),
  KEY `FK_port` (`FK_port`),
  KEY `FK_vlan` (`FK_vlan`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_networking_wire

DROP TABLE IF EXISTS `glpi_networking_wire`;
CREATE TABLE `glpi_networking_wire` (
  `ID` int(11) NOT NULL auto_increment,
  `end1` int(11) NOT NULL default '0',
  `end2` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `end1_1` (`end1`,`end2`),
  KEY `end1` (`end1`),
  KEY `end2` (`end2`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_ocs_admin_link

DROP TABLE IF EXISTS `glpi_ocs_admin_link`;
CREATE TABLE `glpi_ocs_admin_link` (
  `ID` int(10) unsigned NOT NULL auto_increment,
  `glpi_column` varchar(255) collate utf8_unicode_ci default NULL,
  `ocs_column` varchar(255) collate utf8_unicode_ci default NULL,
  `ocs_server_id` int(11) NOT NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_ocs_config

DROP TABLE IF EXISTS `glpi_ocs_config`;
CREATE TABLE `glpi_ocs_config` (
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
  `import_software_buy` int(11) NOT NULL default '1',
  `import_software_licensetype` varchar(255) collate utf8_unicode_ci default 'global',
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
  `import_monitor_comments` int(2) NOT NULL default '0',
  `import_software_comments` int(11) NOT NULL default '0',
  `default_state` int(11) NOT NULL default '0',
  `tag_limit` varchar(255) collate utf8_unicode_ci default NULL,
  `use_soft_dict` char(1) collate utf8_unicode_ci default '1',
  `cron_sync_number` int(11) default '1',
  `is_template` smallint(6) NOT NULL default '0',
  `tplname` varchar(255) collate utf8_unicode_ci default NULL,
  `date_mod` datetime default NULL,
  `deconnection_behavior` varchar(255) collate utf8_unicode_ci default NULL,
  `glpi_link_enabled` int(1) NOT NULL,
  `link_ip` int(1) NOT NULL,
  `link_name` int(1) NOT NULL,
  `link_mac_address` int(1) NOT NULL,
  `link_serial` int(1) NOT NULL,
  `link_if_status` int(11) NOT NULL default '0',
  `ocs_url` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `is_template` (`is_template`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO glpi_ocs_config VALUES ('1','localhost','ocs','ocs','localhost','ocsweb','0','0','0','0','1','global','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0',NULL,'0','0','0','0','','1','1','0',NULL,NULL,NULL,'0','0','0','0','0','0','');

### Dump table glpi_ocs_link

DROP TABLE IF EXISTS `glpi_ocs_link`;
CREATE TABLE `glpi_ocs_link` (
  `ID` int(11) NOT NULL auto_increment,
  `glpi_id` int(11) NOT NULL default '0',
  `ocs_id` int(11) NOT NULL default '0',
  `ocs_deviceid` varchar(255) collate utf8_unicode_ci default NULL,
  `auto_update` int(2) NOT NULL default '1',
  `last_update` datetime NOT NULL default '0000-00-00 00:00:00',
  `last_ocs_update` datetime default NULL,
  `computer_update` longtext collate utf8_unicode_ci,
  `import_device` longtext collate utf8_unicode_ci,
  `import_software` longtext collate utf8_unicode_ci,
  `import_monitor` longtext collate utf8_unicode_ci,
  `import_peripheral` longtext collate utf8_unicode_ci,
  `import_printers` longtext collate utf8_unicode_ci,
  `ocs_server_id` int(11) NOT NULL default '0',
  `import_ip` longtext collate utf8_unicode_ci,
  `ocs_agent_version` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `ocs_server_id` (`ocs_server_id`,`ocs_id`),
  KEY `glpi_id` (`glpi_id`),
  KEY `auto_update` (`auto_update`),
  KEY `last_update` (`last_update`),
  KEY `ocs_deviceid` (`ocs_deviceid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_peripherals

DROP TABLE IF EXISTS `glpi_peripherals`;
CREATE TABLE `glpi_peripherals` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_entities` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `date_mod` datetime NOT NULL default '0000-00-00 00:00:00',
  `contact` varchar(255) collate utf8_unicode_ci default NULL,
  `contact_num` varchar(255) collate utf8_unicode_ci default NULL,
  `tech_num` int(11) NOT NULL default '0',
  `comments` text collate utf8_unicode_ci,
  `serial` varchar(255) collate utf8_unicode_ci default NULL,
  `otherserial` varchar(255) collate utf8_unicode_ci default NULL,
  `location` int(11) NOT NULL default '0',
  `type` int(11) NOT NULL default '0',
  `model` int(11) NOT NULL default '0',
  `brand` varchar(255) collate utf8_unicode_ci default NULL,
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `is_global` smallint(6) NOT NULL default '0',
  `deleted` smallint(6) NOT NULL default '0',
  `is_template` smallint(6) NOT NULL default '0',
  `tplname` varchar(255) collate utf8_unicode_ci default NULL,
  `notes` longtext collate utf8_unicode_ci,
  `FK_users` int(11) NOT NULL default '0',
  `FK_groups` int(11) NOT NULL default '0',
  `state` int(11) NOT NULL default '0',
  `ticket_tco` decimal(20,4) default '0.0000',
  PRIMARY KEY  (`ID`),
  KEY `location` (`location`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `tech_num` (`tech_num`),
  KEY `name` (`name`),
  KEY `type` (`type`),
  KEY `model` (`model`),
  KEY `FK_groups` (`FK_groups`),
  KEY `FK_users` (`FK_users`),
  KEY `FK_entities` (`FK_entities`),
  KEY `is_template` (`is_template`),
  KEY `is_global` (`is_global`),
  KEY `deleted` (`deleted`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_phones

DROP TABLE IF EXISTS `glpi_phones`;
CREATE TABLE `glpi_phones` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_entities` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `date_mod` datetime NOT NULL default '0000-00-00 00:00:00',
  `contact` varchar(255) collate utf8_unicode_ci default NULL,
  `contact_num` varchar(255) collate utf8_unicode_ci default NULL,
  `tech_num` int(11) NOT NULL default '0',
  `comments` text collate utf8_unicode_ci,
  `serial` varchar(255) collate utf8_unicode_ci default NULL,
  `otherserial` varchar(255) collate utf8_unicode_ci default NULL,
  `firmware` varchar(255) collate utf8_unicode_ci default NULL,
  `location` int(11) NOT NULL default '0',
  `type` int(11) NOT NULL default '0',
  `model` int(11) NOT NULL default '0',
  `brand` varchar(255) collate utf8_unicode_ci default NULL,
  `power` int(11) NOT NULL default '0',
  `number_line` varchar(255) collate utf8_unicode_ci default NULL,
  `flags_casque` smallint(6) NOT NULL default '0',
  `flags_hp` smallint(6) NOT NULL default '0',
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `is_global` smallint(6) NOT NULL default '0',
  `deleted` smallint(6) NOT NULL default '0',
  `is_template` smallint(6) NOT NULL default '0',
  `tplname` varchar(255) collate utf8_unicode_ci default NULL,
  `notes` longtext collate utf8_unicode_ci,
  `FK_users` int(11) NOT NULL default '0',
  `FK_groups` int(11) NOT NULL default '0',
  `state` int(11) NOT NULL default '0',
  `ticket_tco` decimal(20,4) default '0.0000',
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`),
  KEY `location` (`location`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `tech_num` (`tech_num`),
  KEY `type` (`type`),
  KEY `model` (`model`),
  KEY `FK_groups` (`FK_groups`),
  KEY `FK_users` (`FK_users`),
  KEY `power` (`power`),
  KEY `FK_entities` (`FK_entities`),
  KEY `is_template` (`is_template`),
  KEY `is_global` (`is_global`),
  KEY `deleted` (`deleted`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_printers

DROP TABLE IF EXISTS `glpi_printers`;
CREATE TABLE `glpi_printers` (
  `ID` int(10) NOT NULL auto_increment,
  `FK_entities` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `date_mod` datetime NOT NULL default '0000-00-00 00:00:00',
  `contact` varchar(255) collate utf8_unicode_ci default NULL,
  `contact_num` varchar(255) collate utf8_unicode_ci default NULL,
  `tech_num` int(11) NOT NULL default '0',
  `serial` varchar(255) collate utf8_unicode_ci default NULL,
  `otherserial` varchar(255) collate utf8_unicode_ci default NULL,
  `flags_serial` smallint(6) NOT NULL default '0',
  `flags_par` smallint(6) NOT NULL default '0',
  `flags_usb` smallint(6) NOT NULL default '0',
  `comments` text collate utf8_unicode_ci,
  `ramSize` varchar(255) collate utf8_unicode_ci default NULL,
  `location` int(11) NOT NULL default '0',
  `domain` int(11) NOT NULL default '0',
  `network` int(11) NOT NULL default '0',
  `type` int(11) NOT NULL default '0',
  `model` int(11) NOT NULL default '0',
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `is_global` smallint(6) NOT NULL default '0',
  `deleted` smallint(6) NOT NULL default '0',
  `is_template` smallint(6) NOT NULL default '0',
  `tplname` varchar(255) collate utf8_unicode_ci default NULL,
  `initial_pages` varchar(255) collate utf8_unicode_ci default NULL,
  `notes` longtext collate utf8_unicode_ci,
  `FK_users` int(11) NOT NULL default '0',
  `FK_groups` int(11) NOT NULL default '0',
  `state` int(11) NOT NULL default '0',
  `ticket_tco` decimal(20,4) default '0.0000',
  PRIMARY KEY  (`ID`),
  KEY `id` (`ID`),
  KEY `location` (`location`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `tech_num` (`tech_num`),
  KEY `name` (`name`),
  KEY `type` (`type`),
  KEY `model` (`model`),
  KEY `FK_groups` (`FK_groups`),
  KEY `FK_users` (`FK_users`),
  KEY `network` (`network`),
  KEY `domain` (`domain`),
  KEY `FK_entities` (`FK_entities`),
  KEY `is_template` (`is_template`),
  KEY `is_global` (`is_global`),
  KEY `deleted` (`deleted`)
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
  `contract_infocom` char(1) collate utf8_unicode_ci default NULL,
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
  `group` char(1) collate utf8_unicode_ci default NULL,
  `entity` char(1) collate utf8_unicode_ci default NULL,
  `transfer` char(1) collate utf8_unicode_ci default NULL,
  `logs` char(1) collate utf8_unicode_ci default NULL,
  `reminder_public` char(1) collate utf8_unicode_ci default NULL,
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
  `show_all_planning` char(1) collate utf8_unicode_ci default NULL,
  `statistic` char(1) collate utf8_unicode_ci default NULL,
  `password_update` char(1) collate utf8_unicode_ci default NULL,
  `helpdesk_hardware` smallint(6) NOT NULL default '0',
  `helpdesk_hardware_type` int(11) NOT NULL default '0',
  `show_group_ticket` char(1) collate utf8_unicode_ci default NULL,
  `show_group_hardware` char(1) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `interface` (`interface`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO glpi_profiles VALUES ('1','post-only','helpdesk','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'r','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1',NULL,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1',NULL,NULL,NULL,NULL,'1','1','8388674','0','0');
INSERT INTO glpi_profiles VALUES ('2','normal','central','0','r','r','r','r','r','r','r','r','r','r','r','r','r','r','r','1','r','r',NULL,'r',NULL,NULL,NULL,NULL,'r','r',NULL,NULL,NULL,NULL,NULL,'w',NULL,'r',NULL,'r','r',NULL,NULL,NULL,NULL,NULL,'1','1','1','0','0','1','0','0','1','1','0','1','0','1','0','1','1','1','8388674','0','0');
INSERT INTO glpi_profiles VALUES ('3','admin','central','0','w','w','w','w','w','w','w','w','w','w','w','w','w','w','w','1','w','r','w','r','w','w','w','w','w','w',NULL,NULL,NULL,NULL,NULL,'w','w','r','r','w','w',NULL,NULL,NULL,NULL,NULL,'1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','3','8388674','0','0');
INSERT INTO glpi_profiles VALUES ('4','super-admin','central','0','w','w','w','w','w','w','w','w','w','w','w','w','w','w','w','1','w','r','w','r','w','w','w','w','w','w','w','w','w','w','w','w','w','r','w','w','w','w','w','r','w','w','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','3','8388674','0','0');

### Dump table glpi_registry

DROP TABLE IF EXISTS `glpi_registry`;
CREATE TABLE `glpi_registry` (
  `ID` int(10) NOT NULL auto_increment,
  `computer_id` int(10) NOT NULL default '0',
  `registry_hive` varchar(255) collate utf8_unicode_ci default NULL,
  `registry_path` varchar(255) collate utf8_unicode_ci default NULL,
  `registry_value` varchar(255) collate utf8_unicode_ci default NULL,
  `registry_ocs_name` char(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `computer_id` (`computer_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_reminder

DROP TABLE IF EXISTS `glpi_reminder`;
CREATE TABLE `glpi_reminder` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_entities` int(11) NOT NULL default '0',
  `date` datetime default NULL,
  `author` int(11) NOT NULL default '0',
  `title` varchar(255) collate utf8_unicode_ci default NULL,
  `text` text collate utf8_unicode_ci,
  `type` varchar(255) collate utf8_unicode_ci default 'private',
  `begin` datetime default NULL,
  `end` datetime default NULL,
  `rv` smallint(6) NOT NULL default '0',
  `date_mod` datetime default NULL,
  `state` smallint(6) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY `date` (`date`),
  KEY `author` (`author`),
  KEY `type` (`type`),
  KEY `begin` (`begin`),
  KEY `end` (`end`),
  KEY `FK_entities` (`FK_entities`),
  KEY `rv` (`rv`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_reservation_item

DROP TABLE IF EXISTS `glpi_reservation_item`;
CREATE TABLE `glpi_reservation_item` (
  `ID` int(11) NOT NULL auto_increment,
  `device_type` smallint(6) NOT NULL default '0',
  `id_device` int(11) NOT NULL default '0',
  `comments` text collate utf8_unicode_ci,
  `active` smallint(6) NOT NULL default '1',
  PRIMARY KEY  (`ID`),
  KEY `device_type` (`device_type`),
  KEY `device_type_2` (`device_type`,`id_device`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_reservation_resa

DROP TABLE IF EXISTS `glpi_reservation_resa`;
CREATE TABLE `glpi_reservation_resa` (
  `ID` bigint(20) NOT NULL auto_increment,
  `id_item` int(11) NOT NULL default '0',
  `begin` datetime NOT NULL default '0000-00-00 00:00:00',
  `end` datetime NOT NULL default '0000-00-00 00:00:00',
  `id_user` int(11) NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `id_item` (`id_item`),
  KEY `id_user` (`id_user`),
  KEY `begin` (`begin`),
  KEY `end` (`end`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rules_actions

DROP TABLE IF EXISTS `glpi_rules_actions`;
CREATE TABLE `glpi_rules_actions` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_rules` int(11) NOT NULL default '0',
  `action_type` varchar(255) collate utf8_unicode_ci default NULL,
  `field` varchar(255) collate utf8_unicode_ci default NULL,
  `value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `FK_rules` (`FK_rules`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO glpi_rules_actions VALUES ('1','1','assign','FK_entities','0');
INSERT INTO glpi_rules_actions VALUES ('2','2','assign','FK_entities','0');

### Dump table glpi_rules_criterias

DROP TABLE IF EXISTS `glpi_rules_criterias`;
CREATE TABLE `glpi_rules_criterias` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_rules` int(11) NOT NULL default '0',
  `criteria` varchar(255) collate utf8_unicode_ci default NULL,
  `condition` smallint(4) NOT NULL default '0',
  `pattern` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`),
  KEY `FK_rules` (`FK_rules`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO glpi_rules_criterias VALUES ('1','1','TAG','0','*');
INSERT INTO glpi_rules_criterias VALUES ('2','2','uid','0','*');
INSERT INTO glpi_rules_criterias VALUES ('3','2','samaccountname','0','*');
INSERT INTO glpi_rules_criterias VALUES ('4','2','MAIL_EMAIL','0','*');

### Dump table glpi_rules_descriptions

DROP TABLE IF EXISTS `glpi_rules_descriptions`;
CREATE TABLE `glpi_rules_descriptions` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_entities` int(11) NOT NULL default '-1',
  `rule_type` smallint(4) NOT NULL default '0',
  `ranking` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `description` text collate utf8_unicode_ci,
  `match` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO glpi_rules_descriptions VALUES ('1','-1','0','0','Root','','AND');
INSERT INTO glpi_rules_descriptions VALUES ('2','-1','1','1','Root','','OR');

### Dump table glpi_rules_ldap_parameters

DROP TABLE IF EXISTS `glpi_rules_ldap_parameters`;
CREATE TABLE `glpi_rules_ldap_parameters` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `value` varchar(255) collate utf8_unicode_ci default NULL,
  `rule_type` smallint(6) NOT NULL default '1',
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO glpi_rules_ldap_parameters VALUES ('1','(LDAP)Organization','o','1');
INSERT INTO glpi_rules_ldap_parameters VALUES ('2','(LDAP)Common Name','cn','1');
INSERT INTO glpi_rules_ldap_parameters VALUES ('3','(LDAP)Department Number','departmentnumber','1');
INSERT INTO glpi_rules_ldap_parameters VALUES ('4','(LDAP)Email','mail','1');
INSERT INTO glpi_rules_ldap_parameters VALUES ('5','Object Class','objectclass','1');
INSERT INTO glpi_rules_ldap_parameters VALUES ('6','(LDAP)User ID','uid','1');
INSERT INTO glpi_rules_ldap_parameters VALUES ('7','(LDAP)Telephone Number','phone','1');
INSERT INTO glpi_rules_ldap_parameters VALUES ('8','(LDAP)Employee Number','employeenumber','1');
INSERT INTO glpi_rules_ldap_parameters VALUES ('9','(LDAP)Manager','manager','1');
INSERT INTO glpi_rules_ldap_parameters VALUES ('10','(LDAP)DistinguishedName','dn','1');
INSERT INTO glpi_rules_ldap_parameters VALUES ('11','(AD)DistinguishedName','distinguishedname','1');
INSERT INTO glpi_rules_ldap_parameters VALUES ('12','(AD)User ID','samaccountname','1');

### Dump table glpi_software

DROP TABLE IF EXISTS `glpi_software`;
CREATE TABLE `glpi_software` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_entities` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  `location` int(11) NOT NULL default '0',
  `tech_num` int(11) NOT NULL default '0',
  `platform` int(11) NOT NULL default '0',
  `is_update` smallint(6) NOT NULL default '0',
  `update_software` int(11) NOT NULL default '-1',
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `deleted` smallint(6) NOT NULL default '0',
  `is_template` smallint(6) NOT NULL default '0',
  `tplname` varchar(255) collate utf8_unicode_ci default NULL,
  `date_mod` datetime default NULL,
  `notes` longtext collate utf8_unicode_ci,
  `FK_users` int(11) NOT NULL default '0',
  `FK_groups` int(11) NOT NULL default '0',
  `state` int(11) NOT NULL default '0',
  `ticket_tco` decimal(20,4) default '0.0000',
  `helpdesk_visible` int(11) NOT NULL default '1',
  `category` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY `platform` (`platform`),
  KEY `location` (`location`),
  KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
  KEY `date_mod` (`date_mod`),
  KEY `tech_num` (`tech_num`),
  KEY `name` (`name`),
  KEY `FK_groups` (`FK_groups`),
  KEY `FK_users` (`FK_users`),
  KEY `update_software` (`update_software`),
  KEY `FK_entities` (`FK_entities`),
  KEY `is_template` (`is_template`),
  KEY `is_update` (`is_update`),
  KEY `deleted` (`deleted`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_tracking

DROP TABLE IF EXISTS `glpi_tracking`;
CREATE TABLE `glpi_tracking` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_entities` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `date` datetime default NULL,
  `closedate` datetime NOT NULL default '0000-00-00 00:00:00',
  `status` varchar(255) collate utf8_unicode_ci default 'new',
  `author` int(11) NOT NULL default '0',
  `recipient` int(11) NOT NULL default '0',
  `FK_group` int(11) NOT NULL default '0',
  `request_type` smallint(6) NOT NULL default '0',
  `assign` int(11) NOT NULL default '0',
  `assign_ent` int(11) NOT NULL default '0',
  `assign_group` int(11) NOT NULL default '0',
  `device_type` int(11) NOT NULL default '1',
  `computer` int(11) NOT NULL default '0',
  `contents` text collate utf8_unicode_ci,
  `priority` smallint(6) NOT NULL default '1',
  `uemail` varchar(255) collate utf8_unicode_ci default NULL,
  `emailupdates` smallint(6) NOT NULL default '0',
  `realtime` float NOT NULL default '0',
  `category` int(11) NOT NULL default '0',
  `cost_time` decimal(20,4) NOT NULL default '0.0000',
  `cost_fixed` decimal(20,4) NOT NULL default '0.0000',
  `cost_material` decimal(20,4) NOT NULL default '0.0000',
  PRIMARY KEY  (`ID`),
  KEY `computer` (`computer`),
  KEY `author` (`author`),
  KEY `assign` (`assign`),
  KEY `date` (`date`),
  KEY `closedate` (`closedate`),
  KEY `status` (`status`),
  KEY `category` (`category`),
  KEY `FK_group` (`FK_group`),
  KEY `assign_ent` (`assign_ent`),
  KEY `device_type` (`device_type`),
  KEY `priority` (`priority`),
  KEY `request_type` (`request_type`),
  KEY `FK_entities` (`FK_entities`),
  KEY `recipient` (`recipient`),
  KEY `assign_group` (`assign_group`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_tracking_planning

DROP TABLE IF EXISTS `glpi_tracking_planning`;
CREATE TABLE `glpi_tracking_planning` (
  `ID` bigint(20) NOT NULL auto_increment,
  `id_followup` int(11) NOT NULL default '0',
  `id_assign` int(11) NOT NULL default '0',
  `begin` datetime NOT NULL default '0000-00-00 00:00:00',
  `end` datetime NOT NULL default '0000-00-00 00:00:00',
  `state` smallint(6) NOT NULL default '1',
  PRIMARY KEY  (`ID`),
  KEY `begin` (`begin`),
  KEY `end` (`end`),
  KEY `id_assign` (`id_assign`),
  KEY `id_followup` (`id_followup`)
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
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO glpi_transfers VALUES ('1','complete','2','2','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1');

### Dump table glpi_type_computers

DROP TABLE IF EXISTS `glpi_type_computers`;
CREATE TABLE `glpi_type_computers` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO glpi_type_computers VALUES ('1','Serveur',NULL);

### Dump table glpi_type_docs

DROP TABLE IF EXISTS `glpi_type_docs`;
CREATE TABLE `glpi_type_docs` (
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
) ENGINE=MyISAM AUTO_INCREMENT=68 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO glpi_type_docs VALUES ('1','JPEG','jpg','jpg-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('2','PNG','png','png-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('3','GIF','gif','gif-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('4','BMP','bmp','bmp-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('5','Photoshop','psd','psd-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('6','TIFF','tif','tif-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('7','AIFF','aiff','aiff-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('8','Windows Media','asf','asf-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('9','Windows Media','avi','avi-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('44','C source','c','','','1','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('27','RealAudio','rm','rm-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('16','Midi','mid','mid-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('17','QuickTime','mov','mov-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('18','MP3','mp3','mp3-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('19','MPEG','mpg','mpg-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('20','Ogg Vorbis','ogg','ogg-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('24','QuickTime','qt','qt-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('10','BZip','bz2','bz2-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('25','RealAudio','ra','ra-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('26','RealAudio','ram','ram-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('11','Word','doc','doc-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('12','DjVu','djvu','','','1','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('42','MNG','mng','','','1','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('13','PostScript','eps','ps-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('14','GZ','gz','gz-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('37','WAV','wav','wav-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('15','HTML','html','html-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('34','Flash','swf','','','1','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('21','PDF','pdf','pdf-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('22','PowerPoint','ppt','ppt-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('23','PostScript','ps','ps-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('40','Windows Media','wmv','','','1','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('28','RTF','rtf','rtf-dist.png','','1','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('29','StarOffice','sdd','sdd-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('30','StarOffice','sdw','sdw-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('31','Stuffit','sit','sit-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('43','Adobe Illustrator','ai','ai-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('32','OpenOffice Impress','sxi','sxi-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('33','OpenOffice','sxw','sxw-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('46','DVI','dvi','dvi-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('35','TGZ','tgz','tgz-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('36','texte','txt','txt-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('49','RedHat/Mandrake/SuSE','rpm','rpm-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('38','Excel','xls','xls-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('39','XML','xml','xml-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('41','Zip','zip','zip-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('45','Debian','deb','deb-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('47','C header','h','','','1','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('48','Pascal','pas','','','1','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('50','OpenOffice Calc','sxc','sxc-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('51','LaTeX','tex','tex-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('52','GIMP multi-layer','xcf','xcf-dist.png','','1','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('53','JPEG','jpeg','jpg-dist.png','','1','2005-03-07 22:23:17');
INSERT INTO glpi_type_docs VALUES ('54','Oasis Open Office Writer','odt','odt-dist.png','','1','2006-01-21 17:41:13');
INSERT INTO glpi_type_docs VALUES ('55','Oasis Open Office Calc','ods','ods-dist.png','','1','2006-01-21 17:41:31');
INSERT INTO glpi_type_docs VALUES ('56','Oasis Open Office Impress','odp','odp-dist.png','','1','2006-01-21 17:42:54');
INSERT INTO glpi_type_docs VALUES ('57','Oasis Open Office Impress Template','otp','odp-dist.png','','1','2006-01-21 17:43:58');
INSERT INTO glpi_type_docs VALUES ('58','Oasis Open Office Writer Template','ott','odt-dist.png','','1','2006-01-21 17:44:41');
INSERT INTO glpi_type_docs VALUES ('59','Oasis Open Office Calc Template','ots','ods-dist.png','','1','2006-01-21 17:45:30');
INSERT INTO glpi_type_docs VALUES ('60','Oasis Open Office Math','odf','odf-dist.png','','1','2006-01-21 17:48:05');
INSERT INTO glpi_type_docs VALUES ('61','Oasis Open Office Draw','odg','odg-dist.png','','1','2006-01-21 17:48:31');
INSERT INTO glpi_type_docs VALUES ('62','Oasis Open Office Draw Template','otg','odg-dist.png','','1','2006-01-21 17:49:46');
INSERT INTO glpi_type_docs VALUES ('63','Oasis Open Office Base','odb','odb-dist.png','','1','2006-01-21 18:03:34');
INSERT INTO glpi_type_docs VALUES ('64','Oasis Open Office HTML','oth','oth-dist.png','','1','2006-01-21 18:05:27');
INSERT INTO glpi_type_docs VALUES ('65','Oasis Open Office Writer Master','odm','odm-dist.png','','1','2006-01-21 18:06:34');
INSERT INTO glpi_type_docs VALUES ('66','Oasis Open Office Chart','odc','','','1','2006-01-21 18:07:48');
INSERT INTO glpi_type_docs VALUES ('67','Oasis Open Office Image','odi','','','1','2006-01-21 18:08:18');

### Dump table glpi_type_monitors

DROP TABLE IF EXISTS `glpi_type_monitors`;
CREATE TABLE `glpi_type_monitors` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_type_networking

DROP TABLE IF EXISTS `glpi_type_networking`;
CREATE TABLE `glpi_type_networking` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_type_peripherals

DROP TABLE IF EXISTS `glpi_type_peripherals`;
CREATE TABLE `glpi_type_peripherals` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_type_phones

DROP TABLE IF EXISTS `glpi_type_phones`;
CREATE TABLE `glpi_type_phones` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_type_printers

DROP TABLE IF EXISTS `glpi_type_printers`;
CREATE TABLE `glpi_type_printers` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comments` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ID`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


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
  `location` int(11) default NULL,
  `tracking_order` smallint(6) NOT NULL default '0',
  `language` varchar(255) collate utf8_unicode_ci default NULL,
  `list_limit` int(11) NOT NULL default '20',
  `active` int(2) NOT NULL default '1',
  `comments` text collate utf8_unicode_ci,
  `id_auth` int(11) NOT NULL default '-1',
  `auth_method` int(11) NOT NULL default '-1',
  `last_login` datetime NOT NULL default '0000-00-00 00:00:00',
  `date_mod` datetime NOT NULL default '0000-00-00 00:00:00',
  `deleted` smallint(6) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `name` (`name`),
  KEY `name_2` (`name`),
  KEY `location` (`location`),
  KEY `firstname` (`firstname`),
  KEY `realname` (`realname`),
  KEY `deleted` (`deleted`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO glpi_users VALUES ('1','Helpdesk','','','',NULL,'','','Helpdesk Injector',NULL,NULL,'0','fr_FR','20','0',NULL,'-1','-1','0000-00-00 00:00:00','0000-00-00 00:00:00','0');
INSERT INTO glpi_users VALUES ('2','glpi','','41ece51526515624ff89973668497d00','','','','','',NULL,'0','1','fr_FR','20','1',NULL,'-1','1','2007-09-29 15:51:43','2007-09-29 15:51:43','0');
INSERT INTO glpi_users VALUES ('3','post-only','*5683D7F638D6598D057638B1957F194E4CA974FB','3177926a7314de24680a9938aaa97703','','','','','',NULL,'0','0','en_GB','20','1',NULL,'-1','-1','0000-00-00 00:00:00','0000-00-00 00:00:00','0');
INSERT INTO glpi_users VALUES ('4','tech','*B09F1B2C210DEEA69C662977CC69C6C461965B09','d9f9133fb120cd6096870bc2b496805b','','','','','',NULL,'0','1','fr_FR','20','1',NULL,'-1','-1','0000-00-00 00:00:00','0000-00-00 00:00:00','0');
INSERT INTO glpi_users VALUES ('5','normal','*F3F91B23FC1DB728B49B1F22DEE3D7A839E10F0E','fea087517c26fadd409bd4b9dc642555','','','','','',NULL,'0','0','en_GB','20','1',NULL,'-1','-1','0000-00-00 00:00:00','0000-00-00 00:00:00','0');

### Dump table glpi_users_groups

DROP TABLE IF EXISTS `glpi_users_groups`;
CREATE TABLE `glpi_users_groups` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_users` int(11) NOT NULL default '0',
  `FK_groups` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `FK_users` (`FK_users`,`FK_groups`),
  KEY `FK_users_2` (`FK_users`),
  KEY `FK_groups` (`FK_groups`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_users_profiles

DROP TABLE IF EXISTS `glpi_users_profiles`;
CREATE TABLE `glpi_users_profiles` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_users` int(11) NOT NULL default '0',
  `FK_profiles` int(11) NOT NULL default '0',
  `FK_entities` int(11) NOT NULL default '0',
  `recursive` smallint(6) NOT NULL default '1',
  `dynamic` smallint(6) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY `FK_users` (`FK_users`),
  KEY `FK_profiles` (`FK_profiles`),
  KEY `FK_entities` (`FK_entities`),
  KEY `recursive` (`recursive`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO glpi_users_profiles VALUES ('1','1','1','0','1','0');
INSERT INTO glpi_users_profiles VALUES ('2','2','4','0','1','0');
INSERT INTO glpi_users_profiles VALUES ('3','3','1','0','1','0');
INSERT INTO glpi_users_profiles VALUES ('4','4','4','0','1','0');
INSERT INTO glpi_users_profiles VALUES ('5','5','2','0','1','0');
