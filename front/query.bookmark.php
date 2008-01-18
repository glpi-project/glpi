<?php
/*
 * @version $Id: rule.cache.php 6282 2008-01-04 21:28:59Z moyo $
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


$NEEDED_ITEMS=array("query.bookmark");

if(!defined('GLPI_ROOT')){
	define('GLPI_ROOT', '..');
}
include (GLPI_ROOT . "/inc/includes.php");

if (!ereg("popup",$_SERVER['PHP_SELF']))
	commonHeader($LANG["rulesengine"][17],$_SERVER['PHP_SELF'],"admin","dictionnary","cache");

$query_bookmark = new QueryBookmark;

if (isset($_POST["save"]))
{
	$_POST = exportSearchParameters($_POST,$_SESSION["glpisearch"][$_POST["type"]]);
	$query_bookmark->add($_POST);
	$query_bookmark->showQuerySavedForm();	
}elseif (isset($_POST["load"]))
{
	$query_bookmark->getFromDB($_POST["ID"]);
	$url=buildRequestUrl($query_bookmark->fields);
	$query_bookmark->showQueryLoadedForm($url);
}elseif (isset($_POST["delete"]))
{
	$query_bookmark->delete($_POST);
	$query_bookmark->showLoadQueryForm($_SERVER['PHP_SELF'],$_POST["type"],$_SESSION["glpiID"]);
}
else
{
	if ($_GET["action"] == "save")
		$query_bookmark->showSaveQueryForm($_SERVER['PHP_SELF'],$_GET["type"],$_SESSION["glpiID"]);	
	else
		$query_bookmark->showLoadQueryForm($_SERVER['PHP_SELF'],$_GET["type"],$_SESSION["glpiID"]);
		
	if (!ereg("popup",$_SERVER['PHP_SELF']))
		commonFooter();
}
?>
