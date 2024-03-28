<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

include('../../inc/includes.php');

use Glpi\Form\AccessControl\ControlType\AllowListDropdown;
use Glpi\Form\Form;

Session::checkRight(Form::$rightname, READ);

/**
 * Special endpoint to preview the results of the AllowListDropdown.
 *
 * It will count the number ofusers that match the supplied restrictions.
 * A link to the search results will also be provided if the user can read the user list.
 */

if (isset($_POST['values'])) {
    $users = AllowListDropdown::getPostedIds($_POST['values'], User::class);
    $groups = AllowListDropdown::getPostedIds($_POST['values'], Group::class);
    $profiles = AllowListDropdown::getPostedIds($_POST['values'], Profile::class);
} else {
    // If the dropdown has no selected value, $_POST['values'] is not defined at all.
    // This mean there are no criteria and all users should be found.
    $users = [];
    $groups = [];
    $profiles = [];
}

$data = AllowListDropdown::countUsersForCriteria(
    $users,
    $groups,
    $profiles
);

// Do not display the link if the user does not have the right to read the user list
if (!Session::haveRight(User::$rightname, READ)) {
    unset($data['link']);
}

// Content will be rendered as JSON to allow the calling script to render it as it wishes.
header('Content-Type: application/json');
echo json_encode($data);
