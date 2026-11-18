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

use stdClass;
use Toolbox;

/**
 * Result set of a query.
 *
 * Rows are fully buffered when the query is executed, so the result can be counted and
 * sought, whatever the database driver is.
 *
 * Fetch methods move an internal cursor and return `null` once every row has been read,
 * like the `mysqli_result` methods they replace.
 *
 * @property-read int $num_rows    Deprecated, use {@link self::numRows()}.
 * @property-read int $field_count Deprecated, use {@link self::numFields()}.
 */
final class Result
{
    /**
     * Current cursor position.
     */
    private int $position = 0;

    /**
     * Column names, indexed by column position.
     *
     * @var list<string>
     */
    private array $names;

    /**
     * @param list<list<mixed>>                    $rows    Rows, each one indexed by column position.
     * @param list<array{name: string, table: string}> $columns Columns metadata, indexed by column position.
     */
    public function __construct(
        private array $rows,
        private readonly array $columns
    ) {
        $this->names = array_column($columns, 'name');
    }

    /**
     * Number of rows.
     */
    public function numRows(): int
    {
        return count($this->rows);
    }

    /**
     * Number of columns.
     */
    public function numFields(): int
    {
        return count($this->columns);
    }

    /**
     * Columns metadata, indexed by column position.
     *
     * `table` is the table name or alias the column comes from. It is not provided by
     * Doctrine DBAL and is therefore always an empty string.
     *
     * @return list<array{name: string, table: string}>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Move the cursor to the given row.
     *
     * @return bool `false` if the row does not exist.
     */
    public function seek(int $position): bool
    {
        if ($position < 0 || $position >= count($this->rows)) {
            return false;
        }
        $this->position = $position;
        return true;
    }

    /**
     * Fetch next row, indexed by column position.
     *
     * @return list<mixed>|null
     */
    public function fetchRow(): ?array
    {
        return $this->rows[$this->position++] ?? null;
    }

    /**
     * Fetch next row, indexed by column name.
     *
     * When several columns share a name, the last one wins.
     *
     * @return array<string, mixed>|null
     */
    public function fetchAssoc(): ?array
    {
        $row = $this->fetchRow();
        return $row === null ? null : $this->combine($row);
    }

    /**
     * Fetch next row, indexed by both column position and column name.
     *
     * @return array<int|string, mixed>|null
     */
    public function fetchArray(): ?array
    {
        $row = $this->fetchRow();
        return $row === null ? null : $row + $this->combine($row);
    }

    /**
     * Fetch next row as an object, whose properties are the column names.
     */
    public function fetchObject(): ?stdClass
    {
        $row = $this->fetchAssoc();
        return $row === null ? null : (object) $row;
    }

    /**
     * Fetch every remaining row, indexed by column name.
     *
     * @return list<array<string, mixed>>
     */
    public function fetchAllAssoc(): array
    {
        $rows = [];
        while (($row = $this->fetchAssoc()) !== null) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Release the rows.
     */
    public function free(): void
    {
        $this->rows = [];
        $this->position = 0;
    }

    /**
     * @param list<mixed> $row
     *
     * @return array<string, mixed>
     */
    private function combine(array $row): array
    {
        $assoc = [];
        foreach ($this->names as $index => $name) {
            $assoc[$name] = $row[$index];
        }
        return $assoc;
    }

    /**
     * @deprecated 13.0.0 Use self::numRows() or self::numFields().
     */
    public function __get(string $name): int
    {
        return match ($name) {
            'num_rows'    => $this->deprecatedProxy('num_rows', 'numRows', $this->numRows(...)),
            'field_count' => $this->deprecatedProxy('field_count', 'numFields', $this->numFields(...)),
            default       => throw new \LogicException(sprintf('Undefined property %s::$%s.', self::class, $name)),
        };
    }

    /**
     * @deprecated 13.0.0 Use self::fetchAssoc().
     *
     * @return array<string, mixed>|null
     */
    public function fetch_assoc(): ?array
    {
        return $this->deprecatedProxy('fetch_assoc()', 'fetchAssoc', $this->fetchAssoc(...));
    }

    /**
     * @deprecated 13.0.0 Use self::fetchRow().
     *
     * @return list<mixed>|null
     */
    public function fetch_row(): ?array
    {
        return $this->deprecatedProxy('fetch_row()', 'fetchRow', $this->fetchRow(...));
    }

    /**
     * @deprecated 13.0.0 Use self::fetchArray().
     *
     * @return array<int|string, mixed>|null
     */
    public function fetch_array(): ?array
    {
        return $this->deprecatedProxy('fetch_array()', 'fetchArray', $this->fetchArray(...));
    }

    /**
     * @deprecated 13.0.0 Use self::fetchObject().
     */
    public function fetch_object(): ?stdClass
    {
        return $this->deprecatedProxy('fetch_object()', 'fetchObject', $this->fetchObject(...));
    }

    /**
     * @deprecated 13.0.0 Use self::fetchAllAssoc().
     *
     * @return list<array<string, mixed>>
     */
    public function fetch_all(): array
    {
        return $this->deprecatedProxy('fetch_all()', 'fetchAllAssoc', $this->fetchAllAssoc(...));
    }

    /**
     * @deprecated 13.0.0 Use self::seek().
     */
    public function data_seek(int $offset): bool
    {
        return $this->deprecatedProxy('data_seek()', 'seek', fn() => $this->seek($offset));
    }

    /**
     * @deprecated 13.0.0 Use self::getColumns().
     *
     * @return list<object{name: string, table: string}>
     */
    public function fetch_fields(): array
    {
        return $this->deprecatedProxy(
            'fetch_fields()',
            'getColumns',
            fn() => array_map(static fn(array $column) => (object) $column, $this->getColumns())
        );
    }

    /**
     * @deprecated 13.0.0 Use self::free().
     */
    public function close(): void
    {
        $this->deprecatedProxy('close()', 'free', $this->free(...));
    }

    /**
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     */
    private function deprecatedProxy(string $legacy, string $replacement, callable $callback): mixed
    {
        Toolbox::deprecated(sprintf(
            '`%s::%s` is deprecated, use `%s::%s()` instead.',
            self::class,
            $legacy,
            self::class,
            $replacement
        ));
        return $callback();
    }
}
