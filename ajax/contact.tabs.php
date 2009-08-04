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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

$NEEDED_ITEMS=array("contact","enterprise","link","document");
define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
header_nocache();

if(!isset($_POST["id"])) {
	exit();
}

if(empty($_POST["id"])) $_POST["id"] = -1;

		if ($_POST['id']>0){
			switch($_POST['glpi_tab']){
				case -1 :	
					showEnterpriseContact($_POST["id"]);
					showDocumentAssociated(CONTACT_TYPE,$_POST["id"]);
					showLinkOnDevice(CONTACT_TYPE,$_POST["id"]);
					displayPluginAction(CONTACT_TYPE,$_POST["id"],$_POST['glpi_tab']);
					break;
				case 5 : 
					showDocumentAssociated(CONTACT_TYPE,$_POST["id"]);
					break;
				case 7 : 
					showLinkOnDevice(CONTACT_TYPE,$_POST["id"]);
					break;
				case 10 :
					showNotesForm($_POST['target'],CONTACT_TYPE,$_POST["id"]);
					break;
				default :
					if (!displayPluginAction(CONTACT_TYPE,$_POST["id"],$_POST['glpi_tab'])){
						showEnterpriseContact($_POST["id"]);
					}
					break;
			}
	
}
	ajaxFooter();

?>