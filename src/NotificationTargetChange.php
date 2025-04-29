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

/**
 * NotificationTargetChange Class
 *
 * @since 0.85
 **/
class NotificationTargetChange extends NotificationTargetCommonITILObject
{
    public $private_profiles = [];

    /**
     * Get events related to tickets
     **/
    public function getEvents()
    {

        $events = ['new'               => __('New change'),
            'update'            => __('Update of a change'),
            'solved'            => __('Change solved'),
            'validation'        => __('Validation request'),
            'validation_answer' => __('Validation request answer'),
            'closed'            => __('Closure of a change'),
            'delete'            => __('Deleting a change'),
        ];

        $events = array_merge($events, parent::getEvents());
        asort($events);
        return $events;
    }


    public function getDataForObject(CommonDBTM $item, array $options, $simple = false)
    {
        // Common ITIL data
        $data = parent::getDataForObject($item, $options, $simple);

        // Specific data
        $data['##change.urlvalidation##']
                     = $this->formatURL(
                         $options['additionnaloption']['usertype'],
                         "change_" . $item->getField("id") . '_Change$main'
                     );
        $data['##change.globalvalidation##']
                     = ChangeValidation::getStatus($item->getField('global_validation'));

        $data['##change.impactcontent##']      = $item->getField("impactcontent");
        $data['##change.controlistcontent##']  = $item->getField("controlistcontent");
        $data['##change.rolloutplancontent##'] = $item->getField("rolloutplancontent");
        $data['##change.backoutplancontent##'] = $item->getField("backoutplancontent");
        $data['##change.checklistcontent##']   = $item->getField("checklistcontent");

        // $data["##problem.impacts##"]  = $item->getField('impactcontent');
        // $data["##problem.causes##"]   = $item->getField('causecontent');
        // $data["##problem.symptoms##"] = $item->getField('symptomcontent');

        // Complex mode
        if (!$simple) {
            $restrict = ['changes_id' => $item->getField('id')];
            $tickets  = getAllDataFromTable('glpi_changes_tickets', $restrict);

            $data['tickets'] = [];
            if (count($tickets)) {
                $ticket = new Ticket();
                foreach ($tickets as $row) {
                    if ($ticket->getFromDB($row['tickets_id'])) {
                        $tmp = [];
                        $tmp['##ticket.id##']      = $row['tickets_id'];
                        $tmp['##ticket.date##']    = $ticket->getField('date');
                        $tmp['##ticket.title##']   = $ticket->getField('name');
                        $tmp['##ticket.url##']     = $this->formatURL(
                            $options['additionnaloption']['usertype'],
                            "Ticket_" . $row['tickets_id']
                        );
                        $tmp['##ticket.content##'] = $ticket->getField('content');

                        $data['tickets'][] = $tmp;
                    }
                }
            }

            $data['##change.numberoftickets##'] = count($data['tickets']);

            $problems = getAllDataFromTable('glpi_changes_problems', $restrict);

            $data['problems'] = [];
            if (count($problems)) {
                $problem = new Problem();
                foreach ($problems as $row) {
                    if ($problem->getFromDB($row['problems_id'])) {
                        $tmp = [];
                        $tmp['##problem.id##']
                                       = $row['problems_id'];
                        $tmp['##problem.date##']
                                       = $problem->getField('date');
                        $tmp['##problem.title##']
                                       = $problem->getField('name');
                        $tmp['##problem.url##']
                                       = $this->formatURL(
                                           $options['additionnaloption']['usertype'],
                                           "Problem_" . $row['problems_id']
                                       );
                        $tmp['##problem.content##']
                                     = $problem->getField('content');

                        $data['problems'][] = $tmp;
                    }
                }
            }

            $data['##change.numberofproblems##'] = count($data['problems']);

            $items    = getAllDataFromTable('glpi_changes_items', $restrict);

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
                            if ($item2->getField('locations_id') != NOT_AVAILABLE) {
                                $tmp['##item.location##']
                                 = Dropdown::getDropdownName(
                                     'glpi_locations',
                                     $item2->getField('locations_id')
                                 );
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

            $data['##change.numberofitems##'] = count($data['items']);

            //Validation infos
            if (isset($options['validation_id']) && $options['validation_id']) {
                $restrict['glpi_changevalidations.id'] = $options['validation_id'];
            }

            $validations = getAllDataFromTable(
                'glpi_changevalidations',
                [
                    'WHERE'  => $restrict,
                    'ORDER'  => ['submission_date DESC', 'id ASC'],
                ]
            );
            $data['validations'] = [];
            foreach ($validations as $validation) {
                $tmp = [];
                $tmp['##validation.submission.title##']
                                 //TRANS: %s is the user name
                     = sprintf(
                         __('An approval request has been submitted by %s'),
                         getUserName($validation['users_id'])
                     );

                $tmp['##validation.answer.title##']
                                //TRANS: %s is the user name
                        = sprintf(
                            __('An answer to an approval request was produced by %s'),
                            getUserName($validation['users_id_validate'])
                        );

                $tmp['##validation.author##']
                        = getUserName($validation['users_id']);

                $tmp['##validation.status##']
                        = ChangeValidation::getStatus($validation['status']);

                $tmp['##validation.storestatus##']
                        = $validation['status'];

                $tmp['##validation.submissiondate##']
                        = Html::convDateTime($validation['submission_date']);

                $tmp['##validation.commentsubmission##']
                        = $validation['comment_submission'];

                $tmp['##validation.validationdate##']
                        = Html::convDateTime($validation['validation_date']);

                $tmp['##validation.validator##']
                        =  getUserName($validation['users_id_validate']);

                $tmp['##validation.commentvalidation##']
                        = $validation['comment_validation'];

                $data['validations'][] = $tmp;
            }
        }
        return $data;
    }


    public function getTags()
    {

        parent::getTags();

        //Locales
        $tags = ['change.numberoftickets'    => _x('quantity', 'Number of tickets'),
            'change.numberofproblems'   => _x('quantity', 'Number of problems'),
            'change.impactcontent'      => __('Impact'),
            'change.controlistcontent'  => __('Control list'),
            'change.rolloutplancontent' => __('Deployment plan'),
            'change.backoutplancontent' => __('Backup plan'),
            'change.checklistcontent'   => __('Checklist'),
            // 'problem.impacts'           => __('Impacts'),
            // 'problem.causes'            => __('Causes'),
            // 'problem.symptoms'          => __('Symptoms'),
            'item.name'                 => _n('Associated item', 'Associated items', 1),
            'item.serial'               => __('Serial number'),
            'item.otherserial'          => __('Inventory number'),
            'item.location'             => Location::getTypeName(1),
            'item.model'                => _n('Model', 'Models', 1),
            'item.contact'              => __('Alternate username'),
            'item.contactnumber'        => __('Alternate username number'),
            'item.user'                 => User::getTypeName(1),
            'item.group'                => Group::getTypeName(1),
            'change.globalvalidation'   => __('Global approval status'),
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'    => $tag,
                'label'  => $label,
                'value'  => true,
                'events' => NotificationTarget::TAG_FOR_ALL_EVENTS,
            ]);
        }

        //Events specific for validation
        $tags = ['validation.author'            => _n('Requester', 'Requesters', 1),
            'validation.status'            => __('Status of the approval request'),
            'validation.submissiondate'    => sprintf(
                __('%1$s: %2$s'),
                __('Request'),
                _n('Date', 'Dates', 1)
            ),
            'validation.commentsubmission' => sprintf(
                __('%1$s: %2$s'),
                __('Request'),
                __('Comments')
            ),
            'validation.validationdate'    => sprintf(
                __('%1$s: %2$s'),
                _n('Validation', 'Validations', 1),
                _n('Date', 'Dates', 1)
            ),
            'validation.validator'         => __('Decision-maker'),
            'validation.commentvalidation' => sprintf(
                __('%1$s: %2$s'),
                _n('Validation', 'Validations', 1),
                __('Comments')
            ),
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'    => $tag,
                'label'  => $label,
                'value'  => true,
                'events' => ['validation', 'validation_answer'],
            ]);
        }

        //Tags without lang for validation
        $tags = ['validation.submission.title'
                                    => __('A validation request has been submitted'),
            'validation.answer.title'
                                    => __('An answer to a validation request was produced'),
            'change.urlvalidation'
                                    => sprintf(
                                        __('%1$s: %2$s'),
                                        __('Validation request'),
                                        __('URL')
                                    ),
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'   => $tag,
                'label' => $label,
                'value' => true,
                'lang'  => false,
                'events' => ['validation', 'validation_answer'],
            ]);
        }

        //Foreach global tags
        $tags = ['tickets'     => _n('Ticket', 'Tickets', Session::getPluralNumber()),
            'problems'    => Problem::getTypeName(Session::getPluralNumber()),
            'items'       => _n('Item', 'Items', Session::getPluralNumber()),
            'validations' => _n('Validation', 'Validations', Session::getPluralNumber()),
            'documents'   => Document::getTypeName(Session::getPluralNumber()),
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'     => $tag,
                'label'   => $label,
                'value'   => false,
                'foreach' => true,
            ]);
        }

        //Tags with just lang
        $tags = ['change.tickets'   => _n('Ticket', 'Tickets', Session::getPluralNumber()),
            'change.problems'  => Problem::getTypeName(Session::getPluralNumber()),
            'items'            => _n('Item', 'Items', Session::getPluralNumber()),
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'   => $tag,
                'label' => $label,
                'value' => false,
                'lang'  => true,
            ]);
        }

        //Tags without lang
        $tags = ['ticket.id'       => sprintf(__('%1$s: %2$s'), Ticket::getTypeName(1), __('ID')),
            'ticket.date'     => sprintf(__('%1$s: %2$s'), Ticket::getTypeName(1), _n('Date', 'Dates', 1)),
            'ticket.url'      => sprintf(__('%1$s: %2$s'), Ticket::getTypeName(1), __('URL')),
            'ticket.title'    => sprintf(__('%1$s: %2$s'), Ticket::getTypeName(1), __('Title')),
            'ticket.content'  => sprintf(__('%1$s: %2$s'), Ticket::getTypeName(1), __('Description')),
            'problem.id'      => sprintf(__('%1$s: %2$s'), Problem::getTypeName(1), __('ID')),
            'problem.date'    => sprintf(__('%1$s: %2$s'), Problem::getTypeName(1), _n('Date', 'Dates', 1)),
            'problem.url'     => sprintf(__('%1$s: %2$s'), Problem::getTypeName(1), __('URL')),
            'problem.title'   => sprintf(__('%1$s: %2$s'), Problem::getTypeName(1), __('Title')),
            'problem.content' => sprintf(__('%1$s: %2$s'), Problem::getTypeName(1), __('Description')),
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'   => $tag,
                'label' => $label,
                'value' => true,
                'lang'  => false,
            ]);
        }
        asort($this->tag_descriptions);
    }
}
