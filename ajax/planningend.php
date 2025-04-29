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

$AJAX_INCLUDE = 1;
include('../inc/includes.php');

// Send UTF8 Headers
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

if (
    isset($_POST['duration']) && ($_POST['duration'] == 0)
    && isset($_POST['name'])
) {
    if (!isset($_POST['global_begin'])) {
        $_POST['global_begin'] = '';
    }
    if (!isset($_POST['global_end'])) {
        $_POST['global_end'] = '';
    }
    Html::showDateTimeField($_POST['name'], ['value'      =>  $_POST['end'],
        'timestep'   => -1,
        'maybeempty' => false,
        'canedit'    => true,
        'mindate'    => '',
        'maxdate'    => '',
        'mintime'    => $_POST['global_begin'],
        'maxtime'    => $_POST['global_end'],
    ]);
}
