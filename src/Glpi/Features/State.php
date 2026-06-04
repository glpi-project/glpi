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

namespace Glpi\Features;

use DropdownVisibility;
use Glpi\DBAL\QueryExpression;

trait State
{
    /**
     * Check if itemtype class is present in configuration array
     *
     * @return bool
     */
    private function hasStates(): bool
    {
        global $CFG_GLPI;

        return in_array(static::class, $CFG_GLPI['state_types']);
    }

    /**
     * @see StateInterface::isStateVisible()
     */
    public function isStateVisible(int $id): bool
    {
        if (!$this->hasStates()) {
            return false;
        }

        $dropdownVisibility = new DropdownVisibility();
        return $dropdownVisibility->getFromDBByCrit([
            'itemtype' => \State::class,
            'items_id' => $id,
            'visible_itemtype' => static::class,
            'is_visible' => 1,
        ]);
    }

    /**
     * @see StateInterface::getStateVisibilityCriteria()
     */
    public function getStateVisibilityCriteria(): array
    {
        if (!$this->hasStates()) {
            return [
                'WHERE' => [new QueryExpression('false')],
            ];
        }

        return [
            'LEFT JOIN' => [
                DropdownVisibility::getTable() => [
                    'ON' => [
                        DropdownVisibility::getTable() => 'items_id',
                        \State::getTable() => 'id', [
                            'AND' => [
                                DropdownVisibility::getTable() . '.itemtype' => \State::class,
                            ],
                        ],
                    ],
                ],
            ],
            'WHERE' => [
                DropdownVisibility::getTable() . '.itemtype' => \State::class,
                DropdownVisibility::getTable() . '.visible_itemtype' => static::class,
                DropdownVisibility::getTable() . '.is_visible' => 1,
            ],
        ];
    }
}
