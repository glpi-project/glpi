<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

if (strpos($_SERVER['PHP_SELF'], "dropdownRubDocument.php")) {
    $AJAX_INCLUDE = 1;
    include('../inc/includes.php');
    header("Content-Type: text/html; charset=UTF-8");
    Html::header_nocache();
}

Session::checkCentralAccess();

/** @global DBmysql $DB */

// Make a select box
if (isset($_POST["rubdoc"])) {
    $used = [];

   // Clean used array
    if (isset($_POST['used']) && is_array($_POST['used']) && (count($_POST['used']) > 0)) {
        $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => 'glpi_documents',
            'WHERE'  => [
                'id'                    => $_POST['used'],
                'documentcategories_id' => (int)$_POST['rubdoc']
            ]
        ]);

        foreach ($iterator as $data) {
            $used[$data['id']] = $data['id'];
        }
    }

    if (preg_match('/[^a-z_\-0-9]/i', $_POST['myname'])) {
        throw new \RuntimeException('Invalid name provided!');
    }

    if (!isset($_POST['entity']) || $_POST['entity'] === '') {
        $_POST['entity'] = $_SESSION['glpiactive_entity'];
    }

    Dropdown::show(
        'Document',
        [
            'name'      => $_POST['myname'],
            'used'      => $used,
            'width'     => '50%',
            'entity'    => intval($_POST['entity']),
            'rand'      => intval($_POST['rand']),
            'condition' => ['glpi_documents.documentcategories_id' => (int)$_POST["rubdoc"]]
        ]
    );
}
