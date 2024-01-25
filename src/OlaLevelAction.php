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
 * @since 9.2
 */


/**
 * Class OlaLevelAction
 */
class OlaLevelAction extends RuleAction
{
    public static $itemtype  = 'OlaLevel';
    public static $items_id  = 'olalevels_id';
    public $dohistory = true;

    public function __construct($rule_type = 'OlaLevel')
    {
        // Override in order not to use glpi_rules table.
        if ($rule_type !== static::$itemtype) {
            throw new \LogicException(
                sprintf(
                    '%s is not expected to be used with a different rule type than %s',
                    static::class,
                    static::$itemtype
                )
            );
        }
    }

    public function rawSearchOptions()
    {
       // RuleAction search options requires value of rules_id field which does not exists here
        return [];
    }
}
