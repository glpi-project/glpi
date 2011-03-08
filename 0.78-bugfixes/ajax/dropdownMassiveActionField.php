<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT','..');
include (GLPI_ROOT."/inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
header_nocache();

if (!isset($_POST["itemtype"]) || !class_exists($_POST["itemtype"])) {
   exit();
}

$item = new $_POST["itemtype"]();
if (in_array($_POST["itemtype"],$CFG_GLPI["infocom_types"])) {
   checkSeveralRightsOr(array($_POST["itemtype"]=>"w","infocom"=>"w"));
} else {
   $item->checkGlobal("w");
}


if (isset($_POST["itemtype"]) && isset($_POST["id_field"]) && $_POST["id_field"]) {
   $search = Search::getOptions($_POST["itemtype"]);
   $search = $search[$_POST["id_field"]];

   // Specific budget case
   if ($_POST["id_field"]==50) {
      $search["linkfield"]="budgets_id";
   }
   $FIELDNAME_PRINTED=false;

   echo '&nbsp;';

   if ($search["table"]==getTableForItemType($_POST["itemtype"])) { // field type
      switch ($search["table"].".".$search["linkfield"]) {
         case "glpi_cartridgeitems.alarm_threshold" :
         case "glpi_consumableitems.alarm_threshold" :
            Dropdown::showInteger($search["linkfield"],0,-1,100);
            break;

         case "glpi_contracts.duration" :
         case "glpi_contracts.notice" :
            Dropdown::showInteger($search["field"],0,0,120);
            echo " ".$LANG['financial'][57];
            break;

         case "glpi_softwarelicenses.number" :
            Dropdown::showInteger($search["linkfield"],0,1,1000,1,array(-1=>$LANG['software'][4]));
            break;

         case "glpi_contracts.alert" :
            Contract::dropdownAlert($search["linkfield"],0);
            break;

         case "glpi_tickets.status" :
            Ticket::dropdownStatus($search["linkfield"]);
            break;

         case "glpi_tickets.priority" :
            Ticket::dropdownPriority($search["linkfield"]);
            break;
         case "glpi_tickets.impact" :
            Ticket::dropdownImpact($search["linkfield"]);
            break;
         case "glpi_tickets.urgency" :
            Ticket::dropdownUrgency($search["linkfield"]);
            break;
         case "glpi_tickets.global_validation" :
            TicketValidation::dropdownStatus($search["linkfield"]);
            break;

         default :
            // Specific plugin Type case
            $plugdisplay=false;
            if ($plug=isPluginItemType($_POST["itemtype"])) {
               $plugdisplay=doOneHook($plug['plugin'],
                                         'MassiveActionsFieldsDisplay',
                                          array('itemtype'  => $_POST["itemtype"],
                                                'options'     => $search));
            }
            $already_display=false;
            if (isset($search['datatype'])) {
               switch ($search['datatype']) {
                  case "date" :
                     showDateFormItem($search["field"]);
                     $already_display=true;
                     break;

                  case "datetime" :
                     showDateTimeFormItem($search["field"]);
                     $already_display=true;
                     break;

                  case "bool" :
                     Dropdown::showYesNo($search["linkfield"]);
                     $already_display=true;
                     break;
               }
            }
            if (!$plugdisplay && !$already_display) {
               $newtype=getItemTypeForTable($search["table"]);
               if ($newtype != $_POST["itemtype"]) {
                  $item = new $newtype();
               }
               autocompletionTextField($item,$search["field"],
                                       array('name'   => $search["linkfield"],
                                             'entity' => $_SESSION["glpiactive_entity"]));
            }
            break;
      }
   } else {
      switch ($search["table"]) {
         case "glpi_infocoms" :  // infocoms case
            switch ($search["field"]) {
               case "alert" :
                  Infocom::dropdownAlert($search["field"]);
                  break;

               case "buy_date" :
               case "use_date" :
                  showDateFormItem($search["field"]);
                  echo "&nbsp;&nbsp;";
                  break;

               case "sink_type" :
                  Infocom::dropdownAmortType("sink_type");
                  break;

               case "sink_time" :
                  Dropdown::showInteger("sink_time",0,0,15);
                  break;

               case "warranty_duration" :
                  Dropdown::showInteger("warranty_duration",0,0,120,1,array(-1 => $LANG['financial'][2]));
                  echo " ".$LANG['financial'][57]."&nbsp;&nbsp;";
                  break;

               default :
                  $newtype=getItemTypeForTable($search["table"]);
                  if ($newtype != $_POST["itemtype"]) {
                     $item = new $newtype();
                  }
                  autocompletionTextField($item,$search["field"],
                                          array('entity' => $_SESSION["glpiactive_entity"]));
                  break;
            }
            break;

         case "glpi_suppliers_infocoms" : // Infocoms suppliers
            Dropdown::show('Supplier', array('entity' => $_SESSION['glpiactiveentities']));
            echo "<input type='hidden' name='field' value='suppliers_id'>";
            $FIELDNAME_PRINTED=true;
            break;

         case "glpi_budgets" : // Infocoms budget
            Dropdown::show('Budget');
            break;

         case "glpi_ocslinks" : // auto_update ocs_link
            Dropdown::showYesNo("_auto_update_ocs");
            echo "<input type='hidden' name='field' value='_auto_update_ocs'>";
            $FIELDNAME_PRINTED=true;
            break;

         case "glpi_users" : // users
            switch ($search["linkfield"]) {
               case "users_id_assign" :
                  User::dropdown(array('name'   => $search["linkfield"],
                                       'right'  => 'own_ticket',
                                       'entity' => $_SESSION["glpiactive_entity"]));
                  break;

               case "users_id_tech" :
                  User::dropdown(array('name'   => $search["linkfield"],
                                       'value'  => 0,
                                       'right'  => 'interface',
                                       'entity' => $_SESSION["glpiactive_entity"]));
                  break;

               default :
                  User::dropdown(array('name'   => $search["linkfield"],
                                       'entity' => $_SESSION["glpiactive_entity"],
                                       'right'  => 'all'));
                  break;
            }
            break;
         break;

         case "glpi_softwareversions":
            switch ($search["linkfield"]) {
               case "softwareversions_id_use" :
               case "softwareversions_id_buy" :
                  $_POST['softwares_id']=$_POST['extra_softwares_id'];
                  $_POST['myname']=$search['linkfield'];
                  include("dropdownInstallVersion.php");
                  break;
            }
            break;

         default : // dropdown case
            $plugdisplay=false;
            // Specific plugin Type case
            if (($plug=isPluginItemType($_POST["itemtype"]))
                // Specific for plugin which add link to core object
                || ($plug=isPluginItemType(getItemTypeForTable($search['table'])))) {
               $plugdisplay = doOneHook($plug['plugin'],
                                       'MassiveActionsFieldsDisplay',
                                          array('itemtype'  => $_POST["itemtype"],
                                                'options'     => $search));
            }
            if (!$plugdisplay) {
               Dropdown::show(getItemTypeForTable($search["table"]),
                           array('name'   => $search["linkfield"],
                                 'entity' => $_SESSION['glpiactiveentities']));
            }
            break;
      }
   }
   if (!$FIELDNAME_PRINTED) {
      if (empty($search["linkfield"])) {
         echo "<input type='hidden' name='field' value='".$search["field"]."'>";
      } else {
         echo "<input type='hidden' name='field' value='".$search["linkfield"]."'>";
      }
   }
   echo "&nbsp;<input type='submit' name='massiveaction' class='submit' value=\"".
                $LANG['buttons'][2]."\" >";
}

?>
