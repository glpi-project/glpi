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

class RuleTicket extends RuleCommonITILObject
{
    // From Rule
    public static $rightname = 'rule_ticket';

    public function getTitle()
    {
        return __('Business rules for tickets');
    }

    #[Override]
    public function getTargetItilType(): Ticket
    {
        return new Ticket();
    }

    public function executeActions($output, $params, array $input = [])
    {
        $output = parent::executeActions($output, $params, $input);
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

                    case "delete":
                        if ($action->fields["field"]) {
                            $output[$action->fields["field"]] = null;
                        }
                        break;

                    case "assign":
                        // Special case of slas_id_ttr & slas_id_tto & olas_id_ttr & olas_id_tto
                        if (
                            $action->fields["field"] === 'slas_id_ttr'
                            || $action->fields["field"] === 'slas_id_tto'
                            || $action->fields["field"] === 'olas_id_ttr'
                            || $action->fields["field"] === 'olas_id_tto'
                        ) {
                            $output['_' . $action->fields["field"]] = $action->fields["value"];
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

                        break;

                    case "append":
                        $value   = $action->fields["value"];

                        if ($action->fields["field"] === "assign_project") {
                            if (!array_key_exists("_projects_id", $output)) {
                                $output["_projects_id"] = [];
                            }
                            $output["_projects_id"][] = $value;
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

                    case 'fromitem':
                        if ($action->fields['field'] == 'locations_id' && isset($output['_locations_id_of_item'])) {
                            $output['locations_id'] = $output['_locations_id_of_item'];
                        }
                        break;

                    case 'regex_result':
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

    public function getCriterias()
    {

        static $criterias = [];

        if (count($criterias)) {
            return $criterias;
        }

        $criterias = parent::getCriterias();

        $criterias['type']['table']                           = 'glpi_tickets';
        $criterias['type']['field']                           = 'type';
        $criterias['type']['name']                            = _n('Type', 'Types', 1);
        $criterias['type']['linkfield']                       = 'type';
        $criterias['type']['type']                            = 'dropdown_tickettype';

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

        $criterias['locations_id']['table']                   = 'glpi_locations';
        $criterias['locations_id']['field']                   = 'completename';
        $criterias['locations_id']['name']                    = Location::getTypeName(1);
        $criterias['locations_id']['linkfield']               = 'locations_id';
        $criterias['locations_id']['type']                    = 'dropdown';

        $criterias['_locations_code']['table']              = 'glpi_locations';
        $criterias['_locations_code']['field']              = 'code';
        $criterias['_locations_code']['name']               = __('Location code');

        return $criterias;
    }


    public function getActions()
    {

        $actions                                              = parent::getActions();

        // set a ticket type
        $actions['type']['name']                              = _n('Type', 'Types', 1);
        $actions['type']['table']                             = 'glpi_tickets';
        $actions['type']['type']                              = 'dropdown_tickettype';

        // assign a projet
        $actions['assign_project']['name']                  = Project::getTypeName(1);
        $actions['assign_project']['type']                  = 'dropdown';
        $actions['assign_project']['table']                 = 'glpi_projects';
        $actions['assign_project']['permitseveral']         = ['append'];
        $actions['assign_project']['force_actions']         = ['assign','regex_result', 'append'];
        $actions['assign_project']['appendto']              = '_projects_id';

        // assign sla ttr
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

        // empty ttr
        $actions['time_to_resolve']['name']                   = __('Time to resolve');
        $actions['time_to_resolve']['type']                   = 'yesno';
        $actions['time_to_resolve']['force_actions']          = ['delete'];

        // assign (existing) sla tto
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

        // empty sla tto
        $actions['time_to_own']['name']                       = __('Time to own');
        $actions['time_to_own']['type']                       = 'yesno';
        $actions['time_to_own']['force_actions']              = ['delete'];

        // assign (existing) ola ttr
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

        // empty ola ttr
        $actions['internal_time_to_resolve']['name']          = __('Internal time to resolve');
        $actions['internal_time_to_resolve']['type']          = 'yesno';
        $actions['internal_time_to_resolve']['force_actions'] = ['delete'];

        // assign (existing) ola tto
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

        // set ola tto value
        $actions['internal_time_to_own']['name']              = __('Internal Time to own');
        $actions['internal_time_to_own']['type']              = 'yesno';
        $actions['internal_time_to_own']['force_actions']     = ['delete'];

        // assign a location
        $actions['locations_id']['name']                            = Location::getTypeName(1);
        $actions['locations_id']['type']                            = 'dropdown';
        $actions['locations_id']['table']                           = 'glpi_locations';
        $actions['locations_id']['force_actions']                   = ['assign', 'fromuser', 'fromitem'];

        // assign a contract
        $actions['assign_contract']['name']                  = Contract::getTypeName(1);
        $actions['assign_contract']['type']                  = 'dropdown';
        $actions['assign_contract']['table']                 = 'glpi_contracts';
        $actions['assign_contract']['force_actions']         = ['assign','regex_result'];

        return $actions;
    }
}
