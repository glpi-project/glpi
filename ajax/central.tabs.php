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

define('GLPI_ROOT', '..');
$NEEDED_ITEMS=array("central","tracking","computer","printer","monitor","peripheral","networking","software","user","group","setup","planning","phone","reminder","enterprise","contract");
include (GLPI_ROOT."/inc/includes.php");

	checkCentralAccess();

	// show "my view" in first
	if (isset($_POST['tab'])) $_SESSION['glpi_centraltab']=$_POST['tab'];

	if (!isset($_GET['start'])) $_GET['start']=0;
	if(empty($_GET["start"])) $_GET["start"] = 0;

	if (!isset($_GET["sort"])) $_GET["sort"]="";
	if (!isset($_GET["order"])) $_GET["order"]="";

	switch ($_POST['tab']){
		case "my" :
			showCentralMyView();
			break;
		case "global" :
			showCentralGlobalView();
			break;
		case "group" :
			showCentralGroupView();
			break;
		case -1 : // all
			showCentralMyView();
			echo "<br>";
			showCentralGroupView();
			echo "<br>";
			showCentralGlobalView();
			echo "<br>";
			displayPluginAction("central","",$_POST['tab'],"");
			break;
		default :
			if (!displayPluginAction("central","",$_POST['tab'],""))
				showCentralMyView();		
			break;
	}


?>
