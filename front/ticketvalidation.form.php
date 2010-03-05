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
	
	$validation->check(-1,'w',$_POST);
	$newID=$validation->add($_POST);
	glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["update"])) {
   
   $validation->update($_POST);
	glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["delete"])) {
   $validation->check($_POST['id'], 'd');
   $validation->delete($_POST);

   /*Event::log($task->getField('tickets_id'), "ticket", 4, "tracking",
              $_SESSION["glpiname"]." ".$LANG['log'][21]);*/
   glpi_header(getItemTypeSearchURL('TicketValidation'));

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
   
   $validation->showValidationTicketForm($_GET["id"]);
   
   if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
      helpFooter();
   } else {
      commonFooter();
   }
}


?>