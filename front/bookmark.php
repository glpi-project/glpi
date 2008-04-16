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
// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------

/*
$NEEDED_ITEMS=array("bookmark");

if(!defined('GLPI_ROOT')){
	define('GLPI_ROOT', '..');
}
include (GLPI_ROOT . "/inc/includes.php");

if (!ereg("popup",$_SERVER['PHP_SELF']))
	commonHeader($LANG["rulesengine"][17],$_SERVER['PHP_SELF'],"admin","dictionnary","cache");
*/

if(!isset($_GET["ID"])) {
	$_GET["ID"] = -1;
}

if (!isset($_SESSION['glpi_viewbookmark'])) $_SESSION['glpi_viewbookmark']=1;
if (isset($_GET['onglet'])) $_SESSION['glpi_viewbookmark']=$_GET['onglet'];

if(!isset($_GET["type"])) {
	$_GET["type"] = -1;
}
$bookmark = new Bookmark;

if (isset($_POST["add"])){
	$bookmark->add($_POST);
	$_GET["action"]="load";
} elseif (isset($_POST["update"])){
	$bookmark->update($_POST);
	$_GET["action"]="load";
} elseif (isset($_POST["delete"])){
	$bookmark->delete($_POST);
	$_GET["action"]="load";
}elseif (isset($_POST["delete_several"])){
	foreach ($_POST["bookmark"] as $ID=>$value){
		if ($bookmark->can($ID,'w')){
			$bookmark->delete(array("ID"=>$ID));
		}
	}
	$_GET["action"]="load";
}

switch($_GET["action"]){
	case "edit" :
		if ($_GET['ID']>0){
			$bookmark->showForm($_SERVER['PHP_SELF'],$_GET["ID"]);
		} else  {
			$bookmark->showForm($_SERVER['PHP_SELF'],$_GET["ID"],$_GET["type"],rawurldecode($_GET["url"]),$_GET["device_type"]);	
		}
		break;
	case "load" :
		if (isset($_GET["bookmark_id"])){
			$bookmark->load($_GET["bookmark_id"]);
		}
		$bookmark->showBookmarkList($_SERVER['PHP_SELF'],$_SESSION["glpi_viewbookmark"]);
		break;
}
		
if (!ereg("popup",$_SERVER['PHP_SELF'])){
	commonFooter();
}
?>
