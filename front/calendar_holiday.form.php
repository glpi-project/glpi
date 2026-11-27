<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

Session::checkCentralAccess();

$item = new Calendar_Holiday();

if (isset($_POST["add"])) {
    // Several close times can be added at once
    $inputs = [];
    foreach (array_filter((array) ($_POST['holidays_id'] ?? [])) as $holidays_id) {
        $input = [
            'calendars_id' => (int) ($_POST['calendars_id'] ?? 0),
            'holidays_id'  => (int) $holidays_id,
        ];
        $inputs[] = $input;
        $item->check(-1, CREATE, $input);
    }

    foreach ($inputs as $input) {
        if ($item->add($input)) {
            Event::log(
                $input["calendars_id"],
                "calendars",
                4,
                "setup",
                //TRANS: %s is the user login
                sprintf(__('%s adds a link with an item'), $_SESSION["glpiname"])
            );
        }
    }
    Html::back();
}

throw new BadRequestHttpException();
