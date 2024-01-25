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

class Calendar_Holiday extends CommonDBRelation
{
    public $auto_message_on_action = false;

   // From CommonDBRelation
    public static $itemtype_1 = 'Calendar';
    public static $items_id_1 = 'calendars_id';
    public static $itemtype_2 = 'Holiday';
    public static $items_id_2 = 'holidays_id';

    public static $checkItem_2_Rights = self::DONT_CHECK_ITEM_RIGHTS;


    /**
     * @since 0.84
     **/
    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }


    /**
     * Show holidays for a calendar
     *
     * @param $calendar Calendar object
     *
     * @return void|boolean (HTML display) False if there is a rights error.
     */
    public static function showForCalendar(Calendar $calendar)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $ID = $calendar->getField('id');
        if (!$calendar->can($ID, READ)) {
            return false;
        }

        $canedit = $calendar->can($ID, UPDATE);

        $rand    = mt_rand();

        $iterator = $DB->request([
            'SELECT' => [
                'glpi_calendars_holidays.id AS linkid',
                'glpi_holidays.*'
            ],
            'DISTINCT'        => true,
            'FROM'            => 'glpi_calendars_holidays',
            'LEFT JOIN'       => [
                'glpi_holidays'   => [
                    'ON' => [
                        'glpi_calendars_holidays'  => 'holidays_id',
                        'glpi_holidays'            => 'id'
                    ]
                ]
            ],
            'WHERE'           => [
                'glpi_calendars_holidays.calendars_id' => $ID
            ],
            'ORDERBY'         => 'glpi_holidays.name'
        ]);

        $numrows = count($iterator);
        $holidays = [];
        $used     = [];
        foreach ($iterator as $data) {
            $holidays[$data['id']] = $data;
            $used[$data['id']]     = $data['id'];
        }

        if ($canedit) {
            echo "<div class='firstbloc'>";
            echo "<form name='calendarsegment_form$rand' id='calendarsegment_form$rand' method='post'
                action='";
            echo Toolbox::getItemTypeFormURL(__CLASS__) . "'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_1'><th colspan='7'>" . __('Add a close time') . "</tr>";
            echo "<tr class='tab_bg_2'><td class='right'  colspan='4'>";
            echo "<input type='hidden' name='calendars_id' value='$ID'>";
            Holiday::dropdown(['used'   => $used,
                'entity' => $calendar->fields["entities_id"]
            ]);
            echo "</td><td class='center'>";
            echo "<input type='submit' name='add' value=\"" . _sx('button', 'Add') . "\" class='btn btn-primary'>";
            echo "</td></tr>";
            echo "</table>";
            Html::closeForm();
            echo "</div>";
        }

        echo "<div class='spaced'>";

        if ($canedit && $numrows) {
            Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
            $massiveactionparams = ['num_displayed' => min($_SESSION['glpilist_limit'], $numrows),
                'container'     => 'mass' . __CLASS__ . $rand
            ];
            Html::showMassiveActions($massiveactionparams);
        }
        echo "<table class='tab_cadre_fixehov'>";
        echo "<tr>";
        if ($canedit && $numrows) {
            echo "<th width='10'>";
            echo Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            echo "</th>";
        }
        echo "<th>" . __('Name') . "</th>";
        echo "<th>" . __('Start') . "</th>";
        echo "<th>" . __('End') . "</th>";
        echo "<th>" . __('Recurrent') . "</th>";
        echo "</tr>";

        $used = [];

        if ($numrows) {
            Session::initNavigateListItems(
                'Holiday',
                //TRANS : %1$s is the itemtype name, %2$s is the name of the item (used for headings of a list)
                                        sprintf(
                                            __('%1$s = %2$s'),
                                            Calendar::getTypeName(1),
                                            $calendar->fields["name"]
                                        )
            );

            foreach ($holidays as $data) {
                Session::addToNavigateListItems('Holiday', $data["id"]);
                echo "<tr class='tab_bg_1'>";
                if ($canedit) {
                    echo "<td>";
                    Html::showMassiveActionCheckBox(__CLASS__, $data["linkid"]);
                    echo "</td>";
                }
                echo "<td><a href='" . Toolbox::getItemTypeFormURL('Holiday') . "?id=" . $data['id'] . "'>" .
                       $data["name"] . "</a></td>";
                echo "<td>" . Html::convDate($data["begin_date"]) . "</td>";
                echo "<td>" . Html::convDate($data["end_date"]) . "</td>";
                echo "<td>" . Dropdown::getYesNo($data["is_perpetual"]) . "</td>";
                echo "</tr>";
            }
        }
        echo "</table>";

        if ($canedit && $numrows) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
        }
        echo "</div>";
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate) {
            $nb = 0;
            if ($item instanceof Calendar) {
                if ($_SESSION['glpishow_count_on_tabs']) {
                    $nb = countElementsInTable($this->getTable(), ['calendars_id' => $item->getID()]);
                }
                return self::createTabEntry(
                    _n('Close time', 'Close times', Session::getPluralNumber()),
                    $nb
                );
            }
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item->getType() == 'Calendar') {
            self::showForCalendar($item);
        }
        return true;
    }

    public function post_addItem()
    {

        $this->invalidateCalendarCache($this->fields['calendars_id']);

        parent::post_addItem();
    }

    public function post_updateItem($history = true)
    {

        if (in_array('calendars_id', $this->updates)) {
            $this->invalidateCalendarCache($this->oldvalues['calendars_id']);
        }

        $this->invalidateCalendarCache($this->fields['calendars_id']);

        parent::post_updateItem($history);
    }

    public function post_deleteFromDB()
    {

        $this->invalidateCalendarCache($this->fields['calendars_id']);

        parent::post_deleteFromDB();
    }

    /**
     * Return holidays related to given calendar.
     *
     * @param int $calendars_id
     *
     * @return array
     */
    public function getHolidaysForCalendar(int $calendars_id): array
    {
        /**
         * @var \DBmysql $DB
         * @var \Psr\SimpleCache\CacheInterface $GLPI_CACHE
         */
        global $DB, $GLPI_CACHE;

        $cache_key = $this->getCalendarHolidaysCacheKey($calendars_id);
        if (($holidays = $GLPI_CACHE->get($cache_key)) === null) {
            $holidays_iterator = $DB->request(
                [
                    'SELECT'     => ['begin_date', 'end_date', 'is_perpetual'],
                    'FROM'       => Holiday::getTable(),
                    'INNER JOIN' => [
                        Calendar_Holiday::getTable() => [
                            'FKEY'   => [
                                Calendar_Holiday::getTable() => Holiday::getForeignKeyField(),
                                Holiday::getTable()          => 'id',
                            ],
                        ],
                    ],
                    'WHERE'      => [
                        Calendar_Holiday::getTableField(Calendar::getForeignKeyField()) => $calendars_id,
                    ],
                ]
            );
            $holidays = iterator_to_array($holidays_iterator);
            $GLPI_CACHE->set($cache_key, $holidays);
        }

        return $holidays;
    }

    /**
     * Invalidate cache for given holiday.
     *
     * @param int $holidays_id
     *
     * @return bool
     */
    public function invalidateHolidayCache(int $holidays_id): bool
    {
        /** @var \DBmysql $DB */
        global $DB;

        $success = true;

        $iterator = $DB->request(
            [
                'SELECT'     => [Calendar::getForeignKeyField()],
                'FROM'       => self::getTable(),
                'WHERE'      => [
                    Holiday::getForeignKeyField() => $holidays_id,
                ],
            ]
        );
        foreach ($iterator as $link) {
            $success = $success && $this->invalidateCalendarCache($link[Calendar::getForeignKeyField()]);
        }

        return $success;
    }

    /**
     * Get cache key of cache entry containing holidays of given calendar.
     *
     * @param int $calendars_id
     *
     * @return string
     */
    private function getCalendarHolidaysCacheKey(int $calendars_id): string
    {
        return sprintf('calendar-%s-holidays', $calendars_id);
    }

    /**
     * Invalidate holidays cache of given calendar.
     *
     * @param int $calendars_id
     *
     * @return bool
     */
    private function invalidateCalendarCache(int $calendars_id): bool
    {
        /** @var \Psr\SimpleCache\CacheInterface $GLPI_CACHE */
        global $GLPI_CACHE;
        return $GLPI_CACHE->delete($this->getCalendarHolidaysCacheKey($calendars_id));
    }
}
