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
 * SoftwareLicense_User Class
 *
 * Relation between SoftwareLicense and Users
 **/
class SoftwareLicense_User extends CommonDBRelation
{
    // From CommonDBRelation
    public static $itemtype_1 = 'User';
    public static $items_id_1 = 'users_id';

    public static $itemtype_2 = 'SoftwareLicense';
    public static $items_id_2 = 'softwarelicenses_id';

    public static function getTypeName($nb = 0)
    {
        return User::getTypeName($nb);
    }

    public static function countForLicense($softwarelicenses_id)
    {
        return countElementsInTable(static::getTable(), ['softwarelicenses_id' => $softwarelicenses_id]);
    }
}
