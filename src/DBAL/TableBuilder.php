<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;

/**
 *
 * @phpstan-type ColumnOptions = array{precision?: int, scale?: int, fixed?: bool, length?: int, unsigned?: bool, notnull?: bool, default?: mixed, autoincrement?: bool, comment?: string}
 */
class TableBuilder
{
    private $table;

    public const TEXT_LENGTH = 65535;
    public const MEDIUMTEXT_LENGTH = 16777215;
    public const LONGTEXT_LENGTH = 4294967295;
    public const CHARSET = 'utf8mb4';
    public const COLLATION = 'utf8mb4_unicode_ci';

    public function __construct(string $table_name)
    {
        $this->table = new Table($table_name);
        $this->table->addOption('collation', self::COLLATION);
        $this->table->addOption('charset', self::CHARSET);
    }

    public function withID(): self
    {
        $this->table->addColumn('id', 'integer', ['autoincrement' => true]);
        $this->table->setPrimaryKey(['id']);

        return $this;
    }

    public function withCreateAndModDates(): self
    {
        $this->addDateTime('date_mod', ['notnull' => false, 'default' => null]);
        $this->addDateTime('date_creation', ['notnull' => false, 'default' => null]);
        // Add indexes
        $this->table->addIndex(['date_mod'], 'date_mod');
        $this->table->addIndex(['date_creation'], 'date_creation');

        return $this;
    }

    public function withEntity(bool $allow_recursive = true, bool $recursive_by_default = false): self
    {
        $this->addIDReference('entities_id');
        $this->addIndex('entities_id', ['entities_id']);
        if ($allow_recursive) {
            $this->addBoolean('is_recursive', ['default' => $recursive_by_default ? 1 : 0]);
            $this->addIndex('is_recursive', ['is_recursive']);
        }
        return $this;
    }

    /**
     * @param string $name
     * @param int $length
     * @param array $options
     * @phpstan-param ColumnOptions $options
     * @return $this
     * @throws SchemaException
     */
    public function addString(string $name, int $length = 255, array $options = []): self
    {
        $options['length'] = $length;

        $this->table->addColumn($name, 'string', $options);

        return $this;
    }

    public function addNullableString(string $name, int $length = 255, array $options = []): self
    {
        $options = array_merge(['notnull' => false, 'default' => null], $options);
        return $this->addString($name, $length, $options);
    }

    /**
     * @param string $name
     * @param int $length
     * @param array $options
     * @phpstan-param ColumnOptions $options
     * @return $this
     * @throws SchemaException
     */
    public function addText(string $name, int $length = self::TEXT_LENGTH, array $options = []): self
    {
        $options['length'] = $length;
        $this->table->addColumn($name, 'text', $options);

        return $this;
    }

    public function addNullableText(string $name, int $length = self::TEXT_LENGTH, array $options = []): self
    {
        $options = array_merge(['notnull' => false, 'default' => null], $options);
        return $this->addText($name, $length, $options);
    }

    /**
     * @param string $name
     * @param array $options
     * @phpstan-param ColumnOptions $options
     * @return $this
     * @throws SchemaException
     */
    public function addBoolean(string $name, array $options = []): self
    {
        $this->table->addColumn($name, 'boolean', $options);

        return $this;
    }

    /**
     * @param string $name
     * @param array $options
     * @phpstan-param ColumnOptions $options
     * @return $this
     * @throws SchemaException
     */
    public function addInteger(string $name, array $options = []): self
    {
        $this->table->addColumn($name, 'integer', $options);

        return $this;
    }

    public function addBigInteger(string $name, array $options = []): self
    {
        $this->table->addColumn($name, 'bigint', $options);

        return $this;
    }

    public function addTinyInteger(string $name, array $options = []): self
    {
        $this->table->addColumn($name, 'tinyint', $options);

        return $this;
    }

    /**
     * Adds a foreign key column but does not create the foreign key constraint (true foreign keys are not used by GLPI).
     * @param string $name
     * @param array $options
     * @phpstan-param ColumnOptions $options
     * @return $this
     * @throws SchemaException
     */
    public function addIDReference(string $name, array $options = []): self
    {
        $options = array_merge(['notnull' => true, 'unsigned' => true, 'default' => 0], $options);
        $this->table->addColumn($name, 'integer', $options);

        return $this;
    }

    /**
     * @param string $name
     * @param array $options
     * @phpstan-param ColumnOptions $options
     * @return $this
     * @throws SchemaException
     */
    public function addDateTime(string $name, array $options = []): self
    {
        $this->table->addColumn($name, 'timestamp', $options);

        return $this;
    }

    public function addIndex(string $name, array $columns, array $options = []): self
    {
        $this->table->addIndex($columns, $name, $options);

        return $this;
    }

    public function addUniqueIndex(string $name, array $columns, array $options = []): self
    {
        $this->table->addUniqueIndex($columns, $name, $options);

        return $this;
    }

    public function getSQL(): string
    {
        DB::establishConnection();
        /** @global DB $DBAL */
        global $DBAL;

        return implode("\n", $DBAL->getPlatform()->getCreateTableSQL($this->table));
    }

    public function create(): void
    {
        DB::establishConnection();
        /** @global DB $DBAL */
        global $DBAL;

        $schema_manager = $DBAL->getSchemaManager();
        if (!$schema_manager->tablesExist([$this->table->getName()])) {
            $schema_manager->createTable($this->table);
        }
    }
}
