<?php
/*
 * @version $Id: document.class.php 9112 2009-10-13 20:17:16Z moyo $
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

// ----------------------------------------------------------------------
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

/// CommonDropdown class - generic dropdown
abstract class CommonDropdown extends CommonDBTM {

   // For delete operation (entity will overload this value)
   public $must_be_replace = false;

   /**
    * Return Additional Fileds for this type
    */
   function getAdditionalFields() {
      return array();
   }

   function defineTabs($ID,$withtemplate) {
      global $LANG;

      $ong=array();
      $ong[1] = $this->getTypeName();
      return $ong;
   }

  /**
   * Have I the right to "create" the Object
   *
   * May be overloaded if needed (ex KnowbaseItem)
   *
   * @return booleen
   **/
   function canCreate() {
      return haveRight(($this->entity_assign?'entity_dropdown':'dropdown'),'w');
   }

   /**
   * Have I the right to "view" the Object
   *
   * May be overloaded if needed
   *
   * @return booleen
   **/
   function canView() {
      return haveRight(($this->entity_assign?'entity_dropdown':'dropdown'),'r');
   }


   /**
    * Display content of Tab
    *
    * @param $ID of the item
    * @param $tab number of the tab
    *
    * @return true if handled (for class stack)
    */
   function showTabContent ($ID, $tab) {
      if ($ID>0) {
         switch ($tab) {
            case -1 :
               Plugin::displayAction($this, $tab);
               return false;

            default :
               return Plugin::displayAction($this, $tab);
         }
      }
      return false;
   }

   function showForm ($target,$ID) {
      global $CFG_GLPI, $LANG;

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
         $this->getEmpty();
      }

      $this->showTabs($ID, '',getActiveTab($this->type),array('itemtype'=>$this->type));
      $this->showFormHeader($target,$ID,'',2);

      $fields = $this->getAdditionalFields();
      $nb=count($fields);

      echo "<tr class='tab_bg_1'><td>".$LANG['common'][16]."&nbsp;:</td>";
      echo "<td>";
      echo "<input type='hidden' name='itemtype' value='".$this->type."'>";
      autocompletionTextField("name",$this->table,"name",$this->fields["name"],40);
      echo "</td>";

      echo "<td rowspan='".($nb+1)."'>";
      echo $LANG['common'][25]."&nbsp;:</td>";
      echo "<td rowspan='".($nb+1)."'>
            <textarea cols='45' rows='".($nb+2)."' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>\n";

      foreach ($fields as $field) {
         echo "<tr class='tab_bg_1'><td>".$field['label']."&nbsp;:</td><td>";
         switch ($field['type']) {
            case 'dropdownUsersID' :
               User::dropdownUsersID($field['name'], $this->fields[$field['name']], "interface", 1,
                                $this->fields["entities_id"]);
               break;

            case 'dropdownValue' :
               CommonDropdown::dropdownValue(getTableNameForForeignKeyField($field['name']),
                              $field['name'], $this->fields[$field['name']],1,
                              $this->fields["entities_id"]);
               break;

            case 'text' :
               autocompletionTextField($field['name'],$this->table,$field['name'],
                                       $this->fields[$field['name']],40);
               break;

            case 'parent' :
               CommonDropdown::dropdownValue($this->table, $field['name'],
                             $this->fields[$field['name']], 1,
                             $this->fields["entities_id"], '',
                             ($ID>0 ? getSonsOf($this->table, $ID) : array()));
               break;

            case 'icon' :
               CommonDropdown::dropdownIcons($field['name'],
                             $this->fields[$field['name']],
                             GLPI_ROOT."/pics/icones");
               if (!empty($this->fields[$field['name']])) {
                  echo "&nbsp;<img style='vertical-align:middle;' alt='' src='".
                       $CFG_GLPI["typedoc_icon_dir"]."/".$this->fields[$field['name']]."'>";
               }
               break;

            case 'bool' :
               dropdownYesNo($field['name'], $this->fields[$field['name']]);
               break;
         }
         echo "</td></tr>\n";
      }

      $candel=true;
      if (isset($this->fields['is_protected']) && $this->fields['is_protected']) {
         $candel=false;
      }
      $this->showFormButtons($ID,'',2,$candel);

      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";

      return true;
   }

   function pre_deleteItem($id) {
      if (isset($this->fields['is_protected']) && $this->fields['is_protected']) {
         return false;
      }
      return true;
   }
   /**
    * Get search function for the class
    *
    * @return array of search option
    */
   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common']           = $LANG['common'][32];;

      $tab[1]['table']         = $this->table;
      $tab[1]['field']         = 'name';
      $tab[1]['linkfield']     = '';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_link'] = $this->type;

      $tab[16]['table']     = $this->table;
      $tab[16]['field']     = 'comment';
      $tab[16]['linkfield'] = 'comment';
      $tab[16]['name']      = $LANG['common'][25];
      $tab[16]['datatype']  = 'text';

      if ($this->entity_assign) {
         $tab[80]['table']     = 'glpi_entities';
         $tab[80]['field']     = 'completename';
         $tab[80]['linkfield'] = 'entities_id';
         $tab[80]['name']      = $LANG['entity'][0];
      }
      if ($this->may_be_recursive) {
         $tab[86]['table']     = $this->table;
         $tab[86]['field']     = 'is_recursive';
         $tab[86]['linkfield'] = 'is_recursive';
         $tab[86]['name']      = $LANG['entity'][9];
         $tab[86]['datatype']  = 'bool';
      }
      return $tab;
   }

   /** Check if the dropdown $ID is used into item tables
    *
    * @param $ID integer : value ID
    *
    * @return boolean : is the value used ?
    */
   function isUsed() {
      global $DB;

      $ID = $this->fields['id'];

      $RELATION = getDbRelations();
      if (isset ($RELATION[$this->table])) {
         foreach ($RELATION[$this->table] as $tablename => $field) {
            if ($tablename[0]!='_') {
               if (!is_array($field)) {
                  $query = "SELECT COUNT(*) AS cpt
                            FROM `$tablename`
                            WHERE `$field` = '$ID'";
                  $result = $DB->query($query);
                  if ($DB->result($result, 0, "cpt") > 0) {
                     return true;
                  }
               } else {
                  foreach ($field as $f) {
                     $query = "SELECT COUNT(*) AS cpt
                               FROM `$tablename`
                               WHERE `$f` = '$ID'";
                     $result = $DB->query($query);
                     if ($DB->result($result, 0, "cpt") > 0) {
                        return true;
                     }
                  }
               }
            }
         }
      }
      return false;
   }

   /**
    * Report if a dropdown have Child
    * Used to (dis)allow delete action
    */
   function haveChildren() {
      return false;
   }

   /**
    * Show a dialog to Confirm delete action
    * And propose a value to replace
    *
    * @param $target string URL
    *
    *
    */
   function showDeleteConfirmForm($target) {
      global $DB, $LANG,$CFG_GLPI;

      if ($this->haveChildren()) {
         echo "<div class='center'><p class='red'>" . $LANG['setup'][74] . "</p></div>";
         return false;
      }

      $ID = $this->fields['id'];

      echo "<div class='center'>";
      echo "<p class='red'>" . $LANG['setup'][63] . "</p>";

      if (!$this->must_be_replace) {
         // Delete form (set to 0)
         echo "<p>" . $LANG['setup'][64] . "</p>";
         echo "<form action='$target' method='post'>";
         echo "<table class='tab_cadre'><tr><td>";
         echo "<input type='hidden' name='id' value='$ID'/>";
         echo "<input type='hidden' name='forcedelete' value='1'/>";
         echo "<input class='button' type='submit' name='delete' value='".$LANG['buttons'][2]."'/></td>";
         echo "<td><input class='button' type='submit' name='annuler' value='".$LANG['buttons'][34]."'/>";
         echo "</td></tr></table>\n";
         echo "</form>";
      }

      // Replace form (set to new value)
      echo "<p>" . $LANG['setup'][65] . "</p>";
      echo "<form action='$target' method='post'>";
      echo "<table class='tab_cadre'><tr><td>";

      if ($this instanceof CommonTreeDropdown) {
         // TreeDropdown => default replacement is parent
         $fk=getForeignKeyFieldForTable($this->table);
         CommonDropdown::dropdownValue($this->table, '_replace_by', $this->fields[$fk], 1,
                       $this->getEntityID(), '', getSonsOf($this->table, $ID));
      } else {
         CommonDropdown::dropdownValue($this->table, '_replace_by', 0, 1, $this->getEntityID(),'',array($ID));
      }
      echo "<input type='hidden' name='id' value='$ID'/>";
      echo "</td><td>";
      echo "<input class='button' type='submit' name='replace' value='".$LANG['buttons'][39]."'/>";
      echo "</td><td>";
      echo "<input class='button' type='submit' name='annuler' value='".$LANG['buttons'][34]."' /></td>";
      echo "</tr></table>\n";
      echo "</form>";
      echo "</div>";
   }

   /** Replace a dropdown item (this) by another one (newID)  and update all linked fields
    * @param $new integer ID of the replacement item
   function replace($newID) {
      global $DB,$CFG_GLPI;

      $oldID = $this->fields['id'];

      $RELATION = getDbRelations();

      if (isset ($RELATION[$this->table])) {
         foreach ($RELATION[$this->table] as $table => $field) {
            if ($table[0]!='_') {
               if (!is_array($field)) {
                  // Manage OCS lock for items - no need for array case
                  if ($table=="glpi_computers" && $CFG_GLPI['use_ocs_mode']) {
                     $query = "SELECT `id`
                               FROM `glpi_computers`
                               WHERE `is_ocs_import` = '1'
                                     AND `$field` = '$oldID'";
                     $result=$DB->query($query);
                     if ($DB->numrows($result)) {
                        if (!function_exists('OcsServer::mergeOcsArray')) {
                           include_once (GLPI_ROOT . "/inc/ocsng.function.php");
                        }
                        while ($data=$DB->fetch_array($result)) {
                           OcsServer::mergeOcsArray($data['id'],array($field),"computer_update");
                        }
                     }
                  }
                  $query = "UPDATE
                            `$table`
                            SET `$field` = '$newID'
                            WHERE `$field` = '$oldID'";
                  $DB->query($query);
               } else {
                  foreach ($field as $f) {
                     $query = "UPDATE
                               `$table`
                               SET `$f` = '$newID'
                               WHERE `$f` = '$oldID'";
                     $DB->query($query);
                  }
               }
            }
         }
      }
   }
    */

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
    * Print out an HTML "<select>" for a dropdown
    *
    * @param $table the dropdown table from witch we want values on the select
    * @param $myname the name of the HTML select
    * @param $display_comment display the comment near the dropdown
    * @param $entity_restrict Restrict to a defined entity
    * @param $used Already used items ID: not to display in dropdown
    * @return nothing (display the select box)
    **/
   static function dropdown($table,$myname,$display_comment=1,$entity_restrict=-1,$used=array()) {

      return CommonDropdown::dropdownValue($table,$myname,'',$display_comment,$entity_restrict,"",$used);
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

      global $DB,$CFG_GLPI,$LANG,$INFOFORM_PAGES,$LINK_ID_TABLE;

      $rand=mt_rand();
      $name="------";
      $comment="";
      $limit_length=$_SESSION["glpidropdown_chars_limit"];

      if (strlen($value)==0) {
         $value=-1;
      }

      if ($value>0 || ($table=="glpi_entities"&&$value>=0)) {
         $tmpname=CommonDropdown::getDropdownName($table,$value,1);
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
                onmouseover=\"cleandisplay('comment_$myname$rand')\" ";

         $which="";
         // Check if table is an dropdown, and user right
         if (key_exists_deep($table, getAllDropdowns())) {
            $which=$table;
         }
         if (!empty($which)) {
            if (is_array($entity_restrict) && count($entity_restrict)==1) {
               $entity_restrict=array_pop($entity_restrict);
            }
            if (!is_array($entity_restrict)) {
               echo " style='cursor:pointer;'  onClick=\"var w = window.open('".
                    $CFG_GLPI["root_doc"]."/front/popup.php?popup=dropdown&amp;which=$which".
                    "&amp;rand=$rand&amp;entities_id=$entity_restrict' ,'glpipopup', 'height=400, ".
                    "width=1000, top=100, left=100, scrollbars=yes' );w.focus();\"";
            }
         }
         echo ">";
         echo "<span class='over_link' id='comment_$myname$rand'>".nl2br($comment)."</span>";

         $type = array_search($table, $LINK_ID_TABLE);
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
                        $name .= " (".CommonDropdown::getDropdownName("glpi_locations",$data["locations_id"]).")";
                        break;

                     case "glpi_softwares" :
                        if ($data["operatingsystems_id"]!=0 && $data["is_helpdesk_visible"] != 0)
                           $comment.="<br>".$LANG['software'][3]."&nbsp;: ".
                                    CommonDropdown::getDropdownName("glpi_operatingsystems",$data["operatingsystems_id"]);
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
}

?>