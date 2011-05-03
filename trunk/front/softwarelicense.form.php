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

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}
if (!isset($_GET["softwares_id"])) {
   $_GET["softwares_id"] = "";
}
$license = new SoftwareLicense();

if (isset($_POST["add"])) {
   $license->check(-1,'w',$_POST);

   $newID = $license->add($_POST);
   Event::log($_POST['softwares_id'], "software", 4, "inventory",
              $_SESSION["glpiname"]." ".$LANG['log'][85]." $newID.");
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["delete"])) {
   $license->check($_POST['id'],'d');

   $license->delete($_POST);
   Event::log($license->fields['softwares_id'], "software", 4, "inventory",
              $_SESSION["glpiname"]." ".$LANG['log'][87]." ".$_POST["id"]);
   $license->redirectToList();

} else if (isset($_POST["update"])) {
   $license->check($_POST['id'],'w');

   $license->update($_POST);
   Event::log($license->fields['softwares_id'], "software", 4, "inventory",
              $_SESSION["glpiname"]." ".$LANG['log'][86]." ".$_POST["id"]);
   glpi_header($_SERVER['HTTP_REFERER']);

} else {
   commonHeader($LANG['Menu'][4],$_SERVER['PHP_SELF'],"inventory","software");
   $license->showForm($_GET["id"], array('softwares_id' => $_GET["softwares_id"]));
   commonFooter();
}

?>
