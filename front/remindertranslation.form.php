<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

/**
 * @since 9.5
 */

include ('../inc/includes.php');

$translation = new ReminderTranslation();

if (isset($_POST['add'])) {
   $translation->add($_POST);
   Html::back();

} else if (isset($_POST['update'])) {
   $translation->update($_POST);
   Html::back();

} else if (isset($_POST["purge"])) {
   $translation->delete($_POST, true);
   Html::redirect(Reminder::getFormURLWithID($_POST['reminders_id']));

} else if (isset($_GET["id"])) {
   $translation->check($_GET["id"], READ);
   Html::header(Reminder::getTypeName(1), $_SERVER['PHP_SELF'], "tools", "remindertranslation");
   $translation->display(['id' => $_GET['id']]);
   Html::footer();
}
