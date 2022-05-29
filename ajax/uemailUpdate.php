<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

$AJAX_INCLUDE = 1;
if (strpos($_SERVER['PHP_SELF'], "uemailUpdate.php")) {
    include('../inc/includes.php');
    header("Content-Type: text/html; charset=UTF-8");
    Html::header_nocache();
}

Session::checkLoginUser();

if (
    (isset($_POST['field']) && ($_POST["value"] > 0))
    || (isset($_POST['allow_email']) && $_POST['allow_email'])
) {
    if (preg_match('/[^a-z_\-0-9]/i', $_POST['field'])) {
        throw new \RuntimeException('Invalid field provided!');
    }

    $default_email = "";
    $emails        = [];
    if (isset($_POST['typefield']) && ($_POST['typefield'] == 'supplier')) {
        $supplier = new Supplier();
        if ($supplier->getFromDB($_POST["value"])) {
            $default_email = $supplier->fields['email'];
        }
    } else {
        $user          = new User();
        if ($user->getFromDB($_POST["value"])) {
            $default_email = $user->getDefaultEmail();
            $emails        = $user->getAllEmails();
        }
    }

    $user_index = $_POST['_user_index'] ?? 0;

    $default_notif = $_POST['use_notification'][$user_index] ?? true;

    if (
        isset($_POST['alternative_email'][$user_index])
        && !empty($_POST['alternative_email'][$user_index])
        && empty($default_email)
    ) {
        if (NotificationMailing::isUserAddressValid($_POST['alternative_email'][$user_index])) {
            $default_email = $_POST['alternative_email'][$user_index];
        } else {
            throw new \RuntimeException('Invalid email provided!');
        }
    }

    $switch_name = $_POST['field'] . '[use_notification][]';
    echo "<div class='my-1 d-flex align-items-center'>
         <label  for='email_fup_check'>
            <i class='far fa-envelope me-1'></i>
            " . __('Email followup') . "
         </label>
         <div class='ms-2'>
            " . Dropdown::showYesNo($_POST['field'] . '[use_notification][]', $default_notif, -1, ['display' => false]) . "
         </div>
      </div>";

    $email_string = '';
   // Only one email
    if (
        (count($emails) == 1)
        && !empty($default_email)
        && NotificationMailing::isUserAddressValid($default_email[$user_index])
    ) {
        $email_string =  $default_email[$user_index];
       // Clean alternative email
        echo "<input type='hidden' size='25' name='" . $_POST['field'] . "[alternative_email][]'
             value=''>";
    } else if (count($emails) > 1) {
       // Several emails : select in the list
        $emailtab = [];
        foreach ($emails as $new_email) {
            if ($new_email != $default_email) {
                $emailtab[$new_email] = $new_email;
            } else {
                $emailtab[''] = $new_email;
            }
        }
        $email_string = Dropdown::showFromArray(
            $_POST['field'] . "[alternative_email][]",
            $emailtab,
            [
                'value'   => '',
                'display' => false
            ]
        );
    } else {
        $email_string = "<input type='mail' class='form-control' name='" . $_POST['field'] . "[alternative_email][]'
                        value='" . htmlentities($default_email, ENT_QUOTES, 'utf-8') . "'>";
    }

    echo "$email_string";
}

Ajax::commonDropdownUpdateItem($_POST);
