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

/**
 * @since 9.2
 */

use Glpi\Event;

include ('../inc/includes.php');


$item = new OlaLevel();

if (isset($_POST["update"])) {
   $item->check($_POST["id"], UPDATE);

   $item->update($_POST);

   Event::log($_POST["id"], "olas", 4, "setup",
              //TRANS: %s is the user login
              sprintf(__('%s updates an ola level'), $_SESSION["glpiname"]));

   Html::back();

} else if (isset($_POST["add"])) {
   $item->check(-1, CREATE, $_POST);

   if ($newID = $item->add($_POST)) {
      Event::log($_POST["olas_id"], "olas", 4, "setup",
                 //TRANS: %s is the user login
                 sprintf(__('%s adds a link with an item'), $_SESSION["glpiname"]));
      if ($_SESSION['glpibackcreated']) {
         Html::redirect($item->getLinkURL());
      }
   }
   Html::back();

} else if (isset($_POST["purge"])) {

   if (isset($_POST['id'])) {
      $item->check($_POST['id'], PURGE);
      if ($item->delete($_POST, 1)) {
         Event::log($_POST["id"], "olas", 4, "setup",
                    //TRANS: %s is the user login
                    sprintf(__('%s purges an ola level'), $_SESSION["glpiname"]));
      }
      $item->redirectToList();
   }

   Html::back();

} else if (isset($_POST["add_action"])) {
   $item->check($_POST['olalevels_id'], UPDATE);

   $action = new OlaLevelAction();
   $action->add($_POST);

   Html::back();

} else if (isset($_POST["add_criteria"])) {

   $item->check($_POST['olalevels_id'], UPDATE);
   $criteria = new OlaLevelCriteria();
   $criteria->add($_POST);

   Html::back();

} else if (isset($_GET["id"]) && ($_GET["id"] > 0)) { //print computer information
   Html::header(OlaLevel::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "config", "slm", "olalevel");
   //show computer form to add
   $item->display(['id' => $_GET["id"]]);
   Html::footer();
}
