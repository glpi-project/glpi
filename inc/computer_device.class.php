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

// Relation between Computer and Items (monitor, printer, phone, peripheral only)
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
   static function showDeviceComputerForm($target,$ID,$withtemplate='') {
      global $LANG,$CFG_GLPI;

      if (!haveRight("computer","r")) {
         return false;
      }
      $canedit=haveRight("computer","w");

      $comp = new Computer;
      if (empty($ID) && $withtemplate == 1) {
         $comp->getEmpty();
      } else {
         $comp->getFromDBwithDevices($ID);
      }

      if (!empty($ID)) {
         echo "<div class='center'>";
         echo "<form name='form_device_action' action=\"$target\" method=\"post\" >";
         echo "<input type='hidden' name='id' value='$ID'>";
         echo "<input type='hidden' name='device_action' value='$ID'>";
         echo "<table class='tab_cadre_fixe' >";
         echo "<tr><th colspan='65'>".$LANG['title'][30]."</th></tr>";
         foreach($comp->devices as $key => $val) {
            $device = new Device($val["devType"]);
            $device->getFromDB($val["devID"]);
            printDeviceComputer($device,$val["quantity"],$val["specificity"],$comp->fields["id"],
                                $val["compDevID"],$withtemplate);

         }

         if ($canedit && !(!empty($withtemplate) && $withtemplate == 2)
                      && count($comp->devices)) {
            echo "<tr><td colspan='65' class='tab_bg_1 center'>";
            echo "<input type='submit' class='submit' name='update_device' value='".
                   $LANG['buttons'][7]."'></td></tr>";
         }
         echo "</table>";
         echo "</form>";
         //ADD a new device form.
         Device::dropdownDeviceSelector($target,$comp->fields["id"],$withtemplate);
         echo "</div><br>";
      }
   }
}
?>