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

use Glpi\Toolbox\Sanitizer;

class RuleTicket extends Rule
{
   // From Rule
    public static $rightname = 'rule_ticket';
    public $can_sort  = true;

    const PARENT  = 1024;


    const ONADD    = 1;
    const ONUPDATE = 2;

    public function getTitle()
    {
        return __('Business rules for tickets');
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


    /**
     * @since 0.85
     **/
    public static function getConditionsArray()
    {

        return [static::ONADD                   => __('Add'),
            static::ONUPDATE                => __('Update'),
            static::ONADD | static::ONUPDATE  => sprintf(
                __('%1$s / %2$s'),
                __('Add'),
                __('Update')
            )
        ];
    }


    /**
     * display title for action form
     *
     * @since 0.84.3
     **/
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


    /**
     * @param $params
     **/
    public function addSpecificParamsForPreview($params)
    {

        if (!isset($params["entities_id"])) {
            $params["entities_id"] = $_SESSION["glpiactive_entity"];
        }
        return $params;
    }


    /**
     * Function used to display type specific criterias during rule's preview
     *
     * @param $fields fields values
     **/
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
                    case "send":
                        //recall & recall_ola
                        $ticket = new Ticket();
                        if ($ticket->getFromDB($output['id'])) {
                            NotificationEvent::raiseEvent($action->fields['field'], $ticket);
                        }
                        break;

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

                    case "delete":
                        if ($action->fields["field"]) {
                            $output[$action->fields["field"]] = null;
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
                        }

                     // Special case of slas_id_ttr & slas_id_tto & olas_id_ttr & olas_id_tto
                        if (
                            $action->fields["field"] === 'slas_id_ttr'
                            || $action->fields["field"] === 'slas_id_tto'
                            || $action->fields["field"] === 'olas_id_ttr'
                            || $action->fields["field"] === 'olas_id_tto'
                        ) {
                            $output['_' . $action->fields["field"]] = $action->fields["value"];
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

                        // special case of project
                        if ($action->fields["field"] == "assign_project") {
                            if (!array_key_exists("_projects_id", $output)) {
                                $output["_projects_id"] = [];
                            }
                            $output["_projects_id"][] = $action->fields["value"];
                        }

                        // special case of contract
                        if ($action->fields["field"] == "assign_contract") {
                            if (!array_key_exists("_contracts_id", $output) || $output['_contracts_id'] == '0') {
                                $output["_contracts_id"] = [];
                            }
                            $output["_contracts_id"] = $action->fields["value"];
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

                        // special case of appliance / project
                        if ($action->fields["field"] === "assign_appliance") {
                            if (!array_key_exists("items_id", $output) || $output['items_id'] == '0') {
                                $output["items_id"] = [];
                            }
                            $output["items_id"][Appliance::getType()][] = $value;
                        } else if ($action->fields["field"] === "assign_project") {
                            if (!array_key_exists("_projects_id", $output)) {
                                $output["_projects_id"] = [];
                            }
                            $output["_projects_id"][] = $value;
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
                        break;

                    case 'fromuser':
                        if (
                            ($action->fields['field'] == 'locations_id')
                              &&  isset($output['_locations_id_of_requester'])
                        ) {
                            $output['locations_id'] = $output['_locations_id_of_requester'];
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
                        if ($action->fields['field'] == 'locations_id' && isset($output['_locations_id_of_item'])) {
                            $output['locations_id'] = $output['_locations_id_of_item'];
                        }
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
                        $output['priority'] = Ticket::computePriority($urgency, $impact);
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
                        if ($action->fields["field"] == "_affect_itilcategory_by_code") {
                            if (isset($this->regex_results[0])) {
                                $regexvalue = RuleAction::getRegexResultById(
                                    $action->fields["value"],
                                    $this->regex_results[0]
                                );
                            } else {
                                $regexvalue = $action->fields["value"];
                            }

                            if (!is_null($regexvalue)) {
                                $target_itilcategory = ITILCategory::getITILCategoryIDByCode($regexvalue);
                                if ($target_itilcategory != -1) {
                                    $output["itilcategories_id"] = $target_itilcategory;
                                }
                            }
                        } else if ($action->fields["field"] == "_groups_id_requester") {
                            foreach ($this->regex_results as $regex_result) {
                                $regexvalue          = RuleAction::getRegexResultById(
                                    $action->fields["value"],
                                    $regex_result
                                );
                                $group = new Group();
                                if (
                                    $group->getFromDBByCrit(["name" => $regexvalue,
                                        "is_requester" => true
                                    ])
                                ) {
                                     $output['_additional_groups_requesters'][$group->getID()] = $group->getID();
                                }
                            }
                        }

                        if ($action->fields["field"] == "assign_appliance") {
                            if (isset($this->regex_results[0])) {
                                 $regexvalue = RuleAction::getRegexResultById(
                                     $action->fields["value"],
                                     $this->regex_results[0]
                                 );
                            } else {
                                  $regexvalue = $action->fields["value"];
                            }

                            if (!is_null($regexvalue)) {
                                $appliances = new Appliance();
                                $target_appliances = $appliances->find(["name" => $regexvalue, "is_helpdesk_visible" => true]);

                                if ((!array_key_exists("items_id", $output) || $output['items_id'] == '0') && count($target_appliances) > 0) {
                                    $output["items_id"] = [];
                                }

                                foreach ($target_appliances as $value) {
                                    $output["items_id"][Appliance::getType()][] = $value['id'];
                                }
                            }
                        }

                        if ($action->fields["field"] == "assign_project") {
                            if (isset($this->regex_results[0])) {
                                 $regexvalue = RuleAction::getRegexResultById(
                                     $action->fields["value"],
                                     $this->regex_results[0]
                                 );
                            } else {
                                  $regexvalue = $action->fields["value"];
                            }

                            if (!is_null($regexvalue)) {
                                $projects = new Project();
                                $target_projects = $projects->find(["name" => $regexvalue]);

                                if (!array_key_exists("_projects_id", $output) && count($target_projects) > 0) {
                                    $output["_projects_id"] = [];
                                }

                                foreach ($target_projects as $value) {
                                    $output["_projects_id"][] = $value['id'];
                                }
                            }
                        }

                        if ($action->fields["field"] == "assign_contract") {
                            if (isset($this->regex_results[0])) {
                                $regexvalue = RuleAction::getRegexResultById(
                                    $action->fields["value"],
                                    $this->regex_results[0]
                                );
                            } else {
                                $regexvalue = $action->fields["value"];
                            }

                            if (!is_null($regexvalue)) {
                                $contracts = new Contract();
                                $target_contract = $contracts->find(["name" => $regexvalue, "entities_id" => $output['entities_id']]);

                                if ((!array_key_exists("_contracts_id", $output) || $output['_contracts_id'] == '0') && count($target_contract) > 0) {
                                    $output["_contracts_id"] = array_values($target_contract)[0]['id'];
                                } else {
                                    $output["_contracts_id"] = [];
                                }
                            }
                        }
                        break;
                }
            }
        }
        return $output;
    }


    /**
     * @param $output
     **/
    public function preProcessPreviewResults($output)
    {

        $output = parent::preProcessPreviewResults($output);
        return Ticket::showPreviewAssignAction($output);
    }


    public function getCriterias()
    {

        static $criterias = [];

        if (count($criterias)) {
            return $criterias;
        }

        $criterias['name']['table']                           = 'glpi_tickets';
        $criterias['name']['field']                           = 'name';
        $criterias['name']['name']                            = __('Title');
        $criterias['name']['linkfield']                       = 'name';

        $criterias['content']['table']                        = 'glpi_tickets';
        $criterias['content']['field']                        = 'content';
        $criterias['content']['name']                         = __('Description');
        $criterias['content']['linkfield']                    = 'content';

        $criterias['date_mod']['table']                       = 'glpi_tickets';
        $criterias['date_mod']['field']                       = 'date_mod';
        $criterias['date_mod']['name']                        = __('Last update');
        $criterias['date_mod']['linkfield']                   = 'date_mod';

        $criterias['itilcategories_id']['table']              = 'glpi_itilcategories';
        $criterias['itilcategories_id']['field']              = 'completename';
        $criterias['itilcategories_id']['name']               = _n('Category', 'Categories', 1);
        $criterias['itilcategories_id']['linkfield']          = 'itilcategories_id';
        $criterias['itilcategories_id']['type']               = 'dropdown';
        $criterias['itilcategories_id']['linked_criteria']    = 'itilcategories_id_code';

        $criterias['itilcategories_id_code']['table']              = 'glpi_itilcategories';
        $criterias['itilcategories_id_code']['field']              = 'code';
        $criterias['itilcategories_id_code']['name']               = __('Code representing the ticket category');

        $criterias['type']['table']                           = 'glpi_tickets';
        $criterias['type']['field']                           = 'type';
        $criterias['type']['name']                            = _n('Type', 'Types', 1);
        $criterias['type']['linkfield']                       = 'type';
        $criterias['type']['type']                            = 'dropdown_tickettype';

        $criterias['users_id_recipient']['table']             = 'glpi_users';
        $criterias['users_id_recipient']['field']             = 'name';
        $criterias['users_id_recipient']['name']              = __('Writer');
        $criterias['users_id_recipient']['linkfield']         = 'users_id_recipient';
        $criterias['users_id_recipient']['type']              = 'dropdown_users';

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

        $criterias['_locations_id_of_requester']['table']     = 'glpi_locations';
        $criterias['_locations_id_of_requester']['field']     = 'completename';
        $criterias['_locations_id_of_requester']['name']      = __('Requester location');
        $criterias['_locations_id_of_requester']['linkfield'] = '_locations_id_of_requester';
        $criterias['_locations_id_of_requester']['type']      = 'dropdown';

        $criterias['_locations_id_of_item']['table']          = 'glpi_locations';
        $criterias['_locations_id_of_item']['field']          = 'completename';
        $criterias['_locations_id_of_item']['name']           = __('Item location');
        $criterias['_locations_id_of_item']['linkfield']      = '_locations_id_of_item';
        $criterias['_locations_id_of_item']['type']           = 'dropdown';

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

        $criterias['locations_id']['table']                   = 'glpi_locations';
        $criterias['locations_id']['field']                   = 'completename';
        $criterias['locations_id']['name']                    = __('Ticket location');
        $criterias['locations_id']['linkfield']               = 'locations_id';
        $criterias['locations_id']['type']                    = 'dropdown';

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
        $criterias['_users_id_observer']['name']              = _n('Watcher', 'Watchers', 1);
        $criterias['_users_id_observer']['linkfield']         = '_users_id_observer';
        $criterias['_users_id_observer']['type']              = 'dropdown_users';

        $criterias['_groups_id_observer']['table']            = 'glpi_groups';
        $criterias['_groups_id_observer']['field']            = 'completename';
        $criterias['_groups_id_observer']['name']             = _n('Watcher group', 'Watcher groups', 1);
        $criterias['_groups_id_observer']['linkfield']        = '_groups_id_observer';
        $criterias['_groups_id_observer']['type']             = 'dropdown';

        $criterias['requesttypes_id']['table']                = 'glpi_requesttypes';
        $criterias['requesttypes_id']['field']                = 'name';
        $criterias['requesttypes_id']['name']                 = RequestType::getTypeName(1);
        $criterias['requesttypes_id']['linkfield']            = 'requesttypes_id';
        $criterias['requesttypes_id']['type']                 = 'dropdown';

        $criterias['itemtype']['table']                       = 'glpi_tickets';
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

        $criterias['_mailgate']['table']                      = 'glpi_mailcollectors';
        $criterias['_mailgate']['field']                      = 'name';
        $criterias['_mailgate']['name']                       = __('Mails receiver');
        $criterias['_mailgate']['linkfield']                  = '_mailgate';
        $criterias['_mailgate']['type']                       = 'dropdown';

        $criterias['_x-priority']['name']                     = __('X-Priority email header');
        $criterias['_x-priority']['table']                    = '';
        $criterias['_x-priority']['type']                     = 'text';

        $criterias['_from']['name']                           = __('From email header');
        $criterias['_from']['table']                          = '';
        $criterias['_from']['type']                           = 'text';

        $criterias['_subject']['name']                        = __('Subject email header');
        $criterias['_subject']['table']                       = '';
        $criterias['_subject']['type']                        = 'text';

        $criterias['_reply-to']['name']                       = __('Reply-To email header');
        $criterias['_reply-to']['table']                      = '';
        $criterias['_reply-to']['type']                       = 'text';

        $criterias['_in-reply-to']['name']                    = __('In-Reply-To email header');
        $criterias['_in-reply-to']['table']                   = '';
        $criterias['_in-reply-to']['type']                    = 'text';

        $criterias['_to']['name']                             = __('To email header');
        $criterias['_to']['table']                            = '';
        $criterias['_to']['type']                             = 'text';

        $criterias['slas_id_ttr']['table']                    = 'glpi_slas';
        $criterias['slas_id_ttr']['field']                    = 'name';
        $criterias['slas_id_ttr']['name']                     = sprintf(
            __('%1$s %2$s'),
            __('SLA'),
            __('Time to resolve')
        );
        $criterias['slas_id_ttr']['linkfield']                = 'slas_id_ttr';
        $criterias['slas_id_ttr']['type']                     = 'dropdown';
        $criterias['slas_id_ttr']['condition']                = ['glpi_slas.type' => SLM::TTR];

        $criterias['slas_id_tto']['table']                    = 'glpi_slas';
        $criterias['slas_id_tto']['field']                    = 'name';
        $criterias['slas_id_tto']['name']                     = sprintf(
            __('%1$s %2$s'),
            __('SLA'),
            __('Time to own')
        );
        $criterias['slas_id_tto']['linkfield']                = 'slas_id_tto';
        $criterias['slas_id_tto']['type']                     = 'dropdown';
        $criterias['slas_id_tto']['condition']                = ['glpi_slas.type' => SLM::TTO];

        $criterias['olas_id_ttr']['table']                    = 'glpi_olas';
        $criterias['olas_id_ttr']['field']                    = 'name';
        $criterias['olas_id_ttr']['name']                     = sprintf(
            __('%1$s %2$s'),
            __('OLA'),
            __('Time to resolve')
        );
        $criterias['olas_id_ttr']['linkfield']                = 'olas_id_ttr';
        $criterias['olas_id_ttr']['type']                     = 'dropdown';
        $criterias['olas_id_ttr']['condition']                = ['glpi_olas.type' => SLM::TTR];

        $criterias['olas_id_tto']['table']                    = 'glpi_olas';
        $criterias['olas_id_tto']['field']                    = 'name';
        $criterias['olas_id_tto']['name']                     = sprintf(
            __('%1$s %2$s'),
            __('OLA'),
            __('Time to own')
        );
        $criterias['olas_id_tto']['linkfield']                = 'olas_id_tto';
        $criterias['olas_id_tto']['type']                     = 'dropdown';
        $criterias['olas_id_tto']['condition']                = ['glpi_olas.type' => SLM::TTO];

        $criterias['_contract_types']['table']                = ContractType::getTable();
        $criterias['_contract_types']['field']                = 'name';
        $criterias['_contract_types']['name']                 = ContractType::getTypeName(1);
        $criterias['_contract_types']['type']                 = 'dropdown';

        $criterias['global_validation']['name']               = _n('Validation', 'Validations', 1);
        $criterias['global_validation']['type']               = 'dropdown_validation_status';

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

        $actions                                              = parent::getActions();

        $actions['itilcategories_id']['name']                 = _n('Category', 'Categories', 1);
        $actions['itilcategories_id']['type']                 = 'dropdown';
        $actions['itilcategories_id']['table']                = 'glpi_itilcategories';

        $actions['_affect_itilcategory_by_code']['name']           = __('Ticket category from code');
        $actions['_affect_itilcategory_by_code']['type']           = 'text';
        $actions['_affect_itilcategory_by_code']['force_actions']  = ['regex_result'];

        $actions['type']['name']                              = _n('Type', 'Types', 1);
        $actions['type']['table']                             = 'glpi_tickets';
        $actions['type']['type']                              = 'dropdown_tickettype';

        $actions['_users_id_requester']['name']               = _n('Requester', 'Requesters', 1);
        $actions['_users_id_requester']['type']               = 'dropdown_users';
        $actions['_users_id_requester']['force_actions']      = ['assign', 'append'];
        $actions['_users_id_requester']['permitseveral']      = ['append'];
        $actions['_users_id_requester']['appendto']           = '_additional_requesters';
        $actions['_users_id_requester']['appendtoarray']      = ['use_notification' => 1];
        $actions['_users_id_requester']['appendtoarrayfield'] = 'users_id';

        $actions['_groups_id_requester']['name']              = _n('Requester group', 'Requester groups', 1);
        $actions['_groups_id_requester']['type']              = 'dropdown';
        $actions['_groups_id_requester']['table']             = 'glpi_groups';
        $actions['_groups_id_requester']['condition']         = ['is_requester' => 1];
        $actions['_groups_id_requester']['force_actions']     = ['assign', 'append', 'fromitem', 'defaultfromuser','regex_result'];
        $actions['_groups_id_requester']['permitseveral']     = ['append'];
        $actions['_groups_id_requester']['appendto']          = '_additional_groups_requesters';

        $actions['_users_id_assign']['name']                  = __('Technician');
        $actions['_users_id_assign']['type']                  = 'dropdown_assign';
        $actions['_users_id_assign']['force_actions']         = ['assign', 'append'];
        $actions['_users_id_assign']['permitseveral']         = ['append'];
        $actions['_users_id_assign']['appendto']              = '_additional_assigns';
        $actions['_users_id_assign']['appendtoarray']         = ['use_notification' => 1];
        $actions['_users_id_assign']['appendtoarrayfield']    = 'users_id';

        $actions['_groups_id_assign']['table']                = 'glpi_groups';
        $actions['_groups_id_assign']['name']                 = __('Technician group');
        $actions['_groups_id_assign']['type']                 = 'dropdown';
        $actions['_groups_id_assign']['condition']            = ['is_assign' => 1];
        $actions['_groups_id_assign']['force_actions']        = ['assign', 'append'];
        $actions['_groups_id_assign']['permitseveral']        = ['append'];
        $actions['_groups_id_assign']['appendto']             = '_additional_groups_assigns';

        $actions['_suppliers_id_assign']['table']             = 'glpi_suppliers';
        $actions['_suppliers_id_assign']['name']              = __('Assigned to a supplier');
        $actions['_suppliers_id_assign']['type']              = 'dropdown';
        $actions['_suppliers_id_assign']['force_actions']     = ['assign', 'append'];
        $actions['_suppliers_id_assign']['permitseveral']     = ['append'];
        $actions['_suppliers_id_assign']['appendto']          = '_additional_suppliers_assigns';
        $actions['_suppliers_id_assign']['appendtoarray']     = ['use_notification' => 1];
        $actions['_suppliers_id_assign']['appendtoarrayfield']  = 'suppliers_id';

        $actions['_users_id_observer']['name']                = _n('Watcher', 'Watchers', 1);
        $actions['_users_id_observer']['type']                = 'dropdown_users';
        $actions['_users_id_observer']['force_actions']       = ['assign', 'append'];
        $actions['_users_id_observer']['permitseveral']       = ['append'];
        $actions['_users_id_observer']['appendto']            = '_additional_observers';
        $actions['_users_id_observer']['appendtoarray']       = ['use_notification' => 1];
        $actions['_users_id_observer']['appendtoarrayfield']  = 'users_id';

        $actions['_groups_id_observer']['table']              = 'glpi_groups';
        $actions['_groups_id_observer']['name']               = _n('Watcher group', 'Watcher groups', 1);
        $actions['_groups_id_observer']['type']               = 'dropdown';
        $actions['_groups_id_observer']['condition']          = ['is_watcher' => 1];
        $actions['_groups_id_observer']['force_actions']      = ['assign', 'append'];
        $actions['_groups_id_observer']['permitseveral']      = ['append'];
        $actions['_groups_id_observer']['appendto']           = '_additional_groups_observers';

        $actions['urgency']['name']                           = __('Urgency');
        $actions['urgency']['type']                           = 'dropdown_urgency';

        $actions['impact']['name']                            = __('Impact');
        $actions['impact']['type']                            = 'dropdown_impact';

        $actions['priority']['name']                          = __('Priority');
        $actions['priority']['type']                          = 'dropdown_priority';
        $actions['priority']['force_actions']                 = ['assign', 'compute'];

        $actions['status']['name']                            = __('Status');
        $actions['status']['type']                            = 'dropdown_status';

        $actions['affectobject']['name']                      = _n('Associated element', 'Associated elements', Session::getPluralNumber());
        $actions['affectobject']['type']                      = 'text';
        $actions['affectobject']['force_actions']             = ['affectbyip', 'affectbyfqdn',
            'affectbymac'
        ];

        $actions['assign_appliance']['name']                  = _n('Associated element', 'Associated elements', Session::getPluralNumber()) . " : " . Appliance::getTypeName(1);
        $actions['assign_appliance']['type']                  = 'dropdown';
        $actions['assign_appliance']['table']                 = 'glpi_appliances';
        $actions['assign_appliance']['condition']             = ['is_helpdesk_visible' => 1];
        $actions['assign_appliance']['permitseveral']         = ['append'];
        $actions['assign_appliance']['force_actions']         = ['assign','regex_result', 'append'];
        $actions['assign_appliance']['appendto']              = 'items_id';

        $actions['assign_project']['name']                  = Project::getTypeName(1);
        $actions['assign_project']['type']                  = 'dropdown';
        $actions['assign_project']['table']                 = 'glpi_projects';
        $actions['assign_project']['permitseveral']         = ['append'];
        $actions['assign_project']['force_actions']         = ['assign','regex_result', 'append'];
        $actions['assign_project']['appendto']              = '_projects_id';

        $actions['slas_id_ttr']['table']                      = 'glpi_slas';
        $actions['slas_id_ttr']['field']                      = 'name';
        $actions['slas_id_ttr']['name']                       = sprintf(
            __('%1$s %2$s'),
            __('SLA'),
            __('Time to resolve')
        );
        $actions['slas_id_ttr']['linkfield']                  = 'slas_id_ttr';
        $actions['slas_id_ttr']['type']                       = 'dropdown';
        $actions['slas_id_ttr']['condition']                  = ['glpi_slas.type' => SLM::TTR];

        $actions['time_to_resolve']['name']                   = __('Time to resolve');
        $actions['time_to_resolve']['type']                   = 'yesno';
        $actions['time_to_resolve']['force_actions']          = ['delete'];

        $actions['slas_id_tto']['table']                      = 'glpi_slas';
        $actions['slas_id_tto']['field']                      = 'name';
        $actions['slas_id_tto']['name']                       = sprintf(
            __('%1$s %2$s'),
            __('SLA'),
            __('Time to own')
        );
        $actions['slas_id_tto']['linkfield']                  = 'slas_id_tto';
        $actions['slas_id_tto']['type']                       = 'dropdown';
        $actions['slas_id_tto']['condition']                  = ['glpi_slas.type' => SLM::TTO];

        $actions['time_to_own']['name']                       = __('Time to own');
        $actions['time_to_own']['type']                       = 'yesno';
        $actions['time_to_own']['force_actions']              = ['delete'];

        $actions['olas_id_ttr']['table']                      = 'glpi_olas';
        $actions['olas_id_ttr']['field']                      = 'name';
        $actions['olas_id_ttr']['name']                       = sprintf(
            __('%1$s %2$s'),
            __('OLA'),
            __('Time to resolve')
        );
        $actions['olas_id_ttr']['linkfield']                  = 'olas_id_ttr';
        $actions['olas_id_ttr']['type']                       = 'dropdown';
        $actions['olas_id_ttr']['condition']                  = ['glpi_olas.type' => SLM::TTR];

        $actions['internal_time_to_resolve']['name']          = __('Internal time to resolve');
        $actions['internal_time_to_resolve']['type']          = 'yesno';
        $actions['internal_time_to_resolve']['force_actions'] = ['delete'];

        $actions['olas_id_tto']['table']                      = 'glpi_olas';
        $actions['olas_id_tto']['field']                      = 'name';
        $actions['olas_id_tto']['name']                       = sprintf(
            __('%1$s %2$s'),
            __('OLA'),
            __('Time to own')
        );
        $actions['olas_id_tto']['linkfield']                  = 'olas_id_tto';
        $actions['olas_id_tto']['type']                       = 'dropdown';
        $actions['olas_id_tto']['condition']                  = ['glpi_olas.type' => SLM::TTO];

        $actions['internal_time_to_own']['name']              = __('Internal Time to own');
        $actions['internal_time_to_own']['type']              = 'yesno';
        $actions['internal_time_to_own']['force_actions']     = ['delete'];

        $actions['users_id_validate']['name']                 = sprintf(
            __('%1$s - %2$s'),
            __('Send an approval request'),
            User::getTypeName(1)
        );
        $actions['users_id_validate']['type']                 = 'dropdown_users_validate';
        $actions['users_id_validate']['force_actions']        = ['add_validation'];

        $actions['responsible_id_validate']['name']                 = sprintf(
            __('%1$s - %2$s'),
            __('Send an approval request'),
            __('Responsible of the requester')
        );
        $actions['responsible_id_validate']['type']                 = 'yesno';
        $actions['responsible_id_validate']['force_actions']        = ['add_validation'];

        $actions['groups_id_validate']['name']                = sprintf(
            __('%1$s - %2$s'),
            __('Send an approval request'),
            Group::getTypeName(1)
        );
        $actions['groups_id_validate']['type']                = 'dropdown_groups_validate';
        $actions['groups_id_validate']['force_actions']       = ['add_validation'];

        $actions['validation_percent']['name']                = sprintf(
            __('%1$s - %2$s'),
            __('Send an approval request'),
            __('Minimum validation required')
        );
        $actions['validation_percent']['type']                = 'dropdown_validation_percent';

        $actions['users_id_validate_requester_supervisor']['name']
                                             = __('Approval request to requester group manager');
        $actions['users_id_validate_requester_supervisor']['type']
                                             = 'yesno';
        $actions['users_id_validate_requester_supervisor']['force_actions']
                                             = ['add_validation'];

        $actions['users_id_validate_assign_supervisor']['name']
                                             = __('Approval request to technician group manager');
        $actions['users_id_validate_assign_supervisor']['type']
                                             = 'yesno';
        $actions['users_id_validate_assign_supervisor']['force_actions']
                                             = ['add_validation'];

        $actions['locations_id']['name']                      = Location::getTypeName(1);
        $actions['locations_id']['type']                      = 'dropdown';
        $actions['locations_id']['table']                     = 'glpi_locations';
        $actions['locations_id']['force_actions']             = ['assign', 'fromuser', 'fromitem'];

        $actions['requesttypes_id']['name']                   = RequestType::getTypeName(1);
        $actions['requesttypes_id']['type']                   = 'dropdown';
        $actions['requesttypes_id']['table']                  = 'glpi_requesttypes';

        $actions['takeintoaccount_delay_stat']['name']          = __('Take into account delay');
        $actions['takeintoaccount_delay_stat']['type']          = 'yesno';
        $actions['takeintoaccount_delay_stat']['force_actions'] = ['do_not_compute'];

        $actions['solution_template']['name']                  = _n('Solution template', 'Solution templates', 1);
        $actions['solution_template']['type']                  = 'dropdown';
        $actions['solution_template']['table']                 = 'glpi_solutiontemplates';
        $actions['solution_template']['force_actions']         = ['assign'];

        $actions['task_template']['name']                      = _n('Task template', 'Task templates', 1);
        $actions['task_template']['type']                      = 'dropdown';
        $actions['task_template']['table']                     = TaskTemplate::getTable();
        $actions['task_template']['force_actions']             = ['append'];
        $actions['task_template']['permitseveral']             = ['append'];
        $actions['task_template']['appendto']                  = '_tasktemplates_id';

        $actions['itilfollowup_template']['name']              = ITILFollowupTemplate::getTypeName(1);
        $actions['itilfollowup_template']['type']              = 'dropdown';
        $actions['itilfollowup_template']['table']             = ITILFollowupTemplate::getTable();
        $actions['itilfollowup_template']['force_actions']     = ['append'];
        $actions['itilfollowup_template']['permitseveral']     = ['append'];
        $actions['itilfollowup_template']['appendto']          = '_itilfollowuptemplates_id';

        $actions['global_validation']['name']                  = _n('Validation', 'Validations', 1);
        $actions['global_validation']['type']                  = 'dropdown_validation_status';

        $actions['assign_contract']['name']                  = Contract::getTypeName(1);
        $actions['assign_contract']['type']                  = 'dropdown';
        $actions['assign_contract']['table']                 = 'glpi_contracts';
        $actions['assign_contract']['force_actions']         = ['assign','regex_result'];

        return $actions;
    }


    /**
     * @since 0.85
     *
     * @see commonDBTM::getRights()
     **/
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
        return Ticket::getIcon();
    }
}
