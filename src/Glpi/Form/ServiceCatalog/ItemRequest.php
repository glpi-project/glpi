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

use Glpi\Form\AccessControl\FormAccessParameters;
use Glpi\Form\ServiceCatalog\SortStrategy\SortStrategyEnum;

final class ItemRequest
{
    public function __construct(
        public FormAccessParameters $access_parameters,
        public string $filter = "",
        public ?int $category_id = null,
        public int $page = 1,
        public int $items_per_page = ServiceCatalogManager::ITEMS_PER_PAGE,
        public SortStrategyEnum $sort_strategy = SortStrategyEnum::POPULARITY,
        public ItemRequestContext $context = ItemRequestContext::SERVICE_CATALOG,
    ) {}

    public function getFormAccessParameters(): FormAccessParameters
    {
        return $this->access_parameters;
    }

    public function getFilter(): string
    {
        return $this->filter;
    }

    public function getCategoryID(): ?int
    {
        return $this->category_id;
    }

    public function getSortStrategy(): SortStrategyEnum
    {
        return $this->sort_strategy;
    }

    public function getContext(): ItemRequestContext
    {
        return $this->context;
    }
}
