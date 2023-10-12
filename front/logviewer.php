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

use Glpi\Http\Response;
use Glpi\System\Log\LogParser;
use Glpi\System\Log\LogViewer;

include('../inc/includes.php');

Session::checkRight("logs", READ);

$filepath = $_REQUEST['filepath'] ?? null;

if ($filepath === null) {
    Html::redirect($CFG_GLPI["root_doc"] . "/front/logs.php");
}

if (!file_exists(GLPI_LOG_DIR . '/' . $filepath) || is_dir(GLPI_LOG_DIR . '/' . $filepath)) {
    Response::sendError(404, 'Not found', Response::CONTENT_TYPE_TEXT_HTML);
}

if (($_GET['action'] ?? '') === 'download') {
    $logparser = new LogParser();
    $logparser->download($filepath);
} elseif (($_POST['action'] ?? '') === 'empty') {
    Session::checkRight('config', UPDATE);
    $logparser = new LogParser();
    $logparser->empty($filepath);
    Html::back();
} elseif (($_POST['action'] ?? '') === 'delete') {
    Session::checkRight('config', UPDATE);
    $logparser = new LogParser();
    $logparser->delete($filepath);
    Html::redirect($CFG_GLPI["root_doc"] . "/front/logs.php");
} else {
    Html::header(
        LogViewer::getTypeName(Session::getPluralNumber()),
        $_SERVER['PHP_SELF'],
        'admin',
        'glpi\system\log\logviewer',
        'logfile'
    );

    $logviewer = new LogViewer();
    $logviewer->showLogFile($filepath);

    Html::footer();
}
