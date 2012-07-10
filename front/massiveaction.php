<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2012 by the INDEPNET Development Team.

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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

Session::checkCentralAccess();

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

if (isset($_GET['multiple_actions'])) {
   if (isset($_SESSION['glpi_massiveaction']) && isset($_SESSION['glpi_massiveaction']['POST'])) {
      $_POST = $_SESSION['glpi_massiveaction']['POST'];
   }
}

if (!isset($_POST["itemtype"]) || !($item = getItemForItemtype($_POST["itemtype"]))
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

// Right check
$actions = $item->getAllMassiveActions($_POST['is_deleted'], $checkitem);
if (!isset($actions[$_POST['action']])) {
   Html::displayRightError();
   exit();
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
   if (!isset($_SESSION['glpimassiveactionselected'])
       || count($_SESSION['glpimassiveactionselected']) == 0) {

      $_SESSION['glpimassiveactionselected'] = array();
      foreach ($_POST["item"] as $key => $val) {
         if ($val == 1) {
            $_SESSION['glpimassiveactionselected'][$key] = $key;
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
//    switch($_POST["action"]) {

// 
//       case "connect_to_computer" :
//          if (isset($_POST["connect_item"]) && $_POST["connect_item"]) {
//             $conn = new Computer_Item();
//             foreach ($_POST["item"] as $key => $val) {
//                if ($val == 1) {
//                   $input = array('computers_id' => $key,
//                                  'itemtype'     => $_POST["itemtype"],
//                                  'items_id'     => $_POST["connect_item"]);
//                   if ($conn->can(-1, 'w', $input)) {
//                      if ($conn->add($input)) {
//                         $nbok++;
//                      } else {
//                         $nbko++;
//                      }
//                   } else {
//                      $nbnoright++;
//                   }
//                }
//             }
//          }
//          break;
// 
//       case "connect" :
//          if (isset($_POST["connect_item"]) && $_POST["connect_item"]) {
//             $conn = new Computer_Item();
//             foreach ($_POST["item"] as $key => $val) {
//                if ($val == 1) {
//                   $input = array('computers_id' => $_POST["connect_item"],
//                                  'itemtype'     => $_POST["itemtype"],
//                                  'items_id'     => $key);
//                   if ($conn->can(-1, 'w', $input)) {
//                      if ($conn->add($input)) {
//                         $nbok++;
//                      } else {
//                         $nbko++;
//                      }
//                   } else {
//                      $nbnoright++;
//                   }
//                }
//             }
//          }
//          break;
// 
//       case "disconnect" :
//          $conn = new Computer_Item();
//          foreach ($_POST["item"] as $key => $val) {
//             if ($val == 1) {
//                if ($item->can($key, 'd')) {
//                   if ($conn->disconnectForItem($item)) {
//                      $nbok++;
//                   } else {
//                      $nbko++;
//                   }
//                } else {
//                   $nbnoright++;
//                }
//             }
//          }
//          break;
// 
// 
// 

// 
//       case "duplicate" : // For calendar duplicate in another entity
//          if (method_exists($item,'duplicate')) {
//             $options = array();
//             if ($item->isEntityAssign()) {
//                $options = array('entities_id' => $_POST['entities_id']);
//             }
//             foreach ($_POST["item"] as $key => $val) {
//                if ($val == 1) {
//                   if ($item->getFromDB($key)) {
//                      if (!$item->isEntityAssign()
//                          || ($_POST['entities_id'] != $item->getEntityID())) {
//                         if ($item->can(-1,'w',$options)) {
//                            if ($item->duplicate($options)) {
//                               $nbok++;
//                            } else {
//                               $nbko++;
//                            }
//                         } else {
//                            $nbnoright++;
//                         }
//                      } else {
//                         $nbko++;
//                      }
//                   } else {
//                      $nbko++;
//                   }
//                }
//             }
//          }
//          break;
// 
//       case "install" :
//          if (isset($_POST['softwareversions_id']) && ($_POST['softwareversions_id'] > 0)) {
//             $inst = new Computer_SoftwareVersion();
//             foreach ($_POST['item'] as $key => $val) {
//                if ($val == 1) {
//                   $input = array('computers_id'        => $key,
//                                  'softwareversions_id' => $_POST['softwareversions_id']);
//                   if ($inst->can(-1, 'w', $input)) {
//                      if ($inst->add($input)) {
//                         $nbok++;
//                      } else {
//                         $nbko++;
//                      }
//                   } else {
//                      $nbnoright++;
//                   }
//                }
//             }
//          }
//          break;
// 

// 

//
//

// 
//       case "compute_software_category" :
//          $softcatrule = new RuleSoftwareCategoryCollection();
//          $soft = new Software();
//          foreach ($_POST["item"] as $key => $val) {
//             if ($val == 1) {
//                $params = array();
//                //Get software name and manufacturer
//                if ($soft->can($key,'w')) {
//                   $params["name"]             = $soft->fields["name"];
//                   $params["manufacturers_id"] = $soft->fields["manufacturers_id"];
//                   $params["comment"]          = $soft->fields["comment"];
//                   $output = $softcatrule->processAllRules(null, $output, $params);
//                   //Process rules
//                   if ($soft->update(array('id' => $output['id'],
//                                           'softwarecategories_id' => $output['softwarecategories_id']))) {
//                      $nbok++;
//                   } else {
//                      $nbko++;
//                   }
//                } else {
//                   $nbnoright++;
//                }
//             }
//          }
//          break;
// 
//       case "replay_dictionnary" :
//          $softdictionnayrule = new RuleDictionnarySoftwareCollection();
//          $ids                = array();
//          foreach ($_POST["item"] as $key => $val) {
//             if ($val == 1) {
//                if ($item->can($key,'w')) {
//                   $ids[] = $key;
//                } else {
//                   $nbnoright++;
//                }
//             }
//          }
//          if ($softdictionnayrule->replayRulesOnExistingDB(0, 0, $ids)>0){
//             $nbok += count($ids);
//          } else {
//             $nbko += count($ids);
//          }
// 
//          break;
// 



   if (is_array($res)
         && isset($res['ok'])
         && isset($res['ko'])
         && isset($res['noright'])) {
      $nbok      = $res['ok'];
      $nbko      = $res['ko'];
      $nbnoright = $res['noright'];
   } else {
      if ($res){
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
         "<img src='".$CFG_GLPI["root_doc"]."/pics/warning.png' alt='warning'><br><br>";
   echo "<span class='b'>".__('No selected element or badly defined operation')."</span><br>";
   Html::displayBackLink();
   echo "</div>";
}

Html::footer();
?>