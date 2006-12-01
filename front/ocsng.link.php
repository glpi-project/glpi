<?php
/*
 * @version $Id$
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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
$USE_OCSNGDB=1;
$NEEDED_ITEMS=array("ocsng","computer","device","printer","networking","peripheral","monitor","software","infocom","phone","state","tracking","enterprise","reservation");
include ($phproot . "/inc/includes.php");

checkRight("ocsng","w");

commonHeader($lang["title"][39],$_SERVER['PHP_SELF']);

$cfg_glpi["use_ajax"] = 1; 

if (isset($_SESSION["ocs_link"])){
	if ($count=count($_SESSION["ocs_link"])){
		$percent=min(100,round(100*($_SESSION["ocs_link_count"]-$count)/$_SESSION["ocs_link_count"],0));

		displayProgressBar(400,$percent);

		$key=array_pop($_SESSION["ocs_link"]);
		ocsLinkComputer($key["ocs_id"],$key["glpi_id"]);
		glpi_header($_SERVER['PHP_SELF']);
	} else {
		displayProgressBar(400,100);

		unset($_SESSION["ocs_link"]);
		echo "<div align='center'><strong>".$lang["ocsng"][8]."<br>";
		echo "<a href='".$_SERVER['PHP_SELF']."'>".$lang["buttons"][13]."</a>";
		echo "</strong></div>";
	}
}



if (!isset($_POST["import_ok"])){
	if (!isset($_GET['check'])) $_GET['check']='all';
	if (!isset($_GET['start'])) $_GET['start']=0;

	ocsManageDeleted();
	ocsCleanLinks();
	ocsShowNewComputer($_GET['check'],$_GET['start'],1);

} else {
	if (count($_POST['tolink'])>0){
		$_SESSION["ocs_link_count"]=0;
		foreach ($_POST['tolink'] as $ocs_id => $glpi_id){
			if ($glpi_id>0){
				$_SESSION["ocs_link"][]=array("ocs_id"=>$ocs_id,
						"glpi_id"=>$glpi_id);
				$_SESSION["ocs_link_count"]++;
			}
		}
	}
	glpi_header($_SERVER['PHP_SELF']);
}


commonFooter();

?>
