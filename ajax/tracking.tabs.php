<?php
/*
 * @version $Id: enterprise.form.php 7178 2008-07-31 12:30:25Z moyo $
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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------


$NEEDED_ITEMS=array("group","user","planning","tracking","computer","printer","monitor","peripheral","networking","software","enterprise","phone","document","mailing");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if(!isset($_POST["ID"])) {
	exit();
}
	
		if ($_POST["ID"]>0){
			switch($_POST['glpi_tab']){
				case 1:
					showJobDetails($_POST['target']."?show=user&ID=".$_POST["ID"],$_POST["ID"]);
					showFollowupsSummary($_POST["ID"]);
					break;
				case 2 :
					showAddFollowupForm($_POST["ID"]);
					break;
				default :
					if (!displayPluginAction(TRACKING_TYPE,$_POST["ID"],$_SESSION['glpi_tab'])){
						showJobDetails($_POST['target'],$_POST["ID"]);
					}
					break;
			}
		}
	
	ajaxFooter();
?>