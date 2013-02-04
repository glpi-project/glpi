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

// ----------------------------------------------------------------------
// Original Author of file: Nelly Mahu-Lasson
// Purpose of file:
// ----------------------------------------------------------------------

// Direct access to file
if (strpos($_SERVER['PHP_SELF'],"ticketiteminformation.php")) {
   $AJAX_INCLUDE = 1;
   define('GLPI_ROOT','..');
   include (GLPI_ROOT."/inc/includes.php");
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

Session::checkLoginUser();

if (isset($_REQUEST["my_items"]) && !empty($_REQUEST["my_items"])) {
   $splitter = explode("_",$_REQUEST["my_items"]);
   if (count($splitter) == 2) {
      $_REQUEST["itemtype"] = $splitter[0];
      $_REQUEST["items_id"] = $splitter[1];
   }
}

if (isset($_REQUEST['itemtype']) && isset($_REQUEST['items_id']) && $_REQUEST['items_id'] > 0) {
   // Security
   if (!class_exists($_REQUEST['itemtype']) ) {
      exit();
   }

   $days   = 3;
   $ticket = new Ticket();
   $data   = $ticket->getActiveOrSolvedLastDaysTicketsForItem($_REQUEST['itemtype'],
                                                              $_REQUEST['items_id'], $days);

   echo count($data).'&nbsp;'.$LANG['job'][36];
   if (count($data)) {
      $content = '';
      foreach ($data as $title) {
         $content .= $title.'<br>';
      }
      echo '&nbsp;';
      Html::showToolTip($content);
   }
}
?>