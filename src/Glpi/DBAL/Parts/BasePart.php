<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\DBAL\Parts;

use DBmysqlIterator;

abstract class BasePart
{
    protected string $query;
    /** @var array<int, mixed> */
    protected array $params;
    protected ?string $clause = null;

    /**
     * @param array<string, mixed> $criteria
     */
    public function withCriteria(array $criteria): static
    {
        global $DB;

        if ($criteria === []) {
            return $this;
        }

        $iterator = new DBmysqlIterator($DB);
        $it_criteria = ['FROM' => 'table'];
        if ($this->clause !== null) {
            $it_criteria += [$this->clause => $criteria];
        } else {
            $it_criteria += $criteria;
        }
        $iterator->buildQuery($it_criteria);

        return $this
            ->setQuery($iterator->getSql())
            ->setParams($iterator->getValues())
        ;
    }

    public function setQuery(string $query): static
    {
        $this->query = $query;
        return $this;
    }

    public function getQuery(): string
    {
        return $this->query ?? '';
    }

    /**
     * @param array<int, mixed> $values
     */
    public function setParams(array $values): static
    {
        $this->params = $values;
        return $this;
    }

    /**
     * @return array<int, mixed>
     */
    public function getParams(): array
    {
        return $this->params ?? [];
    }

    public function __toString()
    {
        return $this->getQuery();
    }
}
