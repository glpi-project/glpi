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

checkRight("software", "r");

$license = new SoftwareLicense();

if ($_POST["id"]>0 && $license->can($_POST["id"],'r')) {
   switch($_REQUEST['glpi_tab']) {
      case -1 :
         Infocom::showForItem($license);
         Document::showAssociated($license);
         Plugin::displayAction($license, $_REQUEST['glpi_tab']);
         break;

      case 2 :
         Computer_SoftwareLicense::showForLicense($license);
         break;

      case 4 :
         Infocom::showForItem($license);
         break;

      case 5 :
         Document::showAssociated($license);
         break;

      case 6 :
         $license->showDebug();
         break;

      case 12 :
         Log::showForItem($license);
         break;

      default :
         if (!Plugin::displayAction($license, $_REQUEST['glpi_tab'])) {
            Computer_SoftwareLicense::showForLicenseByEntity($license);
         }
   }
}
ajaxFooter();

?>
