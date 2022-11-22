<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

namespace Glpi\Rules;

use Rule;
use RuleCollection;

final class RulesManager
{
    /**
     * Initialize rules for each collection that does not yet contains any rule.
     */
    public static function initializeRules(): void
    {
        global $CFG_GLPI;

        $itemtypes = array_unique(array_merge($CFG_GLPI['rulecollections_types'], $CFG_GLPI['dictionnary_types']));

        foreach ($itemtypes as $itemtype) {
            $rulecollection = RuleCollection::getClassByType($itemtype);
            if (!($rulecollection instanceof RuleCollection)) {
                continue;
            }
            $ruleclass = $rulecollection->getRuleClass();
            if (!is_a($ruleclass, Rule::class, true) || !method_exists($ruleclass, 'initRules')) {
                continue;
            }
            if (countElementsInTable(Rule::getTable(), ['sub_type' => $ruleclass]) > 0) {
                continue; // Skip collections that already contains rules
            }

            $ruleclass::initRules(false, false, false);
        }
    }
}
