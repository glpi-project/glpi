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

// Direct access to file
if (strpos($_SERVER['PHP_SELF'], "ticketiteminformation.php")) {
   $AJAX_INCLUDE = 1;
   include ('../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

Session::checkLoginUser();

if (isset($_POST["my_items"]) && !empty($_POST["my_items"])) {
   $splitter = explode("_", $_POST["my_items"]);
   if (count($splitter) == 2) {
      $_POST["itemtype"] = $splitter[0];
      $_POST["items_id"] = $splitter[1];
   }
}

if (isset($_POST['itemtype'])
    && isset($_POST['items_id']) && ($_POST['items_id'] > 0)) {
   // Security
   if (!class_exists($_POST['itemtype'])) {
      exit();
   }

   $days   = 3;
   $ticket = new Ticket();
   $data   = $ticket->getActiveOrSolvedLastDaysTicketsForItem($_POST['itemtype'],
                                                              $_POST['items_id'], $days);

   $nb = count($data);
   printf(_n('%s ticket in progress or recently solved on this item.',
             '%s tickets in progress or recently solved on this item.', $nb),
          $nb);

   if ($nb) {
      $content = '';
      foreach ($data as $title) {
         $content .= $title.'<br>';
      }
      echo '&nbsp;';
      Html::showToolTip($content);
   }
}
