#GLPI Dump database on 2009-10-19 19:05

### Dump table glpi_alerts

DROP TABLE IF EXISTS `glpi_alerts`;
CREATE TABLE `glpi_alerts` (
  `id` int(11) NOT NULL auto_increment,
  `itemtype` int(11) NOT NULL default '0',
  `items_id` int(11) NOT NULL default '0',
  `type` int(11) NOT NULL default '0' COMMENT 'see define.php ALERT_* constant',
  `date` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`itemtype`,`items_id`,`type`),
  KEY `type` (`type`),
  KEY `date` (`date`)
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
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_authldapsreplicates

DROP TABLE IF EXISTS `glpi_authldapsreplicates`;
CREATE TABLE `glpi_authldapsreplicates` (
  `id` int(11) NOT NULL auto_increment,
  `authldaps_id` int(11) NOT NULL default '0',
  `host` varchar(255) collate utf8_unicode_ci default NULL,
  `port` int(11) NOT NULL default '389',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `authldaps_id` (`authldaps_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_authmails

DROP TABLE IF EXISTS `glpi_authmails`;
CREATE TABLE `glpi_authmails` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `connect_string` varchar(255) collate utf8_unicode_ci default NULL,
  `host` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`)
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
  `itemtype` int(11) NOT NULL default '0',
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
  `itemtype` int(11) NOT NULL default '0',
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
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_template` (`is_template`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_cartridges

DROP TABLE IF EXISTS `glpi_cartridges`;
CREATE TABLE `glpi_cartridges` (
  `id` int(11) NOT NULL auto_increment,
  `cartridgesitems_id` int(11) NOT NULL default '0',
  `printers_id` int(11) NOT NULL default '0',
  `date_in` date default NULL,
  `date_use` date default NULL,
  `date_out` date default NULL,
  `pages` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `cartridgesitems_id` (`cartridgesitems_id`),
  KEY `printers_id` (`printers_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_cartridges_printersmodels

DROP TABLE IF EXISTS `glpi_cartridges_printersmodels`;
CREATE TABLE `glpi_cartridges_printersmodels` (
  `id` int(11) NOT NULL auto_increment,
  `cartridgesitems_id` int(11) NOT NULL default '0',
  `printersmodels_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`printersmodels_id`,`cartridgesitems_id`),
  KEY `cartridgesitems_id` (`cartridgesitems_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_cartridgesitems

DROP TABLE IF EXISTS `glpi_cartridgesitems`;
CREATE TABLE `glpi_cartridgesitems` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `ref` varchar(255) collate utf8_unicode_ci default NULL,
  `locations_id` int(11) NOT NULL default '0',
  `cartridgesitemstypes_id` int(11) NOT NULL default '0',
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
  KEY `cartridgesitemstypes_id` (`cartridgesitemstypes_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `alarm_threshold` (`alarm_threshold`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_cartridgesitemstypes

DROP TABLE IF EXISTS `glpi_cartridgesitemstypes`;
CREATE TABLE `glpi_cartridgesitemstypes` (
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
  `operatingsystemsversions_id` int(11) NOT NULL default '0',
  `operatingsystemsservicepacks_id` int(11) NOT NULL default '0',
  `os_license_number` varchar(255) collate utf8_unicode_ci default NULL,
  `os_licenseid` varchar(255) collate utf8_unicode_ci default NULL,
  `autoupdatesystems_id` int(11) NOT NULL default '0',
  `locations_id` int(11) NOT NULL default '0',
  `domains_id` int(11) NOT NULL default '0',
  `networks_id` int(11) NOT NULL default '0',
  `computersmodels_id` int(11) NOT NULL default '0',
  `computerstypes_id` int(11) NOT NULL default '0',
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
  KEY `computersmodels_id` (`computersmodels_id`),
  KEY `networks_id` (`networks_id`),
  KEY `operatingsystems_id` (`operatingsystems_id`),
  KEY `operatingsystemsservicepacks_id` (`operatingsystemsservicepacks_id`),
  KEY `operatingsystemsversions_id` (`operatingsystemsversions_id`),
  KEY `states_id` (`states_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `computerstypes_id` (`computerstypes_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_ocs_import` (`is_ocs_import`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_computers_devices

DROP TABLE IF EXISTS `glpi_computers_devices`;
CREATE TABLE `glpi_computers_devices` (
  `id` int(11) NOT NULL auto_increment,
  `specificity` varchar(255) collate utf8_unicode_ci default NULL,
  `devicetype` int(11) NOT NULL default '0',
  `devices_id` int(11) NOT NULL default '0',
  `computers_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `specificity` (`specificity`),
  KEY `device_type` (`devicetype`,`devices_id`),
  KEY `computers_id` (`computers_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_computers_items

DROP TABLE IF EXISTS `glpi_computers_items`;
CREATE TABLE `glpi_computers_items` (
  `id` int(11) NOT NULL auto_increment,
  `items_id` int(11) NOT NULL default '0' COMMENT 'RELATION to various table, according to itemtype (ID)',
  `computers_id` int(11) NOT NULL default '0',
  `itemtype` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`itemtype`,`items_id`,`computers_id`),
  KEY `computers_id` (`computers_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_computers_softwaresversions

DROP TABLE IF EXISTS `glpi_computers_softwaresversions`;
CREATE TABLE `glpi_computers_softwaresversions` (
  `id` int(11) NOT NULL auto_increment,
  `computers_id` int(11) NOT NULL default '0',
  `softwaresversions_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `computers_id` (`computers_id`),
  KEY `softwaresversions_id` (`softwaresversions_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_computersdisks

DROP TABLE IF EXISTS `glpi_computersdisks`;
CREATE TABLE `glpi_computersdisks` (
  `id` int(11) NOT NULL auto_increment,
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
  KEY `filesystems_id` (`filesystems_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_computersmodels

DROP TABLE IF EXISTS `glpi_computersmodels`;
CREATE TABLE `glpi_computersmodels` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_computerstypes

DROP TABLE IF EXISTS `glpi_computerstypes`;
CREATE TABLE `glpi_computerstypes` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_computerstypes` VALUES ('1','Serveur',NULL);

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
  `documentscategories_id_forticket` int(11) NOT NULL default '0' COMMENT 'default category for documents added with a ticket',
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
  `softwarescategories_id_ondelete` int(11) NOT NULL default '0' COMMENT 'category applyed when a software is deleted',
  `x509_email_field` varchar(255) collate utf8_unicode_ci default NULL,
  `is_ticket_title_mandatory` tinyint(1) NOT NULL default '0',
  `is_ticket_content_mandatory` tinyint(1) NOT NULL default '1',
  `is_ticket_category_mandatory` tinyint(1) NOT NULL default '0',
  `default_mailcollector_filesize_max` int(11) NOT NULL default '2097152',
  `followup_private` tinyint(1) NOT NULL default '0',
  `default_software_helpdesk_visible` tinyint(1) NOT NULL default '1',
  `names_format` int(11) NOT NULL default '0' COMMENT 'see *NAME_BEFORE constant in define.php',
  `default_request_type` int(11) NOT NULL default '1',
  `use_noright_users_add` tinyint(1) NOT NULL default '1',
  `cron_limit` tinyint(4) NOT NULL default '1' COMMENT 'Number of tasks execute by external cron',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_configs` VALUES ('1','0','250','15','50',' 0.80','5','0','admsys@xxxxx.fr',NULL,'SIGNATURE','0','fr_FR','#fff2f2','#ffe0e0','#ffcece','#ffbfbf','#ffadad','2005-12-31','10','','443','',NULL,'1',NULL,'0','08:00:00','20:00:00','1','0','0','http://localhost/glpi/','0','','','100','*','0','50','1','1','0','0','0','50','0','0',NULL,'25',NULL,NULL,NULL,'8080',NULL,NULL,'1','0','0','0','0','0','0','5','2',NULL,NULL,'0','2','2','2','2','0','0','1','1','1','1','0','0','0','0','0','0','0','1','1','1',NULL,'1',NULL,'0','1','0','2097152','0','1','0','1','1','1');

### Dump table glpi_consumables

DROP TABLE IF EXISTS `glpi_consumables`;
CREATE TABLE `glpi_consumables` (
  `id` int(11) NOT NULL auto_increment,
  `consumablesitems_id` int(11) NOT NULL default '0',
  `date_in` date default NULL,
  `date_out` date default NULL,
  `users_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `date_in` (`date_in`),
  KEY `date_out` (`date_out`),
  KEY `consumablesitems_id` (`consumablesitems_id`),
  KEY `users_id` (`users_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_consumablesitems

DROP TABLE IF EXISTS `glpi_consumablesitems`;
CREATE TABLE `glpi_consumablesitems` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `ref` varchar(255) collate utf8_unicode_ci default NULL,
  `locations_id` int(11) NOT NULL default '0',
  `consumablesitemstypes_id` int(11) NOT NULL default '0',
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
  KEY `consumablesitemstypes_id` (`consumablesitemstypes_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `alarm_threshold` (`alarm_threshold`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_consumablesitemstypes

DROP TABLE IF EXISTS `glpi_consumablesitemstypes`;
CREATE TABLE `glpi_consumablesitemstypes` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
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
  `contactstypes_id` int(11) NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  `is_deleted` tinyint(1) NOT NULL default '0',
  `notepad` longtext collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`),
  KEY `contactstypes_id` (`contactstypes_id`),
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


### Dump table glpi_contactstypes

DROP TABLE IF EXISTS `glpi_contactstypes`;
CREATE TABLE `glpi_contactstypes` (
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
  `contractstypes_id` int(11) NOT NULL default '0',
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
  KEY `contractstypes_id` (`contractstypes_id`),
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
  `itemtype` int(11) NOT NULL default '0',
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


### Dump table glpi_contractstypes

DROP TABLE IF EXISTS `glpi_contractstypes`;
CREATE TABLE `glpi_contractstypes` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_crontasks

DROP TABLE IF EXISTS `glpi_crontasks`;
CREATE TABLE `glpi_crontasks` (
  `id` int(11) NOT NULL auto_increment,
  `plugin` char(78) collate utf8_unicode_ci default NULL COMMENT 'NULL (glpi) or plugin name',
  `name` varchar(255) collate utf8_unicode_ci NOT NULL COMMENT 'task name',
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
  UNIQUE KEY `unicity` (`plugin`,`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Task run by internal / external cron.';

INSERT INTO `glpi_crontasks` VALUES ('1',NULL,'ocsng','300',NULL,'0','1','3','0','24','30',NULL,NULL,NULL);
INSERT INTO `glpi_crontasks` VALUES ('2',NULL,'cartridge','86400','10','0','1','3','0','24','30',NULL,NULL,NULL);
INSERT INTO `glpi_crontasks` VALUES ('3',NULL,'consumable','86400','10','0','1','3','0','24','30',NULL,NULL,NULL);
INSERT INTO `glpi_crontasks` VALUES ('4',NULL,'software','86400',NULL,'0','1','3','0','24','30',NULL,NULL,NULL);
INSERT INTO `glpi_crontasks` VALUES ('5',NULL,'contract','86400',NULL,'1','1','3','0','24','30',NULL,NULL,NULL);
INSERT INTO `glpi_crontasks` VALUES ('6',NULL,'infocom','86400',NULL,'1','1','3','0','24','30',NULL,NULL,NULL);
INSERT INTO `glpi_crontasks` VALUES ('7',NULL,'logs','86400','30','0','1','3','0','24','30',NULL,NULL,NULL);
INSERT INTO `glpi_crontasks` VALUES ('8',NULL,'optimize','604800',NULL,'1','1','3','0','24','30',NULL,NULL,NULL);
INSERT INTO `glpi_crontasks` VALUES ('9',NULL,'mailgate','600','10','1','1','3','0','24','30',NULL,NULL,NULL);
INSERT INTO `glpi_crontasks` VALUES ('10',NULL,'dbreplicate','60',NULL,'1','1','3','0','24','30',NULL,NULL,NULL);
INSERT INTO `glpi_crontasks` VALUES ('11',NULL,'check_update','604800',NULL,'0','1','3','0','24','30',NULL,NULL,NULL);
INSERT INTO `glpi_crontasks` VALUES ('12',NULL,'session','86400',NULL,'1','1','3','0','24','30',NULL,NULL,NULL);

### Dump table glpi_crontaskslogs

DROP TABLE IF EXISTS `glpi_crontaskslogs`;
CREATE TABLE `glpi_crontaskslogs` (
  `id` int(11) NOT NULL auto_increment,
  `crontasks_id` int(11) NOT NULL,
  `crontaskslogs_id` int(11) NOT NULL COMMENT 'id of ''start'' event',
  `date` datetime NOT NULL,
  `state` int(11) NOT NULL COMMENT '0:start, 1:run, 2:stop',
  `elapsed` float NOT NULL COMMENT 'time elapsed since start',
  `volume` int(11) NOT NULL COMMENT 'for statistics',
  `content` varchar(255) collate utf8_unicode_ci NOT NULL COMMENT 'message',
  PRIMARY KEY  (`id`),
  KEY `crontasks_id` (`crontasks_id`),
  KEY `crontasklogs_id` (`crontaskslogs_id`),
  KEY `crontaskslogs_id_state` (`crontaskslogs_id`,`state`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

### Dump table glpi_devicescases

DROP TABLE IF EXISTS `glpi_devicescases`;
CREATE TABLE `glpi_devicescases` (
  `id` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `devicescasestypes_id` int(11) NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  `manufacturers_id` int(11) NOT NULL default '0',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `devicescasestypes_id` (`devicescasestypes_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_devicescasestypes

DROP TABLE IF EXISTS `glpi_devicescasestypes`;
CREATE TABLE `glpi_devicescasestypes` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_devicescontrols

DROP TABLE IF EXISTS `glpi_devicescontrols`;
CREATE TABLE `glpi_devicescontrols` (
  `id` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `is_raid` tinyint(1) NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  `manufacturers_id` int(11) NOT NULL default '0',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  `interfaces_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `interfaces_id` (`interfaces_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_devicesdrives

DROP TABLE IF EXISTS `glpi_devicesdrives`;
CREATE TABLE `glpi_devicesdrives` (
  `id` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `is_writer` tinyint(1) NOT NULL default '1',
  `speed` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  `manufacturers_id` int(11) NOT NULL default '0',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  `interfaces_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `interfaces_id` (`interfaces_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_devicesgraphiccards

DROP TABLE IF EXISTS `glpi_devicesgraphiccards`;
CREATE TABLE `glpi_devicesgraphiccards` (
  `id` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `interfaces_id` int(11) NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  `manufacturers_id` int(11) NOT NULL default '0',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `interfaces_id` (`interfaces_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_devicesharddrives

DROP TABLE IF EXISTS `glpi_devicesharddrives`;
CREATE TABLE `glpi_devicesharddrives` (
  `id` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `rpm` varchar(255) collate utf8_unicode_ci default NULL,
  `interfaces_id` int(11) NOT NULL default '0',
  `cache` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  `manufacturers_id` int(11) NOT NULL default '0',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `interfaces_id` (`interfaces_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_devicesmemories

DROP TABLE IF EXISTS `glpi_devicesmemories`;
CREATE TABLE `glpi_devicesmemories` (
  `id` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `frequence` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  `manufacturers_id` int(11) NOT NULL default '0',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  `devicesmemoriestypes_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `devicesmemoriestypes_id` (`devicesmemoriestypes_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_devicesmemoriestypes

DROP TABLE IF EXISTS `glpi_devicesmemoriestypes`;
CREATE TABLE `glpi_devicesmemoriestypes` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_devicesmemoriestypes` VALUES ('1','EDO',NULL);
INSERT INTO `glpi_devicesmemoriestypes` VALUES ('2','DDR',NULL);
INSERT INTO `glpi_devicesmemoriestypes` VALUES ('3','SDRAM',NULL);
INSERT INTO `glpi_devicesmemoriestypes` VALUES ('4','SDRAM-2',NULL);

### Dump table glpi_devicesmotherboards

DROP TABLE IF EXISTS `glpi_devicesmotherboards`;
CREATE TABLE `glpi_devicesmotherboards` (
  `id` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `chipset` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  `manufacturers_id` int(11) NOT NULL default '0',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_devicesnetworkcards

DROP TABLE IF EXISTS `glpi_devicesnetworkcards`;
CREATE TABLE `glpi_devicesnetworkcards` (
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


### Dump table glpi_devicespcis

DROP TABLE IF EXISTS `glpi_devicespcis`;
CREATE TABLE `glpi_devicespcis` (
  `id` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  `manufacturers_id` int(11) NOT NULL default '0',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_devicespowersupplies

DROP TABLE IF EXISTS `glpi_devicespowersupplies`;
CREATE TABLE `glpi_devicespowersupplies` (
  `id` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `power` varchar(255) collate utf8_unicode_ci default NULL,
  `is_atx` tinyint(1) NOT NULL default '1',
  `comment` text collate utf8_unicode_ci,
  `manufacturers_id` int(11) NOT NULL default '0',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_devicesprocessors

DROP TABLE IF EXISTS `glpi_devicesprocessors`;
CREATE TABLE `glpi_devicesprocessors` (
  `id` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `frequence` int(11) NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  `manufacturers_id` int(11) NOT NULL default '0',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_devicessoundcards

DROP TABLE IF EXISTS `glpi_devicessoundcards`;
CREATE TABLE `glpi_devicessoundcards` (
  `id` int(11) NOT NULL auto_increment,
  `designation` varchar(255) collate utf8_unicode_ci default NULL,
  `type` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  `manufacturers_id` int(11) NOT NULL default '0',
  `specif_default` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `designation` (`designation`),
  KEY `manufacturers_id` (`manufacturers_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_displayprefs

DROP TABLE IF EXISTS `glpi_displayprefs`;
CREATE TABLE `glpi_displayprefs` (
  `id` int(11) NOT NULL auto_increment,
  `itemtype` int(11) NOT NULL default '0',
  `num` int(11) NOT NULL default '0',
  `rank` int(11) NOT NULL default '0',
  `users_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`users_id`,`itemtype`,`num`),
  KEY `rank` (`rank`),
  KEY `num` (`num`),
  KEY `itemtype` (`itemtype`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_displayprefs` VALUES ('32','1','4','4','0');
INSERT INTO `glpi_displayprefs` VALUES ('34','1','45','6','0');
INSERT INTO `glpi_displayprefs` VALUES ('33','1','40','5','0');
INSERT INTO `glpi_displayprefs` VALUES ('31','1','5','3','0');
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
INSERT INTO `glpi_displayprefs` VALUES ('82','11','34','1','0');
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
INSERT INTO `glpi_displayprefs` VALUES ('43','2','19','7','0');
INSERT INTO `glpi_displayprefs` VALUES ('47','3','4','4','0');
INSERT INTO `glpi_displayprefs` VALUES ('48','3','19','6','0');
INSERT INTO `glpi_displayprefs` VALUES ('53','4','19','6','0');
INSERT INTO `glpi_displayprefs` VALUES ('54','4','7','7','0');
INSERT INTO `glpi_displayprefs` VALUES ('58','5','4','4','0');
INSERT INTO `glpi_displayprefs` VALUES ('59','5','19','6','0');
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
INSERT INTO `glpi_displayprefs` VALUES ('84','11','23','3','0');
INSERT INTO `glpi_displayprefs` VALUES ('85','11','3','4','0');
INSERT INTO `glpi_displayprefs` VALUES ('88','12','6','2','0');
INSERT INTO `glpi_displayprefs` VALUES ('89','12','4','3','0');
INSERT INTO `glpi_displayprefs` VALUES ('90','12','5','4','0');
INSERT INTO `glpi_displayprefs` VALUES ('91','13','3','1','0');
INSERT INTO `glpi_displayprefs` VALUES ('92','13','4','2','0');
INSERT INTO `glpi_displayprefs` VALUES ('93','13','7','3','0');
INSERT INTO `glpi_displayprefs` VALUES ('94','13','5','4','0');
INSERT INTO `glpi_displayprefs` VALUES ('95','13','16','5','0');
INSERT INTO `glpi_displayprefs` VALUES ('96','15','34','1','0');
INSERT INTO `glpi_displayprefs` VALUES ('98','15','5','3','0');
INSERT INTO `glpi_displayprefs` VALUES ('99','15','6','4','0');
INSERT INTO `glpi_displayprefs` VALUES ('100','15','3','5','0');
INSERT INTO `glpi_displayprefs` VALUES ('101','17','34','1','0');
INSERT INTO `glpi_displayprefs` VALUES ('102','17','4','2','0');
INSERT INTO `glpi_displayprefs` VALUES ('103','17','23','3','0');
INSERT INTO `glpi_displayprefs` VALUES ('104','17','3','4','0');
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
INSERT INTO `glpi_displayprefs` VALUES ('115','23','19','6','0');
INSERT INTO `glpi_displayprefs` VALUES ('116','23','7','7','0');
INSERT INTO `glpi_displayprefs` VALUES ('117','27','16','1','0');
INSERT INTO `glpi_displayprefs` VALUES ('118','22','31','1','0');
INSERT INTO `glpi_displayprefs` VALUES ('119','29','4','1','0');
INSERT INTO `glpi_displayprefs` VALUES ('120','29','3','2','0');
INSERT INTO `glpi_displayprefs` VALUES ('121','35','80','1','0');
INSERT INTO `glpi_displayprefs` VALUES ('122','6','72','4','0');
INSERT INTO `glpi_displayprefs` VALUES ('123','6','163','5','0');
INSERT INTO `glpi_displayprefs` VALUES ('124','35','2','2','0');
INSERT INTO `glpi_displayprefs` VALUES ('125','49','8','1','0');
INSERT INTO `glpi_displayprefs` VALUES ('126','49','3','2','0');
INSERT INTO `glpi_displayprefs` VALUES ('127','49','4','3','0');
INSERT INTO `glpi_displayprefs` VALUES ('128','49','7','4','0');

### Dump table glpi_documents

DROP TABLE IF EXISTS `glpi_documents`;
CREATE TABLE `glpi_documents` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `is_recursive` tinyint(1) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `filename` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'for display and transfert',
  `filepath` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'file storage path',
  `documentscategories_id` int(11) NOT NULL default '0',
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
  KEY `documentscategories_id` (`documentscategories_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `sha1sum` (`sha1sum`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_documents_items

DROP TABLE IF EXISTS `glpi_documents_items`;
CREATE TABLE `glpi_documents_items` (
  `id` int(11) NOT NULL auto_increment,
  `documents_id` int(11) NOT NULL default '0',
  `items_id` int(11) NOT NULL default '0',
  `itemtype` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`documents_id`,`itemtype`,`items_id`),
  KEY `item` (`itemtype`,`items_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_documentscategories

DROP TABLE IF EXISTS `glpi_documentscategories`;
CREATE TABLE `glpi_documentscategories` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_documentstypes

DROP TABLE IF EXISTS `glpi_documentstypes`;
CREATE TABLE `glpi_documentstypes` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `ext` varchar(255) collate utf8_unicode_ci default NULL,
  `icon` varchar(255) collate utf8_unicode_ci default NULL,
  `mime` varchar(255) collate utf8_unicode_ci default NULL,
  `is_uploadable` tinyint(1) NOT NULL default '1',
  `date_mod` datetime default NULL,
  `comment` text COLLATE utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`ext`),
  KEY `name` (`name`),
  KEY `is_uploadable` (`is_uploadable`),
  KEY `date_mod` (`date_mod`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_documentstypes` VALUES ('1','JPEG','jpg','jpg-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('2','PNG','png','png-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('3','GIF','gif','gif-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('4','BMP','bmp','bmp-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('5','Photoshop','psd','psd-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('6','TIFF','tif','tif-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('7','AIFF','aiff','aiff-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('8','Windows Media','asf','asf-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('9','Windows Media','avi','avi-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('44','C source','c','','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('27','RealAudio','rm','rm-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('16','Midi','mid','mid-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('17','QuickTime','mov','mov-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('18','MP3','mp3','mp3-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('19','MPEG','mpg','mpg-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('20','Ogg Vorbis','ogg','ogg-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('24','QuickTime','qt','qt-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('10','BZip','bz2','bz2-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('25','RealAudio','ra','ra-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('26','RealAudio','ram','ram-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('11','Word','doc','doc-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('12','DjVu','djvu','','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('42','MNG','mng','','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('13','PostScript','eps','ps-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('14','GZ','gz','gz-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('37','WAV','wav','wav-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('15','HTML','html','html-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('34','Flash','swf','','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('21','PDF','pdf','pdf-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('22','PowerPoint','ppt','ppt-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('23','PostScript','ps','ps-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('40','Windows Media','wmv','','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('28','RTF','rtf','rtf-dist.png','','1','2004-12-13 19:47:21',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('29','StarOffice','sdd','sdd-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('30','StarOffice','sdw','sdw-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('31','Stuffit','sit','sit-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('43','Adobe Illustrator','ai','ai-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('32','OpenOffice Impress','sxi','sxi-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('33','OpenOffice','sxw','sxw-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('46','DVI','dvi','dvi-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('35','TGZ','tgz','tgz-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('36','texte','txt','txt-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('49','RedHat/Mandrake/SuSE','rpm','rpm-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('38','Excel','xls','xls-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('39','XML','xml','xml-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('41','Zip','zip','zip-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('45','Debian','deb','deb-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('47','C header','h','','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('48','Pascal','pas','','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('50','OpenOffice Calc','sxc','sxc-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('51','LaTeX','tex','tex-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('52','GIMP multi-layer','xcf','xcf-dist.png','','1','2004-12-13 19:47:22',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('53','JPEG','jpeg','jpg-dist.png','','1','2005-03-07 22:23:17',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('54','Oasis Open Office Writer','odt','odt-dist.png','','1','2006-01-21 17:41:13',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('55','Oasis Open Office Calc','ods','ods-dist.png','','1','2006-01-21 17:41:31',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('56','Oasis Open Office Impress','odp','odp-dist.png','','1','2006-01-21 17:42:54',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('57','Oasis Open Office Impress Template','otp','odp-dist.png','','1','2006-01-21 17:43:58',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('58','Oasis Open Office Writer Template','ott','odt-dist.png','','1','2006-01-21 17:44:41',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('59','Oasis Open Office Calc Template','ots','ods-dist.png','','1','2006-01-21 17:45:30',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('60','Oasis Open Office Math','odf','odf-dist.png','','1','2006-01-21 17:48:05',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('61','Oasis Open Office Draw','odg','odg-dist.png','','1','2006-01-21 17:48:31',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('62','Oasis Open Office Draw Template','otg','odg-dist.png','','1','2006-01-21 17:49:46',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('63','Oasis Open Office Base','odb','odb-dist.png','','1','2006-01-21 18:03:34',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('64','Oasis Open Office HTML','oth','oth-dist.png','','1','2006-01-21 18:05:27',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('65','Oasis Open Office Writer Master','odm','odm-dist.png','','1','2006-01-21 18:06:34',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('66','Oasis Open Office Chart','odc','','','1','2006-01-21 18:07:48',NULL);
INSERT INTO `glpi_documentstypes` VALUES ('67','Oasis Open Office Image','odi','','','1','2006-01-21 18:08:18',NULL);

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


### Dump table glpi_entitiesdatas

DROP TABLE IF EXISTS `glpi_entitiesdatas`;
CREATE TABLE `glpi_entitiesdatas` (
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

INSERT INTO `glpi_events` VALUES ('1','-1','system','2009-10-19 19:05:38','login','3','glpi connexion de l\'IP : 127.0.0.1');

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
  PRIMARY KEY  (`id`),
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
  `id` int(11) NOT NULL auto_increment,
  `users_id` int(11) NOT NULL default '0',
  `groups_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`users_id`,`groups_id`),
  KEY `groups_id` (`groups_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_infocoms

DROP TABLE IF EXISTS `glpi_infocoms`;
CREATE TABLE `glpi_infocoms` (
  `id` int(11) NOT NULL auto_increment,
  `items_id` int(11) NOT NULL default '0',
  `itemtype` int(11) NOT NULL default '0',
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
  KEY `suppliers_id` (`suppliers_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_interfaces

DROP TABLE IF EXISTS `glpi_interfaces`;
CREATE TABLE `glpi_interfaces` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
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
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `is_recursive` tinyint(1) NOT NULL default '1',
  `knowbaseitemscategories_id` int(11) NOT NULL default '0',
  `question` text collate utf8_unicode_ci,
  `answer` longtext collate utf8_unicode_ci,
  `is_faq` tinyint(1) NOT NULL default '0',
  `users_id` int(11) NOT NULL default '0',
  `view` int(11) NOT NULL default '0',
  `date` datetime default NULL,
  `date_mod` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `users_id` (`users_id`),
  KEY `knowbaseitemscategories_id` (`knowbaseitemscategories_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_faq` (`is_faq`),
  KEY `date_mod` (`date_mod`),
  FULLTEXT KEY `fulltext` (`question`,`answer`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_knowbaseitemscategories

DROP TABLE IF EXISTS `glpi_knowbaseitemscategories`;
CREATE TABLE `glpi_knowbaseitemscategories` (
  `id` int(11) NOT NULL auto_increment,
  `knowbaseitemscategories_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `completename` text collate utf8_unicode_ci,
  `comment` text collate utf8_unicode_ci,
  `level` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`knowbaseitemscategories_id`,`name`),
  KEY `name` (`name`)
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
  `itemtype` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`itemtype`,`links_id`),
  KEY `links_id` (`links_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_locations

DROP TABLE IF EXISTS `glpi_locations`;
CREATE TABLE `glpi_locations` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `locations_id` int(11) NOT NULL default '0',
  `completename` text collate utf8_unicode_ci,
  `comment` text collate utf8_unicode_ci,
  `level` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`entities_id`,`locations_id`,`name`),
  KEY `locations_id` (`locations_id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_logs

DROP TABLE IF EXISTS `glpi_logs`;
CREATE TABLE `glpi_logs` (
  `id` int(11) NOT NULL auto_increment,
  `items_id` int(11) NOT NULL default '0',
  `itemtype` int(11) NOT NULL default '0',
  `devicetype` int(11) NOT NULL default '0',
  `linked_action` int(11) NOT NULL default '0' COMMENT 'see define.php HISTORY_* constant',
  `user_name` varchar(255) collate utf8_unicode_ci default NULL,
  `date_mod` datetime default NULL,
  `id_search_option` int(11) NOT NULL default '0' COMMENT 'see search.constant.php for value',
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `device_type` (`itemtype`),
  KEY `date_mod` (`date_mod`),
  KEY `devicetype` (`devicetype`),
  KEY `linked_action` (`linked_action`),
  KEY `item` (`itemtype`,`items_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_mailcollectors

DROP TABLE IF EXISTS `glpi_mailcollectors`;
CREATE TABLE `glpi_mailcollectors` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `entities_id` int(11) NOT NULL default '0',
  `host` varchar(255) collate utf8_unicode_ci default NULL,
  `login` varchar(255) collate utf8_unicode_ci default NULL,
  `password` varchar(255) collate utf8_unicode_ci default NULL,
  `filesize_max` int(11) NOT NULL default '2097152',
  `is_active` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`),
  KEY `entities_id` (`entities_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_mailingsettings

DROP TABLE IF EXISTS `glpi_mailingsettings`;
CREATE TABLE `glpi_mailingsettings` (
  `id` int(11) NOT NULL auto_increment,
  `type` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'VALUE in (new, followup, finish, update, resa, alertconsumable, alertcartdridge, alertinfocom, alertlicense)',
  `items_id` int(11) NOT NULL default '0',
  `mailingtype` int(11) NOT NULL default '0' COMMENT 'see define.php *_MAILING_TYPE constant',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`type`,`items_id`,`mailingtype`),
  KEY `items` (`mailingtype`,`items_id`)
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
  `monitorstypes_id` int(11) NOT NULL default '0',
  `monitorsmodels_id` int(11) NOT NULL default '0',
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
  KEY `monitorsmodels_id` (`monitorsmodels_id`),
  KEY `states_id` (`states_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `monitorstypes_id` (`monitorstypes_id`),
  KEY `is_deleted` (`is_deleted`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_monitorsmodels

DROP TABLE IF EXISTS `glpi_monitorsmodels`;
CREATE TABLE `glpi_monitorsmodels` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_monitorstypes

DROP TABLE IF EXISTS `glpi_monitorstypes`;
CREATE TABLE `glpi_monitorstypes` (
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
  `networkequipmentstypes_id` int(11) NOT NULL default '0',
  `networkequipmentsmodels_id` int(11) NOT NULL default '0',
  `networkequipmentsfirmwares_id` int(11) NOT NULL default '0',
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
  KEY `networkequipmentsfirmwares_id` (`networkequipmentsfirmwares_id`),
  KEY `entities_id` (`entities_id`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `groups_id` (`groups_id`),
  KEY `users_id` (`users_id`),
  KEY `locations_id` (`locations_id`),
  KEY `networkequipmentsmodels_id` (`networkequipmentsmodels_id`),
  KEY `networks_id` (`networks_id`),
  KEY `states_id` (`states_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `networkequipmentstypes_id` (`networkequipmentstypes_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `date_mod` (`date_mod`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_networkequipmentsfirmwares

DROP TABLE IF EXISTS `glpi_networkequipmentsfirmwares`;
CREATE TABLE `glpi_networkequipmentsfirmwares` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_networkequipmentsmodels

DROP TABLE IF EXISTS `glpi_networkequipmentsmodels`;
CREATE TABLE `glpi_networkequipmentsmodels` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_networkequipmentstypes

DROP TABLE IF EXISTS `glpi_networkequipmentstypes`;
CREATE TABLE `glpi_networkequipmentstypes` (
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
  `itemtype` int(11) NOT NULL default '0',
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
  KEY `item` (`itemtype`,`items_id`)
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
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_ocsservers` VALUES ('1','localhost','ocs','ocs','localhost','ocsweb','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','',NULL,'0','1',NULL,'0','0','0','0','0','0','');

### Dump table glpi_operatingsystems

DROP TABLE IF EXISTS `glpi_operatingsystems`;
CREATE TABLE `glpi_operatingsystems` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_operatingsystemsservicepacks

DROP TABLE IF EXISTS `glpi_operatingsystemsservicepacks`;
CREATE TABLE `glpi_operatingsystemsservicepacks` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_operatingsystemsversions

DROP TABLE IF EXISTS `glpi_operatingsystemsversions`;
CREATE TABLE `glpi_operatingsystemsversions` (
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
  `peripheralstypes_id` int(11) NOT NULL default '0',
  `peripheralsmodels_id` int(11) NOT NULL default '0',
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
  KEY `peripheralsmodels_id` (`peripheralsmodels_id`),
  KEY `states_id` (`states_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `peripheralstypes_id` (`peripheralstypes_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `date_mod` (`date_mod`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_peripheralsmodels

DROP TABLE IF EXISTS `glpi_peripheralsmodels`;
CREATE TABLE `glpi_peripheralsmodels` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_peripheralstypes

DROP TABLE IF EXISTS `glpi_peripheralstypes`;
CREATE TABLE `glpi_peripheralstypes` (
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
  `phonestypes_id` int(11) NOT NULL default '0',
  `phonesmodels_id` int(11) NOT NULL default '0',
  `brand` varchar(255) collate utf8_unicode_ci default NULL,
  `phonespowersupplies_id` int(11) NOT NULL default '0',
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
  KEY `phonesmodels_id` (`phonesmodels_id`),
  KEY `phonespowersupplies_id` (`phonespowersupplies_id`),
  KEY `states_id` (`states_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `phonestypes_id` (`phonestypes_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `date_mod` (`date_mod`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_phonesmodels

DROP TABLE IF EXISTS `glpi_phonesmodels`;
CREATE TABLE `glpi_phonesmodels` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_phonespowersupplies

DROP TABLE IF EXISTS `glpi_phonespowersupplies`;
CREATE TABLE `glpi_phonespowersupplies` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_phonestypes

DROP TABLE IF EXISTS `glpi_phonestypes`;
CREATE TABLE `glpi_phonestypes` (
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
  `comment` text collate utf8_unicode_ci,
  `memory_size` varchar(255) collate utf8_unicode_ci default NULL,
  `locations_id` int(11) NOT NULL default '0',
  `domains_id` int(11) NOT NULL default '0',
  `networks_id` int(11) NOT NULL default '0',
  `printerstypes_id` int(11) NOT NULL default '0',
  `printersmodels_id` int(11) NOT NULL default '0',
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
  KEY `printersmodels_id` (`printersmodels_id`),
  KEY `networks_id` (`networks_id`),
  KEY `states_id` (`states_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `printerstypes_id` (`printerstypes_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `date_mod` (`date_mod`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_printersmodels

DROP TABLE IF EXISTS `glpi_printersmodels`;
CREATE TABLE `glpi_printersmodels` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_printerstypes

DROP TABLE IF EXISTS `glpi_printerstypes`;
CREATE TABLE `glpi_printerstypes` (
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
  `rule_ocs` char(1) collate utf8_unicode_ci default NULL,
  `rule_ldap` char(1) collate utf8_unicode_ci default NULL,
  `rule_softwarescategories` char(1) collate utf8_unicode_ci default NULL,
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
  `helpdesk_hardware` int(11) NOT NULL default '0',
  `helpdesk_item_type` text collate utf8_unicode_ci,
  `helpdesk_status` text collate utf8_unicode_ci COMMENT 'json encoded array of from/dest allowed status change',
  `show_group_ticket` char(1) collate utf8_unicode_ci default NULL,
  `show_group_hardware` char(1) collate utf8_unicode_ci default NULL,
  `rule_dictionnary_software` char(1) collate utf8_unicode_ci default NULL,
  `rule_dictionnary_dropdown` char(1) collate utf8_unicode_ci default NULL,
  `budget` char(1) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `interface` (`interface`),
  KEY `is_default` (`is_default`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_profiles` VALUES ('1','post-only','helpdesk','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'r','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1',NULL,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1',NULL,NULL,NULL,NULL,NULL,'1','1','{\"1\":1,\"6\":6,\"23\":23}',NULL,'0','0',NULL,NULL,NULL);
INSERT INTO `glpi_profiles` VALUES ('2','normal','central','0','r','r','r','r','r','r','r','r','r','r','r','r','r','r','r','r','1','r','r',NULL,'r',NULL,NULL,NULL,NULL,'r','r',NULL,NULL,NULL,NULL,NULL,'w',NULL,'r',NULL,'r','r','r',NULL,NULL,NULL,NULL,NULL,NULL,'1','1','1','0','0','1','0','0','1','1','0','1','0','1','0','0','1','1','1','{\"1\":1,\"6\":6,\"23\":23}',NULL,'0','0',NULL,NULL,'r');
INSERT INTO `glpi_profiles` VALUES ('3','admin','central','0','w','w','w','w','w','w','w','w','w','w','w','w','w','w','w','w','1','w','r','w','r','w','w','w','w','w','w',NULL,NULL,NULL,NULL,NULL,'w','w','r','r','w','w','w',NULL,NULL,NULL,NULL,NULL,NULL,'1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','3','{\"1\":1,\"6\":6,\"23\":23}',NULL,'0','0',NULL,NULL,'w');
INSERT INTO `glpi_profiles` VALUES ('4','super-admin','central','0','w','w','w','w','w','w','w','w','w','w','w','w','w','w','w','w','1','w','r','w','r','w','w','w','w','w','w','w','w','w','w','w','w','w','r','w','w','w','w','w','w','r','w','w','w','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','3','{\"1\":1,\"6\":6,\"23\":23}',NULL,'0','0','w','w','w');

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


### Dump table glpi_reservations

DROP TABLE IF EXISTS `glpi_reservations`;
CREATE TABLE `glpi_reservations` (
  `id` int(11) NOT NULL auto_increment,
  `reservationsitems_id` int(11) NOT NULL default '0',
  `begin` datetime default NULL,
  `end` datetime default NULL,
  `users_id` int(11) NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `begin` (`begin`),
  KEY `end` (`end`),
  KEY `reservationsitems_id` (`reservationsitems_id`),
  KEY `users_id` (`users_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_reservationsitems

DROP TABLE IF EXISTS `glpi_reservationsitems`;
CREATE TABLE `glpi_reservationsitems` (
  `id` int(11) NOT NULL auto_increment,
  `itemtype` int(11) NOT NULL default '0',
  `items_id` int(11) NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  `is_active` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`),
  KEY `is_active` (`is_active`),
  KEY `item` (`itemtype`,`items_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rules

DROP TABLE IF EXISTS `glpi_rules`;
CREATE TABLE `glpi_rules` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `sub_type` int(11) NOT NULL default '0' COMMENT 'see define.php RULE_* constant',
  `ranking` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `description` text collate utf8_unicode_ci,
  `match` char(10) collate utf8_unicode_ci default NULL COMMENT 'see define.php *_MATCHING constant',
  `is_active` tinyint(1) NOT NULL default '1',
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_active` (`is_active`),
  KEY `sub_type` (`sub_type`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_rules` VALUES ('1','-1','0','0','Root','','AND','1',NULL);
INSERT INTO `glpi_rules` VALUES ('2','-1','1','1','Root','','OR','1',NULL);

### Dump table glpi_rulesactions

DROP TABLE IF EXISTS `glpi_rulesactions`;
CREATE TABLE `glpi_rulesactions` (
  `id` int(11) NOT NULL auto_increment,
  `rules_id` int(11) NOT NULL default '0',
  `action_type` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'VALUE IN (assign, regex_result, append_regex_result, affectbyip, affectbyfqdn, affectbymac)',
  `field` varchar(255) collate utf8_unicode_ci default NULL,
  `value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_rulesactions` VALUES ('1','1','assign','entities_id','0');
INSERT INTO `glpi_rulesactions` VALUES ('2','2','assign','entities_id','0');

### Dump table glpi_rulescachecomputersmodels

DROP TABLE IF EXISTS `glpi_rulescachecomputersmodels`;
CREATE TABLE `glpi_rulescachecomputersmodels` (
  `id` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rules_id` int(11) NOT NULL default '0',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  `manufacturer` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `old_value` (`old_value`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulescachecomputerstypes

DROP TABLE IF EXISTS `glpi_rulescachecomputerstypes`;
CREATE TABLE `glpi_rulescachecomputerstypes` (
  `id` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rules_id` int(11) NOT NULL default '0',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `old_value` (`old_value`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulescachemanufacturers

DROP TABLE IF EXISTS `glpi_rulescachemanufacturers`;
CREATE TABLE `glpi_rulescachemanufacturers` (
  `id` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rules_id` int(11) NOT NULL default '0',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `old_value` (`old_value`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulescachemonitorsmodels

DROP TABLE IF EXISTS `glpi_rulescachemonitorsmodels`;
CREATE TABLE `glpi_rulescachemonitorsmodels` (
  `id` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rules_id` int(11) NOT NULL default '0',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  `manufacturer` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `old_value` (`old_value`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulescachemonitorstypes

DROP TABLE IF EXISTS `glpi_rulescachemonitorstypes`;
CREATE TABLE `glpi_rulescachemonitorstypes` (
  `id` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rules_id` int(11) NOT NULL default '0',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `old_value` (`old_value`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulescachenetworkequipmentsmodels

DROP TABLE IF EXISTS `glpi_rulescachenetworkequipmentsmodels`;
CREATE TABLE `glpi_rulescachenetworkequipmentsmodels` (
  `id` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rules_id` int(11) NOT NULL default '0',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  `manufacturer` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `old_value` (`old_value`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulescachenetworkequipmentstypes

DROP TABLE IF EXISTS `glpi_rulescachenetworkequipmentstypes`;
CREATE TABLE `glpi_rulescachenetworkequipmentstypes` (
  `id` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rules_id` int(11) NOT NULL default '0',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `old_value` (`old_value`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulescacheoperatingsystems

DROP TABLE IF EXISTS `glpi_rulescacheoperatingsystems`;
CREATE TABLE `glpi_rulescacheoperatingsystems` (
  `id` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rules_id` int(11) NOT NULL default '0',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `old_value` (`old_value`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulescacheoperatingsystemsservicepacks

DROP TABLE IF EXISTS `glpi_rulescacheoperatingsystemsservicepacks`;
CREATE TABLE `glpi_rulescacheoperatingsystemsservicepacks` (
  `id` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rules_id` int(11) NOT NULL default '0',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `old_value` (`old_value`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulescacheoperatingsystemsversions

DROP TABLE IF EXISTS `glpi_rulescacheoperatingsystemsversions`;
CREATE TABLE `glpi_rulescacheoperatingsystemsversions` (
  `id` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rules_id` int(11) NOT NULL default '0',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `old_value` (`old_value`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulescacheperipheralsmodels

DROP TABLE IF EXISTS `glpi_rulescacheperipheralsmodels`;
CREATE TABLE `glpi_rulescacheperipheralsmodels` (
  `id` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rules_id` int(11) NOT NULL default '0',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  `manufacturer` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `old_value` (`old_value`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulescacheperipheralstypes

DROP TABLE IF EXISTS `glpi_rulescacheperipheralstypes`;
CREATE TABLE `glpi_rulescacheperipheralstypes` (
  `id` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rules_id` int(11) NOT NULL default '0',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `old_value` (`old_value`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulescachephonesmodels

DROP TABLE IF EXISTS `glpi_rulescachephonesmodels`;
CREATE TABLE `glpi_rulescachephonesmodels` (
  `id` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rules_id` int(11) NOT NULL default '0',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  `manufacturer` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `old_value` (`old_value`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulescachephonestypes

DROP TABLE IF EXISTS `glpi_rulescachephonestypes`;
CREATE TABLE `glpi_rulescachephonestypes` (
  `id` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rules_id` int(11) NOT NULL default '0',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `old_value` (`old_value`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulescacheprintersmodels

DROP TABLE IF EXISTS `glpi_rulescacheprintersmodels`;
CREATE TABLE `glpi_rulescacheprintersmodels` (
  `id` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rules_id` int(11) NOT NULL default '0',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  `manufacturer` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `old_value` (`old_value`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulescacheprinterstypes

DROP TABLE IF EXISTS `glpi_rulescacheprinterstypes`;
CREATE TABLE `glpi_rulescacheprinterstypes` (
  `id` int(11) NOT NULL auto_increment,
  `old_value` varchar(255) collate utf8_unicode_ci default NULL,
  `rules_id` int(11) NOT NULL default '0',
  `new_value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `old_value` (`old_value`),
  KEY `rules_id` (`rules_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_rulescachesoftwares

DROP TABLE IF EXISTS `glpi_rulescachesoftwares`;
CREATE TABLE `glpi_rulescachesoftwares` (
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


### Dump table glpi_rulescriterias

DROP TABLE IF EXISTS `glpi_rulescriterias`;
CREATE TABLE `glpi_rulescriterias` (
  `id` int(11) NOT NULL auto_increment,
  `rules_id` int(11) NOT NULL default '0',
  `criteria` varchar(255) collate utf8_unicode_ci default NULL,
  `condition` int(11) NOT NULL default '0' COMMENT 'see define.php PATTERN_* and REGEX_* constant',
  `pattern` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `rules_id` (`rules_id`),
  KEY `condition` (`condition`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_rulescriterias` VALUES ('1','1','TAG','0','*');
INSERT INTO `glpi_rulescriterias` VALUES ('2','2','uid','0','*');
INSERT INTO `glpi_rulescriterias` VALUES ('3','2','samaccountname','0','*');
INSERT INTO `glpi_rulescriterias` VALUES ('4','2','MAIL_EMAIL','0','*');

### Dump table glpi_rulesldapparameters

DROP TABLE IF EXISTS `glpi_rulesldapparameters`;
CREATE TABLE `glpi_rulesldapparameters` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `value` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_rulesldapparameters` VALUES ('1','(LDAP)Organization','o');
INSERT INTO `glpi_rulesldapparameters` VALUES ('2','(LDAP)Common Name','cn');
INSERT INTO `glpi_rulesldapparameters` VALUES ('3','(LDAP)Department Number','departmentnumber');
INSERT INTO `glpi_rulesldapparameters` VALUES ('4','(LDAP)Email','mail');
INSERT INTO `glpi_rulesldapparameters` VALUES ('5','Object Class','objectclass');
INSERT INTO `glpi_rulesldapparameters` VALUES ('6','(LDAP)User ID','uid');
INSERT INTO `glpi_rulesldapparameters` VALUES ('7','(LDAP)Telephone Number','phone');
INSERT INTO `glpi_rulesldapparameters` VALUES ('8','(LDAP)Employee Number','employeenumber');
INSERT INTO `glpi_rulesldapparameters` VALUES ('9','(LDAP)Manager','manager');
INSERT INTO `glpi_rulesldapparameters` VALUES ('10','(LDAP)DistinguishedName','dn');
INSERT INTO `glpi_rulesldapparameters` VALUES ('12','(AD)User ID','samaccountname');
INSERT INTO `glpi_rulesldapparameters` VALUES ('13','(LDAP) Title','title');

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
  `softwarescategories_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `name` (`name`),
  KEY `is_template` (`is_template`),
  KEY `is_update` (`is_update`),
  KEY `softwarescategories_id` (`softwarescategories_id`),
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


### Dump table glpi_softwarescategories

DROP TABLE IF EXISTS `glpi_softwarescategories`;
CREATE TABLE `glpi_softwarescategories` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_softwarescategories` VALUES ('1','FUSION',NULL);

### Dump table glpi_softwareslicenses

DROP TABLE IF EXISTS `glpi_softwareslicenses`;
CREATE TABLE `glpi_softwareslicenses` (
  `id` int(11) NOT NULL auto_increment,
  `softwares_id` int(11) NOT NULL default '0',
  `entities_id` int(11) NOT NULL default '0',
  `is_recursive` tinyint(1) NOT NULL default '0',
  `number` int(11) NOT NULL default '0',
  `softwareslicensestypes_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `serial` varchar(255) collate utf8_unicode_ci default NULL,
  `otherserial` varchar(255) collate utf8_unicode_ci default NULL,
  `softwaresversions_id_buy` int(11) NOT NULL default '0',
  `softwaresversions_id_use` int(11) NOT NULL default '0',
  `expire` date default NULL,
  `computers_id` int(11) NOT NULL default '0',
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `serial` (`serial`),
  KEY `otherserial` (`otherserial`),
  KEY `expire` (`expire`),
  KEY `softwaresversions_id_buy` (`softwaresversions_id_buy`),
  KEY `computers_id` (`computers_id`),
  KEY `entities_id` (`entities_id`),
  KEY `softwares_id` (`softwares_id`),
  KEY `softwareslicensestypes_id` (`softwareslicensestypes_id`),
  KEY `softwaresversions_id_use` (`softwaresversions_id_use`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_softwareslicensestypes

DROP TABLE IF EXISTS `glpi_softwareslicensestypes`;
CREATE TABLE `glpi_softwareslicensestypes` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_softwareslicensestypes` VALUES ('1','OEM','');

### Dump table glpi_softwaresversions

DROP TABLE IF EXISTS `glpi_softwaresversions`;
CREATE TABLE `glpi_softwaresversions` (
  `id` int(11) NOT NULL auto_increment,
  `softwares_id` int(11) NOT NULL default '0',
  `states_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `softwares_id` (`softwares_id`),
  KEY `states_id` (`states_id`)
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
  `supplierstypes_id` int(11) NOT NULL default '0',
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
  KEY `supplierstypes_id` (`supplierstypes_id`),
  KEY `is_deleted` (`is_deleted`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_supplierstypes

DROP TABLE IF EXISTS `glpi_supplierstypes`;
CREATE TABLE `glpi_supplierstypes` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_taskscategories

DROP TABLE IF EXISTS `glpi_taskscategories`;
CREATE TABLE `glpi_taskscategories` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `is_recursive` tinyint(1) NOT NULL default '0',
  `taskscategories_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `completename` text collate utf8_unicode_ci,
  `comment` text collate utf8_unicode_ci,
  `level` int(11) NOT NULL default '0',
  `ancestors_cache` longtext collate utf8_unicode_ci,
  `sons_cache` longtext collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `taskscategories_id` (`taskscategories_id`),
  KEY `entities_id` (`entities_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_tickets

DROP TABLE IF EXISTS `glpi_tickets`;
CREATE TABLE `glpi_tickets` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `date` datetime default NULL,
  `closedate` datetime default NULL,
  `date_mod` datetime default NULL,
  `status` varchar(255) collate utf8_unicode_ci default 'new',
  `users_id` int(11) NOT NULL default '0',
  `users_id_recipient` int(11) NOT NULL default '0',
  `groups_id` int(11) NOT NULL default '0',
  `request_type` int(11) NOT NULL default '0',
  `users_id_assign` int(11) NOT NULL default '0',
  `suppliers_id_assign` int(11) NOT NULL default '0',
  `groups_id_assign` int(11) NOT NULL default '0',
  `itemtype` int(11) NOT NULL default '0',
  `items_id` int(11) NOT NULL default '0',
  `content` longtext collate utf8_unicode_ci,
  `priority` int(11) NOT NULL default '1',
  `user_email` varchar(255) collate utf8_unicode_ci default NULL,
  `use_email_notification` tinyint(1) NOT NULL default '0',
  `realtime` float NOT NULL default '0',
  `ticketscategories_id` int(11) NOT NULL default '0',
  `cost_time` decimal(20,4) NOT NULL default '0.0000',
  `cost_fixed` decimal(20,4) NOT NULL default '0.0000',
  `cost_material` decimal(20,4) NOT NULL default '0.0000',
  PRIMARY KEY  (`id`),
  KEY `date` (`date`),
  KEY `closedate` (`closedate`),
  KEY `status` (`status`),
  KEY `priority` (`priority`),
  KEY `request_type` (`request_type`),
  KEY `date_mod` (`date_mod`),
  KEY `users_id_assign` (`users_id_assign`),
  KEY `groups_id_assign` (`groups_id_assign`),
  KEY `suppliers_id_assign` (`suppliers_id_assign`),
  KEY `users_id` (`users_id`),
  KEY `ticketscategories_id` (`ticketscategories_id`),
  KEY `entities_id` (`entities_id`),
  KEY `groups_id` (`groups_id`),
  KEY `users_id_recipient` (`users_id_recipient`),
  KEY `item` (`itemtype`,`items_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_ticketscategories

DROP TABLE IF EXISTS `glpi_ticketscategories`;
CREATE TABLE `glpi_ticketscategories` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `is_recursive` tinyint(1) NOT NULL default '0',
  `ticketscategories_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `completename` text collate utf8_unicode_ci,
  `comment` text collate utf8_unicode_ci,
  `level` int(11) NOT NULL default '0',
  `knowbaseitemscategories_id` int(11) NOT NULL default '0',
  `users_id` int(11) NOT NULL default '0',
  `groups_id` int(11) NOT NULL default '0',
  `ancestors_cache` longtext collate utf8_unicode_ci,
  `sons_cache` longtext collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `ticketscategories_id` (`ticketscategories_id`),
  KEY `entities_id` (`entities_id`),
  KEY `knowbaseitemscategories_id` (`knowbaseitemscategories_id`),
  KEY `users_id` (`users_id`),
  KEY `groups_id` (`groups_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_ticketsfollowups

DROP TABLE IF EXISTS `glpi_ticketsfollowups`;
CREATE TABLE `glpi_ticketsfollowups` (
  `id` int(11) NOT NULL auto_increment,
  `tickets_id` int(11) NOT NULL default '0',
  `date` datetime default NULL,
  `users_id` int(11) NOT NULL default '0',
  `content` longtext collate utf8_unicode_ci,
  `is_private` tinyint(1) NOT NULL default '0',
  `realtime` float NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `date` (`date`),
  KEY `users_id` (`users_id`),
  KEY `tickets_id` (`tickets_id`),
  KEY `is_private` (`is_private`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_ticketsplannings

DROP TABLE IF EXISTS `glpi_ticketsplannings`;
CREATE TABLE `glpi_ticketsplannings` (
  `id` int(11) NOT NULL auto_increment,
  `ticketsfollowups_id` int(11) NOT NULL default '0',
  `users_id` int(11) NOT NULL default '0',
  `begin` datetime default NULL,
  `end` datetime default NULL,
  `state` int(11) NOT NULL default '1',
  PRIMARY KEY  (`id`),
  KEY `begin` (`begin`),
  KEY `end` (`end`),
  KEY `users_id` (`users_id`),
  KEY `ticketsfollowups_id` (`ticketsfollowups_id`),
  KEY `state` (`state`)
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
  `keep_cartridgesitem` int(11) NOT NULL default '0',
  `clean_cartridgesitem` int(11) NOT NULL default '0',
  `keep_cartridge` int(11) NOT NULL default '0',
  `keep_consumable` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_transfers` VALUES ('1','complete','2','2','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1','1');

### Dump table glpi_users

DROP TABLE IF EXISTS `glpi_users`;
CREATE TABLE `glpi_users` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `password` char(32) collate utf8_unicode_ci default NULL,
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
  `userstitles_id` int(11) NOT NULL default '0',
  `userscategories_id` int(11) NOT NULL default '0',
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
  `is_categorized_soft_expanded` tinyint(1) default NULL,
  `is_not_categorized_soft_expanded` tinyint(1) default NULL,
  `followup_private` tinyint(1) default NULL,
  `default_request_type` int(11) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unicity` (`name`),
  KEY `firstname` (`firstname`),
  KEY `realname` (`realname`),
  KEY `entities_id` (`entities_id`),
  KEY `profiles_id` (`profiles_id`),
  KEY `locations_id` (`locations_id`),
  KEY `userstitles_id` (`userstitles_id`),
  KEY `userscategories_id` (`userscategories_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_active` (`is_active`),
  KEY `date_mod` (`date_mod`),
  KEY `authitem` (`authtype`,`auths_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_users` VALUES ('2','glpi','41ece51526515624ff89973668497d00','','','','','',NULL,'0',NULL,'0','20','1',NULL,'0','1','2009-10-19 19:05:37','2009-10-19 19:05:37','0','0','0','0','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0',NULL,NULL);
INSERT INTO `glpi_users` VALUES ('3','post-only','3177926a7314de24680a9938aaa97703','','','','','',NULL,'0','en_GB','0','20','1',NULL,'0','0',NULL,NULL,'0','0','0','0','0',NULL,NULL,'0',NULL,'0','0',NULL,NULL,NULL,NULL,NULL,'0','0','0',NULL);
INSERT INTO `glpi_users` VALUES ('4','tech','d9f9133fb120cd6096870bc2b496805b','','','','','',NULL,'0','fr_FR','0','20','1',NULL,'0','0',NULL,NULL,'0','0','0','0','0',NULL,NULL,'0',NULL,'0','0',NULL,NULL,NULL,NULL,NULL,'0','0','0',NULL);
INSERT INTO `glpi_users` VALUES ('5','normal','fea087517c26fadd409bd4b9dc642555','','','','','',NULL,'0','en_GB','0','20','1',NULL,'0','0',NULL,NULL,'0','0','0','0','0',NULL,NULL,'0',NULL,'0','0',NULL,NULL,NULL,NULL,NULL,'0','0','0',NULL);

### Dump table glpi_userscategories

DROP TABLE IF EXISTS `glpi_userscategories`;
CREATE TABLE `glpi_userscategories` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `comment` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_userstitles

DROP TABLE IF EXISTS `glpi_userstitles`;
CREATE TABLE `glpi_userstitles` (
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

