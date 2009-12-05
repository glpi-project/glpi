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

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

// Functions Dropdown

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
function dropdown($table,$myname,$display_comment=1,$entity_restrict=-1,$used=array()) {

   return dropdownValue($table,$myname,'',$display_comment,$entity_restrict,"",$used);
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
function dropdownValue($table,$myname,$value='',$display_comment=1,$entity_restrict=-1,
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
      $tmpname=getDropdownName($table,$value,1);
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
 * Print out an HTML "<select>" for a dropdown with preselected value
 *
 *
 * @param $myname the name of the HTML select
 * @param $value the preselected value we want
 * @param $locations_id default location ID for search
 * @param $display_comment display the comment near the dropdown
 * @param $entity_restrict Restrict to a defined entity
 * @param $devtype
 * @return nothing (display the select box)
 *
 */
function dropdownNetpoint($myname,$value=0,$locations_id=-1,$display_comment=1,$entity_restrict=-1,
                          $devtype=-1) {

   global $DB,$CFG_GLPI,$LANG;

   $rand=mt_rand();
   $name="------";
   $comment="";
   $limit_length=$_SESSION["glpidropdown_chars_limit"];
   if (empty($value)) {
      $value=0;
   }
   if ($value>0) {
      $tmpname=getDropdownName("glpi_netpoints",$value,1);
      if ($tmpname["name"]!="&nbsp;") {
         $name=$tmpname["name"];
         $comment=$tmpname["comment"];
         $limit_length=max(utf8_strlen($name),$_SESSION["glpidropdown_chars_limit"]);
      }
   }
   $use_ajax=false;
   if ($CFG_GLPI["use_ajax"]) {
      if ($locations_id < 0 || $devtype==NETWORKING_TYPE) {
         $nb=countElementsInTableForEntity("glpi_netpoints",$entity_restrict);
      } else if ($locations_id > 0) {
         $nb=countElementsInTable("glpi_netpoints", "locations_id=$locations_id ");
      } else {
         $nb=countElementsInTable("glpi_netpoints",
               "locations_id=0 ".getEntitiesRestrictRequest(" AND ","glpi_netpoints",'',$entity_restrict));
      }
      if ($nb>$CFG_GLPI["ajax_limit_count"]) {
         $use_ajax=true;
      }
   }

   $params=array('searchText'=>'__VALUE__',
                 'value'=>$value,
                 'locations_id'=>$locations_id,
                 'myname'=>$myname,
                 'limit'=>$limit_length,
                 'comment'=>$display_comment,
                 'rand'=>$rand,
                 'entity_restrict'=>$entity_restrict,
                 'devtype'=>$devtype,);

   $default="<select name='$myname'><option value='$value'>$name</option></select>";
   ajaxDropdown($use_ajax,"/ajax/dropdownNetpoint.php",$params,$default,$rand);

   // Display comment
   if ($display_comment) {
      echo "<img alt='' src='".$CFG_GLPI["root_doc"]."/pics/aide.png'
             onmouseout=\"cleanhide('comment_$myname$rand')\"
             onmouseover=\"cleandisplay('comment_$myname$rand')\" ";
      if (haveRight("entity_dropdown","w")) {
         echo " style='cursor:pointer;' onClick=\"var w = window.open('".$CFG_GLPI["root_doc"].
                "/front/popup.php?popup=dropdown&amp;which=glpi_netpoints&amp;value2=$locations_id".
                "&amp;rand=$rand&amp;entities_id=$entity_restrict' ,'glpipopup', 'height=400, ".
                "width=1000, top=100, left=100, scrollbars=yes' );w.focus();\"";
      }
      echo ">";
      echo "<span class='over_link' id='comment_$myname$rand'>".nl2br($comment)."</span>";
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
function dropdownNoValue($table,$myname,$value,$entity_restrict=-1) {
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
 * Execute the query to select box with all glpi users where select key = name
 *
 * Internaly used by showGroupUsers, dropdownUsers and ajax/dropdownUsers.php
 *
 * @param $count true if execute an count(*),
 * @param $right limit user who have specific right
 * @param $entity_restrict Restrict to a defined entity
 * @param $value default value
 * @param $used Already used items ID: not to display in dropdown
 * @param $search pattern
 *
 * @return mysql result set.
 *
 */
function dropdownUsersSelect ($count=true, $right="all", $entity_restrict=-1, $value=0,
                              $used=array(), $search='') {
   global $DB, $CFG_GLPI;

   if ($entity_restrict<0) {
      $entity_restrict = $_SESSION["glpiactive_entity"];
   }

   $joinprofile=false;
   switch ($right) {
      case "interface" :
         $where=" `glpi_profiles`.`interface`='central' ";
         $joinprofile=true;
         $where.=getEntitiesRestrictRequest("AND","glpi_profiles_users",'',$entity_restrict,1);
         break;

      case "id" :
         $where=" `glpi_users`.`id`='".$_SESSION["glpiID"]."' ";
         break;

      case "all" :
         $where=" `glpi_users`.`id` > '1' ".
                 getEntitiesRestrictRequest("AND","glpi_profiles_users",'',$entity_restrict,1);
         break;

      default :
         $joinprofile=true;
         $where=" ( `glpi_profiles`.`".$right."`='1'
                   AND `glpi_profiles`.`interface`='central' ".
                   getEntitiesRestrictRequest("AND","glpi_profiles_users",'',$entity_restrict,1)." ) ";
         break;
   }

   $where .= " AND `glpi_users`.`is_deleted`='0'
               AND `glpi_users`.`is_active`='1' ";

   if ($value || count($used)) {
      $where .= " AND `glpi_users`.`id` NOT IN (";
      if ($value) {
         $first=false;
         $where .= $value;
      }
      else {
         $first=true;
      }
      foreach($used as $val) {
         if ($first) {
            $first = false;
         } else {
            $where .= ",";
         }
         $where .= $val;
      }
      $where .= ")";
   }

   if ($count) {
      $query = "SELECT COUNT(DISTINCT `glpi_users`.`id` ) AS cpt
                FROM `glpi_users` ";
   } else {
      $query = "SELECT DISTINCT `glpi_users`.*
                FROM `glpi_users` ";
   }
   $query.=" LEFT JOIN `glpi_profiles_users`
                       ON (`glpi_users`.`id` = `glpi_profiles_users`.`users_id`)";
   if ($joinprofile) {
      $query .= " LEFT JOIN `glpi_profiles`
                            ON (`glpi_profiles`.`id` = `glpi_profiles_users`.`profiles_id`) ";
   }

   if ($count) {
      $query.= " WHERE $where ";
   } else {
      if (strlen($search)>0 && $search!=$CFG_GLPI["ajax_wildcard"]) {
         $where.=" AND (`glpi_users`.`name` ".makeTextSearch($search)."
                        OR `glpi_users`.`realname` ".makeTextSearch($search)."
                        OR `glpi_users`.`firstname` ".makeTextSearch($search)."
                        OR CONCAT(`glpi_users`.`realname`,' ',`glpi_users`.`firstname`) ".
                                  makeTextSearch($search).")";
      }
      $query .= " WHERE $where ";

      if ($CFG_GLPI["names_format"]==FIRSTNAME_BEFORE) {
         $query.=" ORDER BY `glpi_users`.`firstname`,`glpi_users`.`realname`,`glpi_users`.`name` ";
      } else {
         $query.=" ORDER BY `glpi_users`.`realname`,`glpi_users`.`firstname`,`glpi_users`.`name` ";
      }

      if ($search != $CFG_GLPI["ajax_wildcard"]) {
         $query .= " LIMIT 0,".$CFG_GLPI["dropdown_max"];
      }
   }
   return $DB->query($query);
}

/**
 * Make a select box with all glpi users where select key = name
 *
 *
 *
 * @param $myname select name
 * @param $value default value
 * @param $right limit user who have specific right : interface -> central ; ID -> only current user ; all -> all users ; sinon specific right like show_all_ticket, create_ticket....
 * @param $all Nobody or All display for none selected $all =0 -> Nobody $all=1 -> All $all=-1-> nothing
 * @param $display_comment display comment near the dropdown
 * @param $entity_restrict Restrict to a defined entity
 * @param $helpdesk_ajax use ajax for helpdesk auto update (mail itemtype)
 * @param $used Already used items ID: not to display in dropdown
 *
 * @return nothing (print out an HTML select box)
 *
 */
function dropdownUsers($myname,$value,$right,$all=0,$display_comment=1,$entity_restrict=-1,
                       $helpdesk_ajax=0,$used=array()) {

   global $DB,$CFG_GLPI,$LANG;

   // Make a select box with all glpi users
   $rand=mt_rand();
   $use_ajax=false;
   if ($CFG_GLPI["use_ajax"]) {
      $res=dropdownUsersSelect (true, $right, $entity_restrict, $value, $used);
      $nb=($res ? $DB->result($res,0,"cpt") : 0);
      if ($nb > $CFG_GLPI["ajax_limit_count"]) {
         $use_ajax=true;
      }
   }
   $user=getUserName($value,2);
   $default_display="";

   $default_display = "<select id='dropdown_".$myname.$rand."' name='$myname'>";
   $default_display.= "<option value='$value'>";
   $default_display.= utf8_substr($user["name"],0,$_SESSION["glpidropdown_chars_limit"]);
   $default_display.= "</option></select>";

   $view_users=(haveRight("user","r"));

   $params=array('searchText'=>'__VALUE__',
                 'value'=>$value,
                 'myname'=>$myname,
                 'all'=>$all,
                 'right'=>$right,
                 'comment'=>$display_comment,
                 'rand'=>$rand,
                 'helpdesk_ajax'=>$helpdesk_ajax,
                 'entity_restrict'=>$entity_restrict,
                 'used'=>$used);
   if ($view_users) {
      $params['update_link']=$view_users;
   }

   $default="";
   if (!empty($value)&&$value>0) {
      $default=$default_display;
   } else {
      if ($all) {
         $default = "<select name='$myname' id='dropdown_".$myname.$rand."'>";
         $default.= "<option value='0'>[ ".$LANG['common'][66]." ]</option></select>";
      } else {
         $default = "<select name='$myname' id='dropdown_".$myname.$rand."'>";
         $default.= "<option value='0'>[ Nobody ]</option></select>\n";
      }
   }

   ajaxDropdown($use_ajax,"/ajax/dropdownUsers.php",$params,$default,$rand);

   // Display comment
   if ($display_comment) {
      if (!$view_users) {
         $user["link"] = '';
      } else if (empty($user["link"])) {
         $user["link"]=$CFG_GLPI['root_doc']."/front/user.php";
      }
      displayToolTip($user["comment"], $user["link"],
                     array('widget'=>'dropdown_'.$myname.$rand,
                           'value'=>'__VALUE__',
                           'table'=>'glpi_users'));
   }
   return $rand;
}

/**
 * Make a select box with all glpi users
 *
 *
 * @param $myname select name
 * @param $value default value
 * @param $display_comment display comment near the dropdown
 * @param $entity_restrict Restrict to a defined entity
 * @param $helpdesk_ajax use ajax for helpdesk auto update (mail itemtype)
 * @param $used Already used items ID: not to display in dropdown
 *
 * @return nothing (print out an HTML select box)
 *
 */
function dropdownAllUsers($myname,$value=0,$display_comment=1,$entity_restrict=-1,$helpdesk_ajax=0,
                          $used=array()) {

   return dropdownUsers($myname,$value,"all",0,$display_comment,$entity_restrict,$helpdesk_ajax,$used);
}

/**
 * Make a select box with all glpi users where select key = ID
 *
 *
 *
* @param $myname select name
 * @param $value default value
 * @param $right limit user who have specific right : interface -> central ; ID -> only current user ; all -> all users ; sinon specific right like show_all_ticket, create_ticket....
 * @param $entity_restrict Restrict to a defined entity
 * @param $display_comment display comment near the dropdown
 * @return nothing (print out an HTML select box)
 */
function dropdownUsersID($myname,$value,$right,$display_comment=1,$entity_restrict=-1) {
   // Make a select box with all glpi users

   return dropdownUsers($myname,$value,$right,0,$display_comment,$entity_restrict);
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
function getDropdownName($table,$id,$withcomment=0) {
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
                     $name .= " (".getDropdownName("glpi_locations",$data["locations_id"]).")";
                     break;

                  case "glpi_softwares" :
                     if ($data["operatingsystems_id"]!=0 && $data["is_helpdesk_visible"] != 0)
                        $comment.="<br>".$LANG['software'][3]."&nbsp;: ".
                                 getDropdownName("glpi_operatingsystems",$data["operatingsystems_id"]);
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
function getDropdownArrayNames($table,$ids) {
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
 * Make a select box with all glpi users in tracking table
 *
 *
 *
 * @param $myname the name of the HTML select
 * @param $value the preselected value we want
 * @param $field field of the glpi_tickets table to lookiup for possible users
 * @param $display_comment display the comment near the dropdown
 * @return nothing (print out an HTML select box)
 */
function dropdownUsersTracking($myname,$value,$field,$display_comment=1) {
   global $CFG_GLPI,$LANG,$DB;

   $rand=mt_rand();
   $use_ajax=false;
   if ($CFG_GLPI["use_ajax"]) {
      if ($CFG_GLPI["ajax_limit_count"]==0) {
         $use_ajax=true;
      } else {
         $query="SELECT COUNT(`".$field."`)
                 FROM `glpi_tickets` ".
                 getEntitiesRestrictRequest("WHERE","glpi_tickets");
         $result=$DB->query($query);
         $nb=$DB->result($result,0,0);
         if ($nb>$CFG_GLPI["ajax_limit_count"]) {
            $use_ajax=true;
         }
      }
   }

   $default="";
   $user=getUserName($value,2);
   $default = "<select name='$myname'><option value='$value'>";
   $default.= utf8_substr($user["name"],0,$_SESSION["glpidropdown_chars_limit"])."</option></select>";
   if (empty($value) || $value==0) {
      $default= "<select name='$myname'><option value='0'>[ ".$LANG['common'][66]." ]</option></select>";
   }

   $params=array('searchText'=>'__VALUE__',
                 'value'=>$value,
                 'field'=>$field,
                 'myname'=>$myname,
                 'comment'=>$display_comment,
                 'rand'=>$rand);

   ajaxDropdown($use_ajax,"/ajax/dropdownUsersTracking.php",$params,$default,$rand);

   if (!haveRight("user","r")) {
      $user["link"] = '';
   } else if (empty($user["link"])) {
      $user["link"]=$CFG_GLPI['root_doc']."/front/user.php";
   }
   // Display comment
   if ($display_comment) {
      displayToolTip($user["comment"], $user["link"],
                     array('widget'=>'dropdown_'.$myname.$rand,
                           'value'=>'__VALUE__',
                           'table'=>'glpi_users'));
   }
   return $rand;
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
function dropdownIcons($myname,$value,$store_path) {
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
 *
 * Make a select box for device type
 *
 *
 * @param $name name of the select box
 * @param $value default device type
 * @param $types types to display
 * @return nothing (print out an HTML select box)
 */
function dropdownDeviceTypes($name,$value=0,$types=array()) {
   global $CFG_GLPI;

   $options=array(0=>'----');
   if (count($types)) {
      $ci=new CommonItem();
      foreach ($types as $type) {
         $ci->setType($type);
         $options[$type]=$ci->getType();
      }
      asort($options);
   }
   dropdownArrayValues($name,$options,$value);
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
function dropdownAllItems($myname,$value_type=0,$value=0,$entity_restrict=-1,$types='',
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
         echo "window.document.getElementById('item_ype$rand').value='".$value_type."';";
         echo "</script>\n";

         $params["idtable"]=$value_type;
         ajaxUpdateItem("show_$myname$rand",$CFG_GLPI["root_doc"]."/ajax/dropdownAllItems.php",$params);
      }
   }
   return $rand;
}

/**
 * Make a select box for a boolean choice (Yes/No)
 *
 * @param $name select name
 * @param $value preselected value.
 * @return nothing (print out an HTML select box)
 */
function dropdownYesNo($name,$value=0) {
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
function getYesNo($value) {
   global $LANG;

   if ($value) {
      return $LANG['choice'][1];
   } else {
      return $LANG['choice'][0];
   }
}

/**
 * Make a select box for a None Read Write choice
 *
 *
 *
 * @param $name select name
 * @param $value preselected value.
 * @param $none display none choice ?
 * @param $read display read choice ?
 * @param $write display write choice ?
 * @return nothing (print out an HTML select box)
 */
function dropdownNoneReadWrite($name,$value,$none=1,$read=1,$write=1) {
   global $LANG;

   echo "<select name='$name'>\n";
   if ($none) {
      echo "<option value='' ".(empty($value)?" selected ":"").">".$LANG['profiles'][12]."</option>";
   }
   if ($read) {
      echo "<option value='r' ".($value=='r'?" selected ":"").">".$LANG['profiles'][10]."</option>";
   }
   if ($write) {
      echo "<option value='w' ".($value=='w'?" selected ":"").">".$LANG['profiles'][11]."</option>";
   }
   echo "</select>";
}

/**
 * Make a select box for Tracking my devices
 *
 *
 * @param $userID User ID for my device section
 * @param $entity_restrict restrict to a specific entity
 * @param $itemtype of selected item
 * @param $items_id of selected item
 *
 * @return nothing (print out an HTML select box)
 */
function dropdownMyDevices($userID=0, $entity_restrict=-1, $itemtype=0, $items_id=0) {
   global $DB,$LANG,$CFG_GLPI,$LINK_ID_TABLE;

   if ($userID==0) {
      $userID=$_SESSION["glpiID"];
   }

   $rand=mt_rand();
   $already_add=array();

   if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"]&pow(2,HELPDESK_MY_HARDWARE)) {
      $my_devices="";

      $ci=new CommonItem();
      $my_item= $itemtype.'_'.$items_id;

      // My items
      foreach ($CFG_GLPI["linkuser_types"] as $itemtype) {
         if (isPossibleToAssignType($itemtype)) {
            $query="SELECT *
                    FROM ".$LINK_ID_TABLE[$itemtype]."
                    WHERE `users_id`='".$userID."'";
            if (in_array($LINK_ID_TABLE[$itemtype],$CFG_GLPI["deleted_tables"])) {
               $query.=" AND `is_deleted`='0' ";
            }
            if (in_array($LINK_ID_TABLE[$itemtype],$CFG_GLPI["template_tables"])) {
               $query.=" AND `is_template`='0' ";
            }
            if (in_array($itemtype,$CFG_GLPI["helpdesk_visible_types"])){
               $query.=" AND `is_helpdesk_visible`='1' ";
            }

            $query.=getEntitiesRestrictRequest("AND",$LINK_ID_TABLE[$itemtype],"",$entity_restrict,
                                               in_array($itemtype,$CFG_GLPI["recursive_type"]));
            $query.=" ORDER BY `name` ";

            $result=$DB->query($query);
            if ($DB->numrows($result)>0) {
               $ci->setType($itemtype);
               $type_name=$ci->getType();

               while ($data=$DB->fetch_array($result)) {
                  $output=$data["name"];
                  if ($itemtype!=SOFTWARE_TYPE) {
                     if (!empty($data['serial'])) {
                        $output.=" - ".$data['serial'];
                     }
                     if (!empty($data['otherserial'])) {
                        $output.=" - ".$data['otherserial'];
                     }
                  }
                  if (empty($output)||$_SESSION["glpiis_ids_visible"]) {
                     $output.=" (".$data['id'].")";
                  }
                  $my_devices.="<option title=\"$output\" value='".$itemtype."_".$data["id"]."' ";
                  $my_devices.=($my_item==$itemtype."_".$data["id"]?"selected":"").">$type_name - ";
                  $my_devices.=utf8_substr($output,0,$_SESSION["glpidropdown_chars_limit"])."</option>";

                  $already_add[$itemtype][]=$data["id"];
               }
            }
         }
      }
      if (!empty($my_devices)) {
         $my_devices="<optgroup label=\"".$LANG['tracking'][1]."\">".$my_devices."</optgroup>";
      }

      // My group items
      if (haveRight("show_group_hardware","1")) {
         $group_where="";
         $groups=array();
         $query="SELECT `glpi_groups_users`.`groups_id`, `glpi_groups`.`name`
                 FROM `glpi_groups_users`
                 LEFT JOIN `glpi_groups` ON (`glpi_groups`.`id` = `glpi_groups_users`.`groups_id`)
                 WHERE `glpi_groups_users`.`users_id`='".$userID."' ".
                       getEntitiesRestrictRequest("AND","glpi_groups","",$entity_restrict);
         $result=$DB->query($query);
         $first=true;
         if ($DB->numrows($result)>0) {
            while ($data=$DB->fetch_array($result)) {
               if ($first) {
                  $first=false;
               } else {
                  $group_where.=" OR ";
               }
               $group_where.=" `groups_id` = '".$data["groups_id"]."' ";
            }

            $tmp_device="";
            foreach ($CFG_GLPI["linkgroup_types"] as $itemtype) {
               if (isPossibleToAssignType($itemtype)) {
                  $query="SELECT *
                          FROM `".$LINK_ID_TABLE[$itemtype]."`
                          WHERE ($group_where) ".
                                getEntitiesRestrictRequest("AND",$LINK_ID_TABLE[$itemtype],"",
                                   $entity_restrict,in_array($itemtype,$CFG_GLPI["recursive_type"]));

                  if (in_array($LINK_ID_TABLE[$itemtype],$CFG_GLPI["deleted_tables"])) {
                     $query.=" AND `is_deleted`='0' ";
                  }
                  if (in_array($LINK_ID_TABLE[$itemtype],$CFG_GLPI["template_tables"])) {
                     $query.=" AND `is_template`='0' ";
                  }

                  $result=$DB->query($query);
                  if ($DB->numrows($result)>0) {
                     $ci->setType($itemtype);
                     $type_name=$ci->getType();
                     if (!isset($already_add[$itemtype])) {
                        $already_add[$itemtype]=array();
                     }
                     while ($data=$DB->fetch_array($result)) {
                        if (!in_array($data["id"],$already_add[$itemtype])) {
                           $output='';
                           if (isset($data["name"])) {
                              $output = $data["name"];
                           }
                           if (isset($data['serial'])) {
                              $output .= " - ".$data['serial'];
                           }
                           if (isset($data['otherserial'])) {
                              $output .= " - ".$data['otherserial'];
                           }
                           if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                              $output .= " (".$data['id'].")";
                           }
                           $tmp_device.="<option title=\"$output\" value='".$itemtype."_".$data["id"];
                           $tmp_device.="' ".($my_item==$itemtype."_".$data["id"]?"selected":"").">";
                           $tmp_device.="$type_name - ";
                           $tmp_device.=utf8_substr($output,0,$_SESSION["glpidropdown_chars_limit"]);
                           $tmp_device.="</option>";

                           $already_add[$itemtype][]=$data["id"];
                        }
                     }
                  }
               }
            }
            if (!empty($tmp_device)) {
               $my_devices.="<optgroup label=\"".$LANG['tracking'][1]." - ".$LANG['common'][35]."\">";
               $my_devices.=$tmp_device."</optgroup>";
            }
         }
      }
      // Get linked items to computers
      if (isset($already_add[COMPUTER_TYPE]) && count($already_add[COMPUTER_TYPE])) {
         $search_computer=" XXXX IN (".implode(',',$already_add[COMPUTER_TYPE]).') ';
         $tmp_device="";

         // Direct Connection
         $types=array(PERIPHERAL_TYPE,
                      MONITOR_TYPE,
                      PRINTER_TYPE,
                      PHONE_TYPE);
         foreach ($types as $itemtype) {
            if (in_array($itemtype,$_SESSION["glpiactiveprofile"]["helpdesk_item_type"])) {
               if (!isset($already_add[$itemtype])) {
                  $already_add[$itemtype]=array();
               }
               $query="SELECT DISTINCT ".$LINK_ID_TABLE[$itemtype].".*
                       FROM `glpi_computers_items`
                       LEFT JOIN ".$LINK_ID_TABLE[$itemtype]."
                            ON (`glpi_computers_items`.`items_id`=".$LINK_ID_TABLE[$itemtype].".`id`)
                       WHERE `glpi_computers_items`.`itemtype`='$itemtype'
                             AND  ".str_replace("XXXX","`glpi_computers_items`.`computers_id`",
                                                $search_computer);
               if (in_array($LINK_ID_TABLE[$itemtype],$CFG_GLPI["deleted_tables"])) {
                  $query.=" AND `is_deleted`='0' ";
               }
               if (in_array($LINK_ID_TABLE[$itemtype],$CFG_GLPI["template_tables"])) {
                  $query.=" AND `is_template`='0' ";
               }
               $query.=getEntitiesRestrictRequest("AND",$LINK_ID_TABLE[$itemtype],"",$entity_restrict)
                       ." ORDER BY ".$LINK_ID_TABLE[$itemtype].".name";

               $result=$DB->query($query);
               if ($DB->numrows($result)>0) {
                  $ci->setType($itemtype);
                  $type_name=$ci->getType();
                  while ($data=$DB->fetch_array($result)) {
                     if (!in_array($data["id"],$already_add[$itemtype])) {
                        $output=$data["name"];
                        if ($itemtype!=SOFTWARE_TYPE) {
                           $output.=" - ".$data['serial']." - ".$data['otherserial'];
                        }
                        if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                           $output.=" (".$data['id'].")";
                        }
                        $tmp_device.="<option title=\"$output\" value='".$itemtype."_".$data["id"];
                        $tmp_device.="' ".($my_item==$itemtype."_".$data["id"]?"selected":"").">";
                        $tmp_device.="$type_name - ";
                        $tmp_device.=utf8_substr($output,0,$_SESSION["glpidropdown_chars_limit"]);
                        $tmp_device.="</option>";

                        $already_add[$itemtype][]=$data["id"];
                     }
                  }
               }
            }
         }
         if (!empty($tmp_device)) {
            $my_devices.="<optgroup label=\"".$LANG['reports'][36]."\">".$tmp_device."</optgroup>";
         }

         // Software
         if (in_array(SOFTWARE_TYPE,$_SESSION["glpiactiveprofile"]["helpdesk_item_type"])) {
            $query = "SELECT DISTINCT `glpi_softwareversions`.`name` AS version,
                                      `glpi_softwares`.`name` AS name, `glpi_softwares`.`id`
                      FROM `glpi_computers_softwareversions`, `glpi_softwares`,
                           `glpi_softwareversions`
                      WHERE `glpi_computers_softwareversions`.`softwareversions_id`=
                               `glpi_softwareversions`.`id`
                            AND `glpi_softwareversions`.`softwares_id` = `glpi_softwares`.`id`
                            AND ".str_replace("XXXX","`glpi_computers_softwareversions`.`computers_id`",
                                              $search_computer)."
                            AND `glpi_softwares`.`is_helpdesk_visible`='1' ".
                            getEntitiesRestrictRequest("AND","glpi_softwares","",$entity_restrict)."
                      ORDER BY `glpi_softwares`.`name`";

            $result=$DB->query($query);
            if ($DB->numrows($result)>0) {
               $tmp_device="";
               $ci->setType(SOFTWARE_TYPE);
               $type_name=$ci->getType();
               if (!isset($already_add[SOFTWARE_TYPE])) {
                  $already_add[SOFTWARE_TYPE]=array();
               }
               while ($data=$DB->fetch_array($result)) {
                  if (!in_array($data["id"],$already_add[SOFTWARE_TYPE])) {
                     $tmp_device.="<option value='".SOFTWARE_TYPE."_".$data["id"]."' ";
                     $tmp_device.=($my_item==SOFTWARE_TYPE."_".$data["id"]?"selected":"").">";
                     $tmp_device.="$type_name - ".$data["name"]." (v. ".$data["version"].")";
                     $tmp_device.=($_SESSION["glpiis_ids_visible"]?" (".$data["id"].")":"");
                     $tmp_device.="</option>";

                     $already_add[SOFTWARE_TYPE][]=$data["id"];
                  }
               }
               if (!empty($tmp_device)) {
                  $my_devices.="<optgroup label=\"".ucfirst($LANG['software'][17])."\">";
                  $my_devices.=$tmp_device."</optgroup>";
               }
            }
         }
      }
      echo "<div id='tracking_my_devices'>";
      echo $LANG['tracking'][1].":&nbsp;<select id='my_items' name='_my_items'><option value=''>--- ";
      echo $LANG['help'][30]." ---</option>$my_devices</select></div>";
   }
}

/**
 * Make a select box for Tracking All Devices
 *
 * @param $myname select name
 * @param $itemtype preselected value.for item type
 * @param $items_id preselected value for item ID
 * @param $admin is an admin access ?
 * @param $entity_restrict Restrict to a defined entity
 * @return nothing (print out an HTML select box)
 */
function dropdownTrackingAllDevices($myname,$itemtype,$items_id=0,$admin=0,$entity_restrict=-1) {
   global $LANG,$CFG_GLPI,$DB,$LINK_ID_TABLE;

   $rand=mt_rand();

   if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"]==0) {
      echo "<input type='hidden' name='$myname' value='0'>";
      echo "<input type='hidden' name='items_id' value='0'>";
   } else {
      echo "<div id='tracking_all_devices'>";
      if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"]&pow(2,HELPDESK_ALL_HARDWARE)) {
         // Display a message if view my hardware
         if (!$admin
             && $_SESSION["glpiactiveprofile"]["helpdesk_hardware"]&pow(2,HELPDESK_MY_HARDWARE)) {
            echo $LANG['tracking'][2]."&nbsp;: ";
         }

         $types = getAllTypesForHelpdesk();
         echo "$itemtype<select id='search_$myname$rand' name='$myname'>\n";
         echo "<option value='-1' >-----</option>\n";
         echo "<option value='' ".(empty($itemtype)?" selected":"").">".$LANG['help'][30]."</option>";
         foreach ($types as $type => $label) {
            echo "<option value='".$type."' ".(($type==$itemtype)?" selected":"").">".$label;
            echo "</option>\n";
         }
         echo "</select>";

         $params=array('itemtype'=>'__VALUE__',
                       'entity_restrict'=>$entity_restrict,
                       'admin'=>$admin,
                       'myname'=>"items_id",);

         ajaxUpdateItemOnSelectEvent("search_$myname$rand","results_$myname$rand",$CFG_GLPI["root_doc"].
                                     "/ajax/dropdownTrackingDeviceType.php",$params);

         echo "<span id='results_$myname$rand'>\n";

         if ($itemtype && $items_id) {
            $ci=new CommonItem();
            if ($ci->getFromDB($itemtype, $items_id)) {
               echo "<select name='items_id'>\n";
               echo "<option value='$items_id'>".$ci->getName();
               echo "</option></select>";
            }
         }
         echo "</span>\n";
      }
      echo "</div>";
   }
   return $rand;
}

/**
 * Make a select box for connections
 *
 * @param $itemtype type to connect
 * @param $fromtype from where the connection is
 * @param $myname select name
 * @param $entity_restrict Restrict to a defined entity
 * @param $onlyglobal display only global devices (used for templates)
 * @param $used Already used items ID: not to display in dropdown
 *
 * @return nothing (print out an HTML select box)
 */
function dropdownConnect($itemtype,$fromtype,$myname,$entity_restrict=-1,$onlyglobal=0,
                         $used=array()) {
   global $CFG_GLPI,$LINK_ID_TABLE;

   $rand=mt_rand();

   $use_ajax=false;
   if ($CFG_GLPI["use_ajax"]) {
      $nb=0;
      if ($entity_restrict>=0) {
         $nb=countElementsInTableForEntity($LINK_ID_TABLE[$itemtype],$entity_restrict);
      } else {
         $nb=countElementsInTableForMyEntities($LINK_ID_TABLE[$itemtype]);
      }
      if ($nb>$CFG_GLPI["ajax_limit_count"]) {
         $use_ajax=true;
      }
   }

   $params=array('searchText'=>'__VALUE__',
                 'fromtype'=>$fromtype,
                 'idtable'=>$itemtype,
                 'myname'=>$myname,
                 'onlyglobal'=>$onlyglobal,
                 'entity_restrict'=>$entity_restrict,
                 'used'=>$used);

   $default="<select name='$myname'><option value='0'>------</option></select>\n";
   ajaxDropdown($use_ajax,"/ajax/dropdownConnect.php",$params,$default,$rand);

   return $rand;
}

/**
 * Make a select box for  connected port
 *
 * @param $ID ID of the current port to connect
 * @param $myname select name
 * @param $entity_restrict Restrict to a defined entity (or an array of entities)
 * @return nothing (print out an HTML select box)
 */
function dropdownConnectPort($ID,$myname,$entity_restrict=-1) {
   global $LANG,$CFG_GLPI;

   $rand=mt_rand();
   echo "<select name='itemtype[$ID]' id='itemtype$rand'>";
   echo "<option value='0'>-----</option>";

   $ci =new CommonItem();

   foreach ($CFG_GLPI["netport_types"] as $itemtype) {
      $ci->setType($itemtype);
      echo "<option value='".$itemtype."'>".$ci->getType()."</option>";
   }
   echo "</select>";

   $params=array('itemtype'=>'__VALUE__',
                 'entity_restrict'=>$entity_restrict,
                 'current'=>$ID,
                 'myname'=>$myname);

   ajaxUpdateItemOnSelectEvent("itemtype$rand","show_$myname$rand",$CFG_GLPI["root_doc"].
                               "/ajax/dropdownConnectPortDeviceType.php",$params);

   echo "<span id='show_$myname$rand'>&nbsp;</span>\n";

   return $rand;
}

/**
 * Make a select box for link document
 *
 * @param $myname name of the select box
 * @param $entity_restrict restrict multi entity
 * @param $used Already used items ID: not to display in dropdown
 * @return nothing (print out an HTML select box)
 */
function dropdownDocument($myname,$entity_restrict='',$used=array()) {
   global $DB,$LANG,$CFG_GLPI;

   $rand=mt_rand();

   $where=" WHERE `glpi_documents`.`is_deleted`='0' ".
                  getEntitiesRestrictRequest("AND","glpi_documents",'',$entity_restrict,true);
   if (count($used)) {
      $where .= " AND `id` NOT IN ('0','".implode("','",$used)."')";
   }

   $query="SELECT *
           FROM `glpi_documentcategories`
           WHERE `id` IN (SELECT DISTINCT `documentcategories_id`
                          FROM `glpi_documents`
                          $where)
           ORDER BY `name`";
   $result=$DB->query($query);

   echo "<select name='_rubdoc' id='rubdoc$rand'>";
   echo "<option value='0'>------</option>";
   while ($data=$DB->fetch_assoc($result)) {
      echo "<option value='".$data['id']."'>".$data['name']."</option>";
   }
   echo "</select>";

   $params=array('rubdoc'=>'__VALUE__',
                 'entity_restrict'=>$entity_restrict,
                 'rand'=>$rand,
                 'myname'=>$myname,
                 'used'=>$used);

   ajaxUpdateItemOnSelectEvent("rubdoc$rand","show_$myname$rand",$CFG_GLPI["root_doc"].
                               "/ajax/dropdownRubDocument.php",$params);

   echo "<span id='show_$myname$rand'>";
   $_POST["entity_restrict"]=$entity_restrict;
   $_POST["rubdoc"]=0;
   $_POST["myname"]=$myname;
   $_POST["rand"]=$rand;
   $_POST["used"]=$used;
   include (GLPI_ROOT."/ajax/dropdownRubDocument.php");
   echo "</span>\n";

   return $rand;
}

/**
 * Make a select box for  software to install
 *
 *
 * @param $myname select name
 * @param $massiveaction is it a massiveaction select ?
 * @param $entity_restrict Restrict to a defined entity
 * @return nothing (print out an HTML select box)
 */
function dropdownSoftwareToInstall($myname,$entity_restrict,$massiveaction=0) {
   global $CFG_GLPI;

   $rand=mt_rand();
   $use_ajax=false;

   if ($CFG_GLPI["use_ajax"]) {
      if (countElementsInTableForEntity("glpi_softwares",$entity_restrict)
          > $CFG_GLPI["ajax_limit_count"]) {
         $use_ajax=true;
      }
   }

   $params=array('searchText'=>'__VALUE__',
                 'myname'=>$myname,
                 'entity_restrict'=>$entity_restrict);

   $default="<select name='$myname'><option value='0'>------</option></select>";
   ajaxDropdown($use_ajax,"/ajax/dropdownSelectSoftware.php",$params,$default,$rand);

   return $rand;
}

/**
 * Make a select box for  software to install
 *
 *
 * @param $myname select name
 * @param $softwares_id ID of the software
 * @param $value value of the selected version
 * @return nothing (print out an HTML select box)
 */
function dropdownSoftwareVersions($myname,$softwares_id,$value=0) {
   global $CFG_GLPI;

   $rand=mt_rand();
   $params=array('softwares_id'=>$softwares_id,
                 'myname'=>$myname,
                 'value'=>$value);

   $default="<select name='$myname'><option value='0'>------</option></select>";
   ajaxDropdown(false,"/ajax/dropdownInstallVersion.php",$params,$default,$rand);

   return $rand;
}

/**
 * Show div with auto completion
 *
 * @param $myname text field name
 * @param $table table to search for autocompletion
 * @param $field field to serahc for autocompletion
 * @param $value value to fill text field
 * @param $size size of the text field
 * @param $option option of the textfield
 * @param $entity_restrict Restrict to a defined entity
 * @param $user_restrict Restrict to a specific user
 * @return nothing (print out an HTML div)
 */
function autocompletionTextField($myname,$table,$field,$value='',$size=40,$entity_restrict=-1,
                                 $user_restrict=-1,$option='') {
   global $CFG_GLPI;

   if ($CFG_GLPI["use_ajax"] && $CFG_GLPI["use_ajax_autocompletion"]) {
      $rand=mt_rand();
      echo "<input $option id='textfield_$myname$rand' type='text' name='$myname' value=\"".
             cleanInputText($value)."\" size='$size'>\n";
      $output = "<script type='text/javascript' >\n";

      $output .= "var textfield_$myname$rand = new Ext.data.Store({
         proxy: new Ext.data.HttpProxy(
         new Ext.data.Connection ({
            url: '".$CFG_GLPI["root_doc"]."/ajax/autocompletion.php',
            extraParams : {
               table: '$table',
               field: '$field'";

            if (!empty($entity_restrict) && $entity_restrict>=0){
               $output .= ",entity_restrict: $entity_restrict";
            }
            if (!empty($user_restrict) && $user_restrict>=0){
               $output .= ",user_restrict: $user_restrict";
            }
            $output .= "
            },
            method: 'POST'
            })
         ),
         reader: new Ext.data.JsonReader({
            totalProperty: 'totalCount',
            root: 'items',
            id: 'value'
         }, [
         {name: 'value', mapping: 'value'}
         ])
      });
      ";

      $output .= "var searchfield_$myname$rand = new Ext.ux.form.SpanComboBox({
         store: textfield_$myname$rand,
         displayField:'value',
         pageSize:20,
         hideTrigger:true,
         minChars:3,
         resizable:true,
         minListWidth:".($size*5).", // IE problem : wrong computation of the width of the ComboBox field
         applyTo: 'textfield_$myname$rand'
      });";

      $output .= "</script>";

      echo $output;

   } else {
      echo "<input $option type='text' name='$myname' value=\"".
             cleanInputText($value)."\" size='$size'>\n";
   }
}

/**
 * Make a select box form  for device type
 *
 * @param $target URL to post the form
 * @param $computers_id computer ID
 * @param $withtemplate is it a template computer ?
 * @return nothing (print out an HTML select box)
 */
function device_selecter($target,$computers_id,$withtemplate='') {
   global $LANG,$CFG_GLPI;

   if (!haveRight("computer","w")) {
      return false;
   }
   if (!empty($withtemplate) && $withtemplate == 2) {
      //do nothing
   } else {
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr  class='tab_bg_1'><td colspan='2' class='right' width='30%'>";
      echo $LANG['devices'][0]."&nbsp;:";
      echo "</td>";
      echo "<td colspan='63'>";
      echo "<form action=\"$target\" method=\"post\">";

      $rand=mt_rand();

      $devices=getDictDeviceLabel();

      echo "<select name='devicetype' id='device$rand'>";

      echo '<option value="-1">-----</option>';


      foreach ($devices as $i => $name) {
         echo '<option value="'.$i.'">'.$name.'</option>';
      }
      echo "</select>";

      $params=array('idtable'=>'__VALUE__',
                    'myname'=>'devices_id');

      ajaxUpdateItemOnSelectEvent("device$rand","showdevice$rand",$CFG_GLPI["root_doc"].
                                  "/ajax/dropdownDevice.php",$params);

      echo "<span id='showdevice$rand'>&nbsp;</span>\n";

      echo '<input type="hidden" name="withtemplate" value="'.$withtemplate.'" >';
      echo '<input type="hidden" name="connect_device" value="'.true.'" >';
      echo '<input type="hidden" name="computers_id" value="'.$computers_id.'" >';
      echo '<input type="submit" class ="submit" value="'.$LANG['buttons'][2].'" >';
      echo '</form>';
      echo '</td>';
      echo '</tr></table>';
   }
}

/**
 * Dropdown of actions for massive action
 *
 * @param $itemtype item type
 * @param $is_deleted massive action for deleted items ?
 * @param $extraparams array of extra parameters
 */
function dropdownMassiveAction($itemtype,$is_deleted=0,$extraparams=array()) {
   global $LANG,$CFG_GLPI,$PLUGIN_HOOKS;

   $isadmin=haveTypeRight($itemtype,"w");

   echo '<select name="massiveaction" id="massiveaction">';
   echo '<option value="-1" selected>-----</option>';
   if (!in_array($itemtype,$CFG_GLPI["massiveaction_noupdate_types"])
       && ($isadmin ||(in_array($itemtype,$CFG_GLPI["infocom_types"]) && haveTypeRight(INFOCOM_TYPE,"w"))
                    || ($itemtype==TRACKING_TYPE && haveRight('update_ticket',1)))) {

      echo "<option value='update'>".$LANG['buttons'][14]."</option>";
   }

   if ($is_deleted) {
      if ($isadmin) {
         echo "<option value='purge'>".$LANG['buttons'][22]."</option>";
         echo "<option value='restore'>".$LANG['buttons'][21]."</option>";
      }
   } else {
      // No delete for entities and tracking of not have right
      if (!in_array($itemtype,$CFG_GLPI["massiveaction_nodelete_types"])
          && (($isadmin && $itemtype != TRACKING_TYPE)
              || ($itemtype==TRACKING_TYPE && haveRight('delete_ticket',1)))) {

         echo "<option value='delete'>".$LANG['buttons'][6]."</option>";
      }
      if ($isadmin && in_array($itemtype,array(PHONE_TYPE,
                                               PRINTER_TYPE,
                                               PERIPHERAL_TYPE,
                                               MONITOR_TYPE))) {

         echo "<option value='connect'>".$LANG['buttons'][9]."</option>";
         echo "<option value='disconnect'>".$LANG['buttons'][10]."</option>";
      }
      if (haveTypeRight(DOCUMENT_TYPE,"w") && in_array($itemtype,$CFG_GLPI["doc_types"])) {
         echo "<option value='add_document'>".$LANG['document'][16]."</option>";
      }

      if (haveTypeRight(CONTRACT_TYPE,"w") && in_array($itemtype,$CFG_GLPI["contract_types"])) {
         echo "<option value='add_contract'>".$LANG['financial'][36]."</option>";
      }
      if (haveRight('transfer','r') && isMultiEntitiesMode()
          && in_array($itemtype, array(CARTRIDGEITEM_TYPE,COMPUTER_TYPE,CONSUMABLEITEM_TYPE,CONTACT_TYPE,
                                       CONTRACT_TYPE,ENTERPRISE_TYPE,MONITOR_TYPE,NETWORKING_TYPE,
                                       PERIPHERAL_TYPE,PHONE_TYPE,PRINTER_TYPE,SOFTWARE_TYPE,
                                       SOFTWARELICENSE_TYPE,TRACKING_TYPE,DOCUMENT_TYPE,
                                       GROUP_TYPE,LINK_TYPE))
          && $isadmin) {

         echo "<option value=\"add_transfer_list\">".$LANG['buttons'][48]."</option>";
      }
      switch ($itemtype) {
         case SOFTWARE_TYPE :
            if ($isadmin
                && countElementsInTable("glpi_rules","sub_type='".RULE_SOFTWARE_CATEGORY."'") > 0) {
               echo "<option value=\"compute_software_category\">".$LANG['rulesengine'][38]." ".
                      $LANG['rulesengine'][40]."</option>";
            }
            if (haveRight("rule_dictionnary_software","w")
                && countElementsInTable("glpi_rules","sub_type='".RULE_DICTIONNARY_SOFTWARE."'") > 0) {
               echo "<option value=\"replay_dictionnary\">".$LANG['rulesengine'][76]."</option>";
            }
            break;

         case COMPUTER_TYPE :
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

         case ENTERPRISE_TYPE :
            if ($isadmin) {
               echo "<option value='add_contact'>".$LANG['financial'][24]."</option>";
            }
            break;

         case CONTACT_TYPE :
            if ($isadmin) {
               echo "<option value='add_enterprise'>".$LANG['financial'][25]."</option>";
            }
            break;

         case USER_TYPE :
            if ($isadmin) {
               echo "<option value='add_group'>".$LANG['setup'][604]."</option>";
               echo "<option value='add_userprofile'>".$LANG['setup'][607]."</option>";
               echo "<option value='change_authtype'>".$LANG['login'][30]."</option>";
            }
            if (haveRight("user","w")) {
               echo "<option value='force_user_ldap_update'>".$LANG['ocsng'][24]."</option>";
            }
            break;

         case TRACKING_TYPE :
            if (haveRight("comment_all_ticket","1")) {
               echo "<option value='add_followup'>".$LANG['job'][29]."</option>";
            }
            break;

         case 'CronTask' :
            echo "<option value='reset'>".$LANG['buttons'][16].
               " (".$LANG['crontask'][40].")</option>";
            break;

         case 'TicketCategory' :
         case 'TaskCategory' :
         case 'Location' :
            if ($isadmin) {
               echo "<option value='move_under'>".$LANG['buttons'][20]."</option>";
            }
            break;
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

   $params=array('action'=>'__VALUE__',
                 'is_deleted'=>$is_deleted,
                 'itemtype'=>$itemtype);

   if (count($extraparams)) {
      foreach ($extraparams as $key => $val) {
         $params['extra_'.$key]=$val;
      }
   }

   ajaxUpdateItemOnSelectEvent("massiveaction","show_massiveaction",$CFG_GLPI["root_doc"].
                               "/ajax/dropdownMassiveAction.php",$params);

   echo "<span id='show_massiveaction'>&nbsp;</span>\n";
}

/**
 * Dropdown of actions for massive action of networking ports
 *
 * @param $itemtype item type
 */
function dropdownMassiveActionPorts($itemtype) {
   global $LANG,$CFG_GLPI;

   echo "<select name='massiveaction' id='massiveaction'>";

   echo "<option value='-1' selected>-----</option>";
   echo "<option value='delete'>".$LANG['buttons'][6]."</option>";
   echo "<option value='assign_vlan'>".$LANG['networking'][55]."</option>";
   echo "<option value='unassign_vlan'>".$LANG['networking'][58]."</option>";
   echo "<option value='move'>".$LANG['buttons'][20]."</option>";
   echo "</select>";

   $params=array('action'=>'__VALUE__',
                 'itemtype'=>$itemtype);

   ajaxUpdateItemOnSelectEvent("massiveaction","show_massiveaction",$CFG_GLPI["root_doc"].
                               "/ajax/dropdownMassiveActionPorts.php",$params);

   echo "<span id='show_massiveaction'>&nbsp;</span>\n";
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
function globalManagementDropdown($target,$withtemplate,$ID,$value,$management_restrict=0) {
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
 * Dropdown for alerting of contracts
 *
* @param $myname select name
 * @param $value default value
 */
function dropdownContractAlerting($myname,$value) {
   global $LANG;

   echo "<select name='$myname'>";
   echo "<option value='0' ".($value==0?"selected":"")." >-------</option>";
   echo "<option value='".pow(2,ALERT_END)."' ".($value==pow(2,ALERT_END)?"selected":"")." >".
          $LANG['buttons'][32]."</option>";
   echo "<option value='".pow(2,ALERT_NOTICE)."' ".($value==pow(2,ALERT_NOTICE)?"selected":"")." >".
          $LANG['financial'][10]."</option>";
   echo "<option value='".(pow(2,ALERT_END)+pow(2,ALERT_NOTICE))."' ".
          ($value==(pow(2,ALERT_END)+pow(2,ALERT_NOTICE))?"selected":"")." >".
          $LANG['buttons'][32]." + ".$LANG['financial'][10]."</option>";
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
function dropdownHours($name,$value,$limit_planning=0) {
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
 * Dropdown licenses for a software
 *
* @param $myname select name
 * @param $softwares_id software ID
 */
function dropdownLicenseOfSoftware($myname,$softwares_id) {
   global $DB,$LANG;

// TODO : this function is probably no more used (no more glpi_licenses)

   $query="SELECT *
          FROM `glpi_licenses`
          WHERE `softwares_id`='$softwares_id'
          GROUP BY `version`, `serial`, `expire`, `oem`, `oem_computer`, `buy`";
   $result=$DB->query($query);
   if ($DB->numrows($result)) {
      echo "<select name='$myname'>";
      while ($data=$DB->fetch_array($result)) {
         echo "<option value='".$data["id"]."'>".$data["version"]." - ".$data["serial"];
         if ($data["expire"]!=NULL) {
            echo " - ".$LANG['software'][25]." ".$data["expire"];
         } else {
            echo " - ".$LANG['software'][26];
         }
         if ($data["buy"]) {
            echo " - ".$LANG['software'][35];
         } else {
            echo " - ".$LANG['software'][37];
         }
         if ($data["oem"]) {
            echo " - ".$LANG['software'][28];
         }
         echo "</option>";
      }
      echo "</select>";
   }
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
function dropdownInteger($myname,$value,$min=0,$max=100,$step=1,$toadd=array()) {

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
 * Dropdown available languages
 *
* @param $myname select name
 * @param $value default value
 */
function dropdownLanguages($myname,$value) {
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
 * Display entities of the loaded profile
 *
* @param $myname select name
 * @param $target target for entity change action
 */
function displayActiveEntities($target,$myname) {
   global $CFG_GLPI,$LANG;

   $rand=mt_rand();

   echo "<div class='center' ><span class='b'>".$LANG['entity'][10]." ( <img src=\"".
          $CFG_GLPI["root_doc"]."/pics/entity_all.png\" alt=''> ".$LANG['entity'][11].")</span><br>";
   echo "<a style='font-size:14px;' href='".$target."?active_entity=all' title=\"".
          $LANG['buttons'][40]."\">".str_replace(" ","&nbsp;",$LANG['buttons'][40])."</a></div>";

   echo "<div class='left' style='width:100%'>";

   echo "<script type='javascript'>";
   echo "var Tree_Category_Loader$rand = new Ext.tree.TreeLoader({
      dataUrl:'".$CFG_GLPI["root_doc"]."/ajax/entitytreesons.php'
   });";

   echo "var Tree_Category$rand = new Ext.tree.TreePanel({
      collapsible      : false,
      animCollapse     : false,
      border           : false,
      id               : 'tree_projectcategory$rand',
      el               : 'tree_projectcategory$rand',
      autoScroll       : true,
      animate          : false,
      enableDD         : true,
      containerScroll  : true,
      height           : 320,
      width            : 770,
      loader           : Tree_Category_Loader$rand,
      rootVisible     : false
   });";

   // SET the root node.
   echo "var Tree_Category_Root$rand = new Ext.tree.AsyncTreeNode({
      text     : '',
      draggable   : false,
      id    : '-1'                  // this IS the id of the startnode
   });
   Tree_Category$rand.setRootNode(Tree_Category_Root$rand);";

   // Render the tree.
   echo "Tree_Category$rand.render();
         Tree_Category_Root$rand.expand();";

   echo "</script>";

   echo "<div id='tree_projectcategory$rand' ></div>";
   echo "</div>";
}

/**
 * get the Ticket status list
 *
 * @param $withmetaforsearch boolean
 * @return an array
 */
function getAllStatus($withmetaforsearch=false) {
   global $LANG;

   $tab = array(
      'new'          => $LANG['joblist'][9],
      'assign'       => $LANG['joblist'][18],
      'plan'         => $LANG['joblist'][19],
      'waiting'      => $LANG['joblist'][26],
      'old_done'     => $LANG['joblist'][10],
      'old_notdone'  => $LANG['joblist'][17]);

   if ($withmetaforsearch) {
      $tab['notold']  = $LANG['joblist'][24];
      $tab['process'] = $LANG['joblist'][21];
      $tab['old']     = $LANG['joblist'][25];
      $tab['all']     = $LANG['common'][66];
   }
   return $tab;
}

/**
 * get the Ticket status allowed for a current status
 *
 * @param $current status
 * @return an array
 */
function getAllowedStatus($current) {
   global $LANG;

   $tab = getAllStatus();
   if (!isset($current)) {
      $current = 'new';
   }
   foreach ($tab as $status => $label) {
      if ($status != $current
          && isset($_SESSION['glpiactiveprofile']['helpdesk_status'][$current][$status])
          && !$_SESSION['glpiactiveprofile']['helpdesk_status'][$current][$status]) {
         unset($tab[$status]);
      }
   }
   return $tab;
}


/**
 * Dropdown of ticket status
 *
 * @param $name select name
 * @param $value default value
 * @param $option list proposed 0:normal, 1:search, 2:allowed
 *
 * @return nothing (display)
 */
function dropdownStatus($name, $value='new', $option=0) {

   if ($option == 2) {
      $tab = getAllowedStatus($value);
   } else if ($option == 1) {
      $tab = getAllStatus(true);
   } else {
      $tab = getAllStatus(false);
   }
   echo "<select name='$name'>";
   foreach ($tab as $key => $val) {
      echo "<option value='$key' ".($value==$key?" selected ":"").">$val</option>";
   }
   echo "</select>";
}

/**
 * Get ticket status Name
 *
 * @param $value status ID
 */
function getStatusName($value) {
   global $LANG;

   $tab = getAllStatus();
   return (isset($tab[$value]) ? $tab[$value] : '');
}

/**
 * Get ticket request type name  ***** TODO : to be removed
 *
 * @param $value status ID
 */
function getRequestTypeName($value) {
   getDropdownName('glpi_requesttypes', $value);
}

/**
 * Dropdown of ticket request type ***** TODO : to be removed
 *
 *
 * @param $name select name
 * @param $value default value
 */
function dropdownRequestType($name,$value=0) {
   dropdownValue('glpi_requesttypes',$name,$value,1);
}

/**
 * Dropdown of amortissement type for infocoms
 *
 * @param $name select name
 * @param $value default value
 */
function dropdownAmortType($name,$value=0) {
   global $LANG;

   echo "<select name='$name'>";
   echo "<option value='0' ".($value==0?" selected ":"").">-------------</option>";
   echo "<option value='2' ".($value==2?" selected ":"").">".$LANG['financial'][47]."</option>";
   echo "<option value='1' ".($value==1?" selected ":"").">".$LANG['financial'][48]."</option>";
   echo "</select>";
}
/**
 * Get amortissement type name for infocoms
 *
 * @param $value status ID
 */
function getAmortTypeName($value) {
   global $LANG;

   switch ($value) {
      case 2 :
         return $LANG['financial'][47];
         break;

      case 1 :
         return $LANG['financial'][48];
         break;

      case 0 :
         return "";
         break;
   }
}

/**
 * Get planninf state name
 *
 * @param $value status ID
 */
function getPlanningState($value) {
   global $LANG;

   switch ($value) {
      case 0 :
         return $LANG['planning'][16];
         break;

      case 1 :
         return $LANG['planning'][17];
         break;

      case 2 :
         return $LANG['planning'][18];
         break;
   }
}

/**
 * Dropdown of planning state
 *
 * @param $name select name
 * @param $value default value
 */
function dropdownPlanningState($name,$value='') {
   global $LANG;

   echo "<select name='$name' id='$name'>";
   echo "<option value='0'".($value==0?" selected ":"").">".$LANG['planning'][16]."</option>";
   echo "<option value='1'".($value==1?" selected ":"").">".$LANG['planning'][17]."</option>";
   echo "<option value='2'".($value==2?" selected ":"").">".$LANG['planning'][18]."</option>";
   echo "</select>";
}

/**
 * Dropdown of values in an array
 *
 * @param $name select name
 * @param $elements array of elements to display
 * @param $value default value
 * @param $used Already used items ID: not to display in dropdown
 *
 */
function dropdownArrayValues($name,$elements,$value='',$used=array()) {

   $rand=mt_rand();
   echo "<select name='$name' id='dropdown_".$name.$rand."'>";

   foreach ($elements as $key => $val) {
      if (!isset($used[$key])) {
         echo "<option value='".$key."'".($value==$key?" selected ":"").">".$val."</option>";
      }
   }

   echo "</select>";
   return $rand;
}

/**
 * Dropdown for frequency (interval between 2 actions)
 *
 * @param $name select name
 * @param $value default value
 */
function dropdownFrequency($name,$value=0) {
   global $LANG;

   $tab = array();

   $tab[MINUTE_TIMESTAMP] = '1 ' .$LANG['job'][22];

   // Minutes
   for ($i=5 ; $i<60 ; $i+=5) {
      $tab[$i*MINUTE_TIMESTAMP] = $i . ' ' .$LANG['job'][22];
   }

   // Heures
   for ($i=1 ; $i<24 ; $i++) {
      $tab[$i*HOUR_TIMESTAMP] = $i . ' ' .$LANG['job'][21];
   }

   // Jours
   $tab[DAY_TIMESTAMP] = $LANG['setup'][305];
   for ($i=2 ; $i<7 ; $i++) {
      $tab[$i*DAY_TIMESTAMP] = $i . ' ' .$LANG['stats'][31];
   }

   $tab[WEEK_TIMESTAMP] = $LANG['setup'][308];
   $tab[MONTH_TIMESTAMP] = $LANG['setup'][309];

   dropdownArrayValues($name, $tab, $value);
}
/**
 * Remplace an dropdown by an hidden input field
 * and display the value.
 *
 * @param $name select name
 * @param $elements array of elements to display
 * @param $value default value
 * @param $used already used elements key (not used in this RO mode)
 *
 */
function dropdownArrayValuesReadonly($name,$elements,$value='',$used=array()) {

   echo "<input type='hidden' name='$name' value='$value'>";

   if (isset($elements[$value])) {
      echo $elements[$value];
   }
}

/**
 * Dropdown of states for behaviour config
 *
 * @param $name select name
 * @param $lib string to add for -1 value
 * @param $value default value
 */
function dropdownStateBehaviour ($name, $lib="", $value=0) {
   global $DB, $LANG;

   $elements=array("0"=>$LANG['setup'][195]);
   if ($lib) {
      $elements["-1"]=$lib;
   }

   $queryStateList = "SELECT `id`,`name`
                      FROM `glpi_states`
                      ORDER BY `name`";
   $result = $DB->query($queryStateList);
   if ($DB->numrows($result) > 0) {
      while (($data = $DB->fetch_assoc($result))) {
         $elements[$data["id"]] = $LANG['setup'][198] . ": " . $data["name"];
      }
   }
   dropdownArrayValues($name, $elements, $value);
}

/**
 * Dropdown for global management config
 *
 * @param $name select name
 * @param $value default value
 * @param $software is it for software ?
 */
function adminManagementDropdown($name,$value,$software=0) {
   global $LANG;

   echo "<select name=\"".$name."\">";

   if (!$software) {
      $yesUnit = $LANG['peripherals'][32];
      $yesGlobal = $LANG['peripherals'][31];
   } else {
      $yesUnit = $LANG['ocsconfig'][46];
      $yesGlobal = $LANG['ocsconfig'][45];
   }

   echo "<option value='2'";
   if ($value == 2) {
      echo " selected";
   }
   echo ">".$LANG['choice'][0]."</option>";

   echo "<option value='0'";
   if ($value == 0) {
   echo " selected";
   }
   echo ">" . $LANG['choice'][1]." - ". $LANG['setup'][274]. " : ".  $yesUnit . "</option>";

   echo "<option value='1'";
   if ($value == 1) {
   echo " selected";
   }
   echo ">" . $LANG['choice'][1]." - ". $LANG['setup'][274]. " : ". $yesGlobal . " </option>";

   echo "</select>";
}

/**
 * Dropdown for GMT selection
 *
 * @param $name select name
 * @param $value default value
 */
function dropdownGMT($name,$value='') {
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
 * Dropdown rules for a defined sub_type of rule
 *
 * @param $myname select name
 * @param $sub_type rule type
 */
function dropdownRules ($sub_type, $myname) {
   global $DB, $CFG_GLPI, $LANG;

   $rand=mt_rand();
   $limit_length=$_SESSION["glpidropdown_chars_limit"];

   $use_ajax=false;
   if ($CFG_GLPI["use_ajax"]) {
      $nb=countElementsInTable("glpi_rules", "sub_type=".$sub_type);

      if ($nb>$CFG_GLPI["ajax_limit_count"]) {
         $use_ajax=true;
      }
   }
   $params=array('searchText'=>'__VALUE__',
                 'myname'=>$myname,
                 'limit'=>$limit_length,
                 'rand'=>$rand,
                 'type'=>$sub_type);
   $default ="<select name='$myname' id='dropdown_".$myname.$rand."'>";
   $default.="<option value='0'>------</option></select>";
   ajaxDropdown($use_ajax,"/ajax/dropdownRules.php",$params,$default,$rand);

   return $rand;
}

/**
 * Dropdown profiles which have rights under the active one
 *
 * @param $name select name
 * @param $value default value
 */
function dropdownUnderProfiles($name,$value='') {
   global $DB;

   $profiles[0]="-----";

   $prof=new Profile();

   $query="SELECT *
           FROM `glpi_profiles` ".
           $prof->getUnderProfileRetrictRequest("WHERE")."
           ORDER BY `name`";

   $res = $DB->query($query);

   //New rule -> get the next free ranking
   if ($DB->numrows($res)) {
      while ($data = $DB->fetch_array($res)) {
         $profiles[$data['id']]=$data['name'];
      }
   }

   dropdownArrayValues($name,$profiles,$value);
}

/**
 * Dropdown for infocoms alert config
 *
 * @param $name select name
 * @param $value default value
 */
function dropdownAlertInfocoms($name,$value=0) {
   global $LANG;

   echo "<select name=\"$name\">";
   echo "<option value='0'".($value==0?" selected ":"")." >-----</option>";
   echo "<option value=\"".pow(2,ALERT_END)."\" ".($value==pow(2,ALERT_END)?" selected ":"")." >".
          $LANG['financial'][80]." </option>";
   echo "</select>";
}

/**
 * Private / Public switch for items which may be assign to a user and/or an entity
 *
 * @param $is_private default is private ?
 * @param $entity working entity ID
 * @param $is_recursive is the item recursive ?
 */
function privatePublicSwitch($is_private,$entity,$is_recursive) {
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
 * Print a select with contract renewal
 *
 * Print a select named $name with contract renewal options and selected value $value
 *
 *@param $name string : HTML select name
 *@param $value integer : HTML select selected value
 *
 *@return Nothing (display)
 *
 **/
function dropdownContractRenewal($name,$value=0) {
   global $LANG;

   echo "<select name='$name'>";
   echo "<option value='0' ".($value==0?" selected ":"").">-------------</option>";
   echo "<option value='1' ".($value==1?" selected ":"").">".$LANG['financial'][105]."</option>";
   echo "<option value='2' ".($value==2?" selected ":"").">".$LANG['financial'][106]."</option>";
   echo "</select>";
}

/**
 * Get the renewal type name
 *
 *@param $value integer : HTML select selected value
 *
 *@return string
 *
 **/
function getContractRenewalName($value) {
   global $LANG;

   switch ($value) {
      case 1:
         return $LANG['financial'][105];
         break;

      case 2:
         return $LANG['financial'][106];
         break;

      default :
      return "";
   }
}

/**
 * Get renewal ID by name
 * @param $value the name of the renewal
 *
 * @return the ID of the renewal
 */
function getContractRenewalIDByName($value) {
   global $LANG;

   if (preg_match("/^$value\$/i",$LANG['financial'][105])) {
      return 1;
   } else if (preg_match("/^$value\$/i",$LANG['financial'][106])){
      return 2;
   }
   return 0;
}

/**
 * Print all the authentication methods
 * @param name the dropdown name
 *
 *@return Nothing (display)
 */
function dropdownAuthMethods($name) {
   global $LANG,$DB;

   $methods[0]='-----';
   $methods[AUTH_DB_GLPI]=$LANG['login'][32];

   $sql = "SELECT count(*) AS cpt
           FROM `glpi_authldaps`";
   $result = $DB->query($sql);

   if ($DB->result($result,0,"cpt") > 0) {
      $methods[AUTH_LDAP]=$LANG['login'][31];
      $methods[AUTH_EXTERNAL]=$LANG['setup'][67];
   }

   $sql = "SELECT count(*) AS cpt
           FROM `glpi_authmails`";
   $result = $DB->query($sql);

   if ($DB->result($result,0,"cpt") > 0) {
      $methods[AUTH_MAIL]=$LANG['login'][33];
   }

   return dropdownArrayValues($name,$methods);
}

/**
 * Print a select with contracts
 *
 * Print a select named $name with contracts options and selected value $value
 *
 * @param $name string : HTML select name
 * @param $entity_restrict Restrict to a defined entity
 * @param $alreadyused : already used contract, do not add to dropdown
 * @param $nochecklimit : true to disable limit for nomber of device (for supplier)
 *
 *@return Nothing (display)
 *
 **/
function dropdownContracts($name,$entity_restrict=-1,$alreadyused=array(),$nochecklimit=false) {
   global $DB;

   $entrest="";
   $idrest="";
   if ($entity_restrict>=0) {
      $entrest=getEntitiesRestrictRequest("AND","glpi_contracts","entities_id",$entity_restrict,true);
   }
   if (count($alreadyused)) {
      foreach ($alreadyused AS $ID) {
         $idrest .= (empty($idrest) ? "AND `glpi_contracts`.`id` NOT IN(" : ",") . "'".$ID."'";
      }
      $idrest .= ")";
   }
   $query = "SELECT `glpi_contracts`.*, `glpi_entities`.`completename`
             FROM `glpi_contracts`
             LEFT JOIN `glpi_entities` ON (`glpi_contracts`.`entities_id` = `glpi_entities`.`id`)
             WHERE `glpi_contracts`.`is_deleted` = '0' $entrest $idrest
             ORDER BY `glpi_entities`.`completename`, `glpi_contracts`.`name` ASC,
                      `glpi_contracts`.`begin_date` DESC";
   $result=$DB->query($query);
   echo "<select name='$name'>";
   echo "<option value='-1'>-----</option>";
   $prev=-1;
   while ($data=$DB->fetch_array($result)) {
      if ($nochecklimit || $data["max_links_allowed"]==0
          || $data["max_links_allowed"]>countElementsInTable("glpi_contracts_items",
                                                            "contracts_id = '".$data['id']."'" )) {
         if ($data["entities_id"]!=$prev) {
            if ($prev>=0) {
               echo "</optgroup>";
            }
            $prev=$data["entities_id"];
            echo "<optgroup label=\"". $data["completename"] ."\">";
         }
         echo "<option value='".$data["id"]."'>";
         echo utf8_substr($data["name"]." - #".$data["num"]." - ".
                          convDateTime($data["begin_date"]),0,$_SESSION["glpidropdown_chars_limit"]);
         echo "</option>";
      }
   }
   if ($prev>=0) {
      echo "</optgroup>";
   }
   echo "</select>";
}


/**
 * Get the dropdown list name the user is allowed to edit
 *
 * @return array (group of dropdown) of array (table => localized name)
 */
 function getAllDropdowns() {
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

               $LANG['title'][24] => array ('TicketCategory'   => $LANG['setup'][79],
                                            'TaskCategory'     => $LANG['setup'][98],
                                            'RequestType'      => $LANG['job'][44]),

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

?>
