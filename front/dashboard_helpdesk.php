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

/**
 * Filename was previously states.php
 * @since 0.84
 */

include('../inc/includes.php');


Session::checkCentralAccess();
$default = Glpi\Dashboard\Grid::getDefaultDashboardForMenu('helpdesk');

// Redirect to "/front/ticket.php" if no dashboard found
if ($default == "") {
    Html::redirect($CFG_GLPI["root_doc"] . "/front/ticket.php");
}

Html::header(__('Helpdesk Dashboard'), $_SERVER['PHP_SELF'], "helpdesk", "dashboard");

$dashboard = new Glpi\Dashboard\Grid($default);
$dashboard->showDefault();

Html::footer();
