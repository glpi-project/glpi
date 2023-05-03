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

namespace Glpi\Dashboard\Filters;

use Html;
use DBmysql;
use Ticket;
use Change;
use Problem;

class DatesFilter extends AbstractFilter
{
    /**
     * Get the filter name
     *
     * @return string
     */
    public static function getName(): string
    {
        return __("Creation date");
    }

    /**
     * Get the filter id
     *
     * @return string
     */
    public static function getId() : string
    {
        return "dates";
    }

    /**
     * Get the filter criteria
     * 
     * @return array
     */
    public static function getCriteria(DBmysql $DB, string $table = "", array $apply_filters = []) : array
    {
        $criteria = [];

        if (
            ($DB->fieldExists($table, 'date'))
            && isset($apply_filters[self::getId()])
            && count($apply_filters[self::getId()]) == 2
        ) {
            $criteria[] = self::getDatesCriteria("$table.date", $apply_filters[self::getId()]);
        }

       //exclude itilobject already processed with 'date'
        if (
            (!in_array($table, [
                Ticket::getTable(),
                Change::getTable(),
                Problem::getTable(),
            ]) && $DB->fieldExists($table, 'date_creation'))
            && isset($apply_filters[self::getId()])
            && count($apply_filters[self::getId()]) == 2
        ) {
            $criteria[] = self::getDatesCriteria("$table.date_creation", $apply_filters[self::getId()]);
        }

        return $criteria;
    }

    private static function getDatesCriteria(string $field = "", array $dates = []): array
    {
        $begin = strtotime($dates[0]);
        $end   = strtotime($dates[1]);

        return [
            [$field => ['>=', date('Y-m-d', $begin)]],
            [$field => ['<=', date('Y-m-d', $end)]],
        ];
    }

     /**
     * Get the search filter criteria
     *
     * @return array
     */
    public static function getSearchCriteria(DBmysql $DB, string $table = "", array $apply_filters = []) : array
    {
        $criteria = [
            "WHERE" => [],
            "JOIN"  => [],
        ];

        if (
            $DB->fieldExists($table, 'date')
            && isset($apply_filters[self::getId()])
            && count($apply_filters[self::getId()]) == 2
        ) {
            $criteria["WHERE"] += self::getDatesSearchCriteria(self::getSearchOptionID($table, "date", $table), $apply_filters[DatesFilter::getId()], 'begin');
            $criteria["WHERE"] += self::getDatesSearchCriteria(self::getSearchOptionID($table, "date", $table), $apply_filters[DatesFilter::getId()], 'end');
        }

        //exclude itilobject already processed with 'date'
        if (
            !in_array($table, [
                Ticket::getTable(),
                Change::getTable(),
                Problem::getTable(),
            ]) && $DB->fieldExists($table, 'date_creation')
            && isset($apply_filters[self::getId()])
            && count($apply_filters[self::getId()]) == 2
        ) {
            $criteria["WHERE"] += self::getDatesSearchCriteria(self::getSearchOptionID($table, "date_creation", $table), $apply_filters[DatesFilter::getId()], 'begin');
            $criteria["WHERE"] += self::getDatesSearchCriteria(self::getSearchOptionID($table, "date_creation", $table), $apply_filters[DatesFilter::getId()], 'end');
        }

        return $criteria;
    }

    private static function getDatesSearchCriteria(int $searchoption_id, array $dates = [], $when = 'begin'): array
    {

        if ($when == "begin") {
            $begin = strtotime($dates[0]);
            return [
                'link'       => 'AND',
                'field'      => $searchoption_id, // creation date
                'searchtype' => 'morethan',
                'value'      => date('Y-m-d 00:00:00', $begin)
            ];
        } else {
            $end   = strtotime($dates[1]);
            return [
                'link'       => 'AND',
                'field'      => $searchoption_id, // creation date
                'searchtype' => 'lessthan',
                'value'      => date('Y-m-d 00:00:00', $end)
            ];
        }
    }

    /**
     * Get HTML for a dates range filter
     *
     * @param string|array $values init the input with these values, will be a string if empty values
     * @param string $fieldname how is named the current date field
     *                         (used to specify creation date or last update)
     *
     * @return string
     */
    public static function getHtml($values = "", string $fieldname = 'dates'): string
    {
        // string mean empty value
        if (is_string($values)) {
            $values = [];
        }

        $rand  = mt_rand();
        $label = self::getName();
        $field = Html::showDateField('filter-dates', [
            'value'        => $values,
            'rand'         => $rand,
            'range'        => true,
            'display'      => false,
            'calendar_btn' => false,
            'placeholder'  => $label,
            'on_change'    => "on_change_{$rand}(selectedDates, dateStr, instance)",
        ]);

        $js = <<<JAVASCRIPT
        var on_change_{$rand} = function(selectedDates, dateStr, instance) {
        // we are waiting for empty value or a range of dates,
        // don't trigger when only the first date is selected
        var nb_dates = selectedDates.length;
        if (nb_dates == 0 || nb_dates == 2) {
            Dashboard.getActiveDashboard().saveFilter('{$fieldname}', selectedDates);
            $(instance.input).closest("fieldset").addClass("filled");
        }
        };
        JAVASCRIPT;
        $field .= Html::scriptBlock($js);

        return self::field($fieldname, $field, $label, is_array($values) && count($values) > 0);
    }
}
