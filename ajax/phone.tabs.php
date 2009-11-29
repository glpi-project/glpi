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


$NEEDED_ITEMS=array('computer','contract','document','enterprise','group','infocom','link',
                    'networking','ocsng','peripheral','phone','printer','reservation','tracking',
                    'user');

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
header_nocache();

if (!isset($_POST["id"])) {
   exit();
}

checkRight("phone","r");

if (empty($_POST["id"])) {
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

$phone = new Phone();

if ($_POST["id"]>0 && $phone->can($_POST["id"],'r')) {
   if (!empty($_POST["withtemplate"])) {
      switch($_REQUEST['glpi_tab']) {
         case 4 :
            showInfocomForm($CFG_GLPI["root_doc"]."/front/infocom.form.php",PHONE_TYPE,
                            $_POST["id"],1,$_POST["withtemplate"]);
            Contract::showAssociated($phone,$_POST["withtemplate"]);
            break;

         case 5 :
            Document::showAssociated($phone,$_POST["withtemplate"]);
            break;

         default :
            if (!displayPluginAction(PHONE_TYPE,$_POST["id"],$_REQUEST['glpi_tab'],
                                     $_POST["withtemplate"])) {
               if ($_POST["withtemplate"]!=2) {
                  showPortsAdd($_POST["id"],PHONE_TYPE);
               }
               showPorts($_POST["id"], PHONE_TYPE,$_POST["withtemplate"]);
            }
      }
   } else {
      switch($_REQUEST['glpi_tab']) {
         case -1 :
            Computer_Item::showForItem($phone);
            showPortsAdd($_POST["id"],PHONE_TYPE);
            showPorts($_POST["id"], PHONE_TYPE,$_POST["withtemplate"]);
            showInfocomForm($CFG_GLPI["root_doc"]."/front/infocom.form.php",PHONE_TYPE,$_POST["id"]);
            Contract::showAssociated($phone);
            Document::showAssociated($phone);
            showJobListForItem(PHONE_TYPE,$_POST["id"]);
            showLinkOnDevice(PHONE_TYPE,$_POST["id"]);
            displayPluginAction(PHONE_TYPE,$_POST["id"],$_REQUEST['glpi_tab'],$_POST["withtemplate"]);
            break;

         case 4 :
            showInfocomForm($CFG_GLPI["root_doc"]."/front/infocom.form.php",PHONE_TYPE,$_POST["id"]);
            Contract::showAssociated($phone);
            break;

         case 5 :
            Document::showAssociated($phone);
            break;

         case 6 :
            showJobListForItem(PHONE_TYPE,$_POST["id"]);
            break;

         case 7 :
            showLinkOnDevice(PHONE_TYPE,$_POST["id"]);
            break;

         case 10 :
            showNotesForm($_POST['target'],PHONE_TYPE,$_POST["id"]);
            break;

         case 11 :
            showDeviceReservations($_POST['target'],PHONE_TYPE,$_POST["id"]);
            break;

         case 12 :
            showHistory(PHONE_TYPE,$_POST["id"]);
            break;

         default :
            if (!displayPluginAction(PHONE_TYPE,$_POST["id"],$_REQUEST['glpi_tab'],
                                     $_POST["withtemplate"])) {
               Computer_Item::showForItem($phone);
               showPortsAdd($_POST["id"],PHONE_TYPE);
               showPorts($_POST["id"], PHONE_TYPE,$_POST["withtemplate"]);
            }
      }
   }
}

ajaxFooter();

?>