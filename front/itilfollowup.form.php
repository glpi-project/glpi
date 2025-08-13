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

require_once(__DIR__ . '/_check_webserver_config.php');

use Glpi\Event;
use Glpi\Exception\Http\BadRequestHttpException;

global $DB;

$fup = new ITILFollowup();

$redirect = null;
$handled = false;

if (!isset($_POST['itemtype']) || !class_exists($_POST['itemtype'])) {
    throw new BadRequestHttpException();
}
$track = getItemForItemtype($_POST['itemtype']);
if ($track === false) {
    throw new BadRequestHttpException();
}

if (isset($_POST["add"])) {
    $fup->check(-1, CREATE, $_POST);
    $fup->add($_POST);

    Event::log(
        $fup->getField('items_id'),
        strtolower($_POST['itemtype']),
        4,
        "tracking",
        //TRANS: %s is the user login
        sprintf(__('%s adds a followup'), $_SESSION["glpiname"])
    );
    $redirect = $track->getFormURLWithID($fup->getField('items_id'));
    $handled = true;
} elseif (
    isset($_POST['add_close'])
           || isset($_POST['add_reopen'])
) {
    if ($track->getFromDB($_POST['items_id']) && (method_exists($track, 'canApprove') && $track->canApprove())) {
        $fup->add($_POST);

        Event::log(
            $fup->getField('items_id'),
            strtolower($_POST['itemtype']),
            4,
            "tracking",
            //TRANS: %s is the user login
            sprintf(__('%s approves or refuses a solution'), $_SESSION["glpiname"])
        );
    }
} elseif (isset($_POST["update"])) {
    $fup->check($_POST['id'], UPDATE);
    $fup->update($_POST);

    Event::log(
        $fup->getField('items_id'),
        strtolower($_POST['itemtype']),
        4,
        "tracking",
        //TRANS: %s is the user login
        sprintf(__('%s updates a followup'), $_SESSION["glpiname"])
    );
    $redirect = $track->getFormURLWithID($fup->getField('items_id'));
    $handled = true;
} elseif (isset($_POST["purge"])) {
    $fup->check($_POST['id'], PURGE);
    $fup->delete($_POST, true);

    Event::log(
        $fup->getField('items_id'),
        strtolower($_POST['itemtype']),
        4,
        "tracking",
        //TRANS: %s is the user login
        sprintf(__('%s purges a followup'), $_SESSION["glpiname"])
    );
    $redirect = $track->getFormURLWithID($fup->getField('items_id'));
}

if ($handled) {
    if ($track->can($_POST["items_id"], READ)) {
        $toadd = '';
        // Copy followup to KB redirect to KB
        if (isset($_POST['_fup_to_kb']) && $_POST['_fup_to_kb']) {
            $toadd = "&_fup_to_kb=" . $fup->getID();
        }
        $redirect = $track->getLinkURL() . $toadd;
    } else {
        Session::addMessageAfterRedirect(
            __s('You have been redirected because you no longer have access to this ticket'),
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
