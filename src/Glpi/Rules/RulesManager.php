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

namespace Glpi\Rules;

use Config;
use Rule;
use RuleCollection;

use function Safe\json_decode;
use function Safe\json_encode;

final class RulesManager
{
    /**
     * Initialize rules for each collection that does not yet contains any rule.
     */
    public static function initializeRules(): void
    {
        global $CFG_GLPI;

        $rulecollections_types = $CFG_GLPI['rulecollections_types'];

        foreach ($CFG_GLPI['dictionnary_types'] as $itemtype) {
            $rulecollection = RuleCollection::getClassByType($itemtype);
            if ($rulecollection instanceof RuleCollection) {
                $rulecollections_types[] = get_class($rulecollection);
            }
        }

        $initialized_collections = json_decode(
            Config::getConfigurationValue('core', 'initialized_rules_collections'),
            true
        );
        if (!is_array($initialized_collections)) {
            // Reinitialize configuration value if stored value does not exists or is corrupted.
            // It can happen either if migration did not worked as expected, either if value
            // in database was corrupted/deleted.
            $initialized_collections = [];
        }

        foreach ($rulecollections_types as $rulecollection_type) {
            if (
                !is_a($rulecollection_type, RuleCollection::class, true)
                || in_array($rulecollection_type, $initialized_collections)
            ) {
                continue;
            }

            $rulecollection = new $rulecollection_type();
            $ruleclass = $rulecollection->getRuleClass();
            if (!($ruleclass instanceof Rule) || !$ruleclass->hasDefaultRules()) {
                continue;
            }

            if (countElementsInTable(Rule::getTable(), ['sub_type' => $ruleclass->getType()]) === 0) {
                $ruleclass->initRules(false);
            }

            // Mark collection as already initialized, to not reinitialize it on next update
            // if admin remove all corresponding rules.
            $initialized_collections[] = get_class($rulecollection);
            Config::setConfigurationValues(
                'core',
                ['initialized_rules_collections' => json_encode($initialized_collections)]
            );
        }
    }
}
