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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/** \file search.function.php
 * Generic functions for Search Engine
 */
// Get search_option array / Already include in includes.php
if (!isset($SEARCH_OPTION)) {
   $SEARCH_OPTION=getSearchOptions();
}

/**
 * Clean search options depending of user active profile
 *
 * @param $itemtype item type to manage
 * @param $action action which is used to manupulate searchoption (r/w)
 * @return clean $SEARCH_OPTION array
 */
function cleanSearchOption($itemtype,$action='r') {
   global $CFG_GLPI,$SEARCH_OPTION;

   $options=$SEARCH_OPTION[$itemtype];
   $todel=array();
   if (!haveRight('infocom',$action) && in_array($itemtype,$CFG_GLPI["infocom_types"])) {
      $todel=array_merge($todel,array('financial',
                                      25,26,27,28,37,38,50,51,52,53,54,55,56,57,58,59,120,122));
   }

   if (!haveRight('contract',$action) && in_array($itemtype,$CFG_GLPI["infocom_types"])) {
      $todel=array_merge($todel,array('financial',
                                      29,30,130,131,132,133,134,135,136,137,138));
   }

   if ($itemtype==COMPUTER_TYPE) {
      if (!haveRight('networking',$action)) {
         $todel=array_merge($todel,array('network',
                                         20,21,22,83,84,85));
      }
      if (!$CFG_GLPI['use_ocs_mode'] || !haveRight('view_ocsng',$action)) {
         $todel=array_merge($todel,array('ocsng',
                                         100,101,102,103));
      }
   }
   if (!haveRight('notes',$action)) {
      $todel[]=90;
   }

   if (count($todel)) {
      foreach ($todel as $ID) {
         if (isset($options[$ID])) {
            unset($options[$ID]);
         }
      }
   }

   return $options;
}


/**
 * Completion of the URL $_GET values with the $_SESSION values or define default values
 *
 * @param $itemtype item type to manage
 * @param $usesession Use datas save in session
 * @param $save Save params to session
 * @return nothing
 */
function manageGetValuesInSearch($itemtype=0,$usesession=true,$save=true) {
   global $_GET,$DB;

   $tab=array();

   $default_values["start"]=0;
   $default_values["order"]="ASC";
   $default_values["is_deleted"]=0;
   $default_values["distinct"]="N";
   $default_values["link"]=array();
   $default_values["field"]=array(0=>"view");
   $default_values["contains"]=array(0=>"");
   $default_values["link2"]=array();
   $default_values["field2"]=array(0=>"view");
   $default_values["contains2"]=array(0=>"");
   $default_values["itemtype2"]="";
   $default_values["sort"]=1;

   // First view of the page : try to load a bookmark
   if ($usesession && !isset($_SESSION['glpisearch'][$itemtype])) {
      $query = "SELECT `bookmarks_id`
                FROM `glpi_bookmarks_users`
                WHERE `users_id`='".$_SESSION['glpiID']."'
                      AND `itemtype` = '$itemtype'";
      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)>0) {
            $IDtoload=$DB->result($result,0,0);
            // Set session variable
            $_SESSION['glpisearch'][$itemtype]=array();
            // Load bookmark on main window
            $bookmark=new Bookmark();
            $bookmark->load($IDtoload,false);
         }
      }
   }
   if ($usesession
       && (isset($_GET["reset_before"]) || (isset($_GET["reset"]) && $_GET["reset"]="reset_before"))) {

      if (isset($_SESSION['glpisearch'][$itemtype])) {
         unset($_SESSION['glpisearch'][$itemtype]);
      }
      if (isset($_SESSION['glpisearchcount'][$itemtype])) {
         unset($_SESSION['glpisearchcount'][$itemtype]);
      }
      if (isset($_SESSION['glpisearchcount2'][$itemtype])) {
         unset($_SESSION['glpisearchcount2'][$itemtype]);
      }
      // Bookmark use
      if (isset($_GET["glpisearchcount"])) {
         $_SESSION["glpisearchcount"][$itemtype]=$_GET["glpisearchcount"];
      }
      // Bookmark use
      if (isset($_GET["glpisearchcount2"])) {
         $_SESSION["glpisearchcount2"][$itemtype]=$_GET["glpisearchcount2"];
      }
   }

   if (is_array($_GET) && $save) {
      foreach ($_GET as $key => $val) {
         $_SESSION['glpisearch'][$itemtype][$key]=$val;
      }
   }

   foreach ($default_values as $key => $val) {
      if (!isset($_GET[$key])) {
         if ($usesession && isset($_SESSION['glpisearch'][$itemtype][$key])) {
            $_GET[$key]=$_SESSION['glpisearch'][$itemtype][$key];
         } else {
            $_GET[$key] = $val;
            $_SESSION['glpisearch'][$itemtype][$key] = $val;
         }
      }
   }

   if (!isset($_SESSION["glpisearchcount"][$itemtype])) {
      if (isset($_GET["glpisearchcount"])) {
         $_SESSION["glpisearchcount"][$itemtype]=$_GET["glpisearchcount"];
      } else {
         $_SESSION["glpisearchcount"][$itemtype]=1;
      }
   }
   if (!isset($_SESSION["glpisearchcount2"][$itemtype])) {
      // Set in URL for bookmark
      if (isset($_GET["glpisearchcount2"])) {
         $_SESSION["glpisearchcount2"][$itemtype]=$_GET["glpisearchcount2"];
      } else {
         $_SESSION["glpisearchcount2"][$itemtype]=0;
      }
   }
}


/**
 * Print generic search form
 *
 *@param $itemtype type to display the form
 *@param $params parameters array may include field, contains, sort, is_deleted, link, link2, contains2, field2, type2
 *
 *@return nothing (diplays)
 *
 **/
function searchForm($itemtype,$params) {
   global $LANG,$SEARCH_OPTION,$CFG_GLPI,$LINK_ID_TABLE,$INFOFORM_PAGES,$SEARCH_PAGES;

   // Default values of parameters
   $default_values["link"]="";
   $default_values["field"]="";
   $default_values["contains"]="";
   $default_values["sort"]="";
   $default_values["is_deleted"]=0;
   $default_values["link2"]="";
   $default_values["contains2"]="";
   $default_values["field2"]="";
   $default_values["itemtype2"]="";

   if (isset($SEARCH_PAGES[$itemtype])) {
      $default_values["target"] = $CFG_GLPI['root_doc'].'/'.$SEARCH_PAGES[$itemtype];
   } else if (isset($INFOFORM_PAGES[$itemtype])) {
      $default_values["target"] = $CFG_GLPI['root_doc'].'/'.
                                  str_replace('.form','',$INFOFORM_PAGES[$itemtype]);
   } else {
      $default_values["target"] = $_SERVER['PHP_SELF'];
   }

   foreach ($default_values as $key => $val) {
      if (isset($params[$key])) {
         $$key=$params[$key];
      } else {
         $$key=$default_values[$key];
      }
   }

   $options=cleanSearchOption($itemtype);

   // Meta search names
   $names=array(COMPUTER_TYPE   => $LANG['Menu'][0],
                PRINTER_TYPE    => $LANG['Menu'][2],
                MONITOR_TYPE    => $LANG['Menu'][3],
                PERIPHERAL_TYPE => $LANG['Menu'][16],
                SOFTWARE_TYPE   => $LANG['Menu'][4],
                PHONE_TYPE      => $LANG['Menu'][34]);

   echo "<form method='get' action=\"$target\">";
   echo "<table class='tab_cadre_fixe'>";
   echo "<tr class='tab_bg_1'>";
   echo "<td>";
   echo "<table>";

   // Display normal search parameters
   for ($i=0 ; $i<$_SESSION["glpisearchcount"][$itemtype] ; $i++) {
      echo "<tr><td class='right'>";
      // First line display add / delete images for normal and meta search items
      if ($i==0) {
         echo "<a href='".$CFG_GLPI["root_doc"]."/front/computer.php?add_search_count=".
               "1&amp;itemtype=$itemtype'>";
         echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/plus.png\" alt='+' title='".
               $LANG['search'][17]."'></a>&nbsp;&nbsp;&nbsp;&nbsp;";
         if ($_SESSION["glpisearchcount"][$itemtype]>1) {
            echo "<a href='".$CFG_GLPI["root_doc"]."/front/computer.php?delete_search_count=".
                  "1&amp;itemtype=$itemtype'>";
            echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/moins.png\" alt='-' title='".
                  $LANG['search'][18]."'></a>&nbsp;&nbsp;&nbsp;&nbsp;";
         }
         if (isset($names[$itemtype])) {
            echo "<a href='".$CFG_GLPI["root_doc"]."/front/computer.php?add_search_count2=".
                  "1&amp;itemtype=$itemtype'>";
            echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/meta_plus.png\" alt='+' title='".
                  $LANG['search'][19]."'></a>&nbsp;&nbsp;&nbsp;&nbsp;";
            if ($_SESSION["glpisearchcount2"][$itemtype]>0) {
               echo "<a href='".$CFG_GLPI["root_doc"]."/front/computer.php?delete_search_count2=".
                     "1&amp;itemtype=$itemtype'>";
               echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/meta_moins.png\" alt='-' title='".
                     $LANG['search'][20]."'></a>&nbsp;&nbsp;&nbsp;&nbsp;";
            }
         }
      }
      // Display link item
      if ($i>0) {
         echo "<select name='link[$i]'>";
         echo "<option value='AND' ";
         if (is_array($link) && isset($link[$i]) && $link[$i] == "AND") {
            echo "selected";
         }
         echo ">AND</option>\n";

         echo "<option value='OR' ";
         if (is_array($link) && isset($link[$i]) && $link[$i] == "OR") {
            echo "selected";
         }
         echo ">OR</option>\n";

         echo "<option value='AND NOT' ";
         if (is_array($link) && isset($link[$i]) && $link[$i] == "AND NOT") {
            echo "selected";
         }
         echo ">AND NOT</option>\n";

         echo "<option value='OR NOT' ";
         if (is_array($link) && isset($link[$i]) && $link[$i] == "OR NOT") {
            echo "selected";
         }
         echo ">OR NOT</option>";
         echo "</select>&nbsp;";
      }

      // display search field
      echo "<input type='text' size='15' name=\"contains[$i]\" value=\"".
            (is_array($contains) && isset($contains[$i])?stripslashes($contains[$i]):"" )."\" >";
      echo "&nbsp;".$LANG['search'][10]."&nbsp;";

      // display select box to define serach item
      echo "<select name=\"field[$i]\" size='1'>";
      echo "<option value='view' ";
      if (is_array($field) && isset($field[$i]) && $field[$i] == "view") {
         echo "selected";
      }
      echo ">".$LANG['search'][11]."</option>\n";

      reset($options);
      $first_group=true;
      foreach ($options as $key => $val) {
         // print groups
         if (!is_array($val)) {
            if (!$first_group) {
               echo "</optgroup>\n";
            } else {
               $first_group=false;
            }
            echo "<optgroup label='$val'>";
         } else {
            echo "<option value='$key'";
            if (is_array($field) && isset($field[$i]) && $key == $field[$i]) {
               echo "selected";
            }
            echo ">". utf8_substr($val["name"],0,32) ."</option>\n";
         }
      }
      if (!$first_group) {
         echo "</optgroup>\n";
      }
      echo "<option value='all' ";
      if (is_array($field) && isset($field[$i]) && $field[$i] == "all") {
         echo "selected";
      }
      echo ">".$LANG['common'][66]."</option>";
      echo "</select>&nbsp;\n";

      echo "</td></tr>\n";
   }

   // Display meta search items
   $linked=array();
   if ($_SESSION["glpisearchcount2"][$itemtype]>0) {
      // Define meta search items to linked
      switch ($itemtype) {
         case COMPUTER_TYPE :
            $linked=array(PRINTER_TYPE,
                          MONITOR_TYPE,
                          PERIPHERAL_TYPE,
                          SOFTWARE_TYPE,
                          PHONE_TYPE);
            break;

         case PRINTER_TYPE :
         case MONITOR_TYPE :
         case PERIPHERAL_TYPE :
         case SOFTWARE_TYPE :
         case PHONE_TYPE :
            $linked=array(COMPUTER_TYPE);
            break;
      }
   }

   if (is_array($linked) && count($linked)>0) {
      for ($i=0 ; $i<$_SESSION["glpisearchcount2"][$itemtype] ; $i++) {
         echo "<tr><td class='left'>";
         $rand=mt_rand();

         // Display link item (not for the first item)
         echo "<select name='link2[$i]'>";
         echo "<option value='AND' ";
         if (is_array($link2) && isset($link2[$i]) && $link2[$i] == "AND") {
            echo "selected";
         }
         echo ">AND</option>\n";

         echo "<option value='OR' ";
         if (is_array($link2) && isset($link2[$i]) && $link2[$i] == "OR") {
            echo "selected";
         }
         echo ">OR</option>\n";

         echo "<option value='AND NOT' ";
         if (is_array($link2) && isset($link2[$i]) && $link2[$i] == "AND NOT") {
            echo "selected";
         }
         echo ">AND NOT</option>\n";

         echo "<option value='OR NOT' ";
         if (is_array($link2 )&& isset($link2[$i]) && $link2[$i] == "OR NOT") {
            echo "selected";
         }
         echo ">OR NOT</option>\n";
         echo "</select>";

         // Display select of the linked item type available
         echo "<select name='itemtype2[$i]' id='itemtype2_".$itemtype."_".$i."_$rand'>";
         echo "<option value='-1'>------</option>";
         foreach ($linked as $key) {
            echo "<option value='$key'>".utf8_substr($names[$key],0,20)."</option>\n";
         }
         echo "</select>";

         // Ajax script for display search meat item
         echo "<span id='show_".$itemtype."_".$i."_$rand'>&nbsp;</span>\n";

         $params=array('itemtype'=>'__VALUE__',
                       'num'=>$i,
                       'field'=>(is_array($field2) && isset($field2[$i])?$field2[$i]:""),
                       'val'=>(is_array($contains2) && isset($contains2[$i])?$contains2[$i]:""));

         ajaxUpdateItemOnSelectEvent("itemtype2_".$itemtype."_".$i."_$rand","show_".$itemtype."_".
                  $i."_$rand",$CFG_GLPI["root_doc"]."/ajax/updateMetaSearch.php",$params,false);

         if (is_array($itemtype2) && isset($itemtype2[$i]) && $itemtype2[$i]>0) {
            $params['itemtype']=$itemtype2[$i];
            ajaxUpdateItem("show_".$itemtype."_".$i."_$rand",
                           $CFG_GLPI["root_doc"]."/ajax/updateMetaSearch.php",$params,false);
            echo "<script type='text/javascript' >";
            echo "window.document.getElementById('itemtype2_".$itemtype."_".$i."_$rand').value='".
                                                 $itemtype2[$i]."';";
            echo "</script>\n";
         }
         echo "</td></tr>\n";
      }
   }
   echo "</table>\n";
   echo "</td>\n";

   // Display sort selection
   echo "<td>".$LANG['search'][4];
   echo "&nbsp;<select name='sort' size='1'>";
   reset($options);
   $first_group=true;
   foreach ($options as $key => $val) {
      if (!is_array($val)) {
         if (!$first_group) {
            echo "</optgroup>\n";
         } else {
            $first_group=false;
         }
         echo "<optgroup label='$val'>";
      } else {
         echo "<option value='$key'";
         if ($key == $sort) {
            echo " selected";
         }
         echo ">".utf8_substr($val["name"],0,20)."</option>\n";
      }
   }
   if (!$first_group) {
      echo "</optgroup>\n";
   }
   echo "</select> ";
   echo "</td>\n";

   // Display deleted selection
   echo "<td>";
   if (in_array($LINK_ID_TABLE[$itemtype],$CFG_GLPI["deleted_tables"])) {
      dropdownYesNo("is_deleted",$is_deleted);
      echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/showdeleted.png\" alt='".$LANG['common'][3].
             "' title='".$LANG['common'][3]."'>";
   }
   echo "</td>\n";

   // Display Reset search
   echo "<td class='center'>";
   echo "<a href='".$CFG_GLPI["root_doc"]."/front/computer.php?reset_search=".
         "reset_search&amp;itemtype=$itemtype' >";
   echo "<img title=\"".$LANG['buttons'][16]."\" alt=\"".$LANG['buttons'][16]."\" src='".
          $CFG_GLPI["root_doc"]."/pics/reset.png' class='calendrier'></a>";
   showSaveBookmarkButton(BOOKMARK_SEARCH,$itemtype);
   echo "</td>\n";

   // Display submit button
   echo "<td width='80' class='tab_bg_2'>";
   echo "<input type='submit' value=\"".$LANG['buttons'][0]."\" class='submit' >";
   echo "</td></tr>";
   echo "</table>\n";

   // Reset to start when submit new search
   echo "<input type='hidden' name='start' value='0'>";
   echo "</form>";
}


/**
 * Generic Search and list function
 *
 * Build the query, make the search and list items after a search.
 *
 *@param $itemtype item type
 *@param $params parameters array may include field, contains, sort, order, start, deleted, link, link2, contains2, field2, itemtype2
 *
 *@return Nothing (display)
 *
 **/
function showList ($itemtype,$params) {
   global $DB,$CFG_GLPI,$INFOFORM_PAGES,$LANG,$LINK_ID_TABLE,$SEARCH_OPTION,$SEARCH_PAGES,
          $PLUGIN_HOOKS;

   // Default values of parameters
   $default_values["link"]=array();
   $default_values["field"]=array();
   $default_values["contains"]=array();
   $default_values["sort"]="1";
   $default_values["order"]="ASC";
   $default_values["start"]=0;
   $default_values["is_deleted"]=0;
   $default_values["export_all"]=0;
   $default_values["link2"]="";
   $default_values["contains2"]="";
   $default_values["field2"]="";
   $default_values["itemtype2"]="";

   if (isset($SEARCH_PAGES[$itemtype])) {
      $default_values["target"] = $CFG_GLPI['root_doc'].'/'.$SEARCH_PAGES[$itemtype];
   } else if (isset($INFOFORM_PAGES[$itemtype])) {
      $default_values["target"] = $CFG_GLPI['root_doc'].'/'.
                                  str_replace('.form','',$INFOFORM_PAGES[$itemtype]);
   } else {
      $default_values["target"] = $_SERVER['PHP_SELF'];
   }

   foreach ($default_values as $key => $val) {
      if (isset($params[$key])) {
         $$key=$params[$key];
      } else {
         $$key=$default_values[$key];
      }
   }

   if ($export_all) {
      $start=0;
   }

   $limitsearchopt=cleanSearchOption($itemtype);
   $itemtable=$LINK_ID_TABLE[$itemtype];

   if (isset($CFG_GLPI["union_search_type"][$itemtype])) {
      $itemtable=$CFG_GLPI["union_search_type"][$itemtype];
   }
   $LIST_LIMIT=$_SESSION['glpilist_limit'];

   // Set display type for export if define
   $output_type=HTML_OUTPUT;
   if (isset($_GET["display_type"])) {
      $output_type=$_GET["display_type"];
      // Limit to 10 element
      if ($_GET["display_type"]==GLOBAL_SEARCH) {
         $LIST_LIMIT=GLOBAL_SEARCH_DISPLAY_COUNT;
      }
   }

   $entity_restrict=($itemtype==ENTITY_TYPE || in_array($itemtable,$CFG_GLPI["specif_entities_tables"]));

   $names=array(COMPUTER_TYPE   => $LANG['Menu'][0],
                PRINTER_TYPE    => $LANG['Menu'][2],
                MONITOR_TYPE    => $LANG['Menu'][3],
                PERIPHERAL_TYPE => $LANG['Menu'][16],
                SOFTWARE_TYPE   => $LANG['Menu'][4],
                PHONE_TYPE      => $LANG['Menu'][34]);

   // Get the items to display
   $toview=addDefaultToView($itemtype);

   // Add default items
   $query = "SELECT *
             FROM `glpi_displayprefs`
             WHERE `itemtype` = '$itemtype'
                   AND `users_id` = '".$_SESSION["glpiID"]."'
             ORDER BY `rank`";
   $result=$DB->query($query);

   // GET default serach options
   if ($DB->numrows($result)==0) {
      $query = "SELECT *
                FROM `glpi_displayprefs`
                WHERE `itemtype` = '$itemtype'
                      AND `users_id` = '0'
                ORDER BY `rank`";
      $result=$DB->query($query);
   }

   if ($DB->numrows($result)>0) {
      while ($data=$DB->fetch_array($result)) {
         array_push($toview,$data["num"]);
      }
   }

   // Add searched items
   if (count($field)>0) {
      foreach($field as $key => $val) {
         if (!in_array($val,$toview) && $val!="all" && $val!="view") {
            array_push($toview,$val);
         }
      }
   }

   // Add order item
   if (!in_array($sort,$toview)) {
      array_push($toview,$sort);
   }

   /// TODO to delete see after OR use to_view / to_search arrays to manage left join
   // Manage search on all item
   $SEARCH_ALL=array();
   if (in_array("all",$field)) {
      foreach ($field as $key => $val) {
         if ($val=="all") {
            array_push($SEARCH_ALL,array("contains"=>$contains[$key]));
         }
      }
   }

   // Clean toview array
   $toview=array_unique($toview);
   foreach ($toview as $key => $val) {
      if (!isset($limitsearchopt[$val])) {
         unset($toview[$key]);
      }
   }
   $toview_count=count($toview);

   // Construct the request

   //// 1 - SELECT
   $SELECT = "SELECT ".addDefaultSelect($itemtype);

   // Add select for all toview item
   foreach ($toview as $key => $val) {
      $SELECT.= addSelect($itemtype,$val,$key,0);
   }


   //// 2 - FROM AND LEFT JOIN
   // Set reference table
   $FROM = " FROM `$itemtable`";

   // Init already linked tables array in order not to link a table several times
   $already_link_tables=array();
   // Put reference table
   array_push($already_link_tables,$itemtable);

   // Add default join
   $COMMONLEFTJOIN = addDefaultJoin($itemtype,$itemtable,$already_link_tables);
   $FROM .= $COMMONLEFTJOIN;

   // Add all table for toview items
   foreach ($toview as $key => $val) {
      $FROM .= addLeftJoin($itemtype,$itemtable,$already_link_tables,
                           $SEARCH_OPTION[$itemtype][$val]["table"],
                           $SEARCH_OPTION[$itemtype][$val]["linkfield"]);
   }

   /// TODO to delete : manage Left Join when need of search or display
   // Search all case :
   if (count($SEARCH_ALL)>0) {
      foreach ($SEARCH_OPTION[$itemtype] as $key => $val) {
         // Do not search on Group Name
         if (is_array($val)) {
            $FROM .= addLeftJoin($itemtype,$itemtable,$already_link_tables,
                                 $SEARCH_OPTION[$itemtype][$key]["table"],
                                 $SEARCH_OPTION[$itemtype][$key]["linkfield"]);
         }
      }
   }


   //// 3 - WHERE

   // default string
   $COMMONWHERE = addDefaultWhere($itemtype);
   $first=empty($COMMONWHERE);

   // Add deleted if item have it
   if (in_array($itemtable,$CFG_GLPI["deleted_tables"])) {
      $LINK= " AND " ;
      if ($first) {
         $LINK=" ";
         $first=false;
      }
      $COMMONWHERE .= $LINK."`$itemtable`.`is_deleted` = '$is_deleted' ";
   }

   // Remove template items
   if (in_array($itemtable,$CFG_GLPI["template_tables"])) {
      $LINK= " AND " ;
      if ($first) {
         $LINK=" ";
         $first=false;
      }
      $COMMONWHERE .= $LINK."`$itemtable`.`is_template` = '0' ";
   }

   // Add Restrict to current entities
   if ($entity_restrict) {
      $LINK= " AND " ;
      if ($first) {
         $LINK=" ";
         $first=false;
      }

      if ($itemtype==ENTITY_TYPE) {
         $COMMONWHERE .= getEntitiesRestrictRequest($LINK,$itemtable,'id','',true);
      } else if (isset($CFG_GLPI["union_search_type"][$itemtype])) {
         // Will be replace below in Union/Recursivity Hack
         $COMMONWHERE .= $LINK." ENTITYRESTRICT ";
      } else if (in_array($itemtable, $CFG_GLPI["recursive_type"])) {
         $COMMONWHERE .= getEntitiesRestrictRequest($LINK,$itemtable,'','',true);
      } else {
         $COMMONWHERE .= getEntitiesRestrictRequest($LINK,$itemtable);
      }
   }
   $WHERE="";
   $HAVING="";

   /// TODO do also having here / simplify view - all cases : duplicates
   // Add search conditions
   // If there is search items
   if ($_SESSION["glpisearchcount"][$itemtype]>0 && count($contains)>0) {
      for ($key=0 ; $key<$_SESSION["glpisearchcount"][$itemtype] ; $key++) {
         // if real search (strlen >0) and not all and view search
         if (isset($contains[$key]) && strlen($contains[$key])>0) {
            // common search
            if ($field[$key]!="all" && $field[$key]!="view") {
               $LINK=" ";
               $NOT=0;
               $tmplink="";
               if (is_array($link) && isset($link[$key])) {
                  if (strstr($link[$key],"NOT")) {
                     $tmplink=" ".str_replace(" NOT","",$link[$key]);
                     $NOT=1;
                  } else {
                     $tmplink=" ".$link[$key];
                  }
               } else {
                  $tmplink=" AND ";
               }

               if (isset($SEARCH_OPTION[$itemtype][$field[$key]]["usehaving"])) {
                  // Manage Link if not first item
                  if (!empty($HAVING)) {
                     $LINK=$tmplink;
                  }
                  // Find key
                  $item_num=array_search($field[$key],$toview);
                  $HAVING .= addHaving($LINK,$NOT,$itemtype,$field[$key],$contains[$key],0,$item_num);
               } else {
                  // Manage Link if not first item
                  if (!empty($WHERE)) {
                     $LINK=$tmplink;
                  }
                  $WHERE .= addWhere($LINK,$NOT,$itemtype,$field[$key],$contains[$key]);
               }
            // view search
            } else if ($field[$key]=="view") {
               $LINK=" OR ";
               $NOT=0;
               $globallink=" AND ";
               if (is_array($link) && isset($link[$key])) {
                  switch ($link[$key]) {
                     case "AND" :
                        $LINK=" OR ";
                        $globallink=" AND ";
                        break;

                     case "AND NOT" :
                        $LINK=" AND ";
                        $NOT=1;
                        $globallink=" AND ";
                        break;

                     case "OR" :
                        $LINK=" OR ";
                        $globallink=" OR ";
                        break;

                     case "OR NOT" :
                        $LINK=" AND ";
                        $NOT=1;
                        $globallink=" OR ";
                        break;
                  }
               } else {
                  $tmplink=" AND ";
               }

               // Manage Link if not first item
               if (!empty($WHERE)) {
                  $WHERE .= $globallink;
               }
               $WHERE.= " ( ";
               $first2=true;

               foreach ($toview as $key2 => $val2) {
                  // Add Where clause if not to be done in HAVING CLAUSE
                  if (!isset($SEARCH_OPTION[$itemtype][$val2]["usehaving"])) {
                     $tmplink=$LINK;
                     if ($first2) {
                        $tmplink=" ";
                        $first2=false;
                     }
                     $WHERE .= addWhere($tmplink,$NOT,$itemtype,$val2,$contains[$key]);
                  }
               }
               $WHERE.=" ) ";

            // all search
            } else if ($field[$key]=="all") {
               $LINK=" OR ";
               $NOT=0;
               $globallink=" AND ";
               if (is_array($link) && isset($link[$key])) {
                  switch ($link[$key]) {
                     case "AND" :
                        $LINK=" OR ";
                        $globallink=" AND ";
                        break;

                     case "AND NOT" :
                        $LINK=" AND ";
                        $NOT=1;
                        $globallink=" AND ";
                        break;

                     case "OR" :
                        $LINK=" OR ";
                        $globallink=" OR ";
                        break;

                     case "OR NOT" :
                        $LINK=" AND ";
                        $NOT=1;
                        $globallink=" OR ";
                        break;
                  }
               } else {
                  $tmplink=" AND ";
               }

               // Manage Link if not first item
               if (!empty($WHERE)) {
                  $WHERE.=$globallink;
               }

               $WHERE .= " ( ";
               $first2=true;

               foreach ($SEARCH_OPTION[$itemtype] as $key2 => $val2) {
                  if (is_array($val2)) {
                     // Add Where clause if not to be done ine HAVING CLAUSE
                     if (!isset($val2["usehaving"])) {
                        $tmplink=$LINK;
                        if ($first2) {
                           $tmplink=" ";
                           $first2=false;
                        }
                        $WHERE .= addWhere($tmplink,$NOT,$itemtype,$key2,$contains[$key]);
                     }
                  }
               }
               $WHERE .= ")";
            }
         }
      }
   }


   //// 4 - ORDER
   $ORDER=" ORDER BY `id` ";
   foreach($toview as $key => $val) {
      if ($sort==$val) {
         $ORDER= addOrderBy($itemtype,$sort,$order,$key);
      }
   }


   //// 5 - META SEARCH
   // Preprocessing
   if ($_SESSION["glpisearchcount2"][$itemtype]>0 && is_array($itemtype2)) {

      // a - SELECT
      for ($i=0 ; $i<$_SESSION["glpisearchcount2"][$itemtype] ; $i++) {
         if (isset($itemtype2[$i]) && $itemtype2[$i]>0 && isset($contains2[$i])
             && strlen($contains2[$i])>0) {

            $SELECT .= addSelect($itemtype2[$i],$field2[$i],$i,1,$itemtype2[$i]);
         }
      }

      // b - ADD LEFT JOIN
      // Already link meta table in order not to linked a table several times
      $already_link_tables2=array();
      // Link reference tables
      for ($i=0 ; $i<$_SESSION["glpisearchcount2"][$itemtype] ; $i++) {
         if (isset($itemtype2[$i]) && $itemtype2[$i]>0 && isset($contains2[$i])
             && strlen($contains2[$i])>0) {
            if (!in_array($LINK_ID_TABLE[$itemtype2[$i]],$already_link_tables2)) {
               $FROM .= addMetaLeftJoin($itemtype,$itemtype2[$i],$already_link_tables2,
                                        (($contains2[$i]=="NULL")||(strstr($link2[$i],"NOT"))));
            }
         }
      }
      // Link items tables
      for ($i=0 ; $i<$_SESSION["glpisearchcount2"][$itemtype] ; $i++) {
         if (isset($itemtype2[$i]) && $itemtype2[$i]>0 && isset($contains2[$i])
             && strlen($contains2[$i])>0) {
            if (!in_array($SEARCH_OPTION[$itemtype2[$i]][$field2[$i]]["table"]."_".$itemtype2[$i],
                          $already_link_tables2)) {

               $FROM .= addLeftJoin($itemtype2[$i],$LINK_ID_TABLE[$itemtype2[$i]],$already_link_tables2,
                                    $SEARCH_OPTION[$itemtype2[$i]][$field2[$i]]["table"],
                                    $SEARCH_OPTION[$itemtype2[$i]][$field2[$i]]["linkfield"],
                                    0,1,$itemtype2[$i]);
            }
         }
      }
   }


   //// 6 - Add item ID
   // Add ID to the select
   if (!empty($itemtable)) {
      $SELECT .= "`$itemtable`.`id` AS id ";
   }


   //// 7 - Manage GROUP BY
   $GROUPBY = "";
   // Meta Search / Search All / Count tickets
   if ($_SESSION["glpisearchcount2"][$itemtype]>0 || !empty($HAVING) || in_array('all',$field)) {
      $GROUPBY = " GROUP BY `$itemtable`.`id`";
   }

   if (empty($GROUPBY)) {
      foreach ($toview as $key2 => $val2) {
         if (!empty($GROUPBY)) {
            break;
         }
         if (isset($SEARCH_OPTION[$itemtype][$val2]["forcegroupby"])) {
            $GROUPBY = " GROUP BY `$itemtable`.`id`";
         }
      }
   }

   // Specific search for others item linked  (META search)
   if (is_array($itemtype2)) {
      for ($key=0 ; $key<$_SESSION["glpisearchcount2"][$itemtype] ; $key++) {
         if (isset($itemtype2[$key]) && $itemtype2[$key]>0 && isset($contains2[$key])
             && strlen($contains2[$key])>0) {
            $LINK="";

            // For AND NOT statement need to take into account all the group by items
            if (strstr($link2[$key],"AND NOT")
                || isset($SEARCH_OPTION[$itemtype2[$key]][$field2[$key]]["usehaving"])) {

               $NOT=0;
               if (strstr($link2[$key],"NOT")) {
                  $tmplink = " ".str_replace(" NOT","",$link2[$key]);
                  $NOT=1;
               } else {
                  $tmplink = " ".$link2[$key];
               }
               if (!empty($HAVING)) {
                  $LINK=$tmplink;
               }
               $HAVING .= addHaving($LINK,$NOT,$itemtype2[$key],$field2[$key],$contains2[$key],1,$key);
            } else { // Meta Where Search
               $LINK=" ";
               $NOT=0;
               // Manage Link if not first item
               if (is_array($link2) && isset($link2[$key]) && strstr($link2[$key],"NOT")) {
                  $tmplink = " ".str_replace(" NOT","",$link2[$key]);
                  $NOT=1;
               } else if (is_array($link2) && isset($link2[$key])) {
                  $tmplink = " ".$link2[$key];
               } else {
                  $tmplink = " AND ";
               }
               if (!empty($WHERE)) {
                  $LINK=$tmplink;
               }
               $WHERE .= addWhere($LINK,$NOT,$itemtype2[$key],$field2[$key],$contains2[$key],1);
            }
         }
      }
   }

   // If no research limit research to display item and compute number of item using simple request
   $nosearch=true;
   for ($i=0 ; $i<$_SESSION["glpisearchcount"][$itemtype] ; $i++) {
      if (isset($contains[$i]) && strlen($contains[$i])>0) {
         $nosearch=false;
      }
   }

   if ($_SESSION["glpisearchcount2"][$itemtype]>0) {
      $nosearch=false;
   }

   $LIMIT="";
   $numrows=0;
   //No search : count number of items using a simple count(ID) request and LIMIT search
   if ($nosearch) {
      $LIMIT= " LIMIT $start, ".$LIST_LIMIT;

      // Force group by for all the type -> need to count only on table ID
      if (!isset($SEARCH_OPTION[$itemtype][1]['forcegroupby'])) {
         $count = "count(*)";
      } else {
         $count = "count(DISTINCT `$itemtable`.`id`)";
      }
      $query_num = "SELECT $count
                    FROM `$itemtable`".
                    $COMMONLEFTJOIN;

      $first=true;

      if (!empty($COMMONWHERE)) {
         $LINK= " AND " ;
         if ($first) {
            $LINK = " WHERE ";
            $first=false;
         }
         $query_num .= $LINK.$COMMONWHERE;
      }
      // Union Search :
      if (isset($CFG_GLPI["union_search_type"][$itemtype])) {
         $tmpquery=$query_num;
         $numrows=0;
         foreach ($CFG_GLPI[$CFG_GLPI["union_search_type"][$itemtype]] as $ctype) {
            if (haveTypeRight($ctype,'r')) {
               // No ref table case
               if (empty($LINK_ID_TABLE[$itemtype])) {
                  $query_num=str_replace($CFG_GLPI["union_search_type"][$itemtype],
                                         $LINK_ID_TABLE[$ctype],$tmpquery);
                  // State case :
                  if ($itemtype==STATE_TYPE) {
                     $query_num .= " AND ".$LINK_ID_TABLE[$ctype].".`states_id` > '0' ";
                  }
               } else {// Ref table case
                  $replace = "FROM `".$LINK_ID_TABLE[$itemtype]."`
                              INNER JOIN `".$LINK_ID_TABLE[$ctype]."`
                              ON (`".$LINK_ID_TABLE[$itemtype]."`.`items_id`=".$LINK_ID_TABLE[$ctype].".`id`
                                  AND `".$LINK_ID_TABLE[$itemtype]."`.`itemtype` = '$ctype')";

                  $query_num=str_replace("FROM `".$CFG_GLPI["union_search_type"][$itemtype]."`",
                                         $replace,$tmpquery);
                  $query_num=str_replace($CFG_GLPI["union_search_type"][$itemtype],
                                         $LINK_ID_TABLE[$ctype],$query_num);
               }
               // Union/Recursivity Hack
               if (isset($CFG_GLPI["recursive_type"][$ctype])) {
                  $query_num=str_replace("ENTITYRESTRICT",
                                    getEntitiesRestrictRequest('',$LINK_ID_TABLE[$ctype],'','',true),
                                    $query_num);
               } else {
                  $query_num=str_replace("ENTITYRESTRICT",
                                         getEntitiesRestrictRequest('',$LINK_ID_TABLE[$ctype]),
                                         $query_num);
               }
               $result_num = $DB->query($query_num);
               $numrows+= $DB->result($result_num,0,0);
            }
         }
      } else {
         $result_num = $DB->query($query_num);
         $numrows= $DB->result($result_num,0,0);
      }
   }

   // If export_all reset LIMIT condition
   if ($export_all) {
      $LIMIT="";
   }

   if (!empty($WHERE) || !empty($COMMONWHERE)) {
      if (!empty($COMMONWHERE)) {
         $WHERE =' WHERE '.$COMMONWHERE.(!empty($WHERE)?' AND ( '.$WHERE.' )':'');
      } else {
         $WHERE =' WHERE '.$WHERE.' ';
      }
      $first=false;
   }

   if (!empty($HAVING)) {
      $HAVING=' HAVING '.$HAVING;
   }

   $DB->query("SET SESSION group_concat_max_len = 9999999;");

   // Create QUERY
   if (isset($CFG_GLPI["union_search_type"][$itemtype])) {
      $first=true;
      $QUERY="";
      foreach ($CFG_GLPI[$CFG_GLPI["union_search_type"][$itemtype]] as $ctype) {
         if (haveTypeRight($ctype,'r')) {
            if ($first) {
               $first=false;
            } else {
               $QUERY.=" UNION ";
            }
            $tmpquery="";
            // No ref table case
            if (empty($LINK_ID_TABLE[$itemtype])) {
               $tmpquery = $SELECT.", $ctype AS TYPE ".
                           $FROM.
                           $WHERE;
               $tmpquery = str_replace($CFG_GLPI["union_search_type"][$itemtype],
                                       $LINK_ID_TABLE[$ctype],$tmpquery);
               // State case :
               if ($itemtype==STATE_TYPE) {
                  $tmpquery .= " AND ".$LINK_ID_TABLE[$ctype].".`states_id` > '0' ";
               }
            } else {// Ref table case
               $tmpquery = $SELECT.", $ctype AS TYPE, ".$LINK_ID_TABLE[$itemtype].".`id` AS refID, ".
                                   $LINK_ID_TABLE[$ctype].".`entities_id` AS ENTITY ".
                           $FROM.
                           $WHERE;
               $replace = "FROM `".$LINK_ID_TABLE[$itemtype]."`
                           INNER JOIN `".$LINK_ID_TABLE[$ctype]."`
                           ON (`".$LINK_ID_TABLE[$itemtype]."`.`items_id`=".$LINK_ID_TABLE[$ctype].".`id`
                               AND `".$LINK_ID_TABLE[$itemtype]."`.`itemtype` = '$ctype')";
               $tmpquery = str_replace("FROM `".$CFG_GLPI["union_search_type"][$itemtype]."`",$replace,
                                       $tmpquery);
               $tmpquery = str_replace($CFG_GLPI["union_search_type"][$itemtype],
                                       $LINK_ID_TABLE[$ctype],$tmpquery);
            }
            // Union/Recursivity Hack
            if (isset($CFG_GLPI["recursive_type"][$ctype])) {
               $tmpquery = str_replace("ENTITYRESTRICT",
                                    getEntitiesRestrictRequest('',$LINK_ID_TABLE[$ctype],'','',true),
                                    $tmpquery);
            } else {
               $tmpquery = str_replace("ENTITYRESTRICT",
                                       getEntitiesRestrictRequest('',$LINK_ID_TABLE[$ctype]),$tmpquery);
            }
            // SOFTWARE HACK
            if ($ctype==SOFTWARE_TYPE) {
               $tmpquery = str_replace("glpi_softwares.serial","''",$tmpquery);
               $tmpquery = str_replace("glpi_softwares.otherserial","''",$tmpquery);
            }
            $QUERY .= $tmpquery;
         }
      }
      if (empty($QUERY)) {
         echo displaySearchError($output_type);
         return;
      }
      $QUERY .= str_replace($CFG_GLPI["union_search_type"][$itemtype].".","",$ORDER).
                $LIMIT;
   } else {
      $QUERY = $SELECT.
               $FROM.
               $WHERE.
               $GROUPBY.
               $HAVING.
               $ORDER.
               $LIMIT;
   }

   // Get it from database and DISPLAY
   if ($result = $DB->query($QUERY)) {
      // if real search or complete export : get numrows from request
      if (!$nosearch||$export_all) {
         $numrows= $DB->numrows($result);
      }
      // Contruct Pager parameters
      $globallinkto = getMultiSearchItemForLink("field",$field).
                      getMultiSearchItemForLink("link",$link).
                      getMultiSearchItemForLink("contains",$contains).
                      getMultiSearchItemForLink("field2",$field2).
                      getMultiSearchItemForLink("contains2",$contains2).
                      getMultiSearchItemForLink("itemtype2",$itemtype2).
                      getMultiSearchItemForLink("link2",$link2);

      $parameters = "sort=$sort&amp;order=$order".$globallinkto;

      if ($output_type==GLOBAL_SEARCH) {
         $ci = new CommonItem();
         $ci->setType($itemtype);
         echo "<div class='center'><h2>".$ci->getType($itemtype);
         // More items
         if ($numrows>$start+GLOBAL_SEARCH_DISPLAY_COUNT) {
            echo " <a href='$target?$parameters'>".$LANG['common'][66]."</a>";
         }
         echo "</h2></div>\n";
      }

      // If the begin of the view is before the number of items
      if ($start<$numrows) {
         // Display pager only for HTML
         if ($output_type==HTML_OUTPUT) {
            // For plugin add new parameter if available
            if ($itemtype>1000) {
               if (isset($PLUGIN_HOOKS['plugin_types'][$itemtype])) {
                  $function='plugin_'.$PLUGIN_HOOKS['plugin_types'][$itemtype].
                            '_addParamFordynamicReport';

                  if (function_exists($function)) {
                     $out=$function($itemtype);
                     if (is_array($out) && count($out)) {
                        foreach ($out as $key => $val) {
                           if (is_array($val)) {
                              $parameters .= getMultiSearchItemForLink($key,$val);
                           } else {
                              $parameters .= "&amp;$key=$val";
                           }
                        }
                     }
                  }
               }
            }
            printPager($start,$numrows,$target,$parameters,$itemtype);
         }

         // Form to massive actions
         $isadmin=(haveTypeRight($itemtype,"w")
                   || (in_array($itemtype,$CFG_GLPI["infocom_types"])
                       && haveTypeRight(INFOCOM_TYPE,"w")));

         if ($isadmin && $output_type==HTML_OUTPUT) {
            echo "<form method='post' name='massiveaction_form' id='massiveaction_form' action=\"".
                   $CFG_GLPI["root_doc"]."/front/massiveaction.php\">";
         }

         // Compute number of columns to display
         // Add toview elements
         $nbcols=$toview_count;
         // Add meta search elements if real search (strlen>0) or only NOT search
         if ($_SESSION["glpisearchcount2"][$itemtype]>0 && is_array($itemtype2)) {
            for ($i=0 ; $i<$_SESSION["glpisearchcount2"][$itemtype] ; $i++) {
               if (isset($itemtype2[$i])
                   && isset($contains2[$i])
                   && strlen($contains2[$i])>0
                   && $itemtype2[$i]>0
                   && (!isset($link2[$i]) || !strstr($link2[$i],"NOT"))) {

                  $nbcols++;
               }
            }
         }

         if ($output_type==HTML_OUTPUT) { // HTML display - massive modif
            $nbcols++;
         }

         // Define begin and end var for loop
         // Search case
         $begin_display=$start;
         $end_display=$start+$LIST_LIMIT;

         // No search Case
         if ($nosearch) {
            $begin_display=0;
            $end_display=min($numrows-$start,$LIST_LIMIT);
         }

         // Export All case
         if ($export_all) {
            $begin_display=0;
            $end_display=$numrows;
         }

         // Display List Header
         echo displaySearchHeader($output_type,$end_display-$begin_display+1,$nbcols);

         // New Line for Header Items Line
         echo displaySearchNewLine($output_type);
         $header_num=1;

         if ($output_type==HTML_OUTPUT) { // HTML display - massive modif
            $search_config="";
            if (haveRight("search_config","w") || haveRight("search_config_global","w")) {
               $tmp = " class='pointer' onClick=\"var w = window.open('".$CFG_GLPI["root_doc"].
                      "/front/popup.php?popup=search_config&amp;itemtype=$itemtype' ,'glpipopup', ".
                      "'height=400, width=1000, top=100, left=100, scrollbars=yes' ); w.focus();\"";

               $search_config = "<img alt='".$LANG['setup'][252]."' title='".$LANG['setup'][252].
                                  "' src='".$CFG_GLPI["root_doc"]."/pics/options_search.png' ";
               $search_config .= $tmp.">";
            }
            echo displaySearchHeaderItem($output_type,$search_config,$header_num,"",0,$order);
         }

         // Display column Headers for toview items
         foreach ($toview as $key => $val) {
            $linkto = "$target?sort=".$val."&amp;order=".($order=="ASC"?"DESC":"ASC").
                      "&amp;start=$start".$globallinkto;
            echo displaySearchHeaderItem($output_type,$SEARCH_OPTION[$itemtype][$val]["name"],
                                         $header_num,$linkto,$sort==$val,$order);
         }

         // Display columns Headers for meta items
         if ($_SESSION["glpisearchcount2"][$itemtype]>0 && is_array($itemtype2)) {
            for ($i=0 ; $i<$_SESSION["glpisearchcount2"][$itemtype] ; $i++) {
               if (isset($itemtype2[$i]) && $itemtype2[$i]>0 && isset($contains2[$i])
                   && strlen($contains2[$i])>0 && (!isset($link2[$i])
                   ||(!strstr($link2[$i],"NOT") || $contains2[$i]=="NULL"))) {

                  echo displaySearchHeaderItem($output_type,$names[$itemtype2[$i]]." - ".
                                               $SEARCH_OPTION[$itemtype2[$i]][$field2[$i]]["name"],
                                               $header_num);
               }
            }
         }

         // Add specific column Header
         if ($itemtype==CARTRIDGEITEM_TYPE) {
            echo displaySearchHeaderItem($output_type,$LANG['cartridges'][0],$header_num);
         }
         if ($itemtype==CONSUMABLEITEM_TYPE) {
            echo displaySearchHeaderItem($output_type,$LANG['consumables'][0],$header_num);
         }
         if ($itemtype==STATE_TYPE || $itemtype==RESERVATION_TYPE) {
            echo displaySearchHeaderItem($output_type,$LANG['state'][6],$header_num);
            $ci = new CommonItem();
         }
         if ($itemtype==RESERVATION_TYPE && $output_type==HTML_OUTPUT) {
            if (haveRight("reservation_central","w")) {
               echo displaySearchHeaderItem($output_type,"&nbsp;",$header_num);
               echo displaySearchHeaderItem($output_type,"&nbsp;",$header_num);
            }
            echo displaySearchHeaderItem($output_type,"&nbsp;",$header_num);
         }
         // End Line for column headers
         echo displaySearchEndLine($output_type);

         // if real search seek to begin of items to display (because of complete search)
         if (!$nosearch) {
            $DB->data_seek($result,$start);
         }

         // Define begin and end var for loop
         // Search case
         $i=$begin_display;

         // Init list of items displayed
         if ($output_type==HTML_OUTPUT) {
            initNavigateListItems($itemtype);
         }

         // Num of the row (1=header_line)
         $row_num=1;
         // Display Loop
         while ($i < $numrows && $i<($end_display)) {
            // Column num
            $item_num=1;
            // Get data and increment loop variables
            $data=$DB->fetch_assoc($result);
            $i++;
            $row_num++;
            // New line
            echo displaySearchNewLine($output_type,($i%2));

            // Add item in item list
            addToNavigateListItems($itemtype,$data["id"]);

            if ($output_type==HTML_OUTPUT) { // HTML display - massive modif
               $tmpcheck="";
               if ($isadmin) {
                  if ($itemtype==ENTITY_TYPE
                      && !in_array($data["id"],$_SESSION["glpiactiveentities"])) {

                     $tmpcheck="&nbsp;";
                  } else if (isset($CFG_GLPI["recursive_type"][$itemtype])
                             && !in_array($data["entities_id"],$_SESSION["glpiactiveentities"])) {
                     $tmpcheck="&nbsp;";
                  } else {
                     $sel="";
                     if (isset($_GET["select"]) && $_GET["select"]=="all") {
                        $sel="checked";
                     }
                     if (isset($_SESSION['glpimassiveactionselected'][$data["id"]])) {
                        $sel="checked";
                     }
                     $tmpcheck="<input type='checkbox' name='item[".$data["id"]."]' value='1' $sel>";
                  }
               }
               echo displaySearchItem($output_type,$tmpcheck,$item_num,$row_num,"width='10'");
            }

            // Print first element - specific case for user
            echo displaySearchItem($output_type,giveItem($itemtype,1,$data,0),$item_num,$row_num,
                              displayConfigItem($itemtype,$SEARCH_OPTION[$itemtype][1]["table"].".".
                                                          $SEARCH_OPTION[$itemtype][1]["field"]));
            // Print other toview items
            foreach ($toview as $key => $val) {
               // Do not display first item
               if ($key>0) {
                  echo displaySearchItem($output_type,giveItem($itemtype,$val,$data,$key),$item_num,
                                         $row_num,
                           displayConfigItem($itemtype,$SEARCH_OPTION[$itemtype][$val]["table"].".".
                                                       $SEARCH_OPTION[$itemtype][$val]["field"]));
               }
            }

            // Print Meta Item
            if ($_SESSION["glpisearchcount2"][$itemtype]>0 && is_array($itemtype2)) {
               for ($j=0 ; $j<$_SESSION["glpisearchcount2"][$itemtype] ; $j++) {
                  if (isset($itemtype2[$j]) && $itemtype2[$j]>0 && isset($contains2[$j])
                      && strlen($contains2[$j])>0 && (!isset($link2[$j])
                      ||(!strstr($link2[$j],"NOT") || $contains2[$j]=="NULL"))) {

                     // General case
                     if (strpos($data["META_$j"],"$$$$")===false) {
                        $out=giveItem ($itemtype2[$j],$field2[$j],$data,$j,1);
                        echo displaySearchItem($output_type,$out,$item_num,$row_num);

                     // Case of GROUP_CONCAT item : split item and multilline display
                     } else {
                        $split=explode("$$$$",$data["META_$j"]);
                        $count_display=0;
                        $out="";
                        $unit="";
                        if (isset($SEARCH_OPTION[$itemtype2[$j]][$field2[$j]]['unit'])) {
                           $unit=$SEARCH_OPTION[$itemtype2[$j]][$field2[$j]]['unit'];
                        }
                        for ($k=0 ; $k<count($split) ; $k++) {
                           if ($contains2[$j]=="NULL" || strlen($contains2[$j])==0
                               ||preg_match('/'.$contains2[$j].'/i',$split[$k])
                               || isset($SEARCH_OPTION[$itemtype2[$j]][$field2[$j]]['forcegroupby'])) {

                              if ($count_display) {
                                 $out.= "<br>";
                              }
                              $count_display++;

                              // Manage Link to item
                              $split2=explode("$$",$split[$k]);
                              if (isset($split2[1])) {
                                 $out .= "<a href=\"".$CFG_GLPI["root_doc"]."/".
                                           $INFOFORM_PAGES[$itemtype2[$j]]."?id=".$split2[1]."\">";
                                 $out .= $split2[0].$unit;
                                 if ($_SESSION["glpiis_ids_visible"] || empty($split2[0])) {
                                    $out .= " (".$split2[1].")";
                                 }
                                 $out .= "</a>";
                              } else {
                                 $out .= $split[$k].$unit;
                              }
                           }
                        }
                        echo displaySearchItem($output_type,$out,$item_num,$row_num);
                     }
                  }
               }
            }
            // Specific column display
            if ($itemtype==CARTRIDGEITEM_TYPE) {
               echo displaySearchItem($output_type,
                                      countCartridges($data["id"],$data["ALARM"],$output_type),
                                      $item_num,$row_num);
            }
            if ($itemtype==CONSUMABLEITEM_TYPE) {
               echo displaySearchItem($output_type,
                                      countConsumables($data["id"],$data["ALARM"],$output_type),
                                      $item_num,$row_num);
            }
            if ($itemtype==STATE_TYPE || $itemtype==RESERVATION_TYPE) {
               $ci->setType($data["TYPE"]);
               echo displaySearchItem($output_type,$ci->getType(),$item_num,$row_num);
            }
            if ($itemtype==RESERVATION_TYPE && $output_type==HTML_OUTPUT) {
               if (haveRight("reservation_central","w")) {
                  if (!haveAccessToEntity($data["ENTITY"])) {
                     echo displaySearchItem($output_type,"&nbsp;",$item_num,$row_num);
                     echo displaySearchItem($output_type,"&nbsp;",$item_num,$row_num);
                  } else {
                     if ($data["ACTIVE"]) {
                        echo displaySearchItem($output_type,"<a href=\"".
                                       $CFG_GLPI["root_doc"]."/front/reservation.php?id=".
                                       $data["refID"]."&amp;is_active=0\" title='".
                                       $LANG['buttons'][42]."'><img src=\"".
                                       $CFG_GLPI["root_doc"]."/pics/moins.png\" alt='' title=''></a>",
                                       $item_num,$row_num,"class='center'");
                     } else {
                        echo displaySearchItem($output_type,"<a href=\"".
                                       $CFG_GLPI["root_doc"]."/front/reservation.php?id=".
                                       $data["refID"]."&amp;is_active=1\" title='".
                                       $LANG['buttons'][41]."'><img src=\"".
                                       $CFG_GLPI["root_doc"]."/pics/plus.png\" alt='' title=''></a>",
                                       $item_num,$row_num,"class='center'");
                     }
                     echo displaySearchItem($output_type,"<a href=\"javascript:confirmAction('".
                                    addslashes($LANG['reservation'][38])."\\n".
                                    addslashes($LANG['reservation'][39])."','".
                                    $CFG_GLPI["root_doc"]."/front/reservation.php?id=".
                                    $data["refID"]."&amp;delete=delete')\" title='".
                                    $LANG['reservation'][6]."'><img src=\"".
                                    $CFG_GLPI["root_doc"]."/pics/delete.png\" alt='' title=''></a>",
                                    $item_num,$row_num,"class='center'");
                  }
               }
               if ($data["ACTIVE"]) {
                  echo displaySearchItem($output_type,"<a href='".$target."?show=resa&amp;id=".
                                 $data["refID"]."' title='".$LANG['reservation'][21]."'><img src=\"".
                                 $CFG_GLPI["root_doc"]."/pics/reservation-3.png\" alt='' title=''></a>",
                                 $item_num,$row_num,"class='center'");
               } else {
                  echo displaySearchItem($output_type,"&nbsp;",$item_num,$row_num);
               }
            }
            // End Line
            echo displaySearchEndLine($output_type);
         }

         $title="";
         // Create title
         if ($output_type==PDF_OUTPUT_LANDSCAPE || $output_type==PDF_OUTPUT_PORTRAIT) {
            if ($_SESSION["glpisearchcount"][$itemtype]>0 && count($contains)>0) {
               for ($key=0 ; $key<$_SESSION["glpisearchcount"][$itemtype] ; $key++) {
                  if (strlen($contains[$key])>0) {
                     if (isset($link[$key])) {
                        $title.=" ".$link[$key]." ";
                     }
                     switch ($field[$key]) {
                        case "all" :
                           $title .= $LANG['common'][66];
                           break;

                        case "view" :
                           $title .= $LANG['search'][11];
                           break;

                        default :
                           $title .= $SEARCH_OPTION[$itemtype][$field[$key]]["name"];
                     }
                     $title .= " = ".$contains[$key];
                  }
               }
            }
            if ($_SESSION["glpisearchcount2"][$itemtype]>0 && count($contains2)>0) {
               for ($key=0 ; $key<$_SESSION["glpisearchcount2"][$itemtype] ; $key++) {
                  if (strlen($contains2[$key])>0) {
                     if (isset($link2[$key])) {
                        $title .= " ".$link2[$key]." ";
                     }
                     $title .= $names[$itemtype2[$key]]."/";
                     $title .= $SEARCH_OPTION[$itemtype2[$key]][$field2[$key]]["name"];
                     $title .= " = ".$contains2[$key];
                  }
               }
            }
         }

         // Display footer
         echo displaySearchFooter($output_type,$title);

         // Delete selected item
         if ($output_type==HTML_OUTPUT) {
            if ($isadmin) {
               echo "<table width='80%' class='tab_glpi'>";
               echo "<tr><td><img src=\"".$CFG_GLPI["root_doc"]."/pics/arrow-left.png\" alt=''></td>";
               echo "<td class='center'>";
               echo "<a onclick= \"if (markCheckboxes('massiveaction_form')) return false;\" href='".
                      $_SERVER['PHP_SELF']."?select=all' >".$LANG['buttons'][18]."</a></td>";
               echo "<td>/</td><td class='center'>";
               echo "<a onclick= \"if (unMarkCheckboxes('massiveaction_form')) return false;\" href='".
                      $_SERVER['PHP_SELF']."?select=none'>".$LANG['buttons'][19]."</a>";
               echo "</td><td class='left' width='80%'>";
               dropdownMassiveAction($itemtype,$is_deleted);
               echo "</td>";
               echo "</table>\n";

               // End form for delete item
               echo "</form>\n";
            } else {
               echo "<br>";
            }
         }
         if ($output_type==HTML_OUTPUT) { // In case of HTML display
            printPager($start,$numrows,$target,$parameters);
         }
      } else {
         echo displaySearchError($output_type);
      }
   } else {
      echo $DB->error();
   }
   // Clean selection
   $_SESSION['glpimassiveactionselected']=array();
}


/**
 * Generic Function to add GROUP BY to a request
 *
 *@param $LINK link to use
 *@param $NOT is is a negative search ?
 *@param $itemtype item type
 *@param $ID ID of the item to search
 *@param $val value search
 *@param $meta is it a meta item ?
 *@param $num item number
 *
 *@return select string
 *
 **/
function addHaving($LINK,$NOT,$itemtype,$ID,$val,$meta,$num) {
   global $SEARCH_OPTION;

   $table=$SEARCH_OPTION[$itemtype][$ID]["table"];
   $field=$SEARCH_OPTION[$itemtype][$ID]["field"];

   $NAME="ITEM_";
   if ($meta) {
      $NAME="META_";
   }

   // Plugin can override core definition for its type
   if ($itemtype>1000) {
      if (isset($PLUGIN_HOOKS['plugin_types'][$itemtype])) {
         $function='plugin_'.$PLUGIN_HOOKS['plugin_types'][$itemtype].'_addHaving';
         if (function_exists($function)) {
            $out=$function($LINK,$NOT,$itemtype,$ID,$val,$num);
            if (!empty($out)) {
               return $out;
            }
         }
      }
   }

   switch ($table.".".$field) {
      default :
      break;
   }

   //// Default cases
   // Link with plugin tables
   if ($itemtype<=1000) {
      if (preg_match("/^glpi_plugin_([a-zA-Z]+)/", $table, $matches)) {
         if (count($matches)==2) {
            $plug=$matches[1];
            $function='plugin_'.$plug.'_addHaving';
            if (function_exists($function)) {
               $out=$function($LINK,$NOT,$itemtype,$ID,$val,$num);
               if (!empty($out)) {
                  return $out;
               }
            }
         }
      }
   }

   // Preformat items
   if (isset($SEARCH_OPTION[$itemtype][$ID]["datatype"])) {
      switch ($SEARCH_OPTION[$itemtype][$ID]["datatype"]) {
         case "number" :
         case "decimal" :
            $search=array("/\&lt;/","/\&gt;/");
            $replace=array("<",">");
            $val=preg_replace($search,$replace,$val);
            if (preg_match("/([<>])([=]*)[[:space:]]*([0-9]+)/",$val,$regs)) {
               if ($NOT) {
                  if ($regs[1]=='<') {
                     $regs[1]='>';
                  } else {
                     $regs[1]='<';
                  }
               }
               $regs[1].=$regs[2];
               return " $LINK (`$NAME$num` ".$regs[1]." ".$regs[3]." ) ";
            } else {
               if (is_numeric($val)) {
                  if (isset($SEARCH_OPTION[$itemtype][$ID]["width"])) {
                     if (!$NOT) {
                        return " $LINK (`$NAME$num` < ".
                                 (intval($val) + $SEARCH_OPTION[$itemtype][$ID]["width"])."
                                 AND `$NAME$num` > ".
                                 (intval($val) - $SEARCH_OPTION[$itemtype][$ID]["width"]).") ";
                     } else {
                        return " $LINK (`$NAME$num` > ".
                                 (intval($val) + $SEARCH_OPTION[$itemtype][$ID]["width"])."
                                 OR `$NAME$num` < ".
                                 (intval($val) - $SEARCH_OPTION[$itemtype][$ID]["width"])." ) ";
                     }
                  } else { // Exact search
                     if (!$NOT) {
                        return " $LINK (`$NAME$num` = ".(intval($val)).") ";
                     }
                     return " $LINK (`$NAME$num` <> ".(intval($val)).") ";
                  }
               }
            }
            break;
      }
   }

   $ADD="";
   if (($NOT && $val!="NULL")
       || $val=='^$') {

      $ADD = " OR `$NAME$num` IS NULL";
   }

   return " $LINK (`$NAME$num`".makeTextSearch($val,$NOT)."
                   $ADD ) ";

}


/**
 * Generic Function to add ORDER BY to a request
 *
 *@param $itemtype ID of the device type
 *@param $ID field to add
 *@param $order order define
 *@param $key item number
 *
 *@return select string
 *
 **/
function addOrderBy($itemtype,$ID,$order,$key=0) {
   global $SEARCH_OPTION,$CFG_GLPI,$PLUGIN_HOOKS;

   // Security test for order
   if ($order!="ASC") {
      $order="DESC";
   }

   $table=$SEARCH_OPTION[$itemtype][$ID]["table"];
   $field=$SEARCH_OPTION[$itemtype][$ID]["field"];
   $linkfield=$SEARCH_OPTION[$itemtype][$ID]["linkfield"];

   if (isset($CFG_GLPI["union_search_type"][$itemtype])) {
      return " ORDER BY ITEM_$key $order ";
   }

   // Plugin can override core definition for its type
   if ($itemtype>1000) {
      if (isset($PLUGIN_HOOKS['plugin_types'][$itemtype])) {
         $function='plugin_'.$PLUGIN_HOOKS['plugin_types'][$itemtype].'_addOrderBy';
         if (function_exists($function)) {
            $out=$function($itemtype,$ID,$order,$key);
            if (!empty($out)) {
               return $out;
            }
         }
      }
   }

   switch($table.".".$field) {
      case "glpi_auth_tables.name" :
         return " ORDER BY `glpi_users`.`authtype`, `glpi_authldaps`.`name`,
                           `glpi_authmails`.`name` $order ";
         break;

      case "glpi_contracts.expire" :
         return " ORDER BY ADDDATE(`glpi_contracts`.`begin_date`,
                                   INTERVAL `glpi_contracts`.`duration` MONTH) $order ";
         break;

      case "glpi_contracts.expire_notice" :
         return " ORDER BY ADDDATE(`glpi_contracts`.`begin_date`,
                                   INTERVAL (`glpi_contracts`.`duration`-`glpi_contracts`.`notice`)
                                   MONTH) $order ";
         break;

      case "glpi_users.name" :
         if (!empty($SEARCH_OPTION[$itemtype][$ID]["linkfield"])) {
            $linkfield="_".$SEARCH_OPTION[$itemtype][$ID]["linkfield"];
            return " ORDER BY ".$table.$linkfield.".realname $order, ".
                                $table.$linkfield.".firstname $order, ".
                                $table.$linkfield.".name $order";
         }
         break;

      case "glpi_networkports.ip" :
         return " ORDER BY INET_ATON($table.$field) $order ";
         break;
   }

   //// Default cases

   // Link with plugin tables
   if ($itemtype<=1000) {
      if (preg_match("/^glpi_plugin_([a-zA-Z]+)/", $table, $matches)) {
         if (count($matches)==2) {
            $plug=$matches[1];
            $function='plugin_'.$plug.'_addOrderBy';
            if (function_exists($function)) {
               $out=$function($itemtype,$ID,$order,$key);
               if (!empty($out)) {
                  return $out;
               }
            }
         }
      }
   }

   // Preformat items
   if (isset($SEARCH_OPTION[$itemtype][$ID]["datatype"])) {
      switch ($SEARCH_OPTION[$itemtype][$ID]["datatype"]) {
         case "date_delay" :
            return " ORDER BY ADDDATE($table.".$SEARCH_OPTION[$itemtype][$ID]["datafields"][1].",
                                      INTERVAL $table.".$SEARCH_OPTION[$itemtype][$ID]["datafields"][2]."
                                      MONTH) $order ";
            break;
      }
   }

   //return " ORDER BY $table.$field $order ";
   return " ORDER BY ITEM_$key $order ";

}


/**
 * Generic Function to add default columns to view
 *
 *@param $itemtype device type
 *
 *@return select string
 *
 **/
function addDefaultToView ($itemtype) {
   global $CFG_GLPI;

   $toview=array();

   // Add first element (name)
   array_push($toview,1);

   // Add entity view :
   if (isMultiEntitiesMode()
       && (isset($CFG_GLPI["union_search_type"][$itemtype])
           || isset($CFG_GLPI["recursive_type"][$itemtype])
           || count($_SESSION["glpiactiveentities"])>1)) {

      array_push($toview,80);
   }
   return $toview;
}


/**
 * Generic Function to add default select to a request
 *
 *@param $itemtype device type
 *
 *@return select string
 *
 **/
function addDefaultSelect ($itemtype) {
   global $CFG_GLPI, $LINK_ID_TABLE;

   switch ($itemtype) {
      case RESERVATION_TYPE :
         $ret = "`glpi_reservationsitems`.`is_active` AS ACTIVE, ";
         break;

      case CARTRIDGEITEM_TYPE :
         $ret = "`glpi_cartridgesitems`.`alarm_threshold` AS ALARM, ";
         break;

      case CONSUMABLEITEM_TYPE :
         $ret = "`glpi_consumablesitems`.`alarm_threshold` AS ALARM, ";
         break;

      case CRONTASK_TYPE :
         $ret = "`glpi_crontasks`.`plugin` AS plugin, `glpi_crontasks`.`name` AS name, ";
         break;

      default :
         $ret = "";
   }
   if (isset($CFG_GLPI["recursive_type"][$itemtype])) {
      $ret .= $LINK_ID_TABLE[$itemtype].".entities_id, ".$LINK_ID_TABLE[$itemtype].".is_recursive, ";
   }
   return $ret;
}


/**
 * Generic Function to add select to a request
 *
 *@param $ID ID of the item to add
 *@param $num item num in the request
 *@param $itemtype item type
 *@param $meta is it a meta item ?
 *@param $meta_type meta type table ID
 *
 *@return select string
 *
 **/
function addSelect ($itemtype,$ID,$num,$meta=0,$meta_type=0) {
   global $LINK_ID_TABLE,$SEARCH_OPTION,$PLUGIN_HOOKS,$CFG_GLPI;

   $table=$SEARCH_OPTION[$itemtype][$ID]["table"];
   $field=$SEARCH_OPTION[$itemtype][$ID]["field"];
   $addtable="";
   $NAME="ITEM";
   if ($meta) {
      $NAME="META";
      if ($LINK_ID_TABLE[$meta_type]!=$table) {
         $addtable="_".$meta_type;
      }
   }

   // Plugin can override core definition for its type
   if ($itemtype>1000) {
      if (isset($PLUGIN_HOOKS['plugin_types'][$itemtype])) {
         $function='plugin_'.$PLUGIN_HOOKS['plugin_types'][$itemtype].'_addSelect';
         if (function_exists($function)) {
            $out=$function($itemtype,$ID,$num);
            if (!empty($out)) {
               return $out;
            }
         }
      }
   }

   switch ($table.".".$field) {
      case "glpi_contacts.completename" :
         // Contact for display in the enterprise item
         if ($CFG_GLPI["names_format"]==FIRSTNAME_BEFORE) {
            $name1='firstname';
            $name2='name';
         } else {
            $name1='name';
            $name2='firstname';
         }
         return " GROUP_CONCAT(DISTINCT CONCAT(`$table$addtable`.`$name1`, ' ',
                                               `$table$addtable`.`$name2`, '$$',
                                               `$table$addtable`.`id`)
                               SEPARATOR '$$$$') AS ".$NAME."_$num, ";
         break;

      case "glpi_users.name" :
         if ($itemtype!=USER_TYPE) {
            $linkfield="";
            if (!empty($SEARCH_OPTION[$itemtype][$ID]["linkfield"])) {
               $linkfield="_".$SEARCH_OPTION[$itemtype][$ID]["linkfield"];
            }
            return "`$table$linkfield$addtable`.`$field` AS ".$NAME."_$num,
                    `$table$linkfield$addtable`.`realname` AS ".$NAME."_".$num."_2,
                    `$table$linkfield$addtable`.`id` AS ".$NAME."_".$num."_3,
                    `$table$linkfield$addtable`.`firstname` AS ".$NAME."_".$num."_4, ";
         }
         break;

      case "glpi_contracts.expire_notice" : // ajout jmd
         return "`$table$addtable`.`begin_date` AS ".$NAME."_$num,
                 `$table$addtable`.`duration` AS ".$NAME."_".$num."_2,
                 `$table$addtable`.`notice` AS ".$NAME."_".$num."_3, ";
         break;

      case "glpi_contracts.expire" : // ajout jmd
         return "`$table$addtable`.`begin_date` AS ".$NAME."_$num,
                 `$table$addtable`.`duration` AS ".$NAME."_".$num."_2, ";
         break;

      case "glpi_softwareslicenses.number" :
         return " FLOOR(SUM(`$table$addtable`.`$field`)
                        * COUNT(DISTINCT `$table$addtable`.`id`)
                        / COUNT(`$table$addtable`.`id`)) AS ".$NAME."_".$num.",
                  MIN(`$table$addtable`.`$field`) AS ".$NAME."_".$num."_2, ";
         break;

      case "glpi_computers_softwaresversions.count" :
         return " COUNT(DISTINCT `glpi_computers_softwaresversions$addtable`.`id`)
                     AS ".$NAME."_".$num.", ";
         break;

      case "glpi_devicesharddrives.specif_default" :
         return " SUM(`DEVICE_".HDD_DEVICE."`.`specificity`)
                  / COUNT(`DEVICE_".HDD_DEVICE."`.`id`)
                  * COUNT(DISTINCT `DEVICE_".HDD_DEVICE."`.`id`) AS ".$NAME."_".$num.", ";
         break;

      case "glpi_devicesmemories.specif_default" :
         return " SUM(`DEVICE_".RAM_DEVICE."`.`specificity`)
                  / COUNT(`DEVICE_".RAM_DEVICE."`.`id`)
                  * COUNT(DISTINCT `DEVICE_".RAM_DEVICE."`.`id`) AS ".$NAME."_".$num.", ";
         break;

      case "glpi_devicesprocessors.specif_default" :
         return " SUM(`DEVICE_".PROCESSOR_DEVICE."`.`specificity`)
                  / COUNT(`DEVICE_".PROCESSOR_DEVICE."`.`id`) AS ".$NAME."_".$num.", ";
         break;

      case "glpi_tickets.count" :
         return " COUNT(DISTINCT `glpi_tickets$addtable`.`id`) AS ".$NAME."_".$num.", ";
         break;

      case "glpi_networkports.mac" :
         $port = " GROUP_CONCAT(DISTINCT `$table$addtable`.`$field` SEPARATOR '$$$$')
                     AS ".$NAME."_$num, ";
         if ($itemtype==COMPUTER_TYPE) {
            $port .= "GROUP_CONCAT(DISTINCT `DEVICE_".NETWORK_DEVICE."`.`specificity`
                                   SEPARATOR '$$$$') AS ".$NAME."_".$num."_2, ";
         }
         return $port;
         break;

      case "glpi_profiles.name" :
         if ($itemtype==USER_TYPE) {
            return " GROUP_CONCAT(`$table$addtable`.`$field` SEPARATOR '$$$$') AS ".$NAME."_$num,
                     GROUP_CONCAT(`glpi_entities`.`completename` SEPARATOR '$$$$')
                        AS ".$NAME."_".$num."_2,
                     GROUP_CONCAT(`glpi_profiles_users`.`is_recursive` SEPARATOR '$$$$')
                        AS ".$NAME."_".$num."_3,";
         }
         break;

      case "glpi_entities.completename" :
         if ($itemtype==USER_TYPE) {
            return " GROUP_CONCAT(`$table$addtable`.`completename` SEPARATOR '$$$$')
                        AS ".$NAME."_$num,
                     GROUP_CONCAT(`glpi_profiles`.`name` SEPARATOR '$$$$') AS ".$NAME."_".$num."_2,
                     GROUP_CONCAT(`glpi_profiles_users`.`is_recursive` SEPARATOR '$$$$')
                        AS ".$NAME."_".$num."_3,";
         } else {
            return "`$table$addtable`.`completename` AS ".$NAME."_$num,
                    `$table$addtable`.`id` AS ".$NAME."_".$num."_2, ";
         }
         break;

      case "glpi_auth_tables.name":
         return "`glpi_users`.`authtype` AS ".$NAME."_".$num.",
                 `glpi_users`.`auths_id` AS ".$NAME."_".$num."_2,
                 `glpi_authldaps$addtable`.`$field` AS ".$NAME."_".$num."_3,
                 `glpi_authmails$addtable`.`$field` AS ".$NAME."_".$num."_4, ";
         break;

      case "glpi_softwareslicenses.name" :
      case "glpi_softwaresversions.name" :
         if ($meta) {
            return " GROUP_CONCAT(DISTINCT CONCAT(`glpi_softwares`.`name`, ' - ',
                                                  `$table$addtable`.`$field`)
                                  SEPARATOR '$$$$') AS ".$NAME."_".$num.", ";
         }
         break;

      case "glpi_softwareslicenses.serial" :
      case "glpi_softwareslicenses.otherserial" :
      case "glpi_softwareslicenses.expire" :
      case "glpi_softwareslicenses.comment" :
      case "glpi_softwaresversions.comment" :
         if ($meta) {
            return " GROUP_CONCAT(DISTINCT CONCAT(`glpi_softwares`.`name`, ' - ',
                                                  `$table$addtable`.`$field`)
                                  SEPARATOR '$$$$') AS ".$NAME."_".$num.", ";
         } else {
            return " GROUP_CONCAT(DISTINCT CONCAT(`$table$addtable`.`name`, ' - ',
                                                  `$table$addtable`.`$field`)
                                  SEPARATOR '$$$$') AS ".$NAME."_".$num.", ";
         }
         break;

      case "glpi_states.name" :
         if ($meta && $meta_type==SOFTWARE_TYPE) {
            return " GROUP_CONCAT(DISTINCT CONCAT(`glpi_softwares`.`name`, ' - ',
                                                  `glpi_softwaresversions$addtable`.`name`, ' - ',
                                                  `$table$addtable`.`$field`)
                                  SEPARATOR '$$$$') AS ".$NAME."_".$num.", ";
         } else if ($itemtype==SOFTWARE_TYPE) {
            return " GROUP_CONCAT(DISTINCT CONCAT(`glpi_softwaresversions`.`name`, ' - ',
                                                  `$table$addtable`.`$field`)
                                  SEPARATOR '$$$$') AS ".$NAME."_".$num.", ";
         }
         break;

      case 'glpi_crontasks.description' :
         return " `name` AS ".$NAME."_".$num.", ";
         break;
   }

   //// Default cases
   // Link with plugin tables
   if ($itemtype<=1000) {
      if (preg_match("/^glpi_plugin_([a-zA-Z]+)/", $table, $matches)) {
         if (count($matches)==2) {
            $plug=$matches[1];
            $function='plugin_'.$plug.'_addSelect';
            if (function_exists($function)) {
               $out=$function($itemtype,$ID,$num);
               if (!empty($out)) {
                  return $out;
               }
            }
         }
      }
   }

   $tocompute="`$table$addtable`.`$field`";

   if (isset($SEARCH_OPTION[$itemtype][$ID]["computation"])) {
      $tocompute = $SEARCH_OPTION[$itemtype][$ID]["computation"];
      $tocompute = str_replace("TABLE",$table.$addtable,$tocompute);
   }

   // Preformat items
   if (isset($SEARCH_OPTION[$itemtype][$ID]["datatype"])) {
      switch ($SEARCH_OPTION[$itemtype][$ID]["datatype"]) {
         case "date_delay" :
            if ($meta) {
               return " GROUP_CONCAT
                           (DISTINCT ADDDATE
                              (`$table$addtable`.".$SEARCH_OPTION[$itemtype][$ID]["datafields"][1].",
                               INTERVAL
                               `$table$addtable`.".$SEARCH_OPTION[$itemtype][$ID]["datafields"][2]."
                               MONTH)
                            SEPARATOR '$$$$') AS ".$NAME."_$num, ";
            } else {
               return "`$table$addtable`.".$SEARCH_OPTION[$itemtype][$ID]["datafields"][1]."
                           AS ".$NAME."_$num,
                       `$table$addtable`.".$SEARCH_OPTION[$itemtype][$ID]["datafields"][2]."
                           AS ".$NAME."_".$num."_2, ";
            }
            break;

         case "itemlink" :
            if ($meta
                || (isset($SEARCH_OPTION[$itemtype][$ID]["itemlink_type"])
                    && $SEARCH_OPTION[$itemtype][$ID]["itemlink_type"] != $itemtype)) {
               return " GROUP_CONCAT(DISTINCT CONCAT(`$table$addtable`.`$field`, '$$' ,
                                                     `$table$addtable`.`id`)
                                     SEPARATOR '$$$$') AS ".$NAME."_$num, ";
            } else {
               return "$tocompute AS ".$NAME."_$num,
                       `$table$addtable`.`id` AS ".$NAME."_".$num."_2, ";
            }
         break;
      }
   }

   // Default case
   if ($meta
       || (isset($SEARCH_OPTION[$itemtype][$ID]["forcegroupby"])
           && $SEARCH_OPTION[$itemtype][$ID]["forcegroupby"])) {
      return " GROUP_CONCAT(DISTINCT $tocompute SEPARATOR '$$$$') AS ".$NAME."_$num, ";
   } else {
      return "$tocompute AS ".$NAME."_$num, ";
   }
}


/**
 * Generic Function to add default where to a request
 *
 *@param $itemtype device type
 *
 *@return select string
 *
 **/
function addDefaultWhere ($itemtype) {

   switch ($itemtype) {
      // No link
      case USER_TYPE :
         // View all entities
         if (isViewAllEntities()) {
            return "";
         } else {
            return getEntitiesRestrictRequest("","glpi_profiles_users");
         }
         break;

      default :
         return "";
   }
}


/**
 * Generic Function to add where to a request
 *
 *@param $val item num in the request
 *@param $nott is it a negative serach ?
 *@param $link link string
 *@param $itemtype item type
 *@param $ID ID of the item to search
 *@param $meta is a meta search (meta=2 in search.class.php)
 *
 *@return select string
 *
 **/
function addWhere($link,$nott,$itemtype,$ID,$val,$meta=0) {
   global $LINK_ID_TABLE,$LANG,$SEARCH_OPTION,$PLUGIN_HOOKS,$CFG_GLPI;

   $table = $SEARCH_OPTION[$itemtype][$ID]["table"];
   $field = $SEARCH_OPTION[$itemtype][$ID]["field"];

   $inittable = $table;
   if ($meta && $LINK_ID_TABLE[$itemtype]!=$table) {
      $table .= "_".$itemtype;
   }

   // Hack to allow search by ID on every sub-table
   if (preg_match('/^\$\$\$\$([0-9]+)$/',$val,$regs)) {
      return $link." (`$table`.`id` ".($nott?"<>":"=").$regs[1].") ";
   }

   $SEARCH=makeTextSearch($val,$nott);

   // Plugin can override core definition for its type
   if ($itemtype>1000) {
      if (isset($PLUGIN_HOOKS['plugin_types'][$itemtype])) {
         $function='plugin_'.$PLUGIN_HOOKS['plugin_types'][$itemtype].'_addWhere';
         if (function_exists($function)) {
            $out = $function($link,$nott,$itemtype,$ID,$val);
            if (!empty($out)) {
               return $out;
            }
         }
      }
   }

   switch ($inittable.".".$field) {
      case "glpi_users.name" :
         $linkfield="";
         if (!empty($SEARCH_OPTION[$itemtype][$ID]["linkfield"])) {
            $linkfield = "_".$SEARCH_OPTION[$itemtype][$ID]["linkfield"];

            if ($meta && $LINK_ID_TABLE[$itemtype]!=$inittable) {
               $table = $inittable;
               $linkfield .= "_".$itemtype;
            }
         }
         if ($itemtype==USER_TYPE) { // glpi_users case / not link table
            return makeTextCriteria("`$table$linkfield`.`$field`",$val,$nott,$link);
         } else {
            if ($CFG_GLPI["names_format"]==FIRSTNAME_BEFORE) {
               $name1='firstname';
               $name2='realname';
            } else {
               $name1='realname';
               $name2='firstname';
            }
            return $link." (`$table$linkfield`.`$name1` $SEARCH
                            OR `$table$linkfield`.`$name2` $SEARCH
                            OR CONCAT(`$table$linkfield`.`$name1`,' ',
                                      `$table$linkfield`.`$name2`) $SEARCH".
                            makeTextCriteria("`$table$linkfield`.`$field`",$val,$nott,'OR').") ";
         }
         break;

      case "glpi_networkports.mac" :
         if ($itemtype==COMPUTER_TYPE) {
            return "$link (".makeTextCriteria("`DEVICE_".NETWORK_DEVICE."`.`specificity`",$val,$nott,'').
                             makeTextCriteria("`$table`.`$field`",$val,$nott,'OR').")";
         }
         return makeTextCriteria("`$table`.`$field`",$val,$nott,$link);

      case "glpi_contracts.expire" :
         $search=array("/\&lt;/","/\&gt;/");
         $replace=array("<",">");
         $val=preg_replace($search,$replace,$val);
         if (preg_match("/([<>=])(.*)/",$val,$regs)) {
            return $link." DATEDIFF(ADDDATE(`$table`.`begin_date`,
                                            INTERVAL `$table`.`duration` MONTH),
                                    CURDATE() )".$regs[1].$regs[2]." ";
         } else {
            return $link." ADDDATE(`$table`.`begin_date`,
                                   INTERVAL `$table`.`duration` MONTH) $SEARCH ";
         }
         break;

      // ajout jmd
      case "glpi_contracts.expire_notice" :
         $search=array("/\&lt;/","/\&gt;/");
         $replace=array("<",">");
         $val=preg_replace($search,$replace,$val);
         if (preg_match("/([<>])(.*)/",$val,$regs)){
            return $link." `$table`.`notice`<>'0'
                      AND DATEDIFF(ADDDATE(`$table`.`begin_date`,
                                           INTERVAL (`$table`.`duration` - `$table`.`notice`) MONTH),
                                   CURDATE() )".$regs[1].$regs[2]." ";
         } else {
            return $link." ADDDATE(`$table`.`begin_date`,
                                   INTERVAL (`$table`.`duration` - `$table`.`notice`) MONTH) $SEARCH ";
         }
         break;

      case "glpi_infocoms.sink_time" :
      case "glpi_infocoms.warranty_duration" :
         $ADD = "";
         if ($nott && $val!="NULL") {
            $ADD = " OR `$table`.`$field` IS NULL";
         }
         if (is_numeric($val)) {
            if ($nott) {
               return $link." (`$table`.`$field` <> ".intval($val)." ".
                               $ADD." ) ";
            } else {
               return $link." (`$table`.`$field` = ".intval($val)."  ".
                               $ADD." ) ";
            }
         }
         break;

      case "glpi_infocoms.sink_type" :
         $ADD = "";
         if ($nott && $val!="NULL") {
            $ADD = " OR `$table`.`$field` IS NULL";
         }
         if (preg_match("/$val/i",getAmortTypeName(1))) {
            $val=1;
         } else if (preg_match("/$val/i",getAmortTypeName(2))) {
            $val=2;
         }
         if (is_int($val) && $val>0) {
            if ($nott) {
               return $link." (`$table`.`$field` <> '$val' ".
                               $ADD." ) ";
            } else {
               return $link." (`$table`.`$field` = '$val' ".
                               $ADD." ) ";
            }
         }
         break;

      case "glpi_contacts.completename" :
         if ($CFG_GLPI["names_format"]==FIRSTNAME_BEFORE) {
            $name1='firstname';
            $name2='name';
         } else {
            $name1='name';
            $name2='firstname';
         }
         return $link." (`$table`.`$name1` $SEARCH
                         OR `$table`.`$name2` $SEARCH
                         OR CONCAT(`$table`.`$name1`,' ',`$table`.`$name2`) $SEARCH) ";
         break;

      case "glpi_auth_tables.name" :
         return $link." (`glpi_authmails`.`name` $SEARCH
                         OR `glpi_authldaps`.`name` $SEARCH ) ";
         break;

      case "glpi_contracts.renewal" :
         $valid=getContractRenewalIDByName($val);
         if ($valid>0){
            return $link." `$table`.`$field`"."="."'$valid'";
         } else {
            return "";
         }
      break;
   }

   //// Default cases

   // Link with plugin tables
   if ($itemtype<=1000) {
      if (preg_match("/^glpi_plugin_([a-zA-Z]+)/", $inittable, $matches)) {
         if (count($matches)==2) {
            $plug=$matches[1];
            $function='plugin_'.$plug.'_addWhere';
            if (function_exists($function)) {
               $out=$function($link,$nott,$itemtype,$ID,$val);
               if (!empty($out)) {
                  return $out;
               }
            }
         }
      }
   }
   $tocompute="`$table`.`$field`";
   if (isset($SEARCH_OPTION[$itemtype][$ID]["computation"])) {
      $tocompute=$SEARCH_OPTION[$itemtype][$ID]["computation"];
   }

   // Preformat items
   if (isset($SEARCH_OPTION[$itemtype][$ID]["datatype"])) {
      switch ($SEARCH_OPTION[$itemtype][$ID]["datatype"]) {
         case "date" :
         case "datetime" :
         case "date_delay" :
            $date_computation=$tocompute;
            $interval_search=" MONTH ";

            if ($SEARCH_OPTION[$itemtype][$ID]["datatype"]=="date_delay") {
               $date_computation="ADDDATE(`$table`.".$SEARCH_OPTION[$itemtype][$ID]["datafields"][1].",
                                          INTERVAL
                                          `$table`.".$SEARCH_OPTION[$itemtype][$ID]["datafields"][2]."
                                          MONTH)";
            }
            $search=array("/\&lt;/","/\&gt;/");
            $replace=array("<",">");
            $val=preg_replace($search,$replace,$val);
            if (preg_match("/([<>=])(.*)/",$val,$regs)) {
               if (is_numeric($regs[2])) {
                  return $link." NOW() ".$regs[1]." ADDDATE($date_computation,
                                                            INTERVAL ".$regs[2]." $interval_search) ";
               } else {
                  // Reformat date if needed
                  $regs[2]=preg_replace('@(\d{1,2})(-|/)(\d{1,2})(-|/)(\d{4})@','\5-\3-\1',$regs[2]);
                  if (preg_match('/[0-9]{2,4}-[0-9]{1,2}-[0-9]{1,2}/',$regs[2])) {
                     return $link." $date_computation ".$regs[1]." '".$regs[2]."'";
                  } else {
                     return "";
                  }
               }
            } else { // standard search
               // Date format modification if needed
               $val=preg_replace('@(\d{1,2})(-|/)(\d{1,2})(-|/)(\d{4})@','\5-\3-\1',$val);
               return makeTextCriteria($date_computation,$val,$nott,$link);
            }
            break;

         case "bool" :
            if (!is_numeric($val)) {
               if (strcasecmp($val,$LANG['choice'][0])==0) {
                  $val=0;
               } else if (strcasecmp($val,$LANG['choice'][1])==0) {
                  $val=1;
               }
            }
            // No break here : use number comparaison case

         case "number" :
         case "decimal" :
            $search=array("/\&lt;/",
                          "/\&gt;/");
            $replace=array("<",
                           ">");
            $val=preg_replace($search,$replace,$val);
            if (preg_match("/([<>])([=]*)[[:space:]]*([0-9]+)/",$val,$regs)) {
               if ($nott) {
                  if ($regs[1]=='<') {
                     $regs[1]='>';
                  } else {
                     $regs[1]='<';
                  }
               }
               $regs[1].=$regs[2];
               return $link." ($tocompute ".$regs[1]." ".$regs[3].") ";
            } else if (is_numeric($val)) {
               if (isset($SEARCH_OPTION[$itemtype][$ID]["width"])) {
                  $ADD = "";
                  if ($nott && $val!="NULL") {
                     $ADD = " OR $tocompute IS NULL";
                  }
                  if ($nott) {
                     return $link." ($tocompute < ".(intval($val)
                                                       - $SEARCH_OPTION[$itemtype][$ID]["width"])."
                                     OR $tocompute > ".(intval($val)
                                                          + $SEARCH_OPTION[$itemtype][$ID]["width"])."
                                     $ADD) ";
                  } else {
                     return $link." (($tocompute >= ".(intval($val)
                                                         - $SEARCH_OPTION[$itemtype][$ID]["width"])."
                                      AND $tocompute <= ".(intval($val)
                                                             + $SEARCH_OPTION[$itemtype][$ID]["width"]).").
                                     $ADD) ";
                  }
               } else {
                  if (!$nott) {
                     return " $link ($tocompute = ".(intval($val)).") ";
                  } else {
                     return " $link ($tocompute <> ".(intval($val)).") ";
                  }
               }
            }
            break;
      }
   }

   // Default case
   return makeTextCriteria($tocompute,$val,$nott,$link);

}


/**
 * Generic Function to display Items
 *
 *@param $field field which have a specific display type
 *@param $itemtype item type
 *
 *@return string to print
 *
 **/
function displayConfigItem ($itemtype,$field) {

   switch ($field) {
      case "glpi_ocslinks.last_update" :
      case "glpi_ocslinks.last_ocs_update" :
      case "glpi_computers.date_mod" :
      case "glpi_printers.date_mod" :
      case "glpi_networkequipments.date_mod" :
      case "glpi_peripherals.date_mod" :
      case "glpi_phones.date_mod" :
      case "glpi_softwares.date_mod" :
      case "glpi_monitors.date_mod" :
      case "glpi_documents.date_mod" :
      case "glpi_ocsservers.date_mod" :
      case "glpi_users.last_login" :
      case "glpi_users.date_mod" :
         return " class='center'";
         break;

      default:
         return "";
         break;
   }
}


/**
 * Generic Function to display Items
 *
 *@param $itemtype item type
 *@param $ID ID of the SEARCH_OPTION item
 *@param $data array containing data results
 *@param $num item num in the request
 *@param $meta is a meta item ?
 *
 *@return string to print
 *
 **/
function giveItem ($itemtype,$ID,$data,$num,$meta=0) {
   global $CFG_GLPI,$SEARCH_OPTION,$INFOFORM_PAGES,$LANG,$PLUGIN_HOOKS;

   if (isset($CFG_GLPI["union_search_type"][$itemtype])
       && $CFG_GLPI["union_search_type"][$itemtype]==$SEARCH_OPTION[$itemtype][$ID]["table"]) {
      return giveItem ($data["TYPE"],$ID,$data,$num);
   }

   // Plugin can override core definition for its type
   if ($itemtype>1000) {
      if (isset($PLUGIN_HOOKS['plugin_types'][$itemtype])) {
         $function='plugin_'.$PLUGIN_HOOKS['plugin_types'][$itemtype].'_giveItem';
         if (function_exists($function)) {
            $out=$function($itemtype,$ID,$data,$num);
            if (!empty($out)) {
               return $out;
            }
         }
      }
   }

   $NAME="ITEM_";
   if ($meta) {
      $NAME="META_";
   }
   $table=$SEARCH_OPTION[$itemtype][$ID]["table"];
   $field=$SEARCH_OPTION[$itemtype][$ID]["field"];
   $linkfield=$SEARCH_OPTION[$itemtype][$ID]["linkfield"];

   switch ($table.'.'.$field) {
      case "glpi_users.name" :
         // USER search case
         if (!empty($linkfield)) {
            return formatUserName($data[$NAME.$num."_3"],$data[$NAME.$num],$data[$NAME.$num."_2"],
                                  $data[$NAME.$num."_4"],1);
         }
         break;

      case "glpi_profiles.name" :
         if ($itemtype==USER_TYPE) {
            $out="";
            $split=explode("$$$$",$data[$NAME.$num]);
            $split2=explode("$$$$",$data[$NAME.$num."_2"]);
            $split3=explode("$$$$",$data[$NAME.$num."_3"]);
            $count_display=0;
            $added=array();
            for ($k=0 ; $k<count($split) ; $k++) {
               if (strlen(trim($split[$k]))>0) {
                  $text=$split[$k]." - ".$split2[$k];
                  if ($split3[$k]) {
                     $text .= " (R)";
                  }
                  if (!in_array($text,$added)) {
                     if ($count_display) {
                        $out.= "<br>";
                     }
                     $count_display++;
                     $out .= $text;
                     $added[]=$text;
                  }
               }
            }
            return $out;
         }
         break;

      case "glpi_entities.completename" :
         if ($itemtype==USER_TYPE) {
            $out="";
            $split=explode("$$$$",$data[$NAME.$num]);
            $split2=explode("$$$$",$data[$NAME.$num."_2"]);
            $split3=explode("$$$$",$data[$NAME.$num."_3"]);
            $added=array();
            $count_display=0;
            for ($k=0 ; $k<count($split) ; $k++) {
               if (strlen(trim($split[$k]))>0) {
                  $text=$split[$k]." - ".$split2[$k];
                  if ($split3[$k]) {
                     $text .= " (R)";
                  }
                  if (!in_array($text,$added)) {
                     if ($count_display) {
                        $out.= "<br>";
                     }
                     $count_display++;
                     $out .= $text;
                     $added[]=$text;
                  }
               }
            }
            return $out;
         } else if ($data[$NAME.$num."_2"]==0) {  // Set name for Root entity
            $data[$NAME.$num]=$LANG['entity'][2];
         }
         break;

      case "glpi_documentstypes.icon" :
         if (!empty($data[$NAME.$num])) {
            return "<img class='middle' alt='' src='".$CFG_GLPI["typedoc_icon_dir"]."/".
                     $data[$NAME.$num]."'>";
         }
         return "&nbsp;";

      case "glpi_documents.filename" :
         return getDocumentLink($data['id']);

      case "glpi_devicesharddrives.specif_default" :
      case "glpi_devicesmemories.specif_default" :
      case "glpi_devicesprocessors.specif_default" :
         return $data[$NAME.$num];

      case "glpi_networkports.mac" :
         $out = "";
         if ($itemtype==COMPUTER_TYPE) {
            $displayed=array();
            if (!empty($data[$NAME.$num."_2"])) {
               $split=explode("$$$$",$data[$NAME.$num."_2"]);
               $count_display=0;
               for ($k=0 ; $k<count($split) ; $k++) {
                  $lowstr=utf8_strtolower($split[$k]);
                  if (strlen(trim($split[$k]))>0 && !in_array($lowstr,$displayed)) {
                     if ($count_display) {
                        $out .= "<br>";
                     }
                     $count_display++;
                     $out .= $split[$k];
                     $displayed[]=$lowstr;
                  }
               }
               if (!empty($data[$NAME.$num])) {
                  $out .= "<br>";
               }
            }
            if (!empty($data[$NAME.$num])) {
               $split=explode("$$$$",$data[$NAME.$num]);
               $count_display=0;
               for ($k=0 ; $k<count($split) ; $k++){
                  $lowstr=utf8_strtolower($split[$k]);
                  if (strlen(trim($split[$k]))>0 && !in_array($lowstr,$displayed)) {
                     if ($count_display) {
                        $out .= "<br>";
                     }
                     $count_display++;
                     $out.= $split[$k];
                     $displayed[]=$lowstr;
                  }
               }
            }
            return $out;
         }
         break;

      case "glpi_contracts.duration" :
      case "glpi_contracts.notice" :
      case "glpi_contracts.periodicity" :
      case "glpi_contracts.billing" :
         if (!empty($data[$NAME.$num])) {
            $split=explode('$$$$', $data[$NAME.$num]);
            $output = "";
            foreach ($split as $duration) {
               $output .= (empty($output)?'':'<br>') . $duration . " " . $LANG['financial'][57];
            }
            return $output;
         }
         return "&nbsp;";

      case "glpi_contracts.renewal" :
         return getContractRenewalName($data[$NAME.$num]);

      case "glpi_contracts.expire_notice" : // ajout jmd
         if ($data[$NAME.$num]!='' && !empty($data[$NAME.$num])) {
            return getExpir($data[$NAME.$num],$data[$NAME.$num."_2"],$data[$NAME.$num."_3"]);
         }
         return "&nbsp;";

      case "glpi_contracts.expire" : // ajout jmd
         if ($data[$NAME.$num]!='' && !empty($data[$NAME.$num])){
            return getExpir($data[$NAME.$num],$data[$NAME.$num."_2"]);
         }
         return "&nbsp;";

      case "glpi_infocoms.sink_time" :
         if (!empty($data[$NAME.$num])) {
            return $data[$NAME.$num]." ".$LANG['financial'][9];
         }
         return "&nbsp;";

      case "glpi_infocoms.warranty_duration" :
         if (!empty($data[$NAME.$num])) {
            return $data[$NAME.$num]." ".$LANG['financial'][57];
         }
         return "&nbsp;";

      case "glpi_infocoms.sink_type" :
         return getAmortTypeName($data[$NAME.$num]);

      case "glpi_infocoms.alert" :
         if ($data[$NAME.$num]==pow(2,ALERT_END)) {
            return $LANG['financial'][80];
         }
         return "";

      case "glpi_contracts.alert" :
         switch ($data[$NAME.$num]) {
            case pow(2,ALERT_END);
               return $LANG['buttons'][32];

            case pow(2,ALERT_NOTICE);
               return $LANG['financial'][10];

            case pow(2,ALERT_END) + pow(2,ALERT_NOTICE);
               return $LANG['buttons'][32]." + ".$LANG['financial'][10];
         }
         return "";

      case "glpi_tickets.count" :
         if ($data[$NAME.$num]>0 && haveRight("show_all_ticket","1")) {
            $out= "<a href=\"".$CFG_GLPI["root_doc"]."/front/tracking.php?reset=".
                    "reset_before&status=all&itemtype=$itemtype&items_id=".$data['id']."\">";
            $out .= $data[$NAME.$num];
            $out .= "</a>";
         } else {
            $out= $data[$NAME.$num];
         }
         return $out;

      case "glpi_softwareslicenses.number" :
         if ($data[$NAME.$num."_2"]==-1) {
            return $LANG['software'][4];
         }
         if (empty($data[$NAME.$num])) {
            return 0;
         }
         return $data[$NAME.$num];

      case "glpi_auth_tables.name" :
         return getAuthMethodName($data[$NAME.$num], $data[$NAME.$num."_2"], 1,
                                  $data[$NAME.$num."_3"].$data[$NAME.$num."_4"]);

      case "glpi_reservationsitems.comment" :
         if (empty($data[$NAME.$num])) {
            return "<a href='".$CFG_GLPI["root_doc"]."/front/reservation.php?comment=".
                     $data["refID"]."' title='".$LANG['reservation'][22]."'>".$LANG['common'][49].
                   "</a>";
         }
         return "<a href='".$CFG_GLPI["root_doc"]."/front/reservation.php?comment=".
                  $data['refID']."' title='".$LANG['reservation'][22]."'>".
                  resume_text($data[$NAME.$num])."</a>";

      case 'glpi_crontasks.description' :
         return CronTask::getDescription($data['id'],$data['plugin'],$data['name']);

      case 'glpi_crontasks.state':
         return CronTask::getStateName($data[$NAME.$num]);

      case 'glpi_crontasks.mode':
         return CronTask::getModeName($data[$NAME.$num]);
   }


   //// Default case

   // Link with plugin tables : need to know left join structure
   if ($itemtype<=1000) {
      if (preg_match("/^glpi_plugin_([a-zA-Z]+)/", $table.'.'.$field, $matches)) {
         if (count($matches)==2) {
            $plug=$matches[1];
            $function='plugin_'.$plug.'_giveItem';
            if (function_exists($function)) {
               $out=$function($itemtype,$ID,$data,$num);
               if (!empty($out)) {
                  return $out;
               }
            }
         }
      }
   }
   $unit='';
   if (isset($SEARCH_OPTION[$itemtype][$ID]['unit'])) {
      $unit=$SEARCH_OPTION[$itemtype][$ID]['unit'];
   }

   // Preformat items
   if (isset($SEARCH_OPTION[$itemtype][$ID]["datatype"])) {
      switch ($SEARCH_OPTION[$itemtype][$ID]["datatype"]) {
         case "itemlink" :
            if (!empty($data[$NAME.$num."_2"])) {
               $out= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$itemtype]."?id=".
                       $data[$NAME.$num."_2"]."\">";
               $out .= $data[$NAME.$num].$unit;
               if ($_SESSION["glpiis_ids_visible"] || empty($data[$NAME.$num])) {
                  $out .= " (".$data[$NAME.$num."_2"].")";
               }
               $out .= "</a>";
               return $out;
            } else if (isset($SEARCH_OPTION[$itemtype][$ID]["itemlink_type"])) {
               $out="";
               $split=explode("$$$$",$data[$NAME.$num]);
               $count_display=0;
               for ($k=0 ; $k<count($split) ; $k++) {
                  if (strlen(trim($split[$k]))>0) {
                     $split2=explode("$$",$split[$k]);
                     if (isset($split2[1]) && $split2[1]>0) {
                        if ($count_display) {
                           $out .= "<br>";
                        }
                        $count_display++;
                        $out .= "<a href=\"".$CFG_GLPI["root_doc"]."/".
                                  $INFOFORM_PAGES[$SEARCH_OPTION[$itemtype][$ID]["itemlink_type"]].
                                  "?id=".$split2[1]."\">";
                        $out .= $split2[0].$unit;
                        if ($_SESSION["glpiis_ids_visible"] || empty($split2[0])) {
                           $out .= " (".$split2[1].")";
                        }
                        $out .= "</a>";
                     }
                  }
               }
               return $out;
            }
            break;

         case "text" :
            return nl2br($data[$NAME.$num]);

         case "date" :
            return convDate($data[$NAME.$num]);

         case "datetime" :
            return convDateTime($data[$NAME.$num]);

         case "realtime" :
            return getRealtime($data[$NAME.$num]);

         case "date_delay" :
            if ($data[$NAME.$num]!='' && !empty($data[$NAME.$num])) {
               return getWarrantyExpir($data[$NAME.$num],$data[$NAME.$num."_2"]);
            }
            return "&nbsp;";

         case "email" :
            $email=trim($data[$NAME.$num]);
            if (!empty($email)) {
               return "<a href='mailto:$email'>$email</a>";
            }
            return "&nbsp;";

         case "weblink" :
            $orig_link=trim($data[$NAME.$num]);
            if (!empty($orig_link)) {
               // strip begin of link
               $link=preg_replace('/https?:\/\/(www.)?/','',$orig_link);
               $link=preg_replace('/\/$/','',$link);
               if (utf8_strlen($link)>30) {
                  $link=utf8_substr($link,0,30)."...";
               }
               return "<a href=\"$orig_link\" target='_blank'>$link</a>";
            }
            return "&nbsp;";

         case "number" :
            if (isset($SEARCH_OPTION[$itemtype][$ID]['forcegroupby'])
                && $SEARCH_OPTION[$itemtype][$ID]['forcegroupby']) {
               $out="";
               $split=explode("$$$$",$data[$NAME.$num]);
               $count_display=0;
               for ($k=0 ; $k<count($split) ; $k++) {
                  if (strlen(trim($split[$k]))>0) {
                     if ($count_display) {
                        $out.= "<br>";
                     }
                     $count_display++;
                     $out .= str_replace(' ','&nbsp;',formatNumber($split[$k],false,0)).$unit;
                  }
               }
               return $out;
            }
            return str_replace(' ','&nbsp;',formatNumber($data[$NAME.$num],false,0)).$unit;

         case "decimal" :
            if (isset($SEARCH_OPTION[$itemtype][$ID]['forcegroupby'])
                && $SEARCH_OPTION[$itemtype][$ID]['forcegroupby']) {
               $out="";
               $split=explode("$$$$",$data[$NAME.$num]);
               $count_display=0;
               for ($k=0 ; $k<count($split) ; $k++) {
                  if (strlen(trim($split[$k]))>0) {
                     if ($count_display) {
                        $out.= "<br>";
                     }
                     $count_display++;
                     $out .= str_replace(' ','&nbsp;',formatNumber($split[$k])).$unit;
                  }
               }
               return $out;
            }
            return str_replace(' ','&nbsp;',formatNumber($data[$NAME.$num])).$unit;

         case "bool" :
            return getYesNo($data[$NAME.$num]).$unit;
      }
   }

   // Manage items with need group by / group_concat
   if (isset($SEARCH_OPTION[$itemtype][$ID]['forcegroupby'])
       && $SEARCH_OPTION[$itemtype][$ID]['forcegroupby']) {
      $out="";
      $split=explode("$$$$",$data[$NAME.$num]);
      $count_display=0;
      for ($k=0 ; $k<count($split) ; $k++) {
         if (strlen(trim($split[$k]))>0) {
            if ($count_display) {
               $out.= "<br>";
            }
            $count_display++;
            $out .= $split[$k].$unit;
         }
      }
      return $out;
   }

   return $data[$NAME.$num].$unit;
}


/**
 * Generic Function to get transcript table name
 *
 *@param $table reference table
 *@param $devicetype device type ID
 *@param $meta_type meta table type ID
 *
 *@return Left join string
 *
 **/
function translate_table($table,$devicetype=0,$meta_type=0) {

   $ADD="";
   if ($meta_type) {
      $ADD="_".$meta_type;
   }

   switch ($table) {
      case "glpi_computers_devices" :
         if ($devicetype==0) {
            return "$table$ADD";
         }
         return "DEVICE_$devicetype$ADD";

      default :
         return "$table$ADD";
   }
}


/**
 * Generic Function to add Default left join to a request
 *
 *@param $itemtype reference ID
 *@param $ref_table reference table
 *@param $already_link_tables array of tables already joined
 *
 *@return Left join string
 *
 **/
function addDefaultJoin ($itemtype,$ref_table,&$already_link_tables) {

   switch ($itemtype) {
      // No link
      case USER_TYPE :
         return addLeftJoin($itemtype,$ref_table,$already_link_tables,"glpi_profiles_users","");

      default :
         return "";
   }
}


/**
 * Generic Function to add left join to a request
 *
 *@param $itemtype item type
 *@param $ref_table reference table
 *@param $already_link_tables array of tables already joined
 *@param $new_table new table to join
 *@param $devicetype devicetype for search on computer device
 *@param $meta is it a meta item ?
 *@param $meta_type meta type table
 *@param $linkfield linkfield for LeftJoin
 *
 *@return Left join string
 *
 **/
function addLeftJoin ($itemtype,$ref_table,&$already_link_tables,$new_table,$linkfield,
                      $devicetype=0,$meta=0,$meta_type=0){
   global $PLUGIN_HOOKS,$LANG;

   // Rename table for meta left join
   $AS = "";
   $nt = $new_table;

   // Multiple link possibilies case
   if ($new_table=="glpi_users") {
      $nt .= "_".$linkfield;
      $AS .= " AS ".$nt;
   }

   $addmetanum = "";
   $rt = $ref_table;
   if ($meta) {
      $addmetanum = "_".$meta_type;
      $AS = " AS $nt$addmetanum";
      $nt = $nt.$addmetanum;
   }

   // Auto link
   if ($ref_table==$new_table) {
      return "";
   }

   if (in_array
       (translate_table($new_table,$devicetype,$meta_type).".".$linkfield,$already_link_tables)) {
      return "";
   } else {
      array_push($already_link_tables,
                 translate_table($new_table,$devicetype,$meta_type).".".$linkfield);
   }

   // Plugin can override core definition for its type
   if ($itemtype>1000) {
      if (isset($PLUGIN_HOOKS['plugin_types'][$itemtype])) {
         $function='plugin_'.$PLUGIN_HOOKS['plugin_types'][$itemtype].'_addLeftJoin';
         if (function_exists($function)) {
            $out = $function($itemtype,$ref_table,$new_table,$linkfield,$already_link_tables);
            if (!empty($out)) {
               return $out;
            }
         }
      }
   }

   switch ($new_table) {
      // No link
      case "glpi_auth_tables" :
         return " LEFT JOIN `glpi_authldaps` ON (`glpi_users`.`authtype` = ".AUTH_LDAP."
                                                AND `glpi_users`.`auths_id` = `glpi_authldaps`.`id`)
                  LEFT JOIN `glpi_authmails` ON (`glpi_users`.`authtype` = ".AUTH_MAIL."
                                                 AND `glpi_users`.`auths_id` = `glpi_authmails`.`id`)";

      case "glpi_reservationsitems" :
         return "";

      case "glpi_computersdisks" :
         if ($meta) {
            return " INNER JOIN `$new_table` $AS ON (`$rt`.`id` = `$nt`.`computers_id`) ";
         }
         return " LEFT JOIN `$new_table` $AS ON (`$rt`.`id` = `$nt`.`computers_id`) ";

      case "glpi_filesystems" :
         $out = addLeftJoin($itemtype,$rt,$already_link_tables,"glpi_computersdisks",$linkfield);
         return $out."
                LEFT JOIN `$new_table` $AS ON (`glpi_computersdisks`.`filesystems_id` = `$nt`.`id`) ";

      case "glpi_entitiesdatas" :
         return " LEFT JOIN `$new_table` $AS ON (`$rt`.`id` = `$nt`.`entities_id`) ";

      case "glpi_ocslinks" :
      case "glpi_registrykeys":
         return " LEFT JOIN `$new_table` $AS ON (`$rt`.`id` = `$nt`.`computers_id`) ";

      case "glpi_operatingsystems" :
         return " LEFT JOIN `$new_table` $AS ON (`$rt`.`operatingsystems_id` = `$nt`.`id`) ";

      case "glpi_networkports" :
         $out="";
         // Add networking device for computers
         if ($itemtype==COMPUTER_TYPE) {
            $out = addLeftJoin($itemtype,$rt,$already_link_tables,"glpi_computers_devices",
                               $linkfield,NETWORK_DEVICE,$meta,$meta_type);
         }
         return $out."
                LEFT JOIN `$new_table` $AS ON (`$rt`.`id` = `$nt`.`items_id`
                                               AND `$nt`.`itemtype` = '$itemtype') ";

      case "glpi_netpoints" :
         // Link to glpi_networkports before
         $out = addLeftJoin($itemtype,$rt,$already_link_tables,"glpi_networkports",$linkfield);
         return $out."
                LEFT JOIN `$new_table` $AS ON (`glpi_networkports`.`netpoints_id` = `$nt`.`id`) ";

      case "glpi_tickets" :
      case "glpi_contracts_items" :
         return " LEFT JOIN `$new_table` $AS ON (`$rt`.`id` = `$nt`.`items_id`
                                                 AND `$nt`.`itemtype` = '$itemtype') ";

      case "glpi_users" :
         return " LEFT JOIN `$new_table` $AS ON (`$rt`.`$linkfield` = `$nt`.`id`) ";

      case "glpi_suppliers" :
         if ($itemtype==CONTACT_TYPE) {
            $out = addLeftJoin($itemtype,$rt,$already_link_tables,"glpi_contacts_suppliers",
                               "contacts_id");
            return $out."
                   LEFT JOIN `$new_table` $AS
                        ON (`glpi_contacts_suppliers`.`suppliers_id` = `$nt`.`id` ".
                            getEntitiesRestrictRequest("AND","glpi_suppliers",'','',true).") ";
         }
         return " LEFT JOIN `$new_table` $AS ON (`$rt`.`suppliers_id` = `$nt`.`id`) ";

      case "glpi_contacts" :
         $out = addLeftJoin($itemtype,$rt,$already_link_tables,"glpi_contacts_suppliers",
                            "suppliers_id");
         return $out."
                LEFT JOIN `$new_table` $AS
                     ON (`glpi_contacts_suppliers`.`contacts_id` = `$nt`.`id` ".
                         getEntitiesRestrictRequest("AND","glpi_contacts",'','',true)." ) ";

      case "glpi_contacts_suppliers" :
         return " LEFT JOIN `$new_table` $AS ON (`$rt`.`id` = `$nt`.`$linkfield`) ";

      case "glpi_manufacturers" :
         return " LEFT JOIN `$new_table` $AS ON (`$rt`.`manufacturers_id` = `$nt`.`id`) ";

      case "glpi_suppliers_infocoms" :
         $out = addLeftJoin($itemtype,$rt,$already_link_tables,"glpi_infocoms",$linkfield);
         return $out."
                LEFT JOIN `glpi_suppliers` AS glpi_suppliers_infocoms
                     ON (`glpi_infocoms`.`suppliers_id` = `$nt`.`id`) ";

      case "glpi_budgets" :
         $out = addLeftJoin($itemtype,$rt,$already_link_tables,"glpi_infocoms",$linkfield);
         return $out."
                LEFT JOIN `$new_table` $AS ON (`glpi_infocoms`.`budgets_id` = `$nt`.`id`) ";

      case "glpi_infocoms" :
         if ($itemtype == SOFTWARE_TYPE) {
            // Return the infocom linked to the license, not the template linked to the software
            return addLeftJoin($itemtype,$rt,$already_link_tables,"glpi_softwareslicenses",
                               $linkfield) ."
                   LEFT JOIN `$new_table` $AS
                        ON (`glpi_softwareslicenses`.`id` = `$nt`.`items_id`
                            AND `$nt`.`itemtype` = ".SOFTWARELICENSE_TYPE.") ";
         }
         return " LEFT JOIN `$new_table` $AS ON (`$rt`.`id` = `$nt`.`items_id`
                                                 AND `$nt`.`itemtype` = '$itemtype') ";

      case "glpi_states" :
         if ($itemtype == SOFTWARE_TYPE) {
            // Return the state of the version of the software
            $tt = translate_table("glpi_softwaresversions",$meta,$meta_type);
            return addLeftJoin($itemtype,$rt,$already_link_tables,"glpi_softwaresversions",
                               $linkfield,$devicetype,$meta,$meta_type) ."
                   LEFT JOIN `$new_table` $AS ON (`$tt`.`states_id` = `$nt`.`id`)";
         }
         return " LEFT JOIN `$new_table` $AS ON (`$rt`.`states_id` = `$nt`.`id`) ";

      case "glpi_profiles_users" :
      case "glpi_groups_users" :
         return " LEFT JOIN `$new_table` $AS ON (`$rt`.`id` = `$nt`.`users_id`) ";

      case "glpi_profiles" :
         // Link to glpi_profiles_users before
         $out = addLeftJoin($itemtype,$rt,$already_link_tables,"glpi_profiles_users",$linkfield);
         if ($itemtype==USER_TYPE) {
            $out .= addLeftJoin($itemtype,"glpi_profiles_users",$already_link_tables,
                                "glpi_complete_entities","entities_id");
         }
         return $out."
                LEFT JOIN `$new_table` $AS ON (`glpi_profiles_users`.`profiles_id` = `$nt`.`id`) ";

      case "glpi_entities" :
         if ($itemtype==USER_TYPE) {
            $out = addLeftJoin($itemtype,"glpi_profiles_users",$already_link_tables,
                               "glpi_profiles","");
            $out.= addLeftJoin($itemtype,"glpi_profiles_users",$already_link_tables,
                               "glpi_complete_entities","entities_id");
            return $out;
         }
         return " LEFT JOIN `$new_table` $AS ON (`$rt`.`$linkfield` = `$nt`.`id`) ";

      case "glpi_complete_entities" :
         array_push($already_link_tables,translate_table("glpi_entities",$devicetype,$meta_type).".".
                    $linkfield);
         if (empty($AS)) {
            $AS = "AS glpi_entities";
         }
         return " LEFT JOIN (SELECT `id`, `name`, `entities_id`, `completename`, `comment`, `level`
                             FROM `glpi_entities`
                             UNION
                             SELECT 0 AS id, '".addslashes($LANG['entity'][2])."' AS name,
                                    -1 AS entities_id,
                                    '".addslashes($LANG['entity'][2])."' AS completename,
                                    '' AS comment, -1 AS level) $AS
                        ON (`$rt`.`$linkfield` = `glpi_entities`.`id`) ";

      case "glpi_groups":
         if (empty($linkfield)) {
            // Link to glpi_users_group before
            $out = addLeftJoin($itemtype,$rt,$already_link_tables,"glpi_groups_users",$linkfield,
                               $devicetype,$meta,$meta_type);
            return $out."
                   LEFT JOIN `$new_table` $AS
                        ON (`glpi_groups_users$addmetanum`.`groups_id` = `$nt`.`id`) ";
         }
         return " LEFT JOIN `$new_table` $AS ON (`$rt`.`$linkfield` = `$nt`.`id`) ";

      case "glpi_contracts" :
         $out = addLeftJoin($itemtype,$rt,$already_link_tables,"glpi_contracts_items",$linkfield,
                            $devicetype,$meta,$meta_type);
         return $out."
                LEFT JOIN `$new_table` $AS
                     ON (`glpi_contracts_items$addmetanum`.`contracts_id` = `$nt`.`id`) ";

      case "glpi_softwareslicensestypes" :
         $tt = translate_table("glpi_softwareslicenses",$meta,$meta_type);
         return addLeftJoin($itemtype,$rt,$already_link_tables,"glpi_softwareslicenses",$linkfield,
                            $devicetype,$meta,$meta_type) ."
                LEFT JOIN `$new_table` $AS ON (`$tt`.`softwareslicensestypes_id` = `$nt`.`id`)";

      case "glpi_softwareslicenses" :
         if (!$meta) {
            return " LEFT JOIN `$new_table` $AS
                           ON (`$rt`.`id` = `$nt`.`softwares_id` ".
                               getEntitiesRestrictRequest("AND",$nt,'','',true).") ";
         }
         return "";

      case "glpi_softwaresversions" :
         if (!$meta) {
            return " LEFT JOIN `$new_table` $AS ON (`$rt`.`id` = `$nt`.`softwares_id`) ";
         }
         return "";

      case "glpi_computers_softwaresversions" :
         $out = addLeftJoin($itemtype,$rt,$already_link_tables,"glpi_softwaresversions",$linkfield,
                            $devicetype,$meta,$meta_type);
         return $out."
                LEFT JOIN `$new_table` $AS
                     ON (`glpi_softwaresversions$addmetanum`.`id` = `$nt`.`softwaresversions_id`) ";

      case "glpi_computers_devices" :
         if ($devicetype==0) {
            return " LEFT JOIN `$new_table` $AS ON (`$rt`.`id` = `$nt`.`computers_id`) ";
         }
         return " LEFT JOIN `$new_table` AS DEVICE_".$devicetype."
                        ON (`$rt`.`id` = `DEVICE_".$devicetype."`.`computers_id`
                            AND `DEVICE_".$devicetype."`.`devicetype` = '$devicetype') ";

      case "glpi_devicesprocessors" :
      case "glpi_devicespowersupplies" :
      case "glpi_devicesmemories" :
      case "glpi_devicesnetworkcards" :
      case "glpi_devicessoundcards" :
      case "glpi_devicesgraphiccards" :
      case "glpi_devicesmotherboards" :
      case "glpi_devicesharddrives" :
         switch ($new_table) {
            case "glpi_devicesprocessors" :
               $type = PROCESSOR_DEVICE;
               break;
            case "glpi_devicespowersupplies" :
               $type = POWER_DEVICE;
               break;
            case "glpi_devicesmemories" :
               $type = RAM_DEVICE;
               break;
            case "glpi_devicesnetworkcards" :
               $type = NETWORK_DEVICE;
               break;
            case "glpi_devicessoundcards" :
               $type = SND_DEVICE;
               break;
            case "glpi_devicesgraphiccards" :
               $type = GFX_DEVICE;
               break;
            case "glpi_devicesmotherboards" :
               $type = MOBOARD_DEVICE;
               break;
            case "glpi_devicesharddrives" :
               $type = HDD_DEVICE;
               break;
         }
         $out = addLeftJoin($itemtype,$rt,$already_link_tables,"glpi_computers_devices",
                            $linkfield,$type,$meta,$meta_type);
         return $out."
                LEFT JOIN `$new_table` $AS ON (`DEVICE_$type`.`devices_id` = `$nt`.`id`) ";

      default :
         // Link with plugin tables : need to know left join structure
         if ($itemtype<=1000) {
            if (preg_match("/^glpi_plugin_([a-zA-Z]+)/", $new_table, $matches)) {
               if (count($matches)==2) {
                  $plug=$matches[1];
                  $function = 'plugin_'.$plug.'_addLeftJoin';
                  if (function_exists($function)) {
                     $out=$function($itemtype,$ref_table,$new_table,$linkfield,$already_link_tables);
                     if (!empty($out)) {
                        return $out;
                     }
                  }
               }
            }
         }
         if (!empty($linkfield)) {
            return " LEFT JOIN `$new_table` $AS ON (`$rt`.`$linkfield` = `$nt`.`id`) ";
         }
         return "";
   }
}


/**
 * Generic Function to add left join for meta items
 *
 *@param $from_type reference item type ID
 *@param $to_type item type to add
 *@param $already_link_tables2 array of tables already joined
 *@param $nullornott Used LEFT JOIN (null generation) or INNER JOIN for strict join
 *
 *@return Meta Left join string
 *
 **/
function addMetaLeftJoin($from_type,$to_type,&$already_link_tables2,$nullornott) {
   global $LINK_ID_TABLE;

   $LINK=" INNER JOIN ";
   if ($nullornott) {
      $LINK=" LEFT JOIN ";
   }

   switch ($from_type) {
      case COMPUTER_TYPE :
         switch ($to_type) {
            case PRINTER_TYPE :
               array_push($already_link_tables2,$LINK_ID_TABLE[PRINTER_TYPE]);
               return " $LINK `glpi_computers_items` AS conn_print_$to_type
                           ON (`conn_print_$to_type`.`computers_id` = `glpi_computers`.`id`
                               AND `conn_print_$to_type`.`itemtype` = '".PRINTER_TYPE."')
                        $LINK `glpi_printers`
                           ON (`conn_print_$to_type`.`items_id` = `glpi_printers`.`id`) ";

            case MONITOR_TYPE :
               array_push($already_link_tables2,$LINK_ID_TABLE[MONITOR_TYPE]);
               return " $LINK `glpi_computers_items` AS conn_mon_$to_type
                           ON (`conn_mon_$to_type`.`computers_id` = `glpi_computers`.`id`
                               AND `conn_mon_$to_type`.`itemtype` = '".MONITOR_TYPE."')
                        $LINK `glpi_monitors`
                           ON (`conn_mon_$to_type`.`items_id` = `glpi_monitors`.`id`) ";

            case PERIPHERAL_TYPE :
               array_push($already_link_tables2,$LINK_ID_TABLE[PERIPHERAL_TYPE]);
               return " $LINK `glpi_computers_items` AS conn_periph_$to_type
                           ON (`conn_periph_$to_type`.`computers_id` = `glpi_computers`.`id`
                               AND `conn_periph_$to_type`.`itemtype` = '".PERIPHERAL_TYPE."')
                        $LINK `glpi_peripherals`
                           ON (`conn_periph_$to_type`.`items_id` = `glpi_peripherals`.`id`) ";

            case PHONE_TYPE :
               array_push($already_link_tables2,$LINK_ID_TABLE[PHONE_TYPE]);
               return " $LINK `glpi_computers_items` AS conn_phones_$to_type
                           ON (`conn_phones_$to_type`.`computers_id` = `glpi_computers`.`id`
                               AND `conn_phones_$to_type`.`itemtype` = '".PHONE_TYPE."')
                        $LINK `glpi_phones`
                           ON (`conn_phones_$to_type`.`items_id` = `glpi_phones`.`id`) ";

            case SOFTWARE_TYPE :
               /// TODO: link licenses via installed software OR by affected/computers_id ???
               array_push($already_link_tables2,$LINK_ID_TABLE[SOFTWARE_TYPE]);
               return " $LINK `glpi_computers_softwaresversions` AS inst_$to_type
                           ON (`inst_$to_type`.`computers_id` = `glpi_computers`.`id`)
                        $LINK `glpi_softwaresversions` AS glpi_softwaresversions_$to_type
                           ON (`inst_$to_type`.`softwaresversions_id`
                               = `glpi_softwaresversions_$to_type`.`id`)
                        $LINK `glpi_softwares`
                           ON (`glpi_softwaresversions_$to_type`.`softwares_id`
                               = `glpi_softwares`.`id`)
                        LEFT JOIN `glpi_softwareslicenses` AS glpi_softwareslicenses_$to_type
                           ON (`glpi_softwares`.`id` = `glpi_softwareslicenses_$to_type`.`softwares_id`" .
                               getEntitiesRestrictRequest(' AND',"glpi_softwareslicenses_$to_type",
                                                          '','',true).") ";
         }
         break;

      case MONITOR_TYPE :
         switch ($to_type) {
            case COMPUTER_TYPE :
               array_push($already_link_tables2,$LINK_ID_TABLE[COMPUTER_TYPE]);
               return " $LINK `glpi_computers_items` AS conn_mon_$to_type
                           ON (`conn_mon_$to_type`.`items_id` = `glpi_monitors`.`id`
                               AND `conn_mon_$to_type`.`itemtype` = '".MONITOR_TYPE."')
                        $LINK `glpi_computers`
                           ON (`conn_mon_$to_type`.`computers_id` = `glpi_computers`.`id`) ";
         }
         break;

      case PRINTER_TYPE :
         switch ($to_type) {
            case COMPUTER_TYPE :
               array_push($already_link_tables2,$LINK_ID_TABLE[COMPUTER_TYPE]);
               return " $LINK `glpi_computers_items` AS conn_mon_$to_type
                           ON (`conn_mon_$to_type`.`items_id` = `glpi_printers`.`id`
                               AND `conn_mon_$to_type`.`itemtype` = '".PRINTER_TYPE."')
                        $LINK `glpi_computers`
                           ON (`conn_mon_$to_type`.`computers_id` = `glpi_computers`.`id` ".
                               getEntitiesRestrictRequest("AND",'glpi_computers').") ";
         }
         break;

      case PERIPHERAL_TYPE :
         switch ($to_type) {
            case COMPUTER_TYPE :
               array_push($already_link_tables2,$LINK_ID_TABLE[COMPUTER_TYPE]);
               return " $LINK `glpi_computers_items` AS conn_mon_$to_type
                           ON (`conn_mon_$to_type`.`items_id` = `glpi_peripherals`.`id`
                               AND `conn_mon_$to_type`.`itemtype` = '".PERIPHERAL_TYPE."')
                        $LINK `glpi_computers`
                           ON (`conn_mon_$to_type`.`computers_id` = `glpi_computers`.`id`) ";
         }
         break;

      case PHONE_TYPE :
         switch ($to_type) {
            case COMPUTER_TYPE :
               array_push($already_link_tables2,$LINK_ID_TABLE[COMPUTER_TYPE]);
               return " $LINK `glpi_computers_items` AS conn_mon_$to_type
                           ON (`conn_mon_$to_type`.`items_id` = `glpi_phones`.`id`
                               AND `conn_mon_$to_type`.`itemtype` = '".PHONE_TYPE."')
                        $LINK `glpi_computers`
                           ON (`conn_mon_$to_type`.`computers_id` = `glpi_computers.id`) ";
         }
         break;

      case SOFTWARE_TYPE :
         switch ($to_type) {
            case COMPUTER_TYPE :
               array_push($already_link_tables2,$LINK_ID_TABLE[COMPUTER_TYPE]);
               return " $LINK `glpi_softwaresversions` AS glpi_softwaresversions_$to_type
                           ON (`glpi_softwaresversions_$to_type`.`softwares_id` = `glpi_softwares`.`id`)
                        $LINK `glpi_computers_softwaresversions` AS inst_$to_type
                           ON (`inst_$to_type`.`softwaresversions_id`
                               = `glpi_softwaresversions_$to_type`.`id`)
                        $LINK `glpi_computers`
                           ON (`inst_$to_type`.`computers_id` = `glpi_computers`.`id` ".
                               getEntitiesRestrictRequest("AND",'glpi_computers').") ";
         }
         break;
   }
}


/**
 * Convert an array to be add in url
 *
 * @param $name name of array
 * @param $array array to be added
 * @return string to add
 *
 */
function getMultiSearchItemForLink($name,$array) {

   $out="";
   if (is_array($array) && count($array)>0) {
      foreach($array as $key => $val) {
         $out .= "&amp;".$name."[$key]=".urlencode(stripslashes($val));
      }
   }
   return $out;
}


/**
 * Is the search item related to infocoms
 *
 * @param $itemtype item type
 * @param $searchID ID of the element in $SEARCH_OPTION
 * @return boolean
 *
 */
function isInfocomSearch($itemtype,$searchID) {
   global $CFG_GLPI;

   return (($searchID>=25 && $searchID<=28) || ($searchID>=37 && $searchID<=38)
           ||($searchID>=50 && $searchID<=59) || ($searchID>=120 && $searchID<=122))
          && in_array($itemtype,$CFG_GLPI["infocom_types"]);
}

?>
