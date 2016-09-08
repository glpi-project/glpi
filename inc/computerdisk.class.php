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
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Disk Class
**/
class ComputerDisk extends CommonDBChild {

   // From CommonDBChild
   static public $itemtype = 'Computer';
   static public $items_id = 'computers_id';
   public $dohistory       = true;


   static function getTypeName($nb=0) {
      return _n('Volume', 'Volumes', $nb);
   }

   function post_getEmpty() {

      $this->fields["totalsize"] = '0';
      $this->fields["freesize"]  = '0';
   }


   /**
    * @see CommonGLPI::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      // can exists for template
      if (($item->getType() == 'Computer')
          && Computer::canView()) {
         $nb = 0;
         if ($_SESSION['glpishow_count_on_tabs']) {
            $nb = countElementsInTable('glpi_computerdisks',
                                       "computers_id = '".$item->getID()."' AND `is_deleted`='0'");
         }
         return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
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
    * @see CommonGLPI::defineTabs()
    *
    * @since version 0.85
   **/
   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('Log', $ong, $options);
      
      return $ong;
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

      if (!Session::haveRight("computer", UPDATE)) {
         return false;
      }

      $comp = new Computer();
      if ($ID > 0) {
         $this->check($ID, READ);
         $comp->getFromDB($this->fields['computers_id']);
      } else {
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
          || !$comp->can($ID, READ)) {
         return false;
      }
      $canedit = $comp->canEdit($ID);


      if ($canedit
          && !(!empty($withtemplate) && ($withtemplate == 2))) {
         echo "<div class='center firstbloc'>".
               "<a class='vsubmit' href='computerdisk.form.php?computers_id=$ID&amp;withtemplate=".
                  $withtemplate."'>";
         _e('Add a volume');
         echo "</a></div>\n";
      }

      echo "<div class='center'>";

      $query = "SELECT `glpi_filesystems`.`name` AS fsname,
                       `glpi_computerdisks`.*
                FROM `glpi_computerdisks`
                LEFT JOIN `glpi_filesystems`
                          ON (`glpi_computerdisks`.`filesystems_id` = `glpi_filesystems`.`id`)
                WHERE `computers_id` = '$ID'
                      AND `is_deleted` = '0'";

      if ($result = $DB->query($query)) {
         echo "<table class='tab_cadre_fixehov'>";
         $colspan = 7;
         if (Plugin::haveImport()) {
            $colspan++;
         }
         echo "<tr class='noHover'><th colspan='$colspan'>".self::getTypeName($DB->numrows($result)).
              "</th></tr>";

         if ($DB->numrows($result)) {

            $header = "<tr><th>".__('Name')."</th>";
            if (Plugin::haveImport()) {
               $header .= "<th>".__('Automatic inventory')."</th>";
            }
            $header .= "<th>".__('Partition')."</th>";
            $header .= "<th>".__('Mount point')."</th>";
            $header .= "<th>".__('File system')."</th>";
            $header .= "<th>".__('Global size')."</th>";
            $header .= "<th>".__('Free size')."</th>";
            $header .= "<th>".__('Free percentage')."</th>";
            $header .= "</tr>";
            echo $header;

            Session::initNavigateListItems(__CLASS__,
                              //TRANS : %1$s is the itemtype name,
                              //        %2$s is the name of the item (used for headings of a list)
                                           sprintf(__('%1$s = %2$s'),
                                                   Computer::getTypeName(1), $comp->getName()));

            $disk = new self();
            while ($data = $DB->fetch_assoc($result)) {
               $disk->getFromDB($data['id']);
               echo "<tr class='tab_bg_2'>";
               echo "<td>".$disk->getLink()."</td>";
               if (Plugin::haveImport()) {
                  echo "<td>".Dropdown::getYesNo($data['is_dynamic'])."</td>";
               }
               echo "<td>".$data['device']."</td>";
               echo "<td>".$data['mountpoint']."</td>";
               echo "<td>".$data['fsname']."</td>";
               //TRANS: %s is a size
               $tmp = Toolbox::getSize($data['totalsize'] * 1024 * 1024);
               echo "<td class='right'>$tmp<span class='small_space'></span></td>";
               $tmp = Toolbox::getSize($data['freesize'] * 1024 * 1024);
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
            echo $header;
         } else {
            echo "<tr class='tab_bg_2'><th colspan='$colspan'>".__('No item found')."</th></tr>";
         }

         echo "</table>";
      }
      echo "</div><br>";
   }

}
?>
