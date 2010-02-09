<?php


/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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

/// Update from 0.71.2 to 0.71.3
function update0712to0713() {
	global $DB, $CFG_GLPI, $LANG;

	if (!FieldExists("glpi_rule_cache_software", "ignore_ocs_import")) {
		$query = "ALTER TABLE `glpi_rule_cache_software` ADD `ignore_ocs_import` VARCHAR( 255 ) NULL ;";
		$DB->query($query) or die("0.71.3 add ignore_ocs_import field in dictionnary cache " . $LANG['update'][90] . $DB->error());
	}
	
	// Update to longtext for fields which may be very long
	if (FieldExists("glpi_kbitems", "answer")) {
		
		if (isIndex("glpi_kbitems","fulltext")){ // to avoid pb in altering column answer 		 
			$query = "ALTER TABLE `glpi_kbitems` DROP INDEX `fulltext`";
			$DB->query($query) or die("0.71.3 alter kbitem drop index Fulltext " . $LANG['update'][90] . $DB->error());
		}  
		$query = "ALTER TABLE `glpi_kbitems` CHANGE `answer` `answer` LONGTEXT NULL DEFAULT NULL  ";
		$DB->query($query) or die("0.71.3 alter kbitem answer field to longtext " . $LANG['update'][90] . $DB->error());
		
		$query = "ALTER TABLE `glpi_kbitems` ADD FULLTEXT `fulltext` (`question`,`answer`)";
		$DB->query($query) or die("0.71.3 alter kbitem re-add index Fulltext " . $LANG['update'][90] . $DB->error());
		
	}
	if (FieldExists("glpi_tracking", "contents")) {
		$query = "ALTER TABLE `glpi_tracking` CHANGE `contents` `contents` LONGTEXT NULL DEFAULT NULL  ";
		$DB->query($query) or die("0.71.3 alter tracking contents field to longtext " . $LANG['update'][90] . $DB->error());
	}
		
	
} 
?>
