<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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
use Glpi\DBAL\QueryExpression;
use Glpi\Form\Export\Specification\DataRequirementSpecification;
use InvalidArgumentException;
use Search;

final class DatabaseMapper
{
    // Store itemtype => [name => id] relations.
    /** @var array<string, array<string, int>> $values */
    private array $values = [];

    /** @var array<int> $entities_restrictions */
    private array $entities_restrictions;

    public function __construct(array $entities_restrictions)
    {
        if (empty($entities_restrictions)) {
            throw new InvalidArgumentException("Must specify at least one entity");
        }

        $this->entities_restrictions = $entities_restrictions;
    }

    public function addMappedItem(string $itemtype, string $name, int $id): void
    {
        if (!$this->isValidItemtype($itemtype)) {
            return;
        }

        if (!isset($this->values[$itemtype])) {
            $this->values[$itemtype] = [];
        }

        $this->values[$itemtype][$name] = $id;
    }

    public function getItemId(string $itemtype, string $name): int
    {
        if (!$this->contextExist($itemtype, $name)) {
            // Can't recover from this point, it is the serializer
            // responsability to validate that all requirements are found in the
            // context before attempting to import the forms.
            throw new \LogicException();
        }

        return $this->values[$itemtype][$name];
    }

    /** @param DataRequirementSpecification[] $data_requirements */
    public function validateRequirements(array $data_requirements): bool
    {
        foreach ($data_requirements as $requirement) {
            $itemtype = $requirement->itemtype;
            $name = $requirement->name;

            if (!$this->contextExist($itemtype, $name)) {
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
            if ($this->contextExist($itemtype, $name)) {
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

    private function contextExist(string $itemtype, string $name): bool
    {
        return isset($this->values[$itemtype][$name]);
    }

    private function tryTofindOneRowByName(string $itemtype, string $name): ?array
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (!$this->isValidItemtype($itemtype)) {
            throw new InvalidArgumentException();
        }

        /** @var CommonDBTM $item */
        $item = new $itemtype();
        $query = [
            'FROM' => $item::getTable(),
        ];
        $condition = [
            'name' => $name
        ];

        // Entities restrictions are not always included in addDefaultWhere,
        // it is safer to add them manually (they might be checked twice tho).
        $entities_restrictions = getEntitiesRestrictCriteria(
            $item::getTable(),
            value: $this->entities_restrictions
        );
        $condition[] = $entities_restrictions;
        $query['WHERE'] = $condition;

        // Find item
        $rows = $DB->request($query);
        $rows = iterator_to_array($rows);
        if (count($rows) !== 1) {
            return null;
        }
        return current($rows);
    }
}
