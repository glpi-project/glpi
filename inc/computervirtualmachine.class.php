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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Disk class
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

      $this->fields["ram"] = '0';
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
      echo "</td><td colspan='2'></tr>";

      $this->showFormButtons($options);
      $this->addDivForTabs();

      return true;

   }


   function defineTabs($options=array()) {
      global $LANG;

      $ong[1] = $LANG['title'][26];

      return $ong;
   }


   static function showForVirtualMachine(Computer $comp, $withtemplate='') {
      global $DB, $LANG;

      $ID = $comp->fields['id'];

      if (!$comp->getFromDB($ID) || ! $comp->can($ID, "r")) {
         return false;
      }
      $canedit = $comp->can($ID, "w");

      echo "<div class='center'>";

      if (isset($comp->fields['uuid']) && $comp->fields['uuid'] != '') {
         $where = "`uuid`='".$comp->fields['uuid']."'";
         $hosts = getAllDatasFromTable('glpi_computervirtualmachines',$where);
         if (!empty($hosts)) {

            echo "<table class='tab_cadre_fixe'>";

            echo "<tr><th colspan='2'>";
            echo $LANG['computers'][65];
            echo "</th></tr>";

            echo "<tr><th>".$LANG['common'][16]."</th>";
            echo "<th>".$LANG['entity'][0]."</th>";
            echo "</th></tr>";
            
            $computer = new Computer();
            foreach ($hosts as $host) {
               $href = "<a href='computer.form.php?id=".$host['id']."'>";
               echo "<tr class='tab_bg_2'>";
               echo "<td>"; 
               if ($computer->can($host['computers_id'],'r')) {
                  echo "<a href='computer.form.php?id=".$computer->fields['id']."'>";
                  echo $computer->fields['name']."</a>";
               } else {
                  echo $computer->fields['name'];
               }
               echo "</td>";
               echo "<td>"; 
               echo Dropdown::getDropdownName('glpi_entities',$computer->fields['entities_id']);
               echo "</td>";
            }
         }

         echo "</table>";
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
    * @param $withtemplate=''  boolean : Template or basic item.
    *
    * @return Nothing (call to classes members)
   **/
   static function showForComputer(Computer $comp, $withtemplate='') {
      global $DB, $LANG;

      $ID = $comp->fields['id'];

      if (!$comp->getFromDB($ID) || ! $comp->can($ID, "r")) {
         return false;
      }
      $canedit = $comp->can($ID, "w");

      echo "<div class='center'>";

      $virtualmachines = getAllDatasFromTable('glpi_computervirtualmachines',
                                              "`computers_id` = '$ID'");
      if (empty($virtualmachines)) {
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>";
         echo $LANG['computers'][59];
         echo "</th></tr>";
      } else {
         echo "<table class='tab_cadre_fixe'>";

        echo "<tr><th colspan='9'>";
        echo $LANG['computers'][66];
        echo "</th></tr>";

         echo "<tr><th>".$LANG['common'][16]."</th>";
         echo "<th>".$LANG['computers'][62]."</th>";
         echo "<th>".$LANG['computers'][60]."</th>";
         echo "<th>".$LANG['computers'][63]."</th>";
         echo "<th>".$LANG['computers'][58]."</th>";
         echo "<th>".$LANG['computers'][61]."</th>";
         echo "<th>".$LANG['computers'][24]."</th>";
         echo "<th>".$LANG['computers'][64]."</th>";
         echo "</tr>";
         echo "</th></tr>";
         
         $vm = new ComputerVirtualMachine();
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
               } else {
                  $url = $this->fields['name'];
               }
               echo $url;
            }
            echo "</td>";
            echo "</tr>";
         }
      }

      if ($canedit &&!(!empty($withtemplate) && $withtemplate == 2)) {
         echo "<tr class='tab_bg_2'><th colspan='8'>";
         echo "<a href='computervirtualmachine.form.php?computers_id=$ID&amp;withtemplate=".
                $withtemplate."'>".$LANG['computers'][55]."</a></th></tr>";
      }

      echo "</table>";
      echo "</div><br>";
   }

   static function findVirtualMachine($fields = array()) {
      global $DB;
      $query = "SELECT `id` FROM `glpi_computers` 
                WHERE id NOT IN ('".$fields['id']."')
                   AND `uuid`='".$fields['uuid']."'";
      $result = $DB->query($query);
      if ($DB->numrows($result)) {
         return $DB->result($result,0,'id');
      } else {
         return false;
      }
   }
}

?>
