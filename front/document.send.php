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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------


$NEEDED_ITEMS=array("document","tracking");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if ($CFG_GLPI["public_faq"] == 0)
checkLoginUser();

if (isset($_GET["file"])){

	$splitter=explode("/",$_GET["file"]);

	if (count($splitter)==2){
		$send=false;

		if ($splitter[0]=="_dumps"&&haveRight("backup","w")) $send=true;

		if (!$send){
			$doc=new Document;
			$founded=$doc->getFromDBbyFilename($_GET["file"]);

			if ($founded){


				if (isset($_SESSION["glpiactiveprofile"]["interface"])&&$_SESSION["glpiactiveprofile"]["interface"]=="central"){
					// My doc Check and Common doc right access
					if (haveRight("document","r")
							||$doc->fields["FK_users"]==$_SESSION["glpiID"])
						$send=true;

					// Knowbase Case
					if (!$send&&haveRight("knowbase","r")){
						$query = "SELECT * FROM glpi_doc_device WHERE glpi_doc_device.device_type = '".KNOWBASE_TYPE."' AND glpi_doc_device.FK_doc='".$doc->fields["ID"]."'";

						$result=$DB->query($query);
						if ($DB->numrows($result)>0)
							$send=true;
					}
					if (!$send&&haveRight("faq","r")){
						$query = "SELECT * FROM glpi_doc_device LEFT JOIN glpi_kbitems ON (glpi_kbitems.ID = glpi_doc_device.Fk_device) WHERE glpi_doc_device.device_type = '".KNOWBASE_TYPE."' AND glpi_doc_device.FK_doc='".$doc->fields["ID"]."' AND glpi_kbitems.faq='1'";

						$result=$DB->query($query);
						if ($DB->numrows($result)>0)
							$send=true;
					}


					// Tracking Case
					if (!$send&&isset($_GET["tracking"])){
						$job=new Job;
						$job->getFromDB($_GET["tracking"]);

						if ($job->fields["author"]==$_SESSION["glpiID"]||$job->fields["assign"]==$_SESSION["glpiID"]){
							$query = "SELECT * FROM glpi_doc_device WHERE glpi_doc_device.FK_device = '".$_GET["tracking"]."' AND glpi_doc_device.device_type = '".TRACKING_TYPE."' AND FK_doc='".$doc->fields["ID"]."'";
							$result=$DB->query($query);
							if ($DB->numrows($result)>0)
								$send=true;
						}
					}
				} else {

					// Check if it is my doc
					if (isset($_SESSION["glpiID"])&&$doc->fields["FK_users"]==$_SESSION["glpiID"]){
						$send=true;
					} else {
						if (haveRight("faq","r")||$CFG_GLPI["public_faq"]){
							// Check if it is a FAQ document
							$query = "SELECT * FROM glpi_doc_device LEFT JOIN glpi_kbitems ON (glpi_kbitems.ID = glpi_doc_device.Fk_device) WHERE glpi_doc_device.device_type = '".KNOWBASE_TYPE."' AND glpi_doc_device.FK_doc='".$doc->fields["ID"]."' AND glpi_kbitems.faq='1'";

							$result=$DB->query($query);
							if ($DB->numrows($result)>0)
								$send=true;
						}

						// Tracking Case
						if (!$send&&isset($_GET["tracking"])){
							$job=new Job;
							$job->getFromDB($_GET["tracking"]);

							if ($job->fields["author"]==$_SESSION["glpiID"]){
								$query = "SELECT * FROM glpi_doc_device WHERE glpi_doc_device.FK_device = '".$_GET["tracking"]."' AND glpi_doc_device.device_type = '".TRACKING_TYPE."' AND FK_doc='".$doc->fields["ID"]."'";
								$result=$DB->query($query);
								if ($DB->numrows($result)>0)
									$send=true;
							}
						}

					}
				}
			} else echo $LANG["document"][43];
		}


		if ($send&&file_exists(GLPI_DOC_DIR."/".$_GET["file"]))
			sendFile(GLPI_DOC_DIR."/".$_GET["file"],$splitter[1]);
		else echo $LANG["document"][45];
	} else echo $LANG["document"][44];
}



?>
