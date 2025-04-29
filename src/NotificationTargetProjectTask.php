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
 * NotificationTargetTicket Class
 *
 * @since 0.85
 **/
class NotificationTargetProjectTask extends NotificationTarget
{
    /**
     * Get events related to tickets
     *
     * @return array
     **/
    public function getEvents()
    {

        $events = ['new'               => __('New project task'),
            'update'            => __('Update of a project task'),
            'delete'            => __('Deletion of a project task'),
        ];
        asort($events);
        return $events;
    }


    public function addAdditionalTargets($event = '')
    {

        $this->addTarget(Notification::TEAM_USER, __('Project task team user'));
        $this->addTarget(Notification::TEAM_GROUP, __('Project task team group'));
        $this->addTarget(Notification::TEAM_GROUP_SUPERVISOR, __('Manager of group of project team'));
        $this->addTarget(
            Notification::TEAM_GROUP_WITHOUT_SUPERVISOR,
            __("Group of project team except manager users")
        );
        $this->addTarget(Notification::TEAM_CONTACT, __('Contact of project team'));
        $this->addTarget(Notification::TEAM_SUPPLIER, __('Supplier of project team'));
    }


    public function addSpecificTargets($data, $options)
    {
        // Look for all targets whose type is Notification::ITEM_USER
        switch ($data['type']) {
            case Notification::USER_TYPE:
                switch ($data['items_id']) {
                    // Send to the users in project team
                    case Notification::TEAM_USER:
                        $this->addTeamUsers();
                        break;

                        // Send to the groups in project task team
                    case Notification::TEAM_GROUP:
                        $this->addTeamGroups(0);
                        break;

                        // Send to the groups supervisors in project team
                    case Notification::TEAM_GROUP_SUPERVISOR:
                        $this->addProjectTeamGroups(1);
                        break;

                        // Send to the groups without supervisors in project team
                    case Notification::TEAM_GROUP_WITHOUT_SUPERVISOR:
                        $this->addProjectTeamGroups(2);
                        break;

                        // Send to the contacts in project team
                    case Notification::TEAM_CONTACT:
                        $this->addTeamContacts();
                        break;

                        // Send to the suppliers in project team
                    case Notification::TEAM_SUPPLIER:
                        $this->addTeamSuppliers();
                        break;
                }
        }
    }


    /**
     * Add team users to the notified user list
     *
     * @return void
     **/
    public function addTeamUsers()
    {
        /** @var \DBmysql $DB */
        global $DB;

        $iterator = $DB->request([
            'SELECT' => 'items_id',
            'FROM'   => 'glpi_projecttaskteams',
            'WHERE'  => [
                'itemtype'        => 'User',
                'projecttasks_id' => $this->obj->fields['id'],
            ],
        ]);

        $user = new User();
        foreach ($iterator as $data) {
            if ($user->getFromDB($data['items_id'])) {
                $this->addToRecipientsList(['language' => $user->getField('language'),
                    'users_id' => $user->getField('id'),
                ]);
            }
        }
    }


    /**
     * Add team groups to the notified user list
     *
     * @param integer $manager 0 all users, 1 only supervisors, 2 all users without supervisors
     *
     * @return void
     **/
    public function addTeamGroups($manager)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $iterator = $DB->request([
            'SELECT' => 'items_id',
            'FROM'   => 'glpi_projecttaskteams',
            'WHERE'  => [
                'itemtype'        => 'Group',
                'projecttasks_id' => $this->obj->fields['id'],
            ],
        ]);

        foreach ($iterator as $data) {
            $this->addForGroup($manager, $data['items_id']);
        }
    }

    /**
     * Add project team groups to the notified user list
     *
     * @param integer $manager 0 all users, 1 only supervisors, 2 all users without supervisors
     *
     * @return void
     **/
    public function addProjectTeamGroups($manager)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $iterator = $DB->request([
            'SELECT' => 'items_id',
            'FROM'   => 'glpi_projectteams',
            'WHERE'  => [
                'itemtype'    => 'Group',
                'projects_id' => $this->obj->fields['projects_id'],
            ],
        ]);

        foreach ($iterator as $data) {
            $this->addForGroup($manager, $data['items_id']);
        }
    }


    /**
     * Add team contacts to the notified user list
     *
     * @return void
     **/
    public function addTeamContacts()
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $iterator = $DB->request([
            'SELECT' => 'items_id',
            'FROM'   => 'glpi_projecttaskteams',
            'WHERE'  => [
                'itemtype'        => 'Contact',
                'projecttasks_id' => $this->obj->fields['id'],
            ],
        ]);

        $contact = new Contact();
        foreach ($iterator as $data) {
            if ($contact->getFromDB($data['items_id'])) {
                $this->addToRecipientsList(["email"    => $contact->fields["email"],
                    "name"     => $contact->getName(),
                    "language" => $CFG_GLPI["language"],
                    'usertype' => NotificationTarget::ANONYMOUS_USER,
                ]);
            }
        }
    }


    /**
     * Add team suppliers to the notified user list
     *
     * @return void
     **/
    public function addTeamSuppliers()
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $iterator = $DB->request([
            'SELECT' => 'items_id',
            'FROM'   => 'glpi_projecttaskteams',
            'WHERE'  => [
                'itemtype'        => 'Supplier',
                'projecttasks_id' => $this->obj->fields['id'],
            ],
        ]);

        $supplier = new Supplier();
        foreach ($iterator as $data) {
            if ($supplier->getFromDB($data['items_id'])) {
                $this->addToRecipientsList(["email"    => $supplier->fields["email"],
                    "name"     => $supplier->getName(),
                    "language" => $CFG_GLPI["language"],
                    'usertype' => NotificationTarget::ANONYMOUS_USER,
                ]);
            }
        }
    }


    public function addDataForTemplate($event, $options = [])
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        //----------- Reservation infos -------------- //
        $events     = $this->getAllEvents();
        $item       = $this->obj;

        $this->data['##projecttask.action##']
                  = $events[$event];
        $this->data['##projecttask.url##']
                  = $this->formatURL(
                      $options['additionnaloption']['usertype'],
                      "ProjectTask_" . $item->getField("id")
                  );
        $this->data["##projecttask.name##"]
                  = $item->getField('name');
        $this->data["##projecttask.project##"]
                  = Dropdown::getDropdownName('glpi_projects', $item->getField('projects_id'));
        $this->data["##projecttask.projecturl##"]
                  = $this->formatURL(
                      $options['additionnaloption']['usertype'],
                      "Project_" . $item->getField("projects_id")
                  );
        $this->data["##projecttask.description##"]
                  = $item->getField('content');
        $this->data["##projecttask.comments##"]
                  = $item->getField('comment');
        $this->data["##projecttask.creationdate##"]
                  = Html::convDateTime($item->getField('date_creation'));
        $this->data["##projecttask.lastupdatedate##"]
                  = Html::convDateTime($item->getField('date_mod'));
        $this->data["##projecttask.percent##"]
                  = Dropdown::getValueWithUnit($item->getField('percent_done'), "%");
        $this->data["##projecttask.planstartdate##"]
                  = Html::convDateTime($item->getField('plan_start_date'));
        $this->data["##projecttask.planenddate##"]
                  = Html::convDateTime($item->getField('plan_end_date'));
        $this->data["##projecttask.realstartdate##"]
                  = Html::convDateTime($item->getField('real_start_date'));
        $this->data["##projecttask.realenddate##"]
                  = Html::convDateTime($item->getField('real_end_date'));

        $this->data["##projecttask.plannedduration##"]
                  = Html::timestampToString($item->getField('planned_duration'), false);
        $this->data["##projecttask.effectiveduration##"]
                  = Html::timestampToString($item->getField('effective_duration'), false);
        $ticket_duration
                  = ProjectTask_Ticket::getTicketsTotalActionTime($item->getID());
        $this->data["##projecttask.ticketsduration##"]
                  = Html::timestampToString($ticket_duration, false);
        $this->data["##projecttask.totalduration##"]
                  = Html::timestampToString(
                      $ticket_duration + $item->getField('effective_duration'),
                      false
                  );

        $entity = new Entity();
        $this->data["##projecttask.entity##"] = '';
        $this->data["##projecttask.shortentity##"] = '';
        if ($entity->getFromDB($this->getEntity())) {
            $this->data["##projecttask.entity##"]      = $entity->getField('completename');
            $this->data["##projecttask.shortentity##"] = $entity->getField('name');
        }

        $this->data["##projecttask.father##"] = '';
        if ($item->getField('projecttasks_id')) {
            $this->data["##projecttask.father##"]
                              = Dropdown::getDropdownName(
                                  'glpi_projecttasks',
                                  $item->getField('projecttasks_id')
                              );
        }

        $this->data["##projecttask.state##"] = '';
        if ($item->getField('projectstates_id')) {
            $this->data["##projecttask.state##"]
                              = Dropdown::getDropdownName(
                                  'glpi_projectstates',
                                  $item->getField('projectstates_id')
                              );
        }

        $this->data["##projecttask.type##"] = '';
        if ($item->getField('projecttasktypes_id')) {
            $this->data["##projecttask.type##"]
                              = Dropdown::getDropdownName(
                                  'glpi_projecttasktypes',
                                  $item->getField('projecttasktypes_id')
                              );
        }

        $this->data["##projecttask.createbyuser##"] = '';
        if ($item->getField('users_id')) {
            $user_tmp = new User();
            $user_tmp->getFromDB($item->getField('users_id'));
            $this->data["##projecttask.createbyuser##"] = $user_tmp->getName();
        }

        // Team infos
        $restrict = ['projecttasks_id' => $item->getField('id')];
        $items    = getAllDataFromTable('glpi_projecttaskteams', $restrict);

        $this->data['teammembers'] = [];
        if (count($items)) {
            foreach ($items as $data) {
                if ($item2 = getItemForItemtype($data['itemtype'])) {
                    if ($item2->getFromDB($data['items_id'])) {
                        $tmp                               = [];
                        $tmp['##teammember.itemtype##']    = $item2->getTypeName();
                        $tmp['##teammember.name##']        = $item2->getName();
                        $this->data['teammembers'][]      = $tmp;
                    }
                }
            }
        }

        $this->data['##projecttask.numberofteammembers##'] = count($this->data['teammembers']);

        // Task infos
        $tasks                = getAllDataFromTable(
            'glpi_projecttasks',
            [
                'WHERE'  => $restrict,
                'ORDER'  => ['date_creation DESC', 'id ASC'],
            ]
        );
        $this->data['tasks'] = [];
        foreach ($tasks as $task) {
            $tmp                            = [];
            $tmp['##task.creationdate##']   = Html::convDateTime($task['date_creation']);
            $tmp['##task.lastupdatedate##'] = Html::convDateTime($task['date_mod']);
            $tmp['##task.name##']           = $task['name'];
            $tmp['##task.description##']    = $task['content'];
            $tmp['##task.comments##']       = $task['comment'];

            $tmp['##task.state##']          = '';
            if ($task['projectstates_id']) {
                $tmp['##task.state##'] = Dropdown::getDropdownName('glpi_projectstates', $task['projectstates_id']);
            }
            $tmp['##task.type##']           = '';
            if ($task['projecttasktypes_id']) {
                $tmp['##task.type##'] = Dropdown::getDropdownName('glpi_projecttasktypes', $task['projecttasktypes_id']);
            }
            $tmp['##task.percent##']        = Dropdown::getValueWithUnit($task['percent_done'], "%");

            $this->data["##task.planstartdate##"]    = '';
            $this->data["##task.planenddate##"]      = '';
            $this->data["##task.realstartdate##"]    = '';
            $this->data["##task.realenddate##"]      = '';
            if (!is_null($task['plan_start_date'])) {
                $tmp['##task.planstartdate##']         = Html::convDateTime($task['plan_start_date']);
            }
            if (!is_null($task['plan_end_date'])) {
                $tmp['##task.planenddate##']           = Html::convDateTime($task['plan_end_date']);
            }
            if (!is_null($task['real_start_date'])) {
                $tmp['##task.realstartdate##']         = Html::convDateTime($task['real_start_date']);
            }
            if (!is_null($task['real_end_date'])) {
                $tmp['##task.realenddate##']           = Html::convDateTime($task['real_end_date']);
            }

            $this->data['tasks'][]                   = $tmp;
        }

        $this->data["##projecttask.numberoftasks##"] = count($this->data['tasks']);

        // History infos

        $this->data['log'] = [];
        // Use list_limit_max or load the full history ?
        foreach (Log::getHistoryData($item, 0, $CFG_GLPI['list_limit_max']) as $data) {
            $tmp                                = [];
            $tmp["##projecttask.log.date##"]    = $data['date_mod'];
            $tmp["##projecttask.log.user##"]    = $data['user_name'];
            $tmp["##projecttask.log.field##"]   = $data['field'];
            $tmp["##projecttask.log.content##"] = $data['change'];
            $this->data['log'][]               = $tmp;
        }

        $this->data["##projecttask.numberoflogs##"] = count($this->data['log']);

        // Tickets infos
        $tickets  = getAllDataFromTable('glpi_projecttasks_tickets', $restrict);

        $this->data['tickets'] = [];
        if (count($tickets)) {
            $ticket = new Ticket();
            foreach ($tickets as $data) {
                if ($ticket->getFromDB($data['tickets_id'])) {
                    $tmp                    = [];

                    $tmp['##ticket.id##']   = $data['tickets_id'];
                    $tmp['##ticket.date##'] = $ticket->getField('date');
                    $tmp['##ticket.title##']
                                       = $ticket->getField('name');
                    $tmp['##ticket.url##']  = $this->formatURL(
                        $options['additionnaloption']['usertype'],
                        "Ticket_" . $data['tickets_id']
                    );
                    $tmp['##ticket.content##']
                                      = $ticket->getField('content');

                    $this->data['tickets'][] = $tmp;
                }
            }
        }

        $this->data['##projecttask.numberoftickets##'] = count($this->data['tickets']);

        // Document
        $iterator = $DB->request([
            'SELECT'    => 'glpi_documents.*',
            'FROM'      => 'glpi_documents',
            'LEFT JOIN' => [
                'glpi_documents_items'  => [
                    'ON' => [
                        'glpi_documents_items'  => 'documents_id',
                        'glpi_documents'        => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                'glpi_documents_items.itemtype'  => 'ProjectTask',
                'glpi_documents_items.items_id'  => $item->fields['id'],
            ],
        ]);

        $this->data["documents"] = [];
        foreach ($iterator as $data) {
            $tmp                      = [];
            $tmp['##document.id##']   = $data['id'];
            $tmp['##document.name##'] = $data['name'];
            $tmp['##document.weblink##']
                                    = $data['link'];

            $tmp['##document.url##']  = $this->formatURL(
                $options['additionnaloption']['usertype'],
                "document_" . $data['id']
            );
            $downloadurl              = "/front/document.send.php?docid=" . $data['id'];

            $tmp['##document.downloadurl##']
                                    = $this->formatURL(
                                        $options['additionnaloption']['usertype'],
                                        $downloadurl
                                    );
            $tmp['##document.heading##'] = '';
            if ($data['documentcategories_id']) {
                $tmp['##document.heading##'] = Dropdown::getDropdownName('glpi_documentcategories', $data['documentcategories_id']);
            }

            $tmp['##document.filename##']
                                    = $data['filename'];

            $this->data['documents'][]     = $tmp;
        }

        $this->data["##projecttask.urldocument##"]
                     = $this->formatURL(
                         $options['additionnaloption']['usertype'],
                         "ProjectTask_" . $item->getField("id") . '_Document_Item$1'
                     );

        $this->data["##projecttask.numberofdocuments##"]
                     = count($this->data['documents']);

        $this->getTags();
        foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
            if (!isset($this->data[$tag])) {
                $this->data[$tag] = $values['label'];
            }
        }
    }


    public function getTags()
    {

        $tags_all = ['projecttask.url'                 => __('URL'),
            'projecttask.action'              => _n('Event', 'Events', 1),
            'projecttask.name'                => __('Name'),
            'projecttask.project'             => Project::getTypeName(1),
            'projecttask.description'         => __('Description'),
            'projecttask.comments'            => __('Comments'),
            'projecttask.creationdate'        => __('Creation date'),
            'projecttask.lastupdatedate'      => __('Last update'),
            'projecttask.planstartdate'       => __('Planned start date'),
            'projecttask.planenddate'         => __('Planned end date'),
            'projecttask.realstartdate'       => __('Real start date'),
            'projecttask.realenddate'         => __('Real end date'),
            'projecttask.father'              => __('Father'),
            'projecttask.createbyuser'        => __('Writer'),
            'projecttask.type'                => _n('Type', 'Types', 1),
            'projecttask.state'               => _x('item', 'State'),
            'projecttask.percent'             => __('Percent done'),
            'projecttask.plannedduration'     => __('Planned duration'),
            'projecttask.effectiveduration'   => __('Effective duration'),
            'projecttask.ticketsduration'     => __('Tickets duration'),
            'projecttask.totalduration'       => __('Total duration'),
            'projecttask.numberoftasks'       => _x('quantity', 'Number of tasks'),
            'projecttask.numberofteammembers' => _x('quantity', 'Number of team members'),
            'task.date'                       => __('Opening date'),
            'task.name'                       => __('Name'),
            'task.description'                => __('Description'),
            'task.comments'                   => __('Comments'),
            'task.creationdate'               => __('Creation date'),
            'task.lastupdatedate'             => __('Last update'),
            'task.type'                       => _n('Type', 'Types', 1),
            'task.state'                      => _x('item', 'State'),
            'task.percent'                    => __('Percent done'),
            'task.planstartdate'              => __('Planned start date'),
            'task.planenddate'                => __('Planned end date'),
            'task.realstartdate'              => __('Real start date'),
            'task.realenddate'                => __('Real end date'),
            'projecttask.numberoflogs'        => sprintf(
                __('%1$s: %2$s'),
                __('Historical'),
                _x(
                    'quantity',
                    'Number of items'
                )
            ),
            'projecttask.log.date'            => sprintf(
                __('%1$s: %2$s'),
                __('Historical'),
                _n('Date', 'Dates', 1)
            ),
            'projecttask.log.user'            => sprintf(
                __('%1$s: %2$s'),
                __('Historical'),
                User::getTypeName(1)
            ),
            'projecttask.log.field'           => sprintf(
                __('%1$s: %2$s'),
                __('Historical'),
                _n('Field', 'Fields', 1)
            ),
            'projecttask.log.content'         => sprintf(
                __('%1$s: %2$s'),
                __('Historical'),
                _x('name', 'Update')
            ),
            'projecttask.numberoftickets'     => _x('quantity', 'Number of tickets'),
            'projecttask.numberofdocuments'   => _x('quantity', 'Number of documents'),
        ];

        foreach ($tags_all as $tag => $label) {
            $this->addTagToList(['tag'   => $tag,
                'label' => $label,
                'value' => true,
            ]);
        }

        //Tags without lang
        $tags = ['ticket.id'               => sprintf(__('%1$s: %2$s'), Ticket::getTypeName(1), __('ID')),
            'ticket.date'             => sprintf(__('%1$s: %2$s'), Ticket::getTypeName(1), _n('Date', 'Dates', 1)),
            'ticket.url'              => sprintf(__('%1$s: %2$s'), Ticket::getTypeName(1), ('URL')),
            'ticket.title'            => sprintf(
                __('%1$s: %2$s'),
                Ticket::getTypeName(1),
                __('Title')
            ),
            'ticket.content'          => sprintf(
                __('%1$s: %2$s'),
                Ticket::getTypeName(1),
                __('Description')
            ),
            'projecttask.projecturl'  => sprintf(__('%1$s: %2$s'), Project::getTypeName(1), __('URL')),
            'document.url'            => sprintf(
                __('%1$s: %2$s'),
                Document::getTypeName(1),
                __('URL')
            ),
            'document.downloadurl'    => sprintf(
                __('%1$s: %2$s'),
                Document::getTypeName(1),
                __('Download URL')
            ),
            'document.heading'        => sprintf(
                __('%1$s: %2$s'),
                Document::getTypeName(1),
                __('Heading')
            ),
            'document.id'             => sprintf(__('%1$s: %2$s'), Document::getTypeName(1), __('ID')),
            'document.filename'       => sprintf(
                __('%1$s: %2$s'),
                Document::getTypeName(1),
                __('File')
            ),
            'document.weblink'        => sprintf(
                __('%1$s: %2$s'),
                Document::getTypeName(1),
                __('Web link')
            ),
            'document.name'           => sprintf(
                __('%1$s: %2$s'),
                Document::getTypeName(1),
                __('Name')
            ),
            'projecttask.urldocument' => sprintf(
                __('%1$s: %2$s'),
                Document::getTypeName(Session::getPluralNumber()),
                __('URL')
            ),
            'projecttask.entity'      => sprintf(
                __('%1$s (%2$s)'),
                Entity::getTypeName(1),
                __('Complete name')
            ),
            'projecttask.shortentity' => sprintf(
                __('%1$s (%2$s)'),
                Entity::getTypeName(1),
                __('Name')
            ),
            'teammember.name'        => sprintf(
                __('%1$s: %2$s'),
                _n('Team member', 'Team members', 1),
                __('Name')
            ),
            'teammember.itemtype'    => sprintf(
                __('%1$s: %2$s'),
                _n('Team member', 'Team members', 1),
                _n('Type', 'Types', 1)
            ),
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'   => $tag,
                'label' => $label,
                'value' => true,
                'lang'  => false,
            ]);
        }

        //Tags with just lang
        $tags = ['projecttask.entity'   => Entity::getTypeName(1),
            'projecttask.log'      => __('Historical'),
            'projecttask.tasks'    => _n('Task', 'Tasks', Session::getPluralNumber()),
            'projecttask.team'     => ProjectTeam::getTypeName(1),
            'projecttask.tickets'  => _n('Ticket', 'Tickets', Session::getPluralNumber()),
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'   => $tag,
                'label' => $label,
                'value' => false,
                'lang'  => true,
            ]);
        }

        //Foreach global tags
        $tags = ['log'         => __('Historical'),
            'tasks'       => _n('Task', 'Tasks', Session::getPluralNumber()),
            'tickets'     => _n('Ticket', 'Tickets', Session::getPluralNumber()),
            'teammembers' => _n('Team member', 'Team members', Session::getPluralNumber()),
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'     => $tag,
                'label'   => $label,
                'value'   => false,
                'foreach' => true,
            ]);
        }
        asort($this->tag_descriptions);
    }
}
