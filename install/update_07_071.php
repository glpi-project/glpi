<?php


/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

// Update from 0.7 to 0.71
function update07to071() {
	global $DB, $CFG_GLPI, $LANG, $LINK_ID_TABLE;

	@mysql_query("SET NAMES 'latin1'",$DB->dbh);


	if (!FieldExists("glpi_profiles", "rule_dictionnary_software")) {
		$query = "ALTER TABLE `glpi_profiles` ADD `rule_dictionnary_software` VARCHAR( 1 ) NULL DEFAULT NULL;";
		$DB->query($query) or die("0.71 add rule_dictionnary_software in glpi_profiles if not present for compatibility " . $LANG["update"][90] . $DB->error());
		
		$query="UPDATE glpi_profiles SET rule_dictionnary_software=rule_softwarecategories";
		$DB->query($query) or die("0.71 update value of rule_dictionnary_software right " . $LANG["update"][90] . $DB->error());
	}

	if (!FieldExists("glpi_profiles", "rule_dictionnary_dropdown")) {
		$query = "ALTER TABLE `glpi_profiles` ADD `rule_dictionnary_dropdown` VARCHAR( 1 ) NULL DEFAULT NULL;";
		$DB->query($query) or die("0.71 add rule_dictionnary_dropdown in glpi_profiles " . $LANG["update"][90] . $DB->error());

		$query="UPDATE glpi_profiles SET rule_dictionnary_dropdown=rule_dictionnary_software";
		$DB->query($query) or die("0.71 update value of rule_dictionnary_dropdown" . $LANG["update"][90] . $DB->error());
	}


	$cache_tables = array("glpi_rule_cache_manufacturer",
							"glpi_rule_cache_model_computer",
							"glpi_rule_cache_model_monitor",
							"glpi_rule_cache_model_printer",
							"glpi_rule_cache_model_peripheral",
							"glpi_rule_cache_model_phone",
							"glpi_rule_cache_model_networking",
							"glpi_rule_cache_type_computer",
							"glpi_rule_cache_type_monitor",
							"glpi_rule_cache_type_printer",
							"glpi_rule_cache_type_peripheral",
							"glpi_rule_cache_type_phone",
							"glpi_rule_cache_type_networking",
							"glpi_rule_cache_software",
							"glpi_rule_cache_os",
							"glpi_rule_cache_os_sp",
							"glpi_rule_cache_os_version"							
							);

	foreach ($cache_tables as $cache_table)
	{

		if (!TableExists($cache_table)) {
			$query = "CREATE TABLE `".$cache_table."` (
			`ID` INT( 11 ) NOT NULL auto_increment ,
			`old_value` VARCHAR( 255 ) NULL default NULL ,
			`rule_id` INT( 11 ) NOT NULL DEFAULT '0',
			`new_value` VARCHAR( 255 ) NULL default NULL ,
			PRIMARY KEY ( `ID` ),
			KEY `rule_id` (`rule_id`),
			KEY `old_value` (`old_value`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
			$DB->query($query) or die("0.71 add table ".$cache_table." " . $LANG["update"][90] . $DB->error());
		}
		
	}
	
	//Add the field version espacially for the software's cache
	if (!FieldExists("glpi_rule_cache_software", "version")) {
		$query = "ALTER TABLE `glpi_rule_cache_software` ADD `version` VARCHAR( 255 ) DEFAULT NULL ;";
		$DB->query($query) or die("0.71 add version in glpi_rule_cache_software if not present " . $LANG["update"][90] . $DB->error());
	}
	if (!FieldExists("glpi_rule_cache_software", "manufacturer")) {
		$query = "ALTER TABLE `glpi_rule_cache_software` ADD `manufacturer` VARCHAR( 255 ) NOT NULL AFTER `old_value` ;";
		$DB->query($query) or die("0.71 add manufacturer in glpi_rule_cache_software if not present " . $LANG["update"][90] . $DB->error());
	}
	if (!FieldExists("glpi_rule_cache_software", "new_manufacturer")) {
		$query = "ALTER TABLE `glpi_rule_cache_software` ADD `new_manufacturer` VARCHAR( 255 ) NOT NULL AFTER `version` ;";
		$DB->query($query) or die("0.71 add new_manufacturer in glpi_rule_cache_software if not present " . $LANG["update"][90] . $DB->error());
	}

	$model_cache_tables = array("glpi_rule_cache_model_computer",
							"glpi_rule_cache_model_monitor",
							"glpi_rule_cache_model_printer",
							"glpi_rule_cache_model_peripheral",
							"glpi_rule_cache_model_phone",
							"glpi_rule_cache_model_networking",
							);

	foreach ($model_cache_tables as $model_cache_table)
	{
		if (!FieldExists($model_cache_table, "manufacturer")) {
			$query = "ALTER TABLE `".$model_cache_table."` ADD `manufacturer` VARCHAR( 255 ) DEFAULT NULL ;";
			$DB->query($query) or die("0.71 add manufacturer in ".$model_cache_table." if not present " . $LANG["update"][90] . $DB->error());
		}
	}	

	if (!FieldExists("glpi_rules_descriptions", "active")) {
		$query = "ALTER TABLE `glpi_rules_descriptions` ADD `active` INT( 1 ) NOT NULL DEFAULT '1';";
		$DB->query($query) or die("0.71 add active in glpi_rules_descriptions if not present " . $LANG["update"][90] . $DB->error());
	}

	if (!TableExists("glpi_auth_ldap_replicate")) {
	$query="CREATE TABLE IF NOT EXISTS `glpi_auth_ldap_replicate` (
	  `ID` int(11) NOT NULL auto_increment,
	  `server_id` int(11) NOT NULL default '0',
	  `ldap_host` varchar(255) NULL default NULL,
	  `ldap_port` int(11) NOT NULL default '389',
	  `name` varchar(255) NULL default NULL,
	  PRIMARY KEY  (`ID`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		$DB->query($query) or die("0.71 add table glpi_auth_ldap_replicate " . $LANG["update"][90] . $DB->error());
	}	

	if (!FieldExists("glpi_config","dbreplicate_notify_desynchronization")) {
		$query = "ALTER TABLE `glpi_config` ADD `dbreplicate_notify_desynchronization` SMALLINT NOT NULL DEFAULT '0',
				ADD `dbreplicate_email` VARCHAR( 255 ) NULL ,
				ADD `dbreplicate_maxdelay` INT NOT NULL DEFAULT '3600';";

		$DB->query($query) or die("0.71 alter config add config for dbreplicate notif " . $LANG["update"][90] . $DB->error());
	}

 	if (!FieldExists("glpi_reminder", "recursive")) {
 		$query = "ALTER TABLE `glpi_reminder` ADD `recursive` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `type`;";
 		$DB->query($query) or die("0.71 add recursive in glpi_reminder" . $LANG["update"][90] . $DB->error());
 	}	  	

	if (!isIndex("glpi_ocs_link", "last_ocs_update")) {
		$query = "ALTER TABLE `glpi_ocs_link` ADD INDEX `last_ocs_update` ( `ocs_server_id` , `last_ocs_update` )";
		$DB->query($query) or die("0.7 alter ocs_link add index on last_ocs_update " . $LANG["update"][90] . $DB->error());
	}
 	
 	if (!FieldExists("glpi_contacts", "recursive")) {
 		$query = "ALTER TABLE `glpi_contacts` ADD `recursive` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `FK_entities`;";
 		$DB->query($query) or die("0.71 add recursive in glpi_contacts" . $LANG["update"][90] . $DB->error());
 	}	  	
 	if (!FieldExists("glpi_contracts", "recursive")) {
 		$query = "ALTER TABLE `glpi_contracts` ADD `recursive` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `FK_entities`;";
 		$DB->query($query) or die("0.71 add recursive in glpi_contracts" . $LANG["update"][90] . $DB->error());
 	}	  	
 	if (!FieldExists("glpi_enterprises", "recursive")) {
 		$query = "ALTER TABLE `glpi_enterprises` ADD `recursive` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `FK_entities`;";
 		$DB->query($query) or die("0.71 add recursive in glpi_enterprises" . $LANG["update"][90] . $DB->error());
 	}	  	
 	if (!FieldExists("glpi_docs", "recursive")) {
 		$query = "ALTER TABLE `glpi_docs` ADD `recursive` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `FK_entities`;";
 		$DB->query($query) or die("0.71 add recursive in glpi_docs" . $LANG["update"][90] . $DB->error());
 	}	  	
 	if (!FieldExists("glpi_monitors", "flags_pivot")) {
 		$query = "ALTER TABLE `glpi_monitors` ADD `flags_pivot` SMALLINT( 6 ) NOT NULL DEFAULT 0 AFTER `flags_dvi`;";
 		$DB->query($query) or die("0.71 add flags_pivot in glpi_monitors" . $LANG["update"][90] . $DB->error());
 	}	  	

 	if (!FieldExists("glpi_kbitems", "FK_entities")) {
 		$query = "ALTER TABLE `glpi_kbitems` ADD `FK_entities` INT(11) NOT NULL DEFAULT 0 AFTER `ID`;";
 		$DB->query($query) or die("0.71 add FK_entities in glpi_kbitems" . $LANG["update"][90] . $DB->error());
 	}	  	
 	if (!FieldExists("glpi_kbitems", "recursive")) {
 		// Default 1 for migration. All articles become "global" (root + recursive)
 		$query = "ALTER TABLE `glpi_kbitems` ADD `recursive` TINYINT(1) NOT NULL DEFAULT 1 AFTER `FK_entities`;";
 		$DB->query($query) or die("0.71 add recursive in glpi_kbitems" . $LANG["update"][90] . $DB->error());
 	}	  	
	if (!isIndex("glpi_kbitems", "FK_entities")) {
		$query = "ALTER TABLE `glpi_kbitems` ADD INDEX `FK_entities` (`FK_entities`)";
		$DB->query($query) or die("0.7 alter ocs_link add index on last_ocs_update " . $LANG["update"][90] . $DB->error());
	}

 	if (!FieldExists("glpi_config", "category_on_software_delete")) {
 		$query = "ALTER TABLE `glpi_config` ADD `category_on_software_delete` INT( 11 ) NOT NULL DEFAULT '0';";
 		$DB->query($query) or die("0.71 add category_on_software_delete in glpi_config" . $LANG["update"][90] . $DB->error());
		
		//Create a software category for softwares to be deleted by the dictionnary
	 	$result = $DB->query("SELECT ID FROM glpi_dropdown_software_category WHERE name='".$LANG["rulesengine"][94]."'");
	 	if (!$DB->numrows($result))
	 	{
	 		$DB->query("INSERT INTO glpi_dropdown_software_category SET name='".$LANG["rulesengine"][94]."'");
	 		$result = $DB->query("SELECT ID FROM glpi_dropdown_software_category WHERE name='".$LANG["rulesengine"][94]."'");
	 	}
	 	$cat_id = $DB->result($result,0,"ID");

		$DB->query("UPDATE glpi_config SET category_on_software_delete=".$cat_id);
 	}	  	

	$query="DELETE FROM glpi_display WHERE num='121'";
	$DB->query($query) or die("0.71 clean glpi_display for end_warranty infocoms " . $DB->error());

	// Delete helpdesk injector user
	$query="DELETE FROM glpi_users WHERE ID='1'";
	$DB->query($query) or die("0.71 delete helpdesk injector user " . $DB->error());
	// Delete helpdesk injector user
	$query="DELETE FROM glpi_users_profiles WHERE FK_users='1'";
	$DB->query($query) or die("0.71 delete helpdesk injector user profile " . $DB->error());
	// change default device type for tracking
	if (FieldExists("glpi_tracking", "device_type")) {
		$query=" ALTER TABLE `glpi_tracking` CHANGE `device_type` `device_type` INT( 11 ) NOT NULL DEFAULT '0' ";
		$DB->query($query) or die("0.71 alter device_type from glpi_tracking " . $DB->error());		
	}

	// Change ldap condition field bigger
	if (FieldExists("glpi_auth_ldap", "ldap_condition")) {
 	
		$query="ALTER TABLE `glpi_auth_ldap` CHANGE `ldap_condition` `ldap_condition` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL   ";
		$DB->query($query) or die("0.71 alter change ldap_condition field to be bigger " . $DB->error());		
	}

	// Add date_mod to glpi_tracking
	if (!FieldExists("glpi_tracking", "date_mod")) {
		$query="ALTER TABLE `glpi_tracking` ADD `date_mod` DATETIME NULL DEFAULT NULL AFTER `closedate` ;";
		$DB->query($query) or die("0.71 alter glpi_tracking add date_mod" . $DB->error());		
		$query="UPDATE `glpi_tracking` SET `date_mod` = date;";
		$DB->query($query) or die("0.71 alter glpi_tracking update date_mod value to creation date" . $DB->error());		

	}

	// Add number format
	if (!FieldExists("glpi_config", "numberformat")) {
		$query="ALTER TABLE `glpi_config` ADD `numberformat` SMALLINT NOT NULL DEFAULT '0' AFTER `dateformat` ;";
		$DB->query($query) or die("0.71 alter config add numberformat" . $DB->error());		
	}
	// Add group supervisor
	if (!FieldExists("glpi_groups", "FK_users")) {
		$query="ALTER TABLE `glpi_groups` ADD `FK_users` INT NOT NULL DEFAULT '0' AFTER `comments` ;";
		$DB->query($query) or die("0.71 alter groups add FK_users supervisor" . $DB->error());		
	}

	// Add group supervisor
	if (!FieldExists("glpi_entities_data", "admin_email")) {
		$query="ALTER TABLE `glpi_entities_data` ADD `admin_email` VARCHAR( 255 ) NULL AFTER `email` ;";
		$DB->query($query) or die("0.71 alter entities_data add admin_email " . $DB->error());		
	}

	// Add cas ldap server link
	if (!FieldExists("glpi_config", "extra_ldap_server")) {
		$query="ALTER TABLE `glpi_config` ADD `extra_ldap_server` INT NOT NULL DEFAULT '1' AFTER `cas_logout` ;";
		$DB->query($query) or die("0.71 alter config add extra_ldap_server" . $DB->error());		
	}

	// Add x509 email field definition
	if (!FieldExists("glpi_config", "x509_email_field")) {
		$query="ALTER TABLE `glpi_config` ADD `x509_email_field` VARCHAR( 255 ) NULL;";
		$DB->query($query) or die("0.71 alter config add x509_email_field" . $DB->error());		
	}

	// Add x509 email field definition
	if (!FieldExists("glpi_config", "existing_auth_server_field")) {
		$query="ALTER TABLE `glpi_config` ADD `existing_auth_server_field` VARCHAR( 255 ) NULL  AFTER `extra_ldap_server`;";
		$DB->query($query) or die("0.71 alter config add existing_auth_server_field" . $DB->error());		
	}

	// update cas auth field from 0 -> 5
	$query="UPDATE `glpi_users` SET `auth_method`=5 WHERE `auth_method`=0;";
	$DB->query($query) or die("0.71 update auth method for CAS " . $DB->error());		
	
	if (!TableExists("glpi_query_bookmark"))
	{
	 	$query="CREATE TABLE IF NOT EXISTS `glpi_query_bookmark` (
		  `ID` int(11) NOT NULL auto_increment,
		  `name` varchar(255) NOT NULL,
		  `FK_user` int(11) NOT NULL,
		  `type` int(11) NOT NULL,
		  `params` text NOT NULL,
		  `link` text NOT NULL,
		  `field` text NOT NULL,
		  `contains` text NOT NULL,
		  `link2` text NOT NULL,
		  `field2` text NOT NULL,
		  `contains2` text NOT NULL,
		  PRIMARY KEY  (`ID`),
		  KEY `FK_user` (`FK_user`,`type`)
		) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=19 ;";
		$DB->query($query) or die("0.71 add table glpi_query_bookmark " . $DB->error());		
	}
} // fin 0.71 #####################################################################################
?>