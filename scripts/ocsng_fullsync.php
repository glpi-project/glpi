<?php
/*
 * @version $Id: ocsng_mass_sync.php 4213 2006-12-25 19:56:49Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.

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
// Original Author of file: Julien Dombre
// Contributor: Goneri Le Bouder <goneri@rulezlan.org>
// Purpose of file:
// ----------------------------------------------------------------------


// MASS IMPORT for OCSNG

define('GLPI_ROOT', '..');
$USE_OCSNGDB=1;
$NEEDED_ITEMS=array("ocsng","computer","device","printer","networking","peripheral","monitor","software","infocom","phone","tracking","enterprise","reservation","setup","rulesengine","rule.ocs","group");
include (GLPI_ROOT."/inc/includes.php");

$ocs_server_id=0;
if (isset($_GET["ocs_server_id"])) $ocs_server_id=$_GET["ocs_server_id"];


$cfg_ocs=getOcsConf($ocs_server_id);
ocsManageDeleted($ocs_server_id);

$query_ocs = "SELECT ID FROM hardware WHERE CHECKSUM&".intval($cfg_ocs["checksum"])." >0";
$result_ocs = $DBocs->query($query_ocs);

# Feed the list of ocs IDs to sync
$ocsMachinesToSync=array();
while($data=$DBocs->fetch_array($result_ocs)){
	$ocsMachinesToSync[$data["ID"]] = 1;
	ocsImportComputer($data["ID"],$ocs_server_id);
	echo ".";
}
echo "done";

?>
