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

use function Safe\strtotime;

// Class PlanningRecall
// @since 0.84
class PlanningRecall extends CommonDBChild
{
    // From CommonDBChild
    public static $itemtype        = 'itemtype';
    public static $items_id        = 'items_id';

    public static function getTypeName($nb = 0)
    {
        return _n('Planning reminder', 'Planning reminders', $nb);
    }

    public static function canCreate(): bool
    {
        return true;
    }

    public function canCreateItem(): bool
    {
        return (int) $this->fields['users_id'] === Session::getLoginUserID();
    }

    public function cleanDBonPurge()
    {
        $class = new Alert();
        $class->cleanDBonItemDelete(static::class, $this->fields['id']);
    }

    public static function isAvailable()
    {
        global $CFG_GLPI;

        // Cache in session
        if (isset($_SESSION['glpiplanningreminder_isavailable'])) {
            return $_SESSION['glpiplanningreminder_isavailable'];
        }

        $_SESSION['glpiplanningreminder_isavailable'] = 0;
        if ($CFG_GLPI["use_notifications"]) {
            $task = new CronTask();
            if ($task->getFromDBbyName('PlanningRecall', 'planningrecall')) {
                // Only disabled by config
                if ($task->isDisabled() !== 1) {
                    if (
                        Session::haveRightsOr(
                            "planning",
                            [Planning::READMY, Planning::READGROUP, Planning::READALL]
                        )
                    ) {
                        $_SESSION['glpiplanningreminder_isavailable'] = 1;
                    }
                }
            }
        }

        return $_SESSION['glpiplanningreminder_isavailable'];
    }

    /**
     * Retrieve an item from the database
     *
     * @param string $itemtype itemtype to get
     * @param integer $items_id id of the item
     * @param integer $users_id id of the user
     *
     * @return boolean true if succeed else false
     **/
    public function getFromDBForItemAndUser($itemtype, $items_id, $users_id)
    {
        return $this->getFromDBByCrit([
            static::getTable() . '.itemtype'  => $itemtype,
            static::getTable() . '.items_id'  => $items_id,
            static::getTable() . '.users_id'  => $users_id,
        ]);
    }

    public function post_updateItem($history = true)
    {
        $alert = new Alert();
        $alert->clear(static::class, $this->fields['id'], Alert::ACTION);

        parent::post_updateItem($history);
    }

    /**
     * Manage recall set
     *
     * @param array $data array of data to manage
     **/
    public static function manageDatas(array $data)
    {
        // Check data information
        if (
            !isset($data['itemtype'])
            || !isset($data['items_id'])
            || !isset($data['users_id'])
            || !isset($data['before_time'])
            || !isset($data['field'])
        ) {
            return false;
        }

        $pr = new self();
        // Data OK : check if recall already exists
        if (
            $pr->getFromDBForItemAndUser(
                $data['itemtype'],
                $data['items_id'],
                $data['users_id']
            )
        ) {
            if ($data['before_time'] !== $pr->fields['before_time']) {
                // Recall exists and is different : update datas and clean alert
                if ($item = getItemForItemtype($data['itemtype'])) {
                    if (
                        $item->getFromDB($data['items_id'])
                        && isset($item->fields[$data['field']])
                        && !empty($item->fields[$data['field']])
                    ) {
                        $when = date(
                            "Y-m-d H:i:s",
                            strtotime($item->fields[$data['field']]) - $data['before_time']
                        );
                        if ($data['before_time'] >= 0) {
                            if ($pr->can($pr->fields['id'], UPDATE)) {
                                $pr->update(['id'          => $pr->fields['id'],
                                    'before_time' => $data['before_time'],
                                    'when'        => $when,
                                ]);
                            }
                        } else {
                            if ($pr->can($pr->fields['id'], PURGE)) {
                                $pr->delete(['id' => $pr->fields['id']]);
                            }
                        }
                    }
                }
            }
        } else {
            // Recall does not exists : create it
            if ($pr->can(-1, CREATE, $data)) {
                if ($item = getItemForItemtype($data['itemtype'])) {
                    $item->getFromDB($data['items_id']);
                    if (
                        $item->getFromDB($data['items_id'])
                        && !empty($item->fields[$data['field']])
                    ) {
                        $data['when'] = date(
                            "Y-m-d H:i:s",
                            strtotime($item->fields[$data['field']])
                            - $data['before_time']
                        );
                        if ($data['before_time'] >= 0) {
                            $pr->add($data);
                        }
                    }
                }
            }
        }
    }

    /**
     * Update planning recal date when changing begin of planning
     *
     * @param string $itemtype itemtype to get
     * @param integer $items_id id of the item
     * @param string $begin new begin date
     *
     * @return boolean true if succeed else false
     **/
    public static function managePlanningUpdates($itemtype, $items_id, $begin)
    {
        global $DB;

        if (isset($_SESSION['glpiplanningreminder_isavailable'])) {
            unset($_SESSION['glpiplanningreminder_isavailable']);
        }

        return $DB->update(
            'glpi_planningrecalls',
            [
                'when'   => QueryFunction::dateSub(
                    date: new QueryExpression($DB::quoteValue($begin)),
                    interval: new QueryExpression($DB::quoteName('before_time')),
                    interval_unit: 'SECOND'
                ),
            ],
            [
                'itemtype'  => $itemtype,
                'items_id'  => $items_id,
            ]
        );
    }

    /**
     * Get the planning recall for an item if it exists
     * @param class-string<CommonDBTM> $itemtype The itemtype
     * @param int $items_id The item id
     * @param int $users_id The user id. If 0 (default), the current user is used.
     * @return PlanningRecall|null
     */
    public static function getForItem(string $itemtype, int $items_id, int $users_id = 0): ?PlanningRecall
    {
        $pr = new self();
        if ($users_id === 0) {
            $users_id = Session::getLoginUserID();
        }
        if ($pr->getFromDBForItemAndUser($itemtype, $items_id, $users_id)) {
            return $pr;
        }
        return null;
    }

    /**
     * Make a select box with recall times
     *
     * Mandatory options : itemtype, items_id
     *
     * @param array $options array of possible options:
     *    - itemtype : string itemtype
     *    - items_id : integer id of the item
     *    - users_id : integer id of the user (if not set used login user)
     *    - value    : integer preselected value for before_time
     *    - field    : string  field used as time mark (default begin)
     *
     * @return void|false print out an HTML select box or return false if mandatory fields are not ok
     **/
    public static function dropdown($options = [])
    {
        // Default values
        $p['itemtype'] = '';
        $p['items_id'] = 0;
        $p['users_id'] = Session::getLoginUserID();
        $p['value']    = Entity::CONFIG_NEVER;
        $p['field']    = 'begin';
        $p['rand']     = mt_rand();

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }
        if (!(getItemForItemtype($p['itemtype']))) {
            return false;
        }

        $pr = new self();
        // Get recall for item and user
        if ($pr->getFromDBForItemAndUser($p['itemtype'], $p['items_id'], $p['users_id'])) {
            $p['value'] = $pr->fields['before_time'];
        }

        $possible_values                       = [];
        $possible_values[Entity::CONFIG_NEVER] = __('None');

        $min_values = [0, 15, 30, 45];
        foreach ($min_values as $val) {
            $possible_values[$val * MINUTE_TIMESTAMP] = sprintf(
                _n('%d minute', '%d minutes', $val),
                $val
            );
        }

        $h_values = [1, 2, 3, 4, 12];
        foreach ($h_values as $val) {
            $possible_values[$val * HOUR_TIMESTAMP] = sprintf(_n('%d hour', '%d hours', $val), $val);
        }
        $d_values = [1, 2];
        foreach ($d_values as $val) {
            $possible_values[$val * DAY_TIMESTAMP] = sprintf(_n('%d day', '%d days', $val), $val);
        }
        $w_values = [1];
        foreach ($w_values as $val) {
            $possible_values[$val * 7 * DAY_TIMESTAMP] = sprintf(_n('%d week', '%d weeks', $val), $val);
        }

        ksort($possible_values);

        Dropdown::showFromArray('_planningrecall[before_time]', $possible_values, [
            'value' => $p['value'],
            'rand'  => $p['rand'],
        ]);
        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            <input type="hidden" name="_planningrecall[itemtype]" value="{{ itemtype }}">
            <input type="hidden" name="_planningrecall[items_id]" value="{{ items_id }}">
            <input type="hidden" name="_planningrecall[users_id]" value="{{ users_id }}">
            <input type="hidden" name="_planningrecall[field]" value="{{ field }}">
TWIG, $p);
    }

    /**
     * Give cron information
     *
     * @param $name : task's name
     *
     * @return array of information
     * @used-by CronTask
     **/
    public static function cronInfo($name)
    {
        switch ($name) {
            case 'planningrecall':
                return ['description' => __('Send planning recalls')];
        }
        return [];
    }

    /**
     * Cron action on contracts : alert depending of the config : on notice and expire
     *
     * @param CronTask $task for log, if NULL display (default NULL)
     * @used-by CronTask
     **/
    public static function cronPlanningRecall($task = null)
    {
        global $CFG_GLPI, $DB;

        if (!$CFG_GLPI["use_notifications"]) {
            return 0;
        }

        $cron_status = 0;
        $iterator = $DB->request([
            'SELECT'    => 'glpi_planningrecalls.*',
            'FROM'      => 'glpi_planningrecalls',
            'LEFT JOIN' => [
                'glpi_alerts'  => [
                    'ON' => [
                        'glpi_planningrecalls'  => 'id',
                        'glpi_alerts'           => 'items_id', [
                            'AND' => [
                                'glpi_alerts.itemtype'  => 'PlanningRecall',
                                'glpi_alerts.type'      => Alert::ACTION,
                            ],
                        ],
                    ],
                ],
            ],
            'WHERE'     => [
                'NOT'                         => ['glpi_planningrecalls.when' => null],
                'glpi_planningrecalls.when'   => ['<', QueryFunction::now()],
                'glpi_alerts.date'            => null,
            ],
        ]);

        $pr = new self();
        foreach ($iterator as $data) {
            if ($pr->getFromDB($data['id']) && $pr->getItem()) {
                $options = [];

                //retrieve entities id from parent linked item
                //planningrecall -> TicketTask ->  Ticket which have entity notion
                //               -> ChangeTask ->  Change which have entity notion
                //               -> ProblemTask -> Problem which have entity notion
                $itemToNotify = $pr->getItem();
                if ($itemToNotify instanceof CommonITILTask) {
                    /** @var CommonITILObject $linkedItem */
                    $linkedItem = $itemToNotify->getItem();
                    // No recall, if the parent item is in a closed status
                    if (in_array($linkedItem->fields['status'], array_merge($linkedItem->getSolvedStatusArray(), $linkedItem->getClosedStatusArray()))) {
                        $pr->delete($data);
                        continue;
                    }
                    if ($linkedItem && $linkedItem->isEntityAssign()) {
                        $options['entities_id'] = $linkedItem->getEntityID();
                    }
                }

                if (NotificationEvent::raiseEvent('planningrecall', $pr, $options, $itemToNotify)) {
                    $cron_status         = 1;
                    $task->addVolume(1);
                    $alert               = new Alert();
                    $input["itemtype"]   = self::class;
                    $input["type"]       = Alert::ACTION;
                    $input["items_id"]   = $data['id'];

                    $alert->add($input);
                }
            } else {
                // Clean item
                $pr->delete($data);
            }
        }
        return $cron_status;
    }
}
