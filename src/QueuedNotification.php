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

use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use Glpi\RichText\RichText;

use function Safe\preg_match;
use function Safe\strtotime;

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

    public static function getSectorizedDetails(): array
    {
        return ['admin', self::class];
    }

    public static function canCreate(): bool
    {
        // Everybody can create : human and cron
        return Session::getLoginUserID(false);
    }

    public static function unsetUndisclosedFields(&$fields)
    {
        parent::unsetUndisclosedFields($fields);

        if (
            !array_key_exists('event', $fields)
            || !array_key_exists('itemtype', $fields)
        ) {
            return;
        }

        $target = NotificationTarget::getInstanceByType((string) $fields['itemtype']);
        if (
            $target instanceof NotificationTarget
            && !$target->canNotificationContentBeDisclosed((string) $fields['event'])
        ) {
            $fields['body_html'] = '********';
            $fields['body_text'] = '********';
        }
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
            $forbidden[] = self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'send';
        }

        return $forbidden;
    }

    public function getSpecificMassiveActions($checkitem = null, $is_deleted = false)
    {
        $isadmin = static::canUpdate();
        $actions = parent::getSpecificMassiveActions($checkitem);

        if ($isadmin && !$is_deleted) {
            $actions[self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'send'] = "<i class='ti ti-send'></i>" . _sx('button', 'Send');
        }

        return $actions;
    }

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
                        } elseif (
                            ($item instanceof QueuedNotification)
                            && $item->sendById($id)
                        ) {
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
        if (empty($input['create_time'])) {
            $input['create_time'] = $_SESSION["glpi_currenttime"];
        }
        if (empty($input['send_time'])) {
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
        if (empty($input['items_id'])) {
            $input['items_id'] = 0;
        }

        return $input;
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
            'table'              => static::getTable(),
            'field'              => 'name',
            'name'               => __('Subject'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => static::getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number',
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => static::getTable(),
            'field'              => 'create_time',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => static::getTable(),
            'field'              => 'send_time',
            'name'               => __('Expected send date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => static::getTable(),
            'field'              => 'sent_time',
            'name'               => __('Send date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => static::getTable(),
            'field'              => 'sender',
            'name'               => __('Sender email'),
            'datatype'           => 'text',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => static::getTable(),
            'field'              => 'sendername',
            'name'               => __('Sender name'),
            'datatype'           => 'string',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => static::getTable(),
            'field'              => 'recipient',
            'name'               => __('Recipient email'),
            'datatype'           => 'string',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '8',
            'table'              => static::getTable(),
            'field'              => 'recipientname',
            'name'               => __('Recipient name'),
            'datatype'           => 'string',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '9',
            'table'              => static::getTable(),
            'field'              => 'replyto',
            'name'               => __('Reply-To email'),
            'datatype'           => 'string',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '10',
            'table'              => static::getTable(),
            'field'              => 'replytoname',
            'name'               => __('Reply-To name'),
            'datatype'           => 'string',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '11',
            'table'              => static::getTable(),
            'field'              => 'headers',
            'name'               => __('Additional headers'),
            'datatype'           => 'specific',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '12',
            'table'              => static::getTable(),
            'field'              => 'body_html',
            'name'               => __('Email HTML body'),
            'datatype'           => 'specific',
            'nosearch'           => true, // can contain sensitive data, fine-grain filtering would be too complex
            'additionalfields'   => ['itemtype', 'event'],
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '13',
            'table'              => static::getTable(),
            'field'              => 'body_text',
            'name'               => __('Email text body'),
            'datatype'           => 'specific',
            'nosearch'           => true, // can contain sensitive data, fine-grain filtering would be too complex
            'additionalfields'   => ['itemtype', 'event'],
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '14',
            'table'              => static::getTable(),
            'field'              => 'messageid',
            'name'               => __('Message ID'),
            'datatype'           => 'string',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '15',
            'table'              => static::getTable(),
            'field'              => 'sent_try',
            'name'               => __('Number of tries of sent'),
            'datatype'           => 'integer',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '20',
            'table'              => static::getTable(),
            'field'              => 'itemtype',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'itemtypename',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '21',
            'table'              => static::getTable(),
            'field'              => 'items_id',
            'name'               => __('Associated item ID'),
            'massiveaction'      => false,
            'datatype'           => 'integer',
        ];

        $tab[] = [
            'id'                 => '22',
            'table'              => 'glpi_notificationtemplates',
            'field'              => 'name',
            'name'               => _n('Notification template', 'Notification templates', 1),
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
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
                1 => 'notequals',
            ],
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
        ];

        return $tab;
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        global $CFG_GLPI;

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'body_html':
            case 'body_text':
                if (
                    array_key_exists('event', $values)
                    && array_key_exists('itemtype', $values)
                ) {
                    $target = NotificationTarget::getInstanceByType((string) $values['itemtype']);
                    if (
                        $target instanceof NotificationTarget
                        && !$target->canNotificationContentBeDisclosed((string) $values['event'])
                    ) {
                        return __s('The content of the notification contains sensitive information and therefore cannot be displayed.');
                    }
                }

                // Rendering simitar to the `text` datatype
                $value     = $values[$field];
                $plaintext = '';
                if ($field === 'body_html') {
                    $plaintext = RichText::getTextFromHtml($value, false, true);
                } else {
                    $plaintext = $value;
                }

                if (Toolbox::strlen($plaintext) > $CFG_GLPI['cut']) {
                    $rand = mt_rand();
                    $popup_params = [
                        'display'       => false,
                        'awesome-class' => 'fa-comments',
                        'autoclose'     => false,
                        'onclick'       => true,
                    ];
                    $out = sprintf(
                        __s('%1$s %2$s'),
                        "<span id='text$rand'>" . Html::resume_text($plaintext, $CFG_GLPI['cut']) . '</span>',
                        Html::showToolTip(
                            '<div class="fup-popup">' . RichText::getEnhancedHtml($value) . '</div>',
                            $popup_params
                        )
                    );
                } else {
                    $out = htmlescape($plaintext);
                }
                return $out;
            case 'headers':
                $values[$field] = importArrayFromDB($values[$field]);
                $out = '';
                if (is_array($values[$field]) && count($values[$field])) {
                    foreach ($values[$field] as $key => $val) {
                        $out .= htmlescape($key . ': ' . $val) . '<br>';
                    }
                }
                return $out;
            case 'mode':
                $mode = Notification_NotificationTemplate::getMode($values[$field]);
                if (is_array($mode) && !empty($mode['label'])) {
                    return htmlescape($mode['label']);
                }
                return htmlescape(sprintf(__('%s (%s)'), NOT_AVAILABLE, $values[$field]));
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
            $mode = $this->getField('mode');
            $eventclass = 'NotificationEvent' . ucfirst($mode);
            $conf = Notification_NotificationTemplate::getMode($mode);
            if ($conf['from'] !== 'core') {
                $eventclass = 'Plugin' . ucfirst($conf['from']) . $eventclass;
            }

            return $eventclass::send([$this->fields]);
        }

        return false;
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
        return match ($name) {
            'queuednotification' => [
                'description' => __('Send mails in queue'),
                'parameter' => __('Maximum emails to send at once'),
            ],
            'queuednotificationclean' => [
                'description' => __('Clean notification queue'),
                'parameter' => __('Days to keep sent emails'),
            ],
            'queuednotificationcleanstaleajax' => [
                'description' => __('Clean stale queued browser notifications'),
            ],
            default => [],
        };
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
        global $CFG_GLPI, $DB;

        if ($send_time === null) {
            $send_time = date('Y-m-d H:i:s');
        }

        $base_query = [
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'is_deleted'   => 0,
                'mode'         => 'TOFILL',
                'send_time'    => ['<=', $send_time],
            ] +  $extra_where,
            'ORDER'  => 'send_time ASC',
            'START'  => 0,
            'LIMIT'  => $limit,
        ];

        $pendings = [];
        $modes = Notification_NotificationTemplate::getModes();
        foreach ($modes as $mode => $conf) {
            $eventclass = 'NotificationEvent' . ucfirst($mode);
            if ($conf['from'] !== 'core') {
                $eventclass = 'Plugin' . ucfirst($conf['from']) . $eventclass;
            }

            if (
                ($limit_modes !== null && !in_array($mode, $limit_modes, true))
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
     * @param CronTask $task for log (default NULL)
     *
     * @return integer either 0 or 1
     * @used-by CronTask
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
            $eventclass = 'NotificationEvent' . ucfirst($mode);
            $conf = Notification_NotificationTemplate::getMode($mode);
            if ($conf['from'] !== 'core') {
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
     * @param CronTask $task for log (default NULL)
     *
     * @return integer either 0 or 1
     * @used-by CronTask
     **/
    public static function cronQueuedNotificationClean(?CronTask $task = null)
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
                    new QueryExpression(QueryFunction::unixTimestamp('send_time') . ' < ' . $DB::quoteValue($send_time)),
                ]
            );
            $vol = $DB->affectedRows();
        }

        $task->setVolume($vol);
        return ($vol > 0 ? 1 : 0);
    }

    /**
     * Cron action on queued notification: clean stale ajax notification queue
     *
     * @param CronTask $task for log (default NULL)
     *
     * @return integer either 0 or 1
     * @used-by CronTask
     **/
    public static function cronQueuedNotificationCleanStaleAjax(?CronTask $task = null)
    {
        global $CFG_GLPI, $DB;

        $vol = 0;

        // Stale ajax notifications in queue
        if ($CFG_GLPI["notifications_ajax_expiration_delay"] > 0) {
            $secs = $CFG_GLPI["notifications_ajax_expiration_delay"] * DAY_TIMESTAMP;
            $DB->update(
                self::getTable(),
                [
                    'is_deleted'   => 1,
                ],
                [
                    'is_deleted'   => 0,
                    'mode'         => Notification_NotificationTemplate::MODE_AJAX,
                    new QueryExpression(
                        QueryFunction::unixTimestamp('send_time') . ' + ' . $secs
                            . ' < ' . QueryFunction::unixTimestamp()
                    ),
                ]
            );
            $vol = $DB->affectedRows();
        }

        $task->setVolume($vol);
        return ($vol > 0 ? 1 : 0);
    }

    /**
     * Print the queued mail form
     *
     * @param integer $ID      ID of the item
     * @param array   $options Options
     *
     * @return boolean true if displayed  false if item not found or not right to display
     **/
    public function showForm($ID, array $options = [])
    {
        if (!Session::haveRight("queuednotification", READ)) {
            return false;
        }
        $this->check($ID, READ);
        $options['canedit'] = false;

        $item = getItemForItemtype($this->fields['itemtype']);
        if ($item instanceof CommonDBTM) {
            $item->getFromDB($this->fields['items_id']);
        }

        $target = NotificationTarget::getInstanceByType((string) $this->fields['itemtype']);

        TemplateRenderer::getInstance()->display('pages/setup/notification/queued_notification.html.twig', [
            'item' => $this,
            'params' => $options,
            'parent' => $item,
            'additional_headers' => self::getSpecificValueToDisplay('headers', $this->fields),
            'undisclose_body' => $target instanceof NotificationTarget
                && !$target->canNotificationContentBeDisclosed((string) $this->fields['event']),
        ]);

        return true;
    }

    /**
     * @param $string
     * @return string
     * @since 0.85
     */
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
