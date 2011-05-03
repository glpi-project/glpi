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
   $_GET["id"] = -1;
}

$contract = new Contract();
$contractitem = new Contract_Item();
$contractsupplier = new Contract_Supplier();

if (isset($_POST["add"])) {
   $contract->check(-1,'w',$_POST);

   if ($newID = $contract->add($_POST)) {
      Event::log($newID, "contracts", 4, "financial",
               $_SESSION["glpiname"]." ".$LANG['log'][20]." ".$_POST["num"].".");
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["delete"])) {
   $contract->check($_POST['id'],'w');

   if ($contract->delete($_POST)) {
      Event::log($_POST["id"], "contracts", 4, "financial", $_SESSION["glpiname"]." ".$LANG['log'][22]);
   }
   $contact->redirectToList();

} else if (isset($_POST["restore"])) {
   $contract->check($_POST['id'],'w');

   if ($contract->restore($_POST)) {
      Event::log($_POST["id"], "contracts", 4, "financial", $_SESSION["glpiname"]." ".$LANG['log'][23]);
   }
   $contact->redirectToList();

} else if (isset($_POST["purge"])) {
   $contract->check($_POST['id'],'w');

   if ($contract->delete($_POST,1)) {
      Event::log($_POST["id"], "contracts", 4, "financial", $_SESSION["glpiname"]." ".$LANG['log'][24]);
   }
   $contact->redirectToList();

} else if (isset($_POST["update"])) {
   $contract->check($_POST['id'],'w');

   if ($contract->update($_POST)) {
      Event::log($_POST["id"], "contracts", 4, "financial", $_SESSION["glpiname"]." ".$LANG['log'][21]);
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["additem"])) {
   $contractitem->check(-1,'w',$_POST);

   if ($contractitem->add($_POST)) {
      Event::log($_POST["contracts_id"], "contracts", 4, "financial",
               $_SESSION["glpiname"]." ".$LANG['log'][32]);
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["deleteitem"])) {
   if (count($_POST["item"])) {
      foreach ($_POST["item"] as $key => $val) {
         if ($contractitem->can($key,'w')) {
            $contractitem->delete(array('id' => $key));
         }
      }
   }
   Event::log($_POST["contracts_id"], "contracts", 4, "financial",
            $_SESSION["glpiname"]." ".$LANG['log'][33]);
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_GET["deleteitem"])) {
   $contractitem->check($_GET["id"], 'w');

   if ($contractitem->delete($_GET)) {
      Event::log($_GET["contracts_id"], "contracts", 4, "financial",
               $_SESSION["glpiname"]." ".$LANG['log'][33]);
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["addcontractsupplier"])) {
   $contractsupplier->check(-1,'w',$POST);

   if ($contractsupplier->add($_POST)) {
      Event::log($_POST["contracts_id"], "contracts", 4, "financial",
               $_SESSION["glpiname"]." ".$LANG['log'][34]);
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_GET["deletecontractsupplier"])) {
   $contractsupplier->check($_GET['id'],'w');

   if ($contractsupplier->delete($_GET)) {
      Event::log($_GET["contracts_id"], "contracts", 4, "financial",
               $_SESSION["glpiname"]." ".$LANG['log'][35]);
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else {
   commonHeader($LANG['Menu'][25],$_SERVER['PHP_SELF'],"financial","contract");
   $contract->showForm($_GET["id"]);
   commonFooter();
}

?>
