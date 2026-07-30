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

use Glpi\DBAL\Operator;

class Where extends BasePart
{
    protected Operator $operator = Operator::NONE;
    protected ?string $clause = 'WHERE';

    public function setOperator(Operator $operator): static
    {
        $this->operator = $operator;
        return $this;
    }

    public function getOperator(): Operator
    {
        return $this->operator;
    }

    public function getQuery(): string
    {
        $sql = parent::getQuery();
        if ($this->operator !== Operator::NONE) {
            $sql = $this->operator->value . ' ' . $sql;
        }
        return $sql;
    }

    public function setQuery(string $sql): static
    {
        parent::setQuery($sql);

        //remove text clause from SQL
        $this->query = trim(str_replace('SELECT * FROM `table` ' . $this->clause, '', $this->query));
        //Handle case where clause is missing - which should not happen.
        $this->query = trim(str_replace('SELECT * FROM `table`', '', $this->query));
        return $this;
    }
}
