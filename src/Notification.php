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
 * Notification Class
 **/
class Notification extends CommonDBTM
{
    // MAILING TYPE
    //Notification to a user (sse mailing users type below)
    public const USER_TYPE             = 1;
    //Notification to users of a profile
    public const PROFILE_TYPE          = 2;
    //Notification to users of a group
    public const GROUP_TYPE            = 3;
    //Notification to the people in charge of the database synchronisation
    public const MAILING_TYPE          = 4;
    //Notification to the supervisor of a group
    public const SUPERVISOR_GROUP_TYPE = 5;
    //Notification to all users of a group except supervisor
    public const GROUP_WITHOUT_SUPERVISOR_TYPE = 6;

    // MAILING USERS TYPE

    //Notification to the GLPI global administrator
    public const GLOBAL_ADMINISTRATOR                = 1;
    //Notification to the technicial who's assign to a ticket
    public const ASSIGN_TECH                         = 2;
    //Notification to the owner of the item
    public const AUTHOR                              = 3;
    //Notification to the technician previously in charge of the ticket
    public const OLD_TECH_IN_CHARGE                  = 4;
    //Notification to the technician in charge of the item
    public const ITEM_TECH_IN_CHARGE                 = 5;
    //Notification to the item's user
    public const ITEM_USER                           = 6;
    //Notification to the ticket's recipient
    public const RECIPIENT                           = 7;
    //Notificartion to the ticket's assigned supplier
    public const SUPPLIER                            = 8;
    //Notification to the ticket's assigned group
    public const ASSIGN_GROUP                        = 9;
    //Notification to the supervisor of the ticket's assigned group
    public const SUPERVISOR_ASSIGN_GROUP             = 10;
    //Notification to the entity administrator
    public const ENTITY_ADMINISTRATOR                = 11;
    //Notification to the supervisor of the ticket's requester group
    public const SUPERVISOR_REQUESTER_GROUP          = 12;
    //Notification to the ticket's requester group
    public const REQUESTER_GROUP                     = 13;
    //Notification to the ticket's validation approver
    public const VALIDATION_APPROVER                 = 14;
    //Notification to the ticket's validation requester
    public const VALIDATION_REQUESTER                = 15;
    //Notification to the task assigned user
    public const TASK_ASSIGN_TECH                    = 16;
    //Notification to the task author
    public const TASK_AUTHOR                         = 17;
    //Notification to the followup author
    public const FOLLOWUP_AUTHOR                     = 18;
    //Notification to the user
    public const USER                                = 19;
    //Notification to the ticket's observer group
    public const OBSERVER_GROUP                      = 20;
    //Notification to the ticket's observer user
    public const OBSERVER                            = 21;
    //Notification to the supervisor of the ticket's observer group
    public const SUPERVISOR_OBSERVER_GROUP           = 22;
    //Notification to the group of technicians in charge of the item
    public const ITEM_TECH_GROUP_IN_CHARGE           = 23;
    // Notification to the ticket's assigned group without supervisor
    public const ASSIGN_GROUP_WITHOUT_SUPERVISOR     = 24;
    //Notification to the ticket's requester group without supervisor
    public const REQUESTER_GROUP_WITHOUT_SUPERVISOR  = 25;
    //Notification to the ticket's observer group without supervisor
    public const OBSERVER_GROUP_WITHOUT_SUPERVISOR   = 26;
    // Notification to manager users
    public const MANAGER_USER                        = 27;
    // Notification to manager groups
    public const MANAGER_GROUP                       = 28;
    // Notification to supervisor of manager group
    public const MANAGER_GROUP_SUPERVISOR            = 29;
    // Notification to manager group without supervisor
    public const MANAGER_GROUP_WITHOUT_SUPERVISOR    = 30;
    // Notification to team users
    public const TEAM_USER                           = 31;
    // Notification to team groups
    public const TEAM_GROUP                          = 32;
    // Notification to supervisor of team groups
    public const TEAM_GROUP_SUPERVISOR               = 33;
    // Notification to team groups without supervisor
    public const TEAM_GROUP_WITHOUT_SUPERVISOR       = 34;
    // Notification to team contacts
    public const TEAM_CONTACT                        = 35;
    // Notification to team suppliers
    public const TEAM_SUPPLIER                       = 36;
    //Notification to the task assigned group
    public const TASK_ASSIGN_GROUP                   = 37;
    //Notification to planning event's guests
    public const PLANNING_EVENT_GUESTS               = 38;
    //Notification to the mentionned user
    public const MENTIONNED_USER                     = 39;

    // From CommonDBTM
    public $dohistory = true;

    public static $rightname = 'notification';



    public static function getTypeName($nb = 0)
    {
        return _n('Notification', 'Notifications', $nb);
    }


    /**
     *  @see CommonGLPI::getMenuContent()
     *
     *  @since 0.85
     **/
    public static function getMenuContent()
    {
        $menu = [];

        if (
            Notification::canView()
            || Config::canView()
        ) {
            $menu['title']                                      = _n('Notification', 'Notifications', Session::getPluralNumber());
            $menu['page']                                       = '/front/setup.notification.php';
            $menu['icon']                                       = self::getIcon();
            $menu['options']['notification']['title']           = _n('Notification', 'Notifications', Session::getPluralNumber());
            $menu['options']['notification']['page']            = Notification::getSearchURL(false);
            $menu['options']['notification']['links']['add']    = Notification::getFormURL(false);
            $menu['options']['notification']['links']['search'] = Notification::getSearchURL(false);
            //saved search list
            $menu['options']['notification']['links']['lists']  = "";
            $menu['options']['notification']['lists_itemtype']  = Notification::getType();

            $menu['options']['notificationtemplate']['title']
                        = _n('Notification template', 'Notification templates', Session::getPluralNumber());
            $menu['options']['notificationtemplate']['page']
                        = NotificationTemplate::getSearchURL(false);
            $menu['options']['notificationtemplate']['links']['add']
                        = NotificationTemplate::getFormURL(false);
            $menu['options']['notificationtemplate']['links']['search']
                        = NotificationTemplate::getSearchURL(false);
            //saved search list
            $menu['options']['notificationtemplate']['links']['lists']  = "";
            $menu['options']['notificationtemplate']['lists_itemtype']  = NotificationTemplate::getType();
        }
        if (count($menu)) {
            return $menu;
        }
        return false;
    }


    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addImpactTab($ong, $options);
        $this->addStandardTab('Notification_NotificationTemplate', $ong, $options);
        $this->addStandardTab('NotificationTarget', $ong, $options);
        $this->addStandardTab('Log', $ong, $options);

        return $ong;
    }


    public function showForm($ID, array $options = [])
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $this->initForm($ID, $options);
        $this->showFormHeader($options);

        echo "<tr class='tab_bg_1'><td>" . __('Name') . "</td>";
        echo "<td>";
        echo Html::input('name', ['value' => $this->fields['name']]);
        echo "</td>";

        echo "<td rowspan='4' class='middle right'>" . __('Comments') . "</td>";
        echo "<td class='center middle' rowspan='4'><textarea class='form-control' rows='9' name='comment' >" .
             $this->fields["comment"] . "</textarea></td></tr>";

        echo "<tr class='tab_bg_1'><td>" . __('Active') . "</td>";
        echo "<td>";
        Dropdown::showYesNo('is_active', $this->fields['is_active']);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . __('Allow response') . "</td>";
        echo "<td>";
        Dropdown::showYesNo('allow_response', $this->allowResponse());
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . _n('Type', 'Types', 1) . "</td>";
        echo "<td>";
        if (!Session::haveRight(static::$rightname, UPDATE)) {
            $itemtype = $this->fields['itemtype'];
            echo $itemtype::getTypeName(1);
            $rand = '';
        } elseif (
            Config::canUpdate()
            && ($this->getEntityID() == 0)
        ) {
            $rand = Dropdown::showItemTypes(
                'itemtype',
                $CFG_GLPI["notificationtemplates_types"],
                ['value' => $this->fields['itemtype']]
            );
        } else {
            $rand = Dropdown::showItemTypes(
                'itemtype',
                array_diff(
                    $CFG_GLPI["notificationtemplates_types"],
                    ['CronTask', 'DBConnection', 'User']
                ),
                ['value' => $this->fields['itemtype']]
            );
        }

        $params = ['itemtype' => '__VALUE__'];
        Ajax::updateItemOnSelectEvent(
            "dropdown_itemtype$rand",
            "show_events",
            $CFG_GLPI["root_doc"] . "/ajax/dropdownNotificationEvent.php",
            $params
        );
        Ajax::updateItemOnSelectEvent(
            "dropdown_itemtype$rand",
            "show_templates",
            $CFG_GLPI["root_doc"] . "/ajax/dropdownNotificationTemplate.php",
            $params
        );
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . NotificationEvent::getTypeName(1) . "</td>";
        echo "<td><span id='show_events'>";
        NotificationEvent::dropdownEvents(
            $this->fields['itemtype'],
            ['value' => $this->fields['event']]
        );
        echo "</span></td></tr>";

        $this->showFormButtons($options);
        return true;
    }


    /**
     * @since 0.84
     *
     * @param $field
     * @param $values
     * @param $options   array
     **/
    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'event':
                if (isset($values['itemtype']) && !empty($values['itemtype'])) {
                    return NotificationEvent::getEventName($values['itemtype'], $values[$field]);
                }
                break;
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }


    /**
     * @since 0.84
     *
     * @param $field
     * @param $name               (default '')
     * @param $values             (default '')
     * @param $options      array
     **/
    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {

        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (!is_array($values)) {
            $values = [$field => $values];
        }

        switch ($field) {
            case 'event':
                $itemtypes = (isset($values['itemtype']) && !empty($values['itemtype']))
                ? $values['itemtype']
                : $CFG_GLPI["notificationtemplates_types"];

                $events = [];
                foreach ($itemtypes as $itemtype) {
                    $target = NotificationTarget::getInstanceByType($itemtype);
                    if ($target) {
                        $target_events = $target->getAllEvents();
                        foreach ($target_events as $key => $label) {
                            $events[$itemtype][$itemtype . Search::SHORTSEP . $key] = $label;
                        }
                    }
                }

                return Dropdown::showFromArray(
                    $name,
                    $events,
                    [
                        'display'             => false,
                        'display_emptychoice' => true,
                        'value'               => $values[$field],
                    ]
                );
                break;
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }


    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics'),
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => $this->getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'event',
            'name'               => _n('Event', 'Events', 1),
            'massiveaction'      => false,
            'datatype'           => 'specific',
            'additionalfields'   => [
                'itemtype',
            ],
            'searchtype'         => [
                'equals',
                'notequals',
            ],
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => Notification_NotificationTemplate::getTable(),
            'field'              => 'mode',
            'name'               => __('Notification method'),
            'massiveaction'      => false,
            'searchequalsonfield' => true,
            'datatype'           => 'specific',
            'joinparams'         => [
                'jointype'  => 'child',
            ],
            'searchtype'         => [
                '0'                  => 'equals',
                '1'                  => 'notequals',
            ],
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => 'glpi_notificationtemplates',
            'field'              => 'name',
            'name'               => _n('Notification template', 'Notification templates', Session::getPluralNumber()),
            'datatype'           => 'itemlink',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'  => [
                    'table'        => Notification_NotificationTemplate::getTable(),
                    'joinparams'   => [
                        'jointype'  => 'child',
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'itemtype',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'itemtypename',
            'itemtype_list'      => 'notificationtemplates_types',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => $this->getTable(),
            'field'              => 'is_active',
            'name'               => __('Active'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => $this->getTable(),
            'field'              => 'comment',
            'name'               => __('Comments'),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '86',
            'table'              => $this->getTable(),
            'field'              => 'is_recursive',
            'name'               => __('Child entities'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '87',
            'table'              => $this->getTable(),
            'field'              => 'allow_response',
            'name'               => __('Allow response'),
            'datatype'           => 'bool',
        ];

        return $tab;
    }

    /**
     * Get the massive actions for this object
     *
     * @param object|null $checkitem
     * @return array list of actions
     */
    public function getSpecificMassiveActions($checkitem = null)
    {

        $isadmin = static::canUpdate();
        $actions = parent::getSpecificMassiveActions($checkitem);

        if ($isadmin) {
            $actions[__CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'add_template'] = _x('button', 'Add notification template');
            $actions[__CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'remove_all_template'] = _x('button', 'Remove all notification templates');
        }

        return $actions;
    }

    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        switch ($ma->getAction()) {
            case 'add_template':
                $notification_notificationtemplate = new Notification_NotificationTemplate();
                $notification_notificationtemplate->showFormMassiveAction();
                return true;
            case 'remove_all_template':
                echo Html::submit(__('Delete'), ['name' => 'massiveaction']);
                return true;
        }
        return false;
    }


    public static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item, array $ids)
    {

        switch ($ma->getAction()) {
            case 'add_template':
                foreach ($ids as $id) {
                    //load notification
                    $notification = new Notification();
                    $notification->getFromDB($id);

                    //check if selected template
                    $notification_template = new NotificationTemplate();
                    $notification_template->getFromDB($ma->POST['notificationtemplates_id']);

                    if ($notification_template->fields['itemtype'] == $notification->fields['itemtype']) {
                        //check if already exist
                        $notification_notificationtemplate = new Notification_NotificationTemplate();
                        $data = [
                            'mode'                     => $ma->POST['mode'],
                            'notificationtemplates_id' => $ma->POST['notificationtemplates_id'],
                            'notifications_id'         => $id,
                        ];
                        if ($notification_notificationtemplate->getFromDBByCrit($data)) {
                            $ma->itemDone(Notification::getType(), $ma->POST['notificationtemplates_id'], MassiveAction::ACTION_OK);
                        } else {
                            $notification_notificationtemplate->add($data);
                            $ma->itemDone(Notification::getType(), $ma->POST['notificationtemplates_id'], MassiveAction::ACTION_OK);
                        }
                    } else {
                        $ma->itemDone(Notification::getType(), 0, MassiveAction::ACTION_KO);
                        $ma->addMessage($notification->getErrorMessage(ERROR_COMPAT) . " (" . $notification_template->getLink() . ")");
                    }
                }
                return;
            case 'remove_all_template':
                foreach ($ids as $id) {
                    //load notification
                    $notification = new Notification();
                    $notification->getFromDB($id);

                    //delete all links between notification and template
                    $notification_notificationtemplate = new Notification_NotificationTemplate();
                    $notification_notificationtemplate->deleteByCriteria(['notifications_id' => $id]);
                    $ma->itemDone(Notification::getType(), $id, MassiveAction::ACTION_OK);
                }
                return;
        }
        return;
    }


    public function canViewItem()
    {

        if (
            (($this->fields['itemtype'] == 'CronTask')
            || ($this->fields['itemtype'] == 'DBConnection'))
            && !Config::canView()
        ) {
            return false;
        }
        return Session::haveAccessToEntity($this->getEntityID(), $this->isRecursive());
    }


    /**
     * Is the current user have right to update the current notification ?
     *
     * @return boolean
     **/
    public function canCreateItem()
    {

        if (
            (($this->fields['itemtype'] == 'CronTask')
            || ($this->fields['itemtype'] == 'DBConnection'))
            && !Config::canUpdate()
        ) {
            return false;
        }
        return Session::haveAccessToEntity($this->getEntityID());
    }


    public function cleanDBonPurge()
    {

        $this->deleteChildrenAndRelationsFromDb(
            [
                Notification_NotificationTemplate::class,
                NotificationTarget::class,
            ]
        );
    }


    /**
     * Send notification
     *
     * @param array $options Options
     *
     * @return void
     **/
    public static function send($options)
    {
        $classname = Notification_NotificationTemplate::getModeClass($options['mode']);
        $notif = new $classname();
        $notif->sendNotification($options);
    }


    /**
     * Get the mailing signature for the entity
     *
     * @param $entity
     **/
    public static function getMailingSignature($entity)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $signature = trim(Entity::getUsedConfig('mailing_signature', $entity, '', ''));
        if (strlen($signature) > 0) {
            return $signature;
        }

        return $CFG_GLPI['mailing_signature'];
    }


    /**
     * @param string $event    Event name
     * @param string $itemtype Item type
     * @param int    $entity   Restrict to entity
     *
     * @return DBmysqlIterator
     **/
    public static function getNotificationsByEventAndType($event, $itemtype, $entity)
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $criteria = [
            'SELECT'    => [
                Notification::getTable() . '.*',
                Notification_NotificationTemplate::getTable() . '.mode',
                Notification_NotificationTemplate::getTable() . '.notificationtemplates_id',
            ],
            'FROM'      => Notification::getTable(),
            'LEFT JOIN' => [
                Entity::getTable()                              => [
                    'ON' => [
                        Entity::getTable()         => 'id',
                        Notification::getTable()   => 'entities_id',
                    ],
                ],
                Notification_NotificationTemplate::getTable()   => [
                    'ON' => [
                        Notification_NotificationTemplate::getTable()   => 'notifications_id',
                        Notification::getTable()                        => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                Notification::getTable() . '.itemtype' => $itemtype,
                Notification::getTable() . '.event'    => $event,
                Notification::getTable() . '.is_active' => 1,
            ] + getEntitiesRestrictCriteria(
                Notification::getTable(),
                'entities_id',
                $entity,
                true
            ),
            'ORDER'     => Entity::getTable() . '.level DESC',
        ];

        $modes = Notification_NotificationTemplate::getModes();
        $restrict_modes = [];
        foreach ($modes as $mode => $conf) {
            if ($CFG_GLPI['notifications_' . $mode]) {
                $restrict_modes[] = $mode;
            }
        }
        if (count($restrict_modes)) {
            $criteria['WHERE'][Notification_NotificationTemplate::getTable() . '.mode'] = $restrict_modes;
        }

        return $DB->request($criteria);
    }


    public function prepareInputForAdd($input)
    {

        if (isset($input["itemtype"]) && empty($input["itemtype"])) {
            $message = __('Field itemtype is mandatory');
            Session::addMessageAfterRedirect($message, false, ERROR);
            return false;
        }

        return $input;
    }


    public function prepareInputForUpdate($input)
    {

        if (isset($input["itemtype"]) && empty($input["itemtype"])) {
            $message = __('Field itemtype is mandatory');
            Session::addMessageAfterRedirect($message, false, ERROR);
            return false;
        }

        return $input;
    }


    public static function getIcon()
    {
        return "ti ti-bell";
    }

    public function allowResponse()
    {
        return $this->fields['allow_response'];
    }
}
