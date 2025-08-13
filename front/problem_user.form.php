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

/**
 * @since 0.83
 */

global $CFG_GLPI;

$link = new Problem_User();
$item = new Problem();

Html::popHeader(__('Email followup'));

if (isset($_POST["update"])) {
    $link->check($_POST["id"], UPDATE);

    if ($link->update($_POST)) {
        echo "<script type='text/javascript' >";
        echo "window.parent.location.reload();";
        echo "</script>";
    } else {
        Html::back();
    }
} elseif (isset($_POST['delete'])) {
    $link->check($_POST['id'], DELETE);
    $link->delete($_POST);

    Event::log(
        $link->fields['problems_id'],
        "problem",
        4,
        "maintain",
        //TRANS: %s is the user login
        sprintf(__('%s deletes an actor'), $_SESSION["glpiname"])
    );

    if ($item->can($link->fields["problems_id"], READ)) {
        Html::redirect($item->getFormURLWithID($link->fields['problems_id']));
    }
    Session::addMessageAfterRedirect(
        __s('You have been redirected because you no longer have access to this item'),
        true,
        ERROR
    );

    Html::redirect($CFG_GLPI["root_doc"] . "/front/problem.php");
} else {
    throw new BadRequestHttpException();
}

Html::popFooter();
