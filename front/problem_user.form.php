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

if (!defined('GLPI_ROOT')) {
   include ('../inc/includes.php');
}

$problem_user = new Problem_User();

Session ::checkLoginUser();

if (isset($_POST["update"])) {
   $problem_user->check($_POST["id"], 'w');

   $problem_user->update($_POST);
   echo "<script type='text/javascript' >\n";
   echo "window.opener.location.reload();";
   echo "window.close()";
   echo "</script>";

} else if (isset($_GET["id"])) {
   $problem_user->showUserNotificationForm($_GET["id"]);
} else {
   Html::displayErrorAndDie('Lost');
}
?>