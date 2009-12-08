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

//error_log("REQUEST".print_r($_REQUEST,true));

$NEEDED_ITEMS=array('computer','contract','document','enterprise','group','infocom','link',
                    'reservation','rulesengine','rule.softwarecategories','software','tracking',
                    'user');

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
header_nocache();

if (!isset($_POST['id'])) {
   exit();
}

checkRight("software","r");

if (!isset($_POST["sort"])) {
   $_POST["sort"] = "";
}
if (!isset($_POST["order"])) {
   $_POST["order"] = "";
}
if (!isset($_POST["withtemplate"])) {
   $_POST["withtemplate"] = "";
}
$soft = new Software();

if ($_POST["id"]>0 && $soft->can($_POST["id"],'r')) {
   if (!empty($_POST["withtemplate"])) {
      switch($_REQUEST['glpi_tab']) {
         case 4 :
            Infocom::showForItem($CFG_GLPI["root_doc"]."/front/infocom.form.php",$soft,
                                 1,$_POST["withtemplate"]);
            Contract::showAssociated($soft,$_POST["withtemplate"]);
            break;

         case 5 :
            Document::showAssociated($soft,$_POST["withtemplate"]);
            break;

         default :
            Plugin::displayAction($soft, $_REQUEST['glpi_tab'], $_POST["withtemplate"]);
      }
   } else {
      switch($_REQUEST['glpi_tab']) {
         case -1 :
            showVersions($_POST["id"]);
            showLicenses($_POST["id"]);
            showInstallations($_POST["id"]);
            Infocom::showForItem($CFG_GLPI["root_doc"]."/front/infocom.form.php",$soft);
            Contract::showAssociated($soft);
            Document::showAssociated($soft);
            showJobListForItem(SOFTWARE_TYPE,$_POST["id"]);
            showLinkOnDevice(SOFTWARE_TYPE,$_POST["id"]);
            Plugin::displayAction($soft, $_REQUEST['glpi_tab']);
            break;

         case 2 :
            showInstallations($_POST["id"]);
            break;

         case 4 :
            Infocom::showForItem($CFG_GLPI["root_doc"]."/front/infocom.form.php",$soft);
            Contract::showAssociated($soft);
            break;

         case 5 :
            Document::showAssociated($soft);
            break;

         case 6 :
            showJobListForItem(SOFTWARE_TYPE,$_POST["id"]);
            break;

         case 7 :
            showLinkOnDevice(SOFTWARE_TYPE,$_POST["id"]);
            break;

         case 10 :
            showNotesForm($_POST['target'],SOFTWARE_TYPE,$_POST["id"]);
            break;

         case 11 :
            showDeviceReservations($_POST['target'],SOFTWARE_TYPE,$_POST["id"]);
            break;

         case 12 :
            showHistory(SOFTWARE_TYPE,$_POST["id"]);
            break;

         case 21 :
            showSoftwareMergeCandidates($_POST["id"]);
            break;

         default :
            if (!Plugin::displayAction($soft, $_REQUEST['glpi_tab'])) {
               showVersions($_POST["id"]);
               showLicenses($_POST["id"]);
            }
      }
   }
}

ajaxFooter();

?>