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

abstract class NotificationTargetCommonITILObject extends NotificationTarget
{
    public $private_profiles = [];

    /**
     * Keep track of profiles who have acces to the "central" interface
     * Will only be loaded if the source item's entity is using anonymisation
     */
    public $central_profiles = [];

    /**
     * @param $entity          (default '')
     * @param $event           (default '')
     * @param $object          (default null)
     * @param $options   array
     **/
    public function __construct($entity = '', $event = '', $object = null, $options = [])
    {

        parent::__construct($entity, $event, $object, $options);

        if (isset($options['followup_id'])) {
            $this->options['sendprivate'] = $options['is_private'];
        }

        if (isset($options['task_id'])) {
            $this->options['sendprivate'] = $options['is_private'];
        }

        if (isset($options['users_id'])) {
            $this->options['users_id'] = $options['users_id'];
        }
    }


    public function validateSendTo($event, array $infos, $notify_me = false, $emitter = null)
    {

        // Check global ones for notification to myself
        if (!parent::validateSendTo($event, $infos, $notify_me, $emitter)) {
            return false;
        }

        // Private object and no right to see private items : do not send
        if (
            $this->isPrivate()
            && (!isset($infos['additionnaloption']['show_private'])
              || !$infos['additionnaloption']['show_private'])
        ) {
            return false;
        }

        return true;
    }

    protected function canNotificationBeDisabled(string $event): bool
    {
        // Notifications on ITIL objects are relying on `use_notification` property of actors.
        return false;
    }

    /**
     * Get notification subject prefix
     *
     * @param $event Event name (default '')
     *
     * @return string
     **/
    public function getSubjectPrefix($event = '')
    {

        $perso_tag = trim(Entity::getUsedConfig(
            'notification_subject_tag',
            $this->getEntity(),
            '',
            ''
        ));

        if (empty($perso_tag)) {
            $perso_tag = 'GLPI';
        }
        return sprintf("[$perso_tag #%07d] ", $this->obj->getField('id'));
    }

    /**
     * Get events related to Itil Object
     *
     * @since 9.2
     *
     * @return array of events (event key => event label)
     **/
    public function getEvents()
    {

        $events = [
            'requester_user'    => __('New user in requesters'),
            'requester_group'   => __('New group in requesters'),
            'observer_user'     => __('New user in observers'),
            'observer_group'    => __('New group in observers'),
            'assign_user'       => __('New user in assignees'),
            'assign_group'      => __('New group in assignees'),
            'assign_supplier'   => __('New supplier in assignees'),
            'add_task'          => __('New task'),
            'update_task'       => __('Update of a task'),
            'delete_task'       => __('Deletion of a task'),
            'add_followup'      => __("New followup"),
            'update_followup'   => __('Update of a followup'),
            'delete_followup'   => __('Deletion of a followup'),
            'user_mention'      => __('User mentioned'),
            'auto_reminder'     => ITILReminder::getTypeName(1),
            'add_document'      => __('New document'),
            'pendingreason_add' => __('Pending reason added'),
            'pendingreason_del' => __('Pending reason removed'),
            'pendingreason_close' => __('Pending reason auto close'),
        ];

        asort($events);
        return $events;
    }


    /**
     * Add linked users to the notified users list
     *
     * @param integer $type type of linked users
     *
     * @return void
     */
    public function addLinkedUserByType($type)
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $userlinktable = getTableForItemType($this->obj->userlinkclass);
        $fkfield       = $this->obj->getForeignKeyField();

        //Look for the user by his id
        $criteria = ['LEFT JOIN' => [
            User::getTable() => [
                'ON' => [
                    $userlinktable    => 'users_id',
                    User::getTable()  => 'id',
                ],
            ],
        ],
        ] + $this->getDistinctUserCriteria() + $this->getProfileJoinCriteria();
        $criteria['FROM'] = $userlinktable;
        $criteria['FIELDS'] = array_merge(
            $criteria['FIELDS'],
            [
                "$userlinktable.use_notification AS notif",
                "$userlinktable.alternative_email AS altemail",
            ]
        );
        $criteria['WHERE']["$userlinktable.$fkfield"] = $this->obj->fields['id'];
        $criteria['WHERE']["$userlinktable.type"] = $type;

        $iterator = $DB->request($criteria);
        foreach ($iterator as $data) {
            //Add the user email and language in the notified users list
            if ($data['notif']) {
                $author_email = UserEmail::getDefaultForUser($data['users_id']);
                $author_lang  = $data["language"];
                $author_id    = $data['users_id'];

                if (
                    !empty($data['altemail'])
                    && ($data['altemail'] != $author_email)
                    && NotificationMailing::isUserAddressValid($data['altemail'])
                ) {
                    $author_email = $data['altemail'];
                }
                if (empty($author_lang)) {
                    $author_lang = $CFG_GLPI["language"];
                }
                if (empty($author_id)) {
                    $author_id = -1;
                }

                $user = [
                    'language' => $author_lang,
                    'users_id' => $author_id,
                ];
                if ($this->isMailMode()) {
                    $user['email'] = $author_email;
                }
                $this->addToRecipientsList($user);
            }
        }

        // Anonymous user
        $iterator = $DB->request([
            'SELECT' => 'alternative_email',
            'FROM'   => $userlinktable,
            'WHERE'  => [
                $fkfield             => $this->obj->fields['id'],
                'users_id'           => 0,
                'use_notification'   => 1,
                'type'               => $type,
            ],
        ]);
        foreach ($iterator as $data) {
            if ($this->isMailMode()) {
                if (NotificationMailing::isUserAddressValid($data['alternative_email'])) {
                    $this->addToRecipientsList([
                        'email'    => $data['alternative_email'],
                        'language' => $CFG_GLPI["language"],
                        'users_id' => -1,
                    ]);
                }
            }
        }
    }


    /**
     * Add linked group to the notified user list
     *
     * @param integer $type type of linked groups
     *
     * @return void
     */
    public function addLinkedGroupByType($type)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $grouplinktable = getTableForItemType($this->obj->grouplinkclass);
        $fkfield        = $this->obj->getForeignKeyField();

        //Look for the user by his id
        $iterator = $DB->request([
            'SELECT' => 'groups_id',
            'FROM'   => $grouplinktable,
            'WHERE'  => [
                $fkfield => $this->obj->fields['id'],
                'type'   => $type,
            ],
        ]);

        foreach ($iterator as $data) {
            //Add the group in the notified users list
            $this->addForGroup(0, $data['groups_id']);
        }
    }



    /**
     * Add linked group without supervisor to the notified user list
     *
     * @since 0.84.1
     *
     * @param integer $type type of linked groups
     *
     * @return void
     */
    public function addLinkedGroupWithoutSupervisorByType($type)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $grouplinktable = getTableForItemType($this->obj->grouplinkclass);
        $fkfield        = $this->obj->getForeignKeyField();

        $iterator = $DB->request([
            'SELECT' => 'groups_id',
            'FROM'   => $grouplinktable,
            'WHERE'  => [
                $fkfield => $this->obj->fields['id'],
                'type'   => $type,
            ],
        ]);

        foreach ($iterator as $data) {
            //Add the group in the notified users list
            $this->addForGroup(2, $data['groups_id']);
        }
    }


    /**
     * Add linked group supervisor to the notified user list
     *
     * @param integer $type type of linked groups
     *
     * @return void
     */
    public function addLinkedGroupSupervisorByType($type)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $grouplinktable = getTableForItemType($this->obj->grouplinkclass);
        $fkfield        = $this->obj->getForeignKeyField();

        $iterator = $DB->request([
            'SELECT' => 'groups_id',
            'FROM'   => $grouplinktable,
            'WHERE'  => [
                $fkfield => $this->obj->fields['id'],
                'type'   => $type,
            ],
        ]);

        foreach ($iterator as $data) {
            //Add the group in the notified users list
            $this->addForGroup(1, $data['groups_id']);
        }
    }


    /**
     * Get the email of the item's user : Overloaded manual address used
     **/
    public function addItemAuthor()
    {
        $this->addLinkedUserByType(CommonITILActor::REQUESTER);
    }


    /**
     * Add previous technician in charge (before reassign)
     *
     * @return void
     */
    public function addOldAssignTechnician()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (
            isset($this->options['_old_user'])
            && ($this->options['_old_user']['type'] == CommonITILActor::ASSIGN)
            && $this->options['_old_user']['use_notification']
        ) {
            $user = new User();
            $user->getFromDB($this->options['_old_user']['users_id']);

            $author_email = UserEmail::getDefaultForUser($user->fields['id']);
            $author_lang  = $user->fields["language"];
            $author_id    = $user->fields['id'];

            if (
                !empty($this->options['_old_user']['alternative_email'])
                && ($this->options['_old_user']['alternative_email'] != $author_email)
                && NotificationMailing::isUserAddressValid($this->options['_old_user']['alternative_email'])
            ) {
                $author_email = $this->options['_old_user']['alternative_email'];
            }
            if (empty($author_lang)) {
                $author_lang = $CFG_GLPI["language"];
            }
            if (empty($author_id)) {
                $author_id = -1;
            }

            $user = [
                'language' => $author_lang,
                'users_id' => $author_id,
            ];
            if ($this->isMailMode()) {
                $user['email'] = $author_email;
            }
            $this->addToRecipientsList($user);
        }
    }


    /**
     * Add recipient
     *
     * @return void
     */
    public function addRecipientAddress()
    {
        $this->addUserByField("users_id_recipient");
    }


    /**
     * Get supplier related to the ITIL object
     *
     * @param boolean $sendprivate (false by default)
     *
     * @return void
     */
    public function addSupplier($sendprivate = false)
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (
            !$sendprivate
            && $this->obj->countSuppliers(CommonITILActor::ASSIGN)
            && $this->isMailMode()
        ) {
            $supplierlinktable = getTableForItemType($this->obj->supplierlinkclass);
            $fkfield           = $this->obj->getForeignKeyField();

            $iterator = $DB->request([
                'SELECT'          => [
                    'glpi_suppliers.email AS email',
                    'glpi_suppliers.name AS name',
                ],
                'DISTINCT'        => true,
                'FROM'            => $supplierlinktable,
                'LEFT JOIN'       => [
                    'glpi_suppliers'  => [
                        'ON' => [
                            $supplierlinktable   => 'suppliers_id',
                            'glpi_suppliers'     => 'id',
                        ],
                    ],
                ],
                'WHERE'           => [
                    "$supplierlinktable.$fkfield" => $this->obj->getID(),
                ],
            ]);

            foreach ($iterator as $data) {
                $this->addToRecipientsList($data);
            }
        }
    }


    /**
     * Add approver related to the ITIL object validation
     *
     * @param $options array
     *
     * @return void
     */
    public function addValidationApprover($options = [])
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (isset($options['validation_id'])) {
            $validationtable = getTableForItemType($this->obj->getType() . 'Validation');

            $criteria = ['LEFT JOIN' => [
                User::getTable() => [
                    'ON' => [
                        $validationtable  => 'users_id_validate',
                        User::getTable()  => 'id',
                    ],
                ],
            ],
            ] + $this->getDistinctUserCriteria() + $this->getProfileJoinCriteria();
            $criteria['FROM'] = $validationtable;
            $criteria['WHERE']["$validationtable.id"] = $options['validation_id'];

            $iterator = $DB->request($criteria);
            foreach ($iterator as $data) {
                $this->addToRecipientsList($data);
            }
        }
    }

    /**
     * Add requester related to the ITIL object validation
     *
     * @param array $options Options
     *
     * @return void
     **/
    public function addValidationRequester($options = [])
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (isset($options['validation_id'])) {
            $validationtable = getTableForItemType($this->obj->getType() . 'Validation');

            $criteria = ['LEFT JOIN' => [
                User::getTable() => [
                    'ON' => [
                        $validationtable  => 'users_id',
                        User::getTable()  => 'id',
                    ],
                ],
            ],
            ] + $this->getDistinctUserCriteria() + $this->getProfileJoinCriteria();
            $criteria['FROM'] = $validationtable;
            $criteria['WHERE']["$validationtable.id"] = $options['validation_id'];

            $iterator = $DB->request($criteria);
            foreach ($iterator as $data) {
                $this->addToRecipientsList($data);
            }
        }
    }

    /**
     * Add all users and groups who were asked for an approval answer
     *
     * @param array $options Options
     * @return void
     */
    public function addValidationTarget($options = [])
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (isset($options['validation_id'])) {
            $validation = $this->obj->getValidationClassInstance();
            $validation->getFromDB($options['validation_id']);
            if ($validation->fields['itemtype_target'] === User::class) {
                $validationtable = $validation::getTable();

                $criteria = [
                    'LEFT JOIN' => [
                        User::getTable() => [
                            'ON' => [
                                $validationtable => 'items_id_target',
                                User::getTable() => 'id',
                            ],
                        ],
                    ],
                ] + $this->getDistinctUserCriteria() + $this->getProfileJoinCriteria();
                $criteria['FROM'] = $validationtable;
                $criteria['WHERE']["$validationtable.id"] = $options['validation_id'];

                $iterator = $DB->request($criteria);
                foreach ($iterator as $data) {
                    $this->addToRecipientsList($data);
                }
            } elseif ($validation->fields['itemtype_target'] === Group::class) {
                $this->addForGroup(0, $validation->fields['items_id_target']);
            }
        }
    }


    /**
     * Add author related to the followup
     *
     * @param array $options Options
     *
     * @return void
     */
    public function addFollowupAuthor($options = [])
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (isset($options['followup_id'])) {
            $followuptable = ITILFollowup::getTable();

            $criteria = array_merge_recursive(
                ['INNER JOIN' => [
                    User::getTable() => [
                        'ON' => [
                            $followuptable    => 'users_id',
                            User::getTable()  => 'id',
                        ],
                    ],
                ],
                ],
                $this->getDistinctUserCriteria() + $this->getProfileJoinCriteria()
            );
            $criteria['FROM'] = $followuptable;
            $criteria['WHERE']["$followuptable.id"] = $options['followup_id'];

            $iterator = $DB->request($criteria);
            foreach ($iterator as $data) {
                $this->addToRecipientsList($data);
            }
        }
    }


    /**
     * Add task author
     *
     * @param array $options Options
     *
     * @return void
     */
    public function addTaskAuthor($options = [])
    {
        /** @var \DBmysql $DB */
        global $DB;

        // In case of delete task pass user id
        if (isset($options['task_users_id'])) {
            $criteria = $this->getDistinctUserCriteria() + $this->getProfileJoinCriteria();
            $criteria['FROM'] = User::getTable();
            $criteria['WHERE'][User::getTable() . '.id'] = $options['task_users_id'];

            $iterator = $DB->request($criteria);
            foreach ($iterator as $data) {
                $this->addToRecipientsList($data);
            }
        } elseif (isset($options['task_id'])) {
            $tasktable = getTableForItemType($this->obj->getType() . 'Task');

            $criteria = array_merge_recursive(
                ['INNER JOIN' => [
                    User::getTable() => [
                        'ON' => [
                            $tasktable        => 'users_id',
                            User::getTable()  => 'id',
                        ],
                    ],
                ],
                ],
                $this->getDistinctUserCriteria() + $this->getProfileJoinCriteria()
            );
            $criteria['FROM'] = $tasktable;
            $criteria['WHERE']["$tasktable.id"] = $options['task_id'];

            $iterator = $DB->request($criteria);
            foreach ($iterator as $data) {
                $this->addToRecipientsList($data);
            }
        }
    }


    /**
     * Add user assigned to task
     *
     * @param array $options Options
     *
     * @return void
     */
    public function addTaskAssignUser($options = [])
    {
        /** @var \DBmysql $DB */
        global $DB;

        // In case of delete task pass user id
        if (isset($options['task_users_id_tech'])) {
            $criteria = $this->getDistinctUserCriteria() + $this->getProfileJoinCriteria();
            $criteria['FROM'] = User::getTable();
            $criteria['WHERE'][User::getTable() . '.id'] = $options['task_users_id_tech'];

            $iterator = $DB->request($criteria);
            foreach ($iterator as $data) {
                $this->addToRecipientsList($data);
            }
        } elseif (isset($options['task_id'])) {
            $tasktable = getTableForItemType($this->obj->getType() . 'Task');

            $criteria = array_merge_recursive(
                ['INNER JOIN' => [
                    User::getTable() => [
                        'ON' => [
                            $tasktable        => 'users_id_tech',
                            User::getTable()  => 'id',
                        ],
                    ],
                ],
                ],
                $this->getDistinctUserCriteria() + $this->getProfileJoinCriteria()
            );
            $criteria['FROM'] = $tasktable;
            $criteria['WHERE']["$tasktable.id"] = $options['task_id'];

            $iterator = $DB->request($criteria);
            foreach ($iterator as $data) {
                $this->addToRecipientsList($data);
            }
        }
    }


    /**
     * Add group assigned to the task
     *
     * @since 9.1
     *
     * @param array $options Options
     *
     * @return void
     */
    public function addTaskAssignGroup($options = [])
    {
        /** @var \DBmysql $DB */
        global $DB;

        // In case of delete task pass user id
        if (isset($options['task_groups_id_tech'])) {
            $this->addForGroup(0, $options['task_groups_id_tech']);
        } elseif (isset($options['task_id'])) {
            $tasktable = getTableForItemType($this->obj->getType() . 'Task');
            $iterator = $DB->request([
                'FROM'   => $tasktable,
                'INNER JOIN'   => [
                    'glpi_groups'  => [
                        'ON'  => [
                            'glpi_groups'  => 'id',
                            $tasktable     => 'groups_id_tech',
                        ],
                    ],
                ],
                'WHERE'        => ["$tasktable.id" => $options['task_id']],
            ]);
            foreach ($iterator as $data) {
                $this->addForGroup(0, $data['groups_id_tech']);
            }
        }
    }


    public function addAdditionnalInfosForTarget()
    {
        /** @var \DBmysql $DB */
        global $DB;

        $iterator = $DB->request([
            'SELECT' => ['profiles_id'],
            'FROM'   => 'glpi_profilerights',
            'WHERE'  => [
                'name'   => 'followup',
                'rights' => ['&', ITILFollowup::SEEPRIVATE],
            ],
        ]);

        foreach ($iterator as $data) {
            $this->private_profiles[$data['profiles_id']] = $data['profiles_id'];
        }

        $profiles_iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => Profile::getTable(),
            'WHERE'  => [
                'interface' => 'central',
            ],
        ]);

        foreach ($profiles_iterator as $profiles_data) {
            $this->central_profiles[$profiles_data['id']] = $profiles_data['id'];
        }
    }


    public function addAdditionnalUserInfo(array $data)
    {
        return [
            'show_private'    => $this->getShowPrivateInfo($data),
            'is_self_service' => $this->getIsSelfServiceInfo($data),
        ];
    }

    protected function getShowPrivateInfo(array $data)
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (!isset($data['users_id']) || count($this->private_profiles) === 0) {
            return false;
        }

        $result = $DB->request([
            'COUNT'  => 'cpt',
            'FROM'   => 'glpi_profiles_users',
            'WHERE'  => [
                'users_id'     => $data['users_id'],
                'profiles_id'  => $this->private_profiles,
            ] + getEntitiesRestrictCriteria('glpi_profiles_users', 'entities_id', $this->getEntity(), true),
        ])->current();

        if ($result['cpt']) {
            return true;
        }
        return false;
    }

    protected function getIsSelfServiceInfo(array $data)
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (!isset($data['users_id']) || count($this->central_profiles) === 0) {
            return true;
        }

        $result = $DB->request([
            'COUNT'  => 'cpt',
            'FROM'   => Profile_User::getTable(),
            'WHERE'  => [
                'users_id'     => $data['users_id'],
                'profiles_id'  => $this->central_profiles,
            ] + getEntitiesRestrictCriteria(Profile_User::getTable(), 'entities_id', $this->getEntity(), true),
        ])->current();

        if ($result['cpt']) {
            return false;
        }
        return true;
    }

    public function getProfileJoinCriteria()
    {
        $criteria = parent::getProfileJoinCriteria();

        if ($this->isPrivate()) {
            $criteria['INNER JOIN'][Profile::getTable()] = [
                'ON' => [
                    Profile::getTable()        => 'id',
                    Profile_User::getTable()   => 'profiles_id',
                ],
            ];
            $criteria['INNER JOIN'][ProfileRight::getTable()] = [
                'ON' => [
                    ProfileRight::getTable()   => 'profiles_id',
                    Profile::getTable()        => 'id',
                ],
            ];
            $criteria['WHERE'][ProfileRight::getTable() . '.name'] = 'followup';
            $criteria['WHERE'][ProfileRight::getTable() . '.rights'] = ['&', ITILFollowup::SEEPRIVATE];
            $criteria['WHERE'][Profile::getTable() . '.interface'] = 'central';
        }
        return $criteria;
    }



    public function isPrivate()
    {

        if (isset($this->options['sendprivate']) && ($this->options['sendprivate'] == 1)) {
            return true;
        }
        return false;
    }


    /**
     * Add additionnals targets for ITIL objects
     *
     * @param string $event specif event to get additional targets (default '')
     *
     * @return void
     **/
    public function addAdditionalTargets($event = '')
    {

        if ($event === 'user_mention') {
            $this->addTarget(Notification::MENTIONNED_USER, __('Mentioned user'));
            return; // Do not propose more targets
        }

        if ($event == 'update') {
            $this->addTarget(
                Notification::OLD_TECH_IN_CHARGE,
                __('Former technician in charge of the ticket')
            );
        }

        if ($event == 'satisfaction') {
            $this->addTarget(Notification::AUTHOR, _n('Requester', 'Requesters', 1));
            $this->addTarget(Notification::RECIPIENT, __('Writer'));
        } elseif ($event != 'alertnotclosed') {
            $this->addTarget(Notification::RECIPIENT, __('Writer'));
            $this->addTarget(Notification::SUPPLIER, Supplier::getTypeName(1));
            $this->addTarget(
                Notification::SUPERVISOR_ASSIGN_GROUP,
                __('Manager of the group in charge of the ticket')
            );
            $this->addTarget(
                Notification::ASSIGN_GROUP_WITHOUT_SUPERVISOR,
                __("Group in charge of the ticket except manager users")
            );
            $this->addTarget(Notification::SUPERVISOR_REQUESTER_GROUP, __('Requester group manager'));
            $this->addTarget(
                Notification::REQUESTER_GROUP_WITHOUT_SUPERVISOR,
                __("Requester group except manager users")
            );
            $this->addTarget(Notification::ASSIGN_TECH, __('Technician in charge of the ticket'));
            $this->addTarget(Notification::REQUESTER_GROUP, _n('Requester group', 'Requester groups', 1));
            $this->addTarget(Notification::AUTHOR, _n('Requester', 'Requesters', 1));
            $this->addTarget(Notification::ASSIGN_GROUP, __('Group in charge of the ticket'));
            $this->addTarget(Notification::OBSERVER_GROUP, _n('Observer group', 'Observer groups', 1));
            $this->addTarget(Notification::OBSERVER, _n('Observer', 'Observers', 1));
            $this->addTarget(Notification::SUPERVISOR_OBSERVER_GROUP, __('Observer group manager'));
            $this->addTarget(
                Notification::OBSERVER_GROUP_WITHOUT_SUPERVISOR,
                __("Observer group except manager users")
            );
        }

        if (($event == 'validation') || ($event == 'validation_answer') || ($event == 'validation_reminder')) {
            $this->addTarget(Notification::VALIDATION_REQUESTER, __('Approval requester'));
            $this->addTarget(Notification::VALIDATION_TARGET, __('Approval target'));
            $this->addTarget(Notification::VALIDATION_APPROVER, __('Approver'));
        }

        if (($event == 'update_task') || ($event == 'add_task') || ($event == 'delete_task')) {
            $this->addTarget(Notification::TASK_ASSIGN_TECH, __('Technician in charge of the task'));
            $this->addTarget(Notification::TASK_ASSIGN_GROUP, __('Group in charge of the task'));
            $this->addTarget(Notification::TASK_AUTHOR, __('Task author'));
        }

        if (
            ($event == 'update_followup')
            || ($event == 'add_followup')
            || ($event == 'delete_followup')
        ) {
            $this->addTarget(Notification::FOLLOWUP_AUTHOR, __('Followup author'));
        }
    }


    /**
     * Get specifics targets for ITIL objects
     *
     * @param array $data    Data
     * @param array $options Options
     *
     * @return void
     **/
    public function addSpecificTargets($data, $options)
    {

        //Look for all targets whose type is Notification::ITEM_USER
        switch ($data['type']) {
            case Notification::USER_TYPE:
                switch ($data['items_id']) {
                    case Notification::ASSIGN_TECH:
                        $this->addLinkedUserByType(CommonITILActor::ASSIGN);
                        break;

                        //Send to the supervisor of group in charge of the ITIL object
                    case Notification::SUPERVISOR_ASSIGN_GROUP:
                        $this->addLinkedGroupSupervisorByType(CommonITILActor::ASSIGN);
                        break;

                        //Notification to the group in charge of the ITIL object without supervisor
                    case Notification::ASSIGN_GROUP_WITHOUT_SUPERVISOR:
                        $this->addLinkedGroupWithoutSupervisorByType(CommonITILActor::ASSIGN);
                        break;

                        //Send to the user who's got the issue
                    case Notification::RECIPIENT:
                        $this->addRecipientAddress();
                        break;

                        //Send to the supervisor of the requester's group
                    case Notification::SUPERVISOR_REQUESTER_GROUP:
                        $this->addLinkedGroupSupervisorByType(CommonITILActor::REQUESTER);
                        break;

                        //Send to the technician previously in charge of the ITIL object (before reassignation)
                    case Notification::OLD_TECH_IN_CHARGE:
                        $this->addOldAssignTechnician();
                        break;

                        //Assign to a supplier
                    case Notification::SUPPLIER:
                        $this->addSupplier($this->isPrivate());
                        break;

                    case Notification::REQUESTER_GROUP:
                        $this->addLinkedGroupByType(CommonITILActor::REQUESTER);
                        break;

                        //Notification to the requester group without supervisor
                    case Notification::REQUESTER_GROUP_WITHOUT_SUPERVISOR:
                        $this->addLinkedGroupWithoutSupervisorByType(CommonITILActor::REQUESTER);
                        break;

                    case Notification::ASSIGN_GROUP:
                        $this->addLinkedGroupByType(CommonITILActor::ASSIGN);
                        break;

                    case Notification::VALIDATION_TARGET:
                        $this->addValidationTarget($options);
                        break;

                        //Send to the ITIL object validation approver
                    case Notification::VALIDATION_APPROVER:
                        $this->addValidationApprover($options);
                        break;

                        //Send to the ITIL object validation requester
                    case Notification::VALIDATION_REQUESTER:
                        $this->addValidationRequester($options);
                        break;

                        //Send to the ITIL object followup author
                    case Notification::FOLLOWUP_AUTHOR:
                        $this->addFollowupAuthor($options);
                        break;

                        //Send to the ITIL object followup author
                    case Notification::TASK_AUTHOR:
                        $this->addTaskAuthor($options);
                        break;

                        //Send to the ITIL object followup author
                    case Notification::TASK_ASSIGN_TECH:
                        $this->addTaskAssignUser($options);
                        break;

                        //Send to the ITIL object task group assigned
                    case Notification::TASK_ASSIGN_GROUP:
                        $this->addTaskAssignGroup($options);
                        break;

                        //Notification to the ITIL object's observer group
                    case Notification::OBSERVER_GROUP:
                        $this->addLinkedGroupByType(CommonITILActor::OBSERVER);
                        break;

                        //Notification to the ITIL object's observer user
                    case Notification::OBSERVER:
                        $this->addLinkedUserByType(CommonITILActor::OBSERVER);
                        break;

                        //Notification to the supervisor of the ITIL object's observer group
                    case Notification::SUPERVISOR_OBSERVER_GROUP:
                        $this->addLinkedGroupSupervisorByType(CommonITILActor::OBSERVER);
                        break;

                        //Notification to the observer group without supervisor
                    case Notification::OBSERVER_GROUP_WITHOUT_SUPERVISOR:
                        $this->addLinkedGroupWithoutSupervisorByType(CommonITILActor::OBSERVER);
                        break;

                    case Notification::MENTIONNED_USER:
                        $this->addMentionnedUser($options);
                        break;
                }
        }
    }

    /**
     * Add mentionned user to recipients.
     *
     * @param array $options
     *
     * @return void
     */
    protected function addMentionnedUser(array $options): void
    {
        $user = new User();
        if (array_key_exists('users_id', $options) && $user->getFromDB($options['users_id'])) {
            $this->addToRecipientsList(
                [
                    'language' => $user->fields['language'],
                    'users_id' => $user->fields['id'],
                ]
            );
        }
    }


    public function addDataForTemplate($event, $options = [])
    {
        $events    = $this->getAllEvents();
        $objettype = strtolower($this->obj->getType());

        // Get data from ITIL objects
        if ($event != 'alertnotclosed') {
            $this->data = $this->getDataForObject($this->obj, $options);
        } else {
            if (
                isset($options['entities_id'])
                && isset($options['items'])
            ) {
                $entity = new Entity();
                if ($entity->getFromDB($options['entities_id'])) {
                    $this->data["##$objettype.entity##"]      = $entity->getField('completename');
                    $this->data["##$objettype.shortentity##"] = $entity->getField('name');
                }
                if ($item = getItemForItemtype($objettype)) {
                    $objettypes = Toolbox::strtolower(getPlural($objettype));
                    $items      = [];
                    foreach ($options['items'] as $object) {
                        $item->getFromDB($object['id']);
                        $tmp = $this->getDataForObject($item, $options, true);
                        $this->data[$objettypes][] = $tmp;
                    }
                }
            }
        }

        if (
            ($event == 'validation')
            && isset($options['validation_status'])
        ) {
            $this->data["##$objettype.action##"]
                     //TRANS: %s id of the approval's state
                     = sprintf(
                         __('%1$s - %2$s'),
                         CommonITILValidation::getTypeName(1),
                         TicketValidation::getStatus($options['validation_status'])
                     );
        } else {
            $this->data["##$objettype.action##"] = $events[$event];
        }

        $this->getTags();

        foreach ($this->tag_descriptions[parent::TAG_LANGUAGE] as $tag => $values) {
            if (!isset($this->data[$tag])) {
                $this->data[$tag] = $values['label'];
            }
        }
    }


    /**
     * Get data from an item
     *
     * @param CommonITILObject  $item    Object instance
     * @param array             $options Options
     * @param boolean           $simple  (false by default)
     *
     * @return array
     **/
    public function getDataForObject(CommonITILObject $item, array $options, $simple = false)
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $is_self_service = $options['additionnaloption']['is_self_service'] ?? true;
        $are_names_anonymized = $is_self_service
            && Entity::getAnonymizeConfig($item->fields['entities_id']) !== Entity::ANONYMIZE_DISABLED;
        $objettype = strtolower($item->getType());

        $data["##$objettype.title##"]        = $item->getField('name');
        $data["##$objettype.content##"]      = $item->getField('content');
        $data["##$objettype.description##"]  = $item->getField('content');
        $data["##$objettype.id##"]           = sprintf("%07d", $item->getField("id"));

        $data["##$objettype.url##"]
                        = $this->formatURL(
                            $options['additionnaloption']['usertype'],
                            $objettype . "_" . $item->getField("id")
                        );

        $tab = '$1';
        $data["##$objettype.urlapprove##"]
                           = $this->formatURL(
                               $options['additionnaloption']['usertype'],
                               $objettype . "_" . $item->getField("id") . "_" .
                               $item->getType() . $tab
                           );

        $entity = new Entity();
        if ($entity->getFromDB($this->getEntity())) {
            $data["##$objettype.entity##"]          = $entity->getField('completename');
            $data["##$objettype.shortentity##"]     = $entity->getField('name');
            $data["##$objettype.entity.phone##"]    = $entity->getField('phonenumber');
            $data["##$objettype.entity.fax##"]      = $entity->getField('fax');
            $data["##$objettype.entity.website##"]  = $entity->getField('website');
            $data["##$objettype.entity.email##"]    = $entity->getField('email');
            $data["##$objettype.entity.address##"]  = $entity->getField('address');
            $data["##$objettype.entity.postcode##"] = $entity->getField('postcode');
            $data["##$objettype.entity.town##"]     = $entity->getField('town');
            $data["##$objettype.entity.state##"]    = $entity->getField('state');
            $data["##$objettype.entity.country##"]  = $entity->getField('country');
            $data["##$objettype.entity.registration_number##"] = $entity->getField('registration_number');
        }

        $data["##$objettype.storestatus##"]  = $item->getField('status');
        $data["##$objettype.status##"]       = $item->getStatus($item->getField('status'));

        $data["##$objettype.urgency##"]      = $item->getUrgencyName($item->getField('urgency'));
        $data["##$objettype.impact##"]       = $item->getImpactName($item->getField('impact'));
        $data["##$objettype.priority##"]     = $item->getPriorityName($item->getField('priority'));
        $data["##$objettype.time##"]         = $item->getActionTime($item->getField('actiontime'));

        $data["##$objettype.creationdate##"] = Html::convDateTime($item->getField('date'));
        $data["##$objettype.closedate##"]    = Html::convDateTime($item->getField('closedate'));
        $data["##$objettype.solvedate##"]    = Html::convDateTime($item->getField('solvedate'));
        $data["##$objettype.duedate##"]      = Html::convDateTime($item->getField('time_to_resolve'));

        $data["##$objettype.category##"] = '';
        if ($item->getField('itilcategories_id')) {
            $data["##$objettype.category##"]
                              = Dropdown::getDropdownName(
                                  'glpi_itilcategories',
                                  $item->getField('itilcategories_id')
                              );
        }
        $data['actors']                = [];

        $data["##$objettype.authors##"] = '';
        $data['authors']                = [];
        if ($item->countUsers(CommonITILActor::REQUESTER)) {
            $users = [];
            foreach ($item->getUsers(CommonITILActor::REQUESTER) as $tmpusr) {
                $uid = (int) $tmpusr['users_id'];
                $user_tmp = new User();
                if ($uid > 0 && $user_tmp->getFromDB($uid)) {
                    $users[] = $user_tmp->getName();

                    // Legacy authors data
                    $actor_data = self::getActorData($user_tmp, CommonITILActor::REQUESTER, 'author');
                    $actor_data['##author.title##'] = $actor_data['##author.usertitle##'];
                    $actor_data['##author.category##'] = $actor_data['##author.usercategory##'];
                    $data['authors'][] = $actor_data;

                    $data['actors'][]  = self::getActorData($user_tmp, CommonITILActor::REQUESTER, 'actor');
                } elseif ($uid === 0) {
                    // Anonymous users only in xxx.authors, not in authors
                    $users[] = $tmpusr['alternative_email'];

                    // Anonymous user in actors
                    $user_tmp->getEmpty();
                    $actor_data = self::getActorData($user_tmp, CommonITILActor::REQUESTER, 'actor');
                    $actor_data['##actor.name##'] = $tmpusr['alternative_email'];
                }
            }
            $data["##$objettype.authors##"] = implode(', ', $users);
        }

        $data["##$objettype.suppliers##"] = '';
        $data["##$objettype.assigntosupplier##"] = '';
        $data['suppliers'] = [];
        if ($item->countSuppliers(CommonITILActor::ASSIGN)) {
            $suppliers = [];
            foreach ($item->getSuppliers(CommonITILActor::ASSIGN) as $supplier_data) {
                $sid      = $supplier_data['suppliers_id'];
                $supplier = new Supplier();
                if ($sid > 0 && $supplier->getFromDB($sid)) {
                    $suppliers[] = $supplier->getName();

                    // Legacy suppliers data
                    $actor_data = self::getActorData($supplier, CommonITILActor::ASSIGN, 'supplier');
                    $actor_data['##supplier.type##'] = $actor_data['##supplier.suppliertype##'];
                    $data['suppliers'][] = $actor_data;

                    $data['actors'][]    = self::getActorData($supplier, CommonITILActor::ASSIGN, 'actor');
                }
            }
            $data["##$objettype.suppliers##"] = implode(', ', $suppliers);
            $data["##$objettype.assigntosupplier##"] = implode(', ', $suppliers);
        }

        $data["##$objettype.openbyuser##"] = '';
        if ($item->getField('users_id_recipient')) {
            $user_tmp = new User();
            $user_tmp->getFromDB($item->getField('users_id_recipient'));
            $data["##$objettype.openbyuser##"] = $user_tmp->getName();
        }

        $data["##$objettype.lastupdater##"] = '';
        if ($item->getField('users_id_lastupdater')) {
            $user_tmp = new User();
            $user_tmp->getFromDB($item->getField('users_id_lastupdater'));
            $data["##$objettype.lastupdater##"] = $user_tmp->getName();
        }

        $data["##$objettype.assigntousers##"] = '';
        if ($item->countUsers(CommonITILActor::ASSIGN)) {
            $users = [];
            foreach ($item->getUsers(CommonITILActor::ASSIGN) as $tmp) {
                $uid      = $tmp['users_id'];
                $user_tmp = new User();

                if ($user_tmp->getFromDB($uid)) {
                    // Check if the user need to be anonymized
                    if ($are_names_anonymized) {
                        $users[$uid] = User::getAnonymizedNameForUser($uid, $item->fields['entities_id']);
                    } else {
                        $users[$uid] = $user_tmp->getName();
                    }

                    $actor_data = self::getActorData($user_tmp, CommonITILActor::ASSIGN, 'actor');
                    $actor_data['##actor.name##'] = $users[$uid]; // Use anonymized name
                    $data['actors'][] = $actor_data;
                }
            }
            $data["##$objettype.assigntousers##"] = implode(', ', $users);
        }

        $data["##$objettype.groups##"] = '';
        if ($item->countGroups(CommonITILActor::REQUESTER)) {
            $groups = [];
            foreach ($item->getGroups(CommonITILActor::REQUESTER) as $group_data) {
                $gid = $group_data['groups_id'];

                $group = new Group();
                if ($gid > 0 && $group->getFromDB($gid)) {
                    $groups[$gid] = Dropdown::getDropdownName('glpi_groups', $gid);
                    $data['actors'][] = self::getActorData($group, CommonITILActor::REQUESTER, 'actor');
                }
            }
            $data["##$objettype.groups##"] = implode(', ', $groups);
        }

        $data["##$objettype.observergroups##"] = '';
        if ($item->countGroups(CommonITILActor::OBSERVER)) {
            $groups = [];
            foreach ($item->getGroups(CommonITILActor::OBSERVER) as $group_data) {
                $gid = $group_data['groups_id'];

                $group = new Group();
                if ($gid > 0 && $group->getFromDB($gid)) {
                    $groups[$gid] = Dropdown::getDropdownName('glpi_groups', $gid);
                    $data['actors'][] = self::getActorData($group, CommonITILActor::OBSERVER, 'actor');
                }
            }
            $data["##$objettype.observergroups##"] = implode(', ', $groups);
        }

        $data["##$objettype.observerusers##"] = '';
        if ($item->countUsers(CommonITILActor::OBSERVER)) {
            $users = [];
            foreach ($item->getUsers(CommonITILActor::OBSERVER) as $tmp) {
                $uid      = $tmp['users_id'];
                $user_tmp = new User();
                if (
                    $uid
                    && $user_tmp->getFromDB($uid)
                ) {
                    $users[] = $user_tmp->getName();

                    $data['actors'][] = self::getActorData($user_tmp, CommonITILActor::OBSERVER, 'actor');
                } else {
                    $users[] = $tmp['alternative_email'];
                }
            }
            $data["##$objettype.observerusers##"] = implode(', ', $users);
        }

        $data["##$objettype.assigntogroups##"] = '';
        if ($item->countGroups(CommonITILActor::ASSIGN)) {
            $groups = [];
            foreach ($item->getGroups(CommonITILActor::ASSIGN) as $group_data) {
                $gid = $group_data['groups_id'];

                $group = new Group();
                if ($gid > 0 && $group->getFromDB($gid)) {
                    $groups[$gid] = Dropdown::getDropdownName('glpi_groups', $gid);
                    $data['actors'][] = self::getActorData($group, CommonITILActor::ASSIGN, 'actor');
                }
            }
            $data["##$objettype.assigntogroups##"] = implode(', ', $groups);
        }

        $data["##$objettype.solution.type##"] = '';
        $data["##$objettype.solution.description##"] = '';
        $data["##$objettype.solution.author##"] = '';

        $itilsolution = new ITILSolution();
        $solution = $itilsolution->getFromDBByRequest([
            'WHERE'  => [
                'itemtype'  => $objettype,
                'items_id'  => $item->fields['id'],
            ],
            'ORDER'  => 'date_creation DESC',
            'LIMIT'  => 1,
        ]);

        if ($solution) {
            $data["##$objettype.solution.type##"] = '';
            if ($itilsolution->getField('solutiontypes_id')) {
                $data["##$objettype.solution.type##"] = Dropdown::getDropdownName(
                    'glpi_solutiontypes',
                    $itilsolution->getField('solutiontypes_id')
                );
            }

            $data["##$objettype.solution.author##"] = getUserName($itilsolution->getField('users_id'));
            $data["##$objettype.solution.description##"] = $itilsolution->getField('content');
        }

        $itilreminder = new ITILReminder();
        if (
            $itilreminder->getFromDBByRequest([
                'WHERE'  => [
                    'itemtype'  => $objettype,
                    'items_id'  => $item->fields['id'],
                ],
                'ORDER'  => 'date_creation DESC',
                'LIMIT'  => 1,
            ])
        ) {
            $pending_reason = $itilreminder->getPendingReason();
            $pending_reason_item = new PendingReason_Item();
            $followup_template = ITILFollowupTemplate::getById($pending_reason->fields['itilfollowuptemplates_id']);
            if (
                $pending_reason && $pending_reason_item->getFromDBByRequest([
                    'WHERE'  => [
                        'itemtype'  => $objettype,
                        'items_id'  => $item->fields['id'],
                        'pendingreasons_id' => $pending_reason->getID(),
                    ],
                    'ORDER'  => 'last_bump_date DESC',
                    'LIMIT'  => 1,
                ])
            ) {
                $data["##$objettype.reminder.bumpcounter##"]   = $pending_reason_item->getField('bump_count');
                $data["##$objettype.reminder.bumpremaining##"] = $pending_reason_item->getField('followups_before_resolution') - $pending_reason_item->getField('bump_count');
                $data["##$objettype.reminder.bumptotal##"]     = $pending_reason_item->getField('followups_before_resolution');
                $data["##$objettype.reminder.deadline##"]      = $pending_reason_item->getAutoResolvedate();
                $data["##$objettype.reminder.text##"]          = $followup_template !== false ? $followup_template->getRenderedContent($item) : '';
                $data["##$objettype.reminder.name##"]          = $pending_reason->getField('name');
            }
        }

        // Complex mode
        if (!$simple) {
            $linked = CommonITILObject_CommonITILObject::getAllLinkedTo($item->getType(), $item->getField('id'));
            $data['linkedtickets'] = [];
            $data['linkedchanges'] = [];
            $data['linkedproblems'] = [];

            foreach ($linked as $link) {
                $itemtype = $link['itemtype'];
                $link_item = getItemForItemtype($itemtype);
                if ($link_item->getFromDB($link['items_id'])) {
                    $tmp = [];
                    $tmp['##linked' . strtolower($itemtype) . '.id##'] = $link['items_id'];
                    $tmp['##linked' . strtolower($itemtype) . '.link##'] = CommonITILObject_CommonITILObject::getLinkName($link['link']);
                    $tmp['##linked' . strtolower($itemtype) . '.url##'] = $this->formatURL(
                        $options['additionnaloption']['usertype'],
                        strtolower($itemtype) . "_" . $link['items_id']
                    );

                    $tmp['##linked' . strtolower($itemtype) . '.title##'] = $link_item->getField('name');
                    $tmp['##linked' . strtolower($itemtype) . '.content##'] = $link_item->getField('content');

                    switch ($itemtype) {
                        case 'Ticket':
                            $data['linkedtickets'][] = $tmp;
                            break;
                        case 'Change':
                            $data['linkedchanges'][] = $tmp;
                            break;
                        case 'Problem':
                            $data['linkedproblems'][] = $tmp;
                            break;
                    }
                }
            }

            $data['##ticket.numberoflinkedtickets##'] = count($data['linkedtickets']);
            $data['##ticket.numberoflinkedchanges##'] = count($data['linkedchanges']);
            $data['##ticket.numberoflinkedproblems##'] = count($data['linkedproblems']);

            $show_private = $options['additionnaloption']['show_private'] ?? false;
            $followup_restrict = [];
            $followup_restrict['items_id'] = $item->getField('id');
            if (!$show_private) {
                $followup_restrict['is_private'] = 0;
            }
            $followup_restrict['itemtype'] = $objettype;

            //Followup infos
            $followups          = getAllDataFromTable(
                'glpi_itilfollowups',
                [
                    'WHERE'  => $followup_restrict,
                    'ORDER'  => ['date_mod DESC', 'id DESC'],
                ]
            );
            $data['followups'] = [];
            foreach ($followups as $followup) {
                $tmp                             = [];
                $tmp['##followup.isprivate##']   = Dropdown::getYesNo($followup['is_private']);

                // Check if the author need to be anonymized
                if ($are_names_anonymized && ITILFollowup::getById($followup['id'])->isFromSupportAgent()) {
                    $tmp['##followup.author##'] = User::getAnonymizedNameForUser(
                        $followup['users_id'],
                        $item->fields['entities_id']
                    );
                } else {
                    $tmp['##followup.author##'] = getUserName($followup['users_id']);
                }

                $user_tmp = new User();
                if ($user_tmp->getFromDB($followup['users_id'])) {
                    $tmp = array_merge($tmp, self::getActorData($user_tmp, 0, 'followup.author'));
                }

                $tmp['##followup.requesttype##'] = '';
                if ($followup['requesttypes_id']) {
                    $tmp['##followup.requesttype##'] = Dropdown::getDropdownName(
                        'glpi_requesttypes',
                        $followup['requesttypes_id']
                    );
                }
                $tmp['##followup.date##']        = Html::convDateTime($followup['date']);
                $tmp['##followup.description##'] = $followup['content'];

                $data['followups'][] = $tmp;
            }

            $data["##$objettype.numberoffollowups##"] = count($data['followups']);

            $data['log'] = [];
            // Use list_limit_max or load the full history ?
            $log_data = Log::getHistoryData($item, 0, $CFG_GLPI['list_limit_max']);
            foreach ($log_data as $log) {
                $tmp                               = [];
                $tmp["##$objettype.log.date##"]    = Html::convDateTime($log['date_mod']);
                $tmp["##$objettype.log.user##"]    = $log['user_name'];
                $tmp["##$objettype.log.field##"]   = $log['field'];
                $tmp["##$objettype.log.content##"] = $log['change'];
                $data['log'][]                    = $tmp;
            }

            $data["##$objettype.numberoflogs##"] = count($data['log']);

            // Get unresolved items
            $restrict = [
                'NOT' => [
                    $item->getTable() . '.status' => array_merge(
                        $item->getSolvedStatusArray(),
                        $item->getClosedStatusArray()
                    ),
                ],
            ];

            if ($item->maybeDeleted()) {
                $restrict[$item->getTable() . '.is_deleted'] = 0;
            }

            $data["##$objettype.numberofunresolved##"]
               = countElementsInTableForEntity($item->getTable(), $this->getEntity(), $restrict, false);

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
                    $item->getAssociatedDocumentsCriteria(true),
                    'timeline_position' => ['>', CommonITILObject::NO_TIMELINE], // skip inlined images
                ],
            ]);

            $data["documents"] = [];
            $addtodownloadurl   = '';
            if ($item->getType() == 'Ticket') {
                $addtodownloadurl = "&tickets_id=" . $item->fields['id'];
            }
            foreach ($iterator as $row) {
                $tmp                      = [];
                $tmp['##document.id##']   = $row['id'];
                $tmp['##document.name##'] = $row['name'];
                $tmp['##document.weblink##']
                                       = $row['link'];

                $tmp['##document.url##']  = $this->formatURL(
                    $options['additionnaloption']['usertype'],
                    "document_" . $row['id']
                );
                $downloadurl              = "/front/document.send.php?docid=" . $row['id'];

                $tmp['##document.downloadurl##']
                                       = $this->formatURL(
                                           $options['additionnaloption']['usertype'],
                                           $downloadurl . $addtodownloadurl
                                       );

                $tmp['##document.heading##'] = '';
                if ($row['documentcategories_id']) {
                    $tmp['##document.heading##']
                                         = Dropdown::getDropdownName(
                                             'glpi_documentcategories',
                                             $row['documentcategories_id']
                                         );
                }

                $tmp['##document.filename##']
                                       = $row['filename'];

                $data['documents'][]     = $tmp;
            }

            $data["##$objettype.urldocument##"]
                        = $this->formatURL(
                            $options['additionnaloption']['usertype'],
                            $objettype . "_" . $item->getField("id") . '_Document_Item$1'
                        );

            $data["##$objettype.numberofdocuments##"]
                        = count($data['documents']);

            //costs infos
            $costtype = $item->getType() . 'Cost';
            $costs    = $costtype::getCostsSummary($costtype, $item->getField("id"));

            $data["##$objettype.costfixed##"]    = $costs['costfixed'];
            $data["##$objettype.costmaterial##"] = $costs['costmaterial'];
            $data["##$objettype.costtime##"]     = $costs['costtime'];
            $data["##$objettype.totalcost##"]    = $costs['totalcost'];

            $costs          = getAllDataFromTable(
                getTableForItemType($costtype),
                [
                    'WHERE'  => [$item->getForeignKeyField() => $item->getField('id')],
                    'ORDER'  => ['begin_date DESC', 'id ASC'],
                ]
            );
            $data['costs'] = [];
            foreach ($costs as $cost) {
                $tmp = [];
                $tmp['##cost.name##']         = $cost['name'];
                $tmp['##cost.comment##']      = $cost['comment'];
                $tmp['##cost.datebegin##']    = Html::convDate($cost['begin_date']);
                $tmp['##cost.dateend##']      = Html::convDate($cost['end_date']);
                $tmp['##cost.time##']         = $item->getActionTime($cost['actiontime']);
                $tmp['##cost.costtime##']     = Html::formatNumber($cost['cost_time']);
                $tmp['##cost.costfixed##']    = Html::formatNumber($cost['cost_fixed']);
                $tmp['##cost.costmaterial##'] = Html::formatNumber($cost['cost_material']);
                $tmp['##cost.totalcost##']    = CommonITILCost::computeTotalCost(
                    $cost['actiontime'],
                    $cost['cost_time'],
                    $cost['cost_fixed'],
                    $cost['cost_material']
                );
                $tmp['##cost.budget##']       = '';
                if ($cost['budgets_id']) {
                    $tmp['##cost.budget##'] = Dropdown::getDropdownName('glpi_budgets', $cost['budgets_id']);
                }
                $data['costs'][]             = $tmp;
            }
            $data["##$objettype.numberofcosts##"] = count($data['costs']);

            //Task infos
            $taskobj = $item->getTaskClassInstance();
            $restrict = [$item->getForeignKeyField() => $item->getField('id')];
            if (
                $taskobj->maybePrivate()
                && (!isset($options['additionnaloption']['show_private'])
                || !$options['additionnaloption']['show_private'])
            ) {
                $restrict['is_private'] = 0;
            }

            $tasks          = getAllDataFromTable(
                $taskobj->getTable(),
                [
                    'WHERE'  => $restrict,
                    'ORDER'  => ['date_mod DESC', 'id ASC'],
                ]
            );
            $data['tasks'] = [];
            foreach ($tasks as $task) {
                $tmp                          = [];
                $tmp['##task.id##']           = $task['id'];
                if ($taskobj->maybePrivate()) {
                    $tmp['##task.isprivate##'] = Dropdown::getYesNo($task['is_private']);
                }
                $tmp['##task.author##']       = getUserName($task['users_id']);

                $tmp['##task.categoryid##']      = $task['taskcategories_id'];
                $tmp['##task.category##']        = Dropdown::getDropdownName(
                    'glpi_taskcategories',
                    $task['taskcategories_id'],
                );
                $tmp['##task.categorycomment##'] = Dropdown::getDropdownComments(
                    'glpi_taskcategories',
                    $task['taskcategories_id'],
                    tooltip: false
                );

                $tmp['##task.date##']         = Html::convDateTime($task['date']);
                $tmp['##task.description##']  = $task['content'];
                $tmp['##task.time##']         = Ticket::getActionTime($task['actiontime']);
                $tmp['##task.status##']       = Planning::getState($task['state']);

                $tmp['##task.user##']         = getUserName($task['users_id_tech']);
                $tmp['##task.group##'] = '';
                if ($task['groups_id_tech']) {
                    $tmp['##task.group##'] = Dropdown::getDropdownName("glpi_groups", $task['groups_id_tech']);
                }
                $tmp['##task.begin##']        = "";
                $tmp['##task.end##']          = "";
                if (!is_null($task['begin'])) {
                    $tmp['##task.begin##']     = Html::convDateTime($task['begin']);
                    $tmp['##task.end##']       = Html::convDateTime($task['end']);
                }

                $data['tasks'][]             = $tmp;
            }

            $data["##$objettype.numberoftasks##"] = count($data['tasks']);

            $data['timelineitems'] = [];


            $timeline = $item->getTimelineItems(
                [
                    'with_documents'     => false,
                    'with_logs'          => false,
                    'with_validations'   => false,
                    'sort_by_date_desc'  => true,

                    'check_view_rights'  => false,
                    'hide_private_items' => $is_self_service || !$show_private,
                ]
            );

            foreach ($timeline as $timeline_data) {
                $tmptimelineitem = [];

                $tmptimelineitem['##timelineitems.type##']        = $timeline_data['type']::getType();
                $tmptimelineitem['##timelineitems.typename##']    = $tmptimelineitem['##timelineitems.type##']::getTypeName(0);
                $tmptimelineitem['##timelineitems.date##']        = Html::convDateTime($timeline_data['item']['date']);
                $tmptimelineitem['##timelineitems.description##'] = $timeline_data['item']['content'];
                $tmptimelineitem['##timelineitems.position##']    = $this->getUserPositionFromTimelineItemPosition($timeline_data['item']['timeline_position']);

                $item_users_id = (int) $timeline_data['item']['users_id'];

                // Check if the author need to be anonymized
                if (
                    $item_users_id > 0
                    && $timeline_data['type'] == ITILFollowup::getType()
                    && $are_names_anonymized
                    && ITILFollowup::getById($timeline_data['item']['id'])->isFromSupportAgent()
                ) {
                    $tmptimelineitem['##timelineitems.author##'] = User::getAnonymizedNameForUser(
                        $item_users_id,
                        $item->fields['entities_id']
                    );
                } elseif ($item_users_id > 0) {
                    $tmptimelineitem['##timelineitems.author##'] = getUserName($item_users_id);
                } else {
                    $tmptimelineitem['##timelineitems.author##'] = '';
                }
                $data['timelineitems'][] = $tmptimelineitem;
            }

            /** @var CommonITILObject $item */
            $inquest = $item::getSatisfactionClassInstance();
            if ($inquest !== null) {
                $data['##satisfaction.type##'] = '';
                $data['##satisfaction.datebegin##'] = '';
                $data['##satisfaction.dateanswered##'] = '';
                $data['##satisfaction.satisfaction##'] = '';
                $data['##satisfaction.description##'] = '';

                if ($inquest->getFromDB($item->getField('id'))) {
                    // internal inquest
                    if ($inquest->fields['type'] == 1) {
                        $user_type = $options['additionnaloption']['usertype'];
                        $redirect = "{$objettype}_" . $item->getField("id") . '_' . $item::getType() . '$3';
                        $data["##{$objettype}.urlsatisfaction##"] = $this->formatURL($user_type, $redirect);
                    } elseif ($inquest->fields['type'] == 2) { // external inquest
                        $data["##{$objettype}.urlsatisfaction##"] = Entity::generateLinkSatisfaction($item);
                    }

                    $data['##satisfaction.type##']
                        = $inquest->getTypeInquestName($inquest->getfield('type'));
                    $data['##satisfaction.datebegin##']
                        = Html::convDateTime($inquest->fields['date_begin']);
                    $data['##satisfaction.dateanswered##']
                        = Html::convDateTime($inquest->fields['date_answered']);
                    $data['##satisfaction.satisfaction##']
                        = $inquest->fields['satisfaction'];
                    $data['##satisfaction.description##']
                        = $inquest->fields['comment'];
                }
            }
        }
        return $data;
    }

    protected function getActorData(CommonDBTM $actor, int $actortype, string $key_prefix): array
    {
        $data = [
            sprintf('##%s.itemtype##', $key_prefix)     => get_class($actor),
            sprintf('##%s.actortype##', $key_prefix)    => $actortype,
            sprintf('##%s.id##', $key_prefix)           => $actor->getID(),
            sprintf('##%s.name##', $key_prefix)         => '',
            sprintf('##%s.comments##', $key_prefix)     => $actor->getField('comment'),
            sprintf('##%s.location##', $key_prefix)     => '',
            sprintf('##%s.usertitle##', $key_prefix)    => '',
            sprintf('##%s.usercategory##', $key_prefix) => '',
            sprintf('##%s.email##', $key_prefix)        => '',
            sprintf('##%s.mobile##', $key_prefix)       => '',
            sprintf('##%s.phone##', $key_prefix)        => '',
            sprintf('##%s.phone2##', $key_prefix)       => '',
            sprintf('##%s.fax##', $key_prefix)          => '',
            sprintf('##%s.website##', $key_prefix)      => '',
            sprintf('##%s.address##', $key_prefix)      => '',
            sprintf('##%s.postcode##', $key_prefix)     => '',
            sprintf('##%s.town##', $key_prefix)         => '',
            sprintf('##%s.state##', $key_prefix)        => '',
            sprintf('##%s.country##', $key_prefix)      => '',
            sprintf('##%s.suppliertype##', $key_prefix) => '',
        ];
        switch (get_class($actor)) {
            case User::class:
                $data[sprintf('##%s.name##', $key_prefix)] = $actor->getName();

                $location = new Location();
                if ($actor->fields['locations_id'] > 0 && $location->getFromDB($actor->fields['locations_id'])) {
                    $data[sprintf('##%s.location##', $key_prefix)] = Dropdown::getDropdownName(
                        'glpi_locations',
                        $actor->fields['locations_id']
                    );
                    $data[sprintf('##%s.address##', $key_prefix)]  = $location->getField('address');
                    $data[sprintf('##%s.postcode##', $key_prefix)] = $location->getField('postcode');
                    $data[sprintf('##%s.town##', $key_prefix)]     = $location->getField('town');
                }

                if ($actor->fields['usertitles_id'] > 0) {
                    $data[sprintf('##%s.usertitle##', $key_prefix)] = Dropdown::getDropdownName(
                        'glpi_usertitles',
                        $actor->fields['usertitles_id']
                    );
                }

                if ($actor->fields['usercategories_id'] > 0) {
                    $data[sprintf('##%s.usercategory##', $key_prefix)] = Dropdown::getDropdownName(
                        'glpi_usercategories',
                        $actor->fields['usercategories_id']
                    );
                }

                $data[sprintf('##%s.email##', $key_prefix)]      = $actor->getDefaultEmail();
                $data[sprintf('##%s.mobile##', $key_prefix)]     = $actor->getField('mobile');
                $data[sprintf('##%s.phone##', $key_prefix)]      = $actor->getField('phone');
                $data[sprintf('##%s.phone2##', $key_prefix)]     = $actor->getField('phone2');
                break;
            case Group::class:
                $data[sprintf('##%s.name##', $key_prefix)]       = Dropdown::getDropdownName('glpi_groups', $actor->getID());
                break;
            case Supplier::class:
                $data[sprintf('##%s.name##', $key_prefix)]       = $actor->getName();
                $data[sprintf('##%s.email##', $key_prefix)]      = $actor->getField('email');
                $data[sprintf('##%s.phone##', $key_prefix)]      = $actor->getField('phonenumber');
                $data[sprintf('##%s.fax##', $key_prefix)]        = $actor->getField('fax');
                $data[sprintf('##%s.website##', $key_prefix)]    = $actor->getField('website');
                $data[sprintf('##%s.address##', $key_prefix)]    = $actor->getField('address');
                $data[sprintf('##%s.postcode##', $key_prefix)]   = $actor->getField('postcode');
                $data[sprintf('##%s.town##', $key_prefix)]       = $actor->getField('town');
                $data[sprintf('##%s.state##', $key_prefix)]      = $actor->getField('state');
                $data[sprintf('##%s.country##', $key_prefix)]    = $actor->getField('country');
                if ($actor->getField('suppliertypes_id')) {
                    $data[sprintf('##%s.type##', $key_prefix)]
                               = Dropdown::getDropdownName(
                                   'glpi_suppliertypes',
                                   $actor->getField('suppliertypes_id')
                               );
                    $data[sprintf('##%s.suppliertype##', $key_prefix)]
                               = Dropdown::getDropdownName(
                                   'glpi_suppliertypes',
                                   $actor->getField('suppliertypes_id')
                               );
                }
                break;
        }
        return $data;
    }

    public function getTags()
    {

        $itemtype  = $this->obj->getType();
        $objettype = strtolower($itemtype);

        //Locales
        $tags = [$objettype . '.id'                    => __('ID'),
            $objettype . '.title'                 => __('Title'),
            $objettype . '.url'                   => __('URL'),
            $objettype . '.category'              => _n('Category', 'Categories', 1),
            $objettype . '.content'               => __('Description'),
            $objettype . '.description'           => sprintf(
                __('%1$s: %2$s'),
                $this->obj->getTypeName(1),
                __('Description')
            ),
            $objettype . '.status'                => __('Status'),
            $objettype . '.urgency'               => __('Urgency'),
            $objettype . '.impact'                => __('Impact'),
            $objettype . '.priority'              => __('Priority'),
            $objettype . '.time'                  => __('Total duration'),
            $objettype . '.creationdate'          => __('Opening date'),
            $objettype . '.closedate'             => __('Closing date'),
            $objettype . '.solvedate'             => __('Date of solving'),
            $objettype . '.duedate'               => __('Time to resolve'),
            $objettype . '.authors'               => _n('Requester', 'Requesters', Session::getPluralNumber()),
            'author.id'                         => __('Requester ID'),
            'author.name'                       => _n('Requester', 'Requesters', 1),
            'author.location'                   => __('Requester location'),
            'author.mobile'                     => __('Mobile phone'),
            'author.phone'                      => Phone::getTypeName(1),
            'author.phone2'                     => __('Phone 2'),
            'author.email'                      => _n('Email', 'Emails', 1),
            'author.title'                      => _x('person', 'Title'),
            'author.category'                   => _n('Category', 'Categories', 1),
            $objettype . '.suppliers'             => _n('Supplier', 'Suppliers', Session::getPluralNumber()),
            'supplier.id'                       => __('Supplier ID'),
            'supplier.name'                     => Supplier::getTypeName(1),
            'supplier.phone'                    => Phone::getTypeName(1),
            'supplier.fax'                      => __('Fax'),
            'supplier.website'                  => __('Website'),
            'supplier.email'                    => _n('Email', 'Emails', 1),
            'supplier.address'                  => __('Address'),
            'supplier.postcode'                 => __('Postal code'),
            'supplier.town'                     => __('City'),
            'supplier.state'                    => _x('location', 'State'),
            'supplier.country'                  => __('Country'),
            'supplier.comments'                 => _n('Comment', 'Comments', Session::getPluralNumber()),
            'supplier.type'                     => SupplierType::getTypeName(1),
            $objettype . '.openbyuser'            => __('Writer'),
            $objettype . '.lastupdater'           => __('Last updater'),
            $objettype . '.assigntousers'         => __('Assigned to technicians'),
            'actor.itemtype'     => __('Internal type'),
            'actor.actortype'    => __('Actor type'),
            'actor.id'           => __('ID'),
            'actor.name'         => __('Name'),
            'actor.location'     => __('User location'),
            'actor.usertitle'    => _x('person', 'Title'),
            'actor.usercategory' => _n('Category', 'Categories', 1),
            'actor.email'        => _n('Email', 'Emails', 1),
            'actor.mobile'       => __('Mobile phone'),
            'actor.phone'        => Phone::getTypeName(1),
            'actor.phone2'       => __('Phone 2'),
            'actor.fax'          => __('Fax'),
            'actor.website'      => __('Website'),
            'actor.address'      => __('Address'),
            'actor.postcode'     => __('Postal code'),
            'actor.town'         => __('City'),
            'actor.state'        => _x('location', 'State'),
            'actor.country'      => __('Country'),
            'actor.comments'     => _n('Comment', 'Comments', Session::getPluralNumber()),
            'actor.suppliertype' => SupplierType::getTypeName(1),
            $objettype . '.assigntosupplier'      => __('Assigned to a supplier'),
            $objettype . '.groups'                => _n(
                'Requester group',
                'Requester groups',
                Session::getPluralNumber()
            ),
            $objettype . '.observergroups'        => _n('Observer group', 'Observer groups', Session::getPluralNumber()),
            $objettype . '.assigntogroups'        => __('Assigned to groups'),
            $objettype . '.solution.type'         => SolutionType::getTypeName(1),
            $objettype . '.solution.description'  => ITILSolution::getTypeName(1),
            $objettype . '.solution.author'       => __('Writer'),
            $objettype . '.observerusers'         => _n('Observer', 'Observers', Session::getPluralNumber()),
            $objettype . '.action'                => _n('Event', 'Events', 1),
            'followup.date'                     => __('Opening date'),
            'followup.isprivate'                => __('Private'),
            'followup.author'                   => __('Writer'),
            'followup.author.itemtype'          => __('Internal type'),
            'followup.author.actortype'         => __('Actor type'),
            'followup.author.id'                => __('ID'),
            'followup.author.name'              => __('Name'),
            'followup.author.location'          => __('User location'),
            'followup.author.usertitle'         => _x('person', 'Title'),
            'followup.author.usercategory'      => _n('Category', 'Categories', 1),
            'followup.author.email'             => _n('Email', 'Emails', 1),
            'followup.author.mobile'            => __('Mobile phone'),
            'followup.author.phone'             => Phone::getTypeName(1),
            'followup.author.phone2'            => __('Phone 2'),
            'followup.author.fax'               => __('Fax'),
            'followup.author.website'           => __('Website'),
            'followup.author.address'           => __('Address'),
            'followup.author.postcode'          => __('Postal code'),
            'followup.author.town'              => __('City'),
            'followup.author.state'             => _x('location', 'State'),
            'followup.author.country'           => __('Country'),
            'followup.author.comments'          => _n('Comment', 'Comments', Session::getPluralNumber()),
            'followup.author.suppliertype'      => SupplierType::getTypeName(1),
            'followup.description'              => __('Description'),
            'followup.requesttype'              => RequestType::getTypeName(1),
            $objettype . '.numberoffollowups'     => _x('quantity', 'Number of followups'),
            $objettype . '.numberofunresolved'    => __('Number of unresolved items'),
            $objettype . '.numberofdocuments'     => _x('quantity', 'Number of documents'),
            $objettype . '.costtime'              => __('Time cost'),
            $objettype . '.costfixed'             => __('Fixed cost'),
            $objettype . '.costmaterial'          => __('Material cost'),
            $objettype . '.totalcost'             => __('Total cost'),
            $objettype . '.numberofcosts'         => __('Number of costs'),
            'cost.name'                         => sprintf(
                __('%1$s: %2$s'),
                _n('Cost', 'Costs', 1),
                __('Name')
            ),
            'cost.comment'                      => sprintf(
                __('%1$s: %2$s'),
                _n('Cost', 'Costs', 1),
                _n('Comment', 'Comments', Session::getPluralNumber())
            ),
            'cost.datebegin'                    => sprintf(
                __('%1$s: %2$s'),
                _n('Cost', 'Costs', 1),
                __('Begin date')
            ),
            'cost.dateend'                      => sprintf(
                __('%1$s: %2$s'),
                _n('Cost', 'Costs', 1),
                __('End date')
            ),
            'cost.time'                         => sprintf(
                __('%1$s: %2$s'),
                _n('Cost', 'Costs', 1),
                __('Duration')
            ),
            'cost.costtime'                     => sprintf(
                __('%1$s: %2$s'),
                _n('Cost', 'Costs', 1),
                __('Time cost')
            ),
            'cost.costfixed'                    => sprintf(
                __('%1$s: %2$s'),
                _n('Cost', 'Costs', 1),
                __('Fixed cost')
            ),
            'cost.costmaterial'                 => sprintf(
                __('%1$s: %2$s'),
                _n('Cost', 'Costs', 1),
                __('Material cost')
            ),
            'cost.totalcost'                    => sprintf(
                __('%1$s: %2$s'),
                _n('Cost', 'Costs', 1),
                __('Total cost')
            ),
            'cost.budget'                       => sprintf(
                __('%1$s: %2$s'),
                _n('Cost', 'Costs', 1),
                Budget::getTypeName(1)
            ),
            'task.author'                       => __('Writer'),
            'task.isprivate'                    => __('Private'),
            'task.date'                         => __('Opening date'),
            'task.description'                  => __('Description'),
            'task.categoryid'                   => __('Category id'),
            'task.category'                     => _n('Category', 'Categories', 1),
            'task.categorycomment'              => __('Category comment'),
            'task.time'                         => __('Total duration'),
            'task.user'                         => __('User assigned to task'),
            'task.group'                        => __('Group assigned to task'),
            'task.begin'                        => __('Start date'),
            'task.end'                          => __('End date'),
            'task.status'                       => __('Status'),
            $objettype . '.numberoftasks'         => _x('quantity', 'Number of tasks'),
            $objettype . '.entity.phone'          => sprintf(
                __('%1$s (%2$s)'),
                Entity::getTypeName(1),
                Phone::getTypeName(1)
            ),
            $objettype . '.entity.fax'            => sprintf(
                __('%1$s (%2$s)'),
                Entity::getTypeName(1),
                __('Fax')
            ),
            $objettype . '.entity.website'        => sprintf(
                __('%1$s (%2$s)'),
                Entity::getTypeName(1),
                __('Website')
            ),
            $objettype . '.entity.email'          => sprintf(
                __('%1$s (%2$s)'),
                Entity::getTypeName(1),
                _n('Email', 'Emails', 1)
            ),
            $objettype . '.entity.address'        => sprintf(
                __('%1$s (%2$s)'),
                Entity::getTypeName(1),
                __('Address')
            ),
            $objettype . '.entity.postcode'       => sprintf(
                __('%1$s (%2$s)'),
                Entity::getTypeName(1),
                __('Postal code')
            ),
            $objettype . '.entity.town'           => sprintf(
                __('%1$s (%2$s)'),
                Entity::getTypeName(1),
                __('City')
            ),
            $objettype . '.entity.state'          => sprintf(
                __('%1$s (%2$s)'),
                Entity::getTypeName(1),
                _x('location', 'State')
            ),
            $objettype . '.entity.country'        => sprintf(
                __('%1$s (%2$s)'),
                Entity::getTypeName(1),
                __('Country')
            ),
            $objettype . '.entity.registration_number'        => sprintf(
                __('%1$s (%2$s)'),
                Entity::getTypeName(1),
                __('Administrative Number')
            ),
            'timelineitems.author'              => __('Writer'),
            'timelineitems.date'                => __('Opening date'),
            'timelineitems.type'                => __('Internal type'),
            'timelineitems.typename'            => _n('Type', 'Types', 1),
            'timelineitems.description'         => __('Description'),
            'timelineitems.position'            => __('Position'),
            $objettype . '.numberoflinkedtickets' => _x('quantity', 'Number of linked tickets'),
            $objettype . '.numberoflinkedchanges' => _x('quantity', 'Number of linked changes'),
            $objettype . '.numberoflinkedproblems' => _x('quantity', 'Number of linked problems'),
            $objettype . '.reminder.bumpcounter' => __('Number of sent reminders since status is pending'),
            $objettype . '.reminder.bumpremaining' => __('Number of remaining reminders before automatic resolution'),
            $objettype . '.reminder.bumptotal'  => __('Total number of reminders before automatic resolution'),
            $objettype . '.reminder.deadline'   => __('Auto resolution deadline'),
            $objettype . '.reminder.text'   => __('Reminder text'),
            $objettype . '.reminder.name' => __('Pending reason name'),
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'    => $tag,
                'label'  => $label,
                'value'  => true,
                'events' => parent::TAG_FOR_ALL_EVENTS,
            ]);
        }

        //Foreach global tags
        $tags = ['log'       => __('Historical'),
            'followups' => _n('Followup', 'Followups', Session::getPluralNumber()),
            'tasks'     => _n('Task', 'Tasks', Session::getPluralNumber()),
            'costs'     => _n('Cost', 'Costs', Session::getPluralNumber()),
            'authors'   => _n('Requester', 'Requesters', Session::getPluralNumber()),
            'suppliers' => _n('Supplier', 'Suppliers', Session::getPluralNumber()),
            'actors' => __('Actors'),
            'timelineitems' => sprintf(__('Processing %1$s'), strtolower($objettype)),
            'linkedtickets' => _n('Linked ticket', 'Linked tickets', Session::getPluralNumber()),
            'linkedchanges' => _n('Linked change', 'Linked changes', Session::getPluralNumber()),
            'linkedproblems' => _n('Linked problem', 'Linked problems', Session::getPluralNumber()),
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'     => $tag,
                'label'   => $label,
                'value'   => false,
                'foreach' => true,
            ]);
        }

        //Tags with just lang
        $tags = [
            $objettype . '.days'                => _n('Day', 'Days', Session::getPluralNumber()),
            $objettype . '.attribution'         => __('Assigned to'),
            $objettype . '.entity'              => Entity::getTypeName(1),
            $objettype . '.nocategoryassigned'  => __('No defined category'),
            $objettype . '.log'                 => __('Historical'),
            $objettype . '.tasks'               => _n('Task', 'Tasks', Session::getPluralNumber()),
            $objettype . '.costs'               => _n('Cost', 'Costs', Session::getPluralNumber()),
            $objettype . '.timelineitems'       => sprintf(__('Processing %1$s'), strtolower($objettype)),
            $objettype . '.linkedtickets'       => _n('Linked ticket', 'Linked tickets', Session::getPluralNumber()),
            $objettype . '.linkedchanges'       => _n('Linked change', 'Linked changes', Session::getPluralNumber()),
            $objettype . '.linkedproblems'      => _n('Linked problem', 'Linked problems', Session::getPluralNumber()),
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'   => $tag,
                'label' => $label,
                'value' => false,
                'lang'  => true,
            ]);
        }

        //Tags without lang
        $tags = [$objettype . '.urlapprove'     => __('Web link to approval the solution'),
            $objettype . '.entity'         => sprintf(
                __('%1$s (%2$s)'),
                Entity::getTypeName(1),
                __('Complete name')
            ),
            $objettype . '.shortentity'    => sprintf(
                __('%1$s (%2$s)'),
                Entity::getTypeName(1),
                __('Name')
            ),
            $objettype . '.numberoflogs'   => sprintf(
                __('%1$s: %2$s'),
                __('Historical'),
                _x('quantity', 'Number of items')
            ),
            $objettype . '.log.date'       => sprintf(
                __('%1$s: %2$s'),
                __('Historical'),
                _n('Date', 'Dates', 1)
            ),
            $objettype . '.log.user'       => sprintf(
                __('%1$s: %2$s'),
                __('Historical'),
                User::getTypeName(1)
            ),
            $objettype . '.log.field'      => sprintf(
                __('%1$s: %2$s'),
                __('Historical'),
                _n('Field', 'Fields', 1)
            ),
            $objettype . '.log.content'    => sprintf(
                __('%1$s: %2$s'),
                __('Historical'),
                _x('name', 'Update')
            ),
            'document.url'               => sprintf(
                __('%1$s: %2$s'),
                Document::getTypeName(1),
                __('URL')
            ),
            'document.downloadurl'       => sprintf(
                __('%1$s: %2$s'),
                Document::getTypeName(1),
                __('Download URL')
            ),
            'document.heading'           => sprintf(
                __('%1$s: %2$s'),
                Document::getTypeName(1),
                __('Heading')
            ),
            'document.id'                => sprintf(
                __('%1$s: %2$s'),
                Document::getTypeName(1),
                __('ID')
            ),
            'document.filename'          => sprintf(
                __('%1$s: %2$s'),
                Document::getTypeName(1),
                __('File')
            ),
            'document.weblink'           => sprintf(
                __('%1$s: %2$s'),
                Document::getTypeName(1),
                __('Web link')
            ),
            'document.name'              => sprintf(
                __('%1$s: %2$s'),
                Document::getTypeName(1),
                __('Name')
            ),
            $objettype . '.urldocument'   => sprintf(
                __('%1$s: %2$s'),
                Document::getTypeName(Session::getPluralNumber()),
                __('URL')
            ),
            'linkedticket.id'         => sprintf(
                __('%1$s: %2$s'),
                _n('Linked ticket', 'Linked tickets', 1),
                __('ID')
            ),
            'linkedticket.link'       => sprintf(
                __('%1$s: %2$s'),
                _n('Linked ticket', 'Linked tickets', 1),
                Link::getTypeName(1)
            ),
            'linkedticket.url'        => sprintf(
                __('%1$s: %2$s'),
                _n('Linked ticket', 'Linked tickets', 1),
                __('URL')
            ),
            'linkedticket.title'      => sprintf(
                __('%1$s: %2$s'),
                _n('Linked ticket', 'Linked tickets', 1),
                __('Title')
            ),
            'linkedticket.content'    => sprintf(
                __('%1$s: %2$s'),
                _n('Linked ticket', 'Linked tickets', 1),
                __('Description')
            ),
            'linkedchange.id'         => sprintf(
                __('%1$s: %2$s'),
                _n('Linked change', 'Linked changes', 1),
                __('ID')
            ),
            'linkedchange.link'       => sprintf(
                __('%1$s: %2$s'),
                _n('Linked change', 'Linked changes', 1),
                Link::getTypeName(1)
            ),
            'linkedchange.url'        => sprintf(
                __('%1$s: %2$s'),
                _n('Linked change', 'Linked changes', 1),
                __('URL')
            ),
            'linkedchange.title'      => sprintf(
                __('%1$s: %2$s'),
                _n('Linked change', 'Linked changes', 1),
                __('Title')
            ),
            'linkedchange.content'    => sprintf(
                __('%1$s: %2$s'),
                _n('Linked change', 'Linked changes', 1),
                __('Description')
            ),
            'linkedproblem.id'         => sprintf(
                __('%1$s: %2$s'),
                _n('Linked problem', 'Linked problems', 1),
                __('ID')
            ),
            'linkedproblem.link'       => sprintf(
                __('%1$s: %2$s'),
                _n('Linked problem', 'Linked problems', 1),
                Link::getTypeName(1)
            ),
            'linkedproblem.url'        => sprintf(
                __('%1$s: %2$s'),
                _n('Linked problem', 'Linked problems', 1),
                __('URL')
            ),
            'linkedproblem.title'      => sprintf(
                __('%1$s: %2$s'),
                _n('Linked problem', 'Linked problems', 1),
                __('Title')
            ),
            'linkedproblem.content'    => sprintf(
                __('%1$s: %2$s'),
                _n('Linked problem', 'Linked problems', 1),
                __('Description')
            ),
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'   => $tag,
                'label' => $label,
                'value' => true,
                'lang'  => false,
            ]);
        }

        //Tickets with a fixed set of values
        $status         = $this->obj->getAllStatusArray(false);
        $allowed_ticket = [];
        foreach ($status as $key => $value) {
            $allowed_ticket[] = $key;
        }

        $tags = [$objettype . '.storestatus' => ['text'     => __('Status value in database'),
            'allowed_values'
                                                                  => $allowed_ticket,
        ],
        ];
        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'            => $tag,
                'label'          => $label['text'],
                'value'          => true,
                'lang'           => false,
                'allowed_values' => $label['allowed_values'],
            ]);
        }

        $inquest = $this->obj::getSatisfactionClassInstance();
        if ($inquest !== null) {
            $tags = ['satisfaction.datebegin' => __('Creation date of the satisfaction survey'),
                'satisfaction.dateanswered' => __('Response date to the satisfaction survey'),
                'satisfaction.satisfaction' => __('Satisfaction'),
                'satisfaction.description' => __('Comments to the satisfaction survey'),
            ];

            foreach ($tags as $tag => $label) {
                $this->addTagToList(['tag' => $tag,
                    'label' => $label,
                    'value' => true,
                    'events' => ['satisfaction'],
                ]);
            }

            $tags = ['satisfaction.type' => __('Survey type'),];

            foreach ($tags as $tag => $label) {
                $this->addTagToList(['tag' => $tag,
                    'label' => $label,
                    'value' => true,
                    'lang' => false,
                    'events' => ['satisfaction'],
                ]);
            }

            $tags = ['satisfaction.text' => __('Invitation to fill out the survey')];

            foreach ($tags as $tag => $label) {
                $this->addTagToList(['tag' => $tag,
                    'label' => $label,
                    'value' => false,
                    'lang' => true,
                    'events' => ['satisfaction'],
                ]);
            }

            $this->addTagToList([
                'tag' => $objettype . '.urlsatisfaction',
                'label' => sprintf(
                    __('%1$s: %2$s'),
                    __('Satisfaction'),
                    __('URL')
                ),
                'value' => true,
                'lang' => false,
            ]);
        }
    }

    private function getUserPositionFromTimelineItemPosition($position)
    {

        switch ($position) {
            case CommonITILObject::TIMELINE_LEFT:
                $user_position = 'left';
                break;
            case CommonITILObject::TIMELINE_MIDLEFT:
                $user_position = 'left middle';
                break;
            case CommonITILObject::TIMELINE_MIDRIGHT:
                $user_position = 'right middle';
                break;
            case CommonITILObject::TIMELINE_RIGHT:
                $user_position = 'right';
                break;
            default:
                $user_position = 'left';
                break;
        }

        return $user_position;
    }
}
