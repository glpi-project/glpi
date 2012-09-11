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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Disk class
class ComputerDisk extends CommonDBChild {

   // From CommonDBChild
   static public $itemtype = 'Computer';
   static public $items_id = 'computers_id';
   public $dohistory       = true;


   static function getTypeName($nb=0) {
      return _n('Volume', 'Volumes', $nb);
   }


   static function canCreate() {
      return Session::haveRight('computer', 'w');
   }


   static function canView() {
      return Session::haveRight('computer', 'r');
   }


   /**
    * @see inc/CommonDBChild::prepareInputForAdd()
   **/
   function prepareInputForAdd($input) {

      // Not attached to computer -> not added
      if (!isset($input['computers_id']) || ($input['computers_id'] <= 0)) {
         return false;
      }

      if (!isset($input['entities_id'])) {
         $input['entities_id'] = parent::getItemEntity('Computer', $input['computers_id']);
      }

      return $input;
   }


   function post_getEmpty() {

      $this->fields["totalsize"] = '0';
      $this->fields["freesize"]  = '0';
   }


   /**
    * @see inc/CommonGLPI::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      // can exists for template
      if (($item->getType() == 'Computer')
          && Session::haveRight("computer","r")) {

         if ($_SESSION['glpishow_count_on_tabs']) {
            return self::createTabEntry(self::getTypeName(2),
                                        countElementsInTable('glpi_computerdisks',
                                                             "computers_id = '".$item->getID()."'"));
         }
         return self::getTypeName(2);
      }
      return '';
   }


   /**
    * @param $item            CommonGLPI object
    * @param $tabnum          (default 1)
    * @param $withtemplate    (default 0)
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      self::showForComputer($item, $withtemplate);
      return true;
   }


   /**
    * Duplicate all disks from a computer template to his clone
    *
    * @since version 0.84
    *
    * @param $oldid
    * @param $newid
   **/
   static function cloneComputer ($oldid, $newid) {
      global $DB;

      $query  = "SELECT *
                 FROM `glpi_computerdisks`
                 WHERE `computers_id` = '$oldid'";
      foreach ($DB->request($query) as $data) {
         $cd                   = new self();
         unset($data['id']);
         $data['computers_id'] = $newid;
         $data                 = Toolbox::addslashes_deep($data);
         $cd->add($data);
      }
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

      $computers_id = -1;
      if (isset($options['computers_id'])) {
        $computers_id = $options['computers_id'];
      }

      if (!Session::haveRight("computer", "w")) {
        return false;
      }

      $comp = new Computer();

      if ($ID > 0) {
         $this->check($ID,'r');
         $comp->getFromDB($this->fields['computers_id']);
      } else {
         $comp->getFromDB($computers_id);
         // Create item
         $input                  = array('entities_id'  => $comp->getEntityID(),
                                         'computers_id' => $computers_id);
         $options['entities_id'] = $comp->getEntityID();

         $this->check(-1, 'w', $input);
      }

      $this->showTabs($options);
      $this->showFormHeader($options);

      if ($ID > 0) {
        $computers_id = $this->fields["computers_id"];
      } else {
         echo "<input type='hidden' name='computers_id' value='$computers_id'>";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Computer')."</td>";
      echo "<td colspan='3'>".$comp->getLink()."</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td><td>".__('Partition')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "device");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Mount point')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "mountpoint");
      echo "</td><td>".__('File system')."</td>";
      echo "<td>";
      FileSystem::dropdown(array('value' => $this->fields["filesystems_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Global size')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "totalsize");
      echo "&nbsp;".__('Mio')."</td>";

      echo "<td>".__('Free size')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "freesize");
      echo "&nbsp;".__('Mio')."</td></tr>";

      $this->showFormButtons($options);
      $this->addDivForTabs();

      return true;

   }


   /**
    * Print the computers disks
    *
    * @param $comp                  Computer object
    * @param $withtemplate boolean  Template or basic item (default '')
    *
    * @return Nothing (call to classes members)
   **/
   static function showForComputer(Computer $comp, $withtemplate='') {
      global $DB;

      $ID = $comp->fields['id'];

      if (!$comp->getFromDB($ID)
          || !$comp->can($ID, "r")) {
         return false;
      }
      $canedit = $comp->can($ID, "w");

      echo "<div class='center'>";

      $query = "SELECT `glpi_filesystems`.`name` AS fsname,
                       `glpi_computerdisks`.*
                FROM `glpi_computerdisks`
                LEFT JOIN `glpi_filesystems`
                          ON (`glpi_computerdisks`.`filesystems_id` = `glpi_filesystems`.`id`)
                WHERE (`computers_id` = '$ID')";

      if ($result = $DB->query($query)) {
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='7'>".self::getTypeName($DB->numrows($result))."</th></tr>";

         if ($DB->numrows($result)) {
            echo "<tr><th>".__('Name')."</th>";
            echo "<th>".__('Partition')."</th>";
            echo "<th>".__('Mount point')."</th>";
            echo "<th>".__('File system')."</th>";
            echo "<th>".__('Global size')."</th>";
            echo "<th>".__('Free size')."</th>";
            echo "<th>".__('Free percentage')."</th>";
            echo "</tr>";

         Session::initNavigateListItems(__CLASS__,
                              //TRANS : %1$s is the itemtype name,
                              //        %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'),
                                                $comp->getTypeName(1), $comp->getName()));

            while ($data = $DB->fetch_assoc($result)) {
               echo "<tr class='tab_bg_2'>";
               $name = (empty($data['name'])? sprintf(__('%1$s (%2$s)'),
                                                      $data['name'], $data['id'])
                                            : $data['name']);
               if ($canedit) {
                  echo "<td><a href='computerdisk.form.php?id=".$data['id']."'>".$name."</a></td>";
               } else {
                  echo "<td>".$name."</td>";
               }
               echo "<td>".$data['device']."</td>";
               echo "<td>".$data['mountpoint']."</td>";
               echo "<td>".$data['fsname']."</td>";
               //TRANS: %s is a size
               $tmp = sprintf(__('%s Mio'), Html::formatNumber($data['totalsize'], false, 0));
               echo "<td class='right'>$tmp<span class='small_space'></span></td>";
               $tmp = sprintf(__('%s Mio'), Html::formatNumber($data['freesize'], false, 0));
               echo "<td class='right'>$tmp<span class='small_space'></span></td>";
               echo "<td>";
               $percent = 0;
               if ($data['totalsize'] > 0) {
                  $percent = round(100*$data['freesize']/$data['totalsize']);
               }
               Html::displayProgressBar('100', $percent, array('simple'       => true,
                                                               'forcepadding' => false));
               echo "</td>";
               echo "</tr>";
               Session::addToNavigateListItems(__CLASS__, $data['id']);
            }

         } else {
            echo "<tr><th colspan='7'>".__('No item found')."</th></tr>";
         }

         if ($canedit
             && !(!empty($withtemplate) && ($withtemplate == 2))) {
            echo "<tr><td colspan='7' class='center'>";
            echo "<a class='vsubmit' href='computerdisk.form.php?computers_id=$ID&amp;withtemplate=".
                   $withtemplate."'>".__('Add a volume')."</a></td></tr>";
         }
         echo "</table>";
      }
      echo "</div><br>";
   }

}
?>
