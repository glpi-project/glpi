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

use Glpi\Exception\Http\BadRequestHttpException;

/**
 * Form to edit Cron Task
 */

Session::checkRight("config", READ);

$crontask = new CronTask();

if (isset($_POST['execute'])) {
    Session::checkRight("config", UPDATE);
    if (is_numeric($_POST['execute'])) {
        // Execute button from list.
        $name = CronTask::launch(CronTask::MODE_INTERNAL, intval($_POST['execute']));
    } else {
        // Execute button from Task form (force)
        $name = CronTask::launch(-CronTask::MODE_INTERNAL, 1, $_POST['execute']);
    }
    if ($name) {
        //TRANS: %s is a task name
        Session::addMessageAfterRedirect(htmlescape(sprintf(__('Task %s executed'), $name)));
    }
    Html::back();
} elseif (isset($_POST["update"])) {
    Session::checkRight('config', UPDATE);
    $crontask->update($_POST);
    Html::back();
} elseif (
    isset($_POST['resetdate'])
           && isset($_POST["id"])
) {
    Session::checkRight('config', UPDATE);
    if ($crontask->getFromDB($_POST["id"])) {
        $crontask->resetDate();
    }
    Html::back();
} elseif (
    isset($_POST['resetstate'])
           && isset($_POST["id"])
) {
    Session::checkRight('config', UPDATE);
    if ($crontask->getFromDB($_POST["id"])) {
        $crontask->resetState();
    }
    Html::back();
} else {
    if (!isset($_GET["id"]) || empty($_GET["id"])) {
        throw new BadRequestHttpException();
    }
    $menus = ['config', 'crontask'];
    CronTask::displayFullPageForItem($_GET['id'], $menus);
}
