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

namespace Glpi\Features;

use CommonDBTM;

/**
 * @phpstan-require-extends CommonDBTM
 */
interface AssignableItemInterface
{
    public static function canView(): bool;

    public function canViewItem(): bool;

    public static function canUpdate(): bool;

    public function canUpdateItem(): bool;

    public static function getAssignableVisiblityCriteria(): array;

    /**
     * @param string $interface
     * @phpstan-param 'central'|'helpdesk' $interface
     * @return array
     * @phpstan-return array<integer, string|array>
     */
    public function getRights($interface = 'central');

    public function prepareGroupFields(array $input);

    public function prepareInputForAdd($input);

    public function prepareInputForUpdate($input);

    public function post_addItem();

    public function post_updateItem($history = true);

    public function getEmpty();

    public function post_getFromDB();

    /**
     * Update the values in the 'glpi_groups_items' link table as needed based on the groups set in the 'groups_id' and 'groups_id_tech' fields.
     */
    public function updateGroupFields();
}
