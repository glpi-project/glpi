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

use function Safe\json_encode;

Html::header_nocache();

if (isset($_GET['get_raw']) && filter_var(($_GET['display_container'] ?? true), FILTER_VALIDATE_BOOLEAN)) {
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode($_SESSION['MESSAGE_AFTER_REDIRECT'] ?? []);
    $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
} else {
    // Send UTF8 Headers
    header("Content-Type: text/html; charset=UTF-8");
    Html::displayMessageAfterRedirect(filter_var(($_GET['display_container'] ?? true), FILTER_VALIDATE_BOOLEAN));
}
