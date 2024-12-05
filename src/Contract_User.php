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

/**
 * Contract_User Class
 *
 * Relation between Contracts and Users
 **/
class Contract_User extends CommonDBRelation
{
    // From CommonDBRelation
    public static $itemtype_1 = 'Contract';
    public static $items_id_1 = 'contracts_id';

    public static $itemtype_2 = 'User';
    public static $items_id_2 = 'users_id';

    public static $check_entity_coherency = false; // `entities_id`/`is_recursive` fields from user should not be used here

    public function getForbiddenStandardMassiveAction()
    {
        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }

    public function canCreateItem(): bool
    {
        // Try to load the contract
        $contract = $this->getConnexityItem(static::$itemtype_1, static::$items_id_1);
        if ($contract === false) {
            return false;
        }

        // Don't create a Contract_User on contract that is already max used
        if (
            ($contract->fields['max_links_allowed'] > 0)
            && (countElementsInTable(
                static::getTable(),
                ['contracts_id' => $this->input['contracts_id']]
            )
                >= $contract->fields['max_links_allowed'])
        ) {
            return false;
        }

        return parent::canCreateItem();
    }

    public static function getTypeName($nb = 0)
    {
        return User::getTypeName($nb);
    }
}
