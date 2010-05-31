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

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

$mailgate = new MailCollector();

if (isset($_POST["add"])) {
   $mailgate->check(-1,'w',$_POST);
   $newID = $mailgate->add($_POST);
   Event::log($newID, "mailcollector", 4, "setup", $_SESSION["glpiname"]." added ".$_POST["name"].".");
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["delete"])) {
   $mailgate->check($_POST['id'],'w');
   $mailgate->delete($_POST);
   Event::log($_POST["id"], "mailcollector", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][22]);
   glpi_header($CFG_GLPI["root_doc"]."/front/mailcollector.php");

} else if (isset($_POST["update"])) {
   $mailgate->check($_POST['id'],'w');
   $mailgate->update($_POST);
   Event::log($_POST["id"], "mailcollector", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][21]);
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["get_mails"])) {
   $mailgate->check($_POST['id'],'w');
   $mailgate->collect($_POST["id"],1);
   glpi_header($_SERVER['HTTP_REFERER']);

} else {
   commonHeader($LANG['Menu'][39],$_SERVER['PHP_SELF'],"config","mailcollector");
   $mailgate->showForm($_GET["id"]);
   commonFooter();
}

?>