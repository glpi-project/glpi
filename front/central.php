<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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

define('GLPI_ROOT', '..');
$NEEDED_ITEMS=array("central","tracking","computer","printer","monitor","peripheral","networking","software","user","group","setup","planning","phone","reminder","enterprise","contract","profile");
include (GLPI_ROOT."/inc/includes.php");

	checkCentralAccess();

	// Change profile system
	if (isset ($_POST['newprofile'])) {
		if (isset ($_SESSION["glpiprofiles"][$_POST['newprofile']])) {
			changeProfile($_POST['newprofile']);
			if ($_SESSION["glpiactiveprofile"]["interface"]=="helpdesk"){
				glpi_header($CFG_GLPI['root_doc']."/front/helpdesk.public.php");
			} else {
				glpi_header($_SERVER['PHP_SELF']);
			}
		} else {
			glpi_header(preg_replace("/FK_entities.*/","",$_SERVER['HTTP_REFERER']));
		}
	}
	// Manage entity change
	if (isset($_GET["active_entity"])){
		if (!isset($_GET["recursive"])) {
			$_GET["recursive"]=0;
		}
		changeActiveEntities($_GET["active_entity"],$_GET["recursive"]);
		if ($_GET["active_entity"]==$_SESSION["glpiactive_entity"]){
			glpi_header(preg_replace("/FK_entities.*/","",$_SERVER['HTTP_REFERER']));
		}
	}

	commonHeader($LANG["title"][0],$_SERVER['PHP_SELF']);

	// Redirect management
	if (isset($_GET["redirect"])){
		manageRedirect($_GET["redirect"]);
	}


	// Greet the user

	echo "<br><span class='icon_consol'>".$LANG["central"][0]." ";

	echo formatUserName($_SESSION["glpiID"],$_SESSION["glpiname"],$_SESSION["glpirealname"],$_SESSION["glpifirstname"]);
	echo ", ".$LANG["central"][1]."</span>";

	echo "<br><br>";

	
	$tabs['my']=array('title'=>$LANG["central"][12],
		'url'=>$CFG_GLPI['root_doc']."/ajax/central.tabs.php",
		'params'=>"target=".$_SERVER['PHP_SELF']."&type=central&glpi_tab=my");
	$tabs['global']=array('title'=>$LANG["central"][13],
		'url'=>$CFG_GLPI['root_doc']."/ajax/central.tabs.php",
		'params'=>"target=".$_SERVER['PHP_SELF']."&type=central&glpi_tab=global");
	$tabs['group']=array('title'=>$LANG["central"][14],
		'url'=>$CFG_GLPI['root_doc']."/ajax/central.tabs.php",
		'params'=>"target=".$_SERVER['PHP_SELF']."&type=central&glpi_tab=group");

	$plug_tabs=getPluginTabs($_SERVER['PHP_SELF'],"central","","");
	$tabs+=$plug_tabs;

	$tabs[-1]=array('title'=>$LANG["common"][66],
		'url'=>$CFG_GLPI['root_doc']."/ajax/central.tabs.php",
		'params'=>"target=".$_SERVER['PHP_SELF']."&type=central&glpi_tab=-1");

	echo "<div id='tabspanel' class='center-h'></div>";
	createAjaxTabs('tabspanel','tabcontent',$tabs,$_SESSION['glpi_tab']);
	echo "<div id='tabcontent'></div>";
	echo "<script type='text/javascript'>loadDefaultTab();</script>";


commonFooter();

?>
