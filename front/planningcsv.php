<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Glpi\Csv\CsvResponse;
use Glpi\Csv\PlanningCsv;

include('../inc/includes.php');

Session::checkRight("planning", READ);

$users_id = null;
$groups_id = (isset($_GET["gID"]) ? (int) $_GET['uID'] : 0);
$limititemtype = ($_GET['limititemtype'] ?? '');

if (!isset($_GET["uID"])) {
    if (
        ($uid = Session::getLoginUserID())
        && !Session::haveRight("planning", Planning::READALL)
    ) {
        $users_id = $uid;
    } else {
        $users_id = 0;
    }
} else {
    $users_id = (int) $_GET['uID'];
}

$user = new User();
$user->getFromDB(Session::getLoginUserID());

//// check if the request is valid: rights on uID / gID
// First check mine : user then groups
$ismine = false;
if ($user->getID() == $users_id) {
    $ismine = true;
}

// Check groups if have right to see
if (!$ismine && $groups_id !== 0) {
    $entities = Profile_User::getUserEntitiesForRight(
        $user->getID(),
        Planning::$rightname,
        Planning::READGROUP
    );
    $groups   = Group_User::getUserGroups($user->getID());
    foreach ($groups as $group) {
        if (
            $groups_id == $group['id']
            && in_array($group['entities_id'], $entities)
        ) {
            $ismine = true;
        }
    }
}

$canview = false;
// If not mine check global right
if (!$ismine) {
    // First check user
    $entities = Profile_User::getUserEntitiesForRight(
        $user->getID(),
        Planning::$rightname,
        Planning::READALL
    );
    if ($users_id) {
        $userentities = Profile_User::getUserEntities($user->getID());
        $intersect    = array_intersect($entities, $userentities);
        if (count($intersect)) {
            $canview = true;
        }
    }
    // Else check group
    if (!$canview && $groups_id) {
        $group = new Group();
        if ($group->getFromDB($groups_id)) {
            if (in_array($group->getEntityID(), $entities)) {
                $canview = true;
            }
        }
    }
}

if ($ismine || $canview) {
    CsvResponse::output(new PlanningCsv($users_id, $groups_id, $limititemtype));
}
