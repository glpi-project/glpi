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

namespace Glpi\Form\Export\Context;

use CommonDBTM;
use CommonTreeDropdown;
use Glpi\Form\Comment;
use Glpi\Form\Export\Specification\DataRequirementSpecification;
use Glpi\Form\Form;
use Glpi\Form\Question;
use Glpi\Form\Section;
use Glpi\Toolbox\MapperInterface;
use InvalidArgumentException;
use LogicException;
use Override;

final class DatabaseMapper implements MapperInterface
{
    // Store itemtype => [name => id] relations.
    /** @var array<string, array<string, int>> $values */
    private array $values = [];

    /** @var array<int> $entities_restrictions */
    private array $entities_restrictions;

    public function __construct(array $entities_restrictions)
    {
        if ($entities_restrictions === []) {
            throw new InvalidArgumentException("Must specify at least one entity");
        }

        $this->entities_restrictions = $entities_restrictions;
    }

    #[Override]
    public function addMappedItem(string $itemtype, string|int $key, int $id): void
    {
        if (!$this->isValidItemtype($itemtype)) {
            return;
        }

        if (!isset($this->values[$itemtype])) {
            $this->values[$itemtype] = [];
        }

        $this->values[$itemtype][$key] = $id;
    }

    #[Override]
    public function getItemId(string $itemtype, string|int $key): int
    {
        if (!$this->contextExist($itemtype, $key)) {
            // Can't recover from this point, it is the serializer
            // responsability to validate that all requirements are found in the
            // context before attempting to import the forms.
            throw new LogicException("Unknown item: {$itemtype}::{$key}");
        }

        return $this->values[$itemtype][$key];
    }

    /**
     * Check whether an id is mapped for the given itemtype and key.
     */
    public function hasItemId(string $itemtype, string|int $key): bool
    {
        return isset($this->values[$itemtype][$key]);
    }

    /** @param DataRequirementSpecification[] $data_requirements */
    public function validateRequirements(array $data_requirements): bool
    {
        foreach ($data_requirements as $requirement) {
            $itemtype = $requirement->itemtype;
            $name = $requirement->name;

            if (!$this->hasItemId($itemtype, $name)) {
                return false;
            }
        }

        return true;
    }

    /** @param DataRequirementSpecification[] $data_requirements */
    public function mapExistingItemsForRequirements(
        array $data_requirements
    ): bool {
        foreach ($data_requirements as $requirement) {
            $itemtype = $requirement->itemtype;
            $name = $requirement->name;

            // Skip if already defined
            if ($this->hasItemId($itemtype, $name)) {
                continue;
            }

            // Skip if invalid type
            if (!$this->isValidItemtype($itemtype)) {
                continue;
            }

            // Try to find exactly one item
            $item = $this->tryTofindOneRowByName($itemtype, $name);
            if ($item === null) {
                continue;
            }

            $this->addMappedItem($itemtype, $name, $item['id']);
        }

        return true;
    }

    private function isValidItemtype(string $itemtype): bool
    {
        return is_a($itemtype, CommonDBTM::class, true);
    }

    /** @phpstan-assert-if-true class-string<Question|Section|Comment> $itemtype */
    private function isFormItemtype(string $itemtype): bool
    {
        return $itemtype === Question::class
            || $itemtype === Comment::class
            || $itemtype === Section::class
        ;
    }

    private function contextExist(string $itemtype, string $name): bool
    {
        if ($this->isFormItemtype($itemtype)) {
            return true;
        }

        return isset($this->values[$itemtype][$name]);
    }

    private function tryTofindOneRowByName(string $itemtype, string $name): ?array
    {
        global $DB;

        if (!$this->isValidItemtype($itemtype)) {
            throw new InvalidArgumentException();
        }

        if ($this->isFormItemtype($itemtype)) {
            $query = $this->getFormItemQuery($itemtype, $name);
        } else {
            $query = $this->getItemQuery($itemtype, $name);
        }

        // Find item
        $rows = $DB->request($query);
        $rows = iterator_to_array($rows);
        if (count($rows) !== 1) {
            return null;
        }
        return current($rows);
    }

    /**
     * Questions, sections and comments are not entity assigned and their names
     * are not unique: only look for them in the forms that are visible in the
     * entities restrictions and that are not deleted.
     *
     * @param class-string<Question|Section|Comment> $itemtype
     * @return array<mixed>
     */
    private function getFormItemQuery(string $itemtype, string $name): array
    {
        $table = $itemtype::getTable();
        $forms_table = Form::getTable();
        $query = [
            'SELECT' => ["$table.id"],
            'FROM'   => $table,
            'WHERE'  => [
                "$table.name"             => $name,
                "$forms_table.is_deleted" => 0,
                getEntitiesRestrictCriteria(
                    $forms_table,
                    value: $this->entities_restrictions,
                    is_recursive: true,
                ),
            ],
        ];

        if ($itemtype === Section::class) {
            $query['INNER JOIN'][$forms_table] = [
                'ON' => [
                    $table       => Form::getForeignKeyField(),
                    $forms_table => 'id',
                ],
            ];
        } else {
            $sections_table = Section::getTable();
            $query['INNER JOIN'][$sections_table] = [
                'ON' => [
                    $table          => Section::getForeignKeyField(),
                    $sections_table => 'id',
                ],
            ];
            $query['INNER JOIN'][$forms_table] = [
                'ON' => [
                    $sections_table => Form::getForeignKeyField(),
                    $forms_table    => 'id',
                ],
            ];
        }

        return $query;
    }

    /** @return array<mixed> */
    private function getItemQuery(string $itemtype, string $name): array
    {
        $item = getItemForItemtype($itemtype);
        $query = [
            'FROM' => $item::getTable(),
        ];

        if ($item instanceof CommonTreeDropdown) {
            $condition = ['completename' => $name];
        } else {
            $condition = ['name' => $name];
        }

        // Check entities
        if ($item->isEntityAssign()) {
            $entities_restrictions = getEntitiesRestrictCriteria(
                $item::getTable(),
                value: $this->entities_restrictions
            );
            $condition[] = $entities_restrictions;
        }
        $query['WHERE'] = $condition;

        return $query;
    }

    /**
     * Get requirements with invalid context.
     *
     * @param DataRequirementSpecification[] $data_requirements
     * @return DataRequirementSpecification[]
     */
    public function getInvalidRequirements(array $data_requirements): array
    {
        $invalid_requirements = [];

        foreach ($data_requirements as $requirement) {
            $itemtype = $requirement->itemtype;
            $name = $requirement->name;

            if (!$this->hasItemId($itemtype, $name)) {
                $invalid_requirements[] = $requirement;
            }
        }

        return $invalid_requirements;
    }
}
