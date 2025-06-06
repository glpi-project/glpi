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

require_once(__DIR__ . '/_check_webserver_config.php');

use Glpi\Exception\Http\AccessDeniedHttpException;

Html::header(__('Transfer'), '', 'admin', 'rule', 'Transfer');

$transfer = new Transfer();

$transfer->checkGlobal(READ);

if (isset($_POST['transfer'])) {
    if (isset($_SESSION['glpitransfer_list'])) {
        if (!Session::haveAccessToEntity($_POST['to_entity'])) {
            throw new AccessDeniedHttpException();
        }
        $transfer->moveItems($_SESSION['glpitransfer_list'], $_POST['to_entity'], $_POST);
        unset($_SESSION['glpitransfer_list']);
        echo "<div class='fw-bold text-center'>" . __s('Operation successful') . "<br>";
        echo "<a href='central.php' role='button' class='btn btn-primary'>" . __s('Back') . "</a></div>";
        Html::footer();
        return;
    }
} elseif (isset($_POST['clear'])) {
    unset($_SESSION['glpitransfer_list']);
    echo "<div class='fw-bold text-center'>" . __s('Operation successful') . "<br>";
    echo "<a href='central.php' role='button' class='btn btn-primary'>" . __s('Back') . "</a></div>";
    echo "</div>";
    Html::footer();
    return;
}

unset($_SESSION['glpimassiveactionselected']);

$transfer->showTransferList();

Html::footer();
