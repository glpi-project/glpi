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

// Needed for signal handler to handle SIGTERM in CLI mode
declare(ticks=1);

use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use Glpi\Error\ErrorHandler;
use Glpi\Event;
use Safe\Exceptions\FilesystemException;

use function Safe\filemtime;
use function Safe\glob;
use function Safe\ini_get;
use function Safe\pcntl_signal;
use function Safe\preg_match;
use function Safe\rmdir;
use function Safe\scandir;
use function Safe\strtotime;
use function Safe\unlink;

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

    /** The automatic action is disabled */
    public const STATE_DISABLE = 0;
    /** The automatic action is enabled and waiting to be run */
    public const STATE_WAITING = 1;
    /** The automatic action was started and hasn't returned to the waiting state yet */
    public const STATE_RUNNING = 2;

    /** The automatic action is run internally (run by GLPI via a hidden image src) */
    public const MODE_INTERNAL = 1;
    /** The automatic action is run with an external scheduler like cron or Task Scheduler */
    public const MODE_EXTERNAL = 2;

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

    public static function getSectorizedDetails(): array
    {
        return ['config', self::class];
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addImpactTab($ong, $options);
        $this->addStandardTab(CronTaskLog::class, $ong, $options);
        $this->addStandardTab(Log::class, $ong, $options);

        return $ong;
    }

    public static function canDelete(): bool
    {
        return false;
    }

    public function cleanDBonPurge()
    {
        // Delete related CronTaskLog.
        // It cannot be done with `deleteChildrenAndRelationsFromDb` because `CronTaskLog` does not extend CommonDBConnexity.
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
        $table = self::getTable();
        return $this->getFromDBByCrit([
            $table . '.name'      => (string) $name,
            $table . '.itemtype'  => (string) $itemtype,
        ]);
    }

    /**
     * Check if the task is disabled and the reason if so.
     *
     * @return integer
     * <ul>
     *    <li>0: Enabled</li>
     *    <li>1: Disabled by task configuration</li>
     *    <li>2: Disabled by system lock</li>
     *    <li>3: Task belongs to a disabled plugin</li>
     * </ul>
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
        global $DB;

        $types = [];
        $iterator = $DB->request([
            'SELECT'          => ['itemtype'],
            'DISTINCT'        => true,
            'FROM'            => self::getTable(),
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
     * @todo Is there an alternative way to handle this? ext-pcntl is not enabled by default in PHP and isn't available on Windows.
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
            exit(); // @phpstan-ignore glpi.forbidExit (CLI context)
        }
    }

    /**
     * Start a task, timer, stat, log, ...
     *
     * @return bool : true if ok (not start by another)
     **/
    public function start()
    {
        global $DB;

        if (!isset($this->fields['id']) || ($DB->isSlave())) {
            return false;
        }

        if (isCommandLine() && function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, [$this, 'signal']);
        }

        $DB->update(
            self::getTable(),
            [
                'state'  => self::STATE_RUNNING,
                'lastrun'   => QueryFunction::dateFormat(
                    expression: QueryFunction::now(),
                    format: '%Y-%m-%d %H:%i:00'
                ),
            ],
            [
                'id'  => $this->fields['id'],
                'NOT' => ['state' => self::STATE_RUNNING],
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
                self::getModeName(isCommandLine() ? self::MODE_EXTERNAL
                : self::MODE_INTERNAL)
            );

            $this->startlog = $log->add([
                'crontasks_id'    => $this->fields['id'],
                'date'            => $_SESSION['glpi_currenttime'],
                'content'         => $txt,
                'crontasklogs_id' => 0,
                'state'           => CronTaskLog::STATE_START,
                'volume'          => 0,
                'elapsed'         => 0,
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
     * <ul>
     *    <li>&lt; 0: Need to run again</li>
     *    <li>0: Nothing to do</li>
     *   <li>&gt; 0: Ok</li>
     * </ul>
     * @param int $log_state
     *
     * @return bool : true if ok (not start by another)
     *
     * @since 9.5.5 Added parameter $log_state.
     **/
    public function end($retcode, int $log_state = CronTaskLog::STATE_STOP)
    {
        global $DB;

        if (!isset($this->fields['id'])) {
            return false;
        }

        $DB->update(
            self::getTable(),
            [
                'state'  => $this->fields['state'],
            ],
            [
                'id'     => $this->fields['id'],
                'state'  => self::STATE_RUNNING,
            ]
        );

        if ($DB->affectedRows() > 0) {
            // No gettext for log but add gettext line to be parsed for pot generation
            // order is important for insertion in english in the database
            if ($log_state === CronTaskLog::STATE_ERROR) {
                $content = __('Execution error');
                $content = 'Execution error';
            } elseif (is_null($retcode)) {
                $content = __('Action aborted');
                $content = 'Action aborted';
            } elseif ($retcode < 0) {
                $content = __('Action completed, partially processed');
                $content = 'Action completed, partially processed';
            } elseif ($retcode > 0) {
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
                'elapsed'         => (microtime(true) - $this->timer),
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
            'content'         => $content,
            'crontasklogs_id' => $this->startlog,
            'state'           => CronTaskLog::STATE_RUN,
            'volume'          => $this->volume,
            'elapsed'         => (microtime(true) - $this->timer),
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
        global $DB;

        $hour_criteria = new QueryExpression('hour(curtime())');

        $itemtype_orwhere = [
            // Core crontasks
            [
                ['NOT' => ['itemtype' => ['LIKE', 'Plugin%']]],
                ['NOT' => ['itemtype' => ['LIKE', 'GlpiPlugin\\\\' . '%']]],
            ],
        ];
        foreach (Plugin::getPlugins() as $plug) {
            // Activated plugin tasks
            $itemtype_orwhere[] = [
                'OR' => [
                    ['itemtype' => ['LIKE', sprintf('Plugin%s', $plug) . '%']],
                    ['itemtype' => ['LIKE', sprintf('GlpiPlugin\\\\%s\\\\', $plug) . '%']],
                ],
            ];
        }

        $WHERE = [
            ['OR' => $itemtype_orwhere],
        ];

        if ($name) {
            $WHERE['name'] = $name;
        }

        // In force mode
        if ($mode < 0) {
            $WHERE['state'] = ['!=', self::STATE_RUNNING];
            $WHERE['allowmode'] = ['&', (int) $mode * -1];
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
                    ['hourmin'   => ['<', new QueryExpression($DB::quoteName('hourmax'))]],
                    'hourmin'   => ['<=', $hour_criteria],
                    'hourmax'   => ['>', $hour_criteria],
                ],
                ],
                ['AND' => [
                    'hourmin'   => ['>', new QueryExpression($DB::quoteName('hourmax'))],
                    'OR'        => [
                        'hourmin'   => ['<=', $hour_criteria],
                        'hourmax'   => ['>', $hour_criteria],
                    ],
                ],
                ],
            ],
            ];
            $WHERE[] = [
                'OR' => [
                    'lastrun'   => null,
                    new QueryExpression(QueryFunction::unixTimestamp('lastrun') . ' + ' . $DB::quoteName('frequency') . ' <= ' . QueryFunction::unixTimestamp()),
                ],
            ];
        }

        $iterator = $DB->request([
            'SELECT' => [
                '*',
                QueryFunction::locate(
                    substring: 'Plugin',
                    expression: $DB::quoteName('itemtype'),
                    alias: 'ISPLUGIN'
                ),
            ],
            'FROM'   => self::getTable(),
            'WHERE'  => $WHERE,
            // Core task before plugins
            'ORDER'  => [
                'ISPLUGIN',
                new QueryExpression(QueryFunction::unixTimestamp($DB::quoteName('lastrun')) . ' + ' . $DB::quoteName('frequency')),
            ],
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
        global $DB;

        $alert_iterator = $DB->request(
            [
                'FROM'      => 'glpi_alerts',
                'WHERE'     => [
                    'items_id' => $this->fields['id'],
                    'itemtype' => 'CronTask',
                    'date'     => ['>',
                        QueryFunction::dateSub(
                            date: QueryFunction::now(),
                            interval: 1,
                            interval_unit: 'DAY'
                        ),
                    ],
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
                'LIMIT'  => $threshold * 2,
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

            // Delete existing outdated alerts
            $alert = new Alert();
            $alert->deleteByCriteria(['itemtype' => 'CronTask', 'items_id' => $this->fields['id']], true);

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
        if (!Config::canView() || !$this->getFromDB($ID)) {
            return false;
        }
        $options['candel'] = false;

        if (empty($this->fields['lastrun'])) {
            $next_run_display = __('As soon as possible');
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
                $next_run_display = date('Y-m-d', $next) . " $deb:00:00";
                $next = strtotime($next_run_display);
            } elseif (
                ($deb < $fin)
                    && ($h >= $this->fields['hourmax'])
            ) {
                $next_run_display = date('Y-m-d', $next + DAY_TIMESTAMP) . " $deb:00:00";
                $next = strtotime($next_run_display);
            }

            if (
                ($deb > $fin)
                && ($h < $deb)
                && ($h >= $fin)
            ) {
                $next_run_display = date('Y-m-d', $next) . " $deb:00:00";
                $next = strtotime($next_run_display);
            } else {
                $next_run_display = date("Y-m-d H:i:s", $next);
            }

            if ($next < time()) {
                $next_run_display = __('As soon as possible') . ' (' . Html::convDateTime($next_run_display) . ') ';
            } else {
                $next_run_display = Html::convDateTime($next_run_display);
            }
        }

        TemplateRenderer::getInstance()->display('pages/setup/crontask/crontask.html.twig', [
            'item' => $this,
            'params' => $options,
            'plugin_info' => isPluginItemType($this->fields["itemtype"]),
            'item_meta' => [
                'next_run_display' => $next_run_display ?? __('As soon as possible'),
                'param_description' => $this->getParameterDescription(),
            ],
        ]);
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
        return $this->update([
            'id'      => $this->fields['id'],
            'lastrun' => 'NULL',
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
        return $this->update([
            'id'    => $this->fields['id'],
            'state' => self::STATE_WAITING,
        ]);
    }

    /**
     * Translate task description
     *
     * @param integer $id ID of the crontask
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
            $info = $hook($this->fields['name']);
        } else {
            $info = false;
        }

        return $info['description'] ?? $this->fields['name'];
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
            $info = $hook($this->fields['name']);
        } else {
            $info = false;
        }

        return $info['parameter'] ?? '';
    }

    /**
     * Translate state to string
     *
     * @param integer $state
     * @phpstan-param self::STATE_* $state
     *
     * @return string
     **/
    public static function getStateName($state)
    {
        return match ($state) {
            self::STATE_RUNNING => __('Running'),
            self::STATE_WAITING => __('Scheduled'),
            self::STATE_DISABLE => __('Disabled'),
            default => '???',
        };
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
                self::STATE_WAITING => __('Scheduled'),
            ],
            ['value'   => $value,
                'display' => $display,
            ]
        );
    }

    /**
     * Translate Mode to string
     *
     * @param integer $mode
     * @phpstan-param self::MODE_* $mode
     *
     * @return string
     **/
    public static function getModeName($mode)
    {
        return match ($mode) {
            self::MODE_INTERNAL => __('GLPI'),
            self::MODE_EXTERNAL => __('CLI'),
            default => '???',
        };
    }

    /**
     * Get a global database lock for cron
     *
     * @return boolean
     **/
    private static function get_lock()
    {
        global $DB;

        // Change name every hour in case of MySQL blocking (it happens)
        $name = "glpicron." . (int) (time() / HOUR_TIMESTAMP - 340000);

        if ($DB->getLock($name)) {
            self::$lockname = $name;
            return true;
        }

        return false;
    }

    /**
     * Release the global database lock
     **/
    private static function release_lock()
    {
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
        global $CFG_GLPI;

        // No cron in maintenance mode
        if (isset($CFG_GLPI['maintenance_mode']) && $CFG_GLPI['maintenance_mode']) {
            Toolbox::logInFile('cron', __('Maintenance mode enabled, running tasks is disabled') . "\n");
            return false;
        }

        $crontask = new self();
        $taskname = '';
        if (abs($mode) === self::MODE_EXTERNAL) {
            // If cron is launched in command line, and if memory is insufficient,
            // display a warning in the logs
            if (Toolbox::checkMemoryLimit() === 2) {
                Toolbox::logInFile('cron', __('A minimum of 64 Mio is commonly required for GLPI.') . "\n");
            }
            // If no task in CLI mode, call cron.php from command line is not really usefull ;)
            if (!countElementsInTable(self::getTable(), ['mode' => abs($mode)])) {
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
                    abs($mode) === self::MODE_EXTERNAL ? __('External') : __('Internal'),
                    $i
                );
                if ($crontask->getNeedToRun($mode, $name)) {
                    $_SESSION["glpicronuserrunning"] = "cron_" . $crontask->fields['name'];
                    Session::loadEntity(0, true);
                    $_SESSION["glpigroups"]          = [];
                    $_SESSION["glpiname"]            = "cron";

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
                                $retcode = $function($crontask);
                            } catch (Throwable $e) {
                                ErrorHandler::logCaughtException($e);
                                ErrorHandler::displayCaughtExceptionMessage($e);
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
                } elseif ($i === 1) {
                    $msgcron = sprintf(__('%1$s: %2$s'), $msgprefix, __('Nothing to launch'));
                    Toolbox::logInFile('cron', $msgcron . "\n");
                }
            }

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
     * @phpstan-param array{
     *   state?: CronTask::STATE_*,
     *   mode?: CronTask::MODE_*,
     *   allowmode?: int,
     *   hourmin?: int,
     *   hourmax?: int,
     *   logs_lifetime?: int,
     *   param?: int,
     *   comment?: string
     * } $options
     *
     * @return boolean
     **/
    public static function register($itemtype, $name, $frequency, $options = [])
    {
        // Check that hook exists
        if (!isPluginItemType($itemtype) && !class_exists($itemtype)) {
            return false;
        }

        $temp = new self();
        // Avoid duplicate entry
        if ($temp->getFromDBbyName($itemtype, $name)) {
            return false;
        }
        $input = [
            'itemtype'  => $itemtype,
            'name'      => $name,
            'allowmode' => self::MODE_INTERNAL | self::MODE_EXTERNAL,
            'frequency' => $frequency,
        ];

        $fields = ['allowmode', 'comment', 'hourmax', 'hourmin', 'logs_lifetime', 'mode', 'param', 'state'];
        foreach ($fields as $key) {
            if (isset($options[$key])) {
                $input[$key] = $options[$key];
            }
        }
        if (
            GLPI_SYSTEM_CRON
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
     * @param string $plugin Name of the plugin
     *
     * @return bool for success
     **/
    public static function unregister($plugin)
    {
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
                    ['itemtype' => ['LIKE', sprintf('GlpiPlugin\\\\%s\\\\', $plugin) . '%']],
                ],
            ],
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
        global $DB;

        $nbstart = countElementsInTable(
            'glpi_crontasklogs',
            ['crontasks_id' => $this->fields['id'],
                'state'        => CronTaskLog::STATE_START,
            ]
        );
        $nbstop  = countElementsInTable(
            'glpi_crontasklogs',
            ['crontasks_id' => $this->fields['id'],
                'state'        => CronTaskLog::STATE_STOP,
            ]
        );
        $nberror = countElementsInTable(
            'glpi_crontasklogs',
            ['crontasks_id' => $this->fields['id'],
                'state'        => CronTaskLog::STATE_ERROR,
            ]
        );

        $stats = [
            'runs' => [
                'starts'    => $nbstart,
                'stops'     => $nbstop,
                'errors'    => $nberror,
            ],
            'datemin'       => 0,
            'elapsedmin'    => 0,
            'elapsedmax'    => 0,
            'elapsedavg'    => 0,
            'elapsedtot'    => 0,
            'volmin'        => 0,
            'volmax'        => 0,
            'volavg'        => 0,
            'voltot'        => 0,
        ];

        if ($nbstop) {
            $data = $DB->request([
                'SELECT' => [
                    'MIN' => [
                        'date AS datemin',
                        'elapsed AS elapsedmin',
                        'volume AS volmin',
                    ],
                    'MAX' => [
                        'elapsed AS elapsedmax',
                        'volume AS volmax',
                    ],
                    'SUM' => [
                        'elapsed AS elapsedtot',
                        'volume AS voltot',
                    ],
                    'AVG' => [
                        'elapsed AS elapsedavg',
                        'volume AS volavg',
                    ],
                ],
                'FROM'   => CronTaskLog::getTable(),
                'WHERE'  => [
                    'crontasks_id' => $this->fields['id'],
                    'state'        => CronTaskLog::STATE_STOP,
                ],
            ])->current();

            $stats['datemin'] = $data['datemin'];
            $stats['elapsedmin'] = $data['elapsedmin'];
            $stats['elapsedmax'] = $data['elapsedmax'];
            $stats['elapsedavg'] = $data['elapsedavg'];
            $stats['elapsedtot'] = $data['elapsedtot'];

            if ($data['voltot'] > 0) {
                $stats['volmin'] = $data['volmin'];
                $stats['volmax'] = $data['volmax'];
                $stats['volavg'] = $data['volavg'];
                $stats['voltot'] = $data['voltot'];
            }
        }

        TemplateRenderer::getInstance()->display('pages/setup/crontask/statistics.html.twig', [
            'stats' => $stats,
        ]);
    }

    /**
     * Display list of a runned tasks
     *
     * @return void
     **/
    public function showHistory()
    {
        global $DB;

        if (isset($_GET["crontasklogs_id"]) && $_GET["crontasklogs_id"]) {
            $this->showHistoryDetail($_GET["crontasklogs_id"]);
            return;
        }

        $start = (int) ($_GET["start"] ?? 0);

        $criteria = [
            'FROM'   => 'glpi_crontasklogs',
            'WHERE'  => [
                'crontasks_id' => $this->fields['id'],
                'state'        => [CronTaskLog::STATE_STOP, CronTaskLog::STATE_ERROR],
            ],
            'ORDER'  => 'id DESC',
            'START'  => $start,
            'LIMIT'  => (int) $_SESSION['glpilist_limit'],
        ];
        $iterator = $DB->request($criteria);
        $count_criteria = $criteria;
        unset($count_criteria['START'], $count_criteria['LIMIT']);
        $count_criteria['COUNT'] = 'cpt';
        $total_count = $DB->request($count_criteria)->current()['cpt'];

        $entries = [];
        foreach ($iterator as $data) {
            $entries[] = [
                'itemtype' => CronTaskLog::class,
                'id'       => $data['id'],
                'date'     => sprintf(
                    '<a href="javascript:reloadTab(\'crontasklogs_id=%s\');">%s</a>',
                    (int) $data['id'],
                    htmlescape(Html::convDateTime($data['date']))
                ),
                'elapsed'  => $data['elapsed'],
                'volume'   => $data['volume'],
                'content'  => $data['content'],
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'columns' => [
                'date' => _n('Date', 'Dates', 1),
                'elapsed' => __('Total duration'),
                'volume' => _x('quantity', 'Number'),
                'content' => __('Description'),
            ],
            'formatters' => [
                'date' => 'raw_html',
                'elapsed' => 'duration',
                'volume' => 'integer',
            ],
            'entries' => $entries,
            'total_number' => $total_count,
            'filtered_number' => $total_count,
            'showmassiveactions' => false,
        ]);
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
        global $DB;

        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            <div class="my-2 text-center">
                <button class="btn btn-outline-secondary" onclick="reloadTab('crontasklogs_id=0')">
                    {{ msg }}
                </button>
            </div>
TWIG, ['msg' => __('Last run list')]);


        $iterator = $DB->request([
            'FROM'   => 'glpi_crontasklogs',
            'WHERE'  => [
                'OR' => [
                    'id'              => $logid,
                    'crontasklogs_id' => $logid,
                ],
            ],
            'ORDER'  => 'id ASC',
        ]);

        $first = true;
        $entries = [];
        foreach ($iterator as $data) {
            $content = $data['content'];
            switch ($data['state']) {
                case CronTaskLog::STATE_START:
                    $state = __('Start');
                    // Pass content to gettext
                    // implode (Run mode: XXX)
                    $list = explode(':', $data['content']);
                    if (count($list) === 2) {
                        $content = sprintf('%1$s: %2$s', __($list[0]), $list[1]);
                    }
                    break;
                case CronTaskLog::STATE_STOP:
                    $state = __('End');
                    // Pass content to gettext
                    $content = __($data['content']);
                    break;
                case CronTaskLog::STATE_ERROR:
                    $state = _n('Error', 'Errors', 1);
                    // Pass content to gettext
                    $content = __($data['content']);
                    break;
                default:
                    $state = __('Running');
                    // Pass content to gettext
                    $content = __($data['content']);
            }

            $entries[] = [
                'itemtype' => CronTaskLog::class,
                'id'       => $data['id'],
                'date'     => $first ? $data['date'] : '',
                'state'   => $state,
                'elapsed'  => $data['elapsed'],
                'volume'   => $data['volume'],
                'content'  => $content,
            ];
            $first = false;
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'columns' => [
                'date' => _n('Date', 'Dates', 1),
                'state' => __('Status'),
                'elapsed' => __('Duration'),
                'volume' => _x('quantity', 'Number'),
                'content' => __('Description'),
            ],
            'formatters' => [
                'date' => 'datetime',
                'elapsed' => 'duration',
                'volume' => 'integer',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => false,
        ]);
    }

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
                return self::dropdownState($name, $values[$field], false);
        }

        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        return match ($field) {
            'mode' => htmlescape(self::getModeName($values[$field])),
            'state' => htmlescape(self::getStateName($values[$field])),
            default => parent::getSpecificValueToDisplay($field, $values, $options),
        };
    }

    public function getSpecificMassiveActions($checkitem = null)
    {
        $isadmin = static::canUpdate();
        $actions = parent::getSpecificMassiveActions($checkitem);

        if ($isadmin) {
            $actions[self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'reset'] = __s('Reset last run');
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
        global $DB;

        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics'),
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => self::getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => self::getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number',
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => self::getTable(),
            'field'              => 'description',
            'name'               => __('Description'),
            'nosearch'           => true,
            'nosort'             => true,
            'massiveaction'      => false,
            'datatype'           => 'text',
            'computation'        => $DB::quoteName('TABLE.id'), // Virtual data
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => self::getTable(),
            'field'              => 'state',
            'name'               => __('Status'),
            'searchtype'         => ['equals', 'notequals'],
            'datatype'           => 'specific',
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => self::getTable(),
            'field'              => 'mode',
            'name'               => __('Run mode'),
            'datatype'           => 'specific',
            'searchtype'         => ['equals', 'notequals'],
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => self::getTable(),
            'field'              => 'frequency',
            'name'               => __('Run frequency'),
            'datatype'           => 'timestamp',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => self::getTable(),
            'field'              => 'lastrun',
            'name'               => __('Last run'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '8',
            'table'              => self::getTable(),
            'field'              => 'itemtype',
            'name'               => __('Item type'),
            'massiveaction'      => false,
            'datatype'           => 'itemtypename',
            'types'              => self::getUsedItemtypes(),
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => self::getTable(),
            'field'              => 'comment',
            'name'               => _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '17',
            'table'              => self::getTable(),
            'field'              => 'hourmin',
            'name'               => __('Begin hour of run period'),
            'datatype'           => 'integer',
            'min'                => 0,
            'max'                => 24,
        ];

        $tab[] = [
            'id'                 => '18',
            'table'              => self::getTable(),
            'field'              => 'hourmax',
            'name'               => __('End hour of run period'),
            'datatype'           => 'integer',
            'min'                => 0,
            'max'                => 24,
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => self::getTable(),
            'field'              => 'logs_lifetime',
            'name'               => __('Number of days this action logs are stored'),
            'datatype'           => 'integer',
            'min'                => 10,
            'max'                => 360,
            'step'               => 10,
            'toadd'              => [
                '0'                  => 'Infinite',
            ],
        ];

        $tab[] = [
            'id'                 => '20',
            'table'              => self::getTable(),
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '121',
            'table'              => self::getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        return $tab;
    }

    /**
     * Garbage collector for expired file session
     *
     * @param CronTask $task for log
     *
     * @return integer
     * @used-by self
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
                try {
                    @unlink($filename);
                    ++$nb;
                } catch (FilesystemException $e) {
                    //mepty catch
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
     * @used-by self
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
                    try {
                        unlink($file);
                        $task->log(sprintf(__('Deletion of archived log file: %s'), $shortfile));
                        $actionCode = 1;
                    } catch (FilesystemException $e) {
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
            if (!file_exists($newfilename) && rename($file, $newfilename)) { // @phpstan-ignore theCodingMachineSafe.function
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
     * @used-by self
     **/
    public static function cronGraph(CronTask $task)
    {
        // max time to keep the file session
        $maxlifetime = HOUR_TIMESTAMP;
        $nb          = 0;
        foreach (glob(GLPI_GRAPH_DIR . "/*") as $filename) {
            if (basename($filename) === "remove.txt" && is_dir(GLPI_ROOT . '/.git')) {
                continue;
            }
            if ((filemtime($filename) + $maxlifetime) < time()) {
                try {
                    @unlink($filename);
                    ++$nb;
                } catch (FilesystemException $e) {
                    //empty catch
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
     * @used-by self
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

        // first step unlink only file if needed
        foreach ($files as $filename) {
            if (basename($filename) === ".gitkeep") {
                continue;
            }

            if (
                is_file($filename) && is_writable($filename)
                && (filemtime($filename) + $maxlifetime) < time()
            ) {
                try {
                    @unlink($filename);
                    ++$nb;
                } catch (FilesystemException $e) {
                    //empty catch
                }
            }

            if (
                is_dir($filename) && is_readable($filename)
                // be sure that the directory is empty
                && count(scandir($filename)) === 2
            ) {
                try {
                    @rmdir($filename);
                    ++$nb;
                } catch (FilesystemException $e) {
                    //empty catch
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
     * @used-by self
     **/
    public static function cronLogs($task)
    {
        global $DB;

        $vol = 0;

        // Expire Event Log
        if ($task->fields['param'] > 0) {
            $vol += Event::cleanOld($task->fields['param']);
        }

        $crontasks = $DB->request(['FROM' => self::getTable()]);
        foreach ($crontasks as $data) {
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
     * @used-by self
     **/
    public static function cronCheckUpdate($task)
    {
        $result = Toolbox::checkNewVersionAvailable();
        $task->log($result);

        return 1;
    }

    /**
     * Get criteria to identify crontasks that may be dead.
     * This includes tasks running more than twice as long as their frequency or over 2 hours.
     * @return DBmysqlIterator
     */
    public static function getZombieCronTasks(): DBmysqlIterator
    {
        global $DB;
        return $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'state'  => self::STATE_RUNNING,
                'OR'     => [
                    new QueryExpression(QueryFunction::unixTimestamp('lastrun') . ' + 2 * '
                        . $DB::quoteName('frequency') . ' < ' . QueryFunction::unixTimestamp()),
                    new QueryExpression(QueryFunction::unixTimestamp('lastrun') . ' + 2 * '
                        . HOUR_TIMESTAMP . ' < ' . QueryFunction::unixTimestamp()),
                ],
            ],
        ]);
    }

    /**
     * Check zombie crontask
     *
     * @param CronTask $task for log
     *
     * @return integer
     * @used-by self
     **/
    public static function cronWatcher($task)
    {
        // CronTasks running for more than 1 hour or 2 frequency
        $iterator = self::getZombieCrontasks();
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
        }

        return 1;
    }

    /**
     * get Cron description parameter for this class
     *
     * @param string $name name of the task
     *
     * @return array of string
     **/
    public static function cronInfo($name)
    {
        return match ($name) {
            'checkupdate' => [
                'description' => __('Check for new updates'),
            ],
            'logs'        => [
                'description' => __('Clean old logs'),
                'parameter'   => __('System logs retention period (in days, 0 for infinite)'),
            ],
            'session'     => [
                'description' => __('Clean expired sessions'),
            ],
            'graph'       => [
                'description' => __('Clean generated graphics'),
            ],
            'temp'        => [
                'description' => __('Clean temporary files'),
            ],
            'watcher'     => [
                'description' => __('Monitoring of automatic actions'),
            ],
            'circularlogs' => [
                'description' => __("Archives log files and deletes aging ones"),
                'parameter'   => __("Number of days to keep archived logs"),
            ],
            default       => []
        };
    }

    /**
     * Call cron without time check
     *
     * @return boolean : true if launched
     **/
    public static function callCronForce()
    {
        global $CFG_GLPI;

        if (self::mustRunWebTasks()) {
            $path = htmlescape($CFG_GLPI['root_doc'] . "/front/cron.php");
            echo "<div style=\"background-image: url('$path');\"></div>";
        }

        return true;
    }

    /**
     * Check if any web cron task exist and is enabled
     *
     * @return bool
     **/
    public static function mustRunWebTasks(): bool
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

    /**
     * @return void
     * @used-by templates/components/search/controls.html.twig
     */
    public static function showSearchStatusArea()
    {
        global $CFG_GLPI;

        $crontask = new self();
        $warnings = [];
        if ($crontask->getNeedToRun(self::MODE_INTERNAL)) {
            $warnings[] = __("You have at least one automatic action configured in GLPI mode, we advise you to switch to CLI mode.");
        }

        if (
            $CFG_GLPI['cron_limit'] < countElementsInTable(
                'glpi_crontasks',
                ['frequency' => MINUTE_TIMESTAMP]
            )
        ) {
            $warnings[] = __("You have more actions scheduled to run every minute than the number allowed to run at once. Increase this config.");
        }

        if (count($warnings) > 0) {
            $msg = __s('Automatic actions may not be running as expected');
            $params = [
                'status_message' => $msg,
                'extra_message' => '<ul>' . implode('', array_map(static fn($warning) => '<li>' . htmlescape($warning) . '</li>', $warnings)) . '</ul>',
            ];
            TemplateRenderer::getInstance()->display(
                'components/search/status_area.html.twig',
                $params
            );
        }
    }
}
