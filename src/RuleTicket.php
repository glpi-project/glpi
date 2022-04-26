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

class RuleTicket extends RuleCommonITILObject
{
   // From Rule
    public static $rightname = 'rule_ticket';

    public function getTitle()
    {
        return __('Business rules for tickets');
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

        return $criterias;
    }


    public function getActions()
    {

        $actions                                              = parent::getActions();

        $actions['type']['name']                              = _n('Type', 'Types', 1);
        $actions['type']['table']                             = 'glpi_tickets';
        $actions['type']['type']                              = 'dropdown_tickettype';

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

        $actions['locations_id']['name']                            = Location::getTypeName(1);
        $actions['locations_id']['type']                            = 'dropdown';
        $actions['locations_id']['table']                           = 'glpi_locations';
        $actions['locations_id']['force_actions']                   = ['assign', 'fromuser', 'fromitem'];

        return $actions;
    }
}
