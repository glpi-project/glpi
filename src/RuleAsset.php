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

class RuleAsset extends Rule
{
    // From Rule
    public static $rightname = 'rule_asset';
    public $can_sort  = true;

    public const ONADD    = 1;
    public const ONUPDATE = 2;

    public const PARENT  = 1024;

    public function getTitle()
    {
        return __('Business rules for assets');
    }


    public function maybeRecursive()
    {
        return true;
    }


    public function isEntityAssign()
    {
        return true;
    }


    public function canUnrecurs()
    {
        return true;
    }


    public static function getConditionsArray()
    {

        return [static::ONADD                   => __('Add'),
            static::ONUPDATE                => __('Update'),
            static::ONADD | static::ONUPDATE  => sprintf(
                __('%1$s / %2$s'),
                __('Add'),
                __('Update')
            ),
        ];
    }


    public function getCriterias()
    {

        static $criterias = [];

        if (count($criterias)) {
            return $criterias;
        }

        $criterias['_auto']['name']            = __('Automatic inventory');
        $criterias['_auto']['type']            = 'yesno';
        $criterias['_auto']['table']           = '';
        $criterias['_auto']['allow_condition'] = [Rule::PATTERN_IS, Rule::PATTERN_IS_NOT];

        $criterias['_itemtype']['name']             = __('Item type');
        $criterias['_itemtype']['type']             = 'dropdown_assets_itemtype';
        $criterias['_itemtype']['allow_condition']  = [Rule::PATTERN_IS,
            Rule::PATTERN_IS_NOT,
            Rule::REGEX_MATCH,
            Rule::REGEX_NOT_MATCH,
        ];

        $criterias['states_id']['table']            = 'glpi_states';
        $criterias['states_id']['field']            = 'states_id';
        $criterias['states_id']['name']             = __('Status');
        $criterias['states_id']['type']             = 'dropdown';

        $criterias['comment']['name']               = __('Comments');

        $criterias['contact']['name']               = __('Alternate username');

        $criterias['contact_num']['name']           = __('Alternate username number');

        $criterias['manufacturer']['name']          = Manufacturer::getTypeName(1);
        $criterias['manufacturer']['table']         = 'glpi_manufacturers';
        $criterias['manufacturer']['field']         = 'manufacturers_id';
        $criterias['manufacturer']['type']          = 'dropdown';

        $criterias['locations_id']['table']         = 'glpi_locations';
        $criterias['locations_id']['field']         = 'completename';
        $criterias['locations_id']['name']          = Location::getTypeName(Session::getPluralNumber());
        $criterias['locations_id']['linkfield']     = 'locations_id';
        $criterias['locations_id']['type']          = 'dropdown';

        $criterias['entities_id']['table']          = 'glpi_entities';
        $criterias['entities_id']['field']          = 'name';
        $criterias['entities_id']['name']           = Entity::getTypeName(1);
        $criterias['entities_id']['linkfield']      = 'entities_id';
        $criterias['entities_id']['type']           = 'dropdown';

        $criterias['users_id']['name']            = User::getTypeName(1);
        $criterias['users_id']['type']            = 'dropdown_users';
        $criterias['users_id']['table']           = 'glpi_users';

        $criterias['_tag']['name']            = sprintf('%s > %s', Agent::getTypeName(1), __('Inventory tag'));

        $criterias['_locations_id_of_user']['table']     = 'glpi_locations';
        $criterias['_locations_id_of_user']['field']     = 'completename';
        $criterias['_locations_id_of_user']['name']      = __('User location');
        $criterias['_locations_id_of_user']['linkfield'] = '_locations_id_of_user';
        $criterias['_locations_id_of_user']['type']      = 'dropdown';

        $criterias['_groups_id_of_user']['table']        = 'glpi_groups';
        $criterias['_groups_id_of_user']['field']        = 'completename';
        $criterias['_groups_id_of_user']['name']         = __('User in group');
        $criterias['_groups_id_of_user']['linkfield']    = '_groups_id_of_user';
        $criterias['_groups_id_of_user']['type']         = 'dropdown';

        return $criterias;
    }


    public function getActions()
    {

        $actions                                = parent::getActions();

        $actions['states_id']['name']           = __('Status');
        $actions['states_id']['type']           = 'dropdown';
        $actions['states_id']['table']          = 'glpi_states';

        $actions['locations_id']['name']          = Location::getTypeName(1);
        $actions['locations_id']['type']          = 'dropdown';
        $actions['locations_id']['table']         = 'glpi_locations';
        $actions['locations_id']['force_actions'] = ['assign', 'fromuser'];

        $actions['users_id']['name']            = User::getTypeName(1);
        $actions['users_id']['type']            = 'dropdown_users';
        $actions['users_id']['table']           = 'glpi_users';

        $actions['_affect_user_by_regex']['name']              = __('User based contact information');
        $actions['_affect_user_by_regex']['type']              = 'text';
        $actions['_affect_user_by_regex']['force_actions']     = ['regex_result'];
        $actions['_affect_user_by_regex']['duplicatewith']     = 'users_id';

        $actions['groups_id']['name']          = Group::getTypeName(1);
        $actions['groups_id']['type']          = 'dropdown';
        $actions['groups_id']['table']         = 'glpi_groups';
        $actions['groups_id']['condition']     = ['is_itemgroup' => 1];
        $actions['groups_id']['force_actions'] = ['assign', 'defaultfromuser', 'firstgroupfromuser'];

        $actions['users_id_tech']['table']      = 'glpi_users';
        $actions['users_id_tech']['type']       = 'dropdown_users';
        $actions['users_id_tech']['name']       = __('Technician in charge');

        $actions['groups_id_tech']['name']      = __('Group in charge');
        $actions['groups_id_tech']['type']      = 'dropdown';
        $actions['groups_id_tech']['table']     = 'glpi_groups';
        $actions['groups_id_tech']['condition'] = ['is_assign' => 1];

        $actions['comment']['table']            = '';
        $actions['comment']['field']            = 'comment';
        $actions['comment']['name']             = __('Comments');
        $actions['comment']['force_actions']    = ['assign', 'regex_result'];

        $actions['otherserial']['name']              = __('Inventory number');
        $actions['otherserial']['type']              = 'text';
        $actions['otherserial']['force_actions']     = ['regex_result'];

        return $actions;
    }


    public function getRights($interface = 'central')
    {

        $values = parent::getRights();
        $values[self::PARENT] = ['short' => __('Parent business'),
            'long'  => __('Business rules (entity parent)'),
        ];

        return $values;
    }


    public function executeActions($output, $params, array $input = [])
    {

        if (count($this->actions)) {
            foreach ($this->actions as $action) {
                switch ($action->fields["action_type"]) {
                    case "assign":
                        $output[$action->fields["field"]] = $action->fields["value"];
                        break;

                    case "append":
                        $actions = $this->getAllActions();
                        $value   = $action->fields["value"];
                        if (
                            isset($actions[$action->fields["field"]]["appendtoarray"])
                            && isset($actions[$action->fields["field"]]["appendtoarrayfield"])
                        ) {
                            $value = $actions[$action->fields["field"]]["appendtoarray"];
                            $value[$actions[$action->fields["field"]]["appendtoarrayfield"]]
                            = $action->fields["value"];
                        }
                        $output[$actions[$action->fields["field"]]["appendto"]][] = $value;
                        break;

                    case "regex_result":
                        switch ($action->fields["field"]) {
                            case "_affect_user_by_regex":
                                foreach ($this->regex_results as $regex_result) {
                                    $res = RuleAction::getRegexResultById(
                                        $action->fields["value"],
                                        $regex_result
                                    );
                                    if ($res != null) {
                                        $user = User::getIdByName(addslashes($res));
                                        if ($user) {
                                            $output['users_id'] = $user;
                                        }
                                    }
                                }
                                break;
                            default:
                                $res = "";
                                if (isset($this->regex_results[0])) {
                                    $res .= RuleAction::getRegexResultById(
                                        $action->fields["value"],
                                        $this->regex_results[0]
                                    );
                                } else {
                                    $res .= $action->fields["value"];
                                }
                                $output[$action->fields["field"]] = $res;
                                break;
                        }
                        break;

                    case 'fromuser':
                        if (
                            ($action->fields['field'] == 'locations_id')
                              && isset($input['_locations_id_of_user'])
                        ) {
                            $output['locations_id'] = $input['_locations_id_of_user'];
                        }
                        break;

                    case 'defaultfromuser':
                        if (
                            ($action->fields['field'] == 'groups_id')
                             && isset($input['_default_groups_id_of_user'])
                        ) {
                            $output['groups_id'] = $input['_default_groups_id_of_user'];
                        }
                        break;

                    case 'firstgroupfromuser':
                        if (
                            ($action->fields['field'] == 'groups_id')
                            && isset($input['_groups_id_of_user'])
                        ) {
                            $output['groups_id'] = (int) reset($input['_groups_id_of_user']);
                        }
                        break;

                    default:
                        //plugins actions
                        $executeaction = clone $this;
                        $output = $executeaction->executePluginsActions($action, $output, $params);
                        break;
                }
            }
        }
        return $output;
    }
}
