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
 * NotificationTargetProblem Class
 **/
class NotificationTargetProblem extends NotificationTargetCommonITILObject
{
    public $private_profiles = [];

    /**
     * Get events related to tickets
     **/
    public function getEvents()
    {

        $events = ['new'            => __('New problem'),
            'update'         => __('Update of a problem'),
            'solved'         => __('Problem solved'),
            'closed'         => __('Closure of a problem'),
            'delete'         => __('Deleting a problem')
        ];

        $events = array_merge($events, parent::getEvents());
        asort($events);
        return $events;
    }


    public function getDataForObject(CommonDBTM $item, array $options, $simple = false)
    {
       // Common ITIL data
        $data = parent::getDataForObject($item, $options, $simple);

        $data["##problem.impacts##"]  = $item->getField('impactcontent');
        $data["##problem.causes##"]   = $item->getField('causecontent');
        $data["##problem.symptoms##"] = $item->getField('symptomcontent');

       // Complex mode
        if (!$simple) {
            $restrict = ['problems_id' => $item->getField('id')];
            $tickets  = getAllDataFromTable('glpi_problems_tickets', $restrict);

            $data['tickets'] = [];
            if (count($tickets)) {
                $ticket = new Ticket();
                foreach ($tickets as $row) {
                    if ($ticket->getFromDB($row['tickets_id'])) {
                        $tmp = [];

                        $tmp['##ticket.id##']
                                    = $row['tickets_id'];
                        $tmp['##ticket.date##']
                                    = $ticket->getField('date');
                        $tmp['##ticket.title##']
                                    = $ticket->getField('name');
                        $tmp['##ticket.url##']
                                    = $this->formatURL(
                                        $options['additionnaloption']['usertype'],
                                        "Ticket_" . $row['tickets_id']
                                    );
                         $tmp['##ticket.content##']
                                    = $ticket->getField('content');

                         $data['tickets'][] = $tmp;
                    }
                }
            }

            $data['##problem.numberoftickets##'] = count($data['tickets']);

            $changes  = getAllDataFromTable('glpi_changes_problems', $restrict);

            $data['changes'] = [];
            if (count($changes)) {
                $change = new Change();
                foreach ($changes as $row) {
                    if ($change->getFromDB($row['changes_id'])) {
                        $tmp = [];
                        $tmp['##change.id##']
                                    = $row['changes_id'];
                        $tmp['##change.date##']
                                    = $change->getField('date');
                        $tmp['##change.title##']
                                    = $change->getField('name');
                        $tmp['##change.url##']
                                    = $this->formatURL(
                                        $options['additionnaloption']['usertype'],
                                        "Change_" . $row['changes_id']
                                    );
                        $tmp['##change.content##']
                                    = $change->getField('content');

                        $data['changes'][] = $tmp;
                    }
                }
            }

            $data['##problem.numberofchanges##'] = count($data['changes']);

            $items    = getAllDataFromTable('glpi_items_problems', $restrict);

            $data['items'] = [];
            if (count($items)) {
                foreach ($items as $row) {
                    if ($item2 = getItemForItemtype($row['itemtype'])) {
                        if ($item2->getFromDB($row['items_id'])) {
                            $tmp = [];
                            $tmp['##item.itemtype##']    = $item2->getTypeName();
                            $tmp['##item.name##']        = $item2->getField('name');
                            $tmp['##item.serial##']      = $item2->getField('serial');
                            $tmp['##item.otherserial##'] = $item2->getField('otherserial');
                            $tmp['##item.contact##']     = $item2->getField('contact');
                            $tmp['##item.contactnum##']  = $item2->getField('contactnum');
                            $tmp['##item.location##']    = '';
                            $tmp['##item.user##']        = '';
                            $tmp['##item.group##']       = '';
                            $tmp['##item.model##']       = '';

                          //Object location
                            if ($item2->isField('locations_id') && $item_loc_id = $item2->getField('locations_id')) {
                                $tmp['##item.location##'] = Dropdown::getDropdownName('glpi_locations', $item_loc_id);
                            }

                          //Object user
                            if ($item2->getField('users_id')) {
                                $user_tmp = new User();
                                if ($user_tmp->getFromDB($item2->getField('users_id'))) {
                                    $tmp['##item.user##'] = $user_tmp->getName();
                                }
                            }

                          //Object group
                            if ($item2->getField('groups_id')) {
                                $tmp['##item.group##']
                                      = Dropdown::getDropdownName(
                                          'glpi_groups',
                                          $item2->getField('groups_id')
                                      );
                            }

                            $modeltable = getSingular($item2->getTable()) . "models";
                            $modelfield = getForeignKeyFieldForTable($modeltable);

                            if ($item2->isField($modelfield)) {
                                   $tmp['##item.model##'] = $item2->getField($modelfield);
                            }

                            $data['items'][] = $tmp;
                        }
                    }
                }
            }

            $data['##problem.numberofitems##'] = count($data['items']);
        }
        return $data;
    }


    public function getTags()
    {

        parent::getTags();

       //Locales
        $tags = ['problem.numberoftickets'   => _x('quantity', 'Number of tickets'),
            'problem.numberofchanges'   => _x('quantity', 'Number of changes'),
            'problem.impacts'           => __('Impacts'),
            'problem.causes'            => __('Causes'),
            'problem.symptoms'          => __('Symptoms'),
            'item.name'                 => _n('Associated item', 'Associated items', 1),
            'item.serial'               => __('Serial number'),
            'item.otherserial'          => __('Inventory number'),
            'item.location'             => Location::getTypeName(1),
            'item.model'                => _n('Model', 'Models', 1),
            'item.contact'              => __('Alternate username'),
            'item.contactnumber'        => __('Alternate username number'),
            'item.user'                 => User::getTypeName(1),
            'item.group'                => Group::getTypeName(1),
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'    => $tag,
                'label'  => $label,
                'value'  => true,
                'events' => NotificationTarget::TAG_FOR_ALL_EVENTS
            ]);
        }

       //Foreach global tags
        $tags = ['tickets'  => _n('Ticket', 'Tickets', Session::getPluralNumber()),
            'changes'  => _n('Change', 'Changes', Session::getPluralNumber()),
            'items'    => _n('Item', 'Items', Session::getPluralNumber())
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'     => $tag,
                'label'   => $label,
                'value'   => false,
                'foreach' => true
            ]);
        }

       //Tags with just lang
        $tags = ['problem.tickets'  => _n('Ticket', 'Tickets', Session::getPluralNumber()),
            'problem.changes'  => _n('Change', 'Changes', Session::getPluralNumber()),
            'problem.items'    => _n('Item', 'Items', Session::getPluralNumber())
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'   => $tag,
                'label' => $label,
                'value' => false,
                'lang'  => true
            ]);
        }

       //Tags without lang
        $tags = ['ticket.id'        => sprintf(__('%1$s: %2$s'), Ticket::getTypeName(1), __('ID')),
            'ticket.date'      => sprintf(__('%1$s: %2$s'), Ticket::getTypeName(1), _n('Date', 'Dates', 1)),
            'ticket.url'       => sprintf(__('%1$s: %2$s'), Ticket::getTypeName(1), __('URL')),
            'ticket.title'     => sprintf(__('%1$s: %2$s'), Ticket::getTypeName(1), __('Title')),
            'ticket.content'   => sprintf(__('%1$s: %2$s'), Ticket::getTypeName(1), __('Description')),
            'change.id'        => sprintf(__('%1$s: %2$s'), Change::getTypeName(1), __('ID')),
            'change.date'      => sprintf(__('%1$s: %2$s'), Change::getTypeName(1), _n('Date', 'Dates', 1)),
            'change.url'       => sprintf(__('%1$s: %2$s'), Change::getTypeName(1), __('URL')),
            'change.title'     => sprintf(__('%1$s: %2$s'), Change::getTypeName(1), __('Title')),
            'change.content'   => sprintf(__('%1$s: %2$s'), Change::getTypeName(1), __('Description')),
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'   => $tag,
                'label' => $label,
                'value' => true,
                'lang'  => false
            ]);
        }
        asort($this->tag_descriptions);
    }
}
