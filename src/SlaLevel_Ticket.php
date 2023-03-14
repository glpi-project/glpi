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

/// Class SLALevel
class SlaLevel_Ticket extends CommonDBTM
{
    public static function getTypeName($nb = 0)
    {
        return __('SLA level for Ticket');
    }


    /**
     * Retrieve an item from the database
     *
     * @param $ID        ID of the item to get
     * @param $slatype
     *
     * @since 9.1 2 mandatory parameters
     *
     * @return true if succeed else false
     **/
    public function getFromDBForTicket($ID, $slaType)
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT'       => [static::getTable() . '.id'],
            'FROM'         => static::getTable(),
            'LEFT JOIN'   => [
                'glpi_slalevels' => [
                    'FKEY'   => [
                        static::getTable()   => 'slalevels_id',
                        'glpi_slalevels'     => 'id'
                    ]
                ],
                'glpi_slas'       => [
                    'FKEY'   => [
                        'glpi_slalevels'     => 'slas_id',
                        'glpi_slas'          => 'id'
                    ]
                ]
            ],
            'WHERE'        => [
                static::getTable() . '.tickets_id'  => $ID,
                'glpi_slas.type'                    => $slaType
            ],
            'LIMIT'        => 1
        ]);
        if (count($iterator) == 1) {
            $row = $iterator->current();
            return $this->getFromDB($row['id']);
        }
        return false;
    }


    /**
     * Delete entries for a ticket
     *
     * @param $tickets_id    Ticket ID
     * @param $type          Type of SLA
     *
     * @since 9.1 2 parameters mandatory
     *
     * @return void
     **/
    public function deleteForTicket($tickets_id, $slaType)
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT'    => 'glpi_slalevels_tickets.id',
            'FROM'      => 'glpi_slalevels_tickets',
            'LEFT JOIN' => [
                'glpi_slalevels'  => [
                    'ON' => [
                        'glpi_slalevels_tickets'   => 'slalevels_id',
                        'glpi_slalevels'           => 'id'
                    ]
                ],
                'glpi_slas'       => [
                    'ON' => [
                        'glpi_slalevels'  => 'slas_id',
                        'glpi_slas'       => 'id'
                    ]
                ]
            ],
            'WHERE'     => [
                'glpi_slalevels_tickets.tickets_id' => $tickets_id,
                'glpi_slas.type'                    => $slaType
            ]
        ]);

        foreach ($iterator as $data) {
            $this->delete(['id' => $data['id']]);
        }
    }


    /**
     * Give cron information
     *
     * @param $name : task's name
     *
     * @return array of information
     **/
    public static function cronInfo($name)
    {

        switch ($name) {
            case 'slaticket':
                return ['description' => __('Automatic actions of SLA')];
        }
        return [];
    }


    /**
     * Cron for ticket's automatic close
     *
     * @param $task : CronTask object
     *
     * @return integer (0 : nothing done - 1 : done)
     **/
    public static function cronSlaTicket(CronTask $task)
    {
        global $DB;

        $tot = 0;

        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_slalevels_tickets.*',
                'glpi_slas.type AS type',
            ],
            'FROM'      => 'glpi_slalevels_tickets',
            'LEFT JOIN' => [
                'glpi_slalevels'  => [
                    'ON' => [
                        'glpi_slalevels_tickets'   => 'slalevels_id',
                        'glpi_slalevels'           => 'id'
                    ]
                ],
                'glpi_slas'       => [
                    'ON' => [
                        'glpi_slalevels'  => 'slas_id',
                        'glpi_slas'       => 'id'
                    ]
                ]
            ],
            'WHERE'     => [
                'glpi_slalevels_tickets.date' => ['<', new \QueryExpression('NOW()')]
            ]
        ]);

        foreach ($iterator as $data) {
            $tot++;
            self::doLevelForTicket($data, $data['type']);
        }

        $task->setVolume($tot);
        return ($tot > 0 ? 1 : 0);
    }


    /**
     * Do a specific SLAlevel for a ticket
     *
     * @param $data          array data of an entry of slalevels_tickets
     * @param $slaType             Type of sla
     *
     * @since 9.1   2 parameters mandatory
     *
     * @return void
     **/
    public static function doLevelForTicket(array $data, $slaType)
    {

        $ticket         = new Ticket();
        $slalevelticket = new self();

       // existing ticket and not deleted
        if (
            $ticket->getFromDB($data['tickets_id'])
            && !$ticket->isDeleted()
        ) {
           // search all actors of a ticket
            foreach ($ticket->getUsers(CommonITILActor::REQUESTER) as $user) {
                $ticket->fields['_users_id_requester'][] = $user['users_id'];
            }
            foreach ($ticket->getUsers(CommonITILActor::ASSIGN) as $user) {
                $ticket->fields['_users_id_assign'][] = $user['users_id'];
            }
            foreach ($ticket->getUsers(CommonITILActor::OBSERVER) as $user) {
                $ticket->fields['_users_id_observer'][] = $user['users_id'];
            }

            foreach ($ticket->getGroups(CommonITILActor::REQUESTER) as $group) {
                $ticket->fields['_groups_id_requester'][] = $group['groups_id'];
            }
            foreach ($ticket->getGroups(CommonITILActor::ASSIGN) as $group) {
                $ticket->fields['_groups_id_assign'][] = $group['groups_id'];
            }
            foreach ($ticket->getGroups(CommonITILActor::OBSERVER) as $group) {
                $ticket->fields['_groups_id_observer'][] = $group['groups_id'];
            }

            foreach ($ticket->getSuppliers(CommonITILActor::ASSIGN) as $supplier) {
                $ticket->fields['_suppliers_id_assign'][] = $supplier['suppliers_id'];
            }

            $itil_project = new Itil_Project();
            $itil_projects = $itil_project->find(["itemtype" => Ticket::class, "items_id" => $data['tickets_id']]);
            foreach ($itil_projects as $rel_values) {
                $ticket->fields['assign_project'][] = $rel_values['projects_id'];
            }

            $slalevel = new SlaLevel();
            $sla      = new SLA();
           // Check if sla datas are OK
            list($dateField, $slaField) = SLA::getFieldNames($slaType);
            if (($ticket->fields[$slaField] > 0)) {
                if ($ticket->fields['status'] == CommonITILObject::CLOSED) {
                   // Drop line when status is closed
                    $slalevelticket->delete(['id' => $data['id']]);
                } else if ($ticket->fields['status'] != CommonITILObject::SOLVED) {
                   // No execution if ticket has been taken into account
                    if (
                        !(($slaType == SLM::TTO)
                        && ($ticket->fields['takeintoaccount_delay_stat'] > 0))
                    ) {
                       // If status = solved : keep the line in case of solution not validated
                        $input['id']           = $ticket->getID();
                        $input['_auto_update'] = true;

                        if (
                            $slalevel->getRuleWithCriteriasAndActions($data['slalevels_id'], 1, 1)
                            && $sla->getFromDB($ticket->fields[$slaField])
                        ) {
                            $doit = true;
                            if (count($slalevel->criterias)) {
                                $doit = $slalevel->checkCriterias($ticket->fields);
                            }
                           // Process rules
                            if ($doit) {
                                $input = $slalevel->executeActions($input, [], $ticket->fields);
                            }
                        }

                       // Put next level in todo list
                        if (
                            $next = $slalevel->getNextSlaLevel(
                                $ticket->fields[$slaField],
                                $data['slalevels_id']
                            )
                        ) {
                            $sla->addLevelToDo($ticket, $next);
                        }
                       // Action done : drop the line
                        $slalevelticket->delete(['id' => $data['id']]);

                        $ticket->update($input);
                    } else {
                       // Drop line
                        $slalevelticket->delete(['id' => $data['id']]);
                    }
                }
            } else {
               // Drop line
                $slalevelticket->delete(['id' => $data['id']]);
            }
        } else {
           // Drop line
            $slalevelticket->delete(['id' => $data['id']]);
        }
    }


    /**
     * Replay all task needed for a specific ticket
     *
     * @param $tickets_id Ticket ID
     * @param $slaType Type of sla
     *
     * @since 9.1    2 parameters mandatory
     *
     */
    public static function replayForTicket($tickets_id, $slaType)
    {
        global $DB;

        $criteria = [
            'SELECT'    => 'glpi_slalevels_tickets.*',
            'FROM'      => 'glpi_slalevels_tickets',
            'LEFT JOIN' => [
                'glpi_slalevels'  => [
                    'ON' => [
                        'glpi_slalevels_tickets'   => 'slalevels_id',
                        'glpi_slalevels'           => 'id'
                    ]
                ],
                'glpi_slas'       => [
                    'ON' => [
                        'glpi_slalevels'  => 'slas_id',
                        'glpi_slas'       => 'id'
                    ]
                ]
            ],
            'WHERE'     => [
                'glpi_slalevels_tickets.date'       => ['<', new \QueryExpression('NOW()')],
                'glpi_slalevels_tickets.tickets_id' => $tickets_id,
                'glpi_slas.type'                    => $slaType
            ]
        ];

        $number = 0;
        $last_escalation = -1;
        do {
            $iterator = $DB->request($criteria);
            $number = count($iterator);
            if ($number == 1) {
                $data = $iterator->current();
                if ($data['id'] === $last_escalation) {
                    // Possible infinite loop. Trying to apply exact same SLA assignment.
                    break;
                }
                self::doLevelForTicket($data, $slaType);
                $last_escalation = $data['id'];
            }
        } while ($number == 1);
    }
}
