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

class Dropdown {

   //Empty value displayed in a dropdown
   const EMPTY_VALUE = '-----';

   /**
    * Print out an HTML "<select>" for a dropdown with preselected value
    *
    * @param $itemtype        itemtype used for create dropdown
    * @param $options   array of possible options:
    *    - name                 : string / name of the select (default is depending itemtype)
    *    - value                : integer / preselected value (default -1)
    *    - comments             : boolean / is the comments displayed near the dropdown (default true)
    *    - toadd                : array / array of specific values to add at the begining
    *    - entity               : integer or array / restrict to a defined entity or array of entities
    *                                                (default -1 : no restriction)
    *    - entity_sons          : boolean / if entity restrict specified auto select its sons
    *                                       only available if entity is a single value not an array
    *                                       (default false)
    *    - toupdate             : array / Update a specific item on select change on dropdown
    *                                     (need value_fieldname, to_update,
    *                                      url (see Ajax::updateItemOnSelectEvent for information)
    *                                      and may have moreparams)
    *    - used                 : array / Already used items ID: not to display in dropdown
    *                                    (default empty)
    *    - on_change            : string / value to transmit to "onChange"
    *    - rand                 : integer / already computed rand value
    *    - condition            : string / aditional SQL condition to limit display
    *    - displaywith          : array / array of field to display with request
    *    - emptylabel           : Empty choice's label (default self::EMPTY_VALUE)
    *    - display_emptychoice  : Display emptychoice ? (default true)
    *    - display              : boolean / display or get string (default true)
    *    - width                : specific width needed (default auto adaptive)
    *    - permit_select_parent : boolean / for tree dropdown permit to see parent items
    *                                       not available by default (default false)
    *    - specific_tags        : array of HTML5 tags to add the the field
    *
    * @return boolean : false if error and random id if OK
   **/
   static function show($itemtype, $options=array()) {
      global $DB, $CFG_GLPI;

      if ($itemtype && !($item = getItemForItemtype($itemtype))) {
         return false;
      }

      $table = $item->getTable();

      $params['name']                 = $item->getForeignKeyField();
      $params['value']                = (($itemtype == 'Entity') ? $_SESSION['glpiactive_entity'] : '');
      $params['comments']             = true;
      $params['entity']               = -1;
      $params['entity_sons']          = false;
      $params['toupdate']             = '';
      $params['width']                = '';
      $params['used']                 = array();
      $params['toadd']                = array();
      $params['on_change']            = '';
      $params['condition']            = '';
      $params['rand']                 = mt_rand();
      $params['displaywith']          = array();
      //Parameters about choice 0
      //Empty choice's label
      $params['emptylabel']           = self::EMPTY_VALUE;
      //Display emptychoice ?
      $params['display_emptychoice']  = ($itemtype != 'Entity');
      $params['display']              = true;
      $params['permit_select_parent'] = false;
      $params['addicon']              = true;
      $params['specific_tags']        = array();


      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }
      $output       = '';
      $name         = $params['emptylabel'];
      $comment      = "";

      // Check default value for dropdown : need to be a numeric
      if ((strlen($params['value']) == 0) || !is_numeric($params['value']) && $params['value'] != 'mygroups') {
         $params['value'] = 0;
      }

      if (isset($params['toadd'][$params['value']])) {
         $name = $params['toadd'][$params['value']];
      } else if (($params['value'] > 0)
                 || (($itemtype == "Entity")
                     && ($params['value'] >= 0))) {
         $tmpname = self::getDropdownName($table, $params['value'], 1);

         if ($tmpname["name"] != "&nbsp;") {
            $name    = $tmpname["name"];
            $comment = $tmpname["comment"];
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


      $field_id = Html::cleanId("dropdown_".$params['name'].$params['rand']);

      // Manage condition
      if (!empty($params['condition'])) {
        $params['condition'] = static::addNewCondition($params['condition']);
      }

      if (!$item instanceof CommonTreeDropdown) {
         $name = Toolbox::unclean_cross_side_scripting_deep($name);
      }
      $p = array('value'                => $params['value'],
                 'valuename'            => $name,
                 'width'                => $params['width'],
                 'itemtype'             => $itemtype,
                 'display_emptychoice'  => $params['display_emptychoice'],
                 'displaywith'          => $params['displaywith'],
                 'emptylabel'           => $params['emptylabel'],
                 'condition'            => $params['condition'],
                 'used'                 => $params['used'],
                 'toadd'                => $params['toadd'],
                 'entity_restrict'      => (is_array($params['entity']) ? json_encode(array_values($params['entity'])) : $params['entity']),
                 'on_change'            => $params['on_change'],
                 'permit_select_parent' => $params['permit_select_parent'],
                 'specific_tags'        => $params['specific_tags'],
                );

      $output = "<span class='no-wrap'>";
      $output.= Html::jsAjaxDropdown($params['name'], $field_id,
                                     $CFG_GLPI['root_doc']."/ajax/getDropdownValue.php",
                                     $p);
      // Display comment
      if ($params['comments']) {
         $comment_id      = Html::cleanId("comment_".$params['name'].$params['rand']);
         $link_id         = Html::cleanId("comment_link_".$params['name'].$params['rand']);
         $options_tooltip = array('contentid' => $comment_id,
                                  'linkid'    => $link_id,
                                  'display'   => false);

         if ($item->canView()) {
             if ($params['value']
                 && $item->getFromDB($params['value'])
                 && $item->canViewItem()) {
               $options_tooltip['link']       = $item->getLinkURL();
            } else {
               $options_tooltip['link']       = $item->getSearchURL();
            }
            $options_tooltip['linktarget'] = '_blank';
         }

         $output .= "&nbsp;".Html::showToolTip($comment,$options_tooltip);

         if (($item instanceof CommonDropdown)
             && $item->canCreate()
             && !isset($_REQUEST['_in_modal'])
             && $params['addicon']) {

               $output .= "<img alt='' title=\"".__s('Add')."\" src='".$CFG_GLPI["root_doc"].
                            "/pics/add_dropdown.png' style='cursor:pointer; margin-left:2px;'
                            onClick=\"".Html::jsGetElementbyID('add_dropdown'.$params['rand']).".dialog('open');\">";
               $output .= Ajax::createIframeModalWindow('add_dropdown'.$params['rand'],
                                                        $item->getFormURL(),
                                                        array('display' => false));
         }
         // Display specific Links
         if ($itemtype == "Supplier") {
            if ($item->getFromDB($params['value'])) {
               $output .= $item->getLinks();
            }
         }

         if (($itemtype == 'ITILCategory')
             && Session::haveRight('knowbase', READ)) {

            if ($params['value'] && $item->getFromDB($params['value'])) {
               $output .= '&nbsp;'.$item->getLinks();
            }
         }
         $paramscomment = array('value' => '__VALUE__',
                                'table' => $table);
         if ($item->canView()) {
            $paramscomment['withlink'] = $link_id;
         }

         $output .= Ajax::updateItemOnSelectEvent($field_id, $comment_id,
                                                  $CFG_GLPI["root_doc"]."/ajax/comments.php",
                                                  $paramscomment, false);
      }
      $output .= Ajax::commonDropdownUpdateItem($params, false);
      if ($params['display']) {
         echo $output;
         return $params['rand'];
      }
      $output .= "</span>";
      return $output;
   }

    static function addNewCondition($condition) {
        $condition = Toolbox::cleanNewLines($condition);
        $sha1=sha1($condition);
        $_SESSION['glpicondition'][$sha1] = $condition;
        return $sha1;
    }

   /**
    * Get the value of a dropdown
    *
    * Returns the value of the dropdown from $table with ID $id.
    *
    * @param $table        the dropdown table from witch we want values on the select
    * @param $id           id of the element to get
    * @param $withcomment  give array with name and comment (default 0)
    * @param $translate    (true by default)
    *
    * @return string the value of the dropdown or &nbsp; if not exists
   **/
   static function getDropdownName($table, $id, $withcomment=0, $translate=true) {
      global $DB, $CFG_GLPI;

      $item = getItemForItemtype(getItemTypeForTable($table));

      if ($item instanceof CommonTreeDropdown) {
         return getTreeValueCompleteName($table,$id,$withcomment, $translate);
      }

      $name    = "";
      $comment = "";

      if ($id) {
         $SELECTNAME    = "'' AS transname";
         $SELECTCOMMENT = "'' AS transcomment";
         $JOIN          = '';
         if  ($translate) {
            if (Session::haveTranslations(getItemTypeForTable($table), 'name')) {
               $SELECTNAME = "`namet`.`value` AS transname";
               $JOIN       .= " LEFT JOIN `glpi_dropdowntranslations` AS namet
                                 ON (`namet`.`itemtype` = '".getItemTypeForTable($table)."'
                                     AND `namet`.`items_id` = `$table`.`id`
                                     AND `namet`.`language` = '".$_SESSION['glpilanguage']."'
                                     AND `namet`.`field` = 'name')";
            }
            if (Session::haveTranslations(getItemTypeForTable($table), 'comment')) {
               $SELECTCOMMENT = "`namec`.`value` AS transcomment";
               $JOIN          .= " LEFT JOIN `glpi_dropdowntranslations` AS namec
                                    ON (`namec`.`itemtype` = '".getItemTypeForTable($table)."'
                                        AND `namec`.`items_id` = `$table`.`id`
                                        AND `namec`.`language` = '".$_SESSION['glpilanguage']."'
                                              AND `namec`.`field` = 'comment')";
            }

         }

         $query = "SELECT `$table`.*, $SELECTNAME, $SELECTCOMMENT
                   FROM `$table`
                   $JOIN
                   WHERE `$table`.`id` = '$id'";

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
               if ($translate && !empty($data['transname'])) {
                  $name = $data['transname'];
               } else {
                  $name = $data["name"];
               }
               if (isset($data["comment"])) {
                  if ($translate && !empty($data['transcomment'])) {
                     $comment = $data['transcomment'];
                  } else {
                     $comment = $data["comment"];
                  }
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
                                                           $data["locations_id"], false, $translate));
                     break;
               }
            }
         }
      }

      if (empty($name)) {
         $name = "&nbsp;";
      }
/*
      if (!$item instanceof CommonTreeDropdown) {
         $search  = array("/\&lt;/","/\&gt;/");
         $replace = array("<",">");
         $name    = preg_replace($search, $replace, $name);
      }*/
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

      $params['value']               = '';
      $params['used']                = array();
      $params['emptylabel']          = self::EMPTY_VALUE;
      $params['display']             = true;
      $params['width']               = '80%';
      $params['display_emptychoice'] = true;
      $params['rand']                = mt_rand();

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      $values = array();
      if (count($types)) {
         foreach ($types as $type) {
            if ($item = getItemForItemtype($type)) {
               $values[$type] = $item->getTypeName(1);
            }
         }
      }
      asort($values);
      return self::showFromArray($name, $values, $params);
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

            $values = array('' => self::EMPTY_VALUE);
            foreach ($files as $file) {
               if (preg_match("/\.png$/i",$file)) {
                  $values[$file] = $file;
               }
            }
            Dropdown::showFromArray($myname, $values,
                                    array('value' => $value));

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

      $values = array();
      foreach ($elements as $element) {
         if ($element != 0) {
            $values[$element*HOUR_TIMESTAMP] = sprintf(__('%1$s %2$s'), __('GMT'),
                                                       sprintf(_n('%s hour', '%s hours', $element),
                                                               $element));
         } else {
            $display_value                   = __('GMT');
            $values[$element*HOUR_TIMESTAMP] = __('GMT');
         }
      }
      Dropdown::showFromArray($name, $values, array('value' => $value));
   }


   /**
    * Make a select box for a boolean choice (Yes/No) or display a checkbox. Add a
    * 'use_checkbox' = true to the $params array to display a checkbox instead a select box
    *
    * @param $name               select name
    * @param $value              preselected value. (default 0)
    * @param $restrict_to        allows to display only yes or no in the dropdown (default -1)
    * @param $params       Array of optional options (passed to showFromArray)
    *
    * @return rand value
   **/
   static function showYesNo($name, $value=0, $restrict_to=-1, $params=array()) {

     if (!array_key_exists ('use_checkbox', $params)) {
        // TODO: switch to true when Html::showCheckbox() is validated
        $params['use_checkbox'] = false;
      }
      if ($params['use_checkbox']) {

         if (!empty($params['rand'])) {
            $rand = $params['rand'];
         } else {
            $rand = mt_rand();
         }

         $options = array('name' => $name,
                          'id'   => Html::cleanId("dropdown_".$name.$rand));

         switch ($restrict_to) {
            case 0 :
               $options['checked']  = false;
               $options['readonly'] = true;
               break;

            case 1 :
               $options['checked']  = true;
               $options['readonly'] = true;
               break;

            default :
               $options['checked']  = ($value ? 1 : 0);
               $options['readonly'] = false;
               break;
         }

         Html::showCheckbox($options);
         return $rand;
      }

      if ($restrict_to != 0) {
         $options[0] = __('No');
      }

      if ($restrict_to != 1) {
         $options[1] = __('Yes');
      }

      $params['value'] = $value;
      $params['width'] = "65px";
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

      if (!Session::haveRightsOr('device', array(CREATE, UPDATE, PURGE))) {
         return array();
      }

      if (is_null($optgroup)) {
         $devices = array();
         foreach (CommonDevice::getDeviceTypes() as $device_type) {
            $devices[$device_type] = $device_type::getTypeName(Session::getPluralNumber());
         }
         asort($devices);
         $optgroup = array(_n('Component', 'Components', Session::getPluralNumber()) => $devices);
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
                        => array('Location'               => _n('Location', 'Locations', Session::getPluralNumber()),
                                 'State'                  => _n('Status of items',
                                                                'Statuses of items', Session::getPluralNumber()),
                                 'Manufacturer'           => _n('Manufacturer', 'Manufacturers', Session::getPluralNumber()),
                                 'Blacklist'              => _n('Blacklist','Blacklists', Session::getPluralNumber()),
                                 'BlacklistedMailContent' => __('Blacklisted mail content')
                                ),

                    __('Assistance')
                        => array('ITILCategory'     => _n('Ticket category', 'Ticket categories', Session::getPluralNumber()),
                                 'TaskCategory'     => _n('Task category','Task categories', Session::getPluralNumber()),
                                 'SolutionType'     => _n('Solution type', 'Solution types', Session::getPluralNumber()),
                                 'RequestType'      => _n('Request source', 'Request sources', Session::getPluralNumber()),
                                 'SolutionTemplate' => _n('Solution template',
                                                          'Solution templates', Session::getPluralNumber()),
                                 'ProjectState'     => _n('Project state', 'Project states', Session::getPluralNumber()),
                                 'ProjectType'      => _n('Project type', 'Project types', Session::getPluralNumber()),
                                 'ProjectTaskType'  => _n('Project tasks type',
                                                          'Project tasks types', Session::getPluralNumber()),
                                ),

                    _n('Type', 'Types', Session::getPluralNumber())
                        => array('ComputerType'         => _n('Computer type', 'Computers types', Session::getPluralNumber()),
                                 'NetworkEquipmentType' => _n('Networking equipment type',
                                                              'Networking equipment types', Session::getPluralNumber()),
                                 'PrinterType'          => _n('Printer type', 'Printer types', Session::getPluralNumber()),
                                 'MonitorType'          => _n('Monitor type', 'Monitor types', Session::getPluralNumber()),
                                 'PeripheralType'       => _n('Devices type', 'Devices types', Session::getPluralNumber()),
                                 'PhoneType'            => _n('Phone type', 'Phones types', Session::getPluralNumber()),
                                 'SoftwareLicenseType'  => _n('License type', 'License types', Session::getPluralNumber()),
                                 'CartridgeItemType'    => _n('Cartridge type',
                                                              'Cartridge types', Session::getPluralNumber()),
                                 'ConsumableItemType'   => _n('Consumable type',
                                                              'Consumable types', Session::getPluralNumber()),
                                 'ContractType'         => _n('Contract type', 'Contract types', Session::getPluralNumber()),
                                 'ContactType'          => _n('Contact type', 'Contact types', Session::getPluralNumber()),
                                 'DeviceMemoryType'     => _n('Memory type', 'Memory types', Session::getPluralNumber()),
                                 'SupplierType'         => _n('Third party type',
                                                              'Third party types', Session::getPluralNumber()),
                                 'InterfaceType'        => _n('Interface type (Hard drive...)',
                                                              'Interface types (Hard drive...)', Session::getPluralNumber()) ,
                                 'DeviceCaseType'       => _n('Case type', 'Case types', Session::getPluralNumber()),
                                 'PhonePowerSupply'     => _n('Phone power supply type',
                                                              'Phones power supply types', Session::getPluralNumber()),
                                 'Filesystem'           => _n('File system', 'File systems', Session::getPluralNumber())
                                ),

                    __('Model')
                        => array('ComputerModel'         => _n('Computer model',
                                                               'Computer models', Session::getPluralNumber()),
                                 'NetworkEquipmentModel' => _n('Networking equipment model',
                                                               'Networking equipment models', Session::getPluralNumber()),
                                 'PrinterModel'          => _n('Printer model', 'Printer models', Session::getPluralNumber()),
                                 'MonitorModel'          => _n('Monitor model', 'Monitor models', Session::getPluralNumber()),
                                 'PeripheralModel'       => _n('Peripheral model',
                                                               'Peripheral models', Session::getPluralNumber()),
                                 'PhoneModel'            =>  _n('Phone model', 'Phone models', Session::getPluralNumber())
                                ),

                    _n('Virtual machine', 'Virtual machines', Session::getPluralNumber())
                        => array('VirtualMachineType'   => _n('Virtualization system',
                                                              'Virtualization systems', Session::getPluralNumber()),
                                 'VirtualMachineSystem' => _n('Virtualization model',
                                                              'Virtualization models', Session::getPluralNumber()),
                                 'VirtualMachineState'  => _n('State of the virtual machine',
                                                              'States of the virtual machine', Session::getPluralNumber())
                                ),

                    __('Management')
                        => array('DocumentCategory' => _n('Document heading',
                                                          'Document headings', Session::getPluralNumber()),
                                 'DocumentType'     => _n('Document type', 'Document types', Session::getPluralNumber())
                                ),

                    __('Tools')
                        => array('KnowbaseItemCategory' => _n('Knowledge base category',
                                                              'Knowledge base categories', Session::getPluralNumber())
                                ),

                    __('Calendar')
                        => array('Calendar' => _n('Calendar', 'Calendars', Session::getPluralNumber()),
                                 'Holiday'  => _n('Close time', 'Close times', Session::getPluralNumber())
                                ),

                    _n('Operating system', 'Operating systems', Session::getPluralNumber())
                        => array('OperatingSystem'     => _n('Operating system',
                                                             'Operating systems', Session::getPluralNumber()),
                                 'OperatingSystemVersion'
                                                      => _n('Version of the operating system',
                                                            'Versions of the operating system', Session::getPluralNumber()),
                                 'OperatingSystemServicePack'
                                                      => _n('Service pack', 'Service packs', Session::getPluralNumber()),
                                 'AutoUpdateSystem'   => _n('Update source', 'Update sources', Session::getPluralNumber())
                                ),

                    __('Networking')
                        => array('NetworkInterface'         => _n('Network interface',
                                                                  'Network interfaces', Session::getPluralNumber()),
                                 'NetworkEquipmentFirmware' => _n('Firmware', 'Firmwares', Session::getPluralNumber()),
                                 'Netpoint'                 => _n('Network outlet',
                                                                  'Network outlets', Session::getPluralNumber()),
                                 'Domain'                   => _n('Domain', 'Domains', Session::getPluralNumber()),
                                 'Network'                  => _n('Network', 'Networks', Session::getPluralNumber()),
                                 'Vlan'                     => __('VLAN')
                                ),

                    __('Internet')
                        => array('IPNetwork'    => _n('IP network', 'IP networks', Session::getPluralNumber()),
                                 'FQDN'         => _n('Internet domain', 'Internet domains', Session::getPluralNumber()),
                                 'WifiNetwork'  => _n('Wifi network', 'Wifi networks', Session::getPluralNumber()),
                                 'NetworkName'  => _n('Network name', 'Network names', Session::getPluralNumber())
                                ),

                    _n('Software', 'Software', 1)
                        => array('SoftwareCategory' => _n('Software category',
                                                          'Software categories', Session::getPluralNumber())
                                ),

                    __('User')
                        => array('UserTitle'     => _n('User title', 'Users titles', Session::getPluralNumber()),
                                 'UserCategory'  => _n('User category', 'User categories', Session::getPluralNumber())
                                ),

                    __('Authorizations assignment rules')
                        => array('RuleRightParameter' => _n('LDAP criterion', 'LDAP criteria', Session::getPluralNumber())
                                ),

                    __('Fields unicity')
                        => array('Fieldblacklist' => _n('Ignored value for the unicity',
                                                        'Ignored values for the unicity', Session::getPluralNumber())
                                ),

                    __('External authentications')
                        => array('SsoVariable' => _n('Field storage of the login in the HTTP request',
                                                     'Fields storage of the login in the HTTP request',
                                                     2)
                                )


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

      echo "<table class='tab_cadre' width='50%'>";
      echo "<tr class='tab_bg_1'><td class='b'>&nbsp;".$title."&nbsp; ";
      $values   = array('' => self::EMPTY_VALUE);
      $selected = '';

      foreach ($optgroup as $label => $dp) {
         foreach ($dp as $key => $val) {
            $search = $key::getSearchURL();

            if (basename($search) == basename($value)) {
               $selected = $search;
            }
            $values[$label][$search] = $val;
         }
      }
      Dropdown::showFromArray('dpmenu', $values,
                              array('on_change' => "window.location.href=this.options[this.selectedIndex].value",
                                    'value'     => $selected));

      echo "</td></tr>";
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
      echo "<table class='tab_glpi'><tr class='top'><td width='33%' class='center'>";
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
            echo "<tr $class><td><a href='".$key::getSearchURL()."'>";
            echo "$val</a></td></tr>\n";
            $i++;
         }

         if (($i >= $step) && ($i < $nb)) {
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
    *@param $options array of options :
    *     - value              default value (default '')
    *     - limit_planning     limit planning to the configuration range (default false)
    *     - display   boolean  if false get string
    *     - width              specific width needed (default auto adaptive)
    *     - step               step time (defaut config GLPI)
    *
    * @since 0.85 update prototype
    *@return Nothing (display)
    **/
   static function showHours($name, $options=array()) {
      global $CFG_GLPI;

      $p['value']          = '';
      $p['limit_planning'] = false;
      $p['display']        = true;
      $p['width']          = '';
      $p['step']           = $CFG_GLPI["time_step"];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $begin = 0;
      $end   = 24;
      // Check if the $step is Ok for the $value field
      $split = explode(":", $p['value']);

      // Valid value XX:YY ou XX:YY:ZZ
      if ((count($split) == 2) || (count($split) == 3)) {
         $min = $split[1];

         // Problem
         if (($min%$p['step']) != 0) {
            // set minimum step
            $p['step'] = 5;
         }
      }

      if ($p['limit_planning']) {
         $plan_begin = explode(":", $CFG_GLPI["planning_begin"]);
         $plan_end   = explode(":", $CFG_GLPI["planning_end"]);
         $begin      = (int) $plan_begin[0];
         $end        = (int) $plan_end[0];
      }

      $values   = array();
      $selected = '';

      for ($i=$begin ; $i<$end ; $i++) {
         if ($i < 10) {
            $tmp = "0".$i;
         } else {
            $tmp = $i;
         }

         for ($j=0 ; $j<60 ; $j+=$p['step']) {
            if ($j < 10) {
               $val = $tmp.":0$j";
            } else {
               $val = $tmp.":$j";
            }
            $values[$val] = $val;
            if (($p['value'] == $val.":00") || ($p['value'] == $val)) {
               $selected = $val;
            }
         }
      }
      // Last item
      $val = $end.":00";
      $values[$val] = $val;
      if (($p['value'] == $val.":00") || ($p['value'] == $val)) {
         $selected = $val;
      }
      $p['value'] = $selected;
      return Dropdown::showFromArray($name, $values, $p);
   }


   /**
    * show a dropdown to selec a type
    *
    * @since version 0.83
    *
    * @param $types           Types used (default "state_types") (default '')
    * @param $options   Array of optional options
    *        name, value, rand, emptylabel, display_emptychoice, on_change, plural, checkright
    *       - toupdate            : array / Update a specific item on select change on dropdown
    *                                    (need value_fieldname, to_update,
    *                                     url (see Ajax::updateItemOnSelectEvent for information)
    *                                     and may have moreparams)
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
      $params['toupdate']            = '';

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
      if ($params['display_emptychoice']) {
         $options = array_merge(array(0=>$params['emptylabel']), $options);
      }

      if (count($options)) {
         return Dropdown::showFromArray($params['name'], $options,
                                        array('value'     => $params['value'],
                                              'on_change' => $params['on_change'],
                                              'toupdate'  => $params['toupdate'],));
      }
      return 0;
   }


   /**
    * Make a select box for all items
    *
    * @deprecated since version 0.85, replaced by self::showSelectItemFromItemtypes()
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
      $options = array();
      $options['itemtype_name']   = $itemtypename;
      $options['items_id_name']   = $myname;
      $options['itemtypes']       = $types;
      $options['entity_restrict'] = $entity_restrict;
      $options['onlyglobal']      = $onlyglobal;
      $options['checkright']      = $checkright;

      if ($value > 0) {
         $options['default']         = $value_type;
      }

      self::showSelectItemFromItemtypes($options);
   }


   /**
    * Make a select box for all items
    *
    * @since version 0.85
    *
    * @param $options array:
    *   - itemtype_name        : the name of the field containing the itemtype (default 'itemtype')
    *   - items_id_name        : the name of the field containing the id of the selected item
    *                            (default 'items_id')
    *   - itemtypes            : all possible types to search for (default: $CFG_GLPI["state_types"])
    *   - default_itemtype     : the default itemtype to select (don't define if you don't
    *                            need a default) (defaut 0)
    *    - entity_restrict     : restrict entity in searching items (default -1)
    *    - onlyglobal          : don't match item that don't have `is_global` == 1 (false by default)
    *    - checkright          : check to see if we can "view" the itemtype (false by default)
    *    - showItemSpecificity : given an item, the AJAX file to open if there is special
    *                            treatment. For instance, select a Item_Device* for CommonDevice
    *    - emptylabel          : Empty choice's label (default self::EMPTY_VALUE)
    *    - used                : array / Already used items ID: not to display in dropdown (default empty)
    *
    * @return randomized value used to generate HTML IDs
   **/
   static function showSelectItemFromItemtypes(array $options=array()) {
      global $CFG_GLPI;

      $params = array();
      $params['itemtype_name']       = 'itemtype';
      $params['items_id_name']       = 'items_id';
      $params['itemtypes']           = '';
      $params['default_itemtype']    = 0;
      $params['entity_restrict']     = -1;
      $params['onlyglobal']          = false;
      $params['checkright']          = false;
      $params['showItemSpecificity'] = '';
      $params['emptylabel']          = self::EMPTY_VALUE;
      $params['used']                = array();

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      $rand = self::showItemType($params['itemtypes'],
                                 array('checkright' => $params['checkright'],
                                       'name'       => $params['itemtype_name'],
                                       'emptylabel' => $params['emptylabel']));

      if ($rand) {
         $p = array('idtable'             => '__VALUE__',
                    'name'                => $params['items_id_name'],
                    'entity_restrict'     => $params['entity_restrict'],
                    'showItemSpecificity' => $params['showItemSpecificity']);

         // manage condition
         if ($params['onlyglobal']) {
            $p['condition'] = static::addNewCondition("`is_global` = 1");
         }
         if ($params['used']) {
            $p['used'] = $params['used'];
         }

         $field_id = Html::cleanId("dropdown_".$params['itemtype_name'].$rand);
         $show_id  = Html::cleanId("show_".$params['items_id_name'].$rand);

         Ajax::updateItemOnSelectEvent($field_id, $show_id,
                                       $CFG_GLPI["root_doc"]."/ajax/dropdownAllItems.php", $p);

         echo "<br><span id='$show_id'>&nbsp;</span>\n";

         // We check $options as the caller will set $options['default_itemtype'] only if it needs a
         // default itemtype and the default value can be '' thus empty won't be valid !
         if (array_key_exists ('default_itemtype', $options)) {
            echo "<script type='text/javascript' >\n";
            echo Html::jsSetDropdownValue($field_id, $params['default_itemtype']);
            echo "</script>\n";

            $p["idtable"] = $params['default_itemtype'];
            Ajax::updateItem($show_id, $CFG_GLPI["root_doc"]. "/ajax/dropdownAllItems.php", $p);
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
    *     - value              default value (default 0)
    *     - rand               random value
    *     - min                min value (default 0)
    *     - max                max value (default 100)
    *     - step               step used (default 1)
    *     - toadd     array    of values to add at the beginning
    *     - unit      string   unit to used
    *     - display   boolean  if false get string
    *     - width              specific width needed (default 80%)
    *     - on_change string / value to transmit to "onChange"
    *     - used      array / Already used items ID: not to display in dropdown (default empty)
   **/
   static function showNumber($myname, $options=array()) {
      global $CFG_GLPI;

      $p['value']     = 0;
      $p['rand']      = mt_rand();
      $p['min']       = 0;
      $p['max']       = 100;
      $p['step']      = 1;
      $p['toadd']     = array();
      $p['unit']      = '';
      $p['display']   = true;
      $p['width']     = '';
      $p['on_change'] = '';
      $p['used']      = array();

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }
      if (($p['value'] < $p['min']) && !isset($p['toadd'][$p['value']])) {
         $p['value'] = $p['min'];
      }

      $field_id = Html::cleanId("dropdown_".$myname.$p['rand']);
      if (!isset($p['toadd'][$p['value']])) {
         $valuename = self::getValueWithUnit($p['value'],$p['unit']);
      } else {
         $valuename = $p['toadd'][$p['value']];
      }
      $param = array('value'               => $p['value'],
                     'valuename'           => $valuename,
                     'width'               => $p['width'],
                     'on_change'           => $p['on_change'],
                     'used'                => $p['used'],
                     'unit'                => $p['unit'],
                     'min'                 => $p['min'],
                     'max'                 => $p['max'],
                     'step'                => $p['step'],
                     'toadd'               => $p['toadd']);

      $out   = Html::jsAjaxDropdown($myname, $field_id,
                                    $CFG_GLPI['root_doc']."/ajax/getDropdownNumber.php",
                                    $param);

      if ($p['display']) {
         echo $out;
         return $p['rand'];
      }
      return $out;
   }


   /**
    * Get value with unit / Automatic management of standar unit (year, month, %, ...)
    *
    * @since version 0.84
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
    * \deprecated since 0.84 use Dropdown::showNumber instead
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
    *    - width           : string / display width of the item
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
      $params['width']               = '80%';


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
                                                             'display' => $params['display'],
                                                             'width'   => $params['width'],));
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
    *    - value               : integer / preselected value (default 0)
    *    - used                : array / Already used items ID: not to display in dropdown (default empty)
    *    - readonly            : boolean / used as a readonly item (default false)
    *    - on_change           : string / value to transmit to "onChange"
    *    - multiple            : boolean / can select several values (default false)
    *    - size                : integer / number of rows for the select (default = 1)
    *    - display             : boolean / display or return string
    *    - other               : boolean or string if not false, then we can use an "other" value
    *                            if it is a string, then the default value will be this string
    *    - rand                : specific rand if needed (default is generated one)
    *    - width               : specific width needed (default not set)
    *    - emptylabel          : empty label if empty displayed (default self::EMPTY_VALUE)
    *    - display_emptychoice : display empty choice (default false)
    *
    * Permit to use optgroup defining items in arrays
    * array('optgroupname'  => array('key1' => 'val1',
    *                                'key2' => 'val2'),
    *       'optgroupname2' => array('key3' => 'val3',
    *                                'key4' => 'val4'))
   **/
   static function showFromArray($name, array $elements, $options=array()) {

      $param['value']               = '';
      $param['values']              = array('');
      $param['used']                = array();
      $param['readonly']            = false;
      $param['on_change']           = '';
      $param['width']               = '';
      $param['multiple']            = false;
      $param['size']                = 1;
      $param['display']             = true;
      $param['other']               = false;
      $param['rand']                = mt_rand();
      $param['emptylabel']          = self::EMPTY_VALUE;
      $param['display_emptychoice'] = false;

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

      if ($param["display_emptychoice"]) {
         $elements = array( 0 => $param['emptylabel'] ) + $elements ;
      }

      if ($param["multiple"]) {
         $field_name = $name."[]";
      } else {
         $field_name = $name;
      }

      $output = '';
      // readonly mode
      $field_id = Html::cleanId("dropdown_".$name.$param['rand']);
      if ($param['readonly']) {
         $to_display = array();
         foreach ($param['values'] as $value) {
            $output .= "<input type='hidden' name='$field_name' value='$value'>";
            if (isset($elements[$value])) {
               $to_display[] = $elements[$value];
            }
         }
         $output .= implode('<br>',$to_display);
      } else {


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
                     // Do not use in_array : trouble with 0 and empty value
                     foreach ($param['values'] as $value) {
                       if (strcmp($key2,$value) === 0) {
                           $output .= " selected";
                           break;
                       }
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
                  // Do not use in_array : trouble with 0 and empty value
                  foreach ($param['values'] as $value) {
                     if (strcmp($key,$value)===0) {
                        $output .= " selected";
                        break;
                     }
                  }
                  $output .= ">" .$val . "</option>";
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
      }

      // Width set on select
      $output .= Html::jsAdaptDropdown($field_id, array('width' => $param["width"]));

      if ($param["multiple"]) {
         // Hack for All / None because select2 does not provide it
         $select   = __('All');
         $deselect = __('None');
         $output  .= "<div class='invisible' id='selectallbuttons_$field_id'>";
         $output  .= "<div class='select2-actionable-menu'>";
         $output  .= "<a class='vsubmit floatleft' ".
                      "onclick=\"selectAll('$field_id');$('#$field_id').select2('close');\">$select".
                     "</a> ";
         $output  .= "<a class='vsubmit floatright' onclick=\"deselectAll('$field_id');\">$deselect".
                     "</a>";
         $output  .= "</div></div>";

         $js = "
         var multichecksappend$field_id = false;
         $('#$field_id').on('select2-open', function() {
            if (!multichecksappend$field_id) {
               $('#select2-drop').append($('#selectallbuttons_$field_id').html());
               multichecksappend$field_id = true;
            }
         });";
         $output .= Html::scriptBlock($js);
      }
      $output .= Ajax::commonDropdownUpdateItem($param, false);

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
                 "\" src='".$CFG_GLPI["root_doc"]."/pics/info-small.png' class='pointer'>";
         }

      } else {
         if ($params['management_restrict'] == 2) {
            $rand = mt_rand();
            $values = array(MANAGEMENT_UNITARY => __('Unit management'),
                            MANAGEMENT_GLOBAL  => __('Global management'));
            Dropdown::showFromArray($params['name'], $values, array('value' => $params['value']));
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

      $values[Search::PDF_OUTPUT_LANDSCAPE]     = __('Current page in landscape PDF');
      $values[Search::PDF_OUTPUT_PORTRAIT]      = __('Current page in portrait PDF');
      $values[Search::SYLK_OUTPUT]              = __('Current page in SLK');
      $values[Search::CSV_OUTPUT]               = __('Current page in CSV');
      $values['-'.Search::PDF_OUTPUT_LANDSCAPE] = __('All pages in landscape PDF');
      $values['-'.Search::PDF_OUTPUT_PORTRAIT]  = __('All pages in portrait PDF');
      $values['-'.Search::SYLK_OUTPUT]          = __('All pages in SLK');
      $values['-'.Search::CSV_OUTPUT]           = __('All pages in CSV');

      Dropdown::showFromArray('display_type', $values);
      echo "<input type='image' name='export' class='pointer' src='".$CFG_GLPI["root_doc"]."/pics/export.png'
             title=\""._sx('button', 'Export')."\" value=\""._sx('button', 'Export')."\">";
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
      $max             = Toolbox::get_max_input_vars();
      if ($max > 10) {
         $values[$max-10] = $max-10;
      }
      ksort($values);
      return self::showFromArray('glpilist_limit', $values,
                                 array('on_change' => $onchange,
                                       'value'     => $list_limit));
   }

}
?>
