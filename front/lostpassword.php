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

if (!$CFG_GLPI['notifications_mailing']
    || !countElementsInTable('glpi_notifications',
                             ['itemtype' => 'User', 'event' => 'passwordforget', 'is_active' => 1])) {
   exit();
}

$user = new User();

// Manage lost password
Html::simpleHeader(__('Forgotten password?'));

// REQUEST needed : GET on first access / POST on submit form
if (isset($_REQUEST['password_forget_token'])) {

   if (isset($_POST['email'])) {
      $user->showUpdateForgottenPassword($_REQUEST);
   } else {
      User::showPasswordForgetChangeForm($_REQUEST['password_forget_token']);
   }

} else {

   if (isset($_POST['email'])) {
      $user->showForgetPassword($_POST['email']);
   } else {
      User::showPasswordForgetRequestForm();
   }
}

Html::nullFooter();
exit();
