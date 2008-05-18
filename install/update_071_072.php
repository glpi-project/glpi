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

/// Update from 0.71 to 0.72
function update071to072() {
	global $DB, $CFG_GLPI, $LANG, $LINK_ID_TABLE;

 	if (!FieldExists("glpi_networking", "recursive")) {
 		$query = "ALTER TABLE `glpi_networking` ADD `recursive` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `FK_entities`;";
 		$DB->query($query) or die("0.72 add recursive in glpi_networking" . $LANG["update"][90] . $DB->error());
 	}	  	

	// Clean datetime fields
	$date_fields=array('glpi_docs.date_mod',
			'glpi_event_log.date',
			'glpi_infocoms.buy_date',
			'glpi_infocoms.use_date',
			'glpi_monitors.date_mod',
			'glpi_networking.date_mod',
			'glpi_ocs_link.last_update',
			'glpi_peripherals.date_mod',
			'glpi_phones.date_mod',
			'glpi_printers.date_mod',
			'glpi_reservation_resa.begin',
			'glpi_reservation_resa.end',
			'glpi_tracking.closedate',
			'glpi_tracking_planning.begin',
			'glpi_tracking_planning.end',
			'glpi_users.last_login',
			'glpi_users.date_mod',
	);
	foreach ($date_fields as $tablefield){
		list($table,$field)=explode('.',$tablefield);
		if (FieldExists($table, $field)) {
			$query = "ALTER TABLE `$table` CHANGE `$field` `$field` DATETIME NULL;";
 			$DB->query($query) or die("0.72 alter $field in $table" . $LANG["update"][90] . $DB->error());
		}
	}
	$date_fields[]="glpi_computers.date_mod";
	$date_fields[]="glpi_followups.date";
	$date_fields[]="glpi_history.date_mod";
	$date_fields[]="glpi_kbitems.date";
	$date_fields[]="glpi_kbitems.date_mod";
	$date_fields[]="glpi_ocs_config.date_mod";
	$date_fields[]="glpi_ocs_link.last_ocs_update";
	$date_fields[]="glpi_reminder.date";
	$date_fields[]="glpi_reminder.begin";
	$date_fields[]="glpi_reminder.end";
	$date_fields[]="glpi_reminder.date_mod";
	$date_fields[]="glpi_software.date_mod";
	$date_fields[]="glpi_tracking.date";
	$date_fields[]="glpi_tracking.date_mod";
	$date_fields[]="glpi_type_docs.date_mod";

	foreach ($date_fields as $tablefield){
		list($table,$field)=explode('.',$tablefield);
		if (FieldExists($table, $field)) {
			$query = "UPDATE `$table` SET `$field` = NULL WHERE `$field` ='0000-00-00 00:00:00';";
 			$DB->query($query) or die("0.72 update data of $field in $table" . $LANG["update"][90] . $DB->error());
		}
	}
	

} // fin 0.72 #####################################################################################
?>