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

// Change profile system
if (isset ($_POST['newprofile'])) {
   if (isset ($_SESSION["glpiprofiles"][$_POST['newprofile']])) {
      changeProfile($_POST['newprofile']);
      if ($_SESSION["glpiactiveprofile"]["interface"] == "central") {
         glpi_header($CFG_GLPI['root_doc']."/front/central.php");
      } else {
         glpi_header($_SERVER['PHP_SELF']);
      }
   } else {
      glpi_header(preg_replace("/entities_id=.*/","",$_SERVER['HTTP_REFERER']));
   }
}

// Manage entity change
if (isset($_GET["active_entity"])) {
   if (!isset($_GET["is_recursive"])) {
      $_GET["is_recursive"] = 0;
   }
   if (changeActiveEntities($_GET["active_entity"],$_GET["is_recursive"])) {
      if ($_GET["active_entity"] == $_SESSION["glpiactive_entity"]) {
         glpi_header(preg_replace("/entities_id.*/","",$_SERVER['HTTP_REFERER']));
      }
   }
}

// Redirect management
if (isset($_GET["redirect"])) {
   manageRedirect($_GET["redirect"]);
}

checkHelpdeskAccess();
helpHeader($LANG['Menu'][31],$_SERVER['PHP_SELF'],$_SESSION["glpiname"]);
printHelpDesk(getLoginUserID(),1);

helpFooter();

?>
