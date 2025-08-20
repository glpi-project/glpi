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

use function Safe\json_encode;

header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

$result = [];
if (!isset($_POST['itemtype']) || !isset($_POST['items_id']) || (int) $_POST['items_id'] < 1) {
    $result = [
        'success'   => false,
        'message'   => __s('Required argument missing!'),
    ];
} else {
    $itemtype = $_POST['itemtype'];
    $items_id = $_POST['items_id'];

    if ($itemtype != Location::getType()) {
        $item = getItemForItemtype($itemtype);
        $found = $item->getFromDB($items_id);
        if ($found && isset($item->fields['locations_id']) && (int) $item->fields['locations_id'] > 0) {
            $itemtype = Location::getType();
            $items_id = $item->fields['locations_id'];
        } else {
            $result = [
                'success'   => false,
                'message'   => __s('Element seems not geolocalized or cannot be found'),
            ];
        }
    }

    if (!count($result)) {
        /** @var CommonDBTM $item */
        $item = getItemForItemtype($itemtype);
        if (!$item->can($items_id, READ)) {
            $result = [
                'success'   => false,
                'message'   => __s('Not allowed'),
            ];
        } else {
            $item->getFromDB($items_id);
            if (!empty($item->fields['latitude']) && !empty($item->fields['longitude'])) {
                $result = [
                    'lat'    => (float) $item->fields['latitude'],
                    'lng'    => (float) $item->fields['longitude'],
                ];
            } else {
                $result = [
                    'success'   => false,
                    'message'   => "<h3>" . __("Location seems not geolocalized!") . "</h3>"
                               . "<a href='" . htmlescape($item->getLinkURL()) . "'>" . __s("Consider filling latitude and longitude on this location.") . "</a>",
                ];
            }
        }
    }
}

echo json_encode($result);
