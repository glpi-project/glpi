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

include('../inc/includes.php');

/**
 * @var \DBmysql $DB
 * @var array $_UPOST
 */
global $DB, $_UPOST;

$dropdown = new Entity();

// Root entity : no delete
if (isset($_GET['id']) && ($_GET['id'] == 0)) {
    $options = ['canedit' => true,
        'candel'  => false,
    ];
}

if (array_key_exists('custom_css_code', $_POST)) {
    // Prevent sanitize process to alter '<', '>' and '&' chars.
    $_POST['custom_css_code'] = $DB->escape($_UPOST['custom_css_code']);
}

include(GLPI_ROOT . "/front/dropdown.common.form.php");
