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

Session::checkRightsOr(Consumable::$rightname, [READ, READ_ASSIGNED]);

$con      = new Consumable();
$constype = new ConsumableItem();

if (isset($_POST["add_several"])) {
    $constype->check($_POST["consumableitems_id"], UPDATE);

    for ($i = 0; $i < $_POST["to_add"]; $i++) {
        unset($con->fields["id"]);
        $con->add($_POST);
    }
    Event::log(
        $_POST["consumableitems_id"],
        "consumableitems",
        4,
        "inventory",
        //TRANS: %s is the user login
        sprintf(__('%s adds consumables'), $_SESSION["glpiname"])
    );

    Html::back();
} else {
    Html::back();
}
