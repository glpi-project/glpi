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

use Glpi\Application\ErrorHandler;
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

    const OP_LESS     = 0;
    const OP_LESSEQ   = 1;
    const OP_EQ       = 2;
    const OP_NOT      = 3;
    const OP_GREATEQ  = 4;
    const OP_GREAT    = 5;

    public static function getTypeName($nb = 0)
    {
        return _n('Saved search alert', 'Saved searches alerts', $nb);
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

       // can exists for template
        if (
            ($item->getType() == 'SavedSearch')
            && SavedSearch::canView()
        ) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = countElementsInTable(
                    $this->getTable(),
                    ['savedsearches_id' => $item->getID()]
                );
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        self::showForSavedSearch($item, $withtemplate);
        return true;
    }


    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab('Log', $ong, $options);

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
     * @return true if displayed  false if item not found or not right to display
     **/
    public function showForm($ID, array $options = [])
    {

       /*if (!Session::haveRight("savedsearch", UPDATE)) {
         return false;
       }*/

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
        } catch (\RuntimeException $e) {
            ErrorHandler::getInstance()->handleException($e);
        }

        $this->showFormHeader($options);

        if ($this->isNewID($ID)) {
            echo Html::hidden('savedsearches_id', ['value' => $options['savedsearches_id']]);
        }

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . SavedSearch::getTypeName(1) . "</td>";
        echo "<td>";
        echo $search->getLink();
        if ($count !== null) {
            echo "<span class='primary-bg primary-fg count float-none'>$count</span></a>";
        }
        echo "</td>";
        echo "<td>" . __('Name') . "</td>";
        echo "<td>";
        echo Html::input('name', ['value' => $this->fields['name']]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Operator');
        echo Html::showToolTip(__('Compare number of results the search returns against the specified value with selected operator'));
        echo "</td>";
        echo "<td>";
        Dropdown::showFromArray(
            'operator',
            $this->getOperators(),
            ['value' => $this->getField('operator')]
        );
        echo "</td><td>" . __('Value') . "</td>";
        echo "<td>";
        echo "<input type='number' min='0' name='value' value='" . $this->getField('value') . "' required='required'/>";
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Active') . "</td>";
        echo "<td>";
        Dropdown::showYesNo('is_active', $this->getField('is_active'));
        echo "</td><td>" . __('Notification frequency') . "</td>";
        echo "<td>";
        $alert = new Alert();
        $alert->getFromDBByCrit([
            'items_id'  => $this->fields['savedsearches_id'],
            'itemtype' => SavedSearch::getType(),
        ]);
        Dropdown::showFrequency('frequency', $this->fields["frequency"]);
        echo "</td></tr>";
        $this->showFormButtons($options);

        return true;
    }


    /**
     * Print the searches alerts
     *
     * @param SavedSearch $search       Object instance
     * @param boolean     $withtemplate Template or basic item (default '')
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
            return false;
        }
        $canedit = $search->canEdit($ID);

        echo "<div class='center'>";

        echo "<div class='firstbloc'>";

        $iterator = $DB->request([
            'FROM'   => Notification::getTable(),
            'WHERE'  => [
                'itemtype'  => self::getType(),
                'event'     => 'alert' . ($search->getField('is_private') ? '' : '_' . $search->getID())
            ]
        ]);

        if (!$iterator->numRows()) {
            echo "<span class='required'><strong>" . __('Notification does not exists!') . "</strong></span>";
            if ($canedit) {
                echo "<br/><a href='{$search->getFormURLWithID($search->fields['id'])}&amp;create_notif=true'>"
                 . __('create it now') . "</a>";
                $canedit = false;
            }
        } else {
            echo _n('Notification used:', 'Notifications used:', $iterator->numRows()) . "&nbsp;";
            $first = true;
            foreach ($iterator as $row) {
                if (!$first) {
                    echo ', ';
                }
                if (Session::haveRight('notification', UPDATE)) {
                    $url = Notification::getFormURLWithID($row['id']);
                    echo "<a href='$url'>" . $row['name'] . "</a>";
                } else {
                    echo $row['name'];
                }
                $first = false;
            }
        }
        echo '</div>';

        if (
            $canedit
            && !(!empty($withtemplate) && ($withtemplate == 2))
        ) {
            echo "<div class='firstbloc'>" .
               "<a class='btn btn-primary' href='" . self::getFormURL() . "?savedsearches_id=$ID&amp;withtemplate=" .
                  $withtemplate . "'>";
            echo __('Add an alert');
            echo "</a></div>\n";
        }

        $iterator = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => ['savedsearches_id' => $ID]
        ]);

        echo "<table class='tab_cadre_fixehov'>";

        $colspan = 4;
        if ($iterator->numrows()) {
            echo "<tr class='noHover'><th colspan='$colspan'>" . self::getTypeName($iterator->numrows()) .
            "</th></tr>";

            $header = "<tr><th>" . __('Name') . "</th>";
            $header .= "<th>" . __('Operator') . "</th>";
            $header .= "<th>" . __('Value') . "</th>";
            $header .= "<th>" . __('Active') . "</th>";
            $header .= "</tr>";
            echo $header;

            $alert = new self();
            foreach ($iterator as $data) {
                $alert->getFromDB($data['id']);
                echo "<tr class='tab_bg_2'>";
                echo "<td>" . $alert->getLink() . "</td>";
                echo "<td>" . self::getOperators($data['operator']) . "</td>";
                echo "<td>" . $data['value'] . "</td>";
                echo "<td>" . Dropdown::getYesNo($data['is_active']) . "</td>";
                echo "</tr>";
                Session::addToNavigateListItems(__CLASS__, $data['id']);
            }
            echo $header;
        } else {
            echo "<tr class='tab_bg_2'><th colspan='$colspan'>" . __('No item found') . "</th></tr>";
        }

        echo "</table>";
        echo "</div>";
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
            self::OP_GREAT    => '>'
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
                'glpi_savedsearches_alerts.*'
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
                    ]
                ]
            ],
            'WHERE'     => [
                'glpi_savedsearches_alerts.is_active' => true,
                'OR' => [
                    ['glpi_alerts.date' => null],
                    ['glpi_alerts.date' => ['<', new QueryExpression(sprintf(
                        'CURRENT_TIMESTAMP() - INTERVAL %s second',
                        $DB->quoteName('glpi_savedsearches_alerts.frequency')
                    ))
                    ]
                    ],
                ]
            ]
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
                        $count = (int)$data['data']['totalcount'];
                    } else {
                        $data = [];
                    }
                    $value = (int)$row['value'];

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
                            throw new \RuntimeException("Unknown operator '{$row['operator']}'");
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
                          ], 1);
                          $alert->add([
                              'type'     => Alert::PERIODICITY,
                              'itemtype' => SavedSearch_Alert::class,
                              'items_id' => $row['id'],
                          ]);
                    }
                } catch (\Throwable $e) {
                    self::restoreContext($context);
                    ErrorHandler::getInstance()->handleException($e);
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
