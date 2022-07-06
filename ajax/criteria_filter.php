<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

use Glpi\Http\Response;
use Glpi\Search\FilterableInterface;

include('../inc/includes.php');

Session::checkLoginUser();

// Read endpoint
$action = $_POST['action'] ?? false;
switch ($action) {
    default:
        // Invalid action
        Response::sendError(400, "Invalid or missing value: action");

    case "save_filter":
        // Default values for this endpoint
        $itemtype = $_POST['item_itemtype'] ?? null; // Note: "item_" prefix because the search engine already use the itemtype key
        $items_id = $_POST['item_items_id'] ?? null;
        $search_criteria = $_POST['criteria'] ?? []; // Note: criteria may be missing in a valid form

        // Validate itemtype
        if (
            !is_a($itemtype, CommonDBTM::class, true)
            || !is_a($itemtype, FilterableInterface::class, true)
        ) {
            Response::sendError(400, 'Invalid or missing value: item_itemtype');
        }

        // Validate items_id
        $item = $itemtype::getById($items_id);
        if (!$item) {
            Response::sendError(400, 'Invalid or missing value: item_items_id');
        }

        // Validate search criteria
        if (!is_array($search_criteria)) {
            Response::sendError(400, 'Invalid value: criteria');
        }

        // Check rights, must be able to update parent item
        if (!$item->canUpdateItem()) {
            Response::sendError(403, 'You are not allowed to update this item');
        }

        // Save filters
        if (!$item->saveFilter($search_criteria)) {
            Response::sendError(422, 'Unable to process data');
        }

        // OK
        (new Response(200))->send();
        break;

    case "delete_filter":
        // Default values for this endpoint
        $itemtype = $_POST['itemtype'] ?? null;
        $items_id = $_POST['items_id'] ?? null;

        // Validate itemtype
        if (
            !is_a($itemtype, CommonDBTM::class, true)
            || !is_a($itemtype, FilterableInterface::class, true)
        ) {
            Response::sendError(400, 'Invalid or missing value: itemtype');
        }

        // Validate items_id
        $item = $itemtype::getById($items_id);
        if (!$item) {
            Response::sendError(400, 'Invalid or missing value: items_id');
        }

        // Check rights, must be able to update parent item
        if (!$item->canUpdateItem()) {
            Response::sendError(403, 'You are not allowed to update this item');
        }

        // Delete filters
        if (!$item->deleteFilter()) {
            Response::sendError(422, 'Unable to process data');
        }

        // OK
        (new Response(200))->send();
        break;
}
