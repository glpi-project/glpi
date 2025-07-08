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

Session::checkRight('software', UPDATE);
$inst = new Item_SoftwareVersion();

// From asset - Software tab (add form)
if (isset($_POST['add'])) {
    if (
        isset($_POST['itemtype']) && isset($_POST['items_id']) && $_POST['items_id']
        && isset($_POST['softwareversions_id']) && $_POST['softwareversions_id']
    ) {
        if (
            $inst->add([
                'itemtype'        => $_POST['itemtype'],
                'items_id'        => $_POST['items_id'],
                'softwareversions_id' => $_POST['softwareversions_id'],
            ])
        ) {
            Event::log(
                $_POST["items_id"],
                $_POST['itemtype'],
                5,
                "inventory",
                //TRANS: %s is the user login
                sprintf(__('%s installs software'), $_SESSION["glpiname"])
            );
        }
    } else {
        $message = null;
        if (!isset($_POST['softwares_id']) || !$_POST['softwares_id']) {
            $message = __s('Please select a software!');
        } elseif (!isset($_POST['softwareversions_id']) || !$_POST['softwareversions_id']) {
            $message = __s('Please select a version!');
        }

        Session::addMessageAfterRedirect($message, true, ERROR);
    }
    Html::back();
}

throw new BadRequestHttpException();
