/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

export default function useScheduler() {

    function getListFullView(year_range = 10) {
        return {
            type: 'list',
            titleFormat: function () {
                return __('List');
            },
            visibleRange: (currentDate) => {
                const current_year = currentDate.getFullYear();
                return {
                    start: (new Date(currentDate.getTime())).setFullYear(current_year - year_range),
                    end: (new Date(currentDate.getTime())).setFullYear(current_year + year_range)
                };
            }
        };
    }

    function getResourceWeekView() {
        return {
            type: 'resourceTimeline',
            buttonText: 'Timeline Week',
            duration: { weeks: 1 },
            slotLabelFormat: [
                { week: 'short' },
                { weekday: 'short', day: 'numeric', month: 'numeric', omitCommas: true },
                (date) => {
                    return date.date.hour;
                }
            ]
        };
    }

    return {
        getListFullView,
        getResourceWeekView,
        defaultHeaderToolbar: {
            start: 'prev,next today',
            center: 'title',
            end: 'dayGridMonth,timeGridWeek,timeGridDay,listFull,resourceWeek',
        }
    };
}
