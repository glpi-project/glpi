<?php
/*
 * @version $Id: computer.tabs.php 7152 2008-07-29 12:27:18Z jmd $
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

$NEEDED_ITEMS = array (
	"setup",
	"ocsng",
	"dbreplicate"
);

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if(!isset($_POST["ID"])) {
	exit();
}

	checkRight("config", "r");
	
	$config = new Config();
	
	if ($_POST["ID"]<0){
			switch($_POST['config_tab']){
				case 1 :
					$_SESSION['glpi_configgen']=1;
					$config->showForm($_POST['target']);
					break;
				case 2 :
					$_SESSION['glpi_configgen']=2;
					$config->showForm($_POST['target']);
					break;
				case 3 :
					$_SESSION['glpi_configgen']=3;
					$config->showForm($_POST['target']);
					break;
				case 4 :
					$_SESSION['glpi_configgen']=4;
					$config->showForm($_POST['target']);
					break;
				case 5 :
					$_SESSION['glpi_configgen']=5;
					$config->showForm($_POST['target']);
					break;
				default :
					break;
		}
	}

?>