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

/** @var \DBmysql $DB */
global $DB;

if (strpos($_SERVER['PHP_SELF'], "dropdownTypeCertificates.php")) {
    $AJAX_INCLUDE = 1;
    include('../inc/includes.php');
    header("Content-Type: text/html; charset=UTF-8");
    Html::header_nocache();
}


Session::checkCentralAccess();

// Make a select box
$used = [];

// Clean used array
if (
    isset($_POST['used'])
    && is_array($_POST['used'])
      && (count($_POST['used']) > 0)
) {
    foreach (
        $DB->request(
            'glpi_certificates',
            ['id'                  => $_POST['used'],
                'certificatetypes_id' => $_POST['certificatetype'],
            ]
        ) as $data
    ) {
        $used[$data['id']] = $data['id'];
    }
}

Dropdown::show(
    'Certificate',
    ['name'      => $_POST['name'],
        'used'      => $used,
        'width'     => '50%',
        'entity'    => $_POST['entity'],
        'rand'      => $_POST['rand'],
    ]
);
