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

use Glpi\Exception\Http\NotFoundHttpException;
use Glpi\System\Log\LogParser;
use Glpi\System\Log\LogViewer;

global $CFG_GLPI;

Session::checkRight("logs", READ);

$filepath = $_REQUEST['filepath'] ?? null;

if ($filepath === null) {
    Html::redirect($CFG_GLPI["root_doc"] . "/front/logs.php");
}

$logparser = new LogParser();
if ($logparser->getFullPath($filepath) === null) {
    throw new NotFoundHttpException('Not found');
}

if (($_GET['action'] ?? '') === 'download_log_file') {
    $logparser = new LogParser();
    $logparser->download($filepath);
} elseif (($_POST['action'] ?? '') === 'empty') {
    Session::checkRight('config', UPDATE);
    $logparser->empty($filepath);
    Html::back();
} elseif (($_POST['action'] ?? '') === 'delete') {
    Session::checkRight('config', UPDATE);
    $logparser->delete($filepath);
    Html::redirect($CFG_GLPI["root_doc"] . "/front/logs.php");
} else {
    Html::header(
        LogViewer::getTypeName(Session::getPluralNumber()),
        '',
        'admin',
        'glpi\system\log\logviewer',
        'logfile'
    );

    $logviewer = new LogViewer();
    $logviewer->showLogFile($filepath);

    Html::footer();
}
