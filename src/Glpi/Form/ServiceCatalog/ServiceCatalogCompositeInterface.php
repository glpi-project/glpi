<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\Form\ServiceCatalog;

/**
 * Represent a composite of a the service catalog tree.
 * When the user click on this item (e.g. a category), he goes down the tree
 * and will see new leaves and composites items.
 */
interface ServiceCatalogCompositeInterface extends ServiceCatalogItemInterface
{
    /**
     * Get the URL parameters needed to load this composite item's children using
     * the `/ServiceCatalog/Item` endpoint.
     */
    public function getChildrenUrlParameters(): string;

    /**
     * Get the service catalog's item request that will return the children
     * of the current composite item.
     *
     * The current request is available as the $item_request parameter as some
     * common parameters like the form access rights are likely to be reused.
     */
    public function getChildrenItemRequest(
        ItemRequest $item_request,
    ): ItemRequest;

    /** @param ServiceCatalogItemInterface[] $children */
    public function setChildren(array $children): void;

    /** @return ServiceCatalogItemInterface[] */
    public function getChildren(): array;
}
