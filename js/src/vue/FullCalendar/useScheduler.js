/*!
 * GLPI - Gestionnaire Libre de Parc Informatique
 * SPDX-License-Identifier: GPL-3.0-or-later
 * SPDX-FileCopyrightText: 2015-2026 Teclib' and contributors.
 */

export default function useScheduler() {

    function getListFullView(year_range = 10) {
        return {
            type: 'list',
            titleFormat: function () {
                return __('List');
            },
            visibleRange: function (currentDate) {
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
            groupByDateAndResource: true, //FIXME Option doesn't exist anymore. Not sure the replacement.
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
