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

/** @file
* @brief
*/

include ('../inc/includes.php');

Session::checkLoginUser();

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

if (isset($_GET['multiple_actions'])) {
   if (isset($_SESSION['glpi_massiveaction']) && isset($_SESSION['glpi_massiveaction']['POST'])) {
      $_POST = $_SESSION['glpi_massiveaction']['POST'];
   }
}

if (!isset($_POST["itemtype"])
    || !($item = getItemForItemtype($_POST["itemtype"]))
    || !isset($_POST['is_deleted'])) {
   exit();
}

$checkitem = NULL;
if (isset($_POST['check_itemtype'])) {
   if (!($checkitem = getItemForItemtype($_POST['check_itemtype']))) {
      exit();
   }
   if (isset($_POST['check_items_id'])) {
      if (!$checkitem->getFromDB($_POST['check_items_id'])) {
         exit();
      }
   }
}

$actions = $item->getAllMassiveActions($_POST['is_deleted'], $checkitem);
if (!isset($_POST['specific_action']) || !$_POST['specific_action']) {
   if (!isset($actions[$_POST['action']])) {
      Html::displayRightError();
      exit();
   }
} else {
   // No standard massive action for specific one
   if (isset($actions[$_POST['action']])) {
      Html::displayRightError();
      exit();
   }
}

Html::header(__('Bulk modification'), $_SERVER['PHP_SELF']);

if (isset($_GET['multiple_actions'])) {
   if (isset($_SESSION['glpi_massiveaction'])
       && isset($_SESSION['glpi_massiveaction']['items'])) {

      $percent = min(100,round(100*($_SESSION['glpi_massiveaction']['item_count']
                                    - count($_SESSION['glpi_massiveaction']['items']))
                                 /$_SESSION['glpi_massiveaction']['item_count'],0));
      Html::displayProgressBar(400, $percent);
   }
}

if (isset($_POST["action"])
    && isset($_POST["itemtype"])
    && isset($_POST["item"])
    && count($_POST["item"])) {

   /// Save selection
   if (!isset($_SESSION['glpimassiveactionselected'][$_POST["itemtype"]])
       || (count($_SESSION['glpimassiveactionselected'][$_POST["itemtype"]]) == 0)) {
      $_SESSION['glpimassiveactionselected'][$_POST["itemtype"]] = array();
      foreach ($_POST["item"] as $key => $val) {
         if ($val == 1) {
            $_SESSION['glpimassiveactionselected'][$_POST["itemtype"]][$key] = $key;
         }
      }
   }
   if (isset($_SERVER['HTTP_REFERER'])) {
      $REDIRECT = $_SERVER['HTTP_REFERER'];
   } else { /// Security : not used if no problem
      $REDIRECT = $CFG_GLPI['root_doc']."/front/central.php";
   }

   $nbok      = 0;
   $nbnoright = 0;
   $nbko      = 0;
   $res = $item->doMassiveActions($_POST);

   if (is_array($res)
         && isset($res['ok'])
         && isset($res['ko'])
         && isset($res['noright'])) {
      $nbok      = $res['ok'];
      $nbko      = $res['ko'];
      $nbnoright = $res['noright'];
   } else {
      if ($res) {
         $nbok++;
      } else {
         $nbko++;
      }
   }
   if (isset($res['REDIRECT'])) {
      $REDIRECT = $res['REDIRECT'];
   }
   // Default message : all ok
   $message = __('Operation successful');
   // All failed. operations failed
   if ($nbok == 0) {
      $message = __('Failed operation');
      if ($nbnoright) {
         //TRANS: %$1d and %$2d are numbers
         $message .= "<br>".sprintf(__('(%1$d authorizations problems, %2$d failures)'),
                                     $nbnoright, $nbko);
      }
   } else if ($nbnoright || $nbko) {
      // Partial success
      $message = __('Operation performed partially successful');
      $message .= "<br>".sprintf(__('(%1$d authorizations problems, %2$d failures)'),
                                 $nbnoright, $nbko);
   }
   Session::addMessageAfterRedirect($message);
   Html::redirect($REDIRECT);

} else { //action, itemtype or item not defined
   echo "<div class='center'>".
         "<img src='".$CFG_GLPI["root_doc"]."/pics/warning.png' alt='".__s('Warning')."'><br><br>";
   echo "<span class='b'>".__('No selected element or badly defined operation')."</span><br>";
   Html::displayBackLink();
   echo "</div>";
}

Html::footer();
?>