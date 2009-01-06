<?php
/*
 * @version $Id: HEADER 6217 2008-01-01 01:32:45Z moyo $
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


$NEEDED_ITEMS=array("profile","search","entity","user");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");


if(!isset($_POST["type"])) {
	$_POST["type"] = -1;
}

if(!isset($_POST["device_type"])) {
	$_POST["device_type"] = -1;
}

if(!isset($_POST["mark_default"])) {
	$_POST["mark_default"] = -1;
}

if(!isset($_POST["url"])) {
	$_POST["url"] = "";
}

$bookmark = new Bookmark;

	//	if ($_POST["ID"]>0){
			switch($_POST['glpi_tab']){
				
				case 0 :
				case 1 :
						$_SESSION['glpi_viewbookmark']=$_POST['glpi_tab'];
						switch($_POST['action']){
							case "edit" :
								if ($_POST['ID']>0){
									if (isset($_POST['mark_default']) && $_POST['mark_default']!=-1){
										if ($_POST["mark_default"]>0){
											$bookmark->mark_default($_POST["ID"]);
										}elseif ($_POST["mark_default"]==0){
											$bookmark->unmark_default($_POST["ID"]);
										}
										$bookmark->showBookmarkList($_POST['target'],$_POST['glpi_tab']);
									} else {
										$bookmark->showForm($_POST['target'],$_POST["ID"]);
									}
								} else  {
									$bookmark->showForm($_POST['target'],$_POST["ID"],$_POST["type"],rawurldecode($_POST["url"]),$_POST["device_type"]);	
								}
								break;
							case "load" :
								if (isset($_POST["ID"]) && $_POST["ID"]>0){
									$bookmark->load($_POST["ID"]);
								}
								$bookmark->showBookmarkList($_POST['target'],$_POST['glpi_tab']);
								break;
						}
					break;
					
				default :
					break;
			}
		//}
	
	ajaxFooter();
?>