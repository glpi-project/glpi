<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

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
// Original Author of file: Walif Nouh
// Purpose of file: Virtual machine management
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Class to manage virtual machines
class ComputerVirtualMachine extends CommonDBChild {

   // From CommonDBChild
   public $itemtype  = 'Computer';
   public $items_id  = 'computers_id';
   public $dohistory = true;


   static function getTypeName() {
      global $LANG;

      return $LANG['computers'][57];
   }


   function canCreate() {
      return haveRight('computer', 'w');
   }


   function canView() {
      return haveRight('computer', 'r');
   }


   function prepareInputForAdd($input) {

      // Not attached to computer -> not added
      if (!isset($input['computers_id']) || $input['computers_id'] <= 0) {
         return false;
      }

      if (!isset($input['entities_id'])) {
         $input['entities_id'] = getItemEntity('Computer', $input['computers_id']);
      }

      return $input;
   }


   function post_getEmpty () {

      $this->fields["vcpu"] = '0';
      $this->fields["ram"]  = '0';
   }


   /**
    * Print the version form
    *
    * @param $ID integer ID of the item
    * @param $options array
    *     - target for the Form
    *     - computers_id ID of the computer for add process
    *
    * @return true if displayed  false if item not found or not right to display
   **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI,$LANG;

      $computers_id = -1;
      if (isset($options['computers_id'])) {
        $computers_id = $options['computers_id'];
      }

      if (!haveRight("computer","w")) {
        return false;
      }

      $comp = new Computer();

      if ($ID > 0) {
         $this->check($ID,'r');
         $comp->getFromDB($this->fields['computers_id']);
      } else {
         $comp->getFromDB($computers_id);
         // Create item
         $input = array('entities_id' => $comp->getEntityID());
         $this->check(-1, 'w', $input);
      }

      $this->showTabs($options);
      $this->showFormHeader($options);

      if ($ID>0) {
        $computers_id=$this->fields["computers_id"];
      } else {
         echo "<input type='hidden' name='computers_id' value='$computers_id'>";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['help'][25]."&nbsp;:</td>";
      echo "<td colspan='3'>".$comp->getLink()."</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][16]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField($this, "name");
      echo "</td><td>".$LANG['computers'][62]."&nbsp;:</td>";
      echo "<td>";
      Dropdown::show('VirtualMachineType',
                     array('value' => $this->fields['virtualmachinetypes_id']));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['computers'][60]."&nbsp;:</td>";
      echo "<td>";
      Dropdown::show('VirtualMachineSystem',
                     array('value' => $this->fields['virtualmachinesystems_id']));
      echo "</td><td>".$LANG['computers'][63]."&nbsp;:</td>";
      echo "<td>";
      Dropdown::show('VirtualMachineState',
                     array('value' => $this->fields['virtualmachinestates_id']));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['computers'][58]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField($this, "uuid");
      echo "</td>";

      echo "<td>".$LANG['computers'][61]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField($this, "vcpu");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['computers'][24]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField($this, "ram");
      echo "</td>";

      echo "<td>".$LANG['computers'][64]."&nbsp;:</td>";
      echo "<td>";
      if ($link_computer = self::findVirtualMachine($this->fields)) {
         $computer = new Computer();
         if ($computer->can($link_computer,'r')) {
            $url = "<a href='computer.form.php?id=".$link_computer."'>";
            $url.= $computer->fields["name"]."</a>";

            $tooltip = $LANG['common'][16]."&nbsp;: ".$computer->fields['name'];
            $tooltip.= "<br>".$LANG['common'][19]."&nbsp;: ";
            $tooltip.= "<br>".$computer->fields['serial'];
            $tooltip.= "<br>".$computer->fields['comment'];
            $url .= "&nbsp; ".showToolTip($tooltip, array('display' => false));
         } else {
            $url = $this->fields['name'];
         }
         echo $url;
      }
      echo "</td>";

      echo "</tr>";

      $this->showFormButtons($options);
      $this->addDivForTabs();

      return true;
   }


   function defineTabs($options=array()) {
      global $LANG;

      $ong[1] = $LANG['title'][26];

      return $ong;
   }


   /**
    * Show hosts for a virtualmachine
    *
    * @param $comp a computer object that represents the virtual machine
    *
    * @return Nothing (call to classes members)
   **/
   static function showForVirtualMachine(Computer $comp) {
      global $DB, $LANG;

      $ID = $comp->fields['id'];

      if (!$comp->getFromDB($ID) || !$comp->can($ID, "r")) {
         return false;
      }
      $canedit = $comp->can($ID, "w");

      echo "<div class='center'>";

      if (isset($comp->fields['uuid']) && $comp->fields['uuid'] != '') {
         $where = "`uuid`".self::getUUIDRestrictRequest($comp->fields['uuid']);
         $hosts = getAllDatasFromTable('glpi_computervirtualmachines', $where);

         if (!empty($hosts)) {
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th colspan='2'>".$LANG['computers'][65]."</th></tr>";

            echo "<tr><th>".$LANG['common'][16]."</th>";
            echo "<th>".$LANG['entity'][0]."</th>";
            echo "</tr>";

            $computer = new Computer();
            foreach ($hosts as $host) {

               echo "<tr class='tab_bg_2'>";
               echo "<td>";
               if ($computer->can($host['computers_id'],'r')) {
                  echo "<a href='computer.form.php?id=".$computer->fields['id']."'>";
                  echo $computer->fields['name']."</a>";
                  $tooltip = $LANG['common'][16]."&nbsp;: ".$computer->fields['name'];
                  $tooltip.= "<br>".$LANG['common'][19]."&nbsp;: <br>".$computer->fields['serial'];
                  $tooltip.= "<br>".$computer->fields['comment'];
                  echo "&nbsp; ".showToolTip($tooltip, array('display' => false));

               } else {
                  echo $computer->fields['name'];
               }
               echo "</td>";
               echo "<td>";
               echo Dropdown::getDropdownName('glpi_entities', $computer->fields['entities_id']);
               echo "</td></tr>";

            }

            echo "</table>";
         }
      }
      echo "</div>";
      if (!empty($hosts)) {
         echo "<br>";
      }

   }


   /**
    * Print the computers disks
    *
    * @param $comp Computer
    *
    * @return Nothing (call to classes members)
   **/
   static function showForComputer(Computer $comp) {
      global $DB, $LANG;

      $ID = $comp->fields['id'];

      if (!$comp->getFromDB($ID) || !$comp->can($ID, "r")) {
         return false;
      }
      $canedit = $comp->can($ID, "w");

      echo "<div class='spaced center'>";

      $virtualmachines = getAllDatasFromTable('glpi_computervirtualmachines',
                                              "`computers_id` = '$ID'");

      echo "<table class='tab_cadre_fixe'>";

      if (empty($virtualmachines)) {
         echo "<tr><th>".$LANG['computers'][59]."</th></tr>";
      } else {
         echo "<tr><th colspan='9'>".$LANG['computers'][66]."</th></tr>";

         echo "<tr><th>".$LANG['common'][16]."</th>";
         echo "<th>".$LANG['computers'][62]."</th>";
         echo "<th>".$LANG['computers'][60]."</th>";
         echo "<th>".$LANG['computers'][63]."</th>";
         echo "<th>".$LANG['computers'][58]."</th>";
         echo "<th>".$LANG['computers'][61]."</th>";
         echo "<th>".$LANG['computers'][24]."</th>";
         echo "<th>".$LANG['computers'][64]."</th>";
         echo "</tr>";

         initNavigateListItems('ComputerVirtualMachine',
                               $LANG['help'][25]." = ". (empty($comp->fields['name'])
                                                         ? "($ID)" : $comp->fields['name']));

         $vm = new self();
         foreach ($virtualmachines as $virtualmachine) {
            $href = "<a href='computervirtualmachine.form.php?id=".$virtualmachine['id']."'>";
            echo "<tr class='tab_bg_2'>";
            $vm->fields = $virtualmachines;
            echo "<td>$href".$virtualmachine['name']."</a></td>";
            echo "<td>";
            echo Dropdown::getDropdownName('glpi_virtualmachinetypes',
                                           $virtualmachine['virtualmachinetypes_id']);
            echo "</td>";
            echo "<td>";
            echo Dropdown::getDropdownName('glpi_virtualmachinesystems',
                                           $virtualmachine['virtualmachinesystems_id']);
            echo "</td>";
            echo "<td>";
            echo Dropdown::getDropdownName('glpi_virtualmachinestates',
                                           $virtualmachine['virtualmachinestates_id']);
            echo "</td>";
            echo "<td>$href".$virtualmachine['uuid']."</a></td>";
            echo "<td>".$virtualmachine['vcpu']."</td>";
            echo "<td>".$virtualmachine['ram']."</td>";
            echo "<td>";
            if ($link_computer = self::findVirtualMachine($virtualmachine)) {
               $computer = new Computer();
               if ($computer->can($link_computer,'r')) {
                  $url = "<a href='computer.form.php?id=".$link_computer."'>";
                  $url.= $computer->fields["name"]."</a>";

                  $tooltip = $LANG['common'][16]."&nbsp;: ".$computer->fields['name'];
                  $tooltip.= "<br>".$LANG['common'][19]."&nbsp;: ";
                  $tooltip.= "<br>".$computer->fields['serial'];
                  $tooltip.= "<br>".$computer->fields['comment'];
                  $url .= "&nbsp; ".showToolTip($tooltip, array('display' => false));
               } else {
                  $url = $computer->fields['name'];
               }
               echo $url;
            }
            echo "</td>";
            echo "</tr>";
            addToNavigateListItems('ComputerVirtualMachine', $virtualmachine['id']);

         }
      }

      if ($canedit) {
         echo "<tr class='tab_bg_2'><th colspan='8'>";
         echo "<a href='computervirtualmachine.form.php?computers_id=$ID&amp'>".
                $LANG['computers'][55]."</a></th></tr>";
      }

      echo "</table>";
      echo "</div>";
   }


   /**
    * Get correct uuid sql search for virtualmachines
    *
    * @param $uuid the uuid give
    *
    * @return the restrict which contains uuid, uuid with first block flipped,
    * uuid with 3 first block flipped
   **/
   static function getUUIDRestrictRequest($uuid) {

      //More infos about uuid, please see wikipedia : 
      //http://en.wikipedia.org/wiki/Universally_unique_identifier
      //Some uuid are not conform, so preprocessing is necessary
      //A good uuid likes lik : 550e8400-e29b-41d4-a716-446655440000
      
      //Case one : for example some uuid are like that : 
      //56 4d 77 d0 6b ef 3d da-4d 67 5c 80 a9 52 e2 c9
      $pattern  = "/([\w]{2})\ ([\w]{2})\ ([\w]{2})\ ([\w]{2})\ ";
      $pattern .= "([\w]{2})\ ([\w]{2})\ ([\w]{2})\ ([\w]{2})-";
      $pattern .= "([\w]{2})\ ([\w]{2})\ ([\w]{2})\ ([\w]{2})\ ";
      $pattern .= "([\w]{2})\ ([\w]{2})\ ([\w]{2})\ ([\w]{2})/";
      if (preg_match($pattern, $uuid)) {
         $uuid = preg_replace($pattern, "$1$2$3$4-$5$6-$7$8-$9$10-$11$12$13$14$15$16", $uuid);
      }
      
      //Case two : why this code ? Because some dmidecode < 2.10 is buggy. 
      //On unix is flips first block of uuid and on windows flips 3 first blocks...
      $in      = " IN ('".strtolower($uuid)."'";
      $regexes = array("/([\w]{2})([\w]{2})([\w]{2})([\w]{2})(.*)/" => "$4$3$2$1$5",
                       "/([\w]{2})([\w]{2})([\w]{2})([\w]{2})-([\w]{2})([\w]{2})-([\w]{2})([\w]{2})(.*)/"
                                                                    => "$4$3$2$1-$6$5-$8$7$9");
      foreach ($regexes as $pattern => $replace) {
         $reverse_uuid = preg_replace($pattern, $replace, $uuid);
         if ($reverse_uuid) {
            $in .= " ,'".strtolower($reverse_uuid)."'";
         }
      }
      $in.= ")";

      return $in;
   }


   /**
    * Find a virtual machine by uuid
    *
    * @param fields virtualmachine fields
    *
    * @return the ID of the computer that have this uuid or false otherwise
   **/
   static function findVirtualMachine($fields=array()) {
      global $DB;

      $query = "SELECT `id`
                FROM `glpi_computers`
                WHERE `id` NOT IN ('".$fields['id']."')
                      AND LOWER(`uuid`) ".self::getUUIDRestrictRequest($fields['uuid']);
      $result = $DB->query($query);

      //Virtual machine found, return ID
      if ($DB->numrows($result)) {
         return $DB->result($result,0,'id');
      }

      return false;
   }

}

?>
