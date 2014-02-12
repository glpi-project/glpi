<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

include ('../inc/includes.php');

Session::checkRight("config", "w");
$notificationmail = new NotificationMailSetting();

if (!empty($_POST["test_smtp_send"])) {
   NotificationMail::testNotification();
   Html::back();

} else if (!empty($_POST["update"])) {
   $config = new Config();
   $config->update($_POST);
   Html::back();
}

Html::header(Notification::getTypeName(2), $_SERVER['PHP_SELF'], "config", "mailing", "config");

$notificationmail->showForm(1);

Html::footer();
?>