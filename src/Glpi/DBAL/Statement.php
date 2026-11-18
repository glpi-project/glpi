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

namespace Glpi\DBAL;

use DBmysql;
use Doctrine\DBAL\Statement as DoctrineStatement;
use Toolbox;

/**
 * Prepared statement, obtained with {@link DBmysql::prepare()} and executed
 * with {@link DBmysql::executeStatement()}.
 *
 * @property-read int $affected_rows Deprecated, use {@link self::getAffectedRows()}.
 */
final class Statement
{
    private ?Result $result = null;

    private int $affected_rows = 0;

    /**
     * @internal Statements are created by {@link DBmysql::prepare()}.
     */
    public function __construct(
        private readonly DoctrineStatement $handle,
        private readonly string $query
    ) {}

    /**
     * SQL query of the statement.
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * Result set of the last execution, or `null` if it did not produce any
     * (e.g. `INSERT`, `UPDATE` or `DELETE` queries).
     */
    public function getResult(): ?Result
    {
        return $this->result;
    }

    /**
     * Number of rows affected by the last execution.
     */
    public function getAffectedRows(): int
    {
        return $this->affected_rows;
    }

    /**
     * Release the statement.
     */
    public function close(): void
    {
        // Doctrine DBAL statements are released once they are not referenced anymore.
        $this->result = null;
    }

    /**
     * @internal
     */
    public function getHandle(): DoctrineStatement
    {
        return $this->handle;
    }

    /**
     * @internal
     */
    public function setExecutionState(?Result $result, int $affected_rows): void
    {
        $this->result = $result;
        $this->affected_rows = $affected_rows;
    }

    /**
     * @deprecated 13.0.0 Use self::getResult().
     */
    public function get_result(): Result|false
    {
        Toolbox::deprecated(sprintf('`%1$s::get_result()` is deprecated, use `%1$s::getResult()` instead.', self::class));
        return $this->result ?? false;
    }

    /**
     * @deprecated 13.0.0 Use DBmysql::executeStatement().
     *
     * @param ?array<int|string, mixed> $params
     */
    public function execute(?array $params = null): bool
    {
        Toolbox::deprecated(sprintf('`%s::execute()` is deprecated, use `DBmysql::executeStatement()` instead.', self::class));

        /** @var DBmysql $DB */
        global $DB;
        $DB->executeStatement($this, $params);
        return true;
    }

    /**
     * @deprecated 13.0.0 Use self::getAffectedRows().
     */
    public function __get(string $name): int
    {
        if ($name !== 'affected_rows') {
            throw new \LogicException(sprintf('Undefined property %s::$%s.', self::class, $name));
        }
        Toolbox::deprecated(sprintf('`%1$s::$affected_rows` is deprecated, use `%1$s::getAffectedRows()` instead.', self::class));
        return $this->affected_rows;
    }
}
