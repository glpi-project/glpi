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
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;

global $DB;

/**
 * Following variables have to be defined before inclusion of this file:
 * @var CommonITILTask $task
 */

Session::checkCentralAccess();

if (!($task instanceof CommonITILTask)) {
    throw new BadRequestHttpException();
}
if (!$task->canView()) {
    throw new AccessDeniedHttpException();
}

$track = $task::getItilObjectItemInstance();

$itemtype = $track::class;
$fk       = $track::getForeignKeyField();

$track->getFromDB($task->getField($fk));

$redirect = null;
$handled = false;

if (isset($_POST["add"])) {
    $task->check(-1, CREATE, $_POST);
    $task->add($_POST);

    Event::log(
        $task->getField($fk),
        strtolower($itemtype),
        4,
        "tracking",
        //TRANS: %s is the user login
        sprintf(__('%s adds a task'), $_SESSION["glpiname"])
    );
    $redirect = $itemtype::getFormURLWithID($task->getField($fk));
    $handled = true;
} elseif (isset($_POST["purge"])) {
    $task->check($_POST['id'], PURGE);
    $task->delete($_POST, true);

    Event::log(
        $task->getField($fk),
        strtolower($itemtype),
        4,
        "tracking",
        //TRANS: %s is the user login
        sprintf(__('%s purges a task'), $_SESSION["glpiname"])
    );
    Html::redirect($itemtype::getFormURLWithID($task->getField($fk)));
} elseif (isset($_POST["update"])) {
    $task->check($_POST["id"], UPDATE);
    $task->update($_POST);

    Event::log(
        $task->getField($fk),
        strtolower($itemtype),
        4,
        "tracking",
        //TRANS: %s is the user login
        sprintf(__('%s updates a task'), $_SESSION["glpiname"])
    );
    $redirect = $itemtype::getFormURLWithID($task->getField($fk));
    $handled = true;
} elseif (isset($_POST["unplan"])) {
    $task->check($_POST["id"], UPDATE);
    $task->unplan();

    Event::log(
        $task->getField($fk),
        strtolower($itemtype),
        4,
        "tracking",
        //TRANS: %s is the user login
        sprintf(__('%s unplans a task'), $_SESSION["glpiname"])
    );
    $redirect = $itemtype::getFormURLWithID($task->getField($fk));
    $handled = true;
}

if ($handled) {
    if ($track->can($task->getField($fk), READ)) {
        $toadd = '';
        // Copy followup to KB redirect to KB
        if (isset($_POST['_task_to_kb']) && $_POST['_task_to_kb']) {
            $toadd = "&_task_to_kb=" . $task->getID();
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
