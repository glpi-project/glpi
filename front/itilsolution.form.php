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

use Glpi\Event;

/** @var \DBmysql $DB */
global $DB;

include('../inc/includes.php');

Session::checkLoginUser();

$solution = new ITILSolution();
$track = getItemForItemtype($_POST['itemtype']);
$track->getFromDB($_POST['items_id']);

$redirect = null;
$handled = false;

if (isset($_POST["add"])) {
    $solution->check(-1, CREATE, $_POST);
    if (!$track->canSolve()) {
        Session::addMessageAfterRedirect(
            __('You cannot solve this item!'),
            false,
            ERROR
        );
        Html::back();
    }

    if ($solution->add($_POST)) {
        if ($_SESSION['glpibackcreated']) {
            $redirect = $track->getLinkURL();
        }
        $handled = true;
    }
} elseif (isset($_POST['update'])) {
    $solution->getFromDB($_POST['id']);
    $solution->check($_POST['id'], UPDATE);
    $solution->update($_POST);
    $handled = true;
    $redirect = $track->getLinkURL();

    Event::log(
        $_POST["id"],
        "solution",
        4,
        "tracking",
        //TRANS: %s is the user login
        sprintf(__('%s updates an item'), $_SESSION["glpiname"])
    );
}

if ($handled) {
    if (isset($_POST['kb_linked_id']) && (int) $_POST['kb_linked_id'] > 0) {
        //if solution should be linked to selected KB entry
        $params = [
            'knowbaseitems_id' => $_POST['kb_linked_id'],
            'itemtype'         => $track->getType(),
            'items_id'         => $track->getID(),
        ];
        $existing = $DB->request(
            'glpi_knowbaseitems_items',
            $params
        );
        if ($existing->numrows() == 0) {
            $kb_item_item = new KnowbaseItem_Item();
            $kb_item_item->add($params);
        }
    }

    if ($track->can($_POST["items_id"], READ)) {
        $toadd = '';
        // Copy solution to KB redirect to KB
        if (isset($_POST['_sol_to_kb']) && $_POST['_sol_to_kb']) {
            $toadd = "&_sol_to_kb=1";
        }
        $redirect = $track->getLinkURL() . $toadd;
    } else {
        Session::addMessageAfterRedirect(
            __('You have been redirected because you no longer have access to this ticket'),
            true,
            ERROR
        );
        $redirect = $track->getSearchURL();
    }
}

if (null == $redirect) {
    Html::back();
} else {
    Html::redirect($redirect);
}
