<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Disk Class
**/
class Item_Disk extends CommonDBChild {

   // From CommonDBChild
   static public $itemtype = 'itemtype';
   static public $items_id = 'items_id';
   public $dohistory       = true;


   static function getTypeName($nb = 0) {
      return _n('Volume', 'Volumes', $nb);
   }

   function post_getEmpty() {

      $this->fields["totalsize"] = '0';
      $this->fields["freesize"]  = '0';
   }


   /**
    * @see CommonGLPI::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      // can exists for template
      if ($item::canView()) {
         $nb = 0;
         if ($_SESSION['glpishow_count_on_tabs']) {
            $nb = countElementsInTable(
               self::getTable(), [
                  'items_id'     => $item->getID(),
                  'itemtype'     => $item->getType(),
                  'is_deleted'   => 0
               ]);
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
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      self::showForItem($item, $withtemplate);
      return true;
   }


   /**
    * @see CommonGLPI::defineTabs()
    *
    * @since 0.85
   **/
   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   /**
    * Duplicate all disks from an item template to his clone
    *
    * @since 0.84
    *
    * @param string  $type  Item type
    * @param integer $oldid Old ID
    * @param integer $newid New id
    *
    * @return void
   **/
   static function cloneItem($type, $oldid, $newid) {
      global $DB;

      $iterator = $DB->request([
         'FROM'   => self::getTable(),
         'WHERE'  => [
            'itemtype'  => $itemtype,
            'items_id'  => $oldid
         ]
      ]);
      while ($data = $iterator->next()) {
         $cd                  = new self();
         unset($data['id']);
         $data['items_id']    = $newid;
         $data                = Toolbox::addslashes_deep($data);
         $cd->add($data);
      }
   }


   /**
    * Print the version form
    *
    * @param $ID        integer ID of the item
    * @param $options   array
    *     - target for the Form
    *     - itemtype type of the item for add process
    *     - items_id ID of the item for add process
    *
    * @return true if displayed  false if item not found or not right to display
   **/
   function showForm($ID, $options = []) {
      global $CFG_GLPI;

      $itemtype = null;
      if (isset($options['itemtype']) && !empty($options['itemtype'])) {
         $itemtype = $options['itemtype'];
      } else if (isset($this->fields['itemtype']) && !empty($this->fields['itemtype'])) {
         $itemtype = $this->fields['itemtype'];
      } else {
         throw new \RuntimeException('Unable to retrieve itemtype');
      }

      if (!Session::haveRight($itemtype::$rightname, READ)) {
         return false;
      }

      $item = new $itemtype();
      if ($ID > 0) {
         $this->check($ID, READ);
         $item->getFromDB($this->fields['items_id']);
      } else {
         $this->check(-1, CREATE, $options);
         $item->getFromDB($options['items_id']);
      }

      $this->showFormHeader($options);

      if ($this->isNewID($ID)) {
         echo "<input type='hidden' name='items_id' value='".$options['items_id']."'>";
         echo "<input type='hidden' name='itemtype' value='".$options['itemtype']."'>";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Item')."</td>";
      echo "<td>".$item->getLink()."</td>";
      if (Plugin::haveImport()) {
         echo "<td>".__('Automatic inventory')."</td>";
         echo "<td>";
         if ($ID && $this->fields['is_dynamic']) {
            Plugin::doHook("autoinventory_information", $this);
         } else {
            echo __('No');
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
      FileSystem::dropdown(['value' => $this->fields["filesystems_id"]]);
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

      $itemtype = $this->fields['itemtype'];
      $options['canedit'] = Session::haveRight($itemtype::$rightname, UPDATE);
      $this->showFormButtons($options);

      return true;

   }


   /**
    * Print the disks
    *
    * @param $item                  Item object
    * @param $withtemplate boolean  Template or basic item (default 0)
    *
    * @return Nothing (call to classes members)
   **/
   static function showForItem(CommonDBTM $item, $withtemplate = 0) {
      global $DB;

      $ID = $item->fields['id'];
      $itemtype = $item->getType();

      if (!$item->getFromDB($ID)
          || !$item->can($ID, READ)) {
         return false;
      }
      $canedit = $item->canEdit($ID);

      if ($canedit
          && !(!empty($withtemplate) && ($withtemplate == 2))) {
         echo "<div class='center firstbloc'>".
               "<a class='vsubmit' href='".Item_Disk::getFormURL()."?itemtype=$itemtype&items_id=$ID&amp;withtemplate=".
                  $withtemplate."'>";
         echo __('Add a volume');
         echo "</a></div>\n";
      }

      echo "<div class='center'>";

      $query = "SELECT `glpi_filesystems`.`name` AS fsname,
                       `glpi_items_disks`.*
                FROM `glpi_items_disks`
                LEFT JOIN `glpi_filesystems`
                          ON (`glpi_items_disks`.`filesystems_id` = `glpi_filesystems`.`id`)
                WHERE `items_id` = '$ID'
                      AND `itemtype` = '$itemtype'
                      AND `is_deleted` = 0";

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
                                                   $item::getTypeName(1), $item->getName()));

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
               Html::displayProgressBar('100', $percent, ['simple'       => true,
                                                               'forcepadding' => false]);
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
