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

use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use Glpi\Exception\Http\UnprocessableEntityHttpException;
use Glpi\Search\FilterableInterface;

// Read endpoint
$action = $_POST['action'] ?? false;
switch ($action) {
    default:
        // Invalid action
        throw new BadRequestHttpException("Invalid or missing value: action");

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
            throw new BadRequestHttpException('Invalid or missing value: item_itemtype');
        }

        // Validate items_id
        /** @var (CommonDBTM&FilterableInterface)|false $item */
        $item = $itemtype::getById($items_id);
        if (!$item) {
            throw new NotFoundHttpException('Invalid or missing value: item_items_id');
        }

        // Validate search criteria
        if (!is_array($search_criteria)) {
            throw new BadRequestHttpException('Invalid value: criteria');
        }

        // Check rights, must be able to update parent item
        if (!$item->canUpdateItem()) {
            throw new AccessDeniedHttpException('You are not allowed to update this item');
        }

        // Save filters
        if (!$item->saveFilter($search_criteria)) {
            throw new UnprocessableEntityHttpException('Unable to process data');
        }

        // Send empty response when OK
        return;

    case "delete_filter":
        // Default values for this endpoint
        $itemtype = $_POST['itemtype'] ?? null;
        $items_id = $_POST['items_id'] ?? null;

        // Validate itemtype
        if (
            !is_a($itemtype, CommonDBTM::class, true)
            || !is_a($itemtype, FilterableInterface::class, true)
        ) {
            throw new BadRequestHttpException('Invalid or missing value: itemtype');
        }

        // Validate items_id
        /** @var (CommonDBTM&FilterableInterface)|false $item */
        $item = $itemtype::getById($items_id);
        if (!$item) {
            throw new NotFoundHttpException('Invalid or missing value: items_id');
        }

        // Check rights, must be able to update parent item
        if (!$item->canUpdateItem()) {
            throw new AccessDeniedHttpException('You are not allowed to update this item');
        }

        // Delete filters
        if (!$item->deleteFilter()) {
            throw new UnprocessableEntityHttpException('Unable to process data');
        }

        // Send empty response when OK
        return;
}
