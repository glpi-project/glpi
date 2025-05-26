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

namespace Glpi\Form\ServiceCatalog\SortStrategy;

use Glpi\Form\ServiceCatalog\ServiceCatalogCompositeInterface;
use Glpi\Form\ServiceCatalog\ServiceCatalogItemInterface;

abstract class AbstractSortStrategy implements SortStrategyInterface
{
    public function sort(array $items): array
    {
        usort($items, function (
            ServiceCatalogItemInterface $a,
            ServiceCatalogItemInterface $b,
        ) {
            // First compare pinned status
            if ($a->isServiceCatalogItemPinned() !== $b->isServiceCatalogItemPinned()) {
                return $b->isServiceCatalogItemPinned() <=> $a->isServiceCatalogItemPinned();
            }

            // Then handle composite vs non-composite (composite first)
            if (
                $a instanceof ServiceCatalogCompositeInterface
                && !($b instanceof ServiceCatalogCompositeInterface)
            ) {
                return -1;
            }

            if (
                !($a instanceof ServiceCatalogCompositeInterface)
                && $b instanceof ServiceCatalogCompositeInterface
            ) {
                return 1;
            }

            // Delegate the final comparison to the specific strategy
            return $this->compareItems($a, $b);
        });

        return $items;
    }

    /**
     * Compare two service catalog items according to the specific strategy
     *
     * @param ServiceCatalogItemInterface $a
     * @param ServiceCatalogItemInterface $b
     * @return int Negative if $a should come before $b, positive if $b should come before $a, 0 if equal
     */
    abstract protected function compareItems(
        ServiceCatalogItemInterface $a,
        ServiceCatalogItemInterface $b
    ): int;
}
