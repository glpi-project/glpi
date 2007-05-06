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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------


// MASS IMPORT for OCSNG

define('GLPI_ROOT', '..');

$NEEDED_ITEMS=array("ocsng","computer","device","printer","networking","peripheral","monitor","software","infocom","phone","tracking","enterprise","reservation","setup","group","entity","rulesengine","rule.ocs");
include (GLPI_ROOT."/inc/includes.php");



//// PARAMETERS
// Just import these ocs computer
$ocs_id=0;
$ocs_server_id=0;
if (isset($_GET["ocs_id"])) $ocs_id=$_GET["ocs_id"];
if (isset($_GET["ocs_server_id"])) $ocs_server_id=$_GET["ocs_server_id"];

// Limit import
$limit=0;
if (isset($_GET["limit"])) $limit=$_GET["limit"];
// all sync auto_update and not auto_update
$all=0;
if (isset($_GET["all"])) $all=$_GET["all"];


$DBocs = new DBocs($ocs_server_id);
$cfg_ocs=getOcsConf($ocs_server_id);
ocsManageDeleted($ocs_server_id);
$WHERE="";
if ($ocs_id) $WHERE=" AND ID='$ocs_id'";

$query_ocs = "SELECT * 
FROM hardware 
WHERE (CHECKSUM & ".$cfg_ocs["checksum"].") > 0
$WHERE";
$result_ocs = $DBocs->query($query_ocs);
if ($DBocs->numrows($result_ocs)>0){

	$hardware=array();
	while($data=$DBocs->fetch_array($result_ocs)){
		$hardware[$data["ID"]]["date"]=$data["LASTDATE"];
		$hardware[$data["ID"]]["name"]=addslashes($data["NAME"]);
	}
	$WHERE="WHERE auto_update= '1'";
	if ($all) $WHERE="";

	$query_glpi = "SELECT * 
		FROM glpi_ocs_link 
		$WHERE
		ORDER BY last_update";
	$result_glpi = $DB->query($query_glpi);
	$done=0;
	while($data=$DB->fetch_assoc($result_glpi)){
		$data=clean_cross_side_scripting_deep(addslashes_deep($data));

		if (isset($hardware[$data["ocs_id"]])){ 
			ocsUpdateComputer($data["ID"],$ocs_server_id,1);
			if ($limit&&$done>=$limit) exit();
			echo ".";
			$done++;
		}
	}
} 


?>
