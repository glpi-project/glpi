<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

$result = [];
if (!isset($_POST['itemtype']) || !isset($_POST['items_id']) || (int)$_POST['items_id'] < 1) {
    $result = [
        'success'   => false,
        'message'   => __s('Required argument missing!')
    ];
} else {
    $location_id = null;
    if ($_POST['itemtype'] !== Location::class) {
        $item  = getItemForItemtype($_POST['itemtype']);
        $found = $item && $item->getFromDB((int) $_POST['items_id']);
        if ($found && isset($item->fields['locations_id']) && (int)$item->fields['locations_id'] > 0) {
            $location_id = $item->fields['locations_id'];
        } else {
            $result = [
                'success'   => false,
                'message'   => __s('Element seems not geolocalized or cannot be found')
            ];
        }
    } else {
        $location_id = (int) $_POST['items_id'];
    }

    if ($location_id !== null) {
        $location = new Location();
        if ($location->getFromDB($location_id) && !empty($location->fields['latitude']) && !empty($location->fields['longitude'])) {
            $result = [
                'name'   => $location->getName(),
                'lat'    => $location->fields['latitude'],
                'lng'    => $location->fields['longitude']
            ];
        } else {
            $result = [
                'success'   => false,
                'message'   => "<h3>" . __("Location seems not geolocalized!") . "</h3>" .
                           "<a href='" . $location->getLinkURL() . "'>" . __s("Consider filling latitude and longitude on this location.") . "</a>"
            ];
        }
    }
}

echo json_encode($result);
