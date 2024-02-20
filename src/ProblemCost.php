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
 * ProblemCost Class
 *
 * @since 0.85
 **/
class ProblemCost extends CommonITILCost
{
   // From CommonDBChild
    public static $itemtype  = 'Problem';
    public static $items_id  = 'problems_id';


    public static function canCreate()
    {
        return Session::haveRight('problem', UPDATE);
    }


    public static function canView()
    {
        return Session::haveRightsOr('problem', [Problem::READALL, Problem::READMY]);
    }


    public static function canUpdate()
    {
        return Session::haveRight('problem', UPDATE);
    }
}
