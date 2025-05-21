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
 * @copyright 2010-2022 by the FusionInventory Development Team.
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

class RuleLocation extends Rule
{
    public static $rightname = 'rule_location';

    public function getTitle()
    {
        return __('Location rules');
    }

    public function executeActions($output, $params, array $input = [])
    {
        foreach ($this->actions as $action) {
            switch ($action->fields["action_type"]) {
                case "assign":
                    $output[$action->fields["field"]] = $action->fields["value"];
                    break;
                case 'regex_result':
                    if ($action->fields["field"] === "locations_id") {
                        foreach ($this->regex_results as $regex_result) {
                            $regexvalue          = RuleAction::getRegexResultById(
                                $action->fields["value"],
                                $regex_result
                            );

                            // from rule test context just assign regex value to key
                            if ($this->is_preview) {
                                $output['locations_id'] = $regexvalue;
                            } else {
                                $compute_entities_id = $input['entities_id'] ?? 0;
                                $location = new Location();
                                $output['locations_id'] = $location->importExternal($regexvalue, $compute_entities_id);
                            }
                        }
                    }
                    break;
            }
        }
        return $output;
    }

    public function getCriterias()
    {
        return [
            'itemtype' => [
                'name'            => sprintf('%s > %s', _n('Asset', 'Assets', 1), __('Item type')),
                'type'            => 'dropdown_inventory_itemtype',
                'is_global'       => false,
                'allow_condition' => [
                    Rule::PATTERN_IS,
                    Rule::PATTERN_IS_NOT,
                    Rule::PATTERN_EXISTS,
                    Rule::PATTERN_DOES_NOT_EXISTS,
                ],
            ],
            'tag' => [
                'name'            => sprintf('%s > %s', Agent::getTypeName(1), __('Inventory tag')),
            ],
            'domain' => [
                'name'            => Domain::getTypeName(1),
            ],
            'subnet' => [
                'name'            => __("Subnet"),
            ],
            'ip' => [
                'name'            => sprintf('%s > %s', NetworkPort::getTypename(1), __('IP')),
            ],
            'name' => [
                'name'            => __("Name"),
            ],
            'serial' => [
                'name'            => __("Serial number"),
            ],
            'oscomment' => [
                'name'            => sprintf('%s > %s', OperatingSystem::getTypeName(1), _n('Comment', 'Comments', Session::getPluralNumber())),
            ],
        ];
    }

    public function getActions()
    {
        return [
            'locations_id' => [
                'name'  => _n('Location', 'Locations', 1),
                'type'  => 'dropdown',
                'table' => Location::getTable(),
                'force_actions' => [
                    'assign',
                    'regex_result',
                ],
            ],
        ];
    }

    public static function getIcon()
    {
        return Location::getIcon();
    }
}
