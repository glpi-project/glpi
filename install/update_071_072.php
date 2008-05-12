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
 		$DB->query($query) or die("0.71 add recursive in glpi_networking" . $LANG["update"][90] . $DB->error());
 	}	  	

} // fin 0.72 #####################################################################################
?>