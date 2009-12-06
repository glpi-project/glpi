<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

/** Show Infocom form for an item
* @param $target string: where to go on action
* @param $itemtype integer: item type
* @param $dev_ID integer: item ID
* @param $show_immo boolean: show immobilisation infos
* @param $withtemplate integer: template or basic item
*/
function showInfocomForm($target,$itemtype,$dev_ID,$show_immo=true,$withtemplate='') {
   global $CFG_GLPI,$LANG;
   // Show Infocom or blank form
   if (!haveRight("infocom","r")) {
      return false;
   }
   $date_tax=$CFG_GLPI["date_tax"];

   $ic = new Infocom;
   $ci=new CommonItem();
   $option="";
   if ($withtemplate==2) {
      $option=" readonly ";
   }

   if (!strpos($_SERVER['PHP_SELF'],"infocoms-show")
       && ($itemtype==SOFTWARE_TYPE || $itemtype==CARTRIDGEITEM_TYPE || $itemtype==CONSUMABLEITEM_TYPE)) {
      echo "<div class='center'>".$LANG['financial'][84]."</div>";
   }

   if ($ci->getFromDB($itemtype,$dev_ID)) {
      //$entity=$ci->obj->getEntityID();

      if (!$ic->getFromDBforDevice($itemtype,$dev_ID)) {
         //$input=array('entities_id'=>$entity);
         $input=array('itemtype' => $itemtype,
                      'items_id' => $dev_ID);
         if ($ic->can(-1,"w",$input) && $withtemplate!=2) {
            echo "<table class='tab_cadre'><tr><th>";
            echo "<a href='$target?itemtype=$itemtype&amp;items_id=$dev_ID&amp;add=add'>".
                   $LANG['financial'][68]."</a></th></tr></table>";
         }
      } else { // getFromDBforDevice
         $canedit = ($ic->can($ic->fields['id'], "w") && $withtemplate!=2);
         if ($canedit) {
            echo "<form name='form_ic' method='post' action=\"$target\">";
         }
         echo "<div class='center'>";
         echo "<table class='tab_cadre".(!strpos($_SERVER['PHP_SELF'],"infocoms-show")?"_fixe":"")."'>";

         echo "<tr><th colspan='4'>".$LANG['financial'][3]."</th></tr>";

         echo "<tr class='tab_bg_1'><td>".$LANG['financial'][26]."&nbsp;:</td>";
         echo "<td>";
         if ($withtemplate==2) {
            echo getDropdownName("glpi_suppliers",$ic->fields["suppliers_id"]);
         } else {
            dropdownValue("glpi_suppliers","suppliers_id",$ic->fields["suppliers_id"],1,
                          $ci->getField('entities_id'));
         }
         echo "</td>";
         echo "<td>".$LANG['financial'][82]."&nbsp;:</td>";
         echo "<td >";
         autocompletionTextField("bill","glpi_infocoms","bill",$ic->fields["bill"],40,-1,-1,$option);
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td>".$LANG['financial'][18]."&nbsp;:</td>";
         echo "<td >";
         autocompletionTextField("order_number","glpi_infocoms","order_number",
                                 $ic->fields["order_number"],40,-1,-1,$option);
         echo "</td>";
         echo "<td>".$LANG['financial'][19]."&nbsp;:</td><td>";
         autocompletionTextField("delivery_number","glpi_infocoms","delivery_number",
                                 $ic->fields["delivery_number"],40,-1,-1,$option);
         echo "</td></tr>";

         // Can edit calendar ?
         $editcalendar=($withtemplate!=2);

         echo "<tr class='tab_bg_1'><td>".$LANG['financial'][14]."&nbsp;:</td><td>";
         showDateFormItem("buy_date",$ic->fields["buy_date"],true,$editcalendar);
         echo "</td>";
         echo "<td>".$LANG['financial'][76]."&nbsp;:</td><td>";
         showDateFormItem("use_date",$ic->fields["use_date"],true,$editcalendar);
         echo "</td></tr>";

         if ($show_immo) {
            echo "<tr class='tab_bg_1'><td>".$LANG['financial'][15]."&nbsp;:</td><td>";
            if ($withtemplate==2) {
               // -1 = life
               if ($ic->fields["warranty_duration"]==-1) {
                  echo $LANG['financial'][2];
               } else {
                  echo $ic->fields["warranty_duration"];
               }
            } else {
               dropdownInteger("warranty_duration",$ic->fields["warranty_duration"],0,120,1,
                               array(-1=>$LANG['financial'][2]));
            }
            if ($ic->fields["warranty_duration"]>=0) {
               echo " ".$LANG['financial'][57];
            }
            echo "&nbsp;&nbsp; &nbsp; &nbsp;&nbsp;&nbsp;".$LANG['financial'][88]."&nbsp;";
            echo getWarrantyExpir($ic->fields["buy_date"],$ic->fields["warranty_duration"]);
            echo "</td>";

            if (haveRight("budget","r")) {
               echo "<td>".$LANG['financial'][87]."&nbsp;:</td><td >";
               dropdownValue("glpi_budgets","budgets_id",$ic->fields["budgets_id"],0,
                             $ci->obj->getEntityID());
               echo "</td></tr>";
            } else {
               echo "<td colspan='2'></td></tr>";
            }

            echo "<tr class='tab_bg_1'><td>".$LANG['financial'][78]."&nbsp;:</td>";
            echo "<td><input type='text' $option name='warranty_value' value=\"".
                       formatNumber($ic->fields["warranty_value"],true)."\" size='14'></td>";
            echo "<td>".$LANG['financial'][16]."&nbsp;:</td>";
            echo "<td >";
            autocompletionTextField("warranty_info","glpi_infocoms","warranty_info",
                                    $ic->fields["warranty_info"],40,-1,-1,$option);
            echo "</td></tr>";
         }

         echo "<tr class='tab_bg_1'><td>".$LANG['financial'][21]."&nbsp;:</td>";
         echo "<td ".($show_immo?"":" colspan='3'").">
               <input type='text' name='value' $option value=\"".
                formatNumber($ic->fields["value"],true)."\" size='14'></td>";
         if ($show_immo) {
            echo "<td>".$LANG['financial'][81]."&nbsp;:</td><td>";
            echo formatNumber(Infocom::Amort($ic->fields["sink_type"],$ic->fields["value"],
                  $ic->fields["sink_time"],$ic->fields["sink_coeff"],$ic->fields["buy_date"],
                  $ic->fields["use_date"],$date_tax,"n"));
            echo "</td>";
         }
         echo "</tr>";

         if ($show_immo) {
            echo "<tr class='tab_bg_1'><td>".$LANG['financial'][20]."*&nbsp;:</td>";
            echo "<td >";
            $objectName = autoName($ic->fields["immo_number"], "immo_number", ($withtemplate==2),
                          INFOCOM_TYPE,$ci->getField('entities_id'));
            autocompletionTextField("immo_number","glpi_infocoms","immo_number",
                                    $objectName,40,-1,-1,$option);
            echo "</td>";
            echo "<td>".$LANG['financial'][22]."&nbsp;:</td><td >";
            if ($withtemplate==2) {
               echo Infocom::getAmortTypeName($ic->fields["sink_type"]);
            } else {
               Infocom::dropdownAmortType("sink_type",$ic->fields["sink_type"]);
            }
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'><td>".$LANG['financial'][23]."&nbsp;:</td><td>";
            if ($withtemplate==2) {
               echo $ic->fields["sink_time"];
            } else {
               dropdownInteger("sink_time",$ic->fields["sink_time"],0,15);
            }
            echo " ".$LANG['financial'][9];
            echo "</td>";
            echo "<td>".$LANG['financial'][77]."&nbsp;:</td>";
            echo "<td >";
            autocompletionTextField("sink_coeff","glpi_infocoms","sink_coeff",
                                    $ic->fields["sink_coeff"],14,-1,-1,$option);
            echo "</td></tr>";
         }
         //TCO
         if ($itemtype!=SOFTWARE_TYPE && $itemtype!=CARTRIDGEITEM_TYPE && $itemtype!=CONSUMABLEITEM_TYPE
             && $itemtype!=CONSUMABLE_TYPE && $itemtype!=SOFTWARELICENSE_TYPE
             && $itemtype!=CARTRIDGE_TYPE) {

            echo "<tr class='tab_bg_1'><td>";
            echo $LANG['financial'][89]."&nbsp;:</td><td>";
            echo Infocom::showTco($ci->getField('ticket_tco'),$ic->fields["value"]);
            echo "</td><td>".$LANG['financial'][90]."&nbsp;:</td><td>";
            echo Infocom::showTco($ci->getField('ticket_tco'),$ic->fields["value"],$ic->fields["buy_date"]);
            echo "</td></tr>";
         }

         if ($CFG_GLPI['use_mailing']) {
            echo "<tr class='tab_bg_1'><td>".$LANG['setup'][247]."&nbsp;:</td>";
            echo "<td>";
            echo Infocom::dropdownAlert("alert",$ic->fields["alert"]);
            echo "</td>";
            echo "<td>&nbsp;</td>";
            echo "<td >&nbsp;</td></tr>";
         }
         // commment
         echo "<tr class='tab_bg_1'><td class='middle'>";
         echo $LANG['common'][25]."&nbsp;:</td>";
         echo "<td class='left' colspan='3'>
               <textarea cols='116' $option rows='2' name='comment' >".$ic->fields["comment"]."
               </textarea>";
         echo "</td></tr>";

         if ($canedit) {
            echo "<tr>";
            echo "<td class='tab_bg_2 center' colspan='2'>";
            echo "<input type='hidden' name='id' value=\"".$ic->fields['id']."\">";
            echo "<input type='submit' name='update' value=\"".$LANG['buttons'][7]."\" class='submit'>";
            echo "</td>";
            echo "<td class='tab_bg_2 center' colspan='2'>";
            echo "<input type='submit' name='delete' value=\"".$LANG['buttons'][6]."\" class='submit'>";
            echo "</td></tr>";
            echo "</table></div></form>";
         } else {
            echo "</table></div>";
         }
      }
   }
}


?>
