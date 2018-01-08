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

include ('../inc/includes.php');

Session ::checkLoginUser();

$item = new KnowbaseItem_Item();

if (isset($_POST["add"])) {
   if (!isset($_POST['knowbaseitems_id']) || !isset($_POST['items_id']) || !isset($_POST['itemtype'])) {
      $message = __('Mandatory fields are not filled!');
      Session::addMessageAfterRedirect($message, false, ERROR);
      Html::back();
   }

   $item->check(-1, CREATE, $_POST);

   if ($item->add($_POST)) {
      Event::log($_POST["knowbaseitems_id"], "knowbaseitem", 4, "tracking",
                  sprintf(__('%s adds a link with an knowledge base'), $_SESSION["glpiname"]));
   }
   Html::back();
}

Html::displayErrorAndDie("lost");
