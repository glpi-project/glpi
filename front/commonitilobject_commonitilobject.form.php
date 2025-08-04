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
 * @since 11.0.0
 */

Session::checkCentralAccess();

if (isset($_POST['purge'], $_POST['id'])) {
    [$link_class_1, $link_class_2, $link_id] = explode('_', $_POST['id'], 3);
    $link_class = $link_class_1 . '_' . $link_class_2;
    $itil_itil = getItemForItemtype($link_class);
    $_POST['id'] = (int) $link_id;
    $itil_itil->check($_POST['id'], PURGE);

    $itil_itil->delete($_POST, true);

    Event::log(
        $_POST['items_id'],
        strtolower($_POST['itemtype']),
        4,
        "tracking",
        //TRANS: %s is the user login
        sprintf(__('%s purges link between ITIL Objects'), $_SESSION["glpiname"])
    );
    Html::redirect($_POST['itemtype']::getFormURLWithID($_POST['items_id']));
}

throw new BadRequestHttpException();
