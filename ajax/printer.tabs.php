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
   $_POST["id"] = "";
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

checkRight("printer","r");

$printer = new Printer();

if ($_POST["id"]>0 && $printer->can($_POST["id"],'r')) {

   if (!empty($_POST["withtemplate"])) {
      switch($_REQUEST['glpi_tab']) {
         case 3 :
            NetworkPort::showForItem('Printer', $_POST["id"], $_POST["withtemplate"]);
            break;

         case 4 :
            Infocom::showForItem($printer, $_POST["withtemplate"]);
            Contract::showAssociated($printer, $_POST["withtemplate"]);
            break;

         case 5 :
            Document::showAssociated($printer, $_POST["withtemplate"]);
            break;

         default :
            Plugin::displayAction($printer, $_REQUEST['glpi_tab'], $_POST["withtemplate"]);
      }

   } else {
      switch($_REQUEST['glpi_tab']) {
         case -1 :
            Cartridge::showInstalled($printer);
            Cartridge::showInstalled($printer, 1);
            Computer_Item::showForItem($printer);
            NetworkPort::showForItem('Printer', $_POST["id"], $_POST["withtemplate"]);
            Infocom::showForItem($printer);
            Contract::showAssociated($printer);
            Document::showAssociated($printer);
            Ticket::showListForItem('Printer',$_POST["id"]);
            Link::showForItem('Printer',$_POST["id"]);
            Plugin::displayAction($printer, $_REQUEST['glpi_tab']);
            break;

         case 3 :
            Computer_Item::showForItem($printer);
            NetworkPort::showForItem('Printer', $_POST["id"]);
            break;

         case 4 :
            Infocom::showForItem($printer);
            Contract::showAssociated($printer);
            break;

         case 5 :
            Document::showAssociated($printer);
            break;

         case 6 :
            Ticket::showListForItem('Printer', $_POST["id"]);
            break;

         case 7 :
            Link::showForItem('Printer', $_POST["id"]);
            break;

         case 10 :
            showNotesForm($_POST['target'], 'Printer', $_POST["id"]);
            break;

         case 11 :
            Reservation::showForItem('Printer',$_POST["id"]);
            break;

         case 12 :
            Log::showForItem($printer);
            break;

         default :
            if (!Plugin::displayAction($printer, $_REQUEST['glpi_tab'])) {
               Cartridge::showInstalled($printer);
               Cartridge::showInstalled($printer, 1);
            }
      }
   }
}

ajaxFooter();

?>
