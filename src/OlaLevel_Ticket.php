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

/**
 * @since 9.2
 */


/// Class OLALevel
class OlaLevel_Ticket extends CommonDBTM
{
    public static function getTypeName($nb = 0)
    {
        return __('OLA level for Ticket');
    }


    /**
     * Retrieve an item from the database
     *
     * @param $ID        ID of the item to get
     * @param $olatype
     *
     * @since 9.1 2 mandatory parameters
     *
     * @return true if succeed else false
     **/
    public function getFromDBForTicket($ID, $olaType)
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT'       => [static::getTable() . '.id'],
            'FROM'         => static::getTable(),
            'LEFT JOIN'   => [
                'glpi_olalevels'  => [
                    'FKEY'   => [
                        static::getTable()   => 'olalevels_id',
                        'glpi_olalevels'     => 'id'
                    ]
                ],
                'glpi_olas'       => [
                    'FKEY'   => [
                        'glpi_olalevels'     => 'olas_id',
                        'glpi_olas'          => 'id'
                    ]
                ]
            ],
            'WHERE'        => [
                static::getTable() . '.tickets_id'  => $ID,
                'glpi_olas.type'                    => $olaType
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
     * @param $type          Type of OLA
     *
     * @since 9.1 2 parameters mandatory
     *
     * @return void
     **/
    public function deleteForTicket($tickets_id, $olaType)
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT'    => 'glpi_olalevels_tickets.id',
            'FROM'      => 'glpi_olalevels_tickets',
            'LEFT JOIN' => [
                'glpi_olalevels'  => [
                    'ON' => [
                        'glpi_olalevels_tickets'   => 'olalevels_id',
                        'glpi_olalevels'           => 'id'
                    ]
                ],
                'glpi_olas'       => [
                    'ON' => [
                        'glpi_olalevels'  => 'olas_id',
                        'glpi_olas'       => 'id'
                    ]
                ]
            ],
            'WHERE'     => [
                'glpi_olalevels_tickets.tickets_id' => $tickets_id,
                'glpi_olas.type'                    => $olaType
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
            case 'olaticket':
                return ['description' => __('Automatic actions of OLA')];
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
    public static function cronOlaTicket(CronTask $task)
    {
        global $DB;

        $tot = 0;

        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_olalevels_tickets.*',
                'glpi_olas.type AS type'
            ],
            'FROM'      => 'glpi_olalevels_tickets',
            'LEFT JOIN' => [
                'glpi_olalevels'  => [
                    'ON' => [
                        'glpi_olalevels_tickets'   => 'olalevels_id',
                        'glpi_olalevels'           => 'id'
                    ]
                ],
                'glpi_olas'       => [
                    'ON' => [
                        'glpi_olalevels'  => 'olas_id',
                        'glpi_olas'       => 'id'
                    ]
                ]
            ],
            'WHERE'     => [
                'glpi_olalevels_tickets.date' => ['<', new \QueryExpression('NOW()')]
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
     * Do a specific OLAlevel for a ticket
     *
     * @param $data          array data of an entry of olalevels_tickets
     * @param $olaType             Type of ola
     *
     * @since 9.1   2 parameters mandatory
     *
     * @return void
     **/
    public static function doLevelForTicket(array $data, $olaType)
    {

        $ticket         = new Ticket();
        $olalevelticket = new self();

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

            $olalevel = new OlaLevel();
            $ola      = new OLA();
           // Check if ola datas are OK
            list($dateField, $olaField) = OLA::getFieldNames($olaType);
            if (($ticket->fields[$olaField] > 0)) {
                if ($ticket->fields['status'] == CommonITILObject::CLOSED) {
                   // Drop line when status is closed
                    $olalevelticket->delete(['id' => $data['id']]);
                } else if ($ticket->fields['status'] != CommonITILObject::SOLVED) {
                   // No execution if ticket has been taken into account
                    if (
                        !(($olaType == SLM::TTO)
                        && ($ticket->fields['takeintoaccount_delay_stat'] > 0))
                    ) {
                       // If status = solved : keep the line in case of solution not validated
                        $input = [
                            'id'           => $ticket->getID(),
                            '_auto_update' => true,
                        ];

                        if (
                            $olalevel->getRuleWithCriteriasAndActions($data['olalevels_id'], 1, 1)
                            && $ola->getFromDB($ticket->fields[$olaField])
                        ) {
                            $doit = true;
                            if (count($olalevel->criterias)) {
                                $doit = $olalevel->checkCriterias($ticket->fields);
                            }
                           // Process rules
                            if ($doit) {
                                $input = $olalevel->executeActions($input, [], $ticket->fields);
                            }
                        }

                       // Put next level in todo list
                        if (
                            $next = $olalevel->getNextOlaLevel(
                                $ticket->fields[$olaField],
                                $data['olalevels_id']
                            )
                        ) {
                            $ola->addLevelToDo($ticket, $next);
                        }
                       // Action done : drop the line
                        $olalevelticket->delete(['id' => $data['id']]);

                        $ticket->update($input);
                    } else {
                       // Drop line
                        $olalevelticket->delete(['id' => $data['id']]);
                    }
                }
            } else {
               // Drop line
                $olalevelticket->delete(['id' => $data['id']]);
            }
        } else {
           // Drop line
            $olalevelticket->delete(['id' => $data['id']]);
        }
    }


    /**
     * Replay all task needed for a specific ticket
     *
     * @param $tickets_id Ticket ID
     * @param $olaType Type of ola
     *
     * @since 9.1    2 parameters mandatory
     *
     */
    public static function replayForTicket($tickets_id, $olaType)
    {
        global $DB;

        $criteria = [
            'SELECT'    => 'glpi_olalevels_tickets.*',
            'FROM'      => 'glpi_olalevels_tickets',
            'LEFT JOIN' => [
                'glpi_olalevels'  => [
                    'ON' => [
                        'glpi_olalevels_tickets'   => 'olalevels_id',
                        'glpi_olalevels'           => 'id'
                    ]
                ],
                'glpi_olas'       => [
                    'ON' => [
                        'glpi_olalevels'  => 'olas_id',
                        'glpi_olas'       => 'id'
                    ]
                ]
            ],
            'WHERE'     => [
                'glpi_olalevels_tickets.date'       => ['<', new \QueryExpression('NOW()')],
                'glpi_olalevels_tickets.tickets_id' => $tickets_id,
                'glpi_olas.type'                    => $olaType
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
                self::doLevelForTicket($data, $olaType);
                $last_escalation = $data['id'];
            }
        } while ($number == 1);
    }
}
