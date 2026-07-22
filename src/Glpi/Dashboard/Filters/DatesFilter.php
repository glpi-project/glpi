<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

use Change;
use Html;
use Problem;
use Ticket;

class DatesFilter extends AbstractFilter
{
    public static function getName(): string
    {
        return __("Creation date");
    }

    public static function getId(): string
    {
        return "dates";
    }

    public static function canBeApplied(string $table): bool
    {
        global $DB;

        return $DB->fieldExists($table, 'date')
            || (
                $DB->fieldExists($table, 'date_creation')
                // exclude itilobject already processed with 'date'
                && !in_array($table, [Ticket::getTable(), Change::getTable(), Problem::getTable()])
            );
    }

    public static function getCriteria(string $table, $value): array
    {
        global $DB;

        if (!is_array($value) || count($value) !== 2) {
            // Empty filter value
            return [];
        }

        $criteria = [
            'WHERE' => [],
        ];

        if ($DB->fieldExists($table, 'date')) {
            $criteria['WHERE'][] = self::getDatesCriteria("$table.date", $value);
        }

        if (
            $DB->fieldExists($table, 'date_creation')
            // exclude itilobject already processed with 'date'
            && !in_array($table, [Ticket::getTable(), Change::getTable(), Problem::getTable()])
        ) {
            $criteria['WHERE'][] = self::getDatesCriteria("$table.date_creation", $value);
        }

        return $criteria;
    }

    public static function getSearchCriteria(string $table, $value): array
    {
        global $DB;

        if (!is_array($value) || count($value) !== 2) {
            // Empty filter value
            return [];
        }

        $criteria = [];

        if ($DB->fieldExists($table, 'date')) {
            $date_option_id = self::getSearchOptionID($table, "date", $table);
            $criteria[] = self::getDatesSearchCriteria($date_option_id, $value, 'begin');
            $criteria[] = self::getDatesSearchCriteria($date_option_id, $value, 'end');
        }

        if (
            $DB->fieldExists($table, 'date_creation')
            // exclude itilobject already processed with 'date'
            && !in_array($table, [Ticket::getTable(), Change::getTable(), Problem::getTable()])
        ) {
            $date_creation_option_id = self::getSearchOptionID($table, "date_creation", $table);
            $criteria[] = self::getDatesSearchCriteria($date_creation_option_id, $value, 'begin');
            $criteria[] = self::getDatesSearchCriteria($date_creation_option_id, $value, 'end');
        }

        return $criteria;
    }

    public static function getHtml($value): string
    {
        $values = is_array($value)
            ? $value
            : [] // can be a string if values are not initialized yet
        ;

        $rand  = mt_rand();
        $label = self::getName();

        $presets_json = json_encode([
            'P1D' => __('Last day'),
            'P1W' => sprintf(__('Last %s days'), 7),
            'P1M' => sprintf(__('Last %s days'), 30),
            'P3M' => __('Last quarter'),
            'P1Y' => __('Last year'),
        ]);

        // Initial key used to detect no-op onChange calls on page load with existing filter.
        $initial_dates_key = count($values) === 2
            ? json_encode($values[0] . ',' . $values[1])
            : 'null';

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
            // Format a Date as a local YYYY-MM-DD string (avoids UTC conversion from toISOString()
            // which would shift the date for UTC+ timezones and cause wrong DB comparisons).
            var toLocalDateStr_{$rand} = function(d) {
                return d.getFullYear()
                    + '-' + String(d.getMonth() + 1).padStart(2, '0')
                    + '-' + String(d.getDate()).padStart(2, '0');
            };

            // Tracks the last saved value as a 'start,end' key.
            // flatpickr fires onChange once when the user picks dates, then again via
            // onClose → setDate(..., true). We skip the second call if the value is unchanged.
            var last_saved_dates_{$rand} = {$initial_dates_key};

            var on_change_{$rand} = function(selectedDates, dateStr, instance) {
                var nb_dates = selectedDates.length;

                // nb_dates == 1 while calendar is still open = first click of range selection,
                // not yet a complete range — skip. When the calendar closes with only one date
                // selected (instance.isOpen == false at that point), treat it as a single-day filter.
                if (nb_dates == 1 && instance.isOpen) return;

                var dates_str;
                if (nb_dates == 0) {
                    dates_str = [];
                } else if (nb_dates == 1) {
                    // Single-day selection: use the same date for start and end.
                    var d = toLocalDateStr_{$rand}(selectedDates[0]);
                    dates_str = [d, d];
                } else {
                    dates_str = selectedDates.map(toLocalDateStr_{$rand});
                }

                var key = dates_str.join(',');
                if (key === last_saved_dates_{$rand}) return;
                last_saved_dates_{$rand} = key;

                GLPI.Dashboard.getActiveDashboard().saveFilter('dates', dates_str);
                $(instance.input).closest("fieldset").addClass("filled");
            };

            $(function() {
                var fp_elem = document.getElementById('showdate{$rand}');
                if (!fp_elem || !fp_elem._flatpickr) return;
                var fp = fp_elem._flatpickr;
                var presets = {$presets_json};
                var presets_added = false;

                fp.config.onOpen.push(function() {
                    if (presets_added) return;
                    presets_added = true;

                    var container = document.createElement('div');
                    container.className = 'd-flex flex-wrap gap-1 p-2';
                    container.style.borderTop = '1px solid var(--tblr-border-color)';

                    Object.entries(presets).forEach(function([interval, preset_label]) {
                        var btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'btn btn-sm btn-outline-secondary';
                        btn.textContent = preset_label;
                        btn.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();

                            var end = new Date();
                            var start = new Date();
                            switch (interval) {
                                case 'P1D': start.setDate(start.getDate() - 1); break;
                                case 'P1W': start.setDate(start.getDate() - 7); break;
                                case 'P1M': start.setDate(start.getDate() - 30); break;
                                case 'P3M': start.setMonth(start.getMonth() - 3); break;
                                case 'P1Y': start.setFullYear(start.getFullYear() - 1); break;
                            }

                            var start_str = toLocalDateStr_{$rand}(start);
                            var end_str   = toLocalDateStr_{$rand}(end);

                            // Update last_saved so the onClose → onChange chain is deduped.
                            last_saved_dates_{$rand} = start_str + ',' + end_str;
                            GLPI.Dashboard.getActiveDashboard().saveFilter('dates', [start_str, end_str]);
                            fp.setDate([start, end], false);
                            fp.close();
                            $(fp_elem).closest("fieldset").addClass("filled");
                        });
                        container.appendChild(btn);
                    });

                    fp.calendarContainer.appendChild(container);
                });
            });
JAVASCRIPT;
        $field .= Html::scriptBlock($js);

        return self::field('dates', $field, $label, count($values) > 0);
    }
}
