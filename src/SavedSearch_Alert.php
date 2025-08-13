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
use Glpi\Error\ErrorHandler;
use Glpi\Plugin\Hooks;

/**
 * Saved search alerts
 **/
class SavedSearch_Alert extends CommonDBChild
{
    // From CommonDBChild
    public static $itemtype = 'SavedSearch';
    public static $items_id = 'savedsearches_id';
    public $dohistory       = true;
    protected $displaylist  = false;

    public const OP_LESS     = 0;
    public const OP_LESSEQ   = 1;
    public const OP_EQ       = 2;
    public const OP_NOT      = 3;
    public const OP_GREATEQ  = 4;
    public const OP_GREAT    = 5;

    public static function getTypeName($nb = 0)
    {
        return _n('Saved search alert', 'Saved searches alerts', $nb);
    }

    public static function getIcon()
    {
        return 'ti ti-bell';
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        // can exists for template
        if (
            ($item instanceof SavedSearch)
            && SavedSearch::canView()
        ) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = countElementsInTable(
                    $this->getTable(),
                    ['savedsearches_id' => $item->getID()]
                );
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::getType());
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$item instanceof SavedSearch) {
            return false;
        }

        self::showForSavedSearch($item, $withtemplate);
        return true;
    }


    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(Log::class, $ong, $options);

        return $ong;
    }

    /**
     * Print the form
     *
     * @param integer $ID      integer ID of the item
     * @param array   $options array
     *     - target for the Form
     *     - computers_id ID of the computer for add process
     *
     * @return boolean true if displayed  false if item not found or not right to display
     **/
    public function showForm($ID, array $options = [])
    {
        $search = new SavedSearch();
        if ($ID > 0) {
            $this->check($ID, READ);
            $search->getFromDB($this->fields['savedsearches_id']);
        } else {
            $this->check(-1, CREATE, $options);
            $search->getFromDB($options['savedsearches_id']);
        }

        $count = null;
        try {
            if ($data = $search->execute()) {
                $count = $data['data']['totalcount'];
            }
        } catch (RuntimeException $e) {
            ErrorHandler::logCaughtException($e);
            ErrorHandler::displayCaughtExceptionMessage($e);
        }

        TemplateRenderer::getInstance()->display('pages/tools/savedsearch/alert.html.twig', [
            'item' => $this,
            'params' => $options,
            'search_link' => $search->getLink(),
            'count' => $count,
            'operators' => self::getOperators(),
        ]);

        return true;
    }

    /**
     * Print the searches alerts
     *
     * @param SavedSearch $search       Object instance
     * @param integer     $withtemplate Template or basic item (default '')
     *
     * @return void
     **/
    public static function showForSavedSearch(SavedSearch $search, $withtemplate = 0)
    {
        global $DB;

        $ID = $search->getID();

        if (
            !$search->getFromDB($ID)
            || !$search->can($ID, READ)
        ) {
            return;
        }
        $start       = (int) ($_GET["start"] ?? 0);
        $sort        = $_GET["sort"] ?? "";
        $order       = strtoupper($_GET["order"] ?? "");
        if (strlen($sort) == 0) {
            $sort = "name";
        }
        if (strlen($order) == 0) {
            $order = "ASC";
        }

        $notifications = $DB->request([
            'FROM'   => Notification::getTable(),
            'WHERE'  => [
                'itemtype'  => self::getType(),
                'event'     => 'alert' . ($search->getField('is_private') ? '' : '_' . $search->getID()),
            ],
        ]);

        $total_count = countElementsInTable(self::getTable(), ['savedsearches_id' => $ID]);
        $iterator = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => ['savedsearches_id' => $ID],
            'ORDER'  => ["$sort $order"],
            'START'  => $start,
            'LIMIT'  => $_SESSION['glpilist_limit'],
        ]);

        $alert = new self();
        $entries = [];
        foreach ($iterator as $data) {
            $alert->getFromDB($data['id']);
            $entries[] = [
                'name' => $alert->getLink(),
                'operator' => self::getOperators($data['operator']),
                'value' => $data['value'],
                'is_active' => Dropdown::getYesNo($data['is_active']),
            ];
        }

        TemplateRenderer::getInstance()->display('pages/tools/savedsearch/alert_list_notification.html.twig', [
            'notifications' => $notifications,
            'search' => $search,
            'params' => [
                'canedit' => $search->canEdit($ID),
                'withtemplate' => $withtemplate,
            ],
        ]);

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'start' => $start,
            'sort' => $sort,
            'order' => $order,
            'is_tab' => true,
            'filters' => [],
            'nofilter' => true,
            'columns' => [
                'name' => __('Name'),
                'operator' => __('Operator'),
                'value' => __('Value'),
                'is_active' => __('Active'),
            ],
            'formatters' => [
                'name' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => $total_count,
            'filtered_number' => $total_count,
            'showmassiveactions' => false,
        ]);
    }

    /**
     * Get operators
     *
     * @param integer $id ID for the operator to retrieve, or null for the full list
     *
     * @return string|array
     */
    public static function getOperators($id = null)
    {
        $ops = [
            self::OP_LESS     => '<',
            self::OP_LESSEQ   => '<=',
            self::OP_EQ       => '=',
            self::OP_NOT      => '!=',
            self::OP_GREATEQ  => '>=',
            self::OP_GREAT    => '>',
        ];
        return ($id === null ? $ops : $ops[$id]);
    }

    public static function cronInfo($name)
    {
        switch ($name) {
            case 'send':
                return ['description' => __('Saved searches alerts')];
        }
        return [];
    }

    /**
     * Summary of saveContext
     *
     * Save $_SESSION and $CFG_GLPI into the returned array
     *
     * @return array[] which contains a copy of $_SESSION and $CFG_GLPI
     */
    private static function saveContext()
    {
        global $CFG_GLPI;
        $context = [];
        $context['$_SESSION'] = $_SESSION;
        $context['$CFG_GLPI'] = $CFG_GLPI;
        return $context;
    }

    /**
     * Summary of restoreContext
     *
     * restore former $_SESSION and $CFG_GLPI
     * to be sure that logs will be in GLPI default datetime and language
     * and that session is restored for the next crontaskaction
     *
     * @param mixed $context is the array returned by saveContext
     */
    private static function restoreContext($context)
    {
        global $CFG_GLPI;
        $_SESSION = $context['$_SESSION'];
        $CFG_GLPI = $context['$CFG_GLPI'];
        Session::loadLanguage();
        Plugin::doHook(Hooks::INIT_SESSION);
    }

    /**
     * Send saved searches alerts
     *
     * @param CronTask $task CronTask instance
     *
     * @return int : <0 : need to run again, 0:nothing to do, >0:ok
     */
    public static function cronSavedSearchesAlerts($task)
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => [
                'glpi_savedsearches_alerts.*',
            ],
            'FROM'   => self::getTable(),
            'LEFT JOIN' => [
                'glpi_alerts' => [
                    'FKEY'   => [
                        'glpi_alerts'                => 'items_id',
                        'glpi_savedsearches_alerts'  => 'id',
                        [
                            'AND' => [
                                'glpi_alerts.itemtype' => SavedSearch_Alert::class,
                                'glpi_alerts.type'     => Alert::PERIODICITY,
                            ],
                        ],
                    ],
                ],
            ],
            'WHERE'     => [
                'glpi_savedsearches_alerts.is_active' => true,
                'OR' => [
                    ['glpi_alerts.date' => null],
                    [
                        'glpi_alerts.date' => ['<',
                            QueryFunction::dateSub(
                                date: QueryFunction::now(),
                                interval: new QueryExpression($DB::quoteName('glpi_savedsearches_alerts.frequency')),
                                interval_unit: 'SECOND'
                            ),
                        ],
                    ],
                ],
            ],
        ]);

        if ($iterator->numrows()) {
            $savedsearch = new SavedSearch();

            if (!isset($_SESSION['glpiname'])) {
                //required from search class
                $_SESSION['glpiname'] = 'crontab';
            }

            // Will save $_SESSION and $CFG_GLPI cron context into an array
            $context = self::saveContext();

            foreach ($iterator as $row) {
                //execute saved search to get results
                try {
                    $savedsearch->getFromDB($row['savedsearches_id']);
                    if (isCommandLine()) {
                        //search requires a logged in user...
                        $user = new User();
                        $user->getFromDB($savedsearch->fields['users_id']);
                        $auth = new Auth();
                        $auth->user = $user;
                        $auth->auth_succeded = true;
                        Session::init($auth);
                    }

                    $count = null;
                    if ($data = $savedsearch->execute(true)) {
                        $count = (int) $data['data']['totalcount'];
                    } else {
                        $data = [];
                    }
                    $value = (int) $row['value'];

                    $notify = false;
                    $tr_op = null;

                    switch ($row['operator']) {
                        case self::OP_LESS:
                            $notify = $count < $value;
                            $tr_op = __('less than');
                            break;
                        case self::OP_LESSEQ:
                            $notify = $count <= $value;
                            $tr_op = __('less or equals than');
                            break;
                        case self::OP_EQ:
                            $notify = $count == $value;
                            $tr_op = __('equals to');
                            break;
                        case self::OP_NOT:
                            $notify = $count != $value;
                            $tr_op = __('not equals to');
                            break;
                        case self::OP_GREATEQ:
                            $notify = $count >= $value;
                            $tr_op = __('greater or equals than');
                            break;
                        case self::OP_GREAT:
                            $notify = $count > $value;
                            $tr_op = __('greater than');
                            break;
                        default:
                            throw new RuntimeException("Unknown operator '{$row['operator']}'");
                    }

                    //TRANS : %1$s is the name of the saved search,
                    //        %2$s is the comparison translated text
                    //        %3$s is the value compared to
                    $data['msg'] = sprintf(
                        __('Results count for %1$s is %2$s %3$s'),
                        $savedsearch->getName(),
                        $tr_op,
                        $value
                    );

                    // Will restore previously saved $_SESSION and $CFG_GLPI:
                    //  To be sure that logs will be in GLPI with default datetime and language
                    //  and that notifications are sent even if $_SESSION['glpinotification_to_myself'] is false
                    //  and to restore default cron $_SESSION and $CFG_GLPI global variables for next cron task
                    self::restoreContext($context);

                    if ($notify) {
                        $event = 'alert' . ($savedsearch->getField('is_private') ? '' : '_' . $savedsearch->getID());
                        $savedsearch_alert = new self();
                        $savedsearch_alert->getFromDB($row['id']);
                        $data['savedsearch'] = $savedsearch;
                        NotificationEvent::raiseEvent($event, $savedsearch_alert, $data);
                        $task->addVolume(1);

                        $alert = new Alert();
                        $alert->deleteByCriteria([
                            'itemtype' => SavedSearch_Alert::class,
                            'items_id' => $row['id'],
                        ], true);
                        $alert->add([
                            'type'     => Alert::PERIODICITY,
                            'itemtype' => SavedSearch_Alert::class,
                            'items_id' => $row['id'],
                        ]);
                    }
                } catch (Throwable $e) {
                    self::restoreContext($context);
                    ErrorHandler::logCaughtException($e);
                    ErrorHandler::displayCaughtExceptionMessage($e);
                }
            }
            return 1;
        }
        return 0;
    }

    public function getItemsForLog($itemtype, $items_id)
    {
        return ['new' => $this];
    }
}
