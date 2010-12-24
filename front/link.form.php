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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------


define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if (empty($_GET["id"])) {
   $_GET["id"] = "";
}

$link = new Link();

if (isset($_POST["add"])) {
   $link->check(-1,'w');

   $newID = $link->add($_POST);
   Event::log($newID, "links", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][20]." ".
              $_POST["name"].".");
   glpi_header($CFG_GLPI["root_doc"]."/front/link.php");

} else if (isset($_POST["delete"])) {
   $link->check($_GET["id"],'w');
   $link->delete($_POST);
   Event::log($_GET["id"], "links", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][22]);
   $link->redirectToList();

} else if (isset($_POST["update"])) {
   $link->check($_GET["id"],'w');
   $link->update($_POST);
   Event::log($_GET["id"], "links", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][21]);
   glpi_header($_SERVER['HTTP_REFERER']);

} else {
   commonHeader($LANG['title'][33],$_SERVER['PHP_SELF'],"config","link");

   $link->showForm($_GET["id"]);
   commonFooter();
}

?>