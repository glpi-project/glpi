<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class Dropdown {

   //Empty value displayed in a dropdown
   const EMPTY_VALUE = '-----';

   /**
    * Print out an HTML "<select>" for a dropdown with preselected value
    *
    * Parameters which could be used in options array :
    *    - name : string / name of the select (default is depending itemtype)
    *    - value : integer / preselected value (default -1)
    *    - comments : boolean / is the comments displayed near the dropdown (default true)
    *    - toadd : array / array of specific values to add at the begining
    *    - entity : integer or array / restrict to a defined entity or array of entities
    *                   (default -1 : no restriction)
    *    - entity_sons : boolean / if entity restrict specified auto select its sons
    *                   only available if entity is a single value not an array (default false)
    *    - toupdate : array / Update a specific item on select change on dropdown
    *                   (need value_fieldname, to_update,
    *                   url (see Ajax::updateItemOnSelectEvent for informations)
    *                   and may have moreparams)
    *    - used : array / Already used items ID: not to display in dropdown (default empty)
    *    - on_change : string / value to transmit to "onChange"
    *    - rand : integer / already computed rand value
    *    - condition : string / aditional SQL condition to limit display
    *    - displaywith : array / array of field to display with request
    *
    * @param $itemtype itemtype used for create dropdown
    * @param $options array of possible options
    *
    * @return boolean : lse if error and random id if OK
   **/
   static function show($itemtype, $options=array()) {
      global $DB, $CFG_GLPI;

      if ($itemtype && !($item = getItemForItemtype($itemtype))) {
         return false;
      }

      $table = $item->getTable();

      $params['name']        = $item->getForeignKeyField();
      $params['value']       = ($itemtype=='Entity' ? $_SESSION['glpiactive_entity'] : '');
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
      $params['display_emptychoice'] = true;
      //In case of Entity dropdown, display root entity ?
      $params['display_rootentity']  = false;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      $name         = $params['emptylabel'];
      $comment      = "";
      $limit_length = $_SESSION["glpidropdown_chars_limit"];

      // Check default value for dropdown : need to be a numeric
      if (strlen($params['value'])==0 || !is_numeric($params['value'])) {
         $params['value'] = 0;
      }

      if ($params['value'] > 0
         || ($itemtype == "Entity" && $params['value'] >= 0)) {
         $tmpname = self::getDropdownName($table, $params['value'], 1);

         if ($tmpname["name"] != "&nbsp;") {
            $name    = $tmpname["name"];
            $comment = $tmpname["comment"];

            if (Toolbox::strlen($name) > $_SESSION["glpidropdown_chars_limit"]) {
               if ($item instanceof CommonTreeDropdown) {
                  $pos          = strrpos($name, ">");
                  $limit_length = max(Toolbox::strlen($name) - $pos,
                                      $_SESSION["glpidropdown_chars_limit"]);

                  if (Toolbox::strlen($name)>$limit_length) {
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
      if (!($params['entity']<0) && $params['entity_sons']) {
         if (is_array($params['entity'])) {
            echo "entity_sons options is not available with array of entity";
         } else {
            $params['entity'] = getSonsOf('glpi_entities',$params['entity']);
         }
      }

      $use_ajax = false;
      if ($CFG_GLPI["use_ajax"]) {
         $nb = 0;

         if ($item->isEntityAssign()) {
            if (!($params['entity']<0)) {
               $nb = countElementsInTableForEntity($table, $params['entity'], $params['condition']);
            } else {
               $nb = countElementsInTableForMyEntities($table, $params['condition']);
            }

         } else {
            $nb = countElementsInTable($table, $params['condition']);
         }

         $nb -= count($params['used']);

         if ($nb>$CFG_GLPI["ajax_limit_count"]) {
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
                      'display_rootentity'  => $params['display_rootentity']);

      $default  = "<select name='".$params['name']."' id='dropdown_".$params['name'].
                    $params['rand']."'>";
      $default .= "<option value='".$params['value']."'>$name</option></select>";
      Ajax::dropdown($use_ajax, "/ajax/dropdownValue.php", $param, $default, $params['rand']);

      // Display comment
      if ($params['comments']) {
         $options_tooltip = array('contentid' => "comment_".$params['name'].$params['rand']);

         if ($item->canView()
            && $params['value'] && $item->getFromDB($params['value'])
            && $item->canViewItem()) {

            $options_tooltip['link']       = $item->getLinkURL();
            $options_tooltip['linktarget'] = '_blank';
         }

         Html::showToolTip($comment,$options_tooltip);

         if (($item instanceof CommonDropdown)
              && $item->canCreate()
              && !isset($_GET['popup'])) {

               echo "<img alt='' title=\"".__s('Add')."\" src='".$CFG_GLPI["root_doc"].
                     "/pics/add_dropdown.png' style='cursor:pointer; margin-left:2px;'
                     onClick=\"var w = window.open('".$item->getFormURL()."?popup=1&amp;rand=".
                     $params['rand']."' ,'glpipopup', 'height=400, ".
                     "width=1000, top=100, left=100, scrollbars=yes' );w.focus();\">";
         }
         // Display specific Links
         if ($itemtype=="Supplier") {
            if ($item->getFromDB($params['value'])) {
               echo $item->getLinks();
            }
         }

         if ($itemtype=='ITILCategory' && Session::haveRight('knowbase','r')) {
            if ($params['value'] && $item->getFromDB($params['value'])) {
               echo '&nbsp;'.$item->getLinks();
            }
         }

      }

      return $params['rand'];
   }


   /**
    * Get the value of a dropdown
    *
    * Returns the value of the dropdown from $table with ID $id.
    *
    * @param $table the dropdown table from witch we want values on the select
    * @param $id id of the element to get
    * @param $withcomment give array with name and comment (default 0)
    *
    * @return string the value of the dropdown or &nbsp; if not exists
   **/
   static function getDropdownName($table, $id, $withcomment=0) {
      global $DB, $CFG_GLPI;

      $item = getItemForItemtype(getItemTypeForTable($table));

      if ($item instanceof CommonTreeDropdown) {
         return getTreeValueCompleteName($table,$id,$withcomment);

      } else {
         $name    = "";
         $comment = "";

         if ($id) {
            $query = "SELECT *
                      FROM `". $table ."`
                      WHERE `id` = '". $id ."'";
            /// TODO reviewx comment management...
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
                        $name .= " ".$data["firstname"];
                        if (!empty($data["phone"])) {
                           $comment .= "<br><span class='b'>". __('Phone: ')."</span>".
                                        $data["phone"];
                        }
                        if (!empty($data["phone2"])) {
                           $comment .= "<br><span class='b'>". __('Phone 2: ')."</span>".
                                        $data["phone2"];
                        }
                        if (!empty($data["mobile"])) {
                           $comment .= "<br><span class='b'>".__('Mobile phone: ')."</span>".
                                        $data["mobile"];
                        }
                        if (!empty($data["fax"])) {
                           $comment .= "<br><span class='b'>".__('Fax: ')." </span>".
                                        $data["fax"];
                        }
                        if (!empty($data["email"])) {
                           $comment .= "<br><span class='b'>".__('Email: ')."</span>".
                                        $data["email"];
                        }
                        break;

                     case "glpi_suppliers" :
                        if (!empty($data["phonenumber"])) {
                           $comment .= "<br><span class='b'>". __('Phone: ')."</span> ".
                                        $data["phonenumber"];
                        }
                        if (!empty($data["fax"])) {
                           $comment .= "<br><span class='b'>".__('Fax: ')." </span> ".
                                        $data["fax"];
                        }
                        if (!empty($data["email"])) {
                           $comment .= "<br><span class='b'>".__('Email: ')." </span> ".
                                        $data["email"];
                        }
                        break;

                     case "glpi_netpoints" :
                        $name .= " (".self::getDropdownName("glpi_locations",
                                                                $data["locations_id"]).")";
                        break;
                  }
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
    * @param $table the dropdown table from witch we want values on the select
    * @param $ids array containing the ids to get
    *
    * @return array containing the value of the dropdown or &nbsp; if not exists
   **/
   static function getDropdownArrayNames($table, $ids) {
      global $DB, $CFG_GLPI;

      $tabs = array();

      if (count($ids)) {
         $itemtype = getItemTypeForTable($table);
         $item     = new $itemtype();

         $field    = 'name';
         if ($item instanceof CommonTreeDropdown) {
            $field = 'completename';
         }

         $query = "SELECT `id`, `$field`
                   FROM `$table`
                   WHERE `id` IN (".implode(',',$ids).")";

         if ($result=$DB->query($query)) {
            while ($data=$DB->fetch_assoc($result)) {
               $tabs[$data['id']] = $data[$field];
            }
         }
      }
      return $tabs;
   }


   /**
    * Make a select box for device type
    *
    * @param $name name of the select box
    * @param $value='' default device type
    * @param $types array of types to display
    * @param $used array Already used items ID: not to display in dropdown
    *
    * @return nothing (print out an HTML select box)
   **/
   static function dropdownTypes($name, $value='', $types=array(), $used=array()) {
      global $CFG_GLPI;

      $options = array('' => self::EMPTY_VALUE);

      if (count($types)) {
         foreach ($types as $type) {
            if ($item = getItemForItemtype($type)) {
               $options[$type] = $item->getTypeName();
            }
         }
      }
      asort($options);
      return self::showFromArray($name, $options, array('value' => $value,
                                                        'used'  => $used));
   }


   /**
    * Make a select box for device type
    *
    * @param $name name of the select box
    * @param $itemtype_ref string itemtype reference where to search in itemtype field
    * @param $options array options : may be value (default value) / field (used field to search itemtype)
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
      if ($result=$DB->query($query)) {
         while ($data=$DB->fetch_assoc($result)) {
            $tabs[$data[$p['field']]] = $data[$p['field']];
         }
      }

      return self::dropdownTypes($name, $p['value'],$tabs);
   }


   /**
    * Make a select box for icons
    *
    * @param $myname the name of the HTML select
    * @param $value the preselected value we want
    * @param $store_path path where icons are stored
    *
    * @return nothing (print out an HTML select box)
   **/
   static function dropdownIcons($myname, $value, $store_path) {

      if (is_dir($store_path)) {
         if ($dh = opendir($store_path)) {
            $files = array();

            while (($file = readdir($dh)) !== false) {
               $files[] = $file;
            }

            closedir($dh);
            sort($files);
            echo "<select name='$myname'>";
            echo "<option value=''>".self::EMPTY_VALUE."</option>";

            foreach ($files as $file) {
               if (preg_match("/\.png$/i",$file)) {

                  if ($file == $value) {
                     echo "<option value='$file' selected>".$file;
                  } else {
                     echo "<option value='$file'>".$file;
                  }

                  echo "</option>";
               }
            }

            echo "</select>";

         } else {
            echo "Error reading directory $store_path";
         }

      } else {
         echo "Error $store_path is not a directory";
      }
   }


   /**
    * Dropdown for GMT selection
    *
    * @param $name select name
    * @param $value='' default value
   **/
   static function showGMT($name, $value='') {

      $elements = array(-12, -11, -10, -9, -8, -7, -6, -5, -4, -3.5, -3, -2, -1, 0,
                        1, 2, 3, 3.5, 4, 4.5, 5, 5.5, 6, 6.5, 7, 8, 9, 9.5, 10, 11, 12, 13);

      echo "<select name='$name' id='dropdown_".$name."'>";

      foreach ($elements as $element) {
         if ($element != 0) {
            $display_value = __('GMT').($element > 0?" +":" ").$element." ".
                             ($element > 0? __('hour') :__('hours'));
         } else {
            $display_value = __('GMT');
         }

         $eltvalue = $element*HOUR_TIMESTAMP;
         echo "<option value='$eltvalue'".($eltvalue==$value?" selected ":"").">".$display_value.
              "</option>";
      }
      echo "</select>";
   }


   /**
    * Make a select box for a boolean choice (Yes/No)
    *
    * @param $name select name
    * @param $value preselected value. (default 0)
    * @param $restrict_to allows to display only yes or no in the dropdown (default -1)
    * @param $params Array of optional options (passed to showFromArray)
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
         $optgroup = array(__('Common')
                           => array('Location'        => _n('Location', 'Locations', 2),
                                    'State'           => _n('Status of items', 'Status of items', 2),
                                    'Manufacturer'    => Manufacturer::getTypeName(2)),

                           __('Assistance')
                           => array('ITILCategory'     =>  _n('Category of ticket',
                                                              'Categories of tickets',2),
                                    'TaskCategory'     => _n('Tasks category','Tasks categories', 2),
                                    'SolutionType'     => _n('Solution type', 'Solution types', 2),
                                    'RequestType'      => _n('Request source', 'Request sources', 2),
                                    'SolutionTemplate' => _n('Solution template',
                                                             'Solution templates', 2)),

                           _n('Type', 'Types', 2)
                           => array('ComputerType'         => _n('Computer type',
                                                                 'Computers types', 2),
                                    'NetworkEquipmentType' => _n('Networking equipment type',
                                                                 'Networking equipment types', 2),
                                    'PrinterType'          => _n('Printer type', 'Printer types', 2),
                                    'MonitorType'          => _n('Monitor type', 'Monitor types', 2),
                                    'PeripheralType'       => _n('Devices type', 'Devices types', 2),
                                    'PhoneType'            => _n('Phone type', 'Phones types', 2),
                                    'SoftwareLicenseType'  => _n('License type', 'License types', 2),
                                    'CartridgeItemType'    => _n('Cartridge type',
                                                                 'Cartridge types', 2),
                                    'ConsumableItemType'   => _n('Consumable type',
                                                                 'Consumable types', 2),
                                    'ContractType'         => _n('Contract type',
                                                                 'Contract types', 2),
                                    'ContactType'          => _n('Contact type', 'Contact types', 2),
                                    'DeviceMemoryType'     => _n('Memory type', 'Memory types', 2),
                                    'SupplierType'         => _n('Third party type', 'Third party types', 2),
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
                                 'PrinterModel'          => _n('Printers model',
                                                                'Printers models', 2),
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
                                 'DocumentType'     => _n('Document Type', 'Document Types', 2)),

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
                                                            'Versions of the operating systems', 2),
                                 'OperatingSystemServicePack'
                                                      => _n('Service Pack', 'Service Packs', 2),
                                 'AutoUpdateSystem'   => _n('Update Source', 'Update Sources', 2)),

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
                                 'WifiNetwork'  => _n('Wifi network', 'Wifi networks', 2)),

                        __('Software')
                        => array('SoftwareCategory' => _n('Software category',
                                                          'Software categories', 2)),

                        __('User')
                        => array('UserTitle'     => _n('User title', 'Users titles', 2),
                                 'UserCategory'  => _n('User category', 'User categories', 2)),

                        __('Authorizations assignment rules')
                        => array('RuleRightParameter' => _n('LDAP criteria', 'LDAP criterias', 2)),

                        __('Fields unicity')
                        => array('Fieldblacklist' => _n('Ignored value for the unicity',
                                                        'Ignored values for the unicity', 2))

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

            if (count($optgroup[$label])==0) {
               unset($optgroup[$label]);
            }
         }
      }
      return $optgroup;
   }


   /**
    * Display a menu to select a itemtype which open the search form
    *
    * @param $title string title to display
    * @param $optgroup array (group of dropdown) of array (itemtype => localized name)
    * @param $value='' string URL of selected current value
   **/
   static function showItemTypeMenu($title, $optgroup, $value='') {

      echo "<table class='tab_cadre'>";
      echo "<tr class='tab_bg_1'><td class='b'>&nbsp;".$title."&nbsp;: ";
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
      echo "<input type='submit' name='add' value=\"".__s('Search')."\" class='submit' ";
      echo "onClick='document.location=document.getElementById(\"menu_nav\").value;'";
      echo ">&nbsp;</td></tr>";
      echo "</table><br>";
   }


   /**
    * Display a list to select a itemtype with link to search form
    *
    * @param $optgroup array (group of dropdown) of array (itemtype => localized name)
    */
   static function showItemTypeList($optgroup) {

      echo "<p><a href=\"javascript:showHideDiv('list_nav','img_nav','";
      echo GLPI_ROOT . "/pics/folder.png','" . GLPI_ROOT . "/pics/folder-open.png');\">";
      echo "<img alt='' name='img_nav' src=\"" . GLPI_ROOT . "/pics/folder.png\">&nbsp;";
      echo __('Show all')."</a></p>";

      echo "<div id='list_nav' style='display:none;'>";

      $nb = 0;
      foreach ($optgroup as $label => $dp) {
         $nb += count($dp);
      }
      $step = ($nb>15 ? ($nb/3) : $nb);

      echo "<table><tr class='top'><td><table class='tab_cadre'>";
      $i = 1;

      foreach ($optgroup as $label => $dp) {
         echo "<tr><th>$label</th></tr>\n";

         foreach ($dp as $key => $val) {
            $class="class='tab_bg_4'";
            $itemtype = new $key();
            if ($itemtype->isEntityAssign()) {
               $class="class='tab_bg_2'";
            }
            echo "<tr $class><td><a href='".Toolbox::getItemTypeSearchURL($key)."'>";
            echo "$val</a></td></tr>\n";
            $i++;
         }

         if ($i>=$step) {
            echo "</table></td><td width='25'>&nbsp;</td><td><table class='tab_cadre'>";
            $step += $step;
         }
      }
      echo "</table></td></tr></table></div>";
   }


   /**
    * Dropdown available languages
    *
    * @param $myname select name
    * @param $options array of additionnal options :
    *    - display_none : allow selection of no language
   **/
   static function showLanguages($myname, $options=array()) {
      global $CFG_GLPI;

      $values = array();
      if (isset($options['display_none']) && ($options['display_none'])) {
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
      self::showFromArray($myname,$values,$options);
   }


   /**
    * Print a select with hours
    *
    * Print a select named $name with hours options and selected value $value
    *
    *@param $name string : HTML select name
    *@param $value integer : HTML select selected value
    *@param $limit_planning limit planning to the configuration range (default 0)
    *
    *@return Nothing (display)
    **/
   static function showHours($name, $value, $limit_planning=0) {
      global $CFG_GLPI;

      $begin = 0;
      $end   = 24;
      $step  = $CFG_GLPI["time_step"];
      // Check if the $step is Ok for the $value field
      $split = explode(":",$value);

      // Valid value XX:YY ou XX:YY:ZZ
      if (count($split)==2 || count($split)==3) {
         $min = $split[1];

         // Problem
         if (($min%$step)!=0) {
            // set minimum step
            $step = 5;
         }
      }

      if ($limit_planning) {
         $plan_begin = explode(":",$CFG_GLPI["planning_begin"]);
         $plan_end   = explode(":",$CFG_GLPI["planning_end"]);
         $begin      = (int) $plan_begin[0];
         $end        = (int) $plan_end[0];
      }
      echo "<select name=\"$name\">";

      for ($i=$begin ; $i<$end ; $i++) {
         if ($i<10) {
            $tmp = "0".$i;
         } else {
            $tmp = $i;
         }

         for ($j=0 ; $j<60 ; $j+=$step) {
            if ($j<10) {
               $val = $tmp.":0$j";
            } else {
               $val = $tmp.":$j";
            }

            echo "<option value='$val' ".($value==$val.":00"||$value==$val?" selected ":"").">$val
                  </option>";
         }
      }
      // Last item
      $val = $end.":00";
      echo "<option value='$val' ".($value==$val.":00"||$value==$val?" selected ":"").">$val</option>";
      echo "</select>";
   }


   /**
    * show a dropdown to selec a type
    *
    * @since version 0.83
    *
    * @param $types=='' Types used (default "state_types")
    * @param $options   Array of optional options
    *        name, value, rand, emptylabel, display_emptychoice, on_change
    *
    * @return integer rand for select id
   **/
   static function showItemType($types='', $options=array()) {
      global $CFG_GLPI;

      $params['name']        = 'itemtype';
      $params['value']       = '';
      $params['rand']        = mt_rand();
      $params['on_change']   = '';
      //Parameters about choice 0
      //Empty choice's label
      $params['emptylabel']  = self::EMPTY_VALUE;
      //Display emptychoice ?
      $params['display_emptychoice'] = true;

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
            $options[$type] = $item->getTypeName($type);
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
            $sel = ($key===$params['value'] ? 'selected' : '');
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
    * @param $myname select name
    * @param $value_type default value for the device type (default 0)
    * @param $value default value (default 0)
    * @param $entity_restrict Restrict to a defined entity (default -1)
    * @param $types='' Types used
    * @param $onlyglobal Restrict to global items (false by default)
    *
    * @return nothing (print out an HTML select box)
   **/
   static function showAllItems($myname, $value_type=0, $value=0, $entity_restrict=-1, $types='',
                                $onlyglobal=false) {
      global $CFG_GLPI;

      $rand = self::showItemType($types);
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

         if ($value>0) {
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
    * Dropdown integers
    *
    * @param $myname select name
    * @param $value default value
    * @param $min min value (default 0)
    * @param $max max value (default 100)
    * @param $step step used (default 1)
    * @param $toadd array of values to add at the beginning
    * @param $options array of additionnal options :
   **/
   static function showInteger($myname, $value, $min=0, $max=100, $step=1, $toadd=array(),
                               $options=array()) {

      echo "<select name='$myname'>\n";

      if (count($toadd)) {
         foreach ($toadd as $key => $val) {
            echo "<option value='$key' ".($key==$value?" selected ":"").">";
            echo $val."</option>";
         }
      }

      for ($i=$min ; $i<=$max ; $i+=$step) {
         $txt = $i;
         if (isset($options['unit']) && $i) {
            switch ($options['unit']) {
               case 'month' :
                  //TRANS: %d is a number of months
                  $txt = sprintf(_n('%d month', '%d months', $i), $i);
                  break;
            }
         }
         echo "<option value='$i' ".($i==$value?" selected ":"").">$txt</option>";
      }
      echo "</select>";

   }

   /**
    * Dropdown integers
    *
    * @since version 0.83
    *
    * @param $myname select name
    * @param $options array of options
    *    - value : default value
    *    - min : min value : default 0
    *    - max : max value : default DAY_TIMESTAMP
    *    - value : default value
   **/
   static function showTimeStamp($myname, $options=array()) {

      $params['value']       = 0;
      $params['min']         = 0;
      $params['max']         = DAY_TIMESTAMP;
      $params['step']        = 15*MINUTE_TIMESTAMP;
      $params['emptylabel']  = self::EMPTY_VALUE;

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
      $params['value'] = floor(($params['value'])/$params['step'])*$params['step'];

      $values = array(0  => $params['emptylabel']);
      for ($i = $params['min'] ; $i <= $params['max']; $i+=$params['step']) {
         $day        = floor($i/DAY_TIMESTAMP);
         $hour       = floor(($i%DAY_TIMESTAMP)/HOUR_TIMESTAMP);
         $minute     = floor(($i%HOUR_TIMESTAMP)/MINUTE_TIMESTAMP);
         if ($minute == 0) {
            $minute='00';
         }
         $values[$i] = '';
         if ($day > 0) {
            if ($hour > 0 || $minute > 0) {
               //TRANS: %1$d is the number of days, %2$d the number of hours,
               //       %3$s the number of minutes : display 1 day 3h15
               $values[$i] = sprintf(_n('%1$d day %2$dh%3$s','%1$d days %2$dh%3$s', $day),
                                     $day, $hour, $minute);
            } else {
               $values[$i] = sprintf(_n('%d day','%d days',$day), $day);
            }

         } else if ($hour > 0 || $minute > 0) {
            //TRANS: %1$d the number of hours, %2$s the number of minutes : display 3h15
            $values[$i] = sprintf(__('%1$dh%2$s'), $hour, $minute);
         }
      }

      return Dropdown::showFromArray("$myname", $values, array('value' => $params['value']));
   }


   /**
    * Private / Public switch for items which may be assign to a user and/or an entity
    *
    * @param $is_private default is private ?
    * @param $entity working entity ID
    * @param $is_recursive is the item recursive ?
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
    * @param $name select name
    * @param $elements array of elements to display
    * @param $options array of options
    *
    * Parameters which could be used in options array :
    *    - value : integer / preselected value (default 0)
    *    - used : array / Already used items ID: not to display in dropdown (default empty)
    *    - readonly : boolean / used as a readonly item (default false)
    *    - on_change : string / value to transmit to "onChange"
   **/
   static function showFromArray($name, $elements, $options=array()) {

      $param['value']    = '';
      $param['used']     = array();
      $param['readonly'] = false;
      $param['on_change']   = '';

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $param[$key] = $val;
         }
      }

      // readonly mode
      if ($param['readonly']) {
         echo "<input type='hidden' name='$name' value='".$param['value']."'>";

         if (isset($elements[$param['value']])) {
            echo $elements[$param['value']];
         }

      } else {
         $rand = mt_rand();

         echo "<select name='$name' id='dropdown_".$name.$rand."'";

         if (!empty($param["on_change"])) {
            echo " onChange='".$param["on_change"]."'";
         }

         echo '>';

         foreach ($elements as $key => $val) {
            if (!isset($param['used'][$key])) {
               echo "<option value='".$key."'".($param['value']==$key?" selected ":"").">".$val.
                    "</option>";
            }
         }

         echo "</select>";
         return $rand;
      }
   }


   /**
    * Dropdown for global item management
    *
    * @param $ID item ID
    * @param attrs an array which contains the extra paramters
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

      if ($params['value'] && empty($params['withtemplate'])) {
         echo __('Global management');

         if ($params['management_restrict'] == 2) {
            echo "&nbsp;<a title=\"".__s('Duplicate the element as many times as there are connections').
                 "\" href=\"javascript:confirmAction('".
                 __s('Do you really want to use unitary management for this item ?')."\\n".
                 __s('Duplicate the element as many times as there are connections').
                 "','".$params['target']."?unglobalize=unglobalize&amp;id=$ID')\">".
                 __('Use unitary management')."</a>&nbsp;";

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
    * @param $itemtype string name of the class
    * @param $input array of value to import
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
    * @param $itemtype string name of the class
    * @param $value string : Value of the new dropdown.
    * @param $entities_id int : entity in case of specific dropdown (default -1)
    * @param $external_params array
    * @param $comment=''
    * @param $add if true, add it if not found. if false, just check if exists (true by default)
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
    * Dropdown of actions for massive action
    *
    * @param $itemtype item type
    * @param $is_deleted massive action for deleted items ? (default 0)
    * @param $extraparams array of extra parameters
   **/
   static function showForMassiveAction($itemtype, $is_deleted=0, $extraparams=array()) {
      global $CFG_GLPI,$PLUGIN_HOOKS;

      /// TODO include in CommonDBTM defining only getAdditionalMassiveAction in sub classes
      /// for specific actions (return a array of action name and title)

      if (!($item = getItemForItemtype($itemtype))) {
         return false;
      }

      if ($itemtype == 'NetworkPort') {
         echo "<select name='massiveaction' id='massiveaction'>";

         echo "<option value='-1' selected>".self::EMPTY_VALUE."</option>";
         echo "<option value='delete'>".__('Delete')."</option>";
         echo "<option value='assign_vlan'>".__('Associate a VLAN')."</option>";
         echo "<option value='unassign_vlan'>".__('Dissociate a VLAN')."</option>";
         // Interest of this massive action ?
//          echo "<option value='move'>".__('Move')."</option>";
         echo "</select>";

         $params = array('action'   => '__VALUE__',
                         'itemtype' => $itemtype);

         Ajax::updateItemOnSelectEvent("massiveaction", "show_massiveaction",
                                       $CFG_GLPI["root_doc"]."/ajax/dropdownMassiveActionPorts.php",
                                       $params);

         echo "<span id='show_massiveaction'>&nbsp;</span>\n";

      } else {
         $infocom = new Infocom();
         $isadmin = $item->canUpdate();

         echo "<select name='massiveaction' id='massiveaction'>";
         echo "<option value='-1' selected>".self::EMPTY_VALUE."</option>";
         if (!in_array($itemtype,$CFG_GLPI["massiveaction_noupdate_types"])
             && (($isadmin && $itemtype != 'Ticket')
                 || (in_array($itemtype,$CFG_GLPI["infocom_types"]) && $infocom->canUpdate())
                 || ($itemtype == 'Ticket' && Session::haveRight('update_ticket',1)))) {

            //TRANS: select action 'update' (before doing it)
            echo "<option value='update'>"._x('button', 'Update')."</option>";
         }

         if (in_array($itemtype,$CFG_GLPI["infocom_types"]) && $infocom->canCreate() ) {
            echo "<option value='activate_infocoms'>".
                  __('Enable the financial and administrative information')."</option>";
         }

         if ($is_deleted && !in_array($itemtype,$CFG_GLPI["massiveaction_nodelete_types"])) {
            if ($isadmin) {
               echo "<option value='purge'>".__('Purge')."</option>";
               echo "<option value='restore'>".__('Restore')."</option>";
            }

         } else {
            // No delete for entities and tracking of not have right
            if (!in_array($itemtype,$CFG_GLPI["massiveaction_nodelete_types"])
                && (($isadmin && $itemtype != 'Ticket')
                    || ($itemtype == 'Ticket' && Session::haveRight('delete_ticket',1)))) {

               if ($item->maybeDeleted()) {
                  echo "<option value='delete'>".__('Delete')."</option>";
               } else {
                  echo "<option value='purge'>".__('Purge')."</option>";
               }
            }
            if ($isadmin && in_array($itemtype, array('Phone', 'Printer', 'Peripheral',
                                                      'Monitor'))) {

               echo "<option value='connect'>".__('Connect')."</option>";
               echo "<option value='disconnect'>".__('Disconnect')."</option>";
            }

            if (in_array($itemtype,$CFG_GLPI["document_types"])) {
               $doc = new Document();
               if ($doc->canView()) {
                  echo "<option value='add_document'>".__('Add a document')."</option>";
               }
            }

            if (in_array($itemtype,$CFG_GLPI["contract_types"])) {
               $contract = new Contract();
               if ($contract->canUpdate()) {
                  echo "<option value='add_contract'>".__('Add a contract')."</option>";
               }
            }

            if (Session::haveRight('transfer','r')
                && Session::isMultiEntitiesMode()
                && in_array($itemtype, array('CartridgeItem', 'Computer', 'ConsumableItem',
                                             'Contact', 'Contract', 'Document', 'Group',
                                             'Link', 'Monitor', 'NetworkEquipment',
                                             'Peripheral', 'Phone', 'Printer', 'Problem',
                                             'Software', 'SoftwareLicense', 'Supplier', 'Ticket'))
                && $isadmin) {

               echo "<option value='add_transfer_list'>".__('Add to transfer list')."</option>";
            }

            switch ($itemtype) {
               case 'Software' :
                  if ($isadmin && countElementsInTable("glpi_rules",
                                                       "sub_type='RuleSoftwareCategory'")>0) {

                     echo "<option value='compute_software_category'>".
                            __('Recalculate the category')."</option>";
                  }

                  if (Session::haveRight("rule_dictionnary_software","w")
                      && countElementsInTable("glpi_rules","sub_type='RuleDictionnarySoftware'")>0) {

                     echo "<option value='replay_dictionnary'>".
                            __('Replay the dictionary rules')."</option>";
                  }
                  break;

               case 'Computer' :
                  if ($isadmin) {
                     echo "<option value='connect_to_computer'>".__('Connect')."</option>";
                     echo "<option value='install'>".__('Install')."</option>";

                     if ($CFG_GLPI['use_ocs_mode']) {

                        if (Session::haveRight("ocsng","w")
                            || Session::haveRight("sync_ocsng","w")) {
                           echo "<option value='force_ocsng_update'>".
                                  __('Force synchronization')."</option>";
                        }

                        echo "<option value='unlock_ocsng_field'>".
                               __('Unlock the locked field for OCSNG')."</option>";
                        echo "<option value='unlock_ocsng_monitor'>".
                               __('Unlock the locked monitor for OCSNG')."</option>";
                        echo "<option value='unlock_ocsng_peripheral'>".
                               __('Unlock the locked device for OCSNG')."</option>";
                        echo "<option value='unlock_ocsng_printer'>".
                               __('Unlock the locked printer for OCSNG')."</option>";
                        echo "<option value='unlock_ocsng_software'>".
                               __('Unlock the locked software for OCSNG')."</option>";
                        echo "<option value='unlock_ocsng_ip'>".
                               __('Unlock the locked IP for OCSNG')."</option>";
                        echo "<option value='unlock_ocsng_disk'>".
                               __('Unlock the locked volume for OCSNG')."</option>";
                     }
                  }
                  break;

               case 'Supplier' :
                  if ($isadmin) {
                     echo "<option value='add_contact'>".__('Add Contact...')."</option>";
                  }
                  break;

               case 'Calendar' :
                  echo "<option value='duplicate'>".__('Duplicate')."</option>";
                  break;

               case 'Contact' :
                  if ($isadmin) {
                     echo "<option value='add_enterprise'>".__('Add Supplier...')."</option>";
                  }
                  break;

               case 'User' :
                  if ($isadmin) {
                     echo "<option value='add_group'>".__('Associate to a group')."</option>";
                     echo "<option value='add_userprofile'>".__('Associate to a profile')."</option>";
                  }

                  if (Session::haveRight("user_authtype","w")) {
                     echo "<option value='change_authtype'>".
                            __('Change of the authentication method')."</option>";
                     echo "<option value='force_user_ldap_update'>".
                            __('Force synchronization')."</option>";
                  }
                  break;

               case 'Ticket' :
                  $tmp = new TicketFollowup();
                  if ($tmp->canCreate()
                      && $_SESSION['glpiactiveprofile']['interface'] == 'central') {
                     echo "<option value='add_followup'>".__('Add a new followup')."</option>";
                  }

                  $tmp = new TicketTask();
                  if ($tmp->canCreate()) {
                     echo "<option value='add_task'>".__('Add a new task')."</option>";
                  }

                  $tmp = new TicketValidation();
                  if ($tmp->canCreate()) {
                     echo "<option value='submit_validation'>".__('Approval request')."</option>";
                  }

                  if (Session::haveRight("update_ticket","1")) {
                     echo "<option value='add_actor'>".__('Add an actor')."</option>";
                     echo "<option value='link_ticket'>".__('Link tickets')."</option>";
                  }

                  break;

               case 'CronTask' :
                  echo "<option value='reset'>".__('Reset last run');
                  echo "</option>";
                  break;

               case 'NotImportedEmail':
                     echo "<option value='delete_email'>".__('Delete emails')."</option>";
                     echo "<option value='import_email'>".__('Import')."</option>";
                  break;

               case 'Problem' :
                  $tmp = new ProblemTask();
                  if ($tmp->canCreate()) {
                     echo "<option value='add_task'>".__('Add a new task')."</option>";
                  }
                  if (Session::haveRight("edit_all_problem","1")) {
                     echo "<option value='add_actor'>".__('Add an actor')."</option>";
                  }

                  break;

            }

            if (($item instanceof CommonTreeDropdown)
                && (!($item instanceof CommonImplicitTreeDropdown))) {
               if ($isadmin) {
                  echo "<option value='move_under'>".__('Move')."</option>";
               }
            }

            if ($itemtype!='Entity'
                && $itemtype!='Calendar'
                && ($item instanceof CommonDropdown)
                && $item->maybeRecursive()) {

               if ($isadmin && (count($_SESSION['glpiactiveentities'])>1)) {
                  echo "<option value='merge'>".__('Transfer and Merge');
                  echo "</option>";
               }
            }

            // Plugin Specific actions
            if (isset($PLUGIN_HOOKS['use_massive_action'])) {
               foreach ($PLUGIN_HOOKS['use_massive_action'] as $plugin => $val) {
                  $actions = Plugin::doOneHook($plugin,'MassiveActions',$itemtype);

                  if (count($actions)) {
                     foreach ($actions as $key => $val) {
                        echo "<option value = '$key'>$val</option>";
                     }
                  }
               }
            }
         }
         echo "</select>";

         $params = array('action'     => '__VALUE__',
                         'is_deleted' => $is_deleted,
                         'itemtype'   => $itemtype);

         if (count($extraparams)) {
            foreach ($extraparams as $key => $val) {
               $params['extra_'.$key] = $val;
            }
         }

         Ajax::updateItemOnSelectEvent("massiveaction", "show_massiveaction",
                                       $CFG_GLPI["root_doc"]."/ajax/dropdownMassiveAction.php",
                                       $params);

         echo "<span id='show_massiveaction'>&nbsp;</span>\n";
      }
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
         default :
            return "";

         case 0 :
            return __('Unit management');

         case 1 :
            return __('Global management');
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
    * @param $onchange='' String, optional, for ajax
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

      for ($i=5 ; $i<20 ; $i+=5) {
         echo "<option value='$i' ".(($list_limit==$i)?" selected ":"").">$i</option>";
      }
      for ($i=20 ; $i<50 ; $i+=10) {
         echo "<option value='$i' ".(($list_limit==$i)?" selected ":"").">$i</option>";
      }
      for ($i=50 ; $i<250 ; $i+=50) {
         echo "<option value='$i' ".(($list_limit==$i)?" selected ":"").">$i</option>";
      }
      for ($i=250 ; $i<1000 ; $i+=250) {
         echo "<option value='$i' ".(($list_limit==$i)?" selected ":"").">$i</option>";
      }
      for ($i=1000 ; $i<5000 ; $i+=1000) {
         echo "<option value='$i' ".(($list_limit==$i)?" selected ":"").">$i</option>";
      }
      for ($i=5000 ; $i<=10000 ; $i+=5000) {
         echo "<option value='$i' ".(($list_limit==$i)?" selected ":"").">$i</option>";
      }

      echo "<option value='9999999' ".(($list_limit==9999999)?" selected ":"").">9999999</option>";
      echo "</select>";
   }

}
?>