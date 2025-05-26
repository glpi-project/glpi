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
 * @since 0.85
 */

$link = new Problem_Supplier();

Html::popHeader(__('Email followup'));

if (isset($_POST["update"])) {
    $link->check($_POST["id"], UPDATE);

    $link->update($_POST);
    echo "<script type='text/javascript' >\n";
    echo "window.parent.location.reload();";
    echo "</script>";
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
    Html::redirect(Problem::getFormURLWithID($link->fields['problems_id']));
} else {
    throw new BadRequestHttpException();
}

Html::popFooter();
