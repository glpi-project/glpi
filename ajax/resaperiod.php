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
 * @since 0.84
 */

// Send UTF8 Headers
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

if (isset($_POST['type'], $_POST['end'])) {
    echo "<table style='width: 90%'>";
    switch ($_POST['type']) {
        case 'day':
            echo "<tr><td>" . __s('End date') . '</td><td>';
            Html::showDateField('periodicity[end]', ['value' => $_POST['end']]);
            echo "</td></tr>";
            break;

        case 'week':
            echo "<tr><td>" . __s('End date') . '</td><td>';
            Html::showDateField('periodicity[end]', ['value' => $_POST['end']]);
            echo "</td></tr></table>";
            echo "<table class='tab_glpi'>";
            echo "<tr class='center'><td>&nbsp;</td>";
            $days = [
                'Monday'    => __('Monday'),
                'Tuesday'   => __('Tuesday'),
                'Wednesday' => __('Wednesday'),
                'Thursday'  => __('Thursday'),
                'Friday'    => __('Friday'),
                'Saturday'  => __('Saturday'),
                'Sunday'    => __('Sunday'),
            ];
            foreach ($days as $day) {
                echo "<th>" . htmlescape($day) . "</th>";
            }
            echo "</tr><tr class='center'><td>" . __s('By day') . '</td>';

            foreach (array_keys($days) as $day_key) {
                echo "<td><input type='checkbox' name='periodicity[days][" . htmlescape($day_key) . "]'></td>";
            }
            echo "</tr>";
            break;

        case 'month':
            echo "<tr><td colspan='2'>";
            $values = [
                'date' => __('Each month, same date'),
                'day'  => __('Each month, same day of week'),
            ];
            Dropdown::showFromArray('periodicity[subtype]', $values);
            echo "</td></tr>";
            echo "<tr><td>" . __s('End date') . '</td><td>';
            Html::showDateField('periodicity[end]', ['value' => $_POST['end']]);
            echo "</td></tr>";
    }
    echo '</table>';
}
