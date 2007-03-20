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
// Installation:
// Add in your contabl (crontab -e):
// */2  *  *  *  *  root  /path/glpi/scripts/ocsng_fullsync.php -- ocs_server_id=X -- lockfile='/tmp/lock' >>/dev/null 2>&1
// lockfile is optional is your user can write in the file/_cron directory
// ----------------------------------------------------------------------
ini_set("memory_limit","-1");
ini_set("max_execution_time", "0");

// MASS IMPORT for OCSNG
define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");
include (GLPI_ROOT . "/config/based_config.php");

/*
if (isset($_GET["lockfile"])){
	$lockfile=$_GET["lockfile"];
	} else {
	$lockfile = GLPI_CRON_DIR. '/' . 'ocsng_fullsync.lock';
}
$lock = fopen($lockfile, "w+",4);
flock($lock, LOCK_EX|LOCK_NB) or die("Error getting lock!");
*/

$USE_OCSNGDB=1;
$NEEDED_ITEMS=array("ocsng","computer","device","printer","networking","peripheral","monitor","software","infocom","phone","tracking","enterprise","reservation","setup","rulesengine","rule.ocs","group");
include (GLPI_ROOT."/inc/includes.php");

if (isset($_GET["ocs_server_id"])) 
{
	checkOCSconnection($_GET["ocs_server_id"]);
	echo "import computers from server : ".$_GET["ocs_server_id"]."\n";
	importFromOcsServer($_GET["ocs_server_id"]);
}
else
{
	$query = "SELECT ID, name FROM glpi_ocs_config";
	$result = $DB->query($query);
	while ($ocs_server = $DB->fetch_array($result))
	{
		checkOCSconnection($ocs_server["ID"]);
		echo "import computers from server : ".$ocs_server["name"]."\n";
		importFromOcsServer($ocs_server["ID"]);
	}
}

/*
flock($lock, LOCK_UN);
fclose($lock);
unlink($lockfile);
*/
echo "done\n";

function importFromOcsServer($ocs_server_id)
{
	global $DBocs;
	$cfg_ocs=getOcsConf($ocs_server_id);
	ocsManageDeleted($ocs_server_id);

	$query_ocs = "SELECT ID FROM hardware WHERE CHECKSUM&".intval($cfg_ocs["checksum"])." >0";
	$result_ocs = $DBocs->query($query_ocs);
	
			while($data=$DBocs->fetch_array($result_ocs)){
				ocsImportComputer($data["ID"],$ocs_server_id);
				echo ".";
			}
}
?>
