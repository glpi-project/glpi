<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

use Glpi\Event;

include ('../inc/includes.php');

Session::checkRight("config", READ);

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

$mailgate = new MailCollector();

if (isset($_POST["add"])) {
   $mailgate->check(-1, CREATE, $_POST);

   if ($newID = $mailgate->add($_POST)) {
      Event::log($newID, "mailcollector", 4, "setup",
                 sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"]));
      if ($_SESSION['glpibackcreated']) {
         Html::redirect($mailgate->getLinkURL());
      }
   }
   Html::back();

} else if (isset($_POST["purge"])) {
   $mailgate->check($_POST['id'], PURGE);
   $mailgate->delete($_POST, 1);

   Event::log($_POST["id"], "mailcollector", 4, "setup",
              //TRANS: %s is the user login
              sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
   $mailgate->redirectToList();

} else if (isset($_POST["update"])) {
   $mailgate->check($_POST['id'], UPDATE);
   $mailgate->update($_POST);

   Event::log($_POST["id"], "mailcollector", 4, "setup",
              //TRANS: %s is the user login
              sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
   Html::back();

} else if (isset($_POST["get_mails"])) {
   $mailgate->check($_POST['id'], UPDATE);
   $mailgate->collect($_POST["id"], 1);

   Html::back();

} else {
   Html::header(MailCollector::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "config", "mailcollector");
   $mailgate->display(['id' =>$_GET["id"]]);
   Html::footer();
}

