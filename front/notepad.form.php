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
 * @since 0.85
 */

use Glpi\Event;

include ('../inc/includes.php');

$note = new Notepad();

if (isset($_POST['add'])) {
   $note->check(-1, CREATE, $_POST);

   $newID = $note->add($_POST, false);
   Event::log($newID, "notepad", 4, "tools",
              sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $newID));
   Html::back();

} else if (isset($_POST["purge"])) {
   $note->check($_POST["id"], PURGE);
   $note->delete($_POST, 1);
   Event::log($_POST["id"], "notepad", 4, "tools",
              //TRANS: %s is the user login
              sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
   Html::back();

} else if (isset($_POST["update"])) {
   $note->check($_POST["id"], UPDATE);

   $note->update($_POST);
   Event::log($_POST["id"], "notepad", 4, "tools",
              //TRANS: %s is the user login
              sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
   Html::back();

}
Html::displayErrorAndDie("lost");
