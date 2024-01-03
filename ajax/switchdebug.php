<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

if (Config::canUpdate()) {
    $mode = ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? Session::NORMAL_MODE : Session::DEBUG_MODE);
    $user = new User();
    $user->update(
        [
            'id'        => Session::getLoginUserID(),
            'use_mode'  => $mode
        ]
    );
    Session::addMessageAfterRedirect(
        $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ?
         __('Debug mode has been enabled!') :
         __('Debug mode has been disabled!')
    );
}

Html::back();
