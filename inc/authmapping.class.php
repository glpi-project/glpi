<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

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

/**
 *  Class used to manage Auth LDAP config
**/
class AuthMapping extends CommonDBTM {

   // From CommonDBTM
   public $dohistory = true;

   static $rightname = 'config';


   static function getTypeName($nb=0) {
      return _n('Field mapping', 'Fields mapping', $nb);
   }


   static function canCreate() {
      return static::canUpdate();
   }


   /**
    * @since version 0.85
   **/
   static function canPurge() {
      return static::canUpdate();
   }



   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate) {
         if ($_SESSION['glpishow_count_on_tabs']) {
            return self::createTabEntry(self::getTypeName(1));
         }
         return self::getTypeName(1);
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      $authmapping = new AuthMapping();
      $authmapping->showForm($item);
      $authmapping->showForItem($item);
      return true;
   }



   function showForm($item) {

      if (!Config::canUpdate()) {
         return false;
      }
      $this->getEmpty();

      $options = array(
         'colspan' => 1
      );
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Remote field'). "</td>";
      echo "<td>";
      echo Html::hidden('itemtype', array('value' => $item->getType()));
      echo Html::hidden('items_id', array('value' => $item->getID()));
      echo "<input type='text' name='remotefield' size='80' value=\"".$this->fields["remotefield"]."\">";
      echo "</td>";
      echo "</tr>";

      echo "<td>" . __('User field'). "</td>";
      echo "<td>";
      $elements = $this->get_user_fields();
      Dropdown::showFromArray('userfield', $elements);
      echo "</td>";
      echo "</tr>";

      $this->showFormButtons($options);
   }



   function showForItem($item) {
      global $DB;

      $query = "SELECT * FROM `glpi_authmappings`"
              . " WHERE `itemtype`='".$item->getType()."'"
              . "  AND `items_id`='".$item->getID()."'";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_2'>";
      echo "<th>" . __('Remote field') . "</th>";
      echo "<th>" . __('User field') . "</th>";
      echo "</tr>";

      $elements = $this->get_user_fields();

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            while ($data= $DB->fetch_assoc($result)) {
               echo "<tr class='tab_bg_2'>";
               echo "<td>" . $data['remotefield'] . "</td>";
               echo "<td>" . $elements[$data['userfield']] . "</td>";
               echo "</tr>";
            }
         }
      }
      echo "</table>";
   }


   function get_user_fields() {
       return array(
         'picture' => __('Picture'),
         'realname' => __('Surname'),
         'firstname' => __('First name'),
         'email' => _n('Email','Emails', 1),
         'phone' => __('Phone'),
         'mobile' => __('Mobile phone'),
         'usercategories_id' => __('Category'),
         'phone2' => __('Phone 2'),
         'comment' => __('Comments'),
         'registration_number' => __('Administrative number'),
         'usertitles_id' => _x('person','Title'),
         'locations_id' => __('Location'),
      );

   }
}
?>
