<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

class Dropdown {


   /**
   * Print out an HTML "<select>" for a dropdown with preselected value
   *
   * Parameters which could be used in options array :
   *    - name : string / name of the select (default is depending itemtype)
   *    - value : integer / preselected value (default 0)
   *    - comments : boolean / is the comments displayed near the dropdown (default true)
   *    - entity : integer or array / restrict to a defined entity or array of entities
   *                   (default -1 : no restriction)
   *    - entity_sons : boolean / if entity restrict specified auto select its sons
   *                   only available if entity is a single value not an array (default false)
   *    - toupdate : array / Update a specific item on select change on dropdown
   *                   (need value_fieldname, to_update, url (see ajaxUpdateItemOnSelectEvent for informations)
   *                   and may have moreparams)
   *    - used : array / Already used items ID: not to display in dropdown (default empty)
   *    - auto_submit : boolean / preselected value (default 0)
   *    - rand : integer / already computed rand value
   *    - condition : string / aditional SQL condition to limit display
   *    - displaywith : array / array of field to display with request
   *
   *
   * @param $itemtype itemtype used for create dropdown
   * @param $options possible options
   * @return boolean : lse if error and random id if OK
   *
   */
   static function show($itemtype,$options=array()) {

      global $DB,$CFG_GLPI,$LANG;


      if ($itemtype && !class_exists($itemtype)) {
         return false;
      }
      $item = new $itemtype();

      $table=$item->getTable();
      $params['name']=$item->getForeignKeyField();

      $params['value']='';
      $params['comments']=1;
      $params['entity']=-1;
      $params['entity_sons']=false;
      $params['toupdate']='';
      $params['used']=array();
      $params['auto_submit']=0;
      $params['condition']='';
      $params['rand']=mt_rand();
      $params['displaywith']=array();
      //Parameters about choice 0
      //Empty choice's label
      $params['emptylabel'] = '';
      //Display emptychoice ?
      $params['display_emptychoice'] = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key]=$val;
         }
      }

      $name="------";
      $comment="";
      $limit_length=$_SESSION["glpidropdown_chars_limit"];


      // Check default value for dropdown : need to be a numeric
      if (strlen($params['value'])==0 || !is_numeric($params['value'])) {
         $params['value']=-1;
      }

      if ($params['value'] > 0
         || ($itemtype == "Entity" && $params['value'] >= 0)) {
         $tmpname=Dropdown::getDropdownName($table,$params['value'],1);
         if ($tmpname["name"]!="&nbsp;") {
            $name=$tmpname["name"];
            $comment=$tmpname["comment"];

            if (utf8_strlen($name) > $_SESSION["glpidropdown_chars_limit"]) {
               if ($item instanceof CommonTreeDropdown) {
                  $pos = strrpos($name,">");
                  $limit_length=max(utf8_strlen($name)-$pos,$_SESSION["glpidropdown_chars_limit"]);
                  if (utf8_strlen($name)>$limit_length) {
                     $name = "&hellip;".utf8_substr($name,-$limit_length);
                  }
               } else {
                  $limit_length = utf8_strlen($name);
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

      $use_ajax=false;
      if ($CFG_GLPI["use_ajax"]) {
         $nb=0;
         if ($item->isEntityAssign()) {
            if (!($params['entity']<0)) {
               $nb=countElementsInTableForEntity($table,$params['entity'],$params['condition']);
            } else {
               $nb=countElementsInTableForMyEntities($table,$params['condition']);
            }
         } else {
            $nb=countElementsInTable($table,$params['condition']);
         }
         $nb -= count($params['used']);
         if ($nb>$CFG_GLPI["ajax_limit_count"]) {
            $use_ajax=true;
         }
      }

      $param=array('searchText'           => '__VALUE__',
                    'value'               => $params['value'],
                    'itemtype'            => $itemtype,
                    'myname'              => $params['name'],
                    'limit'               => $limit_length,
                    'comment'             => $params['comments'],
                    'rand'                => $params['rand'],
                    'entity_restrict'     => $params['entity'],
                    'update_item'         => $params['toupdate'],
                    'used'                => $params['used'],
                    'auto_submit'         => $params['auto_submit'],
                    'condition'           => $params['condition'],
                    'emptylabel'          => $params['emptylabel'],
                    'display_emptychoice' => $params['display_emptychoice']);

      $default="<select name='".$params['name']."' id='dropdown_".$params['name'].$params['rand']."'>";
      $default.="<option value='".$params['value']."'>$name</option></select>";
      ajaxDropdown($use_ajax,"/ajax/dropdownValue.php",$param,$default,$params['rand']);

      // Display comment
      if ($params['comments']) {
         $options_tooltip=array('contentid'=>"comment_".$params['name'].$params['rand']);

         if ($itemtype=='TicketCategory' && haveRight('knowbase','r')) {
            if ($params['value'] && $item->getFromDB($params['value'])) {
               if ($kbid=$item->getField('knowbaseitemcategories_id')) {
                  $options_tooltip['link']=$CFG_GLPI['root_doc'].
                                       '/front/knowbaseitem.php?knowbaseitemcategories_id='.$kbid;
               }
            }
            $options_tooltip['linkid']="comment_link_".$params["name"].$params['rand'];
         } else {
            if ($item->canView()
               && $params['value'] && $item->getFromDB($params['value'])
               && $item->canViewItem()) {
               $options_tooltip['link']=$item->getLinkURL();

               $options_tooltip['linktarget']='_blank';
            }
         }

         showToolTip($comment,$options_tooltip);


         if (($item instanceof CommonDropdown)
               && $item->canCreate()) {

               echo "<img alt='' title='".$LANG['buttons'][8]."' src='".$CFG_GLPI["root_doc"].
                     "/pics/add_dropdown.png' style='cursor:pointer; margin-left:2px;'  onClick=\"var w = window.open('".
                     $item->getFormURL().
                     "?popup=1&amp;rand=".$params['rand']."' ,'glpipopup', 'height=400, ".
                     "width=1000, top=100, left=100, scrollbars=yes' );w.focus();\">";
         }
         // Display specific Links
         if ($itemtype=="Supplier") {
            if ($item->getFromDB($params['value'])) {
               echo $item->getLinks();
            }
         }
      }

      return $params['rand'];
   }

   /**
    * Get the value of a dropdown
    *
    *
    * Returns the value of the dropdown from $table with ID $id.
    *
   * @param $table the dropdown table from witch we want values on the select
    * @param $id id of the element to get
    * @param $withcomment give array with name and comment
    * @return string the value of the dropdown or &nbsp; if not exists
    */
   static function getDropdownName($table,$id,$withcomment=0) {
      global $DB,$CFG_GLPI,$LANG;

      $itemtype=getItemTypeForTable($table);
      $item = new $itemtype();
      if ($item instanceof CommonTreeDropdown) {
         return getTreeValueCompleteName($table,$id,$withcomment);
      } else {
         $name = "";
         $comment = "";
         if ($id) {
            $query = "SELECT *
                      FROM `". $table ."`
                      WHERE `id` = '". $id ."'";
            if ($result = $DB->query($query)) {
               if($DB->numrows($result) != 0) {
                  $data=$DB->fetch_assoc($result);
                  $name = $data["name"];
                  if (isset($data["comment"])) {
                     $comment = $data["comment"];
                  }
                  switch ($table) {
                     case "glpi_computers" :
                        if (empty($name)) {
                           $name="($id)";
                        }
                        break;

                     case "glpi_contacts" :
                        $name .= " ".$data["firstname"];
                        if (!empty($data["phone"])) {
                           $comment.="<br><strong>".$LANG['help'][35]."&nbsp;:</strong> ".
                                       $data["phone"];
                        }
                        if (!empty($data["phone2"])) {
                           $comment.="<br><strong>".$LANG['help'][35]." 2&nbsp;:</strong> ".
                                       $data["phone2"];
                        }
                        if (!empty($data["mobile"])) {
                           $comment.="<br><strong>".$LANG['common'][42]."&nbsp;:</strong> ".
                                       $data["mobile"];
                        }
                        if (!empty($data["fax"])) {
                           $comment.="<br><strong>".$LANG['financial'][30]."&nbsp;:</strong> ".
                                       $data["fax"];
                        }
                        if (!empty($data["email"])) {
                           $comment.="<br><strong>".$LANG['setup'][14]."&nbsp;:</strong> ".
                                       $data["email"];
                        }
                        break;

                     case "glpi_suppliers" :
                        if (!empty($data["phone"])) {
                           $comment.="<br><strong>".$LANG['help'][35]."&nbsp;:</strong> ".
                                       $data["phone"];
                        }
                        if (!empty($data["fax"])) {
                           $comment.="<br><strong>".$LANG['financial'][30]."&nbsp;:</strong> ".
                                       $data["fax"];
                        }
                        if (!empty($data["email"])) {
                           $comment.="<br><strong>".$LANG['setup'][14]."&nbsp;:</strong> ".
                                       $data["email"];
                        }
                        break;

                     case "glpi_netpoints" :
                        $name .= " (".Dropdown::getDropdownName("glpi_locations",$data["locations_id"]).")";
                        break;
                  }
               }
            }
         }
      }
      if (empty($name)) {
         $name="&nbsp;";
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
    * @return array containing the value of the dropdown or &nbsp; if not exists
    */
   static function getDropdownArrayNames($table,$ids) {
      global $DB,$CFG_GLPI;

      $tabs=array();

      if (count($ids)) {
         $itemtype=getItemTypeForTable($table);
         $item = new $itemtype();

         $field='name';
         if ($item instanceof CommonTreeDropdown) {
            $field='completename';
         }

         $query="SELECT `id`, `$field`
                 FROM `$table`
                 WHERE `id` IN (".implode(',',$ids).")";

         if ($result=$DB->query($query)) {
            while ($data=$DB->fetch_assoc($result)) {
               $tabs[$data['id']]=$data[$field];
            }
         }
      }
      return $tabs;
   }

   /**
   *
   * Make a select box for device type
   *
   *
   * @param $name name of the select box
   * @param $value default device type
   * @param $types types to display
   * @param $used Already used items ID: not to display in dropdown
   * @return nothing (print out an HTML select box)
   */
   static function dropdownTypes($name,$value=0,$types=array(),$used=array()) {
      global $CFG_GLPI;

      $options=array(''=>'----');
      if (count($types)) {
         foreach ($types as $type) {
            if (class_exists($type)) {
               $item = new $type();
               $options[$type]=$item->getTypeName();
            }
         }
      }
      asort($options);
      return Dropdown::showFromArray($name,$options,array('value'  => $value,'used'  => $used));
   }

   /**
   *
   * Make a select box for device type
   *
   *
   * @param $name name of the select box
   * @param $options array options : may be value (default value) / field (used field to search itemtype)
   * @param $itemtype_ref string itemtype reference where to search in itemtype field
   * @return nothing (print out an HTML select box)
   */
   static function dropdownUsedItemTypes($name,$itemtype_ref,$options=array()) {
      global $DB;

      $p['value'] = 0;
      $p['field'] = 'itemtype';

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key]=$val;
         }
      }

      $query="SELECT DISTINCT `".$p['field']."`
               FROM `".getTableForItemType($itemtype_ref)."`";
      $tabs=array();
      if ($result=$DB->query($query)) {
         while ($data=$DB->fetch_assoc($result)) {
            $tabs[$data[$p['field']]]=$data[$p['field']];
         }
      }

      return Dropdown::dropdownTypes($name,$p['value'],$tabs);
   }

   /**
    *
    * Make a select box for icons
    *
    *
    * @param $value the preselected value we want
    * @param $myname the name of the HTML select
    * @param $store_path path where icons are stored
    * @return nothing (print out an HTML select box)
    */
   static function dropdownIcons($myname,$value,$store_path) {
      global $LANG;

      if (is_dir($store_path)) {
         if ($dh = opendir($store_path)) {
            $files=array();
            while (($file = readdir($dh)) !== false) {
               $files[]=$file;
            }
            closedir($dh);
            sort($files);
            echo "<select name='$myname'>";
            echo "<option value=''>".DROPDOWN_EMPTY_VALUE."</option>";
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
    * @param $value default value
    */
   static function showGMT($name,$value='') {
      global $LANG;

      $elements = array (-12, -11, -10, -9, -8, -7, -6, -5, -4, -3.5, -3, -2, -1, 0,
                         1, 2, 3, 3.5, 4, 4.5, 5, 5.5, 6, 6.5, 7, 8, 9, 9.5, 10, 11, 12, 13);

      echo "<select name='$name' id='dropdown_".$name."'>";

      foreach($elements as $element) {
         if ($element != 0) {
            $display_value = $LANG['gmt'][0].($element > 0?" +":" ").$element." ".$LANG['gmt'][1];
         } else {
            $display_value = $LANG['gmt'][0];
         }
         $eltvalue=$element*HOUR_TIMESTAMP;
         echo "<option value='$eltvalue'".($eltvalue==$value?" selected ":"").">".$display_value."</option>";
      }
      echo "</select>";
   }

   /**
    * Make a select box for a boolean choice (Yes/No)
    *
    * @param $name select name
    * @param $value preselected value.
    * @param $restrict_to allows to display only yes or no in the dropdown (default is yes & no)
    * @return nothing (print out an HTML select box)
    */
   static function showYesNo($name,$value=0,$restrict_to=-1) {
      global $LANG;

      if ($restrict_to != 0) {
         $options[0] =$LANG['choice'][0];
      }
      if ($restrict_to != 1) {
         $options[1] =$LANG['choice'][1];
      }
      Dropdown::showFromArray($name,$options,array('value'=>$value));
   }

   /**
   * Get Yes No string
   *
   * @param $value Yes No value
   * @return string
   */
   static function getYesNo($value) {
      global $LANG;

      if ($value) {
         return $LANG['choice'][1];
      } else {
         return $LANG['choice'][0];
      }
   }

   /**
    * Get the Device list name the user is allowed to edit
    *
    * @return array (group of dropdown) of array (itemtype => localized name)
    */
   static function getDeviceItemTypes () {
      global $LANG, $CFG_GLPI;
      static $optgroup=NULL;

      if (!haveRight('device','r')) {
         return array();
      }
      if (is_null($optgroup)) {
         $optgroup =
            array($LANG['title'][30] => array('DeviceMotherboard' => $LANG['devices'][5],
                                              'DeviceProcessor'   => $LANG['devices'][4],
                                              'DeviceNetworkCard' => $LANG['devices'][3],
                                              'DeviceMemory'      => $LANG['devices'][6],
                                              'DeviceHardDrive'   => $LANG['devices'][1],
                                              'DeviceDrive'       => $LANG['devices'][19],
                                              'DeviceControl'     => $LANG['devices'][20],
                                              'DeviceGraphicCard' => $LANG['devices'][2],
                                              'DeviceSoundCard'   => $LANG['devices'][7],
                                              'DeviceCase'        => $LANG['devices'][22],
                                              'DevicePowerSupply' => $LANG['devices'][23],
                                              'DevicePci'         => $LANG['devices'][21]));
      }
      return $optgroup;
   }

   /**
    * Get the dropdown list name the user is allowed to edit
    *
    * @return array (group of dropdown) of array (itemtype => localized name)
    */
   static function getStandardDropdownItemTypes () {
      global $LANG, $CFG_GLPI;
      static $optgroup=NULL;

      if (is_null($optgroup)) {
         $optgroup =
            array($LANG['setup'][139] => array('Location'        => $LANG['common'][15],
                                               'State'           => $LANG['setup'][83],
                                               'Manufacturer'    => $LANG['common'][5],
                                               'Calendar'        => $LANG['Menu'][42],
                                               'Holiday'         => $LANG['calendar'][11]),


                  $LANG['setup'][140] => array('ComputerType'         => $LANG['setup'][4],
                                               'NetworkEquipmentType' => $LANG['setup'][42],
                                               'PrinterType'          => $LANG['setup'][43],
                                               'MonitorType'          => $LANG['setup'][44],
                                               'PeripheralType'       => $LANG['setup'][69],
                                               'PhoneType'            => $LANG['setup'][504],
                                               'SoftwareLicenseType'  => $LANG['software'][30],
                                               'CartridgeItemType'    => $LANG['setup'][84],
                                               'ConsumableItemType'   => $LANG['setup'][92],
                                               'ContractType'         => $LANG['setup'][85],
                                               'ContactType'          => $LANG['setup'][82],
                                               'DeviceMemoryType'     => $LANG['setup'][86],
                                               'SupplierType'         => $LANG['setup'][80],
                                               'InterfaceType'        => $LANG['setup'][93],
                                               'DeviceCaseType'       => $LANG['setup'][45],
                                               'PhonePowerSupply'     => $LANG['setup'][505],
                                               'Filesystem'           => $LANG['computers'][4]),

                  $LANG['common'][22] => array('ComputerModel'         => $LANG['setup'][91],
                                               'NetworkEquipmentModel' => $LANG['setup'][95],
                                               'PrinterModel'          => $LANG['setup'][96],
                                               'MonitorModel'          => $LANG['setup'][94],
                                               'PeripheralModel'       => $LANG['setup'][97],
                                               'PhoneModel'            => $LANG['setup'][503]),

                  $LANG['Menu'][26] => array('DocumentCategory' => $LANG['setup'][81],
                                             'DocumentType'     => $LANG['document'][7]),

                  $LANG['Menu'][18] => array('KnowbaseItemCategory' => $LANG['setup'][87]),

                  $LANG['title'][24] => array ('TicketCategory'     => $LANG['setup'][79],
                                               'TaskCategory'       => $LANG['setup'][98],
                                               'TicketSolutionType' => $LANG['job'][48],
                                               'RequestType'        => $LANG['job'][44]),

                  $LANG['setup'][145] => array('OperatingSystem'            => $LANG['setup'][5],
                                               'OperatingSystemVersion'     => $LANG['computers'][52],
                                               'OperatingSystemServicePack' => $LANG['computers'][53],
                                               'AutoUpdateSystem'           => $LANG['computers'][51]),

                  $LANG['setup'][88] => array('NetworkInterface'         => $LANG['setup'][9],
                                              'NetworkEquipmentFirmware' => $LANG['setup'][71],
                                              'Netpoint'                 => $LANG['setup'][73],
                                              'Domain'                   => $LANG['setup'][89],
                                              'Network'                  => $LANG['setup'][88],
                                              'Vlan'                     => $LANG['setup'][90]),

                  $LANG['Menu'][4] => array('SoftwareCategory' => $LANG['softwarecategories'][5]),

                  $LANG['common'][34] => array('UserTitle'     => $LANG['users'][1],
                                               'UserCategory'  => $LANG['users'][2]),
                  $LANG['rulesengine'][19] => array('RuleRightParameter'=>$LANG['rulesengine'][138])

                 ); //end $opt

         $plugdrop=getPluginsDropdowns();
         if (count($plugdrop)) {
            $optgroup=array_merge($optgroup,$plugdrop);
         }

         foreach ($optgroup as $label=>$dp) {
            foreach ($dp as $key => $val) {
               if (class_exists($key)) {
                  $tmp = new $key();
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
    * @param $optgroup array (group of dropdown) of array (itemtype => localized name)
    * @param $value string URL of selected current value
    * @param $title string title to display
    */
   static function showItemTypeMenu($title, $optgroup, $value='') {
      global $LANG;

      echo "<table class='tab_cadre'>";
      echo "<tr class='tab_bg_1'><td class='b'>&nbsp;".$title."&nbsp;: ";
      echo "<select id='menu_nav'>";
      foreach($optgroup as $label => $dp) {
         echo "<optgroup label=\"$label\">";
         foreach ($dp as $key => $val) {
            $search=getItemTypeSearchURL($key);
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
      echo "<input type='submit' name='add' value=\"".$LANG['buttons'][0]."\" class='submit' ";
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
      global $LANG;

      echo "<p><a href=\"javascript:showHideDiv('list_nav','img_nav','";
      echo GLPI_ROOT . "/pics/folder.png','" . GLPI_ROOT . "/pics/folder-open.png');\">";
      echo "<img alt='' name='img_nav' src=\"" . GLPI_ROOT . "/pics/folder.png\">&nbsp;";
      echo $LANG['buttons'][40]."</a></p>";

      echo "<div id='list_nav' style='display:none;'>";

      $nb=0;
      foreach($optgroup as $label => $dp) {
         $nb += count($dp);
      }
      $step = ($nb>15 ? ($nb/3) : $nb);

      echo "<table><tr class='top'><td><table class='tab_cadre'>";
      $i=1;
      foreach($optgroup as $label => $dp) {
         echo "<tr><th>$label</th></tr>\n";
         foreach ($dp as $key => $val) {
            echo "<tr class='tab_bg_1'><td><a href='".getItemTypeSearchURL($key)."'>";
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
    * @param $options additionnal options :
    *    - display_none : allow selection of no language
    */
   static function showLanguages($myname,$options=array()) {
      global $CFG_GLPI;

      $values = array();
      if (isset($options['display_none']) && ($options['display_none'])) {
         $values[''] = DROPDOWN_EMPTY_VALUE;
      }
      foreach ($CFG_GLPI["languages"] as $key => $val) {
         if (isset($val[1]) && is_file(GLPI_ROOT ."/locales/".$val[1])) {
            $values[$key] = $val[0];
         }
      }
      Dropdown::showFromArray($myname,$values,$options);
   }

   /**
    * Print a select with hours
    *
    * Print a select named $name with hours options and selected value $value
    *
    *@param $name string : HTML select name
    *@param $value integer : HTML select selected value
    *@param $limit_planning limit planning to the configuration range
    *
    *@return Nothing (display)
    *
    **/
   static function showHours($name, $value, $limit_planning=0) {
      global $CFG_GLPI;

      $begin=0;
      $end=24;
      $step=$CFG_GLPI["time_step"];
      // Check if the $step is Ok for the $value field
      $split=explode(":",$value);
      // Valid value XX:YY ou XX:YY:ZZ
      if (count($split)==2 || count($split)==3) {
         $min=$split[1];
         // Problem
         if (($min%$step)!=0) {
            // set minimum step
            $step=5;
         }
      }

      if ($limit_planning) {
         $plan_begin=explode(":",$CFG_GLPI["planning_begin"]);
         $plan_end=explode(":",$CFG_GLPI["planning_end"]);
         $begin=(int) $plan_begin[0];
         $end=(int) $plan_end[0];
      }
      echo "<select name=\"$name\">";
      for ($i=$begin;$i<$end;$i++) {
         if ($i<10) {
            $tmp="0".$i;
         } else {
            $tmp=$i;
         }

         for ($j=0;$j<60;$j+=$step) {
            if ($j<10) {
               $val=$tmp.":0$j";
            } else {
               $val=$tmp.":$j";
            }

            echo "<option value='$val' ".($value==$val.":00"||$value==$val?" selected ":"").">$val</option>";
         }
      }
      // Last item
      $val=$end.":00";
      echo "<option value='$val' ".($value==$val.":00"||$value==$val?" selected ":"").">$val</option>";
      echo "</select>";
   }

   /**
   *
   *Make a select box for all items
   *
   *
   * @param $myname select name
   * @param $value default value
   * @param $value_type default value for the device type
   * @param $entity_restrict Restrict to a defined entity
   * @param $types Types used
   * @param $onlyglobal Restrict to global items
   * @return nothing (print out an HTML select box)
   */
   static function showAllItems($myname,$value_type=0,$value=0,$entity_restrict=-1,$types='',
                           $onlyglobal=false) {
      global $LANG,$CFG_GLPI;

      if (!is_array($types)) {
         $types=$CFG_GLPI["state_types"];
      }
      $rand=mt_rand();
      $options=array();

      foreach ($types as $type) {
         if (class_exists($type)) {
            $item = new $type();
            $options[$type]=$item->getTypeName($type);
         }
      }
      asort($options);
      if (count($options)) {
         echo "<select name='itemtype' id='itemtype$rand'>";
         echo "<option value='0'>".DROPDOWN_EMPTY_VALUE."</option>\n";
         foreach ($options as $key => $val) {
            echo "<option value='".$key."'>".$val."</option>";
         }
         echo "</select>";

         $params=array('idtable'          => '__VALUE__',
                        'value'           => $value,
                        'myname'          => $myname,
                        'entity_restrict' => $entity_restrict);
         if ($onlyglobal) {
            $params['condition']="`is_global`='1'";
         }
         ajaxUpdateItemOnSelectEvent("itemtype$rand","show_$myname$rand",
                                    $CFG_GLPI["root_doc"]."/ajax/dropdownAllItems.php",$params);

         echo "<br><span id='show_$myname$rand'>&nbsp;</span>\n";

         if ($value>0) {
            echo "<script type='text/javascript' >\n";
            echo "window.document.getElementById('itemtype$rand').value='".$value_type."';";
            echo "</script>\n";

            $params["idtable"]=$value_type;
            ajaxUpdateItem("show_$myname$rand",$CFG_GLPI["root_doc"]."/ajax/dropdownAllItems.php",$params);
         }
      }
      return $rand;
   }

   /**
   * Dropdown integers
   *
   * @param $myname select name
   * @param $value default value
   * @param $min min value
   * @param $max max value
   * @param $step step used
   * @param $toadd values to add at the beginning
   */
   static function showInteger($myname,$value,$min=0,$max=100,$step=1,$toadd=array(),$options = array()) {

      if (isset($options['suffix'])) {
         $suffix = $options['suffix'];
      }
      else {
         $suffix = '';
      }
      echo "<select name='$myname'>\n";
      if (count($toadd)) {
         foreach ($toadd as $key => $val) {
            echo "<option value='$key' ".($key==$value?" selected ":"").">";
            echo ($suffix!=''?$val.' '.$suffix:$val)."</option>";
         }
      }
      for ($i=$min ; $i<=$max ; $i+=$step) {
         echo "<option value='$i' ".($i==$value?" selected ":"").">";
         echo ($suffix!=''?$i.' '.$suffix:$i)."</option>";
      }
      echo "</select>";

   }

   /**
   * Private / Public switch for items which may be assign to a user and/or an entity
   *
   * @param $is_private default is private ?
   * @param $entity working entity ID
   * @param $is_recursive is the item recursive ?
   */
   static function showPrivatePublicSwitch($is_private,$entity,$is_recursive) {
      global $LANG,$CFG_GLPI;

      $rand=mt_rand();
      echo "<script type='text/javascript' >\n";
      echo "function setPrivate$rand(){\n";

         $params=array(
            'is_private'   => 1,
            'is_recursive' => $is_recursive,
            'entities_id'  => $entity,
            'rand'         => $rand,
         );
         ajaxUpdateItemJsCode('private_switch'.$rand,$CFG_GLPI["root_doc"]."/ajax/private_public.php",$params,false);

         echo "};";
      echo "function setPublic$rand(){\n";

         $params=array(
            'is_private'   => 0,
            'is_recursive' => $is_recursive,
            'entities_id'  => $entity,
            'rand'         => $rand,
         );
         ajaxUpdateItemJsCode('private_switch'.$rand,$CFG_GLPI["root_doc"]."/ajax/private_public.php",$params,false);

         echo "};";
      echo "</script>";

      echo "<span id='private_switch$rand'>";
         $_POST['rand']=$rand;
         $_POST['is_private']=$is_private;
         $_POST['is_recursive']=$is_recursive;
         $_POST['entities_id']=$entity;
         include (GLPI_ROOT."/ajax/private_public.php");
      echo "</span>\n";
      return $rand;
   }

   /**
    * Toggle view in LDAP user import/synchro between no restriction and date restriction
    */
   static function showAdvanceDateRestrictionSwitch($enabled = 0) {
      global $LANG,$CFG_GLPI;

      $rand=mt_rand();
      $url = $CFG_GLPI["root_doc"]."/ajax/ldapdaterestriction.php";
      echo "<script type='text/javascript' >\n";
      echo "function activateRestriction(){\n";
      $params=array('enabled'   => 1);
      ajaxUpdateItemJsCode('date_restriction',$url,$params,false);
      echo "};";
      echo "function deactivateRestriction(){\n";
      $params=array('enabled'   => 0);
      ajaxUpdateItemJsCode('date_restriction',$url,$params,false);
      echo "};";
      echo "</script>";

      echo "</table>";
      echo "<span id='date_restriction'>";
      $_POST['enabled']=$enabled;
      include (GLPI_ROOT."/ajax/ldapdaterestriction.php");
      echo "</span>\n";
      return $rand;
   }

   /**
   * Dropdown of values in an array
   *
   * Parameters which could be used in options array :
   *    - value : integer / preselected value (default 0)
   *    - used : array / Already used items ID: not to display in dropdown (default empty)
   *    - readonly : boolean / used as a readonly item (default false)
   *
   * @param $name select name
   * @param $elements array of elements to display
   * @param $options options
   *
   */
   static function showFromArray($name,$elements,$options = array()) {

      $param['value']='';
      $param['used']=array();
      $param['readonly']=false;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $param[$key]=$val;
         }
      }

      // readonly mode
      if ($param['readonly']) {
         echo "<input type='hidden' name='$name' value='".$param['value']."'>";

         if (isset($elements[$param['value']])) {
            echo $elements[$param['value']];
         }
      } else {

         $rand=mt_rand();
         echo "<select name='$name' id='dropdown_".$name.$rand."'>";

         foreach ($elements as $key => $val) {
            if (!isset($param['used'][$key])) {
               echo "<option value='".$key."'".($param['value']==$key?" selected ":"").">".$val."</option>";
            }
         }

         echo "</select>";
         return $rand;
      }
   }


   /**
   * Dropdown for global item management
   *
   * @param $target target for actions
   * @param $withtemplate template or basic computer
   * @param $ID item ID
   * @param $value value of global state
   * @param $management_restrict global management restrict mode
   */
   static function showGlobalSwitch($target,$withtemplate,$ID,$value,$management_restrict=0) {
      global $LANG,$CFG_GLPI;

      if ($value && empty($withtemplate)) {
         echo $LANG['peripherals'][31];

         if ($management_restrict == 2) {
            echo "&nbsp;<a title=\"".$LANG['common'][39]."\" href=\"javascript:confirmAction('".addslashes($LANG['common'][40])."\\n".
                        addslashes($LANG['common'][39])."','$target?unglobalize=unglobalize&amp;id=$ID')\">".
                        $LANG['common'][38]."</a>&nbsp;";
            echo "<img alt=\"".$LANG['common'][39]."\" title=\"".$LANG['common'][39]."\" src=\"".
                  $CFG_GLPI["root_doc"]."/pics/aide.png\">";
         }
      } else {

         if ($management_restrict == 2) {
            echo "<select name='is_global'>";
            echo "<option value='0' ".(!$value?" selected":"").">".$LANG['peripherals'][32]."</option>";
            echo "<option value='1' ".($value?" selected":"").">".$LANG['peripherals'][31]."</option>";
            echo "</select>";
         } else {
            // Templates edition
            if (!empty($withtemplate)) {
               echo "<input type='hidden' name='is_global' value=\"".$management_restrict."\">";
               echo (!$management_restrict?$LANG['peripherals'][32]:$LANG['peripherals'][31]);
            } else {
               echo (!$value?$LANG['peripherals'][32]:$LANG['peripherals'][31]);
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
    */
   static function import ($itemtype, $input) {

      if (!class_exists($itemtype)) {
         return false;
      }
      $item = new $itemtype();
      return $item->import($input);
   }

   /**
    * Import a value in a dropdown table.
    *
    * This import a new dropdown if it doesn't exist - Play dictionnary if needed
    *
    * @param $itemtype string name of the class
    * @param $value string : Value of the new dropdown.
    * @param $entities_id int : entity in case of specific dropdown
    * @param $external_params
    * @param $comment
    * @param $add if true, add it if not found. if false, just check if exists
    *
    * @return integer : dropdown id.
    **/
   static function importExternal($itemtype,$value,$entities_id=-1,$external_params=array(),$comment='',$add=true) {

      if (!class_exists($itemtype)) {
         return false;
      }
      $item = new $itemtype();
      return $item->importExternal($value, $entities_id, $external_params, $comment, $add);
   }

   /**
   * Dropdown of actions for massive action
   *
   * @param $itemtype item type
   * @param $is_deleted massive action for deleted items ?
   * @param $extraparams array of extra parameters
   */
   static function showForMassiveAction($itemtype,$is_deleted=0,$extraparams=array()) {
      /// TODO include in CommonDBTM defining only getAdditionalMassiveAction in sub classes
      /// for specific actions (return a array of action name and title)
      global $LANG,$CFG_GLPI,$PLUGIN_HOOKS;

      if (!class_exists($itemtype)) {
         return false;
      }

      if ($itemtype=='NetworkPort') {
         echo "<select name='massiveaction' id='massiveaction'>";

         echo "<option value='-1' selected>".DROPDOWN_EMPTY_VALUE."</option>";
         echo "<option value='delete'>".$LANG['buttons'][6]."</option>";
         echo "<option value='assign_vlan'>".$LANG['networking'][55]."</option>";
         echo "<option value='unassign_vlan'>".$LANG['networking'][58]."</option>";
         echo "<option value='move'>".$LANG['buttons'][20]."</option>";
         echo "</select>";

         $params=array('action'     => '__VALUE__',
                     'itemtype'   => $itemtype);

         ajaxUpdateItemOnSelectEvent("massiveaction","show_massiveaction",$CFG_GLPI["root_doc"].
                                    "/ajax/dropdownMassiveActionPorts.php",$params);

         echo "<span id='show_massiveaction'>&nbsp;</span>\n";
      } else {

         $item = new $itemtype();

         $infocom= new Infocom();

         $isadmin=$item->canUpdate();

         echo "<select name='massiveaction' id='massiveaction'>";
         echo "<option value='-1' selected>".DROPDOWN_EMPTY_VALUE."</option>";
         if (!in_array($itemtype,$CFG_GLPI["massiveaction_noupdate_types"])
            && ($isadmin ||(in_array($itemtype,$CFG_GLPI["infocom_types"]) && $infocom->canUpdate())
                        || ($itemtype == 'Ticket' && haveRight('update_ticket',1)))) {

            echo "<option value='update'>".$LANG['buttons'][14]."</option>";
         }

         if (in_array($itemtype,$CFG_GLPI["infocom_types"]) && $infocom->canCreate() ) {
            echo "<option value='activate_infocoms'>".$LANG['financial'][68]."</option>";
         }

         if ($is_deleted) {
            if ($isadmin) {
               echo "<option value='purge'>".$LANG['buttons'][22]."</option>";
               echo "<option value='restore'>".$LANG['buttons'][21]."</option>";
            }
         } else {
            // No delete for entities and tracking of not have right
            if (!in_array($itemtype,$CFG_GLPI["massiveaction_nodelete_types"])
               && (($isadmin && $itemtype != 'Ticket')
                  || ($itemtype == 'Ticket' && haveRight('delete_ticket',1)))) {

               if ($item->maybeDeleted()) {
                  echo "<option value='delete'>".$LANG['buttons'][6]."</option>";
               } else {
                  echo "<option value='purge'>".$LANG['buttons'][22]."</option>";
               }
            }
            if ($isadmin && in_array($itemtype,array('Phone', 'Printer', 'Peripheral', 'Monitor'))) {

               echo "<option value='connect'>".$LANG['buttons'][9]."</option>";
               echo "<option value='disconnect'>".$LANG['buttons'][10]."</option>";
            }
            if (in_array($itemtype,$CFG_GLPI["doc_types"])) {
               $doc = new Document();
               if ($doc->canUpdate()) {
                  echo "<option value='add_document'>".$LANG['document'][16]."</option>";
               }
            }

            if (in_array($itemtype,$CFG_GLPI["contract_types"])) {
               $contract = new Contract();
               if ($contract->canUpdate()) {
                  echo "<option value='add_contract'>".$LANG['financial'][36]."</option>";
               }
            }
            if (haveRight('transfer','r') && isMultiEntitiesMode()
               && in_array($itemtype, array('CartridgeItem', 'Computer', 'ConsumableItem', 'Contact',
                                             'Contract', 'Supplier', 'Monitor', 'NetworkEquipment',
                                             'Peripheral', 'Phone', 'Printer', 'Software',
                                             'SoftwareLicense', 'Ticket', 'Document', 'Group', 'Link'))
               && $isadmin) {

               echo "<option value='add_transfer_list'>".$LANG['buttons'][48]."</option>";
            }
            switch ($itemtype) {
               case 'Software' :
                  if ($isadmin
                     && countElementsInTable("glpi_rules","sub_type='RuleSoftwareCategory'") > 0) {
                     echo "<option value=\"compute_software_category\">".$LANG['rulesengine'][38]." ".
                           $LANG['rulesengine'][40]."</option>";
                  }
                  if (haveRight("rule_dictionnary_software","w")
                     && countElementsInTable("glpi_rules","sub_type='RuleDictionnarySoftware'") > 0) {
                     echo "<option value=\"replay_dictionnary\">".$LANG['rulesengine'][76]."</option>";
                  }
                  break;

               case 'Computer' :
                  if ($isadmin) {
                     echo "<option value='connect_to_computer'>".$LANG['buttons'][9]."</option>";
                     echo "<option value='install'>".$LANG['buttons'][4]."</option>";
                     if ($CFG_GLPI['use_ocs_mode']) {
                        if (haveRight("ocsng","w") || haveRight("sync_ocsng","w")) {
                           echo "<option value='force_ocsng_update'>".$LANG['ocsng'][24]."</option>";
                        }
                        echo "<option value='unlock_ocsng_field'>".$LANG['buttons'][38]." ".
                              $LANG['Menu'][33]." - ".$LANG['ocsng'][16]."</option>";
                        echo "<option value='unlock_ocsng_monitor'>".$LANG['buttons'][38]." ".
                              $LANG['Menu'][33]." - ".$LANG['ocsng'][30]."</option>";
                        echo "<option value='unlock_ocsng_peripheral'>".$LANG['buttons'][38]." ".
                              $LANG['Menu'][33]." - ".$LANG['ocsng'][32]."</option>";
                        echo "<option value='unlock_ocsng_printer'>".$LANG['buttons'][38]." ".
                              $LANG['Menu'][33]." - ".$LANG['ocsng'][34]."</option>";
                        echo "<option value='unlock_ocsng_software'>".$LANG['buttons'][38]." ".
                              $LANG['Menu'][33]." - ".$LANG['ocsng'][52]."</option>";
                        echo "<option value='unlock_ocsng_ip'>".$LANG['buttons'][38]." ".
                              $LANG['Menu'][33]." - ".$LANG['ocsng'][50]."</option>";
                        echo "<option value='unlock_ocsng_disk'>".$LANG['buttons'][38]." ".
                              $LANG['Menu'][33]." - ".$LANG['ocsng'][55]."</option>";
                     }
                  }
                  break;

               case 'Supplier' :
                  if ($isadmin) {
                     echo "<option value='add_contact'>".$LANG['financial'][24]."</option>";
                  }
                  break;
               case 'Calendar':
                  echo "<option value='duplicate'>".$LANG['buttons'][54]."</option>";
                  break;
               case 'Contact' :
                  if ($isadmin) {
                     echo "<option value='add_enterprise'>".$LANG['financial'][25]."</option>";
                  }
                  break;

               case 'User' :
                  if ($isadmin) {
                     echo "<option value='add_group'>".$LANG['setup'][604]."</option>";
                     echo "<option value='add_userprofile'>".$LANG['setup'][607]."</option>";
                  }
                  if (haveRight("user_authtype","w")) {
                     echo "<option value='change_authtype'>".$LANG['login'][30]."</option>";
                     echo "<option value='force_user_ldap_update'>".$LANG['ocsng'][24]."</option>";
                  }
                  break;

               case 'Ticket' :
                  $tmp = new TicketFollowup();
                  if ($tmp->canCreate()) {
                     echo "<option value='add_followup'>".$LANG['job'][29]."</option>";
                  }
                  $tmp = new TicketTask();
                  if ($tmp->canCreate()) {
                     echo "<option value='add_task'>".$LANG['job'][30]."</option>";
                  }
                  $tmp = new TicketValidation();
                  if ($tmp->canCreate()) {
                     echo "<option value='submit_validation'>".$LANG['validation'][26]."</option>";
                  }
                  break;

               case 'TicketValidation' :
                  $tmp = new TicketValidation();
                  if ($tmp->canUpdate()) {
                     echo "<option value='validate_ticket'>".$LANG['validation'][0]."</option>";
                  }
                  break;

               case 'CronTask' :
                  echo "<option value='reset'>".$LANG['buttons'][16].
                     " (".$LANG['crontask'][40].")</option>";
                  break;

               case 'NotImportedEmail':
                     echo "<option value='delete_email'>".$LANG['mailing'][133]."</option>";
                     echo "<option value='import_email'>".$LANG['buttons'][37]."</option>";
                  break;

            }
            if ($item instanceof CommonTreeDropdown) {
               if ($isadmin) {
                  echo "<option value='move_under'>".$LANG['buttons'][20]."</option>";
               }
            }
            if ($itemtype!='Entity'
                && ($item instanceof CommonDropdown)
                && $item->maybeRecursive()){
               if ($isadmin) {
                  echo "<option value='merge'>".$LANG['buttons'][48]." - ".$LANG['software'][48];
                  echo "</option>";
               }
            }

            // Plugin Specific actions
            if (isset($PLUGIN_HOOKS['use_massive_action'])) {
               foreach ($PLUGIN_HOOKS['use_massive_action'] as $plugin => $val) {
                  $actions=doOneHook($plugin,'MassiveActions',$itemtype);
                  if (count($actions)) {
                     foreach ($actions as $key => $val) {
                        echo "<option value=\"$key\">$val</option>";
                     }
                  }
               }
            }
         }
         echo "</select>";

         $params=array('action'     => '__VALUE__',
                     'is_deleted' => $is_deleted,
                     'itemtype'   => $itemtype);

         if (count($extraparams)) {
            foreach ($extraparams as $key => $val) {
               $params['extra_'.$key]=$val;
            }
         }

         ajaxUpdateItemOnSelectEvent("massiveaction","show_massiveaction",$CFG_GLPI["root_doc"].
                                    "/ajax/dropdownMassiveAction.php",$params);

         echo "<span id='show_massiveaction'>&nbsp;</span>\n";
      }
   }


}

?>
