<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\DBAL;

abstract class Prepared
{
    protected string $sql;
    /** @var array<int, mixed> */
    protected array $values;

    public function setSQL(string $sql): static
    {
        $this->sql = $sql;
        return $this;
    }

    public function getSQL(): string
    {
        return $this->sql;
    }

    /**
     * @param array<int, mixed> $values
     */
    public function setValues(array $values): static
    {
        $this->values = $values;
        return $this;
    }

    /**
     * @return array<int, mixed>
     */
    public function getValues(): array
    {
        return $this->values;
    }

    public function __toString()
    {
        return $this->getSQL();
    }
}
