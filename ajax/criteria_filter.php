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

use Glpi\Search\FilterableInterface;

include('../inc/includes.php');

Session::checkLoginUser();

header("Content-Type: application/json; charset=UTF-8");

function criteria_filter_error(int $code, string $message)
{
    http_response_code($code);
    echo json_encode(['message' => $message]);
    die();
}

$action = $_POST['action'] ?? false;
switch ($action) {
    default:
        criteria_filter_error(400, "Invalid or missing value: action");
        break;

    case "save_filter":
        $itemtype = $_POST['item_itemtype'] ?? null; // Note: "item_" prefix because the search engine already use the itemtype key
        $items_id = $_POST['item_items_id'] ?? null;
        $search_criteria = $_POST['criteria'] ?? []; // Note: criteria may be missing in a valid form

        if (
            !is_string($itemtype)
            || !is_a($itemtype, CommonDBTM::class, true)
            || !is_a($itemtype, FilterableInterface::class, true)
        ) {
            criteria_filter_error(400, "Invalid or missing value: item_itemtype");
        }

        /** @var (CommonDBTM&FilterableInterface)|false $item */
        $item = $itemtype::getById($items_id);
        if (!$item) {
            criteria_filter_error(404, "Invalid or missing value: item_items_id");
        }

        if (!is_array($search_criteria)) {
            criteria_filter_error(400, "Invalid value: criteria");
        }

        if (!$item->canUpdateItem()) {
            criteria_filter_error(403, "You are not allowed to update this item");
        }

        if (!$item->saveFilter($search_criteria)) {
            criteria_filter_error(422, "Unable to process data");
        }

        echo json_encode([]);
        break;

    case "delete_filter":
        $itemtype = $_POST['itemtype'] ?? null;
        $items_id = $_POST['items_id'] ?? null;

        if (
            !is_string($itemtype)
            || !is_a($itemtype, CommonDBTM::class, true)
            || !is_a($itemtype, FilterableInterface::class, true)
        ) {
            criteria_filter_error(400, "Invalid or missing value: itemtype");
        }

        /** @var (CommonDBTM&FilterableInterface)|false $item */
        $item = $itemtype::getById($items_id);
        if (!$item) {
            criteria_filter_error(404, "Invalid or missing value: items_id");
        }

        if (!$item->canUpdateItem()) {
            criteria_filter_error(403, "You are not allowed to update this item");
        }

        if (!$item->deleteFilter()) {
            criteria_filter_error(422, "Unable to process data");
        }

        echo json_encode([]);
        break;
}
