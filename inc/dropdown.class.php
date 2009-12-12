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

      global $DB,$CFG_GLPI,$LANG,$INFOFORM_PAGES;

      $rand=mt_rand();
      $name="------";
      $comment="";
      $limit_length=$_SESSION["glpidropdown_chars_limit"];

      if (strlen($value)==0) {
         $value=-1;
      }

      if ($value>0 || ($table=="glpi_entities"&&$value>=0)) {
         $tmpname=Dropdown::getDropdownName($table,$value,1);
         if ($tmpname["name"]!="&nbsp;") {
            $name=$tmpname["name"];
            $comment=$tmpname["comment"];

            if (utf8_strlen($name) > $_SESSION["glpidropdown_chars_limit"]) {
               if (in_array($table,$CFG_GLPI["dropdowntree_tables"])) {
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
         if ($table=='glpi_entities' || in_array($table,$CFG_GLPI["specif_entities_tables"])) {
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
    * Make a select box without parameters value
    *
    *
   * @param $table the dropdown table from witch we want values on the select
    * @param $myname the name of the HTML select
    * @param $value the preselected value we want
    * @param $entity_restrict Restrict to a defined entity
    * @return nothing (print out an HTML select box)
    *
    */
   static function dropdownNoValue($table,$myname,$value,$entity_restrict=-1) {
      global $DB,$CFG_GLPI,$LANG;

      // Make a select box without parameters value

      $where="";
      if (in_array($table,$CFG_GLPI["specif_entities_tables"])) {
         $where.= "WHERE `".$table."`.`entities_id`='".$entity_restrict."'";
      }

      if (in_array($table,$CFG_GLPI["deleted_tables"])) {
         if (empty($where)) {
            $where=" WHERE ";
         } else {
            $where.=" AND ";
         }
         $where=" WHERE `is_deleted`='0'";
      }
      if (in_array($table,$CFG_GLPI["template_tables"])) {
         if (empty($where)) {
            $where=" WHERE ";
         } else {
            $where.=" AND ";
         }
         $where.=" `is_template`='0'";
      }

      if (empty($where)) {
         $where=" WHERE ";
      } else {
         $where.=" AND ";
      }
      $where.=" `id`<>'$value' ";

      if (in_array($table,$CFG_GLPI["dropdowntree_tables"])) {
         $query = "SELECT `id`, `completename` AS name
                   FROM `$table`
                   $where
                   ORDER BY `name`";
      } else {
         $query = "SELECT `id`, `name`
                   FROM `$table`
                   $where
                          AND `id`<>'$value'
                   ORDER BY `name`";
      }
      $result = $DB->query($query);

      echo "<select name=\"$myname\" size='1'>";
      if ($table=="glpi_entities") {
         echo "<option value='0'>".$LANG['entity'][2]."</option>";
      }

      if ($DB->numrows($result) > 0) {
         while ($data=$DB->fetch_array($result)) {
            echo "<option value='".$data['id']."'>".$data['name']."</option>";
         }
      }
      echo "</select>";
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

      if (in_array($table,$CFG_GLPI["dropdowntree_tables"])) {
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
         $field='name';
         if (in_array($table,$CFG_GLPI["dropdowntree_tables"])) {
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
      dropdownArrayValues($name,$options,$value,$used);
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

}

?>
