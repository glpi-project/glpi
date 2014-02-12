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

class Dropdown {

   //Empty value displayed in a dropdown
   const EMPTY_VALUE = '-----';

   /**
    * Print out an HTML "<select>" for a dropdown with preselected value
    *
    * @param $itemtype        itemtype used for create dropdown
    * @param $options   array of possible options:
    *    - name                : string / name of the select (default is depending itemtype)
    *    - value               : integer / preselected value (default -1)
    *    - comments            : boolean / is the comments displayed near the dropdown (default true)
    *    - toadd               : array / array of specific values to add at the begining
    *    - entity              : integer or array / restrict to a defined entity or array of entities
    *                                               (default -1 : no restriction)
    *    - entity_sons         : boolean / if entity restrict specified auto select its sons
    *                                      only available if entity is a single value not an array
    *                                      (default false)
    *    - toupdate            : array / Update a specific item on select change on dropdown
    *                                    (need value_fieldname, to_update,
    *                                     url (see Ajax::updateItemOnSelectEvent for information)
    *                                     and may have moreparams)
    *    - used                : array / Already used items ID: not to display in dropdown
    *                                    (default empty)
    *    - on_change           : string / value to transmit to "onChange"
    *    - rand                : integer / already computed rand value
    *    - condition           : string / aditional SQL condition to limit display
    *    - displaywith         : array / array of field to display with request
    *    - emptylabel          : Empty choice's label (default self::EMPTY_VALUE)
    *    - display_emptychoice : Display emptychoice ? (default true)
    *    - display             : boolean / display or get string (default true)
    *    - permit_select_parent : boolean / for tree dropdown permit to see parent items not available by default (default false)
    *
    * @return boolean : false if error and random id if OK
   **/
   static function show($itemtype, $options=array()) {
      global $DB, $CFG_GLPI;

      if ($itemtype && !($item = getItemForItemtype($itemtype))) {
         return false;
      }

      $table = $item->getTable();

      $params['name']        = $item->getForeignKeyField();
      $params['value']       = (($itemtype == 'Entity') ? $_SESSION['glpiactive_entity'] : '');
      $params['comments']    = true;
      $params['entity']      = -1;
      $params['entity_sons'] = false;
      $params['toupdate']    = '';
      $params['used']        = array();
      $params['toadd']       = array();
      $params['on_change']   = '';
      $params['condition']   = '';
      $params['rand']        = mt_rand();
      $params['displaywith'] = array();
      //Parameters about choice 0
      //Empty choice's label
      $params['emptylabel'] = self::EMPTY_VALUE;
      //Display emptychoice ?
      $params['display_emptychoice'] = ($itemtype != 'Entity');
      $params['display']        = true;
      $params['permit_select_parent'] = false;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }
      $output       = '';
      $name         = $params['emptylabel'];
      $comment      = "";
      $limit_length = $_SESSION["glpidropdown_chars_limit"];

      // Check default value for dropdown : need to be a numeric
      if ((strlen($params['value']) == 0) || !is_numeric($params['value'])) {
         $params['value'] = 0;
      }

      if (($params['value'] > 0)
         || (($itemtype == "Entity")
             && ($params['value'] >= 0))) {
         $tmpname = self::getDropdownName($table, $params['value'], 1);

         if ($tmpname["name"] != "&nbsp;") {
            $name    = $tmpname["name"];
            $comment = $tmpname["comment"];

            if (Toolbox::strlen($name) > $_SESSION["glpidropdown_chars_limit"]) {
               if ($item instanceof CommonTreeDropdown) {
                  $pos          = strrpos($name, ">");
                  $limit_length = max(Toolbox::strlen($name) - $pos,
                                      $_SESSION["glpidropdown_chars_limit"]);

                  if (Toolbox::strlen($name) > $limit_length) {
                     $name = "&hellip;".Toolbox::substr($name, -$limit_length);
                  }

               } else {
                  $limit_length = Toolbox::strlen($name);
               }

            } else {
               $limit_length = $_SESSION["glpidropdown_chars_limit"];
            }
         }
      }

      // Manage entity_sons
      if (!($params['entity'] < 0)
          && $params['entity_sons']) {
         if (is_array($params['entity'])) {
            // translation not needed - only for debug
            $output .= "entity_sons options is not available with entity option as array";
         } else {
            $params['entity'] = getSonsOf('glpi_entities',$params['entity']);
         }
      }

      $use_ajax = false;
      if ($CFG_GLPI["use_ajax"]) {
         $nb = 0;

         if ($item->isEntityAssign()) {
            if (!($params['entity'] < 0)) {
               $nb = countElementsInTableForEntity($table, $params['entity'], $params['condition']);
            } else {
               $nb = countElementsInTableForMyEntities($table, $params['condition']);
            }

         } else {
            $nb = countElementsInTable($table, $params['condition']);
         }

         $nb -= count($params['used']);

         if ($nb > $CFG_GLPI["ajax_limit_count"]) {
            $use_ajax = true;
         }
      }

      $param = array('searchText'           => '__VALUE__',
                      'value'               => $params['value'],
                      'itemtype'            => $itemtype,
                      'myname'              => $params['name'],
                      'limit'               => $limit_length,
                      'toadd'               => $params['toadd'],
                      'comment'             => $params['comments'],
                      'rand'                => $params['rand'],
                      'entity_restrict'     => $params['entity'],
                      'update_item'         => $params['toupdate'],
                      'used'                => $params['used'],
                      'on_change'           => $params['on_change'],
                      'condition'           => $params['condition'],
                      'emptylabel'          => $params['emptylabel'],
                      'display_emptychoice' => $params['display_emptychoice'],
                      'displaywith'         => $params['displaywith'],
                      'display'             => false,
                      'permit_select_parent' => $params['permit_select_parent']);
      if ($item->canView()) {
         $param['update_link'] = 1;
      }
      
      $default  = "<select name='".$params['name']."' id='dropdown_".$params['name'].
                    $params['rand']."'>";
      $default .= "<option value='".$params['value']."'>$name</option></select>";
      $output .= Ajax::dropdown($use_ajax, "/ajax/dropdownValue.php", $param, $default,
                                $params['rand'], false);

      // Display comment
      if ($params['comments']) {
         $options_tooltip = array('contentid' => "comment_".$params['name'].$params['rand'],
                                  
                                  'display'   => false);

         if ($item->canView()) {
         
             if($params['value'] && $item->getFromDB($params['value'])
               && $item->canViewItem()) {
               $options_tooltip['link']       = $item->getLinkURL();
            } else {
               $options_tooltip['link']       = $item->getSearchURL();
            }
            $options_tooltip['linkid']     = "comment_link_".$params['name'].$params['rand'];
            $options_tooltip['linktarget'] = '_blank';
         }
         
         $output .= Html::showToolTip($comment,$options_tooltip);

         if (($item instanceof CommonDropdown)
             && $item->canCreate()
             && !isset($_GET['popup'])) {

               $output .= "<img alt='' title=\"".__s('Add')."\" src='".$CFG_GLPI["root_doc"].
                            "/pics/add_dropdown.png' style='cursor:pointer; margin-left:2px;'
                            onClick=\"var w = window.open('".$item->getFormURL()."?popup=1&amp;rand=".
                            $params['rand']."' ,'glpipopup', 'height=400, ".
                            "width=1000, top=100, left=100, scrollbars=yes' );w.focus();\">";
         }
         // Display specific Links
         if ($itemtype == "Supplier") {
            if ($item->getFromDB($params['value'])) {
               $output .= $item->getLinks();
            }
         }

         if (($itemtype == 'ITILCategory')
             && Session::haveRight('knowbase','r')) {

            if ($params['value'] && $item->getFromDB($params['value'])) {
               $output .= '&nbsp;'.$item->getLinks();
            }
         }

      }
      if ($params['display']) {
         echo $output;
         return $params['rand'];
      } else {
         return $output;
      }
   }


   /**
    * Get the value of a dropdown
    *
    * Returns the value of the dropdown from $table with ID $id.
    *
    * @param $table        the dropdown table from witch we want values on the select
    * @param $id           id of the element to get
    * @param $withcomment  give array with name and comment (default 0)
    *
    * @return string the value of the dropdown or &nbsp; if not exists
   **/
   static function getDropdownName($table, $id, $withcomment=0) {
      global $DB, $CFG_GLPI;

      $item = getItemForItemtype(getItemTypeForTable($table));

      if ($item instanceof CommonTreeDropdown) {
         return getTreeValueCompleteName($table,$id,$withcomment);
      }

      $name    = "";
      $comment = "";

      if ($id) {
         $query = "SELECT *
                   FROM `". $table ."`
                   WHERE `id` = '". $id ."'";
         /// TODO review comment management...
         /// TODO getDropdownName need to return only name
         /// When needed to use comment use class instead : getComments function
         /// GetName of class already give Name !!
         /// TODO CommonDBTM : review getComments to be recursive and add informations from class hierarchy
         /// getUserName have the same system : clean it too
         /// Need to study the problem
         if ($result = $DB->query($query)) {
            if ($DB->numrows($result) != 0) {
               $data = $DB->fetch_assoc($result);
               $name = $data["name"];

               if (isset($data["comment"])) {
                  $comment = $data["comment"];
               }

               switch ($table) {
                  case "glpi_computers" :
                     if (empty($name)) {
                        $name = "($id)";
                     }
                     break;

                  case "glpi_contacts" :
                     //TRANS: %1$s is the name, %2$s is the firstname
                     $name = sprintf(__('%1$s %2$s'), $name, $data["firstname"]);
                     if (!empty($data["phone"])) {
                        $comment .= "<br>".sprintf(__('%1$s: %2$s'), "<span class='b'>".__('Phone'),
                                                   "</span>".$data['phone']);
                     }
                     if (!empty($data["phone2"])) {
                        $comment .= "<br>".sprintf(__('%1$s: %2$s'),
                                                   "<span class='b'>".__('Phone 2'),
                                                   "</span>".$data['phone2']);
                     }
                     if (!empty($data["mobile"])) {
                        $comment .= "<br>".sprintf(__('%1$s: %2$s'),
                                                   "<span class='b'>".__('Mobile phone'),
                                                   "</span>".$data['mobile']);
                     }
                     if (!empty($data["fax"])) {
                        $comment .= "<br>".sprintf(__('%1$s: %2$s'), "<span class='b'>".__('Fax'),
                                                   "</span>".$data['fax']);
                     }
                     if (!empty($data["email"])) {
                        $comment .= "<br>".sprintf(__('%1$s: %2$s'), "<span class='b'>".__('Email'),
                                                   "</span>".$data['email']);
                     }
                     break;

                  case "glpi_suppliers" :
                     if (!empty($data["phonenumber"])) {
                        $comment .= "<br>".sprintf(__('%1$s: %2$s'), "<span class='b'>".__('Phone'),
                                                   "</span>".$data['phonenumber']);
                     }
                     if (!empty($data["fax"])) {
                        $comment .= "<br>".sprintf(__('%1$s: %2$s'), "<span class='b'>".__('Fax'),
                                                   "</span>".$data['fax']);
                     }
                     if (!empty($data["email"])) {
                        $comment .= "<br>".sprintf(__('%1$s: %2$s'), "<span class='b'>".__('Email'),
                                                   "</span>".$data['email']);
                     }
                     break;

                  case "glpi_netpoints" :
                     $name = sprintf(__('%1$s (%2$s)'), $name,
                                     self::getDropdownName("glpi_locations",
                                                           $data["locations_id"]));
                     break;
               }
            }
         }
      }

      if (empty($name)) {
         $name = "&nbsp;";
      }

      if ($withcomment) {
         return array('name'     => $name,
                      'comment'  => $comment);
      }

      return $name;
   }


   /**
    * Get values of a dropdown for a list of item
    *
    * @param $table        the dropdown table from witch we want values on the select
    * @param $ids    array containing the ids to get
    *
    * @return array containing the value of the dropdown or &nbsp; if not exists
   **/
   static function getDropdownArrayNames($table, $ids) {
      global $DB, $CFG_GLPI;

      $tabs = array();

      if (count($ids)) {
         $itemtype = getItemTypeForTable($table);
         if ($item = getItemForItemtype($itemtype)) {
            $field    = 'name';
            if ($item instanceof CommonTreeDropdown) {
               $field = 'completename';
            }

            $query = "SELECT `id`, `$field`
                      FROM `$table`
                      WHERE `id` IN (".implode(',',$ids).")";

            if ($result = $DB->query($query)) {
               while ($data = $DB->fetch_assoc($result)) {
                  $tabs[$data['id']] = $data[$field];
               }
            }
         }
      }
      return $tabs;
   }


   /**
    * Make a select box for device type
    *
    * @param $name            name of the select box
    * @param $types     array of types to display
    * @param $options   array Already used items ID: not to display in dropdown
    * Parameters which could be used in options array :
    *    - value      : integer / preselected value (default '')
    *    - used       : array / Already used items ID: not to display in dropdown (default empty)
    *    - emptylabel : Empty choice's label (default self::EMPTY_VALUE)
    *    - display    : boolean if false get string
    *
    * @return nothing (print out an HTML select box)
   **/
   static function showItemTypes($name, $types=array(), $options=array()) {
      global $CFG_GLPI;

      $params['value']        = '';
      $params['used']         = array();
      $params['emptylabel']   = self::EMPTY_VALUE;
      $params['display']      = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      $options = array('' => $params['emptylabel']);

      if (count($types)) {
         foreach ($types as $type) {
            if ($item = getItemForItemtype($type)) {
               $options[$type] = $item->getTypeName(1);
            }
         }
      }
      asort($options);
      return self::showFromArray($name, $options, array('value'   => $params['value'],
                                                        'used'    => $params['used'],
                                                        'display' => $params['display']));
   }


   /**
    * Make a select box for device type
    *
    * @param $name                  name of the select box
    * @param $itemtype_ref  string   itemtype reference where to search in itemtype field
    * @param $options       array    of possible options:
    *        - may be value (default value) / field (used field to search itemtype)
    *
    * @return nothing (print out an HTML select box)
   **/
   static function dropdownUsedItemTypes($name, $itemtype_ref, $options=array()) {
      global $DB;

      $p['value'] = 0;
      $p['field'] = 'itemtype';

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $query = "SELECT DISTINCT `".$p['field']."`
                FROM `".getTableForItemType($itemtype_ref)."`";

      $tabs = array();
      if ($result = $DB->query($query)) {
         while ($data = $DB->fetch_assoc($result)) {
            $tabs[$data[$p['field']]] = $data[$p['field']];
         }
      }
      return self::showItemTypes($name, $tabs, array('value' => $p['value']));
   }


   /**
    * Make a select box for icons
    *
    * @param $myname                the name of the HTML select
    * @param $value                 the preselected value we want
    * @param $store_path            path where icons are stored
    * @param $display      boolean  display of get string ? (true by default)
    *
    * @return nothing (print out an HTML select box)
   **/
   static function dropdownIcons($myname, $value, $store_path, $display=true) {

      $output = '';
      if (is_dir($store_path)) {
         if ($dh = opendir($store_path)) {
            $files = array();

            while (($file = readdir($dh)) !== false) {
               $files[] = $file;
            }

            closedir($dh);
            sort($files);
            $output .= "<select name='$myname'>";
            $output .= "<option value=''>".self::EMPTY_VALUE."</option>";

            foreach ($files as $file) {
               if (preg_match("/\.png$/i",$file)) {

                  if ($file == $value) {
                     $output .= "<option value='$file' selected>".$file;
                  } else {
                     $output .= "<option value='$file'>".$file;
                  }

                  $output .= "</option>";
               }
            }

            $output .= "</select>";

         } else {
            //TRANS: %s is the store path
            printf(__('Error reading directory %s'), $store_path);
         }

      } else {
         //TRANS: %s is the store path
         printf(__('Error: %s is not a directory'), $store_path);
      }
      if ($display) {
         echo $output;
      } else {
         return $output;
      }
   }


   /**
    * Dropdown for GMT selection
    *
    * @param $name   select name
    * @param $value  default value (default '')
   **/
   static function showGMT($name, $value='') {

      $elements = array(-12, -11, -10, -9, -8, -7, -6, -5, -4, -3.5, -3, -2, -1, 0,
                        '+1', '+2', '+3', '+3.5', '+4', '+4.5', '+5', '+5.5', '+6', '+6.5', '+7',
                        '+8', '+9', '+9.5', '+10', '+11', '+12', '+13');

      echo "<select name='$name' id='dropdown_".$name."'>";

      foreach ($elements as $element) {
         if ($element != 0) {
            $display_value = sprintf(__('%1$s %2$s'), __('GMT'),
                                     sprintf(_n('%s hour', '%s hours', $element), $element));
         } else {
            $display_value = __('GMT');
         }

         $eltvalue = $element*HOUR_TIMESTAMP;
         echo "<option value='$eltvalue'".($eltvalue==$value?" selected":"").">".$display_value.
              "</option>";
      }
      echo "</select>";
   }


   /**
    * Make a select box for a boolean choice (Yes/No)
    *
    * @param $name               select name
    * @param $value              preselected value. (default 0)
    * @param $restrict_to        allows to display only yes or no in the dropdown (default -1)
    * @param $params       Array of optional options (passed to showFromArray)
    *
    * @return rand value
   **/
   static function showYesNo($name, $value=0, $restrict_to=-1, $params=array()) {

      if ($restrict_to != 0) {
         $options[0] = __('No');
      }

      if ($restrict_to != 1) {
         $options[1] = __('Yes');
      }

      $params['value'] = $value;
      return self::showFromArray($name, $options, $params);
   }


   /**
    * Get Yes No string
    *
    * @param $value Yes No value
    *
    * @return string
   **/
   static function getYesNo($value) {

      if ($value) {
         return __('Yes');
      }
      return __('No');
   }


   /**
    * Get the Device list name the user is allowed to edit
    *
    * @return array (group of dropdown) of array (itemtype => localized name)
   **/
   static function getDeviceItemTypes() {
      global $CFG_GLPI;
      static $optgroup = NULL;

      if (!Session::haveRight('device', 'r')) {
         return array();
      }

      if (is_null($optgroup)) {
         $optgroup = array(_n('Component', 'Components', 2)
                                  => array('DeviceMotherboard' => DeviceMotherboard::getTypeName(2),
                                           'DeviceProcessor'   => DeviceProcessor::getTypeName(2),
                                           'DeviceNetworkCard' => DeviceNetworkCard::getTypeName(2),
                                           'DeviceMemory'      => DeviceMemory::getTypeName(2),
                                           'DeviceHardDrive'   => DeviceHardDrive::getTypeName(2),
                                           'DeviceDrive'       => DeviceDrive::getTypeName(2),
                                           'DeviceControl'     => DeviceControl::getTypeName(2),
                                           'DeviceGraphicCard' => DeviceGraphicCard::getTypeName(2),
                                           'DeviceSoundCard'   => DeviceSoundCard::getTypeName(2),
                                           'DeviceCase'        => DeviceCase::getTypeName(2),
                                           'DevicePowerSupply' => DevicePowerSupply::getTypeName(2),
                                           'DevicePci'         => DevicePci::getTypeName(2)));
      }
      return $optgroup;
   }


   /**
    * Get the dropdown list name the user is allowed to edit
    *
    * @return array (group of dropdown) of array (itemtype => localized name)
   **/
   static function getStandardDropdownItemTypes() {
      global $CFG_GLPI;
      static $optgroup = NULL;

      if (is_null($optgroup)) {
         $optgroup
            = array(__('Common')
                        => array('Location'        => _n('Location', 'Locations', 2),
                                 'State'           => _n('Status of items', 'Statuses of items', 2),
                                 'Manufacturer'    => _n('Manufacturer', 'Manufacturers', 2),
                                 'Blacklist'       => _n('Blacklist','Blacklists',2)),

                    __('Assistance')
                        => array('ITILCategory'     =>  _n('Category of ticket',
                                                           'Categories of tickets',2),
                                 'TaskCategory'     => _n('Tasks category','Tasks categories', 2),
                                 'SolutionType'     => _n('Solution type', 'Solution types', 2),
                                 'RequestType'      => _n('Request source', 'Request sources', 2),
                                 'SolutionTemplate' => _n('Solution template',
                                                          'Solution templates', 2)),

                    _n('Type', 'Types', 2)
                        => array('ComputerType'         => _n('Computer type', 'Computers types', 2),
                                 'NetworkEquipmentType' => _n('Networking equipment type',
                                                              'Networking equipment types', 2),
                                 'PrinterType'          => _n('Printer type', 'Printer types', 2),
                                 'MonitorType'          => _n('Monitor type', 'Monitor types', 2),
                                 'PeripheralType'       => _n('Devices type', 'Devices types', 2),
                                 'PhoneType'            => _n('Phone type', 'Phones types', 2),
                                 'SoftwareLicenseType'  => _n('License type', 'License types', 2),
                                 'CartridgeItemType'    => _n('Cartridge type', 'Cartridge types', 2),
                                 'ConsumableItemType'   => _n('Consumable type',
                                                              'Consumable types', 2),
                                 'ContractType'         => _n('Contract type', 'Contract types', 2),
                                 'ContactType'          => _n('Contact type', 'Contact types', 2),
                                 'DeviceMemoryType'     => _n('Memory type', 'Memory types', 2),
                                 'SupplierType'         => _n('Third party type',
                                                              'Third party types', 2),
                                 'InterfaceType'        => _n('Interface type (Hard drive...)',
                                                              'Interface types (Hard drive...)', 2) ,
                                 'DeviceCaseType'       => _n('Case type', 'Case types', 2),
                                 'PhonePowerSupply'     => _n('Phone power supply type',
                                                              'Phones power supply types', 2),
                                 'Filesystem'           => _n('File system', 'File systems', 2)),

                    __('Model')
                        => array('ComputerModel'         => _n('Computer model',
                                                               'Computer models', 2),
                                 'NetworkEquipmentModel' => _n('Networking equipment model',
                                                               'Networking equipment models', 2),
                                 'PrinterModel'          => _n('Printer model', 'Printer models', 2),
                                 'MonitorModel'          => _n('Monitor model', 'Monitor models', 2),
                                 'PeripheralModel'       => _n('Peripheral model',
                                                               'Peripheral models', 2),
                                 'PhoneModel'            =>  _n('Phone model', 'Phone models', 2)),

                    _n('Virtual machine', 'Virtual machines', 2)
                        => array('VirtualMachineType'   => _n('Virtualization model',
                                                              'Virtualization models', 2),
                                 'VirtualMachineSystem' => _n('Virtualization system',
                                                              'Virtualization systems', 2),
                                 'VirtualMachineState'  => _n('State of the virtual machine',
                                                              'States of the virtual machine', 2)),

                    __('Management')
                        => array('DocumentCategory' => _n('Document heading', 'Document headings', 2),
                                 'DocumentType'     => _n('Document type', 'Document types', 2)),

                    __('Tools')
                        => array('KnowbaseItemCategory' => _n('Knowledge base category',
                                                              'Knowledge base categories', 2)),

                    __('Calendar')
                        => array('Calendar' => _n('Calendar', 'Calendars', 2),
                                 'Holiday'  => _n('Close time', 'Close times', 2)),

                    _n('Operating system', 'Operating systems',2)
                        => array('OperatingSystem'     => _n('Operating system',
                                                             'Operating systems', 2),
                                 'OperatingSystemVersion'
                                                      => _n('Version of the operating system',
                                                            'Versions of the operating system', 2),
                                 'OperatingSystemServicePack'
                                                      => _n('Service pack', 'Service packs', 2),
                                 'AutoUpdateSystem'   => _n('Update source', 'Update sources', 2)),

                    __('Networking')
                        => array('NetworkInterface'         => _n('Network interface',
                                                                  'Network interfaces', 2),
                                 'NetworkEquipmentFirmware' => _n('Firmware', 'Firmwares', 2),
                                 'Netpoint'                 => _n('Network outlet',
                                                                  'Network outlets', 2),
                                 'Domain'                   => _n('Domain', 'Domains', 2),
                                 'Network'                  => _n('Network', 'Networks', 2),
                                 'Vlan'                     => __('VLAN')),

                    __('Internet')
                        => array('IPNetwork'    => _n('IP network', 'IP networks', 2),
                                 'FQDN'         => _n('Internet domain', 'Internet domains', 2),
                                 'WifiNetwork'  => _n('Wifi network', 'Wifi networks', 2),
                                 'NetworkName'  => _n('Network name', 'Network names', 2)),

                    _n('Software', 'Software', 1)
                        => array('SoftwareCategory' => _n('Software category',
                                                          'Software categories', 2)),

                    __('User')
                        => array('UserTitle'     => _n('User title', 'Users titles', 2),
                                 'UserCategory'  => _n('User category', 'User categories', 2)),

                    __('Authorizations assignment rules')
                        => array('RuleRightParameter' => _n('LDAP criterion', 'LDAP criteria', 2)),

                    __('Fields unicity')
                        => array('Fieldblacklist' => _n('Ignored value for the unicity',
                                                        'Ignored values for the unicity', 2)),

                    __('External authentications')
                        => array('SsoVariable' => _n('Field storage of the login in the HTTP request',
                                                     'Fields storage of the login in the HTTP request',
                                                     2))


                 ); //end $opt

         $plugdrop = Plugin::getDropdowns();

         if (count($plugdrop)) {
            $optgroup = array_merge($optgroup, $plugdrop);
         }

         foreach ($optgroup as $label=>$dp) {
            foreach ($dp as $key => $val) {

               if ($tmp = getItemForItemtype($key)) {
                  if (!$tmp->canView()) {
                     unset($optgroup[$label][$key]);
                  }
               } else {
                  unset($optgroup[$label][$key]);
               }
            }

            if (count($optgroup[$label]) == 0) {
               unset($optgroup[$label]);
            }
         }
      }
      return $optgroup;
   }


   /**
    * Display a menu to select a itemtype which open the search form
    *
    * @param $title     string   title to display
    * @param $optgroup  array    (group of dropdown) of array (itemtype => localized name)
    * @param $value     string   URL of selected current value (default '')
   **/
   static function showItemTypeMenu($title, $optgroup, $value='') {

      echo "<table class='tab_cadre'>";
      echo "<tr class='tab_bg_1'><td class='b'>&nbsp;".$title."&nbsp; ";
      echo "<select id='menu_nav'>";

      foreach ($optgroup as $label => $dp) {
         echo "<optgroup label=\"$label\">";

         foreach ($dp as $key => $val) {
            $search = Toolbox::getItemTypeSearchURL($key);

            if (basename($search) == basename($value)) {
               $sel = 'selected';
            } else {
               $sel = '';
            }
            echo "<option value='$search' $sel>$val</option>";
         }
         echo "</optgroup>";
      }
      echo "</select>&nbsp;";
      echo "<input type='submit' name='add' value=\""._sx('button', 'Search')."\" class='submit' ";
      echo "onClick='document.location=document.getElementById(\"menu_nav\").value;'";
      echo "></td></tr>";
      echo "</table><br>";
   }


   /**
    * Display a list to select a itemtype with link to search form
    *
    * @param $optgroup array (group of dropdown) of array (itemtype => localized name)
    */
   static function showItemTypeList($optgroup) {

      echo "<div id='list_nav'>";

      $nb = 0;
      foreach ($optgroup as $label => $dp) {
         $nb += count($dp);
      }
      $step = ($nb > 15 ? ($nb/3) : $nb);

      echo "<table class='tab_glpi'><tr class='top'><td width='33% class='center'>";
      echo "<table class='tab_cadre'>";
      $i = 1;

      foreach ($optgroup as $label => $dp) {
         echo "<tr><th>$label</th></tr>\n";

         foreach ($dp as $key => $val) {
            $class="class='tab_bg_4'";
            if (($itemtype = getItemForItemtype($key))
                && $itemtype->isEntityAssign()) {
               $class="class='tab_bg_2'";
            }
            echo "<tr $class><td><a href='".Toolbox::getItemTypeSearchURL($key)."'>";
            echo "$val</a></td></tr>\n";
            $i++;
         }

         if ($i >= $step) {
            echo "</table></td><td width='25'>&nbsp;</td><td><table class='tab_cadre'>";
            $step += $step;
         }
      }
      echo "</table></td></tr></table></div>";
   }


   /**
    * Dropdown available languages
    *
    * @param $myname          select name
    * @param $options   array of additionnal options:
    *    - display_emptychoice : allow selection of no language
    *    - emptylabel          : specific string to empty label if display_emptychoice is true
   **/
   static function showLanguages($myname, $options=array()) {
      global $CFG_GLPI;

      $values = array();
      if (isset($options['display_emptychoice']) && ($options['display_emptychoice'])) {
         if (isset($options['emptylabel'])) {
            $values[''] = $options['emptylabel'];
         } else {
            $values[''] = self::EMPTY_VALUE;
         }
      }

      foreach ($CFG_GLPI["languages"] as $key => $val) {
         if (isset($val[1]) && is_file(GLPI_ROOT ."/locales/".$val[1])) {
            $values[$key] = $val[0];
         }
      }
      return self::showFromArray($myname, $values, $options);
   }


   /**
    * @since version 0.84
    *
    * @param $value
   **/
   static function getLanguageName($value) {
      global $CFG_GLPI;

      if (isset($CFG_GLPI["languages"][$value][0])) {
         return $CFG_GLPI["languages"][$value][0];
      }
      return $value;
   }


   /**
    * Print a select with hours
    *
    * Print a select named $name with hours options and selected value $value
    *
    *@param $name             string   HTML select name
    *@param $value            integer  HTML select selected value
    *@param $limit_planning            limit planning to the configuration range (default 0)
    *
    *@return Nothing (display)
    **/
   static function showHours($name, $value, $limit_planning=0) {
      global $CFG_GLPI;

      $begin = 0;
      $end   = 24;
      $step  = $CFG_GLPI["time_step"];
      // Check if the $step is Ok for the $value field
      $split = explode(":", $value);

      // Valid value XX:YY ou XX:YY:ZZ
      if ((count($split) == 2) || (count($split) == 3)) {
         $min = $split[1];

         // Problem
         if (($min%$step) != 0) {
            // set minimum step
            $step = 5;
         }
      }

      if ($limit_planning) {
         $plan_begin = explode(":", $CFG_GLPI["planning_begin"]);
         $plan_end   = explode(":", $CFG_GLPI["planning_end"]);
         $begin      = (int) $plan_begin[0];
         $end        = (int) $plan_end[0];
      }
      echo "<select name=\"$name\">";

      for ($i=$begin ; $i<$end ; $i++) {
         if ($i < 10) {
            $tmp = "0".$i;
         } else {
            $tmp = $i;
         }

         for ($j=0 ; $j<60 ; $j+=$step) {
            if ($j < 10) {
               $val = $tmp.":0$j";
            } else {
               $val = $tmp.":$j";
            }

            echo "<option value='$val' ".(($value == $val.":00") || ($value == $val) ?" selected ":"").
                 ">$val</option>";
         }
      }
      // Last item
      $val = $end.":00";
      echo "<option value='$val' ".(($value == $val.":00") || ($value == $val) ?" selected ":"").
           ">$val</option>";
      echo "</select>";
   }


   /**
    * show a dropdown to selec a type
    *
    * @since version 0.83
    *
    * @param $types           Types used (default "state_types") (default '')
    * @param $options   Array of optional options
    *        name, value, rand, emptylabel, display_emptychoice, on_change, plural, checkright
    *
    * @return integer rand for select id
   **/
   static function showItemType($types='', $options=array()) {
      global $CFG_GLPI;

      $params['name']                = 'itemtype';
      $params['value']               = '';
      $params['rand']                = mt_rand();
      $params['on_change']           = '';
      $params['plural']              = false;
      //Parameters about choice 0
      //Empty choice's label
      $params['emptylabel']          = self::EMPTY_VALUE;
      //Display emptychoice ?
      $params['display_emptychoice'] = true;
      $params['checkright']          = false;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      if (!is_array($types)) {
         $types = $CFG_GLPI["state_types"];
      }
      $options = array();

      foreach ($types as $type) {
         if ($item = getItemForItemtype($type)) {
            if ($params['checkright'] && !$item->canView()) {
               continue;
            }
            $options[$type] = $item->getTypeName($params['plural'] ? 2 : 1);
         }
      }
      asort($options);

      if (count($options)) {
         echo "<select name='".$params['name']."' id='itemtype".$params['rand']."'";
         if ($params['on_change']) {
            echo " onChange='".$params['on_change']."'>";
         } else {
            echo ">";
         }
         if ($params['display_emptychoice']) {
            echo "<option value='0'>".$params['emptylabel']."</option>\n";
         }

         foreach ($options as $key => $val) {
            $sel = (($key === $params['value']) ? 'selected' : '');
            echo "<option value='".$key."' $sel>".$val."</option>";
         }
         echo "</select>";

         return $params['rand'];
      }
      return 0;
   }


   /**
    * Make a select box for all items
    *
    * @param $myname          select name
    * @param $value_type      default value for the device type (default 0)
    * @param $value           default value (default 0)
    * @param $entity_restrict Restrict to a defined entity (default -1)
    * @param $types           Types used (default '')
    * @param $onlyglobal      Restrict to global items (false by default)
    * @param $checkright      Restrict to items with read rights (false by default)
    * @param $itemtypename    name used for itemtype select
    *
    * @return nothing (print out an HTML select box)
   **/
   static function showAllItems($myname, $value_type=0, $value=0, $entity_restrict=-1, $types='',
                                $onlyglobal=false, $checkright=false, $itemtypename = 'itemtype') {
      global $CFG_GLPI;

      $options               = array();
      $options['checkright'] = $checkright;
      $options['name']       = $itemtypename;

      $rand = self::showItemType($types, $options);
      if ($rand) {
         $params = array('idtable'          => '__VALUE__',
                          'value'           => $value,
                          'myname'          => $myname,
                          'entity_restrict' => $entity_restrict);

         if ($onlyglobal) {
            $params['condition'] = "`is_global` = '1'";
         }
         Ajax::updateItemOnSelectEvent("itemtype$rand", "show_$myname$rand",
                                       $CFG_GLPI["root_doc"]."/ajax/dropdownAllItems.php", $params);

         echo "<br><span id='show_$myname$rand'>&nbsp;</span>\n";

         if ($value > 0) {
            echo "<script type='text/javascript' >\n";
            echo "window.document.getElementById('itemtype$rand').value='".$value_type."';";
            echo "</script>\n";

            $params["idtable"] = $value_type;
            Ajax::updateItem("show_$myname$rand", $CFG_GLPI["root_doc"]."/ajax/dropdownAllItems.php",
                             $params);
         }
      }
      return $rand;
   }

   /**
    * Dropdown numbers
    *
    * @since version 0.84
    *
    * @param $myname          select name
    * @param $options   array of additionnal options :
    *     - value           default value (defaul 0)
    *     - rand            random value
    *     - min             min value (default 0)
    *     - max             max value (default 100)
    *     - step            step used (default 1)
    *     - toadd     array of values to add at the beginning
    *     - unit : string unit to used
    *     - display : boolean if false get string
   **/
   static function showNumber($myname, $options=array()) {

      $params['value']   = 0;
      $params['rand']    = mt_rand();
      $params['min']     = 0;
      $params['max']     = 100;
      $params['step']    = 1;
      $params['toadd']   = array();
      $params['unit']    = '';
      $params['display'] = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      $out = "<select name='$myname' id='$myname".$params['rand']."'>\n";

      if (count($params['toadd'])) {
         foreach ($params['toadd'] as $key => $val) {
            $out .=  "<option value='$key' ".(($key == $params['value']) ?" selected ":"").">";
            $out .= $val."</option>";
         }
      }

      for ($i=$params['min'] ; $i<=$params['max'] ; $i+=$params['step']) {
         $txt = $i;
         if (isset($params['unit'])) {
            $txt = self::getValueWithUnit($i,$params['unit']);
         }
         $out .= "<option value='$i' ".(($i == $params['value']) ?" selected ":"").">$txt</option>";
      }
      $out .= "</select>";
      if ($params['display']) {
         echo $out;
         return $params['rand'];
      }
      return $out;
   }


   /**
    * Get value with unit / Automatic management of standar unit (year, month, %, ...)
    *
    * @since v ersion 0.84
    *
    * @param $value   integer   number of item
    * @param $unit    string    of unit (maybe year, month, day, hour, % for standard management)
   **/
   static function getValueWithUnit($value, $unit) {

      if (strlen($unit) == 0) {
         return $value;
      }

      switch ($unit) {
         case 'year' :
            //TRANS: %d is a number of years
            return sprintf(_n('%d year', '%d years', $value), $value);

         case 'month' :
            //TRANS: %d is a number of months
            return sprintf(_n('%d month', '%d months', $value), $value);

         case 'day' :
            //TRANS: %d is a number of days
            return sprintf(_n('%d day', '%d days', $value), $value);

         case 'hour' :
            //TRANS: %d is a number of hours
            return sprintf(_n('%d hour', '%d hours', $value), $value);

         case 'minute' :
            //TRANS: %d is a number of minutes
            return sprintf(_n('%d minute', '%d minutes', $value), $value);

         case 'second' :
            //TRANS: %d is a number of seconds
            return sprintf(_n('%d second', '%d seconds', $value), $value);

         case 'millisecond' :
            //TRANS: %d is a number of milliseconds
            return sprintf(_n('%d millisecond', '%d milliseconds', $value), $value);

         case '%' :
            return sprintf(__('%d%%'), $value);

         default :
            return sprintf(__('%1$s %2$s'), $value, $unit);
      }
   }


   /**
    * Dropdown integers
    *
    * @param $myname          select name
    * @param $value           default value
    * @param $min             min value (default 0)
    * @param $max             max value (default 100)
    * @param $step            step used (default 1)
    * @param $toadd     array of values to add at the beginning
    * @param $options   array of additionnal options :
    *                            - unit : string unit to used
    *                            - display : boolean if false get string
    * \deprecated use Dropdown::showNumber instead
   **/
   static function showInteger($myname, $value, $min=0, $max=100, $step=1, $toadd=array(),
                               $options=array()) {

      $opt = array('value' => $value,
                   'min'   => $min,
                   'max'   => $max,
                   'step'  => $step,
                   'toadd' => $toadd);
      if (count($options)) {
         foreach ($options as $key => $val) {
            $opt[$key] = $val;
         }
      }
      return self::showNumber($myname,$opt);

   }


   /**
    * Dropdown integers
    *
    * @since version 0.83
    *
    * @param $myname        select name
    * @param $options array of options
    *    - value           : default value
    *    - min             : min value : default 0
    *    - max             : max value : default DAY_TIMESTAMP
    *    - value           : default value
    *    - addfirstminutes : add first minutes before first step (default false)
    *    - toadd           : array of values to add
    *    - inhours         : only show timestamp in hours not in days
    *    - display         : boolean / display or return string
   **/
   static function showTimeStamp($myname, $options=array()) {
      global $CFG_GLPI;

      $params['value']               = 0;
      $params['min']                 = 0;
      $params['max']                 = DAY_TIMESTAMP;
      $params['step']                = $CFG_GLPI["time_step"]*MINUTE_TIMESTAMP;
      $params['emptylabel']          = self::EMPTY_VALUE;
      $params['addfirstminutes']     = false;
      $params['toadd']               = array();
      $params['inhours']             = false;
      $params['display']             = true;
      $params['display_emptychoice'] = true;


      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      // Manage min :
      $params['min'] = floor($params['min']/$params['step'])*$params['step'];

      if ($params['min'] == 0) {
         $params['min'] = $params['step'];
      }

      $params['max'] = max($params['value'], $params['max']);

      // Floor with MINUTE_TIMESTAMP for rounded purpose
      if (($params['value'] < max($params['min'], 10*MINUTE_TIMESTAMP))
          && $params['addfirstminutes']) {
         $params['value'] = floor(($params['value'])/MINUTE_TIMESTAMP)*MINUTE_TIMESTAMP;
      } else {
         $params['value'] = floor(($params['value'])/$params['step'])*$params['step'];
      }

      $values = array();
      if ($params['display_emptychoice']) {
         $values = array(0 => $params['emptylabel']);
      }

      if ($params['value']) {
         $values[$params['value']] = '';
      }

      if ($params['addfirstminutes']) {
         for ($i=MINUTE_TIMESTAMP; $i<max($params['min'], 10*MINUTE_TIMESTAMP); $i+=MINUTE_TIMESTAMP) {
            $values[$i] = '';
         }
      }

      for ($i = $params['min'] ; $i <= $params['max']; $i+=$params['step']) {
         $values[$i] = '';
      }

      if (count($params['toadd'])) {
         foreach ($params['toadd'] as $key) {
            $values[$key] = '';
         }
         ksort($values);
      }

      foreach ($values as $i => $val) {
         if (empty($val)) {
            if ($params['inhours']) {
               $day  = 0;
               $hour = floor($i/HOUR_TIMESTAMP);
            } else {
               $day  = floor($i/DAY_TIMESTAMP);
               $hour = floor(($i%DAY_TIMESTAMP)/HOUR_TIMESTAMP);
            }
            $minute     = floor(($i%HOUR_TIMESTAMP)/MINUTE_TIMESTAMP);
            if ($minute === '0') {
               $minute = '00';
            }
            $values[$i] = '';
            if ($day > 0) {
               if (($hour > 0) || ($minute > 0)) {
                  if ($minute < 10) {
                     $minute = '0'.$minute;
                  }

                  //TRANS: %1$d is the number of days, %2$d the number of hours,
                  //       %3$s the number of minutes : display 1 day 3h15
                  $values[$i] = sprintf(_n('%1$d day %2$dh%3$s','%1$d days %2$dh%3$s', $day),
                                       $day, $hour, $minute);
               } else {
                  $values[$i] = sprintf(_n('%d day','%d days',$day), $day);
               }

            } else if ($hour > 0 || $minute > 0) {
               if ($minute < 10) {
                  $minute = '0'.$minute;
               }

               //TRANS: %1$d the number of hours, %2$s the number of minutes : display 3h15
               $values[$i] = sprintf(__('%1$dh%2$s'), $hour, $minute);
            }
         }
      }
      return Dropdown::showFromArray($myname, $values, array('value'   => $params['value'],
                                                             'display' => $params['display']));
   }


   /**
    * Private / Public switch for items which may be assign to a user and/or an entity
    *
    * @param $is_private      default is private ?
    * @param $entity          working entity ID
    * @param $is_recursive    is the item recursive ?
   **/
   static function showPrivatePublicSwitch($is_private, $entity, $is_recursive) {
      global $CFG_GLPI;

      $rand = mt_rand();
      echo "<script type='text/javascript' >\n";
      echo "function setPrivate$rand() {\n";

         $params = array('is_private'   => 1,
                         'is_recursive' => $is_recursive,
                         'entities_id'  => $entity,
                         'rand'         => $rand);

         Ajax::updateItemJsCode('private_switch'.$rand,
                                $CFG_GLPI["root_doc"]."/ajax/private_public.php", $params);
      echo "};";

      echo "function setPublic$rand() {\n";

         $params = array('is_private'   => 0,
                         'is_recursive' => $is_recursive,
                         'entities_id'  => $entity,
                         'rand'         => $rand);
         Ajax::updateItemJsCode('private_switch'.$rand,
                                $CFG_GLPI["root_doc"]."/ajax/private_public.php", $params);
      echo "};";
      echo "</script>";

      echo "<span id='private_switch$rand'>";
      $_POST['rand']         = $rand;
      $_POST['is_private']   = $is_private;
      $_POST['is_recursive'] = $is_recursive;
      $_POST['entities_id']  = $entity;
      include (GLPI_ROOT."/ajax/private_public.php");
      echo "</span>\n";
      return $rand;
   }


   /**
    * Toggle view in LDAP user import/synchro between no restriction and date restriction
    *
    * @param $enabled (default 0)
   **/
   static function showAdvanceDateRestrictionSwitch($enabled=0) {
      global $CFG_GLPI;

      $rand = mt_rand();
      $url  = $CFG_GLPI["root_doc"]."/ajax/ldapdaterestriction.php";
      echo "<script type='text/javascript' >\n";
      echo "function activateRestriction() {\n";
         $params = array('enabled'=> 1);
         Ajax::updateItemJsCode('date_restriction', $url, $params);
      echo "};";

      echo "function deactivateRestriction() {\n";
         $params = array('enabled' => 0);
         Ajax::updateItemJsCode('date_restriction', $url, $params);
      echo "};";
      echo "</script>";

      echo "</table>";
      echo "<span id='date_restriction'>";
      $_POST['enabled'] = $enabled;
      include (GLPI_ROOT."/ajax/ldapdaterestriction.php");
      echo "</span>\n";
      return $rand;
   }


   /**
    * Dropdown of values in an array
    *
    * @param $name            select name
    * @param $elements  array of elements to display
    * @param $options   array of possible options:
    *    - value           : integer / preselected value (default 0)
    *    - used            : array / Already used items ID: not to display in dropdown (default empty)
    *    - readonly        : boolean / used as a readonly item (default false)
    *    - on_change       : string / value to transmit to "onChange"
    *    - multiple        : boolean / can select several values (default false)
    *    - size            : integer / number of rows for the select (default = 1)
    *    - mark_unmark_all : add buttons to select or deselect all options (only for multiple)
    *    - display         : boolean / display or return string
    *    - other           : boolean or string if not false, then we can use an "other" value
    *                        if it is a string, then the default value will be this string
    *    - rand            : specific rand if needed (default is generated one)
    *
    * Permit to use optgroup defining items in arrays
    * array('optgroupname'  => array('key1' => 'val1',
    *                                'key2' => 'val2'),
    *       'optgroupname2' => array('key3' => 'val3',
    *                                'key4' => 'val4'))
   **/
   static function showFromArray($name, array $elements, $options=array()) {

      $param['value']           = '';
      $param['values']          = array();
      $param['used']            = array();
      $param['readonly']        = false;
      $param['on_change']       = '';
      $param['multiple']        = false;
      $param['size']            = 1;
      $param['mark_unmark_all'] = false;
      $param['display']         = true;
      $param['other']           = false;
      $param['rand']            = mt_rand();

      if (is_array($options) && count($options)) {
         if (isset($options['value']) && strlen($options['value'])) {
            $options['values'] = array($options['value']);
            unset($options['value']);
         }
         foreach ($options as $key => $val) {
            $param[$key] = $val;
         }
      }
      if ($param['other'] !== false) {
         $other_select_option = $name . '_other_value';
         $param['on_change'] .= "displayOtherSelectOptions(this, \"$other_select_option\");";

         // If $param['other'] is a string, then we must highlight "other" option
         if (is_string($param['other'])) {
            if (!$param["multiple"]) {
               $param['values'] = array($other_select_option);
            } else {
               $param['values'][] = $other_select_option;
            }
         }
      }

      if ($param["multiple"]) {
         $field_name = $name."[]";
      } else {
         $field_name = $name;
      }

      $output = '';
      // readonly mode
      if ($param['readonly']) {
         foreach ($param['values'] as $value) {
            $output .= "<input type='hidden' name='$field_name' value='$value'>";
            if (isset($elements[$value])) {
               $output .= $elements[$value]." ";
            }
         }

      } else {

         $field_id = "dropdown_".$name.$param['rand'];
         $output  .= "<select name='$field_name' id='$field_id'";

         if (!empty($param["on_change"])) {
            $output .= " onChange='".$param["on_change"]."'";
         }

         if ((is_int($param["size"])) && ($param["size"] > 0)) {
            $output .= " size='".$param["size"]."'";
         }

         if ($param["multiple"]) {
            $output .= " multiple";
         }

         $output .= '>';

         $max_option_size = 0;
         foreach ($elements as $key => $val) {
            // optgroup management
            if (is_array($val)) {
               $opt_goup = Html::entities_deep($key);
               if ($max_option_size < strlen($opt_goup)) {
                  $max_option_size = strlen($opt_goup);
               }
               $output .= "<optgroup label=\"$opt_goup\">";
               foreach ($val as $key2 => $val2) {
                  if (!isset($param['used'][$key2])) {
                     $output .= "<option value='".$key2."'";
                     if (in_array($key2, $param['values'])) {
                        $output .= " selected";
                     }
                     $output .= ">" .  $val2 . "</option>";
                     if ($max_option_size < strlen($val2)) {
                        $max_option_size = strlen($val2);
                     }
                  }
               }
               $output .= "</optgroup>";
            } else {
               if (!isset($param['used'][$key])) {
                  $output .= "<option value='".$key."'";
                  if (in_array($key, $param['values'])) {
                     $output .= " selected";
                  }
                  $output .= ">" . $val . "</option>";
                  if ($max_option_size < strlen($val)) {
                     $max_option_size = strlen($val);
                  }
               }
            }
         }

         if ($param['other'] !== false) {
            $output .= "<option value='$other_select_option'";
            if (is_string($param['other'])) {
               $output .= " selected";
            }
            $output .= ">".__('Other...')."</option>";
         }

         $output .= "</select>";
         if ($param['other'] !== false) {
            $output .= "<input name='$other_select_option' id='$other_select_option' type='text'";
            if (is_string($param['other'])) {
               $output .= " value=\"" . $param['other'] . "\"";
            } else {
               $output .= " style=\"display: none\"";
            }
            $output .= ">";
         }

         if ($param['mark_unmark_all'] && $param['multiple']) {
            $select   = __('Select all');
            $deselect = __('Deselect all');
            $size     = strlen($select) +  strlen($deselect);

            $select   = "<input type='button' onclick=\"selectAllOptions('$field_id')\" value=\"$select\">";
            $deselect = "<input type='button' onclick=\"unselectAllOptions('$field_id')\" value=\"$deselect\">";

            if ($size > $max_option_size) {
               $output = "<table><tr><td rowspan='2'>".$output."</td>";
               $output .= "<td>";
               $output .= $select;
               $output .= "</td></tr><tr><td class='center'>";
               $output .= $deselect;
            } else {
               $output = "<table><tr><td colspan='2'>".$output."</td>";
               $output .= "</tr><tr><td class='center'>";
               $output .= $select;
               $output .= "</td><td class='center'>";
               $output .= $deselect;
            }
            $output .= "</td></tr></table>";
         }

      }

      if ($param['display']) {
         echo $output;
         return $param['rand'];
      }
      return $output;
   }


   /**
    * Dropdown for global item management
    *
    * @param $ID           item ID
    * @param attrs   array which contains the extra paramters
    *
    * Parameters can be :
    * - target target for actions
    * - withtemplate template or basic computer
    * - value value of global state
    * - management_restrict global management restrict mode
   **/
   static function showGlobalSwitch($ID, $attrs=array()) {
      global $CFG_GLPI;

      $params['management_restrict'] = 0;
      $params['value']               = 0;
      $params['name']                = 'is_global';
      $params['target']              = '';

      foreach ($attrs as $key => $value) {
         if ($value != '') {
            $params[$key] = $value;
         }
      }

      if ($params['value']
          && empty($params['withtemplate'])) {
         _e('Global management');

         if ($params['management_restrict'] == 2) {
            echo "&nbsp;";
            Html::showSimpleForm($params['target'], 'unglobalize', __('Use unitary management'),
                                 array('id' => $ID), '', '',
                                 array(__('Do you really want to use unitary management for this item?'),
                                       __('Duplicate the element as many times as there are connections')));
            echo "&nbsp;";

            echo "<img alt=\"".__s('Duplicate the element as many times as there are connections').
                 "\" title=\"".__s('Duplicate the element as many times as there are connections').
                 "\" src='".$CFG_GLPI["root_doc"]."/pics/aide.png'>";
         }

      } else {
         if ($params['management_restrict'] == 2) {
            echo "<select name='".$params['name']."'>";
            echo "<option value='".MANAGEMENT_UNITARY."' ".
                  (!$params['value']?" selected":"").">".__('Unit management')."</option>";
            echo "<option value='".MANAGEMENT_GLOBAL."' ".
                  ($params['value']?" selected":"").">".__('Global management')."</option>";
            echo "</select>";

         } else {
            // Templates edition
            if (!empty($params['withtemplate'])) {
               echo "<input type='hidden' name='is_global' value='".
                      $params['management_restrict']."'>";
               echo (!$params['management_restrict']?__('Unit management') :__('Global management'));
            } else {
               echo (!$params['value']?__('Unit management'):__('Global management'));
            }
         }
      }
   }


   /**
    * Import a dropdown - check if already exists
    *
    * @param $itemtype  string   name of the class
    * @param $input     array    of value to import
    *
    * @return the ID of the new
   **/
   static function import($itemtype, $input) {

      if (!($item = getItemForItemtype($itemtype))) {
         return false;
      }
      return $item->import($input);
   }


   /**
    * Import a value in a dropdown table.
    *
    * This import a new dropdown if it doesn't exist - Play dictionnary if needed
    *
    * @param $itemtype        string   name of the class
    * @param $value           string   Value of the new dropdown. (need to be addslashes)
    * @param $entities_id     integer  entity in case of specific dropdown (default -1)
    * @param $external_params array    (need to be addslashes)
    * @param $comment                  (default '') (need to be addslashes)
    * @param $add                      if true, add it if not found. if false, just check if exists
    *                                  (true by default)
    *
    * @return integer : dropdown id.
   **/
   static function importExternal($itemtype, $value, $entities_id=-1, $external_params=array(),
                                  $comment='', $add=true) {

      if (!($item = getItemForItemtype($itemtype))) {
         return false;
      }
      return $item->importExternal($value, $entities_id, $external_params, $comment, $add);
   }

   /**
    * Get the label associated with a management type
    *
    * @param value the type of management (default 0)
    *
    * @return the label corresponding to it, or ""
   **/
   static function getGlobalSwitch($value=0) {

      switch ($value) {
         case 0 :
            return __('Unit management');

         case 1 :
            return __('Global management');

         default :
            return "";
      }
   }


   /**
    * show dropdown for output format
    *
    * @since version 0.83
   **/
   static function showOutputFormat() {
      global $CFG_GLPI;

      echo "<select name='display_type'>";
      echo "<option value='".Search::PDF_OUTPUT_LANDSCAPE."'>".__('Current page in landscape PDF').
           "</option>";
      echo "<option value='".Search::PDF_OUTPUT_PORTRAIT."'>".__('Current page in portrait PDF').
           "</option>";
      echo "<option value='".Search::SYLK_OUTPUT."'>".__('Current page in SLK')."</option>";
      echo "<option value='".Search::CSV_OUTPUT."'>".__('Current page in CSV')."</option>";
      echo "<option value='-".Search::PDF_OUTPUT_LANDSCAPE."'>".__('All pages in landscape PDF').
           "</option>";
      echo "<option value='-".Search::PDF_OUTPUT_PORTRAIT."'>".__('All pages in portrait PDF').
           "</option>";
      echo "<option value='-".Search::SYLK_OUTPUT."'>".__('All pages in SLK')."</option>";
      echo "<option value='-".Search::CSV_OUTPUT."'>".__('All pages in CSV')."</option>";
      echo "</select>&nbsp;";
      echo "<input type='image' name='export' src='".$CFG_GLPI["root_doc"]."/pics/greenbutton.png'
             title=\"".__s('Export')."\" value=\"".__s('Export')."\">";
   }


   /**
    * show dropdown to select list limit
    *
    * @since version 0.83
    *
    * @param $onchange  String   optional, for ajax (default '')
   **/
   static function showListLimit($onchange='') {
      global $CFG_GLPI;

      echo "<select name='glpilist_limit'";
      if ($onchange) {
         echo " onChange='$onchange'>";
      } else {
         echo ">";
      }

      if (isset($_SESSION['glpilist_limit'])) {
         $list_limit = $_SESSION['glpilist_limit'];
      } else {
         $list_limit = $CFG_GLPI['list_limit'];
      }

      $values = array();

      for ($i=5 ; $i<20 ; $i+=5) {
         $values[$i] = $i;
      }
      for ($i=20 ; $i<50 ; $i+=10) {
         $values[$i] = $i;
      }
      for ($i=50 ; $i<250 ; $i+=50) {
         $values[$i] = $i;
      }
      for ($i=250 ; $i<1000 ; $i+=250) {
         $values[$i] = $i;
      }
      for ($i=1000 ; $i<5000 ; $i+=1000) {
         $values[$i] = $i;
      }
      for ($i=5000 ; $i<=10000 ; $i+=5000) {
         $values[$i] = $i;
      }
      $values[9999999] = 9999999;
      // Propose max input vars -10
      $max        = Toolbox::get_max_input_vars();
      if ($max>10) {
         $values[$max-10] = $max-10;
      }
      ksort($values);
      foreach ($values as $val) {
         echo "<option value='$val' ".(($list_limit==$val)?" selected ":"").">$val</option>";
      }
      echo "</select>";
   }

}
?>
