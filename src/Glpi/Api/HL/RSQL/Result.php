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

namespace Glpi\Api\HL\RSQL;

use Glpi\DBAL\QueryExpression;

class Result
{
    /**
     * @param QueryExpression $where_criteria
     * @param QueryExpression $having_criteria
     * @param array<string, Error> $invalid_filters
     */
    public function __construct(
        private QueryExpression $where_criteria,
        private QueryExpression $having_criteria,
        private array $invalid_filters = []
    ) {}

    public function getSQLWhereCriteria(): QueryExpression
    {
        return $this->where_criteria;
    }

    public function getSQLHavingCriteria(): QueryExpression
    {
        return $this->having_criteria;
    }

    public function getInvalidFilters(): array
    {
        return $this->invalid_filters;
    }
}
