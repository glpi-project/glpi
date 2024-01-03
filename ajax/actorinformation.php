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

// Direct access to file
if (strpos($_SERVER['PHP_SELF'], "actorinformation.php")) {
    $AJAX_INCLUDE = 1;
    include('../inc/includes.php');
    header("Content-Type: text/html; charset=UTF-8");
    Html::header_nocache();
}

Session::checkLoginUser();

// save value and force boolval for security of $_REQUEST['only_number]
$only_number = boolval($_REQUEST['only_number'] ?? false);

// check if only one actor key is set
$actor_keys = preg_grep('/^(users|groups|suppliers)_id_(.*)$/', array_keys($_REQUEST));
if (count($actor_keys) !== 1) {
    // Unexpected request
    return;
}

$actor_key = reset($actor_keys);
$actor_id  = (int)$_REQUEST[$actor_key];

// check if user is allowed to see the item (only if not current connected user)
if ($actor_id != Session::getLoginUserID()) {
    $itemtype = getItemtypeForForeignKeyField($actor_key);
    $item     = new $itemtype();
    if (!$item->getFromDB($actor_id) || !$item->canView()) {
        // Unable to get item or no rights to see the item
        return;
    }
}

// compute field searchoption number value according to the type of actor
switch ($actor_key) {
    case 'users_id_requester':
        $field = 4;
        $method = 'countActiveObjectsForUser';
        break;
    case 'users_id_observer':
        $field = 66;
        $method = 'countActiveObjectsForObserverUser';
        break;
    case 'users_id_assign':
        $field = 5;
        $method = 'countActiveObjectsForTech';
        break;
    case 'groups_id_requester':
        $field = 71;
        $method = 'countActiveObjectsForRequesterGroup';
        break;
    case 'groups_id_observer':
        $field = 65;
        $method = 'countActiveObjectsForObserverGroup';
        break;
    case 'groups_id_assign':
        $field = 8;
        $method = 'countActiveObjectsForTechGroup';
        break;
    case 'suppliers_id_assign':
        $field = 6;
        $method = 'countActiveObjectsForSupplier';
        break;
    default:
        // Unexpected request
        return;
}

$options2 = [
    'criteria' => [
        [
            'field'      => $field,
            'searchtype' => 'equals',
            'value'      => $actor_id,
            'link'       => 'AND',
        ],
        [
            'field'      => 12, // status
            'searchtype' => 'equals',
            'value'      => 'notold',
            'link'       => 'AND',
        ],
    ],
    'reset' => 'reset',
];

$ticket = new Ticket();

$url = $ticket->getSearchURL() . "?" . Toolbox::append_params($options2, '&amp;');
$nb  = $ticket->{$method}($actor_id);

if ($only_number) {
    echo "<a href='$url'>" . $nb . "</a>";
} else {
    echo "&nbsp;<a href='$url' title=\"" . __s('Processing') . "\">(";
    printf(__('%1$s: %2$s'), __('Processing'), $nb);
    echo ")</a>";
}
