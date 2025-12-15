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
 * Alert class
 **/
class Alert extends CommonDBTM
{
    // ALERTS TYPE
    public const THRESHOLD   = 1;
    public const END         = 2;
    public const NOTICE      = 3;
    public const NOTCLOSED   = 4;
    public const ACTION      = 5;
    public const PERIODICITY = 6;

    public function prepareInputForAdd($input)
    {

        if (!isset($input['date']) || empty($input['date'])) {
            $input['date'] = $_SESSION['glpi_currenttime'];
        }
        return $input;
    }


    /**
     * Clear all alerts of an alert type for an item
     *
     * @param string  $itemtype   ID of the type to clear
     * @param string  $ID         ID of the item to clear
     * @param int $alert_type ID of the alert type to clear
     *
     * @return bool
     */
    public function clear(string $itemtype, string $ID, int $alert_type): bool
    {

        return $this->deleteByCriteria(['itemtype' => $itemtype, 'items_id' => $ID, 'type' => $alert_type], true);
    }


    /**
     * Clear all alerts  for an item
     *
     * @since 0.84
     *
     * @param string  $itemtype ID of the type to clear
     * @param int $ID       ID of the item to clear
     *
     * @return bool
     */
    public function cleanDBonItemDelete(string $itemtype, int $ID): bool
    {

        return $this->deleteByCriteria(['itemtype' => $itemtype, 'items_id' => $ID], true);
    }

    public static function dropdown($options = [])
    {

        $p = [
            'name'           => 'alert',
            'value'          => 0,
            'display'        => true,
            'inherit_parent' => false,
            'show_hours'     => false,
            'show_days'      => false,
        ];

        if (count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        $times = [];

        if ($p['inherit_parent']) {
            $times[Entity::CONFIG_PARENT] = __('Inheritance of the parent entity');
        }

        $times[Entity::CONFIG_NEVER]  = __('Never');
        if ($p['show_hours']) {
            $times[HOUR_TIMESTAMP] = __('Each hour');
            for ($i = 2; $i <= 24; $i++) {
                $times[$i * HOUR_TIMESTAMP] = sprintf(__('Every %1$s hours'), $i);
            }
        }
        $times[DAY_TIMESTAMP]         = __('Each day');
        if ($p['show_days']) {
            for ($i = 2; $i <= 6; $i++) {
                $times[$i * DAY_TIMESTAMP] = sprintf(__('Every %1$s days'), $i);
            }
        }
        $times[WEEK_TIMESTAMP]        = __('Each week');
        $times[MONTH_TIMESTAMP]       = __('Each month');

        return Dropdown::showFromArray($p['name'], $times, $p);
    }


    /**
     * Builds a Yes/No dropdown
     *
     * @param array $options Display options
     *
     * @return void|string (see $options['display'])
     */
    public static function dropdownYesNo(array $options = [])
    {

        $p = [
            'name'           => 'alert',
            'value'          => 0,
            'display'        => true,
            'inherit_parent' => false,
        ];

        if (count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        $times = [];

        if ($p['inherit_parent']) {
            $times[Entity::CONFIG_PARENT] = __('Inheritance of the parent entity');
        }

        $times[0] = __('No');
        $times[1] = __('Yes');

        return Dropdown::showFromArray($p['name'], $times, $p);
    }


    /**
     * ?
     *
     * @param string $name    Dropdown name
     * @param string $value   Dropdown selected value
     * @param array  $options Display options
     *
     * @return void|string (see $options['display'])
     */
    public static function dropdownIntegerNever(string $name, string $value, array $options = [])
    {

        $p = [
            'min'     => 1,
            'max'     => 100,
            'step'    => 1,
            'toadd'   => [],
            'display' => true,
        ];

        if (isset($options['inherit_parent']) && $options['inherit_parent']) {
            $p['toadd'][-2] = __('Inheritance of the parent entity');
        }

        $never_string = __('Never');
        if (isset($options['never_string']) && $options['never_string']) {
            $never_string = $options['never_string'];
        }
        if (isset($options['never_value']) && $options['never_value']) {
            $p['toadd'][$options['never_value']] = $never_string;
        } else {
            $p['toadd'][0] = $never_string;
        }
        $p['value'] = $value;

        foreach ($options as $key => $val) {
            $p[$key] = $val;
        }

        return Dropdown::showNumber($name, $p);
    }


    /**
     * Does alert exists
     *
     * @since 9.5.0 Made all params required. Dropped invalid defaults.
     * @param string  $itemtype The item type
     * @param int $items_id The item's ID
     * @param int $type     The type of alert (see constants in {@link \Alert} class)
     *
     * @return int|bool
     */
    public static function alertExists(string $itemtype, int $items_id, int $type): int|bool
    {
        global $DB;

        if ($items_id <= 0 || $type <= 0) {
            return false;
        }
        $iter = $DB->request(['FROM' => self::getTable(), 'WHERE' => ['itemtype' => $itemtype, 'items_id' => $items_id, 'type' => $type]]);
        if ($row = $iter->current()) {
            return $row['id'];
        }
        return false;
    }


    /**
     * Get date of alert
     *
     * @since 0.84
     * @since 9.5.0 Made all params required. Dropped invalid defaults.
     *
     * @param string  $itemtype The item type
     * @param int $items_id The item's ID
     * @param int $type     The type of alert (see constants in {@link \Alert} class)
     *
     * @return mixed|bool
     */
    public static function getAlertDate(string $itemtype, int $items_id, int $type): mixed|bool|null
    {
        global $DB;

        if ($items_id <= 0 || $type <= 0) {
            return false;
        }
        $iter = $DB->request(['FROM' => self::getTable(), 'WHERE' => ['itemtype' => $itemtype, 'items_id' => $items_id, 'type' => $type]]);
        if ($row = $iter->current()) {
            return $row['date'];
        }
        return false;
    }


    /**
     * Display last alert
     *
     * @param string  $itemtype The item type
     * @param int $items_id The item's ID
     *
     * @return void
     */
    public static function displayLastAlert(string $itemtype, int $items_id): void
    {
        global $DB;

        if ($items_id) {
            $iter = $DB->request([
                'FROM'     => self::getTable(),
                'FIELDS'   => 'date',
                'ORDER'    => 'date DESC',
                'LIMIT'    => 1,
                'itemtype' => $itemtype,
                'items_id' => $items_id,
            ]);
            if ($row = $iter->current()) {
                //TRANS: %s is the date
                echo htmlescape(sprintf(__('Alert sent on %s'), Html::convDateTime($row['date'])));
            }
        }
    }
}
