<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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
* @brief Virtual machine management
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * ComputerVirtualMachine Class
 *
 * Class to manage virtual machines
**/
class ComputerVirtualMachine extends CommonDBChild {

   // From CommonDBChild
   static public $itemtype = 'Computer';
   static public $items_id = 'computers_id';
   public $dohistory       = true;


   static function getTypeName($nb=0) {
      return _n('Virtual machine', 'Virtual machines', $nb);
   }


   /**
    * @see CommonGLPI::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate
          && ($item->getType() == 'Computer')
          && Computer::canView()) {
         $nb = 0;
         if ($_SESSION['glpishow_count_on_tabs']) {
            $nb = countElementsInTable('glpi_computervirtualmachines',
                                       "computers_id = '".$item->getID()."' AND `is_deleted`='0'");
         }
         return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
      }
      return '';
   }


   /**
    * @see CommonGLPI::defineTabs()
    *
    * @since version 0.85
   **/
   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);

      return $ong;
   }


   /**
    * @param $item         CommonGLPI object
    * @param $tabnum       (default 1)
    * @param $withtemplate (default 0)
   **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      self::showForVirtualMachine($item);
      self::showForComputer($item);
      return true;
   }


   function post_getEmpty() {

      $this->fields["vcpu"] = '0';
      $this->fields["ram"]  = '0';
   }


   /**
    * Print the version form
    *
    * @param $ID        integer ID of the item
    * @param $options   array
    *     - target for the Form
    *     - computers_id ID of the computer for add process
    *
    * @return true if displayed  false if item not found or not right to display
   **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI;

      if (!Session::haveRight("computer", UPDATE)) {
        return false;
      }

      $comp = new Computer();

      if ($ID > 0) {
         $this->check($ID, READ);
         $comp->getFromDB($this->fields['computers_id']);
      } else {
         // Create item
         $this->check(-1, CREATE, $options);
         $comp->getFromDB($options['computers_id']);
      }

      $this->showFormHeader($options);

      if ($this->isNewID($ID)) {
         echo "<input type='hidden' name='computers_id' value='".$options['computers_id']."'>";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Computer')."</td>";
      echo "<td>".$comp->getLink()."</td>";
      if (Plugin::haveImport()) {
         echo "<td>".__('Automatic inventory')."</td>";
         echo "<td>";
         if ($ID && $this->fields['is_dynamic']) {
            Plugin::doHook("autoinventory_information", $this);
         } else {
            _e('No');
         }
         echo "</td>";
      } else {
         echo "<td colspan='2'></td>";
      }
      echo "</tr>\n";


      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td><td rowspan='4'>".__('Comments')."</td>";
      echo "<td rowspan='4'>";
      echo "<textarea cols='45' rows='6' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Virtualization system')."</td>";
      echo "<td>";
      VirtualMachineType::dropdown(array('value' => $this->fields['virtualmachinetypes_id']));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Virtualization model')."</td>";
      echo "<td>";
      VirtualMachineSystem::dropdown(array('value' => $this->fields['virtualmachinesystems_id']));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".__('State of the virtual machine')."</td>";
      echo "<td>";
      VirtualMachineState::dropdown(array('value' => $this->fields['virtualmachinestates_id']));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('UUID')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "uuid");
      echo "</td>";

      echo "<td>".__('Machine')."</td>";
      echo "<td>";
      if ($link_computer = self::findVirtualMachine($this->fields)) {
         $computer = new Computer();
         if ($computer->getFromDB($link_computer)) {
            echo $computer->getLink(array('comments' => true));
         } else {
            echo NOT_AVAILABLE;
         }
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".sprintf(__('%1$s (%2$s)'),__('Memory'),__('Mio'))."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "ram");
      echo "</td>";

      echo "<td>"._x('quantity', 'Processors number')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "vcpu");
      echo "</td></tr>";


      $this->showFormButtons($options);

      return true;
   }


   /**
    * Show hosts for a virtualmachine
    *
    * @param $comp   Computer object that represents the virtual machine
    *
    * @return Nothing (call to classes members)
   **/
   static function showForVirtualMachine(Computer $comp) {
      global $DB;

      $ID = $comp->fields['id'];

      if (!$comp->getFromDB($ID) || !$comp->can($ID, READ)) {
         return false;
      }
      $canedit = $comp->canEdit($ID);

      echo "<div class='center'>";

      if (isset($comp->fields['uuid']) && ($comp->fields['uuid'] != '')) {
         $where = "LOWER(`uuid`)".self::getUUIDRestrictRequest($comp->fields['uuid']);
         $hosts = getAllDatasFromTable('glpi_computervirtualmachines', $where);

         if (!empty($hosts)) {
            echo "<table class='tab_cadre_fixehov'>";
            echo  "<tr class='noHover'><th colspan='2' >".__('List of host machines')."</th></tr>";

            $header = "<tr><th>".__('Name')."</th>";
            $header .= "<th>".__('Entity')."</th>";
            $header .= "</tr>";
            echo $header;

            $computer = new Computer();
            foreach ($hosts as $host) {

               echo "<tr class='tab_bg_2'>";
               echo "<td>";
               if ($computer->can($host['computers_id'], READ)) {
                  echo "<a href='computer.form.php?id=".$computer->fields['id']."'>";
                  echo $computer->fields['name']."</a>";
                  $tooltip = "<table><tr><td>".__('Name')."</td><td>".$computer->fields['name'].
                             '</td></tr>';
                  $tooltip.= "<tr><td>".__('Serial number')."</td><td>".$computer->fields['serial'].
                             '</td></tr>';
                  $tooltip.= "<tr><td>".__('Comments')."</td><td>".$computer->fields['comment'].
                             '</td></tr></table>';
                  echo "&nbsp; ".Html::showToolTip($tooltip, array('display' => false));

               } else {
                  echo $computer->fields['name'];
               }
               echo "</td>";
               echo "<td>";
               echo Dropdown::getDropdownName('glpi_entities', $computer->fields['entities_id']);
               echo "</td></tr>";

            }
            echo $header;
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
    * @param $comp Computer object
    *
    * @return Nothing (call to classes members)
   **/
   static function showForComputer(Computer $comp) {
      global $DB;

      $ID = $comp->fields['id'];

      if (!$comp->getFromDB($ID) || !$comp->can($ID, READ)) {
         return false;
      }
      $canedit = $comp->canEdit($ID);

      if ($canedit) {
         echo "<div class='center firstbloc'>".
                "<a class='vsubmit' href='computervirtualmachine.form.php?computers_id=$ID'>";
         _e('Add a virtual machine');
         echo "</a></div>\n";
      }

      echo "<div class='center'>";

      $virtualmachines = getAllDatasFromTable('glpi_computervirtualmachines',
                                              "`computers_id` = '$ID' AND `is_deleted` = '0'");

      echo "<table class='tab_cadre_fixehov'>";

      Session::initNavigateListItems('ComputerVirtualMachine',
                                     sprintf(__('%1$s = %2$s'), __('Computer'),
                                             (empty($comp->fields['name'])
                                                ? "($ID)" : $comp->fields['name'])));

      if (empty($virtualmachines)) {
         echo "<tr><th>".__('No virtual machine associated with the computer')."</th></tr>";
      } else {
         echo "<tr class='noHover'><th colspan='10'>".__('List of virtual machines')."</th></tr>";

         $header = "<tr><th>".__('Name')."</th>";
         if (Plugin::haveImport()) {
            $header .= "<th>".__('Automatic inventory')."</th>";
         }
         $header .= "<th>".__('Virtualization system')."</th>";
         $header .= "<th>".__('Virtualization model')."</th>";
         $header .= "<th>".__('State of the virtual machine')."</th>";
         $header .= "<th>".__('UUID')."</th>";
         $header .= "<th>"._x('quantity', 'Processors number')."</th>";
         $header .= "<th>".sprintf(__('%1$s (%2$s)'), __('Memory'),__('Mio'))."</th>";
         $header .= "<th>".__('Machine')."</th>";
         $header .= "</tr>";
         echo $header;

         $vm = new self();
         foreach ($virtualmachines as $virtualmachine) {
            $vm->getFromDB($virtualmachine['id']);
            echo "<tr class='tab_bg_2'>";
            echo "<td>".$vm->getLink()."</td>";
            if (Plugin::haveImport()) {
               echo "<td>".Dropdown::getYesNo($vm->isDynamic())."</td>";
            }
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
            echo "<td>".$virtualmachine['uuid']."</td>";
            echo "<td>".$virtualmachine['vcpu']."</td>";
            echo "<td>".$virtualmachine['ram']."</td>";
            echo "<td>";
            if ($link_computer = self::findVirtualMachine($virtualmachine)) {
               $computer = new Computer();
               if ($computer->can($link_computer, READ)) {
                  $url  = "<a href='computer.form.php?id=".$link_computer."'>";
                  $url .= $computer->fields["name"]."</a>";

                  $tooltip = "<table><tr><td>".__('Name')."</td><td>".$computer->fields['name'].
                             '</td></tr>';
                  $tooltip.= "<tr><td>".__('Serial number')."</td><td>".$computer->fields['serial'].
                             '</td></tr>';
                  $tooltip.= "<tr><td>".__('Comments')."</td><td>".$computer->fields['comment'].
                             '</td></tr></table>';

                  $url .= "&nbsp; ".Html::showToolTip($tooltip, array('display' => false));
               } else {
                  $url = $computer->fields['name'];
               }
               echo $url;
            }
            echo "</td>";
            echo "</tr>";
            Session::addToNavigateListItems('ComputerVirtualMachine', $virtualmachine['id']);

         }
         echo $header;
      }



      echo "</table>";
      echo "</div>";
   }


   /**
    * Get correct uuid sql search for virtualmachines
    *
    * @param $uuid the uuid given
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
      $in .= ")";

      return $in;
   }


   /**
    * Find a virtual machine by uuid
    *
    * @param fields array of virtualmachine fields
    *
    * @return the ID of the computer that have this uuid or false otherwise
   **/
   static function findVirtualMachine($fields=array()) {
      global $DB;

      if (!isset($fields['uuid']) || empty($fields['uuid'])) {
         return false;
      }

      $query = "SELECT `id`
                FROM `glpi_computers`
                WHERE LOWER(`uuid`) ".self::getUUIDRestrictRequest($fields['uuid']);
      $result = $DB->query($query);

      //Virtual machine found, return ID
      if ($DB->numrows($result)) {
         return $DB->result($result,0,'id');
      }

      return false;
   }

}
?>
