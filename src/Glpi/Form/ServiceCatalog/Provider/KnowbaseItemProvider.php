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

namespace Glpi\Form\ServiceCatalog\Provider;

use Glpi\Form\ServiceCatalog\ItemRequest;
use Glpi\FuzzyMatcher\FuzzyMatcher;
use Glpi\FuzzyMatcher\PartialMatchStrategy;
use KnowbaseItem;
use Override;

/** @implements LeafProviderInterface<\KnowbaseItem> */
final class KnowbaseItemProvider implements LeafProviderInterface
{
    private FuzzyMatcher $matcher;

    public function __construct()
    {
        $this->matcher = new FuzzyMatcher(new PartialMatchStrategy());
    }

    #[Override]
    public function getItems(ItemRequest $item_request): array
    {
        $category = $item_request->getCategory();
        $filter = $item_request->getFilter();

        $knowbase_items = [];
        $raw_knowbase_items = (new KnowbaseItem())->find([
            'forms_categories_id'     => $category ? $category->getID() : 0,
            'show_in_service_catalog' => true,
        ], ['name']);

        foreach ($raw_knowbase_items as $raw_knowbase_item) {
            $knowbase_item = new KnowbaseItem();
            $knowbase_item->getFromResultSet($raw_knowbase_item);
            $knowbase_item->post_getFromDB();

            // Fuzzy matching
            $name        = $knowbase_item->fields['name'] ?? "";
            $content     = $knowbase_item->fields['content'] ?? "";
            $description = $knowbase_item->fields['description'] ?? "";
            if (
                !$this->matcher->match($name, $filter)
                && !$this->matcher->match($content, $filter)
                && !$this->matcher->match($description, $filter)
            ) {
                continue;
            }

            /// Note: this is in theory less performant than applying the parameters
            // directly to the SQL query (which would require more complicated code).
            // However, the number of KB items is expected to be low, so this is acceptable.
            // If performance becomes an issue, we can revisit this and/or add a cache.
            if (!$knowbase_item->canViewItem()) {
                continue;
            }

            $knowbase_items[] = $knowbase_item;
        }

        return $knowbase_items;
    }
}
