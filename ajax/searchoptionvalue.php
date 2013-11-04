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

// Direct access to file
if (strpos($_SERVER['PHP_SELF'],"searchoptionvalue.php")) {
   include ('../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

if (!defined('GLPI_ROOT')) {
   die("Can not acces directly to this file");
}

Session::checkLoginUser();

if (isset($_POST['searchtype'])) {

   if (!is_array($_POST['searchopt'])) {
      $searchopt = Toolbox::decodeArrayFromInput($_POST['searchopt']);
   } else {
      $searchopt      = $_POST['searchopt'];
   }
   $_POST['value'] = rawurldecode($_POST['value']);

   $addmeta        = "";
   if (isset($_POST['meta']) && $_POST['meta']) {
      $addmeta = '2';
   }

   $inputname        = 'contains'.$addmeta.'['.$_POST['num'].']';
   $display          = false;
   $item             = getItemForItemtype($_POST['itemtype']);
   $options['value'] = $_POST['value'];
   // For tree dropdpowns
   $options['permit_select_parent'] = true;

   switch ($_POST['searchtype']) {
      case "equals" :
      case "notequals" :
      case "morethan" :
      case "lessthan" :
      case "under" :
      case "notunder" :
        if (!$display && isset($searchopt['field'])) {
            // Specific cases
            switch ($searchopt['table'].".".$searchopt['field']) {
               case "glpi_changes.status" :
               case "glpi_changes.impact" :
               case "glpi_changes.urgency" :
               case "glpi_problems.status" :
               case "glpi_problems.impact" :
               case "glpi_problems.urgency" :
               case "glpi_tickets.status" :
               case "glpi_tickets.impact" :
               case "glpi_tickets.urgency" :
                  $options['showtype'] = 'search';
                  break;

               case "glpi_changes.priority" :
               case "glpi_problems.priority" :
               case "glpi_tickets.priority" :
                  $options['showtype']  = 'search';
                  $options['withmajor'] = true;
                  break;


               case "glpi_tickets.global_validation" :
                  $options['all'] = true;
                  break;


               case "glpi_ticketvalidations.status" :
                  $options['all'] = true;
                  break;
            }

            // Standard datatype usage
            if (!$display && isset($searchopt['datatype'])) {
               switch ($searchopt['datatype']) {

                  case "date" :
                  case "date_delay" :
                  case "datetime" :
                     $options['relative_dates'] = true;
                     break;
               }
            }
            $out = $item->getValueToSelect($searchopt, $inputname, $_POST['value'], $options);
            if (strlen($out)) {
               echo $out;
               $display = true;
            }

            //Could display be handled by a plugin ?
            if (!$display
                && $plug = isPluginItemType(getItemTypeForTable($searchopt['table']))) {
               $function = 'plugin_'.$plug['plugin'].'_searchOptionsValues';
               if (function_exists($function)) {
                  $params = array('name'           => $inputname,
                                  'searchtype'     => $_POST['searchtype'],
                                  'searchoption'   => $searchopt,
                                  'value'          => $_POST['value']);
                  $display = $function($params);
               }
            }

        }
        break;
   }

   // Default case : text field
   if (!$display) {
        echo "<input type='text' size='13' name='$inputname' value=\"".
               Html::cleanInputText($_POST['value'])."\">";
   }
}
?>
