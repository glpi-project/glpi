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

// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer
/** @file
* @brief
*/

include ('../inc/includes.php');

Session::checkRight("logs", "r");

Html::header(Event::getTypeName(2), $_SERVER['PHP_SELF'], "admin", "log");

// Show last events
if (isset($_GET["order"])) {
   if (!isset($_GET["start"])) {
      $_GET["start"] = 0;
   }
   Event::showList($_SERVER['PHP_SELF'], $_GET["order"], $_GET["sort"], $_GET["start"]);
} else {
   Event::showList($_SERVER['PHP_SELF']);
}

Html::footer();
?>
