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

// Direct access to file
if (strpos($_SERVER['PHP_SELF'], "actorinformation.php")) {
    $AJAX_INCLUDE = 1;
    include('../inc/includes.php');
    header("Content-Type: text/html; charset=UTF-8");
    Html::header_nocache();
}

Session::checkLoginUser();

$only_number = boolval($_REQUEST['only_number'] ?? false);

if (isset($_REQUEST['users_id_requester']) && ($_REQUEST['users_id_requester'] > 0)) {
    $ticket = new Ticket();

    $options2 = [
        'criteria' => [
            [
                'field'      => 4, // users_id_requester
                'searchtype' => 'equals',
                'value'      => $_REQUEST['users_id_requester'],
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

    $url = $ticket->getSearchURL() . "?" . Toolbox::append_params($options2, '&amp;');
    $nb  = $ticket->countActiveObjectsForUser($_REQUEST['users_id_requester']);

    if ($only_number) {
        if ($nb > 0) {
            echo "<a href='$url'>" . $nb . "</a>";
        }
    } else {
        echo "&nbsp;<a href='$url' title=\"" . __s('Processing') . "\">(";
        printf(__('%1$s: %2$s'), __('Processing'), $nb);
        echo ")</a>";
    }
} else if (isset($_REQUEST['users_id_assign']) && ($_REQUEST['users_id_assign'] > 0)) {
    $ticket = new Ticket();

    $options2 = [
        'criteria' => [
            [
                'field'      => 5, // users_id assign
                'searchtype' => 'equals',
                'value'      => $_REQUEST['users_id_assign'],
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

    $url = $ticket->getSearchURL() . "?" . Toolbox::append_params($options2, '&amp;');
    $nb  = $ticket->countActiveObjectsForTech($_REQUEST['users_id_assign']);

    if ($only_number) {
        if ($nb > 0) {
            echo "<a href='$url'>" . $nb . "</a>";
        }
    } else {
        echo "&nbsp;<a href='$url' title=\"" . __s('Processing') . "\">(";
        printf(__('%1$s: %2$s'), __('Processing'), $nb);
        echo ")</a>";
    }
} else if (isset($_REQUEST['groups_id_assign']) && ($_REQUEST['groups_id_assign'] > 0)) {
    $ticket = new Ticket();

    $options2 = [
        'criteria' => [
            [
                'field'      => 8, // groups_id assign
                'searchtype' => 'equals',
                'value'      => $_REQUEST['groups_id_assign'],
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

    $url = $ticket->getSearchURL() . "?" . Toolbox::append_params($options2, '&amp;');
    $nb  = $ticket->countActiveObjectsForTechGroup($_REQUEST['groups_id_assign']);

    if ($only_number) {
        if ($nb > 0) {
            echo "<a href='$url'>" . $nb . "</a>";
        }
    } else {
        echo "&nbsp;<a href='$url' title=\"" . __s('Processing') . "\">(";
        printf(__('%1$s: %2$s'), __('Processing'), $nb);
        echo ")</a>";
    }
} else if (isset($_REQUEST['suppliers_id_assign']) && ($_REQUEST['suppliers_id_assign'] > 0)) {
    $ticket = new Ticket();

    $options2 = [
        'criteria' => [
            [
                'field'      => 6, // suppliers_id assign
                'searchtype' => 'equals',
                'value'      => $_REQUEST['suppliers_id_assign'],
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

    $url = $ticket->getSearchURL() . "?" . Toolbox::append_params($options2, '&amp;');
    $nb  = $ticket->countActiveObjectsForSupplier($_REQUEST['suppliers_id_assign']);

    if ($only_number) {
        if ($nb > 0) {
            echo "<a href='$url'>" . $nb . "</a>";
        }
    } else {
        echo "<a href='$url' title=\"" . __s('Processing') . "\" class='badge rounded-pill bg-secondary'>
         $nb
      </a>";
    }
}
