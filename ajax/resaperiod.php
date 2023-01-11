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

/**
 * @since 0.84
 */

$AJAX_INCLUDE = 1;
include('../inc/includes.php');

// Send UTF8 Headers
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

if (isset($_POST['type']) && isset($_POST['end'])) {
    echo "<table style='width: 90%'>";
    switch ($_POST['type']) {
        case 'day':
            echo "<tr><td>" . __('End date') . '</td><td>';
            Html::showDateField('periodicity[end]', ['value' => $_POST['end']]);
            echo "</td></tr>";
            break;

        case 'week':
            echo "<tr><td>" . __('End date') . '</td><td>';
            Html::showDateField('periodicity[end]', ['value' => $_POST['end']]);
            echo "</td></tr></table>";
            echo "<table class='tab_glpi'>";
            echo "<tr class='center'><td>&nbsp;</td>";
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            foreach ($days as $day) {
                echo "<th>" . __($day) . "</th>";
            }
            echo "</tr><tr class='center'><td>" . __('By day') . '</td>';

            foreach ($days as $day) {
                echo "<td><input type='checkbox' name='periodicity[days][$day]'></td>";
            }
            echo "</tr>";
            break;

        case 'month':
            echo "<tr><td colspan='2'>";
            $values = ['date' => __('Each month, same date'),
                'day'  => __('Each month, same day of week')
            ];
            Dropdown::showFromArray('periodicity[subtype]', $values);
            echo "</td></tr>";
            echo "<tr><td>" . __('End date') . '</td><td>';
            Html::showDateField('periodicity[end]', ['value' => $_POST['end']]);
            echo "</td></tr>";
    }
    echo '</table>';
}
