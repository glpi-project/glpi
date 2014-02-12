<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}


// CommonDevice Class for Device*class
abstract class CommonDevice extends CommonDropdown {


   static function canCreate() {
      return Session::haveRight('device', 'w');
   }


   static function canView() {
      return Session::haveRight('device', 'r');
   }

   function getAdditionalFields() {

      return array(array('name'  => 'manufacturers_id',
                         'label' => __('Manufacturer'),
                         'type'  => 'dropdownValue'));
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

      return $tab;
   }


   function title() {

      Dropdown::showItemTypeMenu(_n('Component', 'Components', 2),
                                 Dropdown::getDeviceItemTypes(), $this->getSearchURL());
   }


   function displayHeader() {
      Html::header($this->getTypeName(1), '', "config", "device", get_class($this));
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

      switch ($itemtype) {
         case 'Computer':
            $column = $base->addHeader('device', $content, $super, $father);
            $column->setItemType($this_type, (isset($options['itemtype_title']) ?
                                              $options['itemtype_title'] : ''));
            break;
         default:
            $column = $father;
            break;
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
                      onClick=\"Ext.get('$field_name').setDisplayed('block')\"
                      class='pointer' src='".$CFG_GLPI["root_doc"] . "/pics/add_dropdown.png'>";
         $content .= "<span id='$field_name' style='display:none'><br>";
         $content .= __('Add')."&nbsp;";

         $content  = array($content,
                           array('function'   => 'Dropdown::showInteger',
                                 'parameters' => array($field_name, 0, 0, 10)),
                           "</span>");
      }

      switch ($item->getType()) {
         case 'Computer':
            $cell = $row->addCell($row->getHeaderByName('common', 'device'),
                                  $content, $father, $this);
            break;
         default:
            $cell = $father;
            break;
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

}
?>