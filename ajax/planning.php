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

use function Safe\json_decode;
use function Safe\json_encode;

Session::checkCentralAccess();

if (!isset($_REQUEST["action"])) {
    return;
}

$extevent = new PlanningExternalEvent();

if ($_REQUEST["action"] == "get_events") {
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode(Planning::constructEventsArray($_REQUEST));
    return;
}

if (($_POST["action"] ?? null) == "update_event_times") {
    echo Planning::updateEventTimes($_POST);
    return;
}

if (($_POST["action"] ?? null) == "view_changed") {
    Planning::viewChanged($_POST['view']);
    return;
}

if (($_POST["action"] ?? null) == "clone_event") {
    $extevent->check(-1, CREATE);
    echo Planning::cloneEvent($_POST['event']);
    return;
}

if (($_POST["action"] ?? null) == "delete_event") {
    $extevent->check(-1, DELETE);
    echo Planning::deleteEvent($_POST['event']);
    return;
}

if ($_REQUEST["action"] == "get_externalevent_template") {
    $key = 'planningexternaleventtemplates_id';
    if (
        isset($_POST[$key])
        && $_POST[$key] > 0
    ) {
        $template = new PlanningExternalEventTemplate();
        $template->getFromDB($_POST[$key]);

        $template->fields['rrule'] = json_decode($template->fields['rrule'], true);
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode($template->fields, JSON_NUMERIC_CHECK);
        return;
    }
}

Html::header_nocache();
header("Content-Type: text/html; charset=UTF-8");

if ($_REQUEST["action"] == "add_event_fromselect") {
    Planning::showAddEventForm($_REQUEST);
}

if ($_REQUEST["action"] == "add_event_sub_form") {
    Planning::showAddEventSubForm($_REQUEST);
}

if ($_REQUEST["action"] == "add_planning_form") {
    Planning::showAddPlanningForm();
}

if ($_REQUEST["action"] == "add_user_form") {
    Planning::showAddUserForm();
}

if ($_REQUEST["action"] == "add_group_users_form") {
    Planning::showAddGroupUsersForm();
}

if ($_REQUEST["action"] == "add_group_form") {
    Planning::showAddGroupForm();
}

if ($_REQUEST["action"] == "add_external_form") {
    Planning::showAddExternalForm();
}

if ($_REQUEST["action"] == "add_event_classic_form") {
    Planning::showAddEventClassicForm($_REQUEST);
}

if ($_REQUEST["action"] == "edit_event_form") {
    Planning::editEventForm($_REQUEST);
}

if ($_REQUEST["action"] == "get_filters_form") {
    Planning::showPlanningFilter();
}

if (($_POST["action"] ?? null) == "toggle_filter") {
    Planning::toggleFilter($_POST);
}

if (($_POST["action"] ?? null) == "color_filter") {
    Planning::colorFilter($_POST);
}

if (($_POST["action"] ?? null) == "delete_filter") {
    Planning::deleteFilter($_POST);
}
