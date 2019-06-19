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

use Glpi\Event;

include ('../inc/includes.php');

$host = new SIEMHost();

if (isset($_POST['add'])) {
   $host->check(-1, CREATE, $_POST);

   $newID = $host->add($_POST, false);
   Event::logItemAction('add', 'siemhost', $newID, 'tools', $_SESSION['glpiname']);
   Html::back();

} else if (isset($_POST['purge'])) {
   $host->check($_POST['id'], PURGE);
   $host->delete($_POST, 1);
   Event::logItemAction('purge', 'siemhost', $newID, 'tools', $_SESSION['glpiname']);
   Html::back();

} else if (isset($_POST['update'])) {
   $host->check($_POST['id'], UPDATE);

   $host->update($_POST);
   Event::logItemAction('update', 'siemhost', $newID, 'tools', $_SESSION['glpiname']);
   Html::back();

} else {
   Html::header(SIEMHost::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], 'tools', 'siemevent');
   $host->display(['id'           => $_GET['id']]);
   Html::footer();
}