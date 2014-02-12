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

if (strpos($_SERVER['PHP_SELF'],"dropdownTicketCategories.php")) {
   include ('../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}
if (!defined('GLPI_ROOT')) {
   die("Can not acces directly to this file");
}

$opt = array('entity' => $_POST["entity_restrict"]);
if ($_POST['type'] == $_POST['currenttype']) {
   $opt['value'] = $_POST['value'];
}

if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
   $opt['condition'] = "`is_helpdeskvisible`='1' AND ";
} else {
   $opt['condition'] = '';
}
if ($_POST["type"]) {
   switch ($_POST['type']) {
      case Ticket::INCIDENT_TYPE :
         $opt['condition'].= " `is_incident`='1'";
         break;

      case Ticket::DEMAND_TYPE:
         $opt['condition'].= " `is_request`='1'";
         break;
   }
}

ItilCategory::dropdown($opt);
?>