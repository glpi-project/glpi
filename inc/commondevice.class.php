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
 * CommonDevice Class
 * for Device*class
*/
abstract class CommonDevice extends CommonDropdown {

   static $rightname = 'device';

   var $can_be_translated = false;


   static function canView() {
      return Session::haveRightsOr(self::$rightname, array(CREATE, UPDATE, PURGE));
   }


   static function getTypeName($nb=0) {
      return _n('Component', 'Components', $nb);
   }


   /**
    * Get all the kind of devices available inside the system.
    *
    * @since version 0.85
    *
    * @return array of the types of CommonDevice available
   **/
   static function getDeviceTypes() {
      global $CFG_GLPI;

      return $CFG_GLPI['device_types'];
   }



   /**
    * Get the assiociated item_device associated with this device
    * This method can be override, for instance by the plugin
    *
    * @since version 0.85
    *
    * @return array of the types of CommonDevice available
   **/
   static function getItem_DeviceType() {

      $devicetype = get_called_class();
      if ($plug = isPluginItemType($devicetype)) {
         return 'Plugin'.$plug['plugin'].'Item_'.$plug['class'];
      }
      return "Item_$devicetype";
   }


   /**
    *  @see CommonGLPI::getMenuContent()
    *
    *  @since version 0.85
   **/
   static function getMenuContent() {

      $menu = array();
      if (Session::haveRightsOr('device', array(CREATE, UPDATE, PURGE))) {
         $menu['title'] = static::getTypeName(Session::getPluralNumber());
         $menu['page']  = '/front/device.php';

         $dps = Dropdown::getDeviceItemTypes();

         foreach ($dps as $tab) {
            foreach ($tab as $key => $val) {
               if ($tmp = getItemForItemtype($key)) {
                  $menu['options'][$key]['title']           = $val;
                  $menu['options'][$key]['page']            = $tmp->getSearchURL(false);
                  $menu['options'][$key]['links']['search'] = $tmp->getSearchURL(false);
                  if ($tmp->canCreate()) {
                     $menu['options'][$key]['links']['add'] = $tmp->getFormURL(false);
                  }
               }
            }
         }
      }
      if (count($menu)) {
         return $menu;
      }
      return false;
   }


   /**
    * @since version 0.85
    * @see CommonDropdown::displaySpecificTypeField()
   **/
   function displaySpecificTypeField($ID, $field=array()) {

      switch ($field['type']) {
         case 'registeredIDChooser' :
            RegisteredID::showChildsForItemForm($this, '_registeredID');
            break;
      }
   }


   function getAdditionalFields() {

      return array(array('name'  => 'manufacturers_id',
                         'label' => __('Manufacturer'),
                         'type'  => 'dropdownValue'));
   }

   /**
    * Can I change recursive flag to false
    * check if there is "linked" object in another entity
    *
    * Overloaded from CommonDBTM
    *
    * @since version 0.85
    *
    * @return booleen
   **/
   function canUnrecurs() {
      global $DB;

      $ID = $this->fields['id'];
      if (($ID < 0)
          || !$this->fields['is_recursive']) {
         return true;
      }
      if (!parent::canUnrecurs()) {
         return false;
      }
      $entities = "(".$this->fields['entities_id'];
      foreach (getAncestorsOf("glpi_entities", $this->fields['entities_id']) as $papa) {
         $entities .= ",$papa";
      }
      $entities .= ")";


      // RELATION : device -> item_device -> item
      $linktype  = static::getItem_DeviceType();
      $linktable = getTableForItemType($linktype);

      $sql = "SELECT `itemtype`,
                     GROUP_CONCAT(DISTINCT `items_id`) AS ids
              FROM `$linktable`
              WHERE `$linktable`.`".$this->getForeignKeyField()."` = '$ID'
              GROUP BY `itemtype`";

      foreach ($DB->request($sql) as $data) {
         if (!empty($data["itemtype"])) {
            $itemtable = getTableForItemType($data["itemtype"]);
            if ($item = getItemForItemtype($data["itemtype"])) {
               // For each itemtype which are entity dependant
               if ($item->isEntityAssign()) {
                  if (countElementsInTable($itemtable, "id IN (".$data["ids"].")
                                           AND entities_id NOT IN $entities") > 0) {
                     return false;
                  }
               }
            }
         }
      }
      return true;
   }


   function getSearchOptions() {

      $tab = array();
      $tab['common']           = __('Characteristics');

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'designation';
      $tab[1]['name']          = __('Name');
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['massiveaction'] = false;

      $tab[23]['table']        = 'glpi_manufacturers';
      $tab[23]['field']        = 'name';
      $tab[23]['name']         = __('Manufacturer');
      $tab[23]['datatype']     = 'dropdown';

      $tab[16]['table']        = $this->getTable();
      $tab[16]['field']        = 'comment';
      $tab[16]['name']         = __('Comments');
      $tab[16]['datatype']     = 'text';

      $tab[80]['table']        = 'glpi_entities';
      $tab[80]['field']        = 'completename';
      $tab[80]['name']         = __('Entity');
      $tab[80]['datatype']     = 'dropdown';

      return $tab;
   }


   function title() {

      Dropdown::showItemTypeMenu(_n('Component', 'Components', Session::getPluralNumber()),
                                 Dropdown::getDeviceItemTypes(), $this->getSearchURL());
   }


   function displayHeader() {
      Html::header($this->getTypeName(1), '', "config", "commondevice", get_class($this));
   }


   /**
    * @since version 0.84
    *
    * @see CommonDBTM::getNameField
    *
    * @return string
   **/
   static function getNameField() {
      return 'designation';
   }


   /**
    * @since version 0.84
    * get the HTMLTable Header for the current device according to the type of the item that
    * is requesting
    *
    * @param $itemtype  string   the type of the item
    * @param $base               HTMLTableBase object:the element on which adding the header
    *                            (ie.: HTMLTableMain or HTMLTableGroup)
    * @param $super              HTMLTableSuperHeader object: the super header
    *                            (in case of adding to HTMLTableGroup) (default NULL)
    * @param $father             HTMLTableHeader object: the father of the current headers
    *                            (default NULL)
    * @param $options   array    parameter such as restriction
    *
    * @return nothing (elements added to $base)
   **/
   static function getHTMLTableHeader($itemtype, HTMLTableBase $base,
                                      HTMLTableSuperHeader $super=NULL,
                                      HTMLTableHeader $father=NULL, array $options=array()) {

      $this_type = get_called_class();

      if (isset($options['dont_display'][$this_type])) {
         return $father;
      }

      if (static::canView()) {
         $content = "<a href='".static::getSearchURL()."'>" . static::getTypeName(1) . "</a>";
      } else {
         $content = static::getTypeName(1);
      }

      $linktype = static::getItem_DeviceType();
      if (in_array($itemtype, $linktype::itemAffinity())) {
         $column = $base->addHeader('device', $content, $super, $father);
         $column->setItemType($this_type,
                              isset($options['itemtype_title']) ? $options['itemtype_title'] : '');
      } else {
            $column = $father;
      }

      return $column;

   }


   /**
    * @since version 0.84
    *
    * @warning note the difference between getHTMLTableCellForItem and getHTMLTableCellsForItem
    *
    * @param $row                HTMLTableRow object
    * @param $item               CommonDBTM object (default NULL)
    * @param $father             HTMLTableCell object (default NULL)
    * @param $options   array
   **/
   function getHTMLTableCellForItem(HTMLTableRow $row=NULL, CommonDBTM $item=NULL,
                                    HTMLTableCell $father=NULL, array $options=array()) {

      global $CFG_GLPI;

      $this_type = $this->getType();

      if (isset($options['dont_display'][$this_type])) {
         return $father;
      }

      if (static::canView()) {
         $content = $this->getLink();
      } else {
         $content = $this->getName();
      }

      if ($options['canedit']) {
         $field_name  = 'quantity_'.$this->getType().'_'.$this->getID();
         $content .= "&nbsp;<img title='".__s('Add')."' alt='" . __s('Add')."'
                      onClick=\"".Html::jsShow($field_name)."\"
                      class='pointer' src='".$CFG_GLPI["root_doc"] . "/pics/add_dropdown.png'>";
         $content .= "<span id='$field_name' style='display:none'><br>";
         $content .= __('Add')."&nbsp;";

         $content  = array($content,
                           array('function'   => 'Dropdown::showInteger',
                                 'parameters' => array($field_name, 0, 0, 10)),
                           "</span>");
      }

      $linktype = static::getItem_DeviceType();
      if (in_array($item->getType(), $linktype::itemAffinity())) {
         $cell = $row->addCell($row->getHeaderByName('common', 'device'),
                               $content, $father, $this);
      } else {
         $cell = $father;
      }

      return $cell;

   }


   /**
    * Import a device is not exists
    *
    * @param $input array of datas
    *
    * @return interger ID of existing or new Device
   **/
   function import(array $input) {
      global $DB;

      if (!isset($input['designation']) || empty($input['designation'])) {
         return 0;
      }
      $where      = array();
      $a_criteria = $this->getImportCriteria();
      foreach ($a_criteria as $field => $compare) {
         if (isset($input[$field])) {
            $compare = explode(':', $compare);
            switch ($compare[0]) {
               case 'equal':
                  $where[] = "`".$field."`='".$input[$field]."'";
                  break;

               case 'delta':
                  $where[] = "`".$field."`>'".($input[$field] - $compare[1])."'";
                  $where[] = "`".$field."`<'".($input[$field] + $compare[1])."'";
                  break;
            }
         }
      }

      $query = "SELECT `id`
                FROM `".$this->getTable()."`
                WHERE ".  implode(" AND ", $where);

      $result = $DB->query($query);
      if ($DB->numrows($result) > 0) {
         $line = $DB->fetch_assoc($result);
         return $line['id'];
      }
      return $this->add($input);
   }


   /**
    * Criteria used for import function
    *
    * @since version 0.84
   **/
   function getImportCriteria() {

      return array('designation'      => 'equal',
                   'manufacturers_id' => 'equal');
   }


   /**
    * @see CommonDropdown::defineTabs()
    *
    * @since version 0.85
    */
   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab(static::getItem_DeviceType(), $ong, $options);
      $this->addStandardTab('Document_Item', $ong, $options);

      return $ong;
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

}
?>