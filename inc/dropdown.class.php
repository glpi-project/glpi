<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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
    * Print out an HTML "<select>" for a dropdown
    *
    * @param $table the dropdown table from witch we want values on the select
    * @param $myname the name of the HTML select
    * @param $display_comment display the comment near the dropdown
    * @param $entity_restrict Restrict to a defined entity
    * @param $used Already used items ID: not to display in dropdown
    * @return nothing (display the select box)
    **/
   static function dropdownSimple($table,$myname,$display_comment=1,$entity_restrict=-1,$used=array()) {

      return Dropdown::dropdownValue($table,$myname,'',$display_comment,$entity_restrict,"",$used);
   }

   /**
    * Print out an HTML "<select>" for a dropdown with preselected value
    *
    *
    * @param $table the dropdown table from witch we want values on the select
    * @param $myname the name of the HTML select
    * @param $value the preselected value we want
    * @param $display_comment display the comment near the dropdown
    * @param $entity_restrict Restrict to a defined entity
    * @param $update_item Update a specific item on select change on dropdown (need value_fieldname, to_update, url (see ajaxUpdateItemOnSelectEvent for informations) and may have moreparams)
    * @param $used Already used items ID: not to display in dropdown
    * @param $auto_submit boolean : use auto submit on change ?
    * @return nothing (display the select box)
    *
    */
   static function dropdownValue($table,$myname,$value='',$display_comment=1,$entity_restrict=-1,
                          $update_item="",$used=array(),$auto_submit=0) {

      global $DB,$CFG_GLPI,$LANG;

      $rand=mt_rand();
      $name="------";
      $comment="";
      $limit_length=$_SESSION["glpidropdown_chars_limit"];

      // Temporary computation before rewritten function using itemtype param
      $itemtype=getItemTypeForTable($table);
      $item = new $itemtype();

      if (strlen($value)==0) {
         $value=-1;
      }

      if ($value>0 || ($table=="glpi_entities"&&$value>=0)) {
         $tmpname=Dropdown::getDropdownName($table,$value,1);
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

      $use_ajax=false;
      if ($CFG_GLPI["use_ajax"]) {
         $nb=0;
         if ($item->isEntityAssign()) {
            if (!($entity_restrict<0)) {
               $nb=countElementsInTableForEntity($table,$entity_restrict);
            } else {
               $nb=countElementsInTableForMyEntities($table);
            }
         } else {
            $nb=countElementsInTable($table);
         }
         $nb -= count($used);
         if ($nb>$CFG_GLPI["ajax_limit_count"]) {
            $use_ajax=true;
         }
      }

      $params=array('searchText'=>'__VALUE__',
                    'value'=>$value,
                    'table'=>$table,
                    'itemtype'=>$itemtype,
                    'myname'=>$myname,
                    'limit'=>$limit_length,
                    'comment'=>$display_comment,
                    'rand'=>$rand,
                    'entity_restrict'=>$entity_restrict,
                    'update_item'=>$update_item,
                    'used'=>$used,
                    'auto_submit'=>$auto_submit);

      $default="<select name='$myname' id='dropdown_".$myname.$rand."'>";
      $default.="<option value='$value'>$name</option></select>";
      ajaxDropdown($use_ajax,"/ajax/dropdownValue.php",$params,$default,$rand);

      // Display comment
      if ($display_comment) {
         echo "<img alt='' src='".$CFG_GLPI["root_doc"]."/pics/aide.png'
                onmouseout=\"cleanhide('comment_$myname$rand')\"
                onmouseover=\"cleandisplay('comment_$myname$rand')\" >";
         echo "<span class='over_link' id='comment_$myname$rand'>".nl2br($comment)."</span>";

         $type = getItemTypeForTable($table);
         if (class_exists($type)) {
            $item = new $type();
            if ($type
                  && ($item instanceof CommonDropdown)
                  && $item->canCreate()) {

                  echo "<img alt='' title='".$LANG['buttons'][8]."' src='".$CFG_GLPI["root_doc"].
                        "/pics/add_dropdown.png' style='cursor:pointer; margin-left:2px;'  onClick=\"var w = window.open('".
                        $item->getFormURL().
                        "?popup=1&amp;rand=$rand' ,'glpipopup', 'height=400, ".
                        "width=1000, top=100, left=100, scrollbars=yes' );w.focus();\">";
            }
         }
      }
      // Display specific Links
      if ($table=="glpi_suppliers") {
         $supplier = new Supplier();
         if ($supplier->getFromDB($value)) {
            echo $supplier->getLinks();
         }
      }

      return $rand;
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

                     case "glpi_softwares" :
                        if ($data["operatingsystems_id"]!=0 && $data["is_helpdesk_visible"] != 0)
                           $comment.="<br>".$LANG['software'][3]."&nbsp;: ".
                                    Dropdown::getDropdownName("glpi_operatingsystems",$data["operatingsystems_id"]);
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
         return array('name'=>$name,
                      'comment'=>$comment);
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
    * @return nothing (print out an HTML select box)
    */
   static function dropdownTypes($name,$value=0,$types=array(),$used=array()) {
      global $CFG_GLPI;

      $options=array(0=>'----');
      if (count($types)) {
         foreach ($types as $type) {
            if (class_exists($type)) {
               $item = new $type();
               $options[$type]=$item->getTypeName();
            }
         }
         asort($options);
      }
      Dropdown::showFromArray($name,$options,array('value'=>$value,'used'=>$used));
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
            echo "<option value=''>-----</option>";
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
    * @return nothing (print out an HTML select box)
    */
   static function showYesNo($name,$value=0) {
      global $LANG;

      echo "<select name='$name' id='dropdownyesno_$name'>";
      echo "<option value='0' ".(!$value?" selected ":"").">".$LANG['choice'][0]."</option>";
      echo "<option value='1' ".($value?" selected ":"").">".$LANG['choice'][1]."</option>";
      echo "</select>";
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
                                               'Manufacturer'    => $LANG['common'][5]),

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

                  $LANG['Menu'][18] => array('KnowbaseItemCategory' => $LANG['title'][5]),

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
                                               'UserCategory'  => $LANG['users'][2])

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
    */
   static function showItemTypeMenu($optgroup, $value='') {
      global $LANG;

      echo "<table class='tab_cadre'>";
      echo "<tr class='tab_bg_1'><td class='b'>&nbsp;".$LANG['setup'][0]."&nbsp;: ";
      echo "<select id='menu_nav'>";
      foreach($optgroup as $label => $dp) {
         echo "<optgroup label='$label'>";
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
    * @param $value default value
    */
   static function showLanguages($myname,$value) {
      global $CFG_GLPI;

      echo "<select name='$myname'>";

      foreach ($CFG_GLPI["languages"] as $key => $val) {
         if (isset($val[1]) && is_file(GLPI_ROOT ."/locales/".$val[1])) {
            echo "<option value=\"".$key."\"";
            if ($value==$key) {
               echo " selected";
            }
            echo ">".$val[0]." ($key)";
         }
      }
      echo "</select>";
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
         echo "<option value='0'>-----</option>\n";
         foreach ($options as $key => $val) {
            echo "<option value='".$key."'>".$val."</option>";
         }
         echo "</select>";

         $params=array('idtable'=>'__VALUE__',
                     'value'=>$value,
                     'myname'=>$myname,
                     'entity_restrict'=>$entity_restrict);
         if ($onlyglobal) {
            $params['onlyglobal']=1;
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
   static function showInteger($myname,$value,$min=0,$max=100,$step=1,$toadd=array()) {

      echo "<select name='$myname'>\n";
      if (count($toadd)) {
         foreach ($toadd as $key => $val) {
            echo "<option value='$key' ".($key==$value?" selected ":"").">$val</option>";
         }
      }
      for ($i=$min ; $i<=$max ; $i+=$step) {
         echo "<option value='$i' ".($i==$value?" selected ":"").">$i</option>";
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
            'is_private'=>1,
            'is_recursive'=>$is_recursive,
            'entities_id'=>$entity,
            'rand'=>$rand,
         );
         ajaxUpdateItemJsCode('private_switch'.$rand,$CFG_GLPI["root_doc"]."/ajax/private_public.php",$params,false);

         echo "};";
      echo "function setPublic$rand(){\n";

         $params=array(
            'is_private'=>0,
            'is_recursive'=>$is_recursive,
            'entities_id'=>$entity,
            'rand'=>$rand,
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
   * Dropdown of values in an array
   *
   * Parameters which could be used in options array :
   *    - value : integer / preselected value (default 0)
   *    - used : array / Already used items ID: not to display in dropdown (default empty)
   *    - readonly : boolean / used as a readonly item (default false)
   *
   * @param $name select name
   * @param $elements array of elements to display
   * @param $opyions options
   *
   */
   static function showFromArray($name,$elements,$options = array()) {

      //$value='',$used=array()

      $param['value']='';
      $param['used']=array();
      $param['readonly']=false;

      foreach ($options as $key => $val) {
         $param[$key]=$val;
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

}

?>
