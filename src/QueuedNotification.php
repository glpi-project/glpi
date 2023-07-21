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

use Glpi\Toolbox\Sanitizer;

/** QueuedNotification class
 *
 * @since 0.85
 **/
class QueuedNotification extends CommonDBTM
{
    public static $rightname = 'queuednotification';


    public static function getTypeName($nb = 0)
    {
        return __('Notification queue');
    }


    public static function canCreate()
    {
       // Everybody can create : human and cron
        return Session::getLoginUserID(false);
    }


    public static function getForbiddenActionsForMenu()
    {
        return ['add'];
    }


    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }

    public function getForbiddenSingleMassiveActions()
    {
        $forbidden = parent::getForbiddenSingleMassiveActions();

        if ($this->fields['mode'] === Notification_NotificationTemplate::MODE_AJAX) {
            $forbidden[] = __CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'send';
        }

        return $forbidden;
    }

    /**
     * @see CommonDBTM::getSpecificMassiveActions()
     **/
    public function getSpecificMassiveActions($checkitem = null, $is_deleted = false)
    {

        $isadmin = static::canUpdate();
        $actions = parent::getSpecificMassiveActions($checkitem);

        if ($isadmin && !$is_deleted) {
            $actions[__CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'send'] = _x('button', 'Send');
        }

        return $actions;
    }


    /**
     * @see CommonDBTM::processMassiveActionsForOneItemtype()
     **/
    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {
        switch ($ma->getAction()) {
            case 'send':
                foreach ($ids as $id) {
                    if ($item->canEdit($id)) {
                        if ($item->fields['mode'] === Notification_NotificationTemplate::MODE_AJAX) {
                            $ma->itemDone($item->getType(), $id, MassiveAction::NO_ACTION);
                        } elseif ($item->sendById($id)) {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                        } else {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        }
                    } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                    }
                }
                return;
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }


    public function prepareInputForAdd($input)
    {
        global $DB;

        if (!isset($input['create_time']) || empty($input['create_time'])) {
            $input['create_time'] = $_SESSION["glpi_currenttime"];
        }
        if (!isset($input['send_time']) || empty($input['send_time'])) {
            $toadd = 0;
            if (isset($input['entities_id'])) {
                $toadd = Entity::getUsedConfig('delay_send_emails', $input['entities_id']);
            }
            if ($toadd > 0) {
                $input['send_time'] = date(
                    "Y-m-d H:i:s",
                    strtotime($_SESSION["glpi_currenttime"])
                    + $toadd * MINUTE_TIMESTAMP
                );
            } else {
                $input['send_time'] = $_SESSION["glpi_currenttime"];
            }
        }
        $input['sent_try'] = 0;
        if (isset($input['headers']) && is_array($input['headers']) && count($input['headers'])) {
            $input["headers"] = exportArrayToDB($input['headers']);
        } else {
            $input['headers'] = '';
        }

        if (isset($input['documents']) && is_array($input['documents']) && count($input['documents'])) {
            $input["documents"] = exportArrayToDB($input['documents']);
        } else {
            $input['documents'] = '';
        }

       // Force items_id to integer
        if (!isset($input['items_id']) || empty($input['items_id'])) {
            $input['items_id'] = 0;
        }

       // Drop existing mails in queue for the same event and item  and recipient
        $item = isset($input['itemtype']) ? getItemForItemtype($input['itemtype']) : false;
        if (
            $item instanceof CommonDBTM && $item->deduplicate_queued_notifications
            && isset($input['entities_id']) && ($input['entities_id'] >= 0)
            && isset($input['items_id']) && ($input['items_id'] >= 0)
            && isset($input['notificationtemplates_id']) && !empty($input['notificationtemplates_id'])
            && isset($input['recipient'])
        ) {
            $criteria = [
                'FROM'   => $this->getTable(),
                'WHERE'  => [
                    'is_deleted'   => 0,
                    'itemtype'     => $input['itemtype'],
                    'items_id'     => $input['items_id'],
                    'entities_id'  => $input['entities_id'],
                    'notificationtemplates_id' => $input['notificationtemplates_id'],
                    'recipient'                => $input['recipient']

                ]
            ];
            $iterator = $DB->request($criteria);
            foreach ($iterator as $data) {
                $this->delete(['id' => $data['id']], 1);
            }
        }

        return $input;
    }


    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics')
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => $this->getTable(),
            'field'              => 'name',
            'name'               => __('Subject'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number'
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => $this->getTable(),
            'field'              => 'create_time',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'send_time',
            'name'               => __('Expected send date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => $this->getTable(),
            'field'              => 'sent_time',
            'name'               => __('Send date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'sender',
            'name'               => __('Sender email'),
            'datatype'           => 'text',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => $this->getTable(),
            'field'              => 'sendername',
            'name'               => __('Sender name'),
            'datatype'           => 'string',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => $this->getTable(),
            'field'              => 'recipient',
            'name'               => __('Recipient email'),
            'datatype'           => 'string',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '8',
            'table'              => $this->getTable(),
            'field'              => 'recipientname',
            'name'               => __('Recipient name'),
            'datatype'           => 'string',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '9',
            'table'              => $this->getTable(),
            'field'              => 'replyto',
            'name'               => __('Reply-To email'),
            'datatype'           => 'string',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '10',
            'table'              => $this->getTable(),
            'field'              => 'replytoname',
            'name'               => __('Reply-To name'),
            'datatype'           => 'string',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '11',
            'table'              => $this->getTable(),
            'field'              => 'headers',
            'name'               => __('Additional headers'),
            'datatype'           => 'specific',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '12',
            'table'              => $this->getTable(),
            'field'              => 'body_html',
            'name'               => __('Email HTML body'),
            'datatype'           => 'text',
            'massiveaction'      => false,
            'htmltext'           => true
        ];

        $tab[] = [
            'id'                 => '13',
            'table'              => $this->getTable(),
            'field'              => 'body_text',
            'name'               => __('Email text body'),
            'datatype'           => 'text',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '14',
            'table'              => $this->getTable(),
            'field'              => 'messageid',
            'name'               => __('Message ID'),
            'datatype'           => 'string',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '15',
            'table'              => $this->getTable(),
            'field'              => 'sent_try',
            'name'               => __('Number of tries of sent'),
            'datatype'           => 'integer',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '20',
            'table'              => $this->getTable(),
            'field'              => 'itemtype',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'itemtypename',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '21',
            'table'              => $this->getTable(),
            'field'              => 'items_id',
            'name'               => __('Associated item ID'),
            'massiveaction'      => false,
            'datatype'           => 'integer'
        ];

        $tab[] = [
            'id'                 => '22',
            'table'              => 'glpi_notificationtemplates',
            'field'              => 'name',
            'name'               => _n('Notification template', 'Notification templates', 1),
            'massiveaction'      => false,
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '23',
            'table'              => 'glpi_queuednotifications',
            'field'              => 'mode',
            'name'               => __('Mode'),
            'massiveaction'      => false,
            'datatype'           => 'specific',
            'searchtype'         => [
                0 => 'equals',
                1 => 'notequals'
            ]
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'massiveaction'      => false,
            'datatype'           => 'dropdown'
        ];

        return $tab;
    }


    /**
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
            case 'headers':
                $values[$field] = importArrayFromDB($values[$field]);
                $out = '';
                if (is_array($values[$field]) && count($values[$field])) {
                    foreach ($values[$field] as $key => $val) {
                        $out .= $key . ': ' . $val . '<br>';
                    }
                }
                return $out;
            break;
            case 'mode':
                $out = Notification_NotificationTemplate::getMode($values[$field])['label'];
                return $out;
            break;
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }


    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;

        switch ($field) {
            case 'mode':
                $options['name']  = $name;
                $options['value'] = $values[$field];
                return Notification_NotificationTemplate::dropdownMode($options);
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }


    /**
     * Send notification in queue
     *
     * @param integer $ID Id
     *
     * @return boolean
     */
    public function sendById($ID)
    {
        if ($this->getFromDB($ID)) {
            $this->fields = Sanitizer::unsanitize($this->fields);

            $mode = $this->getField('mode');
            $eventclass = 'NotificationEvent' . ucfirst($mode);
            $conf = Notification_NotificationTemplate::getMode($mode);
            if ($conf['from'] != 'core') {
                $eventclass = 'Plugin' . ucfirst($conf['from']) . $eventclass;
            }

            return $eventclass::send([$this->fields]);
        } else {
            return false;
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
            case 'queuednotification':
                return ['description' => __('Send mails in queue'),
                    'parameter'   => __('Maximum emails to send at once')
                ];

            case 'queuednotificationclean':
                return ['description' => __('Clean notification queue'),
                    'parameter'   => __('Days to keep sent emails')
                ];
        }
        return [];
    }


    /**
     * Get pending notifications in queue
     *
     * @param string  $send_time   Maximum sent_time
     * @param integer $limit       Query limit clause
     * @param array   $limit_modes Modes to limit to
     * @param array   $extra_where Extra params to add to the where clause
     *
     * @return array
     */
    public static function getPendings($send_time = null, $limit = 20, $limit_modes = null, $extra_where = [])
    {
        global $DB, $CFG_GLPI;

        if ($send_time === null) {
            $send_time = date('Y-m-d H:i:s');
        }

        $base_query = [
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'is_deleted'   => 0,
                'mode'         => 'TOFILL',
                'send_time'    => ['<', $send_time],
            ] +  $extra_where,
            'ORDER'  => 'send_time ASC',
            'START'  => 0,
            'LIMIT'  => $limit
        ];

        $pendings = [];
        $modes = Notification_NotificationTemplate::getModes();
        foreach ($modes as $mode => $conf) {
            $eventclass = 'NotificationEvent' . ucfirst($mode);
            if ($conf['from'] != 'core') {
                $eventclass = 'Plugin' . ucfirst($conf['from']) . $eventclass;
            }

            if (
                $limit_modes !== null && !in_array($mode, $limit_modes)
                || !$CFG_GLPI['notifications_' . $mode]
                || !$eventclass::canCron()
            ) {
               //mode is not in limits, is disabled, or cannot be called from cron, passing
                continue;
            }

            $query = $base_query;
            $query['WHERE']['mode'] = $mode;

            $iterator = $DB->request($query);
            if ($iterator->numRows() > 0) {
                $pendings[$mode] = [];
                foreach ($iterator as $row) {
                    $pendings[$mode][] = $row;
                }
            }
        }

        return $pendings;
    }


    /**
     * Cron action on notification queue: send notifications in queue
     *
     * @param CommonDBTM $task for log (default NULL)
     *
     * @return integer either 0 or 1
     **/
    public static function cronQueuedNotification($task = null)
    {
        if (!Notification_NotificationTemplate::hasActiveMode()) {
            return 0;
        }
        $cron_status = 0;

       // Send notifications at least 1 minute after adding in queue to be sure that process on it is finished
        $send_time = date("Y-m-d H:i:s", strtotime("+1 minutes"));

        $pendings = self::getPendings(
            $send_time,
            $task->fields['param']
        );

        foreach ($pendings as $mode => $data) {
            $data = Sanitizer::unsanitize($data);

            $eventclass = 'NotificationEvent' . ucfirst($mode);
            $conf = Notification_NotificationTemplate::getMode($mode);
            if ($conf['from'] != 'core') {
                $eventclass = 'Plugin' . ucfirst($conf['from']) . $eventclass;
            }

            $result = $eventclass::send($data);
            if ($result !== false) {
                $cron_status = 1;
                if (!is_null($task)) {
                    $task->addVolume($result);
                }
            }
        }

        return $cron_status;
    }


    /**
     * Cron action on queued notification: clean notification queue
     *
     * @param CommonDBTM $task for log (default NULL)
     *
     * @return integer either 0 or 1
     **/
    public static function cronQueuedNotificationClean($task = null)
    {
        global $DB;

        $vol = 0;

       // Expire mails in queue
        if ($task->fields['param'] > 0) {
            $secs      = $task->fields['param'] * DAY_TIMESTAMP;
            $send_time = date("U") - $secs;
            $DB->delete(
                self::getTable(),
                [
                    'is_deleted'   => 1,
                    new \QueryExpression('(UNIX_TIMESTAMP(' . $DB->quoteName('send_time') . ') < ' . $DB->quoteValue($send_time) . ')')
                ]
            );
            $vol = $DB->affectedRows();
        }

        $task->setVolume($vol);
        return ($vol > 0 ? 1 : 0);
    }


    /**
     * Force sending all mails in queue for a specific item
     *
     * @param string  $itemtype item type
     * @param integer $items_id id of the item
     *
     * @return void
     **/
    public static function forceSendFor($itemtype, $items_id)
    {
        if (
            !empty($itemtype)
            && !empty($items_id)
        ) {
            $pendings = self::getPendings(
                null,
                1,
                null,
                [
                    'itemtype'  => $itemtype,
                    'items_id'  => $items_id
                ]
            );

            foreach ($pendings as $mode => $data) {
                $data = Sanitizer::unsanitize($data);

                $eventclass = Notification_NotificationTemplate::getModeClass($mode, 'event');
                $eventclass::send($data);
            }
        }
    }


    /**
     * Print the queued mail form
     *
     * @param integer $ID      ID of the item
     * @param array   $options Options
     *
     * @return true if displayed  false if item not found or not right to display
     **/
    public function showForm($ID, array $options = [])
    {
        if (!Session::haveRight("queuednotification", READ)) {
            return false;
        }

        $this->check($ID, READ);
        $options['canedit'] = false;

        $this->showFormHeader($options);
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . _n('Type', 'Types', 1) . "</td>";

        echo "<td>";
        if (!($item = getItemForItemtype($this->fields['itemtype']))) {
            echo NOT_AVAILABLE;
            echo "</td>";
            echo "<td>" . _n('Item', 'Items', 1) . "</td>";
            echo "<td>";
            echo NOT_AVAILABLE;
        } else if ($item instanceof CommonDBTM) {
            echo $item->getType();
            $item->getFromDB($this->fields['items_id']);
            echo "</td>";
            echo "<td>" . _n('Item', 'Items', 1) . "</td>";
            echo "<td>";
            echo $item->getLink();
        } else {
            echo get_class($item);
            echo "</td><td></td>";
        }
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . _n('Notification template', 'Notification templates', 1) . "</td>";
        echo "<td>";
        echo Dropdown::getDropdownName(
            'glpi_notificationtemplates',
            $this->fields['notificationtemplates_id']
        );
        echo "</td>";
        echo "<td>&nbsp;</td>";
        echo "<td>&nbsp;</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Creation date') . "</td>";
        echo "<td>";
        echo Html::convDateTime($this->fields['create_time']);
        echo "</td><td>" . __('Expected send date') . "</td>";
        echo "<td>" . Html::convDateTime($this->fields['send_time']) . "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Send date') . "</td>";
        echo "<td>" . Html::convDateTime($this->fields['sent_time']) . "</td>";
        echo "<td>" . __('Number of tries of sent') . "</td>";
        echo "<td>" . $this->fields['sent_try'] . "</td>";
        echo "</tr>";

        echo "<tr><th colspan='4'>" . _n('Email', 'Emails', 1) . "</th></tr>";
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Sender email') . "</td>";
        echo "<td>" . $this->fields['sender'] . "</td>";
        echo "<td>" . __('Sender name') . "</td>";
        echo "<td>" . $this->fields['sendername'] . "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Recipient email') . "</td>";
        echo "<td>" . $this->fields['recipient'] . "</td>";
        echo "<td>" . __('Recipient name') . "</td>";
        echo "<td>" . $this->fields['recipientname'] . "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Reply-To email') . "</td>";
        echo "<td>" . $this->fields['replyto'] . "</td>";
        echo "<td>" . __('Reply-To name') . "</td>";
        echo "<td>" . $this->fields['replytoname'] . "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Message ID') . "</td>";
        echo "<td>" . $this->fields['messageid'] . "</td>";
        echo "<td>" . __('Additional headers') . "</td>";
        echo "<td>" . self::getSpecificValueToDisplay('headers', $this->fields) . "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Subject') . "</td>";
        echo "<td colspan=3>" . $this->fields['name'] . "</td>";
        echo "</tr>";

        echo "<tr><th colspan='2'>" . __('Email HTML body') . "</th>";
        echo "<th colspan='2'>" . __('Email text body') . "</th>";
        echo "</tr>";

        echo "<tr class='tab_bg_1 top' >";
        echo "<td colspan='2' class='queuemail_preview'>";
        echo self::cleanHtml(Sanitizer::unsanitize($this->fields['body_html'] ?? ''));
        echo "</td>";
        echo "<td colspan='2'>" . nl2br($this->fields['body_text'], false) . "</td>";
        echo "</tr>";

        $this->showFormButtons($options);

        return true;
    }


    /**
     * @since 0.85
     *
     * @param $string
     **/
    public static function cleanHtml($string)
    {

        $begin_strip     = -1;
        $end_strip       = -1;
        $begin_match     = "/<body>/";
        $end_match       = "/<\/body>/";
        $content         = explode("\n", $string);
        $newstring       = '';
        foreach ($content as $ID => $val) {
           // Get last tag for end
            if ($begin_strip >= 0) {
                if (preg_match($end_match, $val)) {
                    $end_strip = $ID;
                    continue;
                }
            }
            if (($begin_strip >= 0) && ($end_strip < 0)) {
                $newstring .= $val;
            }
           // Get first tag for begin
            if ($begin_strip < 0) {
                if (preg_match($begin_match, $val)) {
                    $begin_strip = $ID;
                }
            }
        }
        return $newstring;
    }


    public static function getIcon()
    {
        return "ti ti-notification";
    }
}
