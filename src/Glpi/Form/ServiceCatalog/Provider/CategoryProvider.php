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

namespace Glpi\Form\ServiceCatalog\Provider;

use Glpi\Form\Category;
use Glpi\Form\ServiceCatalog\ItemRequest;
use Glpi\FuzzyMatcher\FuzzyMatcher;
use Glpi\FuzzyMatcher\PartialMatchStrategy;
use Override;

/** @implements CompositeProviderInterface<\Glpi\Form\Category> */
final class CategoryProvider implements CompositeProviderInterface
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

        $categories = [];
        $raw_categories = (new Category())->find([
            'forms_categories_id' => $category ? $category->getID() : 0,
        ], ['name']);

        foreach ($raw_categories as $raw_categoriy) {
            $category = new Category();
            $category->getFromResultSet($raw_categoriy);
            $category->post_getFromDB();

            // Fuzzy matching
            $name = $category->fields['name'] ?? "";
            $description = $category->fields['description'] ?? "";
            if (
                !$this->matcher->match($name, $filter)
                && !$this->matcher->match($description, $filter)
            ) {
                continue;
            }

            $categories[] = $category;
        }

        return $categories;
    }
}
