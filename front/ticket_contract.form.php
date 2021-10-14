<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

$item = new Ticket_Contract();

if (isset($_POST["add"])) {
   if (!empty($_POST['tickets_id']) && empty($_POST['contracts_id'])) {
      $message = sprintf(
         __('Mandatory fields are not filled. Please correct: %s'),
         Contract::getTypeName(1)
      );
      Session::addMessageAfterRedirect($message, false, ERROR);
      Html::back();
   }
   if (empty($_POST['tickets_id']) && !empty($_POST['contracts_id'])) {
      $message = sprintf(
         __('Mandatory fields are not filled. Please correct: %s'),
         Ticket::getTypeName(1)
      );
      Session::addMessageAfterRedirect($message, false, ERROR);
      Html::back();
   }
   $item->check(-1, CREATE, $_POST);
   $item->add($_POST);

   Html::back();

}

Html::displayErrorAndDie("lost");
