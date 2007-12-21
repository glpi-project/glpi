<?php


/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2007 by the INDEPNET Development Team.

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
		
		$query="UPDATE glpi_profiles SET rule_dictionnary_software='w' WHERE name='super-admin'";
		$DB->query($query) or die("0.71 add rule_dictionnary_software right for profile super-admin " . $LANG["update"][90] . $DB->error());
	}

	if (!FieldExists("glpi_profiles", "rule_dictionnary_manufacturer")) {
		$query = "ALTER TABLE `glpi_profiles` ADD `rule_dictionnary_manufacturer` VARCHAR( 1 ) NULL DEFAULT NULL ;";
		$DB->query($query) or die("0.71 add rule_dictionnary_manufacturer in glpi_profiles if not present for compatibility " . $LANG["update"][90] . $DB->error());

		$query="UPDATE glpi_profiles SET rule_dictionnary_manufacturer='w' WHERE name='super-admin'";
		$DB->query($query) or die("0.71 add rule_dictionnary_manufacturer right for profile super-admin " . $LANG["update"][90] . $DB->error());
	}

	if (!FieldExists("glpi_profiles", "rule_dictionnary_model")) {
		$query = "ALTER TABLE `glpi_profiles` ADD `rule_dictionnary_model` VARCHAR( 1 ) NULL DEFAULT NULL ;";
		$DB->query($query) or die("0.71 add rule_dictionnary_model in glpi_profiles if not present for compatibility " . $LANG["update"][90] . $DB->error());

		$query="UPDATE glpi_profiles SET rule_dictionnary_model='w' WHERE name='super-admin'";
		$DB->query($query) or die("0.71 add rule_dictionnary_model right for profile super-admin " . $LANG["update"][90] . $DB->error());
	}

	if (!FieldExists("glpi_profiles", "rule_dictionnary_type")) {
		$query = "ALTER TABLE `glpi_profiles` ADD `rule_dictionnary_type` VARCHAR( 1 ) NULL DEFAULT NULL ;";
		$DB->query($query) or die("0.71 add rule_dictionnary_type in glpi_profiles if not present for compatibility " . $LANG["update"][90] . $DB->error());

		$query="UPDATE glpi_profiles SET rule_dictionnary_type='w' WHERE name='super-admin'";
		$DB->query($query) or die("0.71 add rule_dictionnary_type right for profile super-admin " . $LANG["update"][90] . $DB->error());
	}

	if (!FieldExists("glpi_profiles", "rule_dictionnary_os")) {
		$query = "ALTER TABLE `glpi_profiles` ADD `rule_dictionnary_os` VARCHAR( 1 ) NULL DEFAULT NULL ;";
		$DB->query($query) or die("0.71 add rule_dictionnary_os in glpi_profiles if not present for compatibility " . $LANG["update"][90] . $DB->error());

		$query="UPDATE glpi_profiles SET rule_dictionnary_os='w' WHERE name='super-admin'";
		$DB->query($query) or die("0.71 add rule_dictionnary_os right for profile super-admin " . $LANG["update"][90] . $DB->error());
	}

	$cache_tables = array("glpi_rule_cache_manufacturer",
							"glpi_rule_cache_model_computer",
							"glpi_rule_cache_model_monitor",
							"glpi_rule_cache_model_printer",
							"glpi_rule_cache_model_peripheral",
							"glpi_rule_cache_type_computer",
							"glpi_rule_cache_type_monitor",
							"glpi_rule_cache_type_printer",
							"glpi_rule_cache_type_peripheral",
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
			`old_value` VARCHAR( 255 ) NOT NULL ,
			`rule_id` INT( 11 ) NOT NULL ,
			`new_value` VARCHAR( 255 ) NOT NULL ,
			PRIMARY KEY ( `ID` ),
			KEY `rule_id` (`rule_id`),
			KEY `old_value` (`old_value`)
			) ENGINE = MYISAM DEFAULT CHARSET=utf8;";
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
		$DB->query($query) or die("0.71 add version in glpi_rule_cache_software if not present " . $LANG["update"][90] . $DB->error());
	}

	$model_cache_tables = array("glpi_rule_cache_model_computer",
							"glpi_rule_cache_model_monitor",
							"glpi_rule_cache_model_printer",
							"glpi_rule_cache_model_peripheral",
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
	  `ldap_host` varchar(255) NOT NULL,
	  `ldap_port` int(11) NOT NULL default '389',
	  `name` varchar(255) NOT NULL,
	  PRIMARY KEY  (`ID`)
	) ENGINE=MyISAM  DEFAULT CHARSET=latin1;";
		$DB->query($query) or die("0.71 add table glpi_auth_ldap_replicate " . $LANG["update"][90] . $DB->error());
	}	

	if (!TableExists("glpi_db_replicate")) {
		$query = " CREATE TABLE `glpi_db_replicate` (`ID` INT( 11 ) NOT NULL ,
		`notify_db_desynchronization` INT( 1 ) NOT NULL DEFAULT '0',
		`admin_email` VARCHAR( 255 ) NOT NULL,
		`max_delay` INT( 11 ) NOT NULL DEFAULT '3600',
		PRIMARY KEY  (`ID`)
	    ) ENGINE = MYISAM";
		$DB->query($query) or die("0.71 add table glpi_db_replicate if not present " . $LANG["update"][90] . $DB->error());
	
		$query = "INSERT INTO `glpi_db_replicate` (`ID`, `notify_db_desynchronization`, `admin_email`, `max_delay`) VALUES
		(1, 1, 'admsys@xxxxx.fr', 3600);";
		$DB->query($query) or die("0.71 add values in glpi_db_replicate  " . $LANG["update"][90] . $DB->error());
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

} // fin 0.71 #####################################################################################
?>