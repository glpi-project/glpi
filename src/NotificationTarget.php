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

use Glpi\Plugin\Hooks;

/**
 * NotificationTarget Class
 *
 * @since 0.84
 **/
class NotificationTarget extends CommonDBChild
{
    public $prefix                      = '';
   // From CommonDBChild
    public static $itemtype             = 'Notification';
    public static $items_id             = 'notifications_id';
    public $table                       = 'glpi_notificationtargets';

    public $notification_targets        = [];
    public $notification_targets_labels = [];
    public $notificationoptions         = 0;

   // Data from the objet which can be used by the template
   // See https://forge.indepnet.net/projects/5/wiki/NotificationTemplatesTags
    public $data                        = [];
    public $tag_descriptions            = [];

   // From CommonDBTM
    public $dohistory                   = true;

   //Array to store emails by notification
    public $target                      = [];
    public $entity                      = '';

   //Object which raises the notification event
    public $obj                         = null;

   //Object which is associated with the event
    public $target_object               = [];

   // array of event name => event label
    public $events                      = [];
    public $options                     = [];
    public $raiseevent                  = '';

    /**
     * Recipient related to called "add_recipient_to_target" hook.
     * Variable contains `itemtype` and `items_id` keys and is set only during hook execution.
     * @var array
     */
    public $recipient_data;

    private $allow_response             = true;
    private $mode                       = null;
    private $event                      = null;

    const TAG_LANGUAGE               = 'lang';
    const TAG_VALUE                  = 'tag';
    const TAG_FOR_ALL_EVENTS         = 0;


    const ANONYMOUS_USER             = 0;
    const GLPI_USER                  = 1;
    const EXTERNAL_USER              = 2;

    /**
     * @param string $entity  (default '')
     * @param string $event   (default '')
     * @param mixed  $object  (default null)
     * @param array  $options Options
     **/
    public function __construct($entity = '', $event = '', $object = null, $options = [])
    {

        if ($entity === '') {
            $this->entity = (isset($_SESSION['glpiactive_entity']) ? $_SESSION['glpiactive_entity'] : 0);
        } else {
            $this->entity = $entity;
        }

        if ($object) {
            if (
                $object instanceof CommonDBTM
                && isset($object->fields['id'])
            ) {
               // Reread to avoid slashes issue
                $object->getFromDB($object->fields['id']);
            }
            $this->obj = $object;
            $this->getObjectItem($event);
        }

        $this->raiseevent = $event;
        $this->options    = $options;

        $this->addNotificationTargets($this->entity);

        $this->addAdditionalTargets($event);

       // add new target by plugin
        unset($this->data);
        Plugin::doHook(Hooks::ITEM_ADD_TARGETS, $this);
        asort($this->notification_targets);

        $this->registerGlobalTags();
    }


    public static function getTable($classname = null)
    {
        return parent::getTable(__CLASS__);
    }


    /**
     * Retrieve an item from the database for a specific target
     *
     * @param integer $notifications_id notification ID
     * @param string  $type             type of the target to retrive
     * @param integer $ID               ID of the target to retrieve
     *
     * @since 0.85
     *
     * @return boolean
     **/
    public function getFromDBForTarget($notifications_id, $type, $ID)
    {

        if (
            $this->getFromDBByCrit([
                $this->getTable() . '.notifications_id'   => $notifications_id,
                $this->getTable() . '.items_id'           => $ID,
                $this->getTable() . '.type'               => $type
            ])
        ) {
            return true;
        }
        return false;
    }


    /**
     * Validate send before doing it (may be overloaded : exemple for private tasks or followups)
     *
     * @since 0.84 (new parameter)
     *
     * @param string  $event     notification event
     * @param array   $infos     destination of the notification
     * @param boolean $notify_me notify me on my action ?
     *                           ($infos contains users_id to check if the target is me)
     *                           (false by default)
     * @param mixed $emitter     if this action is executed by the cron, we can
     *                           supply the id of the user (or the email if this
     *                           is an anonymous user with no account) who
     *                           triggered the event so it can be used instead of
     *                           getLoginUserID
     *
     * @return boolean
     **/
    public function validateSendTo($event, array $infos, $notify_me = false, $emitter = null)
    {
        $users_id = Session::getLoginUserID(false);

       // Override session ID with emitter ID if supplied
        if (is_int($emitter)) {
           // We have an ID, we can use it directly
            $users_id = $emitter;
        } else if (is_string($emitter)) {
           // We have an email, we need to check that the users_id is -1 which
           // is the value used for anonymous user and compare the emails
            if (
                isset($infos['users_id']) && $infos['users_id'] == -1
                && isset($infos['email']) && $infos['email'] == $emitter
            ) {
                $users_id = -1;
            }
        }

        if (!$notify_me) {
            if (
                isset($infos['users_id'])
                // Check login user and not event launch by crontask
                && ($infos['users_id'] === $users_id)
            ) {
                return false;
            }
        }

        return true;
    }


    /**
     * @param $event  (default '')
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
            $perso_tag = "GLPI";
        }
        return "[$perso_tag] ";
    }

    /**
     * Get header to add to content
     **/
    public function getContentHeader()
    {
        return '';
    }

    /**
     * Get footer to add to content
     **/
    public function getContentFooter()
    {
        return '';
    }

    /**
     * Get message ID for current notification.
     *
     * @since 0.84
     *
     * @return string
     */
    public function getMessageID()
    {
        return self::getMessageIdForEvent(
            $this->obj instanceof CommonDBTM ? $this->obj->getType() : null,
            $this->obj instanceof CommonDBTM ? $this->obj->getID() : null,
            $this->raiseevent
        );
    }

    /**
     * Get message ID for given item/event.
     *
     * @param string $itemtype
     * @param int $items_id
     * @param string $event
     *
     * @return string
     */
    final public static function getMessageIdForEvent(?string $itemtype, ?int $items_id, ?string $event): string
    {
        if (empty($event)) {
            $event = 'none';
        }
        $is_item_related = !empty($itemtype) && is_a($itemtype, CommonDBTM::class, true);

        $message_id  = 'GLPI';
        $message_id .= sprintf('_%s', Config::getUuid('notification'));

        $reference_event = null;
        if ($is_item_related) {
            $message_id .= sprintf('-%s-%d', $itemtype, $items_id);
            $reference_event = $itemtype::getMessageReferenceEvent($event);
        }

        $message_id .= sprintf('/%s', $event);

        if ($reference_event === null || $event !== $reference_event) {
            // Add random, unless event is the reference event for the related item.
            // eg. no random will be added for `new` event of a ticket, but a random will be added for `add_followup` events.
            $message_id .= sprintf('.%d.%d', time(), rand());
        }

        $message_id .= sprintf('@%s', php_uname('n'));

        return $message_id;
    }


    public static function getTypeName($nb = 0)
    {
        return _n('Recipient', 'Recipients', $nb);
    }

    protected function computeFriendlyName()
    {

        if (
            isset($this->notification_targets_labels[$this->getField("type")]
                                                  [$this->getField("items_id")])
        ) {
            return $this->notification_targets_labels[$this->getField("type")]
                                                  [$this->getField("items_id")];
        }
        return '';
    }

    /**
     * Get a notificationtarget class by giving the object which raises the event
     *
     * @param $item            the object which raises the event
     * @param $event           the event which will be used (default '')
     * @param $options   array of options
     *
     * @return NotificationTarget|false
     **/
    public static function getInstance($item, $event = '', $options = [])
    {
        $name = self::getInstanceClass($item->getType());

        $entity = 0;
        if (class_exists($name)) {
           //Entity ID exists in the options array
            if (isset($options['entities_id'])) {
                $entity = $options['entities_id'];
            } else if (method_exists($item, 'getEntityID') && $item->getEntityID() >= 0) {
               //Item which raises the event contains an entityID
                $entity = $item->getEntityID();
            }

            return new $name($entity, $event, $item, $options);
        }
        return false;
    }

    /**
     * Get the expected notification target class name for a given itemtype
     *
     * @param string $itemtype
     *
     * @return string
     */
    public static function getInstanceClass(string $itemtype): string
    {
        if (strpos($itemtype, "\\") != false) {
            // namespace case
            $ns_parts = explode("\\", $itemtype);
            $classname = array_pop($ns_parts);
            $name = implode("\\", $ns_parts) . "\\NotificationTarget$classname";
        } elseif ($plug = isPluginItemType($itemtype)) {
            // plugins case
            $name = 'Plugin' . $plug['plugin'] . 'NotificationTarget' . $plug['class'];
        } else {
            // simple class (without namespace)
            $name = "NotificationTarget$itemtype";
        }

        return $name;
    }

    /**
     * Get a notificationtarget class by giving an itemtype
     *
     * @param $itemtype           the itemtype of the object which raises the event
     * @param $event              the event which will be used (default '')
     * @param $options   array    of options
     *
     * @return NotificationTarget|false
     **/
    public static function getInstanceByType($itemtype, $event = '', $options = [])
    {

        if (
            ($itemtype)
            && ($item = getItemForItemtype($itemtype))
        ) {
            return self::getInstance($item, $event, $options);
        }
        return false;
    }


    /**
     * @param $notification Notification object
     **/
    public function showForNotification(Notification $notification)
    {
        if (!Notification::canView()) {
            return false;
        }
        $canedit = false;

        if ($notification->getField('itemtype') != '') {
            $notifications_id = $notification->fields['id'];
            $canedit = $notification->can($notifications_id, UPDATE);

            if ($canedit) {
                echo "<form name='notificationtargets_form' id='notificationtargets_form'
                  method='post' action=' ";
                echo Toolbox::getItemTypeFormURL(__CLASS__) . "'>";
                echo "<input type='hidden' name='notifications_id' value='" . $notification->getField('id') . "'>";
                echo "<input type='hidden' name='itemtype' value='" . $notification->getField('itemtype') . "'>";
            }
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th colspan='4'>" . _n('Recipient', 'Recipients', Session::getPluralNumber()) . "</th></tr>";
            echo "<tr class='tab_bg_2'>";

            $values = [];
            foreach ($this->notification_targets as $key => $val) {
                list($type,$id) = explode('_', $key);
                $values[$key]   = $this->notification_targets_labels[$type][$id];
            }
            $targets = getAllDataFromTable(
                self::getTable(),
                [
                    'notifications_id' => $notifications_id
                ]
            );
            $actives = [];
            if (count($targets)) {
                foreach ($targets as $data) {
                    $actives[$data['type'] . '_' . $data['items_id']] = $data['type'] . '_' . $data['items_id'];
                }
            }

            echo "<td>";
            Dropdown::showFromArray('_targets', $values, ['values'   => $actives,
                'multiple' => true,
                'readonly' => !$canedit
            ]);
            echo "</td>";
            if ($canedit) {
                echo "<td width='20%'>";
                echo "<input type='submit' class='btn btn-primary' name='update' value=\"" . _x('button', 'Update') . "\">";
                echo "</td>";
            }
            echo "</tr>";
            echo "</table>";
        }

        if ($canedit) {
            Html::closeForm();
        }
    }



    /**
     * @param $input
     **/
    public static function updateTargets($input)
    {

        $type   = "";
        $action = "";
        $target = self::getInstanceByType($input['itemtype']);

        if (!isset($input['notifications_id'])) {
            return;
        }
        $targets = getAllDataFromTable(
            self::getTable(),
            [
                'notifications_id' => $input['notifications_id']
            ]
        );
        $actives = [];
        if (count($targets)) {
            foreach ($targets as $data) {
                $actives[$data['type'] . '_' . $data['items_id']] = $data['type'] . '_' . $data['items_id'];
            }
        }
       // Be sure to have items once
        $actives = array_unique($actives);
        if (isset($input['_targets']) && count($input['_targets'])) {
           // Be sure to have items once
            $input['_targets'] = array_unique($input['_targets']);
            foreach ($input['_targets'] as $val) {
               // Add if not set
                if (!isset($actives[$val])) {
                    list($type, $items_id)   = explode("_", $val);
                    $tmp                     = [];
                    $tmp['items_id']         = $items_id;
                    $tmp['type']             = $type;
                    $tmp['notifications_id'] = $input['notifications_id'];
                    $target->add($tmp);
                }
                unset($actives[$val]);
            }
        }

       // Drop others
        if (count($actives)) {
            foreach ($actives as $val) {
                list($type, $items_id) = explode("_", $val);
                if ($target->getFromDBForTarget($input['notifications_id'], $type, $items_id)) {
                    $target->delete(['id' => $target->getID()]);
                }
            }
        }
    }


    public function addAdditionnalInfosForTarget()
    {
    }


    /**
     * @param $data
     *
     * @return empty array
     **/
    public function addAdditionnalUserInfo(array $data)
    {
        return [];
    }


    /**
     * Add new recipient with lang to current recipients array
     *
     * @param array $data Data (users_id, lang[, field used for notification])
     *
     * @return void|false
     **/
    public function addToRecipientsList(array $data)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $new_target = null;
        $new_lang = '';

       // Default USER TYPE is ANONYMOUS
        $notificationoption = ['usertype' => self::ANONYMOUS_USER];

        if (isset($data['language'])) {
            $new_lang = trim($data['language']);
        }
        $username = '';
        if (isset($data['name']) && !empty($data['name'])) {
            $username = $data['name'];
        }
        if (isset($data['users_id']) && ($data['users_id'] > 0)) {
            $user = new User();
            if (
                !$user->getFromDB($data['users_id'])
                || ($user->getField('is_deleted') == 1)
                || ($user->getField('is_active') == 0)
                || (!is_null($user->getField('begin_date'))
                  && ($user->getField('begin_date') > $_SESSION["glpi_currenttime"]))
                || (!is_null($user->getField('end_date'))
                  && ($user->getField('end_date') < $_SESSION["glpi_currenttime"]))
            ) {
               // unknown, deleted or disabled user
                return false;
            }
            $filt = getEntitiesRestrictCriteria(
                'glpi_profiles_users',
                '',
                $this->getEntity(),
                true
            );

            // If one of the item is recursive, then we should also check the child entities
            if ($this->isTargetItemRecursive()) {
                $filt = [
                    'OR' => [
                        $filt,
                        ['entities_id' => getSonsOf(Entity::getTable(), $this->getEntity())]
                    ]
                ];
            }

            $prof = Profile_User::getUserProfiles($data['users_id'], $filt);
            if (!count($prof)) {
                // No right on the entity of the object
                return false;
            }
            if (empty($username)) {
                $username = formatUserName(
                    0,
                    $user->getField('name'),
                    $user->getField('realname'),
                    $user->getField('firstname'),
                    0,
                    0,
                    true
                );
            }
           // It is a GLPI user :
            $notificationoption['usertype'] = self::GLPI_USER;
            if (
                $user->fields['authtype'] == Auth::LDAP
                || Auth::isAlternateAuth($user->fields['authtype'])
                || (($user->fields['authtype'] == Auth::NOT_YET_AUTHENTIFIED)
                 && Auth::isAlternateAuth(Auth::checkAlternateAuthSystems()))
            ) {
                $notificationoption['usertype'] = self::EXTERNAL_USER;
            }

           // retrieve timezone of the user if exists
            if (!empty($user->fields['timezone']) && 'null' !== strtolower($user->fields['timezone'])) {
                $notificationoption['timezone'] = $user->fields['timezone'];
            }
        }

       // Pass user type as argument ? forced for specific cases
        if (isset($data['usertype'])) {
            $notificationoption['usertype'] = $data['usertype'];
        }

        $notificationoption = array_merge(
            $this->addAdditionnalUserInfo($data),
            $notificationoption
        );

        $param = [
            'language'           => (empty($new_lang) ? $CFG_GLPI["language"] : $new_lang),
            'additionnaloption'  => $notificationoption,
            'username'           => $username
        ];
        if (isset($data['users_id']) && $data['users_id']) {
            $param['users_id'] = $data['users_id'];
        }

        $eventclass = $this->event;
        $target_field = $eventclass::getTargetField($data, $param);
        if ($data[$target_field] !== null) {
            $param[$target_field] = $data[$target_field];
            $this->target[$data[$target_field]] = $param;
        }

        if (isset($data['users_id']) && $data['users_id']) {
            $this->recipient_data = [
                'itemtype' => User::class,
                'items_id' => $data['users_id'],
            ];
            Plugin::doHook(Hooks::ADD_RECIPIENT_TO_TARGET, $this);
            unset($this->recipient_data);
        }
    }


    /**
     * @since 0.84
     **/
    public function getDefaultUserType()
    {

        if (Auth::isAlternateAuth(Auth::checkAlternateAuthSystems())) {
            return self::EXTERNAL_USER;
        }
        return self::GLPI_USER;
    }


    /**
     * @since 0.84
     *
     * @param $usertype
     * @param $redirect
     **/
    public function formatURL($usertype, $redirect)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (urldecode($redirect) === $redirect) {
            // `redirect` parameter value have to be url-encoded.
            // Prior to GLPI 10.0.3, method caller was responsible of this encoding,
            // so we have to ensure that param is not already encoded before encoding it,
            // to prevent BC breaks.
            $redirect = rawurlencode($redirect);
        }

        switch ($usertype) {
            case self::EXTERNAL_USER:
                return $CFG_GLPI["url_base"] . "/index.php?redirect=$redirect";

            case self::ANONYMOUS_USER:
               // No URL
                return '';

            case self::GLPI_USER:
                return $CFG_GLPI["url_base"] . "/index.php?redirect=$redirect&noAUTO=1";
        }
    }


    /**
     * Add GLPI's global administrator email
     *
     * @return void
     */
    final public function addAdmin()
    {
        $eventclass = $this->event;
        $admin_data = $eventclass::getAdminData();

        if ($admin_data) {
            if (!isset($admin_data['users_id']) && !isset($admin_data['usertype'])) {
                $admin_data['usertype'] = $this->getDefaultUserType();
            }
            $this->addToRecipientsList($admin_data);
        }
    }


    /**
     * Add item's author
     *
     * @since 9.2
     *
     * @return void
     */
    public function addItemAuthor()
    {
        $user = new User();
        if (
            $this->obj->isField('users_id')
            && $user->getFromDB($this->obj->getField('users_id'))
        ) {
            $this->addToRecipientsList([
                'language' => $user->getField('language'),
                'users_id' => $user->getField('id')
            ]);
        }
    }


    /**
     * Add item's group
     *
     * @since 9.2
     *
     * @return void
     */
    final public function addItemGroup()
    {

        if (!empty($this->target_object)) {
            foreach ($this->target_object as $val) {
                if ($val->fields['groups_id'] > 0) {
                    $this->addForGroup(0, $val->fields['groups_id']);
                }
            }
        }
    }


    /**
     * Add item's group supervisor
     *
     * @since 9.2
     *
     * @return void
     */
    final public function addItemGroupSupervisor()
    {
        if (!empty($this->target_object)) {
            foreach ($this->target_object as $val) {
                if ($val->fields['groups_id'] > 0) {
                    $this->addForGroup(1, $val->fields['groups_id']);
                }
            }
        }
    }


    /**
     * Add item's group users exepted supervisor
     *
     * @since 9.2
     *
     * @return void
     */
    final public function addItemGroupWithoutSupervisor()
    {

        if (!empty($this->target_object)) {
            foreach ($this->target_object as $val) {
                if ($val->fields['groups_id'] > 0) {
                    $this->addForGroup(2, $val->fields['groups_id']);
                }
            }
        }
    }


    /**
     * Add entity admin
     *
     * @return void
     */
    final public function addEntityAdmin()
    {
        $eventclass = $this->event;
        $admins_data = $eventclass::getEntityAdminsData($this->entity);

        if ($admins_data) {
            foreach ($admins_data as $admin_data) {
                if (!isset($admin_data['users_id']) && !isset($admin_data['usertype'])) {
                    $admin_data['usertype'] = $this->getDefaultUserType();
                }
                $this->addToRecipientsList($admin_data);
            }
        }
    }


    /**
     * Add users of a group to targets
     *
     * @param integer $manager  0 all users, 1 only supervisors, 2 all users without supervisors
     * @param integer $group_id id of the group
     *
     * @since 9.2
     *
     * @return void
     **/
    final public function addForGroup($manager, $group_id)
    {
        /** @var \DBmysql $DB */
        global $DB;

       // members/managers of the group allowed on object entity
       // filter group with 'is_assign' (attribute can be unset after notification)
        $criteria = $this->getDistinctUserCriteria() + $this->getProfileJoinCriteria();
        $criteria['FROM'] = Group_User::getTable();
        $criteria['INNER JOIN'] = array_merge(
            [
                User::getTable() => [
                    'ON' => [
                        Group_User::getTable()  => 'users_id',
                        User::getTable()        => 'id'
                    ]
                ],
                Group::getTable() => [
                    'ON' => [
                        Group_User::getTable()  => 'groups_id',
                        Group::getTable()       => 'id'
                    ]
                ]
            ],
            $criteria['INNER JOIN'] ?? []
        );
        $criteria['WHERE'] = array_merge(
            $criteria['WHERE'] ?? [],
            [
                Group_User::getTable() . '.groups_id'  => $group_id,
                Group::getTable() . '.is_notify'       => 1,
            ]
        );

        if ($manager == 1) {
            $criteria['WHERE']['glpi_groups_users.is_manager'] = 1;
        } else if ($manager == 2) {
            $criteria['WHERE']['glpi_groups_users.is_manager'] = 0;
        }

        $iterator = $DB->request($criteria);
        foreach ($iterator as $data) {
            $this->addToRecipientsList($data);
        }

        if ($manager != 1) {
           // Do not consider it as a group notification if it only targets supervisor
            $this->recipient_data = [
                'itemtype' => Group::class,
                'items_id' => $group_id,
            ];
            Plugin::doHook(Hooks::ADD_RECIPIENT_TO_TARGET, $this);
            unset($this->recipient_data);
        }
    }


    /**
     * Get request criteria to select uniques users
     *
     * @since 9.4
     *
     * @return array
     */
    final public function getDistinctUserCriteria()
    {
        return [
            'FIELDS'          => [
                User::getTable() . '.id AS users_id',
                User::getTable() . '.language AS language'
            ],
            'DISTINCT'        => true,
        ];
    }


    /**
     * Return main notification events for the object type
     * Internal use only => should use getAllEvents
     *
     * @return array an array which contains : event => event label
     **/
    public function getEvents()
    {
        return [];
    }


    /**
     * Return all (GLPI + plugins) notification events for the object type
     *
     * @return array which contains : event => event label
     **/
    public function getAllEvents()
    {

        $this->events = $this->getEvents();
       //If plugin adds new events for an already defined type
        Plugin::doHook(Hooks::ITEM_GET_EVENTS, $this);

        return $this->events;
    }


    /**
     * @param $target    (default '')
     * @param $label     (default '')
     * @param $type      (=Notification::USER_TYPE)
     **/
    public function addTarget($target = '', $label = '', $type = Notification::USER_TYPE)
    {

        $key                                               = $type . '_' . $target;
       // Value used for sort
        $this->notification_targets[$key]                  = $type . '_' . $label;
       // Displayed value
        $this->notification_targets_labels[$type][$target] = $label;
    }


    public function addProfilesToTargets()
    {
        /** @var \DBmysql $DB */
        global $DB;

        foreach ($DB->request('glpi_profiles') as $data) {
            $this->addTarget(
                $data["id"],
                sprintf(__('%1$s: %2$s'), Profile::getTypeName(1), $data["name"]),
                Notification::PROFILE_TYPE
            );
        }
    }


    /**
     * @param $entity
     **/
    final public function addGroupsToTargets($entity)
    {
        /** @var \DBmysql $DB */
        global $DB;

       // Filter groups which can be notified and have members (as notifications are sent to members)
        $iterator = $DB->request([
            'SELECT' => ['id', 'name'],
            'FROM'   => Group::getTable(),
            'WHERE'  => [
                'is_usergroup' => 1,
                'is_notify'    => 1
            ] + getEntitiesRestrictCriteria('glpi_groups', 'entities_id', $entity, true),
            'ORDER'  => 'name'
        ]);

        foreach ($iterator as $data) {
           //Add group
            $this->addTarget(
                $data["id"],
                sprintf(__('%1$s: %2$s'), Group::getTypeName(1), $data["name"]),
                Notification::GROUP_TYPE
            );
           //Add group supervisor
            $this->addTarget(
                $data["id"],
                sprintf(
                    __('%1$s: %2$s'),
                    __('Manager of group'),
                    $data["name"]
                ),
                Notification::SUPERVISOR_GROUP_TYPE
            );
           //Add group without supervisor
            $this->addTarget(
                $data["id"],
                sprintf(
                    __('%1$s: %2$s'),
                    __("Group except manager users"),
                    $data["name"]
                ),
                Notification::GROUP_WITHOUT_SUPERVISOR_TYPE
            );
        }
    }


    /**
     * Add all targets for this notification
     *
     * Can be updated by implementing the addAdditionnalTargets() method
     * Can be overriden (like dbconnection)
     *
     * @param integer $entity the entity on which the event is raised
     *
     * @return void
     **/
    public function addNotificationTargets($entity)
    {

        if (Session::haveRight("config", UPDATE)) {
            $this->addTarget(Notification::GLOBAL_ADMINISTRATOR, __('Administrator'));
        }
        $this->addTarget(Notification::ENTITY_ADMINISTRATOR, __('Entity administrator'));

        $this->addProfilesToTargets();
        $this->addGroupsToTargets($entity);
    }


    /**
     * Allows to add more notification targets
     * Can be overridden in some case (for example Ticket)
     *
     * @param string $event specif event to get additional targets (default '')
     *
     * @return void
     */
    public function addAdditionalTargets($event = '')
    {
    }


    /**
     * Add targets by a method not defined in NotificationTarget (specific to an itemtype)
     *
     * @param array $data    Data
     * @param array $options Options
     *
     * @return void
     **/
    public function addSpecificTargets($data, $options)
    {
    }


    /**
     * Fetch item associated with the object on which the event was raised
     *
     * @param $event  (default '')
     *
     * @return void
     **/
    public function getObjectItem($event = '')
    {
        $this->target_object[] = $this->obj;
    }


    /**
     * Add user to the notified users list
     *
     * @param string  $field            look for user looking for this field in the object
     *                                  which raises the event
     * @param boolean $search_in_object search is done in the object ? if not  in target object
     *                                  (false by default)
     *
     * @return void
     **/
    final public function addUserByField($field, $search_in_object = false)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $id = [];
        if (!$search_in_object) {
            $id[] = $this->obj->getField($field);
        } else if (!empty($this->target_object)) {
            foreach ($this->target_object as $val) {
                $id[] = $val->fields[$field];
            }
        }

        if (!empty($id)) {
           //Look for the user by his id
            $criteria = $this->getDistinctUserCriteria() + $this->getProfileJoinCriteria();
            $criteria['FROM'] = User::getTable();
            $criteria['WHERE'][User::getTable() . '.id'] = $id;
            $iterator = $DB->request($criteria);

            foreach ($iterator as $data) {
               //Add the user email and language in the notified users list
                $this->addToRecipientsList($data);
            }
        }
    }


    /**
     * Add technician in charge of the item
     *
     * @return void
     **/
    final public function addItemTechnicianInCharge()
    {
        $this->addUserByField('users_id_tech', true);
    }


    /**
     * Add group of technicians in charge of the item
     *
     * @return void
     */
    final public function addItemGroupTechInCharge()
    {
        if (!empty($this->target_object)) {
            foreach ($this->target_object as $val) {
                if (isset($val->fields['groups_id_tech']) && $val->fields['groups_id_tech'] > 0) {
                    $this->addForGroup(0, $val->fields['groups_id_tech']);
                }
            }
        }
    }


    /**
     * Add owner of the material
     *
     * @return void
     **/
    final public function addItemOwner()
    {
        $this->addUserByField('users_id', true);
    }


    /**
     * Add users from a profile
     *
     * @param integer $profiles_id the profile ID
     *
     * @return void
     */
    final public function addForProfile($profiles_id)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $criteria = $this->getDistinctUserCriteria() + $this->getProfileJoinCriteria();
        $criteria['FIELDS'][] = Profile_User::getTable() . '.entities_id AS entity';
        $criteria['FROM'] = User::getTable();
        $criteria['WHERE'][Profile_User::getTable() . '.profiles_id'] = $profiles_id;

        $iterator = $DB->request($criteria);
        foreach ($iterator as $data) {
            $this->addToRecipientsList($data);
        }

        $this->recipient_data = [
            'itemtype' => Profile::class,
            'items_id' => $profiles_id,
        ];
        Plugin::doHook(Hooks::ADD_RECIPIENT_TO_TARGET, $this);
        unset($this->recipient_data);
    }


    /**
     * Get email to use as a sender for the notifications
     *
     * @return array [email => sender address, name => sender name]
     */
    public function getSender(): array
    {
        // Compute config values
        return Config::getEmailSender(
            $this->getEntity(),
            !$this->allowResponse()
        );
    }


    /**
     * Get the reply to address
     *
     * @return array [email => replyto address, name => replyto name]
     **/
    public function getReplyTo(): array
    {
        if (!$this->allowResponse()) {
            return Config::getNoReplyEmailSender($this->getEntity());
        }
        return Config::getReplyToEmailSender($this->getEntity());
    }


    /**
     * Add addresses according to type of notification
     *
     * @param array $data    Data
     * @param array $options Option
     *
     * @return void
     **/
    final public function addForTarget($data, $options = [])
    {

       //Look for all targets whose type is Notification::USER_TYPE
        switch ($data['type']) {
           //Notifications for one people
            case Notification::USER_TYPE:
                switch ($data['items_id']) {
                   //Send to glpi's global admin (as defined in the mailing configuration)
                    case Notification::GLOBAL_ADMINISTRATOR:
                        if ($this->isMailMode()) {
                            $this->addAdmin();
                        }
                        break;

                   //Send to the entity's admninistrator
                    case Notification::ENTITY_ADMINISTRATOR:
                        if ($this->isMailMode()) {
                             $this->addEntityAdmin();
                        }
                        break;

                   //Technician in charge of the ticket
                    case Notification::ITEM_TECH_IN_CHARGE:
                        $this->addItemTechnicianInCharge();
                        break;

                 //Group of technician in charge of the ticket
                    case Notification::ITEM_TECH_GROUP_IN_CHARGE:
                        $this->addItemGroupTechInCharge();
                        break;

                 //User who's owner of the material
                    case Notification::ITEM_USER:
                          $this->addItemOwner();
                        break;

                 //Send to the author of the ticket
                    case Notification::AUTHOR:
                        $this->addItemAuthor();
                        break;

                    default:
                        //Maybe a target specific to a type
                        $this->addSpecificTargets($data, $options);
                }
                break;

           //Send to all the users of a group
            case Notification::GROUP_TYPE:
                $this->addForGroup(0, $data['items_id']);
                break;

           //Send to all the users of a group
            case Notification::SUPERVISOR_GROUP_TYPE:
                $this->addForGroup(1, $data['items_id']);
                break;

           //Send to all the users of a profile
            case Notification::PROFILE_TYPE:
                $this->addForProfile($data['items_id']);
                break;

            default:
               //Maybe a target specific to a type
                $this->addSpecificTargets($data, $options);
        }
       // action for target from plugin
        $this->data = $data;
        Plugin::doHook(Hooks::ITEM_ACTION_TARGETS, $this);
    }


    /**
     * Get all data needed for template processing
     * Provides minimum information for alerts
     * Can be overridden by each NotificationTartget class if needed
     *
     * @param string $event   Event name
     * @param array  $options Options
     *
     * @return void
     **/
    public function addDataForTemplate($event, $options = [])
    {
    }


    final public function getTargets()
    {
        return $this->target;
    }


    public function getEntity()
    {
        return $this->entity;
    }


    public function clearAddressesList()
    {
        $this->target = [];
    }


    /**
     * Get SQL join to restrict by profile and by config to avoid send notification
     * to a user without rights.
     *
     * @return string
     */
    public function getProfileJoinCriteria()
    {
        $criteria = [
            'INNER JOIN'   => [
                Profile_User::getTable() => [
                    'ON' => [
                        Profile_User::getTable()   => 'users_id',
                        User::getTable()           => 'id'
                    ]
                ]
            ],
            'WHERE' => getEntitiesRestrictCriteria(
                Profile_User::getTable(),
                'entities_id',
                $this->getEntity(),
                true
            )
        ];

        if ($this->isTargetItemRecursive()) {
            $criteria['WHERE'] = [
                'OR' => [
                    $criteria['WHERE'],
                    [
                        Profile_User::getTableField('entities_id') => getSonsOf(Entity::getTable(), $this->getEntity())
                    ]
                ]
            ];
        }

        return $criteria;
    }


    /**
     * @param $event
     * @param $options
     **/
    public function &getForTemplate($event, $options)
    {
        $this->data = [];

        $this->addDataForTemplate($event, $options);

        // Add global tags data, use `+` operator to preserve overriden values
        $this->data += $this->getGlobalTagsData();

        Plugin::doHook(Hooks::ITEM_GET_DATA, $this);

        return $this->data;
    }

    /**
     * Register global tags.
     *
     * @return void
     */
    private function registerGlobalTags(): void
    {
        $this->addTagToList([
            'tag'   => 'glpi.url',
            'value' => true,
            'label' => __('URL of the application'),
            'lang'  => false,
        ]);
    }

    /**
     * Define global tags data.
     *
     * @return array
     */
    private function getGlobalTagsData(): array
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        return [
            '##glpi.url##' => $CFG_GLPI['url_base'],
        ];
    }


    public function getTags()
    {
        return $this->tag_descriptions;
    }


    /**
     * @param $options   array
     **/
    public function addTagToList($options = [])
    {

        $p['tag']            = false;
        $p['value']          = true;
        $p['label']          = false;
        $p['events']         = self::TAG_FOR_ALL_EVENTS;
        $p['foreach']        = false;
        $p['lang']           = true;
        $p['allowed_values'] = [];

        foreach ($options as $key => $value) {
            $p[$key] = $value;
        }

        if ($p['tag']) {
            if (is_array($p['events'])) {
                $events = $this->getEvents();
                $tmp = [];

                foreach ($p['events'] as $event) {
                    $tmp[$event] = $events[$event];
                }

                $p['events'] = $tmp;
            }

            if ($p['foreach']) {
                $tag = "##FOREACH" . $p['tag'] . "## ##ENDFOREACH" . $p['tag'] . "##";
                $this->tag_descriptions[self::TAG_VALUE][$tag] = $p;
                $tag = "##FOREACH FIRST &lt;number&gt; " . $p['tag'] . "## ##ENDFOREACH" . $p['tag'] . "##";
                $this->tag_descriptions[self::TAG_VALUE][$tag] = $p;
                $tag = "##FOREACH LAST &lt;number&gt; " . $p['tag'] . "## ##ENDFOREACH" . $p['tag'] . "##";
                $this->tag_descriptions[self::TAG_VALUE][$tag] = $p;
            } else {
                if ($p['value']) {
                    $tag = "##" . $p['tag'] . "##";
                    $this->tag_descriptions[self::TAG_VALUE][$tag] = $p;
                }

                if ($p['label'] && $p['lang']) {
                    $tag = "##lang." . $p['tag'] . "##";
                    $this->tag_descriptions[self::TAG_LANGUAGE][$tag] = $p;
                }
            }
        }
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate && Notification::canView()) {
            $nb = 0;
            switch ($item->getType()) {
                case 'Group':
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = self::countForGroup($item);
                    }
                    return self::createTabEntry(
                        Notification::getTypeName(Session::getPluralNumber()),
                        $nb
                    );

                case 'Notification':
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(
                            $this->getTable(),
                            ['notifications_id' => $item->getID()]
                        );
                    }
                    return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
            }
        }
        return '';
    }


    /**
     * Count Notification for a group
     *
     * @since 0.83
     *
     * @param $group Group object
     *
     * @return integer
     **/
    public static function countForGroup(Group $group)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $count = $DB->request([
            'COUNT'        => 'cpt',
            'FROM'         => self::getTable(),
            'INNER JOIN'   => [
                Notification::getTable()   => [
                    'ON'  => [
                        Notification::getTable()   => 'id',
                        self::getTable()           => 'notifications_id'
                    ]
                ]
            ],
            'WHERE'        => [
                'type'      => [
                    Notification::SUPERVISOR_GROUP_TYPE,
                    Notification::GROUP_TYPE
                ],
                'items_id'  => $group->getID()
            ] + getEntitiesRestrictCriteria(Notification::getTable(), '', '', true)
        ])->current();
        return $count['cpt'];
    }


    /**
     * Display notification registered for a group
     *
     * @since 0.83
     *
     * @param $group Group object
     *
     * @return void
     **/
    public static function showForGroup(Group $group)
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (!Notification::canView()) {
            return false;
        }

        $iterator = $DB->request([
            'SELECT'       => [Notification::getTable() . '.id'],
            'FROM'         => self::getTable(),
            'INNER JOIN'   => [
                Notification::getTable() => [
                    'ON' => [
                        self::getTable()           => 'notifications_id',
                        Notification::getTable()   => 'id'
                    ]
                ]
            ],
            'WHERE'        => [
                'type'      => [
                    Notification::SUPERVISOR_GROUP_TYPE,
                    Notification::GROUP_TYPE
                ],
                'items_id'  => $group->getID()
            ] + getEntitiesRestrictCriteria(Notification::getTable(), '', '', true)
        ]);

        echo "<table class='tab_cadre_fixe'>";

        if (count($iterator)) {
            echo "<tr><th>" . __('Name') . "</th>";
            echo "<th>" . Entity::getTypeName(1) . "</th>";
            echo "<th>" . __('Active') . "</th>";
            echo "<th>" . _n('Type', 'Types', 1) . "</th>";
            echo "<th>" . __('Notification method') . "</th>";
            echo "<th>" . NotificationEvent::getTypeName(1) . "</th>";
            echo "<th>" . NotificationTemplate::getTypeName(1) . "</th></tr>";

            $notif = new Notification();

            Session::initNavigateListItems(
                'Notification',
                //TRANS : %1$s is the itemtype name, %2$s is the name of the item (used for headings of a list)
                                        sprintf(
                                            __('%1$s = %2$s'),
                                            Group::getTypeName(1),
                                            $group->getName()
                                        )
            );

            foreach ($iterator as $data) {
                 Session::addToNavigateListItems('Notification', $data['id']);

                if ($notif->getFromDB($data['id'])) {
                    echo "<tr class='tab_bg_2'><td>" . $notif->getLink();
                    echo "</td><td>" . Dropdown::getDropdownName('glpi_entities', $notif->getEntityID());
                    echo "</td><td>" . Dropdown::getYesNo($notif->getField('is_active')) . "</td><td>";
                    $itemtype = $notif->getField('itemtype');
                    if ($tmp = getItemForItemtype($itemtype)) {
                        echo $tmp->getTypeName(1);
                    } else {
                        echo "&nbsp;";
                    }
                    echo "</td><td>" . Notification_NotificationTemplate::getMode($notif->getField('mode'));
                    echo "</td><td>" . NotificationEvent::getEventName(
                        $itemtype,
                        $notif->getField('event')
                    );
                    echo "</td>" .
                       "<td>" . Dropdown::getDropdownName(
                           'glpi_notificationtemplates',
                           $notif->getField('notificationtemplates_id')
                       );
                    echo "</td></tr>";
                }
            }
        } else {
            echo "<tr class='tab_bg_2'><td class='b center'>" . __('No item found') . "</td></tr>";
        }
        echo "</table>";
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item->getType() == 'Group') {
            self::showForGroup($item);
        } else if ($item->getType() == 'Notification') {
            $target = self::getInstanceByType(
                $item->getField('itemtype'),
                $item->getField('event'),
                ['entities_id' => $item->getField('entities_id')]
            );
            if ($target) {
                $target->showForNotification($item);
            }
        }
        return true;
    }

    /**
     * Set mode
     *
     * @param string $mode Mode (see Notification_NotificationTemplate::MODE_*)
     *
     * @return NotificationTarget
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
        return $this;
    }

    /**
     * Get mode
     *
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Is current mode for mail
     *
     * @return boolean
     */
    protected function isMailMode()
    {
        return ($this->mode == Notification_NotificationTemplate::MODE_MAIL);
    }

    /**
     * Set event
     *
     * @param string $event Event class
     *
     * @return NotificationTarget
     */
    public function setEvent($event)
    {
        $this->event = $event;
        return $this;
    }


    /**
     * Get the value of allow_response
     */
    public function allowResponse()
    {
        return $this->allow_response;
    }

    /**
     * Set the value of allow_response
     *
     * @return self
     */
    public function setAllowResponse($allow_response)
    {
        $this->allow_response = $allow_response;

        return $this;
    }

    /**
     * Check if at least one target item is recursive
     *
     * @return bool
     */
    protected function isTargetItemRecursive(): bool
    {
        // If the notification target more than one item, we can't handle the
        // entity restriction correctly and must discard any potential child
        // entities check
        if (count($this->target_object) > 1) {
            return false;
        }

        // Not all items support recursion
        if (!($this->obj instanceof CommonDBTM)  || !$this->obj->maybeRecursive()) {
            return false;
        }

        return $this->obj->isRecursive();
    }
}
