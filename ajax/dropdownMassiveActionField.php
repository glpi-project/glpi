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

if (isset($_POST["itemtype"])
    && isset($_POST["id_field"]) && $_POST["id_field"]) {
   $search = Search::getOptions($_POST["itemtype"]);
   if (!isset($search[$_POST["id_field"]])) {
      exit();
   }
   
   $search            = $search[$_POST["id_field"]];

   $FIELDNAME_PRINTED = false;
   $USE_TABLE         = false;


//          break;
// 
//             if (!$plugdisplay && !$already_display) {
//                $cond = (isset($search['condition']) ? $search['condition'] : '');
//                Dropdown::show(getItemTypeForTable($search["table"]),
//                               array('name'      => $search["linkfield"],
//                                     'entity'    => $_SESSION['glpiactiveentities'],
//                                     'condition' => $cond));
                  /// TODO Check all searchoption to add datatype
//             }

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
      // For ticket template or aditional options of massive actions

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
