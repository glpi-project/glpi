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
use Glpi\Form\Form;
use KnowbaseItem;

final class PopularitySort implements SortStrategyInterface
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

            // Sort by popularity (view count or submission count)
            $a_popularity = $this->getPopularity($a);
            $b_popularity = $this->getPopularity($b);

            if ($a_popularity !== $b_popularity) {
                return $b_popularity <=> $a_popularity; // Higher popularity first
            }

            // If popularity is equal, fall back to alphabetical
            return $a->getServiceCatalogItemTitle() <=> $b->getServiceCatalogItemTitle();
        });

        return $items;
    }

    private function getPopularity(ServiceCatalogItemInterface $item): int
    {
        // For Forms, we could use usage count
        if ($item instanceof Form) {
            return $item->getUsageCount();
        }

        // For KnowledgeBase items, we could use view count
        if ($item instanceof KnowbaseItem) {
            return $item->fields['view'];
        }

        // For categories, we could use the popularity sum of its children
        if ($item instanceof ServiceCatalogCompositeInterface) {
            $popularity = 0;
            foreach ($item->getChildren() as $child) {
                $popularity += $this->getPopularity($child);
            }
            return $popularity;
        }

        return 0;
    }

    public function getLabel(): string
    {
        return __('Most popular');
    }

    public function getIcon(): string
    {
        return 'ti ti-star';
    }
}
