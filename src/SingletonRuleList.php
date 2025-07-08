<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

class SingletonRuleList
{
    /** @var Rule[] */
    public $list = [];
    /// Items loaded ?
    public $load = 0;


    /**
     * get a unique instance of a SingletonRuleList for a type of RuleCollection
     *
     * @param string $type   type of the Rule listed
     * @param string $entity entity where the rule Rule is processed
     *
     * @return SingletonRuleList unique instance of an object
     **/
    public static function &getInstance($type, $entity)
    {
        //FIXME: can be removed when using phpunit 10 and process-isolation
        if (defined('TU_USER')) {
            $o = new self();
            return $o;
        }

        static $instances = [];

        if (!isset($instances[$type][$entity])) {
            $instances[$type][$entity] = new self();
        }
        return $instances[$type][$entity];
    }
}
