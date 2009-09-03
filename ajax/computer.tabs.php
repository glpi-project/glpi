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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------


$NEEDED_ITEMS = array ('computer', 'contract', 'device', 'document', 'enterprise', 'group',
   'infocom', 'link', 'monitor', 'networking', 'ocsng', 'peripheral', 'phone', 'printer',
   'registry', 'reservation', 'rule.dictionnary.dropdown', 'rule.dictionnary.software',
   'rule.softwarecategories', 'rulesengine', 'search', 'setup', 'software', 'tracking', 'user');

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
header_nocache();

if(!isset($_POST["id"])) {
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

checkRight("computer","r");

//show computer form to add
if (!empty($_POST["withtemplate"])) {
   if ($_POST["id"]>0) {
      switch($_POST['glpi_tab']) {
         case 2 :
            showSoftwareInstalled($_POST["id"],$_POST["withtemplate"]);
            break;

         case 3 :
            showConnections($_POST['target'],$_POST["id"],$_POST["withtemplate"]);
            if ($_POST["withtemplate"]!=2) {
               showPortsAdd($_POST["id"],COMPUTER_TYPE);
            }
            showPorts($_POST["id"], COMPUTER_TYPE,$_POST["withtemplate"]);
            break;

         case 4 :
            showInfocomForm($CFG_GLPI["root_doc"]."/front/infocom.form.php",COMPUTER_TYPE,
                            $_POST["id"],1,$_POST["withtemplate"]);
            showContractAssociated(COMPUTER_TYPE,$_POST["id"],$_POST["withtemplate"]);
            break;

         case 5 :
            showDocumentAssociated(COMPUTER_TYPE,$_POST["id"],$_POST["withtemplate"]);
            break;

         case 20 :
            showComputerDisks($_POST["id"],$_POST["withtemplate"]);
            break;

         default :
            if (!displayPluginAction(COMPUTER_TYPE,$_POST["id"],$_POST['glpi_tab'],
                                     $_POST["withtemplate"])) {
               showDeviceComputerForm($_POST['target'],$_POST["id"], $_POST["withtemplate"]);
            }
            break;
      }
   }
} else {
   switch($_POST['glpi_tab']) {
      case -1 :
         showDeviceComputerForm($_POST['target'],$_POST["id"], $_POST["withtemplate"]);
         showComputerDisks($_POST["id"],$_POST["withtemplate"]);
         showSoftwareInstalled($_POST["id"]);
         showConnections($_POST['target'],$_POST["id"]);
         showPortsAdd($_POST["id"],COMPUTER_TYPE);
         showPorts($_POST["id"], COMPUTER_TYPE);
         showInfocomForm($CFG_GLPI["root_doc"]."/front/infocom.form.php",COMPUTER_TYPE,$_POST["id"]);
         showContractAssociated(COMPUTER_TYPE,$_POST["id"]);
         showDocumentAssociated(COMPUTER_TYPE,$_POST["id"]);
         showJobListForItem(COMPUTER_TYPE,$_POST["id"]);
         showLinkOnDevice(COMPUTER_TYPE,$_POST["id"]);
         showRegistry($_POST["id"]);
         displayPluginAction(COMPUTER_TYPE,$_POST["id"],$_POST['glpi_tab'],$_POST["withtemplate"]);
         break;

      case 2 :
         showSoftwareInstalled($_POST["id"]);
         break;

      case 3 :
         showConnections($_POST['target'],$_POST["id"]);
         showPortsAdd($_POST["id"],COMPUTER_TYPE);
         showPorts($_POST["id"], COMPUTER_TYPE);
         break;

      case 4 :
         showInfocomForm($CFG_GLPI["root_doc"]."/front/infocom.form.php",COMPUTER_TYPE,$_POST["id"]);
         showContractAssociated(COMPUTER_TYPE,$_POST["id"]);
         break;

      case 5 :
         showDocumentAssociated(COMPUTER_TYPE,$_POST["id"]);
         break;

      case 6 :
         showJobListForItem(COMPUTER_TYPE,$_POST["id"]);
         break;

      case 7 :
         showLinkOnDevice(COMPUTER_TYPE,$_POST["id"]);
         break;

      case 10 :
         showNotesForm($_POST['target'],COMPUTER_TYPE,$_POST["id"]);
         break;

      case 11 :
         showDeviceReservations($_POST['target'],COMPUTER_TYPE,$_POST["id"]);
         break;

      case 12 :
         showHistory(COMPUTER_TYPE,$_POST["id"]);
         break;

      case 13 :
         ocsEditLock($_POST['target'],$_POST["id"]);
         break;

      case 14:
         showRegistry($_POST["id"]);
         break;

      case 20 :
         showComputerDisks($_POST["id"], $_POST["withtemplate"]);
         break;

      default :
         if (!displayPluginAction(COMPUTER_TYPE,$_POST["id"],$_POST['glpi_tab'],
                                  $_POST["withtemplate"])) {
            showDeviceComputerForm($_POST['target'],$_POST["id"], $_POST["withtemplate"]);
         }
         break;
   }
}

ajaxFooter();

?>
