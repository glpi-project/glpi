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


$NEEDED_ITEMS=array("search",
"user",
"computer",
"printer",
"monitor",
"peripheral",
"networking",
"software",
"phone",
"cartridge",
"consumable",
"stat",
"tracking",
"contract",
"infocom",
"enterprise",
"device",
"document",
"knowbase",
"group"
);


define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");


checkCentralAccess();

if (isset($_GET["item_type"])&&isset($_GET["display_type"])){


	if ($_GET["display_type"]<0) {
		$_GET["display_type"]=-$_GET["display_type"];
		$_GET["export_all"]=1;
	}

	// PDF case
	if ($_GET["display_type"]==PDF_OUTPUT){
		include (GLPI_ROOT . "/lib/ezpdf/class.ezpdf.php");
	}

	switch ($_GET["item_type"]){
		case KNOWBASE_TYPE :
			showKbItemList($_SERVER['PHP_SELF'],$_GET["field"],$_GET["phrasetype"],$_GET["contains"],$_GET["sort"],$_GET["order"],$_GET["start"],$_GET["parentID"],$_GET["faq"]);
			break;
		case TRACKING_TYPE :
			showTrackingList($_SERVER['PHP_SELF'],$_GET["start"],$_GET["sort"],$_GET["order"],$_GET["status"],$_GET["tosearch"],$_GET["search"],$_GET["author"],$_GET["group"],$_GET["showfollowups"],$_GET["category"],$_GET["assign"],$_GET["assign_ent"],$_GET["assign_group"],$_GET["priority"],$_GET["request_type"],$_GET["item"],$_GET["type"],$_GET["field"],$_GET["contains"],$_GET["date1"],$_GET["date2"],$_GET["only_computers"],$_GET["enddate1"],$_GET["enddate2"]);		
			break;
		case STAT_TYPE :

			if (isset($_GET["item_type_param"])){
				$params=unserialize(stripslashes($_GET["item_type_param"]));
				switch ($params["type"]){
					case "comp_champ":
						$val=getStatsItems($params["date1"],$params["date2"],$params["table"]);
					displayStats($params["type"],$params["field"],$params["date1"],$params["date2"],$params["start"],$val,$params["field"]);
					break;
					case "device":
						$val=getStatsItems($params["date1"],$params["date2"],$params["field"]);
					displayStats($params["type"],$params["field"],$params["date1"],$params["date2"],$params["start"],$val,$params["field"]);
					break;
					default:
					$val=getStatsItems($params["date1"],$params["date2"],$params["type"]);
					displayStats($params["type"],$params["field"],$params["date1"],$params["date2"],$params["start"],$val);
					break;
				}
			} else if (isset($_GET["type"])&&$_GET["type"]=="hardwares"){
				showItemStats("",$_GET["date1"],$_GET["date2"],$_GET['start']);
			}
			break;
		default :
			manageGetValuesInSearch($_GET["item_type"]);

			showList($_GET["item_type"],$_SERVER['PHP_SELF'],$_GET["field"],$_GET["contains"],$_GET["sort"],$_GET["order"],$_GET["start"],$_GET["deleted"],$_GET["link"],$_GET["distinct"],$_GET["link2"],$_GET["contains2"],$_GET["field2"],$_GET["type2"]);
			break;
	}
}
?>
