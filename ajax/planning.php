<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

include ('../inc/includes.php');

Session::checkCentralAccess();

if (!isset($_REQUEST["action"])) {
   exit;
}

if ($_REQUEST["action"] == "get_events") {
   header("Content-Type: application/json; charset=UTF-8");
   echo json_encode(Planning::constructEventsArray($_REQUEST));
   exit;
}

if ($_REQUEST["action"] == "update_event_times") {
   echo Planning::updateEventTimes($_REQUEST);
   exit;
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

if ($_REQUEST["action"] == "add_event_classic_form") {
   Planning::showAddEventClassicForm($_REQUEST);
}

if ($_REQUEST["action"] == "edit_event_form") {
   Planning::editEventForm($_REQUEST);
}

if ($_REQUEST["action"] == "get_filters_form") {
   Planning::showPlanningFilter();
}

if ($_REQUEST["action"] == "toggle_filter") {
   Planning::toggleFilter($_REQUEST);
}

if ($_REQUEST["action"] == "color_filter") {
   Planning::colorFilter($_REQUEST);
}

if ($_REQUEST["action"] == "delete_filter") {
   Planning::deleteFilter($_REQUEST);
}

Html::ajaxFooter();

