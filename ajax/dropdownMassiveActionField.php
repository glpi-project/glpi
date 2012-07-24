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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT','..');
include (GLPI_ROOT."/inc/includes.php");

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

if (!isset($_POST["itemtype"]) || !($item = getItemForItemtype($_POST['itemtype']))) {
   exit();
}

if (in_array($_POST["itemtype"], $CFG_GLPI["infocom_types"])) {
   Session::checkSeveralRightsOr(array($_POST["itemtype"] => "w",
                                       "infocom"          => "w"));
} else {
   $item->checkGlobal("w");
}

/// TODO move actions classes  / create standard function to set value : like getValueToDisplay for display
///                                showValueToSelect


if (isset($_POST["itemtype"])
    && isset($_POST["id_field"]) && $_POST["id_field"]) {
   $search = Search::getOptions($_POST["itemtype"]);
   if (!isset($search[$_POST["id_field"]])) {
      exit();
   }
   
   $search            = $search[$_POST["id_field"]];

   $FIELDNAME_PRINTED = false;
   $USE_TABLE         = false;

//    } else {
//       switch ($search["table"]) {
//          case "glpi_infocoms" :  // infocoms case
//             echo "<input type='hidden' name='field' value='".$search["field"]."'>";
//             $FIELDNAME_PRINTED = true;
// 
//             switch ($search["field"]) {
//                case "alert" :
//                   Infocom::dropdownAlert($search["field"]);
//                   break;
// 
//                case "buy_date" :
//                case "use_date" :
//                case "delivery_date" :
//                case "order_date" :
//                case "inventory_date" :
//                case "warranty_date" :
//                   echo "<table><tr><td>";
//                   Html::showDateFormItem($search["field"]);
//                   echo "</td>";
//                   $USE_TABLE = true;
//                   break;
// 
//                case "sink_type" :
//                   Infocom::dropdownAmortType("sink_type");
//                   break;
// 
//                default :
//                   $newtype = getItemTypeForTable($search["table"]);
//                   if ($newtype != $_POST["itemtype"]) {
//                      $item = new $newtype();
//                   }
//                   Html::autocompletionTextField($item, $search["field"],
//                                                 array('entity' => $_SESSION["glpiactive_entity"]));
//             }
//             break;
// 
//          case "glpi_suppliers_infocoms" : // Infocoms suppliers
//             Supplier::dropdown(array('entity' => $_SESSION['glpiactiveentities']));
//             echo "<input type='hidden' name='field' value='suppliers_id'>";
//             $FIELDNAME_PRINTED = true;
//             break;
// 
//          case "glpi_budgets" : // Infocoms budget
//             Budget::dropdown();
//             break;
// 
//          case "glpi_users" : // users
//             switch ($search["linkfield"]) {
// //                case "users_id_assign" :
// //                   User::dropdown(array('name'   => $search["linkfield"],
// //                                        'right'  => 'own_ticket',
// //                                        'entity' => $_SESSION["glpiactive_entity"]));
// //                   break;
// 
//                case "users_id_tech" :
//                   User::dropdown(array('name'   => $search["linkfield"],
//                                        'value'  => 0,
//                                        'right'  => 'interface',
//                                        'entity' => $_SESSION["glpiactive_entity"]));
//                   break;
// 
//                default :
//                   User::dropdown(array('name'   => $search["linkfield"],
//                                        'entity' => $_SESSION["glpiactive_entity"],
//                                        'right'  => 'all'));
//             }
//             break;
// 
//          break;
// 
//          case "glpi_softwareversions" :
//             switch ($search["linkfield"]) {
//                case "softwareversions_id_use" :
//                case "softwareversions_id_buy" :
//                   $_POST['softwares_id'] = $_POST['extra_softwares_id'];
//                   $_POST['myname']       = $search['linkfield'];
//                   include("dropdownInstallVersion.php");
//                   break;
//             }
//             break;
// 
//          default : // dropdown case
//             $plugdisplay = false;
//             // Specific plugin Type case
//             if (($plug=isPluginItemType($_POST["itemtype"]))
//                 // Specific for plugin which add link to core object
//                 || ($plug=isPluginItemType(getItemTypeForTable($search['table'])))) {
//                $plugdisplay = Plugin::doOneHook($plug['plugin'], 'MassiveActionsFieldsDisplay',
//                                                 array('itemtype' => $_POST["itemtype"],
//                                                       'options'  => $search));
//             }
//             $already_display = false;
// 
//             if (isset($search['datatype'])) {
//                switch ($search['datatype']) {
//                   case "date" :
//                      echo "<table><tr><td>";
//                      Html::showDateFormItem($search["linkfield"]);
//                      echo "</td>";
//                      $USE_TABLE       = true;
//                      $already_display = true;
//                      break;
// 
//                   case "datetime" :
//                      echo "<table><tr><td>";
//                      Html::showDateTimeFormItem($search["linkfield"]);
//                      echo "</td>";
//                      $already_display = true;
//                      $USE_TABLE = true;
//                      break;
// 
//                   case "bool" :
//                      Dropdown::showYesNo($search["linkfield"]);
//                      $already_display = true;
//                      break;
// 
//                   case "text" :
//                      echo "<textarea cols='45' rows='5' name='".$search["linkfield"]."'></textarea>";
//                      $already_display = true;
//                      break;
//                }
//             }
// 
//             if (!$plugdisplay && !$already_display) {
//                $cond = (isset($search['condition']) ? $search['condition'] : '');
//                Dropdown::show(getItemTypeForTable($search["table"]),
//                               array('name'      => $search["linkfield"],
//                                     'entity'    => $_SESSION['glpiactiveentities'],
//                                     'condition' => $cond));
                  /// TODO Check all searchoption to add datatype
//             }
//       }
//    }
// 
//    if ($USE_TABLE) {
//       echo "<td>";
//    }

   echo "<table class='tab_glpi'><tr><td>";

   $plugdisplay = false;
   // Specific plugin Type case
   if (($plug=isPluginItemType($_POST["itemtype"]))
      // Specific for plugin which add link to core object
      || ($plug=isPluginItemType(getItemTypeForTable($search['table'])))) {
      $plugdisplay = Plugin::doOneHook($plug['plugin'], 'MassiveActionsFieldsDisplay',
                                       array('itemtype' => $_POST["itemtype"],
                                             'options'  => $search));
   }

   $fieldname = '';
   if (empty($search["linkfield"]) || $search['table'] == 'glpi_infocoms') {
      $fieldname = $search["field"];
   } else {
      $fieldname = $search["linkfield"];
   }
   if (!$plugdisplay) {
      $options = array();
      // For ticket template
      if (isset($_POST['options']) && strlen($_POST['options'])) {
         $options = unserialize(stripslashes($_POST['options']));
      }
      echo $item->getValueToSelect($search, $fieldname, '', $options);
   }

   if (!$FIELDNAME_PRINTED) {
      echo "<input type='hidden' name='field' value='$fieldname'>";
   }
   echo "</td></tr></table>";

   echo "<br><input type='submit' name='massiveaction' class='submit' value='".__s('Post')."'>";

}
?>
