<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
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

use Glpi\Exception\PasswordTooWeakException;

Session::checkLoginUser();

switch (Session::getCurrentInterface()) {
   case 'central':
      Html::header(__('Update password'), $_SERVER['PHP_SELF']);
      break;
   case 'helpdesk':
      Html::helpHeader(__('Update password'), $_SERVER['PHP_SELF']);
      break;
   default:
      Html::simpleHeader(__('Update password'));
      break;
}

$user = new User();
$user->getFromDB(Session::getLoginUserID());

$success  = false;
$error_messages = [];

if (array_key_exists('update', $_POST)) {
   $current_password = $_POST['current_password'];
   if (!Auth::checkPassword($current_password, $user->fields['password'])) {
      $error_messages = [__('Incorrect password')];
   } else {
      $input = [
         'id'               => $user->fields['id'],
         'current_password' => $_POST['current_password'],
         'password'         => $_POST['password'],
         'password2'        => $_POST['password2'],
      ];
      if ($input['password'] === $input['current_password']) {
         $error_messages = [__('The new password must be different from current password')];
      } else if ($input['password'] !== $input['password2']) {
         $error_messages = [__('The two passwords do not match')];
      } else {
         try {
            Config::validatePassword($input['password'], false);
            if ($user->update($input)) {
               $success = true;
            } else {
               $error_messages = [__('An error occured during password update')];
            }
         } catch (PasswordTooWeakException $exception) {
            $error_messages = $exception->getMessages();
         }
      }
   }
}

if ($success) {
   echo '<table class="tab_cadre">';
   echo '<tr><th colspan="2">' . __('Password update') . '</th></tr>';
   echo '<tr>';
   echo '<td>';
   echo __('Your password has been successfully updated.');
   echo '<br />';
   echo '<a href="' . $CFG_GLPI['root_doc'] . '/front/logout.php?noAUTO=1">' . __('Log in') . '</a>';
   echo '</td>';
   echo '</tr>';
   echo '</table>';
} else {
   $user->showPasswordUpdateForm($error_messages);
}


switch (Session::getCurrentInterface()) {
   case 'central':
      Html::footer();
      break;
   case 'helpdesk':
      Html::helpFooter();
      break;
   default:
      Html::nullFooter();
      break;
}
