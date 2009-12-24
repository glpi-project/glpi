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

// Relation between Computer and a CommonDevice (motherboard, memory, processor, ...)
class Computer_Device extends CommonDBRelation{

   // From CommonDBTM
   public $table = 'glpi_computers_devices';
   public $type = 'Computer_Device';

   // From CommonDBRelation
   public $itemtype_1 = 'Computer';
   public $items_id_1 = 'computers_id';

   public $itemtype_2 = 'itemtype';
   public $items_id_2 = 'items_id';

   /**
    * Print the form for devices linked to a computer or a template
    *
    *
    * Print the form for devices linked to a computer or a template
    *
    *@param $target filename : where to go when done.
    *@param $ID Integer : Id of the computer or the template to print
    *@param $withtemplate='' boolean : template or basic computer
    *
    *
    *@return Nothing (display)
    *
    **/
   static function showForComputer(Computer $computer, $withtemplate='') {
      global $DB, $LANG;

      $ID = $computer->getField('id');
      if (!$computer->can($ID, 'r')) {
         return false;
      }
      $canedit = ($withtemplate!=2 && $computer->can($ID, 'w'));

      $query = "SELECT count(*) AS NB, `id`, `itemtype`, `items_id`, `specificity`
                FROM `glpi_computers_devices`
                WHERE `computers_id` = '$ID'
                GROUP BY `itemtype`, `items_id`, `specificity`";

      if ($canedit) {
         echo "<form name='form_device_action' action='".getItemTypeFormURL(__CLASS__)."' method=\"post\" >";
         echo "<input type='hidden' name='computers_id' value='$ID'>";
      }
      echo "<table class='tab_cadre_fixe' >";
      echo "<tr><th colspan='63'>".$LANG['title'][30]."</th></tr>";

      $nb=0;
      $prev = '';
      foreach($DB->request($query) as $data) {
         if ($data['itemtype'] != $prev) {
            $prev = $data['itemtype'];
            initNavigateListItems($data['itemtype'], $computer->getTypeName()." = ".$computer->getName());
         }
         addToNavigateListItems($data['itemtype'], $data['items_id']);

         $device = new $data['itemtype'];
         if ($device->getFromDB($data['items_id'])) {
            echo "<tr class='tab_bg_2'>";
            echo "<td class='center'>";
            Dropdown::showInteger('quantity_'.$data['items_id'], $data['NB']);
            echo "</td><td>";
            if ($device->canCreate()) {
               echo "<a href='".$device->getSearchURL()."'>".$device->getTypeName()."</a>";
            } else {
               echo $device->getTypeName();
            }
            echo "</td><td>".$device->getLink()."</td>";

            $spec = $device->getFormData();
            if (isset($spec['label']) && count($spec['label'])) {
               $colspan = (60/count($spec['label']));
               foreach ($spec['label'] as $i => $label) {
                  if (isset($spec['value'][$i])) {
                     echo "<td colspan='$colspan'>".$spec['label'][$i]."&nbsp;: ";
                     echo $spec['value'][$i]."</td>";
                  } else if ($canedit){
                     // Specificity
                     echo "<td class='right' colspan='$colspan'>".$spec['label'][$i]."&nbsp;: ";
                     echo "<input type='text' name='devicevalue_".$data['items_id']."' value='";
                     echo $data['specificity']."' size='".$spec['size']."' ></td>";
                  } else {
                     echo "<td colspan='$colspan'>".$spec['label'][$i]."&nbsp;: ";
                     echo $data['specificity']."</td>";
                  }
               }
            } else {
               echo "<td colspan='60'>&nbsp;</td>";
            }
            echo "</tr>";
            $nb++;
         }
      }
      if ($canedit && $nb>0) {
         echo "<tr><td colspan='63' class='tab_bg_1 center'>";
         echo "<input type='submit' class='submit' name='update_device' value='".
                $LANG['buttons'][7]."'></td></tr>";


         echo "<tr><td colspan='63' class='tab_bg_1 center'>";
         echo $LANG['devices'][0]."&nbsp;: ";
         $types =  array('DeviceMotherboard', 'DeviceProcessor', 'DeviceNetworkCard', 'DeviceMemory',
                         'DeviceHardDrive', 'DeviceDrive', 'DeviceControl', 'DeviceGraphicCard',
                         'DeviceSoundCard', 'DeviceCase', 'DevicePowerSupply', 'DevicePci');
         Dropdown::showAllItems('items_id', '', 0, -1, $types);
         echo "<input type='submit' name='add' value=\"".$LANG['buttons'][8]."\" class='submit'>";
         echo "</tr></table></form>";
      } else {
      echo "</table>";
      }

//      Device::dropdownDeviceSelector($target,$comp->fields["id"],$withtemplate);
   }
}
?>