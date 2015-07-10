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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * RegisteredID class
 * @since version 0.85
**/
class RegisteredID  extends CommonDBChild {

   // From CommonDBTM
   public $auto_message_on_action = false;

   // From CommonDBChild
   static public $itemtype        = 'itemtype';
   static public $items_id        = 'items_id';
   public $dohistory              = true;


   static function getRegisteredIDTypes() {

      return array('PCI' => __('PCI'),
                   'USB' => __('USB'));
   }


   static function getTypeName($nb=0) {
      return _n('Registered ID (issued by PCI-SIG)', 'Registered IDs (issued by PCI-SIG)', $nb);
   }


   /**
    * @param $field_name
    * @param $child_count_js_var
    *
    * @return string
   **/
   static function getJSCodeToAddForItemChild($field_name, $child_count_js_var) {

      $result  ="<select name=\'" . $field_name . "_type[-'+$child_count_js_var+']\'>";
      $result .="<option value=\'\'>".Dropdown::EMPTY_VALUE."</option>";
      foreach (self::getRegisteredIDTypes() as $name => $label) {
         $result .="<option value=\'$name\'>$label</option>";
      }
      $result .="</select> : ";
      $result .= "<input type=\'text\' size=\'30\' ". "name=\'" . $field_name .
                "[-'+$child_count_js_var+']\'>";
      return $result;
   }


   /**
    * @see CommonDBChild::showChildForItemForm()
   **/
   function showChildForItemForm($canedit, $field_name, $id) {

      if ($this->isNewID($this->getID())) {
         $value = '';
      } else {
         $value = $this->getName();
      }
      $main_field        = $field_name."[$id]";
      $type_field        = $field_name."_type[$id]";
      $registeredIDTypes = self::getRegisteredIDTypes();

      if ($canedit) {
         echo "<select name='$type_field'>";
         echo "<option value=''>".Dropdown::EMPTY_VALUE."</option>";
         foreach ($registeredIDTypes as $name => $label) {
            echo "<option value='$name'";
            if ($this->fields['device_type'] == $name) {
               echo " selected";
            }
            echo ">$label</option>";
         }
         echo "</select> : <input type='text' size='30' name='$main_field' value='$value'>\n";
      } else {
         echo "<input type='hidden' name='$main_field' value='$value'>";
         if (!empty($this->fields['device_type'])) {
            printf(__('%1$s: %2$s'), $registeredIDTypes[$this->fields['device_type']],
                   $value);
         } else {
            echo $value;
         }
      }
   }

}
?>