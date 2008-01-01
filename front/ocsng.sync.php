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


$NEEDED_ITEMS=array("ocsng","rulesengine","rule.ocs","computer","device","printer","networking","peripheral","monitor","software","infocom","phone","tracking","enterprise","reservation","setup","registry","admininfo","group","rule.softwarecategories","rule.dictionnary.software","rule.dictionnary.manufacturer",
"rule.dictionnary.model","rule.dictionnary.type");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkRight("ocsng","w");

commonHeader($LANG["title"][39],$_SERVER['PHP_SELF'],"utils","ocsng");

if (isset($_SESSION["ocs_update"])){	
	if ($count=count($_SESSION["ocs_update"])){
		$percent=min(100,round(100*($_SESSION["ocs_update_count"]-$count)/$_SESSION["ocs_update_count"],0));

		displayProgressBar(400,$percent);

		$key=array_pop($_SESSION["ocs_update"]);
		ocsUpdateComputer($key,$_SESSION["ocs_server_id"],2);
		glpi_header($_SERVER['PHP_SELF']);
	} else {
		unset($_SESSION["ocs_update"]);

		displayProgressBar(400,100);

		echo "<div align='center'><strong>".$LANG["ocsng"][8]."<br>";
		echo "<a href='".$_SERVER['PHP_SELF']."'>".$LANG["buttons"][13]."</a>";
		echo "</strong></div>";
	}
}


if (!isset($_POST["update_ok"])){	
	if (!isset($_GET['check'])) $_GET['check']='all';
	if (!isset($_GET['start'])) $_GET['start']=0;

	ocsManageDeleted($_SESSION["ocs_server_id"]);
	ocsShowUpdateComputer($_SESSION["ocs_server_id"],$_GET['check'],$_GET['start']);

} else {
	if (count($_POST['toupdate'])>0){		
		$_SESSION["ocs_update_count"]=0;
		foreach ($_POST['toupdate'] as $key => $val){
			if ($val=="on")	{
				$_SESSION["ocs_update"][]=$key;
				$_SESSION["ocs_update_count"]++;
			}

		}
	}

	glpi_header($_SERVER['PHP_SELF']);
}


commonFooter();

?>
