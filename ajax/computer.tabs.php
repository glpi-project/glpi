<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
include (GLPI_ROOT . "/inc/includes.php");

header("Content-Type: text/html; charset=UTF-8");
header_nocache();

if (!isset($_POST["id"])) {
   exit();
}
if (!isset($_POST["sort"])) {
   $_POST["sort"] = "";
}
if (!isset($_POST["order"])) {
   $_POST["order"] = "";
}
if (!isset($_POST["withtemplate"])) {
   $_POST["withtemplate"] = "";
}

checkRight("computer", "r");

$computer = new Computer();

//show computer form to add
if ($_POST["id"]>0 && $computer->can($_POST["id"],'r')) {

   if (!empty($_POST["withtemplate"])) {
      switch($_REQUEST['glpi_tab']) {
         case 2 :
            Computer_SoftwareVersion::showForComputer($computer, $_POST["withtemplate"]);
            break;

         case 3 :
            Computer_Item::showForComputer($_POST['target'], $computer, $_POST["withtemplate"]);
            NetworkPort::showForItem('Computer', $_POST["id"], $_POST["withtemplate"]);
            break;

         case 4 :
            Infocom::showForItem($computer, $_POST["withtemplate"]);
            Contract::showAssociated($computer, $_POST["withtemplate"]);
            break;

         case 5 :
            Document::showAssociated($computer, $_POST["withtemplate"]);
            break;

         case 20 :
            ComputerDisk::showForComputer($computer, $_POST["withtemplate"]);
            break;

         default :
            if (!Plugin::displayAction($computer, $_REQUEST['glpi_tab'], $_POST["withtemplate"])) {
               Computer_Device::showForComputer($computer, $_POST["withtemplate"]);
            }
      }

   } else {
      switch($_REQUEST['glpi_tab']) {
         case -1 :
            Computer_Device::showForComputer($computer);
            ComputerDisk::showForComputer($computer, $_POST["withtemplate"]);
            Computer_SoftwareVersion::showForComputer($computer);
            Computer_Item::showForComputer($_POST['target'], $computer);
            NetworkPort::showForItem('Computer', $_POST["id"]);
            Infocom::showForItem($computer);
            Contract::showAssociated($computer ,$_POST["withtemplate"]);
            Document::showAssociated($computer);
            Ticket::showListForItem('Computer', $_POST["id"]);
            Link::showForItem('Computer', $_POST["id"]);
            RegistryKey::showForComputer($_POST["id"]);
            Plugin::displayAction($computer, $_REQUEST['glpi_tab'], $_POST["withtemplate"]);
            break;

         case 2 :
            Computer_SoftwareVersion::showForComputer($computer);
            break;

         case 3 :
            Computer_Item::showForComputer($_POST['target'], $computer);
            NetworkPort::showForItem('Computer', $_POST["id"]);
            break;

         case 4 :
            Infocom::showForItem($computer);
            Contract::showAssociated($computer);
            break;

         case 5 :
            Document::showAssociated($computer);
            break;

         case 6 :
            Ticket::showListForItem('Computer', $_POST["id"]);
            break;

         case 7 :
            Link::showForItem('Computer', $_POST["id"]);
            break;

         case 10 :
            showNotesForm($_POST['target'],'Computer', $_POST["id"]);
            break;

         case 11 :
            Reservation::showForItem('Computer', $_POST["id"]);
            break;

         case 12 :
            Log::showForItem($computer);
            break;

         case 13 :
            OcsServer::editLock($_POST['target'], $_POST["id"]);
            break;

         case 14:
            RegistryKey::showForComputer($_POST["id"]);
            break;

         case 20 :
            ComputerDisk::showForComputer($computer);
            break;

         default :
            if (!Plugin::displayAction($computer, $_REQUEST['glpi_tab'], $_POST["withtemplate"])) {
               Computer_Device::showForComputer($computer);
            }
      }
   }
}

ajaxFooter();

?>
