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

// Needed for signal handler
declare(ticks=1);

use Glpi\Event;

/**
 * CronTask class
 */
class CronTask extends CommonDBTM
{
   // From CommonDBTM
    public $dohistory                   = true;

   // Specific ones
    private static $lockname = '';
    private $timer           = 0.0;
    private $startlog        = 0;
    private $volume          = 0;
    public static $rightname        = 'config';

   // Class constant
    const STATE_DISABLE = 0;
    const STATE_WAITING = 1;
    const STATE_RUNNING = 2;

    const MODE_INTERNAL = 1;
    const MODE_EXTERNAL = 2;


    public static function getForbiddenActionsForMenu()
    {
        return ['add'];
    }


    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'delete';
        $forbidden[] = 'purge';
        $forbidden[] = 'restore';
        return $forbidden;
    }


    public static function getTypeName($nb = 0)
    {
        return _n('Automatic action', 'Automatic actions', $nb);
    }


    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addImpactTab($ong, $options);
        $this->addStandardTab('CronTaskLog', $ong, $options);
        $this->addStandardTab('Log', $ong, $options);

        return $ong;
    }


    public static function canDelete()
    {
        return false;
    }


    public function cleanDBonPurge()
    {

       // CronTaskLog does not extends CommonDBConnexity
        $ctl = new CronTaskLog();
        $ctl->deleteByCriteria(['crontasks_id' => $this->fields['id']]);
    }


    /**
     * Read a CronTask by its name
     *
     * Used by plugins to load its crontasks
     *
     * @param string $itemtype  itemtype of the crontask
     * @param string $name      name of the task
     *
     * @return boolean true if succeed else false
     **/
    public function getFromDBbyName($itemtype, $name)
    {

        return $this->getFromDBByCrit([
            $this->getTable() . '.name'      => (string)$name,
            $this->getTable() . '.itemtype'  => (string)$itemtype
        ]);
    }


    /**
     * Give a task state
     *
     * @return integer 0 : task is enabled
     *    if disable : 1: by config, 2: by system lock, 3: by plugin
     **/
    public function isDisabled()
    {

        if ($this->fields['state'] == self::STATE_DISABLE) {
            return 1;
        }

        if (
            is_file(GLPI_CRON_DIR . '/all.lock')
            || is_file(GLPI_CRON_DIR . '/' . $this->fields['name'] . '.lock')
        ) {
           // Global lock
            return 2;
        }

        if (!($tab = isPluginItemType($this->fields['itemtype']))) {
            return 0;
        }

       // Plugin case
        $plug = new Plugin();
        if (!$plug->isActivated($tab["plugin"])) {
            return 3;
        }
        return 0;
    }

    /**
     * Get all itemtypes used
     *
     * @return string[]
     **/
    public static function getUsedItemtypes()
    {
        /** @var \DBmysql $DB */
        global $DB;

        $types = [];
        $iterator = $DB->request([
            'SELECT'          => 'itemtype',
            'DISTINCT'        => true,
            'FROM'            => 'glpi_crontasks'
        ]);
        foreach ($iterator as $data) {
            $types[] = $data['itemtype'];
        }
        return $types;
    }

    /**
     * Signal handler callback
     *
     * @param integer $signo Signal number
     * @since 9.1
     */
    public function signal($signo)
    {
        if ($signo == SIGTERM) {
            pcntl_signal(SIGTERM, SIG_DFL);

           // End of this task
            $this->end(null);

           // End of this cron
            $_SESSION["glpicronuserrunning"] = '';
            self::release_lock();
            Toolbox::logInFile('cron', __('Action aborted') . "\n");
            exit;
        }
    }

    /**
     * Start a task, timer, stat, log, ...
     *
     * @return bool : true if ok (not start by another)
     **/
    public function start()
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (!isset($this->fields['id']) || ($DB->isSlave())) {
            return false;
        }

        if (isCommandLine() && function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, [$this, 'signal']);
        }

        $result = $DB->update(
            $this->getTable(),
            [
                'state'  => self::STATE_RUNNING,
                'lastrun'   => new \QueryExpression('DATE_FORMAT(NOW(),\'%Y-%m-%d %H:%i:00\')')
            ],
            [
                'id'  => $this->fields['id'],
                'NOT' => ['state' => self::STATE_RUNNING]
            ]
        );

        if ($DB->affectedRows() > 0) {
            $this->timer  = microtime(true);
            $this->volume = 0;
            $log = new CronTaskLog();
            // No gettext for log
            $txt = sprintf(
                '%1$s: %2$s',
                'Run mode',
                $this->getModeName(isCommandLine() ? self::MODE_EXTERNAL
                : self::MODE_INTERNAL)
            );

            $this->startlog = $log->add(['crontasks_id'    => $this->fields['id'],
                'date'            => $_SESSION['glpi_currenttime'],
                'content'         => addslashes($txt),
                'crontasklogs_id' => 0,
                'state'           => CronTaskLog::STATE_START,
                'volume'          => 0,
                'elapsed'         => 0
            ]);
            return true;
        }
        return false;
    }


    /**
     * Set the currently proccessed volume of a running task
     *
     * @param $volume
     **/
    public function setVolume($volume)
    {
        $this->volume = $volume;
    }


    /**
     * Increase the currently proccessed volume of a running task
     *
     * @param $volume
     **/
    public function addVolume($volume)
    {
        $this->volume += $volume;
    }


    /**
     * End a task, timer, stat, log, ...
     *
     * @param int|null $retcode
     *    <0: need to run again
     *    0 : nothing to do
     *    >0: ok
     * @param int $log_state
     *
     * @return bool : true if ok (not start by another)
     *
     * @since 9.5.5 Added parameter $log_state.
     **/
    public function end($retcode, int $log_state = CronTaskLog::STATE_STOP)
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (!isset($this->fields['id'])) {
            return false;
        }

        $result = $DB->update(
            $this->getTable(),
            [
                'state'  => $this->fields['state']
            ],
            [
                'id'     => $this->fields['id'],
                'state'  => self::STATE_RUNNING
            ]
        );

        if ($DB->affectedRows() > 0) {
           // No gettext for log but add gettext line to be parsed for pot generation
           // order is important for insertion in english in the database
            if ($log_state === CronTaskLog::STATE_ERROR) {
                $content = __('Execution error');
                $content = 'Execution error';
            } else if (is_null($retcode)) {
                $content = __('Action aborted');
                $content = 'Action aborted';
            } else if ($retcode < 0) {
                $content = __('Action completed, partially processed');
                $content = 'Action completed, partially processed';
            } else if ($retcode > 0) {
                $content = __('Action completed, fully processed');
                $content = 'Action completed, fully processed';
            } else {
                $content = __('Action completed, no processing required');
                $content = 'Action completed, no processing required';
            }

            $log = new CronTaskLog();
            $log->add(['crontasks_id'    => $this->fields['id'],
                'date'            => $_SESSION['glpi_currenttime'],
                'content'         => $content,
                'crontasklogs_id' => $this->startlog,
                'state'           => $log_state,
                'volume'          => $this->volume,
                'elapsed'         => (microtime(true) - $this->timer)
            ]);
            return true;
        }
        return false;
    }


    /**
     * Add a log message for a running task
     *
     * @param string $content
     **/
    public function log($content)
    {

        if (!isset($this->fields['id'])) {
            return false;
        }
        $log     = new CronTaskLog();
        $content = Toolbox::substr($content, 0, 200);
        return $log->add(['crontasks_id'    => $this->fields['id'],
            'date'            => $_SESSION['glpi_currenttime'],
            'content'         => addslashes($content),
            'crontasklogs_id' => $this->startlog,
            'state'           => CronTaskLog::STATE_RUN,
            'volume'          => $this->volume,
            'elapsed'         => (microtime(true) - $this->timer)
        ]);
    }


    /**
     * read the first task which need to be run by cron
     *
     * @param integer $mode >0 retrieve task configured for this mode
     *                      <0 retrieve task allowed for this mode (force, no time check)
     * @param string $name  one specify action
     *
     * @return boolean false if no task to run
     **/
    public function getNeedToRun($mode = 0, $name = '')
    {
        /** @var \DBmysql $DB */
        global $DB;

        $hour_criteria = new QueryExpression('hour(curtime())');

        $itemtype_orwhere = [
         // Core crontasks
            [
                ['NOT' => ['itemtype' => ['LIKE', 'Plugin%']]],
                ['NOT' => ['itemtype' => ['LIKE', addslashes('GlpiPlugin\\\\') . '%']]]
            ]
        ];
        foreach (Plugin::getPlugins() as $plug) {
           // Activated plugin tasks
            $itemtype_orwhere[] = [
                'OR' => [
                    ['itemtype' => ['LIKE', sprintf('Plugin%s', $plug) . '%']],
                    ['itemtype' => ['LIKE', addslashes(sprintf('GlpiPlugin\\\\%s\\\\', $plug)) . '%']]
                ]
            ];
        }

        $WHERE = [
            ['OR' => $itemtype_orwhere]
        ];

        if ($name) {
            $WHERE['name'] = addslashes($name);
        }

       // In force mode
        if ($mode < 0) {
            $WHERE['state'] = ['!=', self::STATE_RUNNING];
            $WHERE['allowmode'] = ['&', (int)$mode * -1];
        } else {
            $WHERE['state'] = self::STATE_WAITING;
            if ($mode > 0) {
                $WHERE['mode'] = $mode;
            }

           // Get system lock
            if (is_file(GLPI_CRON_DIR . '/all.lock')) {
               // Global lock
                return false;
            }
            $locks = [];
            foreach (glob(GLPI_CRON_DIR . '/*.lock') as $lock) {
                $reg = [];
                if (preg_match('!.*/(.*).lock$!', $lock, $reg)) {
                    $locks[] = $reg[1];
                }
            }
            if (count($locks)) {
                $WHERE[] = ['NOT' => ['name' => $locks]];
            }

           // Build query for frequency and allowed hour
            $WHERE[] = ['OR' => [
                ['AND' => [
                    ['hourmin'   => ['<', new QueryExpression($DB->quoteName('hourmax'))]],
                    'hourmin'   => ['<=', $hour_criteria],
                    'hourmax'   => ['>', $hour_criteria]
                ]
                ],
                ['AND' => [
                    'hourmin'   => ['>', new QueryExpression($DB->quoteName('hourmax'))],
                    'OR'        => [
                        'hourmin'   => ['<=', $hour_criteria],
                        'hourmax'   => ['>', $hour_criteria]
                    ]
                ]
                ]
            ]
            ];
            $WHERE[] = ['OR' => [
                'lastrun'   => null,
                new \QueryExpression('unix_timestamp(' . $DB->quoteName('lastrun') . ') + ' . $DB->quoteName('frequency') . ' <= unix_timestamp(now())')
            ]
            ];
        }

        $iterator = $DB->request([
            'SELECT' => [
                '*',
                new \QueryExpression("LOCATE('Plugin', " . $DB->quoteName('itemtype') . ") AS ISPLUGIN")
            ],
            'FROM'   => $this->getTable(),
            'WHERE'  => $WHERE,
         // Core task before plugins
            'ORDER'  => [
                'ISPLUGIN',
                new \QueryExpression('unix_timestamp(' . $DB->quoteName('lastrun') . ')+' . $DB->quoteName('frequency') . '')
            ]
        ]);

        if (count($iterator)) {
            $this->fields = $iterator->current();
            return true;
        }
        return false;
    }

    /**
     * Send a notification on task error.
     */
    private function sendNotificationOnError(): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        $alert_iterator = $DB->request(
            [
                'FROM'      => 'glpi_alerts',
                'WHERE'     => [
                    'items_id' => $this->fields['id'],
                    'itemtype' => 'CronTask',
                    'date'     => ['>', new QueryExpression('CURRENT_TIMESTAMP() - INTERVAL 1 day')],
                ],
            ]
        );
        if ($alert_iterator->count() > 0) {
           // An alert has been sent within last day, so do not send a new one to not bother administrator
            return;
        }

       // Check if errors threshold is exceeded, and send a notification in this case.
       //
       // We check on last "$threshold * 2" runs as a task that works only half of the time
       // is not a normal behaviour.
       // For instance, if threshold is 5, then a task that fails 5 times on last 10 executions
       // will trigger a notification.
        $threshold = 5;

        $iterator = $DB->request(
            [
                'FROM'   => 'glpi_crontasklogs',
                'WHERE'  => [
                    'crontasks_id' => $this->fields['id'],
                    'state'        => [CronTaskLog::STATE_STOP, CronTaskLog::STATE_ERROR],
                ],
                'ORDER'  => 'id DESC',
                'LIMIT'  => $threshold * 2
            ]
        );

        $error_count = 0;
        foreach ($iterator as $row) {
            if ($row['state'] === CronTaskLog::STATE_ERROR) {
                $error_count++;
            }
        }

        if ($error_count >= $threshold) {
           // No alert has been sent within last day, so we can send one without bothering administrator
            NotificationEvent::raiseEvent('alert', $this, ['items' => [$this->fields['id'] => $this->fields]]);
            QueuedNotification::forceSendFor($this->getType(), $this->fields['id']);

           // Delete existing outdated alerts
            $alert = new Alert();
            $alert->deleteByCriteria(['itemtype' => 'CronTask', 'items_id' => $this->fields['id']], 1);

           // Create a new alert
            $alert->add(
                [
                    'type'     => Alert::THRESHOLD,
                    'itemtype' => 'CronTask',
                    'items_id' => $this->fields['id'],
                ]
            );
        }
    }


    /**
     * Print the contact form
     *
     * @param integer $ID
     * @param array   $options
     *     - target filename : where to go when done.
     *     - withtemplate boolean : template or basic item
     *
     * @return boolean
     **/
    public function showForm($ID, array $options = [])
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (!Config::canView() || !$this->getFromDB($ID)) {
            return false;
        }
        $options['candel'] = false;
        $this->showFormHeader($options);

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Name') . "</td>";
        echo "<td class ='b'>";
        $name = $this->fields["name"];
        if ($isplug = isPluginItemType($this->fields["itemtype"])) {
            $name = sprintf(__('%1$s - %2$s'), $isplug["plugin"], $name);
        }
        echo $name . "</td>";
        echo "<td rowspan='6' class='middle right'>" . __('Comments') . "</td>";
        echo "<td class='center middle' rowspan='6'>";
        echo "<textarea class='form-control' name='comment' >" . $this->fields["comment"] . "</textarea>";
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . __('Description') . "</td><td>";
        echo $this->getDescription($ID);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . __('Run frequency') . "</td><td>";
        Dropdown::showFrequency('frequency', $this->fields["frequency"]);
        echo "</td></tr>";

        $tmpstate = $this->fields["state"];
        echo "<tr class='tab_bg_1'><td>" . __('Status') . "</td><td>";
        if (
            is_file(GLPI_CRON_DIR . '/' . $this->fields["name"] . '.lock')
            || is_file(GLPI_CRON_DIR . '/all.lock')
        ) {
            echo "<span class='b'>" . __('System lock') . "</span><br>";
            $tmpstate = self::STATE_DISABLE;
        }

        if ($isplug) {
            $plug = new Plugin();
            if (!$plug->isActivated($isplug["plugin"])) {
                echo "<span class='b'>" . __('Disabled plugin') . "</span><br>";
                $tmpstate = self::STATE_DISABLE;
            }
        }

        if ($this->fields["state"] == self::STATE_RUNNING) {
            echo "<span class='b'>" . $this->getStateName(self::STATE_RUNNING) . "</span>";
        } else {
            self::dropdownState('state', $this->fields["state"]);
        }
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . __('Run mode') . "</td><td>";
        $modes = [];
        if ($this->fields['allowmode'] & self::MODE_INTERNAL) {
            $modes[self::MODE_INTERNAL] = self::getModeName(self::MODE_INTERNAL);
        }
        if ($this->fields['allowmode'] & self::MODE_EXTERNAL) {
            $modes[self::MODE_EXTERNAL] = self::getModeName(self::MODE_EXTERNAL);
        }
        Dropdown::showFromArray('mode', $modes, ['value' => $this->fields['mode']]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . __('Run period') . "</td><td>";
        Dropdown::showNumber('hourmin', ['value' => $this->fields['hourmin'],
            'min'   => 0,
            'max'   => 24
        ]);
        echo "&nbsp;->&nbsp;";
        Dropdown::showNumber('hourmax', ['value' => $this->fields['hourmax'],
            'min'   => 0,
            'max'   => 24
        ]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . __('Number of days this action logs are stored') . "</td><td>";
        Dropdown::showNumber('logs_lifetime', ['value' => $this->fields['logs_lifetime'],
            'min'   => 10,
            'max'   => 360,
            'step'  => 10,
            'toadd' => [0 => __('Infinite')]
        ]);
        echo "</td><td>" . __('Last run') . "</td><td>";

        if (empty($this->fields['lastrun'])) {
            echo __('Never');
        } else {
            echo Html::convDateTime($this->fields['lastrun']);
            echo "&nbsp;";
            Html::showSimpleForm(
                static::getFormURL(),
                'resetdate',
                __('Blank'),
                ['id' => $ID],
                'fa-times-circle'
            );
        }
        echo "</td></tr>";

        $label = $this->getParameterDescription();
        echo "<tr class='tab_bg_1'><td>";
        if (empty($label)) {
            echo "&nbsp;</td><td>&nbsp;";
        } else {
            echo $label . "&nbsp;</td><td>";
            Dropdown::showNumber('param', ['value' => $this->fields['param'],
                'min'   => 0,
                'max'   => 10000
            ]);
        }
        echo "</td><td>" . __('Next run') . "</td><td>";

        if ($tmpstate == self::STATE_RUNNING) {
            $launch = false;
        } else {
            $launch = $this->fields['allowmode'] & self::MODE_INTERNAL;
        }

        if ($tmpstate != self::STATE_WAITING) {
            echo $this->getStateName($tmpstate);
        } else if (empty($this->fields['lastrun'])) {
            echo __('As soon as possible');
        } else {
            $next = strtotime($this->fields['lastrun']) + $this->fields['frequency'];
            $h    = date('H', $next);
            $deb  = ($this->fields['hourmin'] < 10 ? "0" . $this->fields['hourmin']
                                                : $this->fields['hourmin']);
            $fin  = ($this->fields['hourmax'] < 10 ? "0" . $this->fields['hourmax']
                                                : $this->fields['hourmax']);

            if (
                ($deb < $fin)
                && ($h < $deb)
            ) {
                $disp = date('Y-m-d', $next) . " $deb:00:00";
                $next = strtotime($disp);
            } else if (
                ($deb < $fin)
                    && ($h >= $this->fields['hourmax'])
            ) {
                $disp = date('Y-m-d', $next + DAY_TIMESTAMP) . " $deb:00:00";
                $next = strtotime($disp);
            }

            if (
                ($deb > $fin)
                && ($h < $deb)
                && ($h >= $fin)
            ) {
                $disp = date('Y-m-d', $next) . " $deb:00:00";
                $next = strtotime($disp);
            } else {
                $disp = date("Y-m-d H:i:s", $next);
            }

            if ($next < time()) {
                echo __('As soon as possible') . '<br>(' . Html::convDateTime($disp) . ') ';
            } else {
                echo Html::convDateTime($disp);
            }
        }

        if (isset($CFG_GLPI['maintenance_mode']) && $CFG_GLPI['maintenance_mode']) {
            echo "<div class='warning'>" .
              __('Maintenance mode enabled, running tasks is disabled') .
              "</div>";
        } else if ($launch) {
            echo "&nbsp;";
            Html::showSimpleForm(
                static::getFormURL(),
                ['execute' => $this->fields['name']],
                __('Execute')
            );
        }
        if ($tmpstate == self::STATE_RUNNING) {
            Html::showSimpleForm(
                static::getFormURL(),
                'resetstate',
                __('Blank'),
                ['id' => $ID],
                'fa-times-circle'
            );
        }
        echo "</td></tr>";

        $this->showFormButtons($options);

        return true;
    }


    /**
     * reset the next launch date => for a launch as soon as possible
     **/
    public function resetDate()
    {

        if (!isset($this->fields['id'])) {
            return false;
        }
        return $this->update(['id'      => $this->fields['id'],
            'lastrun' => 'NULL'
        ]);
    }


    /**
     * reset the current state
     **/
    public function resetState()
    {

        if (!isset($this->fields['id'])) {
            return false;
        }
        return $this->update(['id'    => $this->fields['id'],
            'state' => self::STATE_WAITING
        ]);
    }


    /**
     * Translate task description
     *
     * @param $id integer ID of the crontask
     *
     * @return string
     **/
    public function getDescription($id)
    {

        if (!isset($this->fields['id']) || ($this->fields['id'] != $id)) {
            $this->getFromDB($id);
        }

        $hook = [$this->fields['itemtype'], 'cronInfo'];
        if (is_callable($hook)) {
            $info = call_user_func($hook, $this->fields['name']);
        } else {
            $info = false;
        }

        if (isset($info['description'])) {
            return $info['description'];
        }

        return $this->fields['name'];
    }


    /**
     * Translate task parameter description
     *
     * @return string
     **/
    public function getParameterDescription()
    {

        $hook = [$this->fields['itemtype'], 'cronInfo'];

        if (is_callable($hook)) {
            $info = call_user_func($hook, $this->fields['name']);
        } else {
            $info = false;
        }

        if (isset($info['parameter'])) {
            return $info['parameter'];
        }

        return '';
    }


    /**
     * Translate state to string
     *
     * @param $state integer
     *
     * @return string
     **/
    public static function getStateName($state)
    {

        switch ($state) {
            case self::STATE_RUNNING:
                return __('Running');

            case self::STATE_WAITING:
                return __('Scheduled');

            case self::STATE_DISABLE:
                return __('Disabled');
        }

        return '???';
    }


    /**
     * Dropdown of state
     *
     * @param string  $name     select name
     * @param integer $value    default value
     * @param boolean $display  display or get string
     *
     * @return string|integer HTML output, or random part of dropdown ID.
     **/
    public static function dropdownState($name, $value = 0, $display = true)
    {

        return Dropdown::showFromArray(
            $name,
            [self::STATE_DISABLE => __('Disabled'),
                self::STATE_WAITING => __('Scheduled')
            ],
            ['value'   => $value,
                'display' => $display
            ]
        );
    }


    /**
     * Translate Mode to string
     *
     * @param $mode integer
     *
     * @return string
     **/
    public static function getModeName($mode)
    {

        switch ($mode) {
            case self::MODE_INTERNAL:
                return __('GLPI');

            case self::MODE_EXTERNAL:
                return __('CLI');
        }

        return '???';
    }


    /**
     * Get a global database lock for cron
     *
     * @return Boolean
     **/
    private static function get_lock()
    {
        /** @var \DBmysql $DB */
        global $DB;

       // Changer de nom toutes les heures en cas de blocage MySQL (ca arrive)
        $nom = "glpicron." . intval(time() / HOUR_TIMESTAMP - 340000);

        if ($DB->getLock($nom)) {
            self::$lockname = $nom;
            return true;
        }

        return false;
    }


    /**
     * Release the global database lock
     **/
    private static function release_lock()
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (self::$lockname) {
            $DB->releaseLock(self::$lockname);
            self::$lockname = '';
        }
    }


    /**
     * Launch the need cron tasks
     *
     * @param integer $mode   (internal/external, <0 to force)
     * @param integer $max    number of task to launch
     * @param string  $name   name of task to run
     *
     * @return string|boolean the name of last task launched, or false if execution not available
     **/
    public static function launch($mode, $max = 1, $name = '')
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

       // No cron in maintenance mode
        if (isset($CFG_GLPI['maintenance_mode']) && $CFG_GLPI['maintenance_mode']) {
            Toolbox::logInFile('cron', __('Maintenance mode enabled, running tasks is disabled') . "\n");
            return false;
        }

        $crontask = new self();
        $taskname = '';
        if (abs($mode) == self::MODE_EXTERNAL) {
           // If cron is launched in command line, and if memory is insufficient,
           // display a warning in the logs
            if (Toolbox::checkMemoryLimit() == 2) {
                Toolbox::logInFile('cron', __('A minimum of 64 Mio is commonly required for GLPI.') . "\n");
            }
           // If no task in CLI mode, call cron.php from command line is not really usefull ;)
            if (!countElementsInTable($crontask->getTable(), ['mode' => abs($mode)])) {
                Toolbox::logInFile(
                    'cron',
                    __('No task with Run mode = CLI, fix your tasks configuration') . "\n"
                );
            }
        }

        if (self::get_lock()) {
            for ($i = 1; $i <= $max; $i++) {
                $msgprefix = sprintf(
                //TRANS: %1$s is mode (external or internal), %2$s is an order number,
                    __('%1$s #%2$s'),
                    abs($mode) == self::MODE_EXTERNAL ? __('External') : __('Internal'),
                    $i
                );
                if ($crontask->getNeedToRun($mode, $name)) {
                     $_SESSION["glpicronuserrunning"] = "cron_" . $crontask->fields['name'];

                     $function = sprintf('%s::cron%s', $crontask->fields['itemtype'], $crontask->fields['name']);

                    if (is_callable($function)) {
                        if ($crontask->start()) { // Lock in DB + log start
                            $taskname = $crontask->fields['name'];

                            Toolbox::logInFile(
                                'cron',
                                sprintf(
                                    __('%1$s: %2$s'),
                                    $msgprefix,
                                    sprintf(__('%1$s %2$s') . "\n", __('Launch'), $crontask->fields['name'])
                                )
                            );
                            try {
                                  $retcode = call_user_func($function, $crontask);
                            } catch (\Throwable $e) {
                                /** @var \GLPI $GLPI */
                                global $GLPI;
                                $GLPI->getErrorHandler()->handleException($e);
                                Toolbox::logInFile(
                                    'cron',
                                    sprintf(
                                        __('%1$s: %2$s'),
                                        $msgprefix,
                                        sprintf(
                                            __('Error during %s execution. Check in "%s" for more details.') . "\n",
                                            $crontask->fields['name'],
                                            GLPI_LOG_DIR . '/php-errors.log'
                                        )
                                    )
                                );
                                $retcode = null;
                                $crontask->end(null, CronTaskLog::STATE_ERROR);
                                $crontask->sendNotificationOnError();
                                continue;
                            }
                             $crontask->end($retcode); // Unlock in DB + log end
                        } else {
                            Toolbox::logInFile(
                                'cron',
                                sprintf(
                                    __('%1$s: %2$s'),
                                    $msgprefix,
                                    sprintf(__('%1$s %2$s') . "\n", __("Can't start"), $crontask->fields['name'])
                                )
                            );
                        }
                    } else {
                        $undefined_msg = sprintf(__('Undefined function %s (for cron)') . "\n", $function);
                        Toolbox::logInFile('php-errors', $undefined_msg);
                        Toolbox::logInFile(
                            'cron',
                            sprintf(
                                __('%1$s: %2$s'),
                                $msgprefix,
                                sprintf(__('%1$s %2$s') . "\n", __("Can't start"), $crontask->fields['name'])
                            ) . "\n" . $undefined_msg
                        );
                    }
                } else if ($i == 1) {
                    $msgcron = sprintf(__('%1$s: %2$s'), $msgprefix, __('Nothing to launch'));
                    Toolbox::logInFile('cron', $msgcron . "\n");
                }
            }
            $_SESSION["glpicronuserrunning"] = '';
            self::release_lock();
        } else {
            Toolbox::logInFile('cron', __("Can't get DB lock") . "\n");
        }

        return $taskname;
    }


    /**
     * Register new task for plugin (called by plugin during install)
     *
     * @param string  $itemtype  itemtype of the plugin object
     * @param string  $name      task name
     * @param integer $frequency execution frequency
     * @param array   $options   optional options
     *       (state, mode, allowmode, hourmin, hourmax, logs_lifetime, param, comment)
     *
     * @return boolean
     **/
    public static function register($itemtype, $name, $frequency, $options = [])
    {

        // Check that hook exists
        if (!isPluginItemType($itemtype) && !class_exists($itemtype)) {
            return false;
        }

        // manage NS class
        $itemtype = addslashes($itemtype);

        $temp = new self();
        // Avoid duplicate entry
        if ($temp->getFromDBbyName($itemtype, $name)) {
            return false;
        }
        $input = [
            'itemtype'  => $itemtype,
            'name'      => $name,
            'allowmode' => self::MODE_INTERNAL | self::MODE_EXTERNAL,
            'frequency' => $frequency
        ];

        foreach (
            ['allowmode', 'comment', 'hourmax', 'hourmin', 'logs_lifetime', 'mode',
                'param', 'state'
            ] as $key
        ) {
            if (isset($options[$key])) {
                $input[$key] = $options[$key];
            }
        }
        if (
            defined('GLPI_SYSTEM_CRON')
            && ($input['allowmode'] & self::MODE_EXTERNAL)
            && !isset($input['mode'])
        ) {
            // Downstream packages may provide a good system cron
            $input['mode'] = self::MODE_EXTERNAL;
        }
        return $temp->add($input);
    }


    /**
     * Unregister tasks for a plugin (call by glpi after uninstall)
     *
     * @param $plugin : name of the plugin
     *
     * @return bool for success
     **/
    public static function unregister($plugin)
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (empty($plugin)) {
            return false;
        }
        $temp = new CronTask();
        $ret  = true;

        $iterator = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'OR' => [
                    ['itemtype' => ['LIKE', sprintf('Plugin%s', $plugin) . '%']],
                    ['itemtype' => ['LIKE', addslashes(sprintf('GlpiPlugin\\\\%s\\\\', $plugin)) . '%']]
                ]
            ]
        ]);

        foreach ($iterator as $data) {
            if (!$temp->delete($data)) {
                $ret = false;
            }
        }

        return $ret;
    }


    /**
     * Display statistics of a task
     *
     * @return void
     **/
    public function showStatistics()
    {
        /** @var \DBmysql $DB */
        global $DB;

        echo "<br><div class='center'>";
        echo "<table class='tab_cadre'>";
        echo "<tr><th colspan='2'>&nbsp;" . __('Statistics') . "</th></tr>\n";

        $nbstart = countElementsInTable(
            'glpi_crontasklogs',
            ['crontasks_id' => $this->fields['id'],
                'state'        => CronTaskLog::STATE_START
            ]
        );
        $nbstop  = countElementsInTable(
            'glpi_crontasklogs',
            ['crontasks_id' => $this->fields['id'],
                'state'        => CronTaskLog::STATE_STOP
            ]
        );
        $nberror = countElementsInTable(
            'glpi_crontasklogs',
            ['crontasks_id' => $this->fields['id'],
                'state'        => CronTaskLog::STATE_ERROR
            ]
        );

        echo "<tr class='tab_bg_2'><td>" . __('Run count') . "</td><td class='right'>";
        if ($nbstart == $nbstop) {
            echo $nbstart;
        } else {
           // This should not appen => task crash ?
           //TRANS: %s is the number of starts
            printf(_n('%s start', '%s starts', $nbstart), $nbstart);
            echo "<br>";
           //TRANS: %s is the number of stops
            printf(_n('%s stop', '%s stops', $nbstop), $nbstop);
            echo "<br>";
           //TRANS: %s is the number of errors
            printf(_n('%s error', '%s errors', $nberror), $nberror);
        }
        echo "</td></tr>";

        if ($nbstop) {
            $data = $DB->request([
                'SELECT' => [
                    'MIN' => [
                        'date AS datemin',
                        'elapsed AS elapsedmin',
                        'volume AS volmin'
                    ],
                    'MAX' => [
                        'elapsed AS elapsedmax',
                        'volume AS volmax'
                    ],
                    'SUM' => [
                        'elapsed AS elapsedtot',
                        'volume AS voltot'
                    ],
                    'AVG' => [
                        'elapsed AS elapsedavg',
                        'volume AS volavg'
                    ]
                ],
                'FROM'   => CronTaskLog::getTable(),
                'WHERE'  => [
                    'crontasks_id' => $this->fields['id'],
                    'state'        => CronTaskLog::STATE_STOP
                ]
            ])->current();

            echo "<tr class='tab_bg_1'><td>" . __('Start date') . "</td>";
            echo "<td class='right'>" . Html::convDateTime($data['datemin']) . "</td></tr>";

            echo "<tr class='tab_bg_2'><td>" . __('Minimal time') . "</td>";
            echo "<td class='right'>" . sprintf(
                _n('%s second', '%s seconds', $data['elapsedmin']),
                number_format($data['elapsedmin'], 2)
            );
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'><td>" . __('Maximal time') . "</td>";
            echo "<td class='right'>" . sprintf(
                _n('%s second', '%s seconds', $data['elapsedmax']),
                number_format($data['elapsedmax'], 2)
            );
            echo "</td></tr>";

            echo "<tr class='tab_bg_2'><td>" . __('Average time') . "</td>";
            echo "<td class='right b'>" . sprintf(
                _n('%s second', '%s seconds', $data['elapsedavg']),
                number_format($data['elapsedavg'], 2)
            );
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'><td>" . __('Total duration') . "</td>";
            echo "<td class='right'>" . sprintf(
                _n('%s second', '%s seconds', $data['elapsedtot']),
                number_format($data['elapsedtot'], 2)
            );
            echo "</td></tr>";

            if ($data['voltot'] > 0) {
                echo "<tr class='tab_bg_2'><td>" . __('Minimal count') . "</td>";
                echo "<td class='right'>" . sprintf(
                    _n('%s item', '%s items', $data['volmin']),
                    $data['volmin']
                ) . "</td></tr>";

                 echo "<tr class='tab_bg_1'><td>" . __('Maximal count') . "</td>";
                 echo "<td class='right'>" . sprintf(
                     _n('%s item', '%s items', $data['volmax']),
                     $data['volmax']
                 ) . "</td></tr>";

                 echo "<tr class='tab_bg_2'><td>" . __('Average count') . "</td>";
                 echo "<td class='right b'>" . sprintf(
                     _n('%s item', '%s items', $data['volavg']),
                     number_format($data['volavg'], 2)
                 ) .
                   "</td></tr>";

                 echo "<tr class='tab_bg_1'><td>" . __('Total count') . "</td>";
                 echo "<td class='right'>" . sprintf(
                     _n('%s item', '%s items', $data['voltot']),
                     $data['voltot']
                 ) . "</td></tr>";

                 echo "<tr class='tab_bg_2'><td>" . __('Average speed') . "</td>";
                 echo "<td class='left'>" . sprintf(
                     __('%s items/sec'),
                     number_format($data['voltot'] / $data['elapsedtot'], 2)
                 );
                 echo "</td></tr>";
            }
        }
        echo "</table></div>";
    }


    /**
     * Display list of a runned tasks
     *
     * @return void
     **/
    public function showHistory()
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (isset($_GET["crontasklogs_id"]) && $_GET["crontasklogs_id"]) {
            $this->showHistoryDetail($_GET["crontasklogs_id"]);
            return;
        }

        if (isset($_GET["start"])) {
            $start = $_GET["start"];
        } else {
            $start = 0;
        }

       // Total Number of events
        $number = countElementsInTable(
            'glpi_crontasklogs',
            [
                'crontasks_id' => $this->fields['id'],
                'state'        => [CronTaskLog::STATE_STOP, CronTaskLog::STATE_ERROR],
            ]
        );

        echo "<br><div class='center'>";
        if ($number < 1) {
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th>" . __('No item found') . "</th></tr>";
            echo "</table>";
            echo "</div>";
            return;
        }

       // Display the pager
        Html::printAjaxPager(__('Last run list'), $start, $number);

        $iterator = $DB->request([
            'FROM'   => 'glpi_crontasklogs',
            'WHERE'  => [
                'crontasks_id' => $this->fields['id'],
                'state'        => [CronTaskLog::STATE_STOP, CronTaskLog::STATE_ERROR],
            ],
            'ORDER'  => 'id DESC',
            'START'  => (int)$start,
            'LIMIT'  => (int)$_SESSION['glpilist_limit']
        ]);

        if (count($iterator)) {
            echo "<table class='tab_cadrehov'>";
            $header = "<tr>";
            $header .= "<th>" . _n('Date', 'Dates', 1) . "</th>";
            $header .= "<th>" . __('Total duration') . "</th>";
            $header .= "<th>" . _x('quantity', 'Number') . "</th>";
            $header .= "<th>" . __('Description') . "</th>";
            $header .= "</tr>\n";
            echo $header;

            foreach ($iterator as $data) {
                echo "<tr class='tab_bg_2'>";
                echo "<td><a href='javascript:reloadTab(\"crontasklogs_id=" .
                        $data['crontasklogs_id'] . "\");'>" . Html::convDateTime($data['date']) .
                  "</a></td>";
                echo "<td class='right'>" . sprintf(
                    _n(
                        '%s second',
                        '%s seconds',
                        intval($data['elapsed'])
                    ),
                    number_format($data['elapsed'], 3)
                ) .
                    "&nbsp;&nbsp;&nbsp;</td>";
                echo "<td class='numeric'>" . $data['volume'] . "</td>";
               // Use gettext to display
                echo "<td>" . __($data['content']) . "</td>";
                echo "</tr>\n";
            }
            echo $header;
            echo "</table>";
        } else { // Not found
            echo __('No item found');
        }
        Html::printAjaxPager(__('Last run list'), $start, $number);

        echo "</div>";
    }


    /**
     * Display detail of a runned task
     *
     * @param $logid : crontasklogs_id
     *
     * @return void
     **/
    public function showHistoryDetail($logid)
    {
        /** @var \DBmysql $DB */
        global $DB;

        echo "<br><div class='center'>";
        echo "<p><a href='javascript:reloadTab(\"crontasklogs_id=0\");'>" . __('Last run list') . "</a>" .
           "</p>";

        $iterator = $DB->request([
            'FROM'   => 'glpi_crontasklogs',
            'WHERE'  => [
                'OR' => [
                    'id'              => $logid,
                    'crontasklogs_id' => $logid
                ]
            ],
            'ORDER'  => 'id ASC'
        ]);

        if (count($iterator)) {
            echo "<table class='tab_cadrehov'><tr>";
            echo "<th>" . _n('Date', 'Dates', 1) . "</th>";
            echo "<th>" . __('Status') . "</th>";
            echo "<th>" . __('Duration') . "</th>";
            echo "<th>" . _x('quantity', 'Number') . "</th>";
            echo "<th>" . __('Description') . "</th>";
            echo "</tr>\n";

            $first = true;
            foreach ($iterator as $data) {
                echo "<tr class='tab_bg_2'>";
                echo "<td class='center'>" . ($first ? Html::convDateTime($data['date'])
                                                : "&nbsp;") . "</a></td>";
                $content = $data['content'];
                switch ($data['state']) {
                    case CronTaskLog::STATE_START:
                        echo "<td>" . __('Start') . "</td>";
                     // Pass content to gettext
                     // implode (Run mode: XXX)
                        $list = explode(':', $data['content']);
                        if (count($list) == 2) {
                               $content = sprintf('%1$s: %2$s', __($list[0]), $list[1]);
                        }
                        break;

                    case CronTaskLog::STATE_STOP:
                        echo "<td>" . __('End') . "</td>";
                   // Pass content to gettext
                        $content = __($data['content']);
                        break;

                    case CronTaskLog::STATE_ERROR:
                        echo "<td>" . __('Error') . "</td>";
                        // Pass content to gettext
                        $content = __($data['content']);
                        break;

                    default:
                         echo "<td>" . __('Running') . "</td>";
                         // Pass content to gettext
                         $content = __($data['content']);
                }

                echo "<td class='right'>" . sprintf(
                    _n(
                        '%s second',
                        '%s seconds',
                        intval($data['elapsed'])
                    ),
                    number_format($data['elapsed'], 3)
                ) .
                    "&nbsp;&nbsp;</td>";
                echo "<td class='numeric'>" . $data['volume'] . "</td>";

                echo "<td>" . $content . "</td>";
                echo "</tr>\n";
                $first = false;
            };

            echo "</table>";
        } else { // Not found
            echo __('No item found');
        }

        echo "</div>";
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

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = 0;
        switch ($field) {
            case 'mode':
                $options['value']         = $values[$field];
                $tab = [
                    self::MODE_INTERNAL => self::getModeName(self::MODE_INTERNAL),
                    self::MODE_EXTERNAL => self::getModeName(self::MODE_EXTERNAL),
                ];
                return Dropdown::showFromArray($name, $tab, $options);

            case 'state':
                return CronTask::dropdownState($name, $values[$field], false);
        }

        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }


    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'mode':
                return self::getModeName($values[$field]);

            case 'state':
                return self::getStateName($values[$field]);
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }


    public function getSpecificMassiveActions($checkitem = null)
    {

        $isadmin = static::canUpdate();
        $actions = parent::getSpecificMassiveActions($checkitem);

        if ($isadmin) {
            $actions[__CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'reset'] = __('Reset last run');
        }
        return $actions;
    }


    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {
        /** @var CronTask $item */
        switch ($ma->getAction()) {
            case 'reset':
                foreach ($ids as $key) {
                    if (Config::canUpdate()) {
                        if ($item->getFromDB($key)) {
                            if ($item->resetDate()) {
                                 $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                            } else {
                                $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                                $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                            }
                        } else {
                            $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                            $ma->addMessage($item->getErrorMessage(ERROR_NOT_FOUND));
                        }
                    } else {
                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_NORIGHT);
                        $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                    }
                }
                return;
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }


    public function rawSearchOptions()
    {
        /** @var \DBmysql $DB */
        global $DB;

        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics')
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => $this->getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
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
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'description',
            'name'               => __('Description'),
            'nosearch'           => true,
            'nosort'             => true,
            'massiveaction'      => false,
            'datatype'           => 'text',
            'computation'        => $DB->quoteName('TABLE.id') // Virtual data
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => $this->getTable(),
            'field'              => 'state',
            'name'               => __('Status'),
            'searchtype'         => ['equals', 'notequals'],
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'mode',
            'name'               => __('Run mode'),
            'datatype'           => 'specific',
            'searchtype'         => ['equals', 'notequals']
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => $this->getTable(),
            'field'              => 'frequency',
            'name'               => __('Run frequency'),
            'datatype'           => 'timestamp',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => $this->getTable(),
            'field'              => 'lastrun',
            'name'               => __('Last run'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '8',
            'table'              => $this->getTable(),
            'field'              => 'itemtype',
            'name'               => __('Item type'),
            'massiveaction'      => false,
            'datatype'           => 'itemtypename',
            'types'              => self::getUsedItemtypes()
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => $this->getTable(),
            'field'              => 'comment',
            'name'               => __('Comments'),
            'datatype'           => 'text'
        ];

        $tab[] = [
            'id'                 => '17',
            'table'              => $this->getTable(),
            'field'              => 'hourmin',
            'name'               => __('Begin hour of run period'),
            'datatype'           => 'integer',
            'min'                => 0,
            'max'                => 24
        ];

        $tab[] = [
            'id'                 => '18',
            'table'              => $this->getTable(),
            'field'              => 'hourmax',
            'name'               => __('End hour of run period'),
            'datatype'           => 'integer',
            'min'                => 0,
            'max'                => 24
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => $this->getTable(),
            'field'              => 'logs_lifetime',
            'name'               => __('Number of days this action logs are stored'),
            'datatype'           => 'integer',
            'min'                => 10,
            'max'                => 360,
            'step'               => 10,
            'toadd'              => [
                '0'                  => 'Infinite'
            ]
        ];

        $tab[] = [
            'id'                 => '20',
            'table'              => $this->getTable(),
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '121',
            'table'              => $this->getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        return $tab;
    }


    /**
     * Garbage collector for expired file session
     *
     * @param CronTask $task for log
     *
     * @return integer
     **/
    public static function cronSession(CronTask $task)
    {

       // max time to keep the file session
        $maxlifetime = ini_get('session.gc_maxlifetime');
        if ($maxlifetime == 0) {
            $maxlifetime = WEEK_TIMESTAMP;
        }
        $nb = 0;
        foreach (glob(GLPI_SESSION_DIR . "/sess_*") as $filename) {
            if ((filemtime($filename) + $maxlifetime) < time()) {
               // Delete session file if not delete before
                if (@unlink($filename)) {
                    $nb++;
                }
            }
        }

        $task->setVolume($nb);
        if ($nb) {
           //TRANS: % %1$d is a number, %2$s is a number of seconds
            $task->log(sprintf(
                _n(
                    'Clean %1$d session file created since more than %2$s seconds',
                    'Clean %1$d session files created since more than %2$s seconds',
                    $nb
                ) . "\n",
                $nb,
                $maxlifetime
            ));
            return 1;
        }

        return 0;
    }


    /**
     * Circular logs
     *
     * @since 0.85
     *
     * @param CronTask $task for log
     *
     * @return integer
     **/
    public static function cronCircularlogs(CronTask $task)
    {

        $actionCode = 0; // by default
        $error      = false;
        $task->setVolume(0); // start with zero

       // compute date in the past for the archived log to be deleted
        $firstdate = date("Ymd", time() - ($task->fields['param'] * DAY_TIMESTAMP)); // compute current date - param as days and format it like YYYYMMDD

       // first look for bak to delete
        $dir       = GLPI_LOG_DIR . "/*.bak";
        $findfiles = glob($dir);
        foreach ($findfiles as $file) {
            $shortfile = str_replace(GLPI_LOG_DIR . '/', '', $file);
           // now depending on the format of the name we delete the file (for aging archives) or rename it (will add Ymd.log to the end of the file)
            $match = null;
            if (preg_match('/.+[.]log[.](\\d{8})[.]bak$/', $file, $match) > 0) {
                if ($match[1] < $firstdate) {
                    $task->addVolume(1);
                    if (unlink($file)) {
                        $task->log(sprintf(__('Deletion of archived log file: %s'), $shortfile));
                        $actionCode = 1;
                    } else {
                        $task->log(sprintf(__('Unable to delete archived log file: %s'), $shortfile));
                        $error = true;
                    }
                }
            }
        }

       // second look for log to archive
        $dir       = GLPI_LOG_DIR . "/*.log";
        $findfiles = glob($dir);
        foreach ($findfiles as $file) {
            $shortfile    = str_replace(GLPI_LOG_DIR . '/', '', $file);
           // rename the file
            $newfilename  = $file . "." . date("Ymd", time()) . ".bak"; // will add to filename a string with format YYYYMMDD (= current date)
            $shortnewfile = str_replace(GLPI_LOG_DIR . '/', '', $newfilename);

            $task->addVolume(1);
            if (!file_exists($newfilename) && rename($file, $newfilename)) {
                $task->log(sprintf(__('Archiving log file: %1$s to %2$s'), $shortfile, $shortnewfile));
                $actionCode = 1;
            } else {
                $task->log(sprintf(
                    __('Unable to archive log file: %1$s. %2$s already exists. Wait till next day.'),
                    $shortfile,
                    $shortnewfile
                ));
                $error = true;
            }
        }

        if ($error) {
            return -1;
        }
        return $actionCode;
    }


    /**
     * Garbage collector for cleaning graph files
     *
     * @param CronTask $task for log
     *
     * @return integer
     **/
    public static function cronGraph(CronTask $task)
    {

       // max time to keep the file session
        $maxlifetime = HOUR_TIMESTAMP;
        $nb          = 0;
        foreach (glob(GLPI_GRAPH_DIR . "/*") as $filename) {
            if (basename($filename) == "remove.txt" && is_dir(GLPI_ROOT . '/.git')) {
                continue;
            }
            if ((filemtime($filename) + $maxlifetime) < time()) {
                if (@unlink($filename)) {
                    $nb++;
                }
            }
        }

        $task->setVolume($nb);
        if ($nb) {
            $task->log(sprintf(
                _n(
                    'Clean %1$d graph file created since more than %2$s seconds',
                    'Clean %1$d graph files created since more than %2$s seconds',
                    $nb
                ) . "\n",
                $nb,
                $maxlifetime
            ));
            return 1;
        }

        return 0;
    }

    /**
     * Garbage collector for cleaning tmp files
     *
     * @param CronTask $task for log
     *
     * @return integer
     **/
    public static function cronTemp(CronTask $task)
    {

       // max time to keep the file session
        $maxlifetime = HOUR_TIMESTAMP;
        $nb          = 0;

        $dir = new RecursiveDirectoryIterator(GLPI_TMP_DIR, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator(
            $dir,
            RecursiveIteratorIterator::CHILD_FIRST
        );

       //first step unlike only file if needed
        foreach ($files as $filename) {
            if (basename($filename) == ".gitkeep") {
                continue;
            }

            if (
                is_file($filename) && is_writable($filename)
                && (filemtime($filename) + $maxlifetime) < time()
            ) {
                if (@unlink($filename)) {
                    $nb++;
                }
            }

            if (
                is_dir($filename) && is_readable($filename)
                //be sure that the directory is empty
                && count(scandir($filename)) == 2
            ) {
                if (@rmdir($filename)) {
                    $nb++;
                }
            }
        }

        $task->setVolume($nb);
        if ($nb) {
            $task->log(sprintf(
                _n(
                    'Clean %1$d temporary file created since more than %2$s seconds',
                    'Clean %1$d temporary files created since more than %2$s seconds',
                    $nb
                ) . "\n",
                $nb,
                $maxlifetime
            ));
            return 1;
        }

        return 0;
    }

    /**
     * Clean log cron function
     *
     * @param CronTask $task
     *
     * @return integer
     **/
    public static function cronLogs($task)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $vol = 0;

       // Expire Event Log
        if ($task->fields['param'] > 0) {
            $vol += Event::cleanOld($task->fields['param']);
        }

        foreach ($DB->request(self::getTable()) as $data) {
            if ($data['logs_lifetime'] > 0) {
                $vol += CronTaskLog::cleanOld($data['id'], $data['logs_lifetime']);
            }
        }
        $task->setVolume($vol);
        return ($vol > 0 ? 1 : 0);
    }


    /**
     * Cron job to check if a new version is available
     *
     * @param CronTask $task for log
     *
     * @return integer
     **/
    public static function cronCheckUpdate($task)
    {

        $result = Toolbox::checkNewVersionAvailable();
        $task->log($result);

        return 1;
    }


    /**
     * Check zombie crontask
     *
     * @param CronTask $task for log
     *
     * @return integer
     **/
    public static function cronWatcher($task)
    {
        /** @var \DBmysql $DB */
        global $DB;

       // CronTasks running for more than 1 hour or 2 frequency
        $iterator = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'state'  => self::STATE_RUNNING,
                'OR'     => [
                    new \QueryExpression('unix_timestamp(' . $DB->quoteName('lastrun') . ') + 2 * ' . $DB->quoteName('frequency') . ' < unix_timestamp(now())'),
                    new \QueryExpression('unix_timestamp(' . $DB->quoteName('lastrun') . ') + 2 * ' . HOUR_TIMESTAMP . ' < unix_timestamp(now())')
                ]
            ]
        ]);
        $crontasks = [];
        foreach ($iterator as $data) {
            $crontasks[$data['id']] = $data;
        }

        if (count($crontasks)) {
            $task = new self();
            $task->getFromDBByCrit(['itemtype' => 'CronTask', 'name' => 'watcher']);
            if (NotificationEvent::raiseEvent("alert", $task, ['items' => $crontasks])) {
                $task->addVolume(1);
            }
            QueuedNotification::forceSendFor($task->getType(), $task->fields['id']);
        }

        return 1;
    }


    /**
     * get Cron description parameter for this class
     *
     * @param $name string name of the task
     *
     * @return array of string
     **/
    public static function cronInfo($name)
    {

        switch ($name) {
            case 'checkupdate':
                return ['description' => __('Check for new updates')];

            case 'logs':
                return ['description' => __('Clean old logs'),
                    'parameter'
                           => __('System logs retention period (in days, 0 for infinite)')
                ];

            case 'session':
                return ['description' => __('Clean expired sessions')];

            case 'graph':
                return ['description' => __('Clean generated graphics')];

            case 'temp':
                return ['description' => __('Clean temporary files')];

            case 'watcher':
                return ['description' => __('Monitoring of automatic actions')];

            case 'circularlogs':
                return ['description' => __("Archives log files and deletes aging ones"),
                    'parameter'   => __("Number of days to keep archived logs")
                ];
        }

        return [];
    }


    /**
     * Call cron without time check
     *
     * @return boolean : true if launched
     **/
    public static function callCronForce()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (self::mustRunWebTasks()) {
            $path = $CFG_GLPI['root_doc'] . "/front/cron.php";
            echo "<div style=\"background-image: url('$path');\"></div>";
        }

        return true;
    }


    /**
     * Check if any web cron task exist and is enabled
     *
     * @return bool
     **/
    protected static function mustRunWebTasks(): bool
    {
        $web_tasks_count = countElementsInTable(self::getTable(), [
            'mode'  => self::MODE_INTERNAL, // "GLPI" mode
            'state' => self::STATE_WAITING,
        ]);

        return $web_tasks_count > 0;
    }


    /**
     * Call cron if time since last launch elapsed
     *
     * @return void
     **/
    public static function callCron()
    {

        if (isset($_SESSION["glpicrontimer"])) {
           // call static function callcron() every 5min
            if ((time() - $_SESSION["glpicrontimer"]) > 300) {
                if (self::callCronForce()) {
                   // Restart timer
                    $_SESSION["glpicrontimer"] = time();
                }
            }
        } else {
           // Start timer
            $_SESSION["glpicrontimer"] = time();
        }
    }


    public static function getIcon()
    {
        return "ti ti-settings-automation";
    }
}
