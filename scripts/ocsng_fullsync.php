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
// Contributor: Goneri Le Bouder <goneri@rulezlan.org>
// Contributor: Walid Nouh <walid.nouh@gmail.com>
// Purpose of file:
// Installation:
// Add in your contabl (crontab -e):
// */2  *  *  *  *  root  /path/glpi/scripts/ocsng_fullsync.php -- ocs_server_id=X >>/dev/null 2>&1
// ----------------------------------------------------------------------
ini_set("memory_limit","-1");
ini_set("max_execution_time", "0");

# Converts cli parameter to web parameter for compatibility
if ($argv) {
	for ($i=1;$i<count($argv);$i++)
	{
		$it = split("=",$argv[$i]);
		$it[0] = eregi_replace('^--','',$it[0]);
		$_GET[$it[0]] = $it[1];
	}
}

// MASS IMPORT for OCSNG
define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");
include (GLPI_ROOT . "/config/based_config.php");

$thread_nbr='';
$thread_id='';
$NEEDED_ITEMS=array("ocsng","computer","device","printer","networking","peripheral","monitor","software","infocom","phone","tracking","enterprise","reservation","setup","rulesengine","rule.ocs","rule.softwarecategories","group","registry");
include (GLPI_ROOT."/inc/includes.php");

if (isset($_GET["thread_nbr"]) || isset($_GET["thread_id"])) {
	if (!isset($_GET["thread_id"]) || $_GET["thread_id"] > $_GET["thread_nbr"] || $_GET["thread_id"] < 0) {
		echo ("thread_id invalid: thread_id must be between 0 and thread_nbr\n");
		exit (1);
	}
	$thread_nbr=$_GET["thread_nbr"];
	$thread_id=$_GET["thread_id"];
	echo "Starting thread ($thread_id/$thread_nbr)> ";
}
else
{
	$thread_nbr=-1;
	$thread_id=-1;
}

if (isset($_GET["ocs_server_id"])) 
{
	if (checkOCSconnection($_GET["ocs_server_id"]))
	{
			$cfg_ocs=getOcsConf($_GET["ocs_server_id"]);
			echo "thread=".$thread_id. ", import computers from server: '".$cfg_ocs["name"]."'\n";
			ocsManageDeleted($_GET["ocs_server_id"]);
			importFromOcsServer($cfg_ocs,$thread_nbr, $thread_id);
	}
	else
		echo "thread=".$thread_id. ", cannot contact server\n";
}
else
{
	$query = "SELECT ID, name FROM glpi_ocs_config";
	$result = $DB->query($query);
	while ($ocs_server = $DB->fetch_array($result))
	{
		if (checkOCSconnection($ocs_server["ID"]))
		{
			$cfg_ocs=getOcsConf($ocs_server["ID"]);
			echo "thread=".$thread_id. ", import computers from OCS server: '".$ocs_server["name"]."'\n";
			ocsManageDeleted($ocs_server["ID"]);
			importFromOcsServer($cfg_ocs,$thread_nbr, $thread_id);
		}
		else
			echo "thread=".$thread_id. ", cannot contact server : ".$ocs_server["name"]."\n";
	}
}

echo "thread=".$thread_id." : done !!\n";

function importFromOcsServer($cfg_ocs, $thread_nbr, $thread_id)
{
	global $DBocs;
 
	$where_multi_thread = '';
	if ($thread_nbr != -1 && $thread_id != -1 && $thread_nbr > 1) {
		$where_multi_thread = " AND ID % $thread_nbr = ".($thread_id-1);
	}
	$query_ocs = "SELECT ID FROM hardware WHERE CHECKSUM&".intval($cfg_ocs["checksum"])." >0 $where_multi_thread";
	$result_ocs = $DBocs->query($query_ocs);
	while($data=$DBocs->fetch_array($result_ocs)){
		//echo "thread=".$thread_id.". machine=".$data['ID']."\n";
		echo ".";
		ocsProcessComputer($data["ID"],$cfg_ocs["ID"],1);
	}
}
?>
