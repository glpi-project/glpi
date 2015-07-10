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

/// Class Manufacturer
/// @todo study if we should integrate getHTMLTableHeader and getHTMLTableCellsForItem ...
class Manufacturer extends CommonDropdown {

   var $can_be_translated = false;


   static function getTypeName($nb=0) {
      return _n('Manufacturer', 'Manufacturers', $nb);
   }


   /**
    * @since version 0.85
    * @see CommonDropdown::displaySpecificTypeField()
   **/
   function displaySpecificTypeField($ID, $field=array()) {

      switch ($field['type']) {
         case 'registeredIDChooser':
            RegisteredID::showChildsForItemForm($this, '_registeredID');
            break;
      }
   }


   /**
    * @since version 0.85
    * @see CommonDropdown::getAdditionalFields()
   **/
   function getAdditionalFields() {

      return array(array('name'  => 'none',
                         'label' => RegisteredID::getTypeName(Session::getPluralNumber()).
                                       RegisteredID::showAddChildButtonForItemForm($this,
                                                                                   '_registeredID',
                                                                                   NULL, false),
                         'type'  => 'registeredIDChooser'));
   }


   /**
    * @since version 0.85
   **/
   function post_workOnItem() {

      if ((isset($this->input['_registeredID']))
          && (is_array($this->input['_registeredID']))) {

         $input = array('itemtype' => $this->getType(),
                        'items_id' => $this->getID());

         foreach ($this->input['_registeredID'] as $id => $registered_id) {
            $id_object     = new RegisteredID();
            $input['name'] = $registered_id;

            if (isset($this->input['_registeredID_type'][$id])) {
               $input['device_type'] = $this->input['_registeredID_type'][$id];
            } else {
               $input['device_type'] = '';
            }
            //$input['device_type'] = ;
            if ($id < 0) {
               if (!empty($registered_id)) {
                  $id_object->add($input);
               }
            } else {
               if (!empty($registered_id)) {
                  $input['id'] = $id;
                  $id_object->update($input);
                  unset($input['id']);
               } else {
                  $id_object->delete(array('id' => $id));
               }
            }
         }
         unset($this->input['_registeredID']);
      }
   }


   /**
    * @since version 0.85
    * @see CommonDBTM::post_addItem()
   **/
   function post_addItem() {

      $this->post_workOnItem();
      parent::post_addItem();
   }


   /**
    * @since version 0.85
    * @see CommonDBTM::post_updateItem()
   **/
   function post_updateItem($history=1) {

      $this->post_workOnItem();
      parent::post_updateItem($history);
   }


   /**
    * @param $old_name Old name (need to be addslashes)
    *
    * @return new addslashes name
   **/
   static function processName($old_name) {

      if ($old_name == null) {
         return $old_name;
      }

      $rulecollection = new RuleDictionnaryManufacturerCollection();
      $output         = array();
      $output         = $rulecollection->processAllRules(array("name" => stripslashes($old_name)),
                                                         $output, array());
      if (isset($output["name"])) {
         return $output["name"];
      }
      return $old_name;
   }


   function cleanDBonPurge() {
      // Rules use manufacturer intread of manufacturers_id
      Rule::cleanForItemAction($this, 'manufacturer');
   }


   /**
    * @since version 0.84
    *
    * @param $itemtype
    * @param $base                  HTMLTableBase object
    * @param $super                 HTMLTableSuperHeader object (default NULL)
    * @param $father                HTMLTableHeader object (default NULL)
    * @param $options      array
   **/
   static function getHTMLTableHeader($itemtype, HTMLTableBase $base,
                                      HTMLTableSuperHeader $super=NULL,
                                      HTMLTableHeader $father=NULL, array $options=array()) {

      $column_name = __CLASS__;

      if (isset($options['dont_display'][$column_name])) {
         return;
      }

      $base->addHeader($column_name, __('Manufacturer'), $super, $father);
   }


   /**
    * @since version 0.84
    *
    * @param $row                HTMLTableRow object (default NULL)
    * @param $item               CommonDBTM object (default NULL)
    * @param $father             HTMLTableCell object (default NULL)
    * @param $options   array
   **/
   static function getHTMLTableCellsForItem(HTMLTableRow $row=NULL, CommonDBTM $item=NULL,
                                            HTMLTableCell $father=NULL, array $options = array()) {

      $column_name = __CLASS__;

      if (isset($options['dont_display'][$column_name])) {
         return;
      }

      if (!empty($item->fields["manufacturers_id"])) {
         $row->addCell($row->getHeaderByName($column_name),
                       Dropdown::getDropdownName("glpi_manufacturers",
                                                 $item->fields["manufacturers_id"]),
                       $father);
      }
   }

}
?>
