<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

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

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

Session ::checkRight('tickettemplate','w');

$item = new TicketTemplateHiddenField();

if (isset($_POST["add"])) {
   $item->check(-1, 'w', $_POST);

   if ($item->add($_POST)) {
      Event::log($_POST["tickettemplates_id"], "tickettemplate", 4, "maintain",
                 $_SESSION["glpiname"]." ".$LANG['log'][51]);
   }
   Html::back();

} else if (isset($_POST["delete"])) {

   if (isset($_POST["item"]) && count($_POST["item"])) {
      foreach ($_POST["item"] as $key => $val) {
         if ($val == 1) {
            if ($item->can($key, 'w')) {
               $item->delete(array('id' => $key));
            }
         }
      }
      Event::log($_POST["tickettemplates_id"], "tickettemplate", 4, "maintain",
                 $_SESSION["glpiname"]." ".$LANG['log'][52]);
   }
   Html::back();

}

Html::displayErrorAndDie("lost");
?>