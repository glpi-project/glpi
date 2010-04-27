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
   $_GET["id"] = -1;
}

$contact = new Contact();
$contactsupplier = new Contact_Supplier();

if (isset($_REQUEST['getvcard'])) {
   if ($_GET["id"]<0) {
      glpi_header($CFG_GLPI["root_doc"]."/front/contact.php");
   }
   $contact->check($_GET["id"],'r');
   $contact->generateVcard();
} else if (isset($_POST["add"])) {
   $contact->check(-1,'w',$_POST);

   if ($newID = $contact->add($_POST)) {
      Event::log($newID, "contacts", 4, "financial",
               $_SESSION["glpiname"]." ".$LANG['log'][20]." ".$_POST["name"].".");
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["delete"])) {
   $contact->check($_POST["id"],'w');

   if ($contact->delete($_POST)) {
      Event::log($_POST["id"], "contacts", 4, "financial", $_SESSION["glpiname"]." ".$LANG['log'][22]);
   }
   glpi_header($CFG_GLPI["root_doc"]."/front/contact.php");

} else if (isset($_POST["restore"])) {
   $contact->check($_POST["id"],'w');

   if ($contact->restore($_POST)) {
      Event::log($_POST["id"], "contacts", 4, "financial", $_SESSION["glpiname"]." ".$LANG['log'][23]);
   }
   glpi_header($CFG_GLPI["root_doc"]."/front/contact.php");

} else if (isset($_POST["purge"])) {
   $contact->check($_POST["id"],'w');

   if ($contact->delete($_POST,1)) {
      Event::log($_POST["id"], "contacts", 4, "financial", $_SESSION["glpiname"]." ".$LANG['log'][24]);
   }
   glpi_header($CFG_GLPI["root_doc"]."/front/contact.php");

} else if (isset($_POST["update"])) {
   $contact->check($_POST["id"],'w');

   if ($contact->update($_POST)) {
      Event::log($_POST["id"], "contacts", 4, "financial", $_SESSION["glpiname"]." ".$LANG['log'][21]);
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["addcontactsupplier"])) {
   $contactsupplier->check(-1,'w',$_POST);

   if (isset($_POST["contacts_id"]) && $_POST["contacts_id"] > 0
      && isset($_POST["suppliers_id"]) && $_POST["suppliers_id"] > 0) {
      if ($contactsupplier->add($_POST)) {
         Event::log($_POST["contacts_id"], "contacts", 4, "financial",
         $_SESSION["glpiname"]."  ".$LANG['log'][34]);
      }
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_GET["deletecontactsupplier"])) {
   $contactsupplier->check($_GET["id"],'w');

   if ($contactsupplier->delete($_GET)) {
      Event::log($_GET["contacts_id"], "contacts", 4, "financial",
               $_SESSION["glpiname"]."  ".$LANG['log'][35]);
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else {
   commonHeader($LANG['Menu'][22],$_SERVER['PHP_SELF'],"financial","contact");
   $contact->showForm($_GET["id"],'');
   commonFooter();
}

?>
