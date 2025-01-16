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

namespace Glpi\Form\ServiceCatalog;

use Glpi\Form\ServiceCatalog\Provider\CategoryProvider;
use Glpi\Form\ServiceCatalog\Provider\CompositeProviderInterface;
use Glpi\Form\ServiceCatalog\Provider\FormProvider;
use Glpi\Form\ServiceCatalog\Provider\LeafProviderInterface;

final class ServiceCatalogManager
{
    /** @var \Glpi\Form\ServiceCatalog\Provider\ItemProviderInterface[] */
    private array $providers;

    public function __construct()
    {
        $this->providers = [
            new FormProvider(),
            new CategoryProvider(),
        ];
    }

    /**
     * Return all available forms and non empties categories for the given user.
     *
     * @return ServiceCatalogItemInterface[]
     */
    public function getItems(ItemRequest $item_request): array
    {
        $items = [];

        // Load root items
        foreach ($this->providers as $provider) {
            array_push($items, ...$provider->getItems($item_request));
        }

        // Load children for composite (non recursive, only for the first level)
        foreach ($items as $item) {
            if (!($item instanceof ServiceCatalogCompositeInterface)) {
                continue;
            }

            $children = [];
            $children_request = $item->getChildrenItemRequest($item_request);
            foreach ($this->providers as $provider) {
                array_push($children, ...$provider->getItems($children_request));
            }

            // We don't want to display empty categories
            $children = $this->removeChildrenCompositeWithoutChildren(
                $children_request,
                $children
            );
            $children = $this->sortChildItems($children);
            $item->setChildren($children);
        }

        // Remove empty composite, must be done after the children has been loaded.
        $items = $this->removeRootCompositeWithoutChildren($items);
        $items = $this->sortRootItems($items);

        return $items;
    }

    /**
     * Remove composite items from the root level of the tree that do not have
     * any children.
     *
     * Since children have already been computed at this point, we only need to
     * check for the result of `getChildren`.
     *
     * @param ServiceCatalogItemInterface[] $items
     * @return ServiceCatalogItemInterface[]
     */
    private function removeRootCompositeWithoutChildren(
        array $items,
    ): array {
        return array_filter(
            $items,
            function (ServiceCatalogItemInterface $item) {
                // Not a composite, do not remove it from the list.
                if ($item instanceof ServiceCatalogLeafInterface) {
                    return true;
                }

                // Is a composite, we must check if it has any children.
                // Since children have already been computed at this point, we
                // only need to check for the result of `getChildren()`.
                if ($item instanceof ServiceCatalogCompositeInterface) {
                    return count($item->getChildren()) > 0;
                }

                // Unknown implementation, should never happen.
                throw new \RuntimeException("Unsupported item: " . get_class($item));
            }
        );
    }

    /**
     * Remove composite items from the second level of the tree that do not have
     * any children.
     *
     * @param ServiceCatalogItemInterface[] $items
     * @return ServiceCatalogItemInterface[]
     */
    private function removeChildrenCompositeWithoutChildren(
        ItemRequest $item_request,
        array $items,
    ): array {
        return array_filter(
            $items,
            function (ServiceCatalogItemInterface $item) use ($item_request) {
                // Not a composite, do not remove it from the list.
                if ($item instanceof ServiceCatalogLeafInterface) {
                    return true;
                }

                // Is a composite, we must check if it has any children.
                if ($item instanceof ServiceCatalogCompositeInterface) {
                    return $this->hasChildren($item_request, $item);
                }

                // Unknown implementation, should never happen.
                throw new \RuntimeException("Unsupported item: " . get_class($item));
            }
        );
    }

    private function hasChildren(
        ItemRequest $item_request,
        ?ServiceCatalogCompositeInterface $composite = null,
    ): bool {
        $leaf_providers = array_filter(
            $this->providers,
            fn($p) => $p instanceof LeafProviderInterface
        );
        $composite_providers = array_filter(
            $this->providers,
            fn($p) => $p instanceof CompositeProviderInterface
        );
        $child_request = $composite->getChildrenItemRequest($item_request);

        // Check if the composite has at least one direct children (any leaf)
        foreach ($leaf_providers as $provider) {
            $items = $provider->getItems($child_request);
            if (count($items)) {
                return true;
            }
        }

        // Load sub composites to look for indirect children
        foreach ($composite_providers as $provider) {
            $items = $provider->getItems($child_request);

            // Can't have any indirect children if we have no sub composites
            if (count($items) === 0) {
                return false;
            }

            foreach ($items as $item) {
                // Look for indirect children using recursion
                if ($this->hasChildren($item_request, $item)) {
                    return true;
                }
            }

            // No leaf found in all sub composites
            return false;
        }

        return false;
    }

    /**
     * @param ServiceCatalogItemInterface[] $items
     * @return ServiceCatalogItemInterface[]
     */
    private function sortChildItems(array $items): array
    {
        usort($items, function (
            ServiceCatalogItemInterface $a,
            ServiceCatalogItemInterface $b,
        ) {
            return $a->getServiceCatalogItemTitle() <=> $b->getServiceCatalogItemTitle();
        });

        return $items;
    }

    /**
     * @param ServiceCatalogItemInterface[] $items
     * @return ServiceCatalogItemInterface[]
     */
    private function sortRootItems(array $items): array
    {
        // Root items are sorted with categories at the end as they will be
        // displayed differently
        usort($items, function (
            ServiceCatalogItemInterface $a,
            ServiceCatalogItemInterface $b,
        ) {
            if (
                $a instanceof ServiceCatalogCompositeInterface
                && !($b instanceof ServiceCatalogCompositeInterface)
            ) {
                return 1;
            }

            if (
                !($a instanceof ServiceCatalogCompositeInterface)
                && $b instanceof ServiceCatalogCompositeInterface
            ) {
                return -1;
            }

            return $a->getServiceCatalogItemTitle() <=> $b->getServiceCatalogItemTitle();
        });

        return $items;
    }
}
