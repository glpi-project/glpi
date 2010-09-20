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

checkRight("peripheral", "r");

if (!isset($_POST['id'])) {
   exit();
}
if (!isset($_REQUEST['glpi_tab'])) {
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

$periph = new Peripheral();

if ($_POST["id"]>0 && $periph->can($_POST["id"],'r')) {

   if (!empty($_POST["withtemplate"])) {
      switch($_REQUEST['glpi_tab']) {
         case 4 :
            Infocom::showForItem($periph, $_POST["withtemplate"]);
            Contract::showAssociated($periph, $_POST["withtemplate"]);
            break;

         case 5 :
            Document::showAssociated($periph, $_POST["withtemplate"]);
            break;

         default :
            if (!Plugin::displayAction($periph, $_REQUEST['glpi_tab'], $_POST["withtemplate"])) {
               NetworkPort::showForItem('Peripheral', $_POST["id"], $_POST["withtemplate"]);
            }
      }

   } else {
      switch($_REQUEST['glpi_tab']) {
         case -1 :
            Computer_Item::showForItem($periph);
            NetworkPort::showForItem('Peripheral', $_POST["id"], $_POST["withtemplate"]);
            Infocom::showForItem($periph);
            Contract::showAssociated($periph);
            Document::showAssociated($periph);
            Ticket::showListForItem('Peripheral', $_POST["id"]);
            Link::showForItem('Peripheral', $_POST["id"]);
            Plugin::displayAction($periph, $_REQUEST['glpi_tab']);
            break;

         case 4 :
            Infocom::showForItem($periph);
            Contract::showAssociated($periph);
            break;

         case 5 :
            Document::showAssociated($periph);
            break;

         case 6 :
            Ticket::showListForItem('Peripheral', $_POST["id"]);
            break;

         case 7 :
            Link::showForItem('Peripheral', $_POST["id"]);
            break;

         case 10 :
            showNotesForm($_POST['target'],'Peripheral', $_POST["id"]);
            break;

         case 11 :
            Reservation::showForItem('Peripheral', $_POST["id"]);
            break;

         case 12 :
            Log::showForItem($periph);
            break;

         default :
            if (!Plugin::displayAction($periph, $_REQUEST['glpi_tab'])) {
               Computer_Item::showForItem($periph);
               NetworkPort::showForItem('Peripheral', $_POST["id"], $_POST["withtemplate"]);
            }
      }
   }
}

ajaxFooter();

?>
