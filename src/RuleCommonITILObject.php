<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

abstract class RuleCommonITILObject extends Rule
{
    const PARENT  = 1024;


    const ONADD    = 1;
    const ONUPDATE = 2;

    /**
     * Get the ITIL Object itemtype that this rule is for
     * @return string
     */
    public static function getItemtype(): string
    {
        $itemtype = null;
        switch (true) {
            case is_a(static::class, RuleChange::class, true):
                $itemtype = Change::class;
                break;
            case is_a(static::class, RuleTicket::class, true):
                $itemtype = Ticket::class;
                break;
            default:
                $matches = [];
                if (
                    preg_match('/^Rule(.+)$/', static::class, $matches) === 1
                    && is_subclass_of($matches[1], CommonITILObject::class, true)
                ) {
                    $itemtype = $matches[1];
                }
                break;
        }

        if ($itemtype === null) {
            throw new \RuntimeException(sprintf('Unable to compute related itemtype for class "%s".', static::class));
        }

        return $itemtype;
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

        return [
            static::ONADD                       => __('Add'),
            static::ONUPDATE                    => __('Update'),
            static::ONADD | static::ONUPDATE    => sprintf(
                __('%1$s / %2$s'),
                __('Add'),
                __('Update')
            ),
        ];
    }

    public function getTitleAction()
    {
        parent::getTitleAction();
        $showwarning = false;
        if (isset($this->actions)) {
            foreach ($this->actions as $key => $val) {
                if (isset($val->fields['field'])) {
                    if (in_array($val->fields['field'], ['impact', 'urgency'])) {
                        $showwarning = true;
                    }
                }
            }
        }
        if ($showwarning) {
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2'><td>" .
                __('Urgency or impact used in actions, think to add Priority: recompute action if needed.') .
                "</td></tr>\n";
            echo "</table><br>";
        }
    }

    public function addSpecificParamsForPreview($params)
    {
        if (!isset($params["entities_id"])) {
            $params["entities_id"] = $_SESSION["glpiactive_entity"];
        }
        return $params;
    }

    public function showSpecificCriteriasForPreview($fields)
    {
        $entity_as_criteria = false;
        foreach ($this->criterias as $criteria) {
            if ($criteria->fields['criteria'] == 'entities_id') {
                $entity_as_criteria = true;
                break;
            }
        }
        if (!$entity_as_criteria) {
            echo "<input type='hidden' name='entities_id' value='" . $_SESSION["glpiactive_entity"] . "'>";
        }
    }

    public function executeActions($output, $params, array $input = [])
    {
        if (count($this->actions)) {
            foreach ($this->actions as $action) {
                switch ($action->fields["action_type"]) {
                    case "add_validation":
                        if (isset($output['_add_validation']) && !is_array($output['_add_validation'])) {
                            $output['_add_validation'] = [$output['_add_validation']];
                        }
                        switch ($action->fields['field']) {
                            case 'users_id_validate_requester_supervisor':
                                $output['_add_validation'][] = 'requester_supervisor';
                                break;

                            case 'users_id_validate_assign_supervisor':
                                $output['_add_validation'][] = 'assign_supervisor';
                                break;

                            case 'groups_id_validate':
                                $output['_add_validation']['group'][] = $action->fields["value"];
                                break;

                            case 'groups_id_validate_any':
                                $output['_add_validation']['group_any'][] = $action->fields["value"];
                                break;

                            case 'users_id_validate':
                                $output['_add_validation'][] = $action->fields["value"];
                                break;

                            case 'responsible_id_validate':
                                $output['_add_validation'][] = 'requester_responsible';
                                break;

                            case 'validation_percent':
                                $output[$action->fields["field"]] = $action->fields["value"];
                                break;

                            default:
                                $output['_add_validation'][] = $action->fields["value"];
                                break;
                        }
                        break;

                    case "assign":
                        $output[$action->fields["field"]] = $action->fields["value"];

                        // Special case of status
                        if ($action->fields["field"] === 'status') {
                            // Add a flag to remember that status was forced by rule
                            $output['_do_not_compute_status'] = true;
                        }

                        // Special case of users_id_requester
                        if ($action->fields["field"] === '_users_id_requester') {
                            // Add groups of requester
                            if (!isset($output['_groups_id_of_requester'])) {
                                $output['_groups_id_of_requester'] = [];
                            }
                            foreach (Group_User::getUserGroups($action->fields["value"]) as $g) {
                                $output['_groups_id_of_requester'][$g['id']] = $g['id'];
                            }
                        }

                        // Special case for _users_id_requester, _users_id_observer and _users_id_assign
                        if (
                            in_array(
                                $action->fields["field"],
                                ['_users_id_requester', '_users_id_observer', '_users_id_assign']
                            )
                        ) {
                            // must reset alternative_email field to prevent mix of user/email
                            unset($output[$action->fields["field"] . '_notif']);

                            if ($action->fields["value"] === 'requester_manager') {
                                foreach ($input['_users_id_requester'] as $user_id) {
                                    $user = new User();
                                    $user->getFromDB($user_id);
                                    if (!empty($output[$action->fields["field"]]) && !is_array($output[$action->fields["field"]])) {
                                        $output[$action->fields["field"]] = [$output[$action->fields["field"]]];
                                    }
                                    if ($user->fields['users_id_supervisor'] > 0) {
                                        $output[$action->fields["field"]][] = $user->fields['users_id_supervisor'];
                                    }
                                }
                            }
                        }

                        // special case of itil solution template
                        if ($action->fields["field"] == 'solution_template') {
                            $output['_solutiontemplates_id'] = $action->fields["value"];
                        }

                        // special case of appliance
                        if ($action->fields["field"] == "assign_appliance") {
                            if (!array_key_exists("items_id", $output) || $output['items_id'] == '0') {
                                $output["items_id"] = [];
                            }
                            $output["items_id"][Appliance::getType()][] = $action->fields["value"];
                        }

                        // Remove values that may have been added by any "append" rule action on same actor field.
                        // Appended actors are stored on `_additional_*` keys.
                        $actions = $this->getActions();
                        $append_key = $actions[$action->fields["field"]]["appendto"] ?? null;
                        if (
                            $append_key !== null
                            && preg_match('/^_additional_/', $append_key) === 1
                            && array_key_exists($append_key, $output)
                        ) {
                            unset($output[$append_key]);
                        }

                        break;

                    case "append":
                        $actions = $this->getActions();
                        $value   = $action->fields["value"];
                        if (
                            isset($actions[$action->fields["field"]]["appendtoarray"])
                            && isset($actions[$action->fields["field"]]["appendtoarrayfield"])
                        ) {
                            $value = $actions[$action->fields["field"]]["appendtoarray"];
                            $value[$actions[$action->fields["field"]]["appendtoarrayfield"]]
                                = $action->fields["value"];
                        }

                        // special case of appliance
                        if ($action->fields["field"] === "assign_appliance") {
                            if (!array_key_exists("items_id", $output) || $output['items_id'] == '0') {
                                $output["items_id"] = [];
                            }
                            $output["items_id"][Appliance::getType()][] = $value;
                        } else {
                            $output[$actions[$action->fields["field"]]["appendto"]][] = $value;
                        }

                        // Special case of users_id_requester
                        if ($action->fields["field"] === '_users_id_requester') {
                            // Add groups of requester
                            if (!isset($output['_groups_id_of_requester'])) {
                                $output['_groups_id_of_requester'] = [];
                            }
                            foreach (Group_User::getUserGroups($action->fields["value"]) as $g) {
                                $output['_groups_id_of_requester'][$g['id']] = $g['id'];
                            }
                        }

                        if (
                            in_array(
                                $action->fields["field"],
                                ['_users_id_requester', '_users_id_observer', '_users_id_assign']
                            )
                            && $action->fields["value"] === 'requester_manager'
                        ) {
                            foreach ($input['_users_id_requester'] as $user_id) {
                                $user = new User();
                                $user->getFromDB($user_id);
                                if ($user->fields['users_id_supervisor'] > 0) {
                                    $output[$action->fields["field"]][] = $user->fields['users_id_supervisor'];
                                }
                            }
                        }
                        break;

                    case 'defaultfromuser':
                        if (
                            ( $action->fields['field'] == '_groups_id_requester')
                            &&  isset($output['users_default_groups'])
                        ) {
                            $output['_groups_id_requester'] = $output['users_default_groups'];
                        }
                        break;

                    case 'fromitem':
                        if (
                            $action->fields['field'] == '_groups_id_requester'
                            && isset($output['_groups_id_of_item'])
                        ) {
                            $output['_groups_id_requester'] = $output['_groups_id_of_item'];
                        }
                        break;

                    case 'compute':
                        // Value could be not set (from test)
                        $urgency = (isset($output['urgency']) ? $output['urgency'] : 3);
                        $impact  = (isset($output['impact']) ? $output['impact'] : 3);
                        // Apply priority_matrix from config
                        /** @var CommonITILObject $itemtype */
                        $itemtype = static::getItemtype();
                        $output['priority'] = $itemtype::computePriority($urgency, $impact);
                        break;

                    case 'do_not_compute':
                        if (
                            $action->fields['field'] == 'takeintoaccount_delay_stat'
                            && $action->fields['value'] == 1
                        ) {
                            $output['_do_not_compute_takeintoaccount'] = true;
                        }
                        break;

                    case "affectbyip":
                    case "affectbyfqdn":
                    case "affectbymac":
                        if (!isset($output["entities_id"])) {
                            $output["entities_id"] = $params["entities_id"];
                        }
                        if (isset($this->regex_results[0])) {
                            $regexvalue = RuleAction::getRegexResultById(
                                $action->fields["value"],
                                $this->regex_results[0]
                            );
                        } else {
                            $regexvalue = $action->fields["value"];
                        }

                        switch ($action->fields["action_type"]) {
                            case "affectbyip":
                                $result = IPAddress::getUniqueItemByIPAddress(
                                    $regexvalue,
                                    $output["entities_id"]
                                );
                                break;

                            case "affectbyfqdn":
                                $result = FQDNLabel::getUniqueItemByFQDN(
                                    $regexvalue,
                                    $output["entities_id"]
                                );
                                break;

                            case "affectbymac":
                                $result = NetworkPortInstantiation::getUniqueItemByMac(
                                    $regexvalue,
                                    $output["entities_id"]
                                );
                                break;

                            default:
                                $result = [];
                        }
                        if (!empty($result)) {
                            $output["items_id"] = [];
                            $output["items_id"][$result["itemtype"]][] = $result["id"];
                        }
                        break;

                    case 'regex_result':
                        // Get each regex values
                        $regex_values = array_map(
                            fn ($regex_result) => RuleAction::getRegexResultById(
                                $action->fields["value"],
                                $regex_result
                            ),
                            $this->regex_results
                        );

                        // Keep weird legacy default value that will not match anything
                        if (empty($regex_values)) {
                            $regex_values[] = $action->fields["value"];
                        }

                        // Get field
                        $field = $action->fields["field"];

                        // Handle each fields
                        if ($field == "_affect_itilcategory_by_code") {
                            $regex_value = $regex_values[0];

                            if (!is_null($regex_value)) {
                                $target_itilcategory = ITILCategory::getITILCategoryIDByCode($regex_value);
                                if ($target_itilcategory != -1) {
                                    $output["itilcategories_id"] = $target_itilcategory;
                                }
                            }
                        } elseif ($field == "_groups_id_requester") {
                            foreach ($regex_values as $regex_value) {
                                // Search group by name
                                $group = new Group();
                                $result = $group->getFromDBByCrit([
                                    "name" => $regex_value,
                                    "is_requester" => true
                                ]);

                                // Add groups found for each regex
                                if ($result) {
                                    $output['_additional_groups_requesters'][$group->getID()] = $group->getID();
                                }
                            }
                        } elseif ($field == "_groups_id_requester_by_completename") {
                            foreach ($regex_values as $regex_value) {
                                // Search group by name
                                $group = new Group();
                                $result = $group->getFromDBByCrit([
                                    "completename" => $regex_value,
                                    "is_requester" => true
                                ]);

                                // Add groups found for each regex
                                if ($result) {
                                    $output['_additional_groups_requesters'][$group->getID()] = $group->getID();
                                }
                            }
                        } elseif ($field == "_groups_id_assign") {
                            foreach ($regex_values as $regex_value) {
                                // Search group by name
                                $group = new Group();
                                $result = $group->getFromDBByCrit([
                                    "name" => $regex_value,
                                    "is_assign" => true
                                ]);

                                // Add groups found for each regex
                                if ($result) {
                                    $output['_additional_groups_assigns'][$group->getID()] = $group->getID();
                                }
                            }
                        } elseif ($field == "_groups_id_assign_by_completename") {
                            foreach ($regex_values as $regex_value) {
                                // Search group by name
                                $group = new Group();
                                $result = $group->getFromDBByCrit([
                                    "completename" => $regex_value,
                                    "is_assign" => true
                                ]);

                                // Add groups found for each regex
                                if ($result) {
                                    $output['_additional_groups_assigns'][$group->getID()] = $group->getID();
                                }
                            }
                        } elseif ($field == "_groups_id_observer") {
                            foreach ($regex_values as $regex_value) {
                                // Search group by name
                                $group = new Group();
                                $result = $group->getFromDBByCrit([
                                    "name" => $regex_value,
                                    "is_watcher" => true
                                ]);

                                // Add groups found for each regex
                                if ($result) {
                                    $output['_additional_groups_observers'][$group->getID()] = $group->getID();
                                }
                            }
                        } elseif ($field == "_groups_id_observer_by_completename") {
                            foreach ($regex_values as $regex_value) {
                                // Search group by name
                                $group = new Group();
                                $result = $group->getFromDBByCrit([
                                    "completename" => $regex_value,
                                    "is_watcher" => true
                                ]);

                                // Add groups found for each regex
                                if ($result) {
                                    $output['_additional_groups_observers'][$group->getID()] = $group->getID();
                                }
                            }
                        } elseif ($field == "assign_appliance") {
                            $regex_value = $regex_values[0];

                            if (!is_null($regex_value)) {
                                $appliances = new Appliance();
                                $target_appliances = $appliances->find([
                                    "name" => $regex_value,
                                    "is_helpdesk_visible" => true
                                ]);

                                if (
                                    (!array_key_exists("items_id", $output) || $output['items_id'] == '0')
                                    && count($target_appliances) > 0
                                ) {
                                    $output["items_id"] = [];
                                }

                                foreach ($target_appliances as $value) {
                                    $output["items_id"][Appliance::getType()][] = $value['id'];
                                }
                            }
                        } elseif ($field == "itilcategories_id") {
                            foreach ($regex_values as $regex_value) {
                                // Search category by name
                                $category = new ITILCategory();
                                $result = $category->getFromDBByCrit([
                                    "name" => $regex_value,
                                ]);

                                // Stop at the first valid category found
                                if ($result) {
                                    $output['itilcategories_id'] = $category->getID();
                                    break;
                                }
                            }
                        } elseif ($field == "_itilcategories_id_by_completename") {
                            foreach ($regex_values as $regex_value) {
                                // Search category by name
                                $category = new ITILCategory();
                                $result = $category->getFromDBByCrit([
                                    "completename" => $regex_value,
                                ]);

                                // Stop at the first valid category found
                                if ($result) {
                                    $output['itilcategories_id'] = $category->getID();
                                    break;
                                }
                            }
                        }
                        break;
                }
            }
        }
        return $output;
    }

    public function preProcessPreviewResults($output)
    {
        $output = parent::preProcessPreviewResults($output);
        /** @var CommonITILObject $itemtype */
        $itemtype = static::getItemtype();
        return $itemtype::showPreviewAssignAction($output);
    }

    public function getCriterias()
    {

        static $criterias = [];

        if (count($criterias)) {
            return $criterias;
        }

        /** @var CommonDBTM $itemtype */
        $itemtype = static::getItemtype();
        $itil_table = $itemtype::getTable();

        $criterias['name']['table']                           = $itil_table;
        $criterias['name']['field']                           = 'name';
        $criterias['name']['name']                            = __('Title');
        $criterias['name']['linkfield']                       = 'name';

        $criterias['content']['table']                        = $itil_table;
        $criterias['content']['field']                        = 'content';
        $criterias['content']['name']                         = __('Description');
        $criterias['content']['linkfield']                    = 'content';

        $criterias['date_mod']['table']                       = $itil_table;
        $criterias['date_mod']['field']                       = 'date_mod';
        $criterias['date_mod']['name']                        = __('Last update');
        $criterias['date_mod']['linkfield']                   = 'date_mod';

        $criterias['date']['table']                           = $itil_table;
        $criterias['date']['field']                           = 'date';
        $criterias['date']['name']                            = __('Opening date');
        $criterias['date']['linkfield']                       = 'date';

        $criterias['itilcategories_id']['table']              = 'glpi_itilcategories';
        $criterias['itilcategories_id']['field']              = 'completename';
        $criterias['itilcategories_id']['name']               = _n('Category', 'Categories', 1);
        $criterias['itilcategories_id']['linkfield']          = 'itilcategories_id';
        $criterias['itilcategories_id']['type']               = 'dropdown';
        $criterias['itilcategories_id']['linked_criteria']    = 'itilcategories_id_code';

        $criterias['itilcategories_id_code']['table']           = 'glpi_itilcategories';
        $criterias['itilcategories_id_code']['field']           = 'code';
        $criterias['itilcategories_id_code']['name']            = __('Code representing the ITIL category');

        $criterias['_users_id_requester']['table']            = 'glpi_users';
        $criterias['_users_id_requester']['field']            = 'name';
        $criterias['_users_id_requester']['name']             = _n('Requester', 'Requesters', 1);
        $criterias['_users_id_requester']['linkfield']        = '_users_id_requester';
        $criterias['_users_id_requester']['type']             = 'dropdown_users';
        $criterias['_users_id_requester']['linked_criteria']  = '_groups_id_of_requester';

        $criterias['_groups_id_of_requester']['table']        = 'glpi_groups';
        $criterias['_groups_id_of_requester']['field']        = 'completename';
        $criterias['_groups_id_of_requester']['name']         = __('Requester in group');
        $criterias['_groups_id_of_requester']['linkfield']    = '_groups_id_of_requester';
        $criterias['_groups_id_of_requester']['type']         = 'dropdown';

        $criterias['_groups_id_of_item']['table']             = 'glpi_groups';
        $criterias['_groups_id_of_item']['field']             = 'completename';
        $criterias['_groups_id_of_item']['name']              = __('Item group');
        $criterias['_groups_id_of_item']['linkfield']         = '_groups_id_of_item';
        $criterias['_groups_id_of_item']['type']              = 'dropdown';

        $criterias['_states_id_of_item']['table']             = 'glpi_states';
        $criterias['_states_id_of_item']['field']             = 'completename';
        $criterias['_states_id_of_item']['name']              = __('Item state');
        $criterias['_states_id_of_item']['linkfield']         = '_states_id_of_item';
        $criterias['_states_id_of_item']['type']              = 'dropdown';

        $criterias['_groups_id_requester']['table']           = 'glpi_groups';
        $criterias['_groups_id_requester']['field']           = 'completename';
        $criterias['_groups_id_requester']['name']            = _n('Requester group', 'Requester groups', 1);
        $criterias['_groups_id_requester']['linkfield']       = '_groups_id_requester';
        $criterias['_groups_id_requester']['type']            = 'dropdown';

        $criterias['_users_id_assign']['table']               = 'glpi_users';
        $criterias['_users_id_assign']['field']               = 'name';
        $criterias['_users_id_assign']['name']                = __('Technician');
        $criterias['_users_id_assign']['linkfield']           = '_users_id_assign';
        $criterias['_users_id_assign']['type']                = 'dropdown_users';

        $criterias['_groups_id_assign']['table']              = 'glpi_groups';
        $criterias['_groups_id_assign']['field']              = 'completename';
        $criterias['_groups_id_assign']['name']               = __('Technician group');
        $criterias['_groups_id_assign']['linkfield']          = '_groups_id_assign';
        $criterias['_groups_id_assign']['type']               = 'dropdown';
        $criterias['_groups_id_assign']['condition']          = ['is_assign' => 1];

        $criterias['_suppliers_id_assign']['table']           = 'glpi_suppliers';
        $criterias['_suppliers_id_assign']['field']           = 'name';
        $criterias['_suppliers_id_assign']['name']            = __('Assigned to a supplier');
        $criterias['_suppliers_id_assign']['linkfield']       = '_suppliers_id_assign';
        $criterias['_suppliers_id_assign']['type']            = 'dropdown';

        $criterias['_users_id_observer']['table']             = 'glpi_users';
        $criterias['_users_id_observer']['field']             = 'name';
        $criterias['_users_id_observer']['name']              = _n('Observer', 'Observers', 1);
        $criterias['_users_id_observer']['linkfield']         = '_users_id_observer';
        $criterias['_users_id_observer']['type']              = 'dropdown_users';

        $criterias['_groups_id_observer']['table']            = 'glpi_groups';
        $criterias['_groups_id_observer']['field']            = 'completename';
        $criterias['_groups_id_observer']['name']             = _n('Observer group', 'Observer groups', 1);
        $criterias['_groups_id_observer']['linkfield']        = '_groups_id_observer';
        $criterias['_groups_id_observer']['type']             = 'dropdown';

        $criterias['requesttypes_id']['table']                = 'glpi_requesttypes';
        $criterias['requesttypes_id']['field']                = 'name';
        $criterias['requesttypes_id']['name']                 = RequestType::getTypeName(1);
        $criterias['requesttypes_id']['linkfield']            = 'requesttypes_id';
        $criterias['requesttypes_id']['type']                 = 'dropdown';

        $criterias['itemtype']['table']                       = $itil_table;
        $criterias['itemtype']['field']                       = 'itemtype';
        $criterias['itemtype']['name']                        = __('Item type');
        $criterias['itemtype']['linkfield']                   = 'itemtype';
        $criterias['itemtype']['type']                        = 'dropdown_tracking_itemtype';

        $criterias['entities_id']['table']                    = 'glpi_entities';
        $criterias['entities_id']['field']                    = 'name';
        $criterias['entities_id']['name']                     = Entity::getTypeName(1);
        $criterias['entities_id']['linkfield']                = 'entities_id';
        $criterias['entities_id']['type']                     = 'dropdown';

        $criterias['profiles_id']['table']                    = 'glpi_profiles';
        $criterias['profiles_id']['field']                    = 'name';
        $criterias['profiles_id']['name']                     = __('Default profile');
        $criterias['profiles_id']['linkfield']                = 'profiles_id';
        $criterias['profiles_id']['type']                     = 'dropdown';

        $criterias['urgency']['name']                         = __('Urgency');
        $criterias['urgency']['type']                         = 'dropdown_urgency';

        $criterias['impact']['name']                          = __('Impact');
        $criterias['impact']['type']                          = 'dropdown_impact';

        $criterias['priority']['name']                        = __('Priority');
        $criterias['priority']['type']                        = 'dropdown_priority';

        $criterias['status']['name']                          = __('Status');
        $criterias['status']['type']                          = 'dropdown_status';

        $criterias['_contract_types']['table']                = ContractType::getTable();
        $criterias['_contract_types']['field']                = 'name';
        $criterias['_contract_types']['name']                 = ContractType::getTypeName(1);
        $criterias['_contract_types']['type']                 = 'dropdown';

        $validation_class = $itemtype . 'Validation';

        if (class_exists($validation_class)) {
            $criterias['global_validation']['name'] = _n('Validation', 'Validations', 1);
            $criterias['global_validation']['type'] = 'dropdown_validation_status';
        }

        $criterias['_date_creation_calendars_id'] = [
            'name'            => __("Creation date is a working hour in calendar"),
            'table'           => Calendar::getTable(),
            'field'           => 'name',
            'linkfield'       => '_date_creation_calendars_id',
            'type'            => 'dropdown',
        ];

        return $criterias;
    }

    public function getActions()
    {
        $actions                                                = parent::getActions();

        $actions['itilcategories_id']['name']                       = _n('Category', 'Categories', 1);
        $actions['itilcategories_id']['type']                       = 'dropdown';
        $actions['itilcategories_id']['table']                      = 'glpi_itilcategories';
        $actions['itilcategories_id']['force_actions']              = ['assign', 'regex_result'];

        $actions['_itilcategories_id_by_completename']['name']                 = sprintf(__('%1$s (%2$s)'), _n('Category', 'Categories', 1), __('by completename'));
        $actions['_itilcategories_id_by_completename']['type']                 = 'dropdown';
        $actions['_itilcategories_id_by_completename']['table']                = 'glpi_itilcategories';
        $actions['_itilcategories_id_by_completename']['force_actions']        = ['regex_result'];

        $actions['_affect_itilcategory_by_code']['name']            = __('ITIL category from code');
        $actions['_affect_itilcategory_by_code']['type']            = 'text';
        $actions['_affect_itilcategory_by_code']['force_actions']   = ['regex_result'];

        $actions['_users_id_requester']['name']                     = _n('Requester', 'Requesters', 1);
        $actions['_users_id_requester']['type']                     = 'dropdown_users';
        $actions['_users_id_requester']['force_actions']            = ['assign', 'append'];
        $actions['_users_id_requester']['permitseveral']            = ['append'];
        $actions['_users_id_requester']['appendto']                 = '_additional_requesters';
        $actions['_users_id_requester']['appendtoarray']            = ['use_notification' => 1];
        $actions['_users_id_requester']['appendtoarrayfield']       = 'users_id';

        $actions['_groups_id_requester']['name']                    = _n('Requester group', 'Requester groups', 1);
        $actions['_groups_id_requester']['type']                    = 'dropdown';
        $actions['_groups_id_requester']['table']                   = 'glpi_groups';
        $actions['_groups_id_requester']['condition']               = ['is_requester' => 1];
        $actions['_groups_id_requester']['force_actions']           = ['assign', 'append', 'fromitem', 'defaultfromuser','regex_result'];
        $actions['_groups_id_requester']['permitseveral']           = ['append'];
        $actions['_groups_id_requester']['appendto']                = '_additional_groups_requesters';

        $actions['_groups_id_requester_by_completename']['name']              = sprintf(__('%1$s (%2$s)'), _n('Requester group', 'Requester groups', 1), __('by completename'));
        $actions['_groups_id_requester_by_completename']['type']              = 'dropdown';
        $actions['_groups_id_requester_by_completename']['table']             = 'glpi_groups';
        $actions['_groups_id_requester_by_completename']['condition']         = ['is_requester' => 1];
        $actions['_groups_id_requester_by_completename']['force_actions']     = ['regex_result'];
        $actions['_groups_id_requester_by_completename']['permitseveral']     = ['append'];
        $actions['_groups_id_requester_by_completename']['appendto']          = '_additional_groups_requesters';

        $actions['_users_id_assign']['name']                        = __('Technician');
        $actions['_users_id_assign']['type']                        = 'dropdown_assign';
        $actions['_users_id_assign']['force_actions']                = ['assign', 'append'];
        $actions['_users_id_assign']['permitseveral']               = ['append'];
        $actions['_users_id_assign']['appendto']                    = '_additional_assigns';
        $actions['_users_id_assign']['appendtoarray']               = ['use_notification' => 1];
        $actions['_users_id_assign']['appendtoarrayfield']          = 'users_id';

        $actions['_groups_id_assign']['table']                      = 'glpi_groups';
        $actions['_groups_id_assign']['name']                       = __('Technician group');
        $actions['_groups_id_assign']['type']                       = 'dropdown';
        $actions['_groups_id_assign']['condition']                  = ['is_assign' => 1];
        $actions['_groups_id_assign']['force_actions']              = ['assign', 'append', 'regex_result'];
        $actions['_groups_id_assign']['permitseveral']              = ['append'];
        $actions['_groups_id_assign']['appendto']                   = '_additional_groups_assigns';

        $actions['_groups_id_assign_by_completename']['table']                = 'glpi_groups';
        $actions['_groups_id_assign_by_completename']['name']                 = sprintf(__('%1$s (%2$s)'), __('Technician group'), __('by completename'));
        $actions['_groups_id_assign_by_completename']['type']                 = 'dropdown';
        $actions['_groups_id_assign_by_completename']['condition']            = ['is_assign' => 1];
        $actions['_groups_id_assign_by_completename']['force_actions']        = ['regex_result'];
        $actions['_groups_id_assign_by_completename']['permitseveral']        = ['append'];
        $actions['_groups_id_assign_by_completename']['appendto']             = '_additional_groups_assigns';

        $actions['_suppliers_id_assign']['table']                   = 'glpi_suppliers';
        $actions['_suppliers_id_assign']['name']                    = __('Assigned to a supplier');
        $actions['_suppliers_id_assign']['type']                    = 'dropdown';
        $actions['_suppliers_id_assign']['force_actions']           = ['assign', 'append'];
        $actions['_suppliers_id_assign']['permitseveral']           = ['append'];
        $actions['_suppliers_id_assign']['appendto']                = '_additional_suppliers_assigns';
        $actions['_suppliers_id_assign']['appendtoarray']           = ['use_notification' => 1];
        $actions['_suppliers_id_assign']['appendtoarrayfield']      = 'suppliers_id';

        $actions['_users_id_observer']['name']                      = _n('Observer', 'Observers', 1);
        $actions['_users_id_observer']['type']                      = 'dropdown_users';
        $actions['_users_id_observer']['force_actions']             = ['assign', 'append'];
        $actions['_users_id_observer']['permitseveral']             = ['append'];
        $actions['_users_id_observer']['appendto']                  = '_additional_observers';
        $actions['_users_id_observer']['appendtoarray']             = ['use_notification' => 1];
        $actions['_users_id_observer']['appendtoarrayfield']        = 'users_id';

        $actions['_groups_id_observer']['table']                    = 'glpi_groups';
        $actions['_groups_id_observer']['name']                     = _n('Observer group', 'Observer groups', 1);
        $actions['_groups_id_observer']['type']                     = 'dropdown';
        $actions['_groups_id_observer']['condition']                = ['is_watcher' => 1];
        $actions['_groups_id_observer']['force_actions']            = ['assign', 'append', 'regex_result'];
        $actions['_groups_id_observer']['permitseveral']            = ['append'];
        $actions['_groups_id_observer']['appendto']                 = '_additional_groups_observers';

        $actions['_groups_id_observer_by_completename']['table']              = 'glpi_groups';
        $actions['_groups_id_observer_by_completename']['name']               = sprintf(__('%1$s (%2$s)'), _n('Watcher group', 'Watcher groups', 1), __('by completename'));
        $actions['_groups_id_observer_by_completename']['type']               = 'dropdown';
        $actions['_groups_id_observer_by_completename']['condition']          = ['is_watcher' => 1];
        $actions['_groups_id_observer_by_completename']['force_actions']      = ['regex_result'];
        $actions['_groups_id_observer_by_completename']['permitseveral']      = ['append'];
        $actions['_groups_id_observer_by_completename']['appendto']           = '_additional_groups_observers';

        $actions['urgency']['name']                                 = __('Urgency');
        $actions['urgency']['type']                                 = 'dropdown_urgency';

        $actions['impact']['name']                                  = __('Impact');
        $actions['impact']['type']                                  = 'dropdown_impact';

        $actions['priority']['name']                                = __('Priority');
        $actions['priority']['type']                                = 'dropdown_priority';
        $actions['priority']['force_actions']                       = ['assign', 'compute'];

        $actions['status']['name']                                  = __('Status');
        $actions['status']['type']                                  = 'dropdown_status';

        $actions['affectobject']['name']                            = _n('Associated element', 'Associated elements', Session::getPluralNumber());
        $actions['affectobject']['type']                            = 'text';
        $actions['affectobject']['force_actions']                   = ['affectbyip', 'affectbyfqdn',
            'affectbymac'
        ];

        $actions['assign_appliance']['name']                        = _n('Associated element', 'Associated elements', Session::getPluralNumber()) . " : " . Appliance::getTypeName(1);
        $actions['assign_appliance']['type']                        = 'dropdown';
        $actions['assign_appliance']['table']                       = 'glpi_appliances';
        $actions['assign_appliance']['condition']                   = ['is_helpdesk_visible' => 1];
        $actions['assign_appliance']['permitseveral']               = ['append'];
        $actions['assign_appliance']['force_actions']               = ['assign','regex_result', 'append'];
        $actions['assign_appliance']['appendto']                    = 'items_id';

        $itemtype = static::getItemtype();
        $validation_class = $itemtype . 'Validation';

        if (class_exists($validation_class)) {
            $actions['users_id_validate']['name'] = sprintf(
                __('%1$s - %2$s'),
                __('Send an approval request'),
                User::getTypeName(1)
            );
            $actions['users_id_validate']['type'] = 'dropdown_users_validate';
            $actions['users_id_validate']['force_actions'] = ['add_validation'];

            $actions['responsible_id_validate']['name'] = sprintf(
                __('%1$s - %2$s'),
                __('Send an approval request'),
                __('Supervisor of the requester')
            );
            $actions['responsible_id_validate']['type'] = 'yesno';
            $actions['responsible_id_validate']['force_actions'] = ['add_validation'];

            // Send approval to all valid users in a group where each user has their own approval
            $actions['groups_id_validate']['name']                      = sprintf(
                __('%1$s - %2$s'),
                __('Send an approval request'),
                __('Group users')
            );
            $actions['groups_id_validate']['type']                      = 'dropdown_groups_validate';
            $actions['groups_id_validate']['force_actions']             = ['add_validation'];

            // Send approval to a group itself where any member can answer
            $actions['groups_id_validate_any']['name']                      = sprintf(
                __('%1$s - %2$s'),
                __('Send an approval request'),
                Group::getTypeName(1)
            );
            $actions['groups_id_validate_any']['type']                      = 'dropdown_groups_validate';
            $actions['groups_id_validate_any']['force_actions']             = ['add_validation'];
            $actions['groups_id_validate_any']['permitseveral']             = ['add_validation'];

            $actions['validation_percent']['name']                      = sprintf(
                __('%1$s - %2$s'),
                __('Send an approval request'),
                __('Minimum validation required')
            );
            $actions['validation_percent']['type']                      = 'dropdown_validation_percent';

            $actions['users_id_validate_requester_supervisor']['name']  = __('Approval request to requester group manager');
            $actions['users_id_validate_requester_supervisor']['type']  = 'yesno';
            $actions['users_id_validate_requester_supervisor']['force_actions'] = ['add_validation'];

            $actions['users_id_validate_assign_supervisor']['name']     = __('Approval request to technician group manager');
            $actions['users_id_validate_assign_supervisor']['type']     = 'yesno';
            $actions['users_id_validate_assign_supervisor']['force_actions'] = ['add_validation'];
        }

        $actions['requesttypes_id']['name']                         = RequestType::getTypeName(1);
        $actions['requesttypes_id']['type']                         = 'dropdown';
        $actions['requesttypes_id']['table']                        = 'glpi_requesttypes';

        $actions['takeintoaccount_delay_stat']['name']              = __('Take into account delay');
        $actions['takeintoaccount_delay_stat']['type']              = 'yesno';
        $actions['takeintoaccount_delay_stat']['force_actions']     = ['do_not_compute'];

        $actions['solution_template']['name']                       = _n('Solution template', 'Solution templates', 1);
        $actions['solution_template']['type']                       = 'dropdown';
        $actions['solution_template']['table']                      = 'glpi_solutiontemplates';
        $actions['solution_template']['force_actions']              = ['assign'];

        $actions['task_template']['name']                           = _n('Task template', 'Task templates', 1);
        $actions['task_template']['type']                           = 'dropdown';
        $actions['task_template']['table']                          = TaskTemplate::getTable();
        $actions['task_template']['force_actions']                  = ['append'];
        $actions['task_template']['permitseveral']                  = ['append'];
        $actions['task_template']['appendto']                       = '_tasktemplates_id';

        $actions['itilfollowup_template']['name']                   = ITILFollowupTemplate::getTypeName(1);
        $actions['itilfollowup_template']['type']                   = 'dropdown';
        $actions['itilfollowup_template']['table']                  = ITILFollowupTemplate::getTable();
        $actions['itilfollowup_template']['force_actions']          = ['append'];
        $actions['itilfollowup_template']['permitseveral']          = ['append'];
        $actions['itilfollowup_template']['appendto']               = '_itilfollowuptemplates_id';

        $actions['global_validation']['name']                       = _n('Validation', 'Validations', 1);
        $actions['global_validation']['type']                       = 'dropdown_validation_status';

        return $actions;
    }

    public function getRights($interface = 'central')
    {

        $values = parent::getRights();
        $values[self::PARENT] = ['short' => __('Parent business'),
            'long'  => __('Business rules (entity parent)')
        ];

        return $values;
    }

    public static function getIcon()
    {
        /** @var CommonITILObject $itemtype */
        $itemtype = static::getItemtype();
        return $itemtype::getIcon();
    }
}
