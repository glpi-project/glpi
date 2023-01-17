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

/**
 * Search engine from cron tasks
 */

include('../inc/includes.php');

Session::checkRight("config", UPDATE);

Html::header(CronTask::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], 'config', 'crontask');

$crontask = new CronTask();
if ($crontask->getNeedToRun(CronTask::MODE_INTERNAL)) {
    Html::displayTitle(
        "",
        __('Next run'),
        "<i class='fas fa-step-forward fa-lg me-2'></i>" . sprintf(__('Next task to run: %s'), $crontask->fields['name'])
    );
    echo "<div class='btn-group flex-wrap mb-3 ms-2'>";
    Html::showSimpleForm(
        $crontask->getFormURL(),
        ['execute' => $crontask->fields['name']],
        __('Execute')
    );
    echo "</div>";
} else {
    Html::displayTitle(
        "",
        __('No action pending'),
        "<i class='fas fa-check fa-lg me-2'></i>" . __('No action pending')
    );
}

if (
    $CFG_GLPI['cron_limit'] < countElementsInTable(
        'glpi_crontasks',
        ['frequency' => MINUTE_TIMESTAMP]
    )
) {
    Html::displayTitle(
        '',
        '',
        "<i class='fas fa-exclamation-triangle fa-lg me-2'></i>" .
        __('You have more automatic actions which need to run each minute than the number allow each run. Increase this config.')
    );
}

Search::show('CronTask');

Html::footer();
