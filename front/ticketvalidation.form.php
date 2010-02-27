<?php
/*
 * @version $Id: setup.php,v 1.2 2006/04/02 14:45:27 moyo Exp $
 ---------------------------------------------------------------------- 
 GLPI - Gestionnaire Libre de Parc Informatique 
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org/
 ----------------------------------------------------------------------

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
 ------------------------------------------------------------------------
*/

// ----------------------------------------------------------------------
// Original Author of file: 
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkLoginUser();

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

$validation = new Ticketvalidation();
$ticket = new Ticket();
$user = new User();

if (isset($_POST["add"])) {
	
	$ticket->check($_POST['tickets_id'],'w');
	$newID=$validation->add($_POST);
	glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["reject"])) {
	if (!empty($_POST["id"])) {
		$validation->getFromDB($_POST["id"]);
		if($validation->fields["users_id_approval"]==getLoginUserID()) {
			$validation->update(array("id" => $_POST["id"]
                                    , "status" => "rejected"
                                    , "approval_date" => date("Y-m-d H:i:s")
                                    , "comment_approval" => $_POST["comment_approval"]));
		} else {
         addMessageAfterRedirect($LANG['validation'][22],false,ERROR);
      }
	}
	glpi_header($_SERVER['HTTP_REFERER']);
	
} else if (isset($_POST["accept"])) {
	if (!empty($_POST["id"])) {
		$validation->getFromDB($_POST["id"]);
		if($validation->fields["users_id_approval"]==getLoginUserID()) {
			$validation->update(array("id" => $_POST["id"]
                                    , "status" => "accepted"
                                    , "approval_date" => date("Y-m-d H:i:s")
                                    , "comment_approval" => $_POST["comment_approval"]));
		} else {
         addMessageAfterRedirect($LANG['validation'][22],false,ERROR);
      }
	}
	glpi_header($_SERVER['HTTP_REFERER']);
/*
} else if (isset($_GET["resend"])) {
	if (!empty($_GET["id"])) {
		if ($validation->getFromDB($_GET["id"])) {

			$ticket->getFromDB($validation->fields["tickets_id"]);
      
			if (haveRight("config", "w") || ($job->fields["author"]==$_SESSION['glpiID'])) {
				$validation->sendMail();
			}
		}
	}
	glpi_header($_SERVER['HTTP_REFERER']);
*/
} else {
   
   if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
      helpHeader($LANG['validation'][0],'',$_SESSION["glpiname"]);
   } else {
      commonHeader($LANG['validation'][0],'',"maintain","validation");
   }
   
   if (!empty($_GET['id']) && $validation->getFromDB($_GET['id'])) {
      if ($validation->fields["users_id_approval"]==getLoginUserID()) {
         if ($validation->fields["status"] == 'waiting') {
            $validation->showApprobationForm($_GET["id"]);
         } else {
            $validation->showValidation($_GET["id"]);
            //glpi_header(getItemTypeSearchURL('TicketValidation'));
         }
      } else {
         echo "<div align='center'><br><br><img src=\"".$CFG_GLPI["root_doc"]."/pics/warning.png\" alt=\"warning\"><br><br>"; 
         echo "<b>".$LANG['validation'][22]."</b></div>"; 
      }
   }
   
   commonFooter();
}


?>