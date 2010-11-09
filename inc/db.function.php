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

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Return foreign key field name for a table
 *
 * @param $table string: table name
 *
 * @return string field name used for a foreign key to the parameter table
**/
function getForeignKeyFieldForTable($table) {

   if (strpos($table,'glpi_')===false) {
      return "";
   }

  return str_replace("glpi_","",$table)."_id";
}


/**
 * Return table name for a given foreign key name
 *
 * @param $fkname string: foreign key name
 *
 * @return string table name corresponding to a foreign key name
**/
function getTableNameForForeignKeyField($fkname) {
   if (strpos($fkname,'_id')===false) {
      return "";
   }
  return "glpi_".preg_replace("/_id.*/", "", $fkname);
}


/**
 * Return ItemType  for a table
 *
 * @param $table string: table name
 *
 * @return string itemtype corresponding to a table name parameter
**/
function getItemTypeForTable($table) {
   global $CFG_GLPI;

   if (isset($CFG_GLPI['glpiitemtypetables'][$table])) {
      return $CFG_GLPI['glpiitemtypetables'][$table];

   } else {
      $inittable = $table;
      $table     = str_replace("glpi_", "", $table);
      $prefix    = "";

      if (preg_match('/^plugin_([a-z0-9]+)_/',$table,$matches)) {
         $table  = preg_replace('/^plugin_[a-z0-9]+_/','',$table);
         $prefix = "Plugin".ucfirst($matches[1]);
      }

      if (strstr($table,'_')) {
         $split = explode('_', $table);

         foreach ($split as $key => $part) {
            $split[$key] = ucfirst(getSingular($part));
         }
         $table = implode('_',$split);

      } else {
         $table = ucfirst(getSingular($table));
      }

      $itemtype=$prefix.$table;

      // Get real existence of itemtype
      if (class_exists($itemtype)) {
         $item     = new $itemtype();
         $itemtype = get_class($item);
         $CFG_GLPI['glpiitemtypetables'][$inittable] = $itemtype;
         $CFG_GLPI['glpitablesitemtype'][$itemtype]  = $inittable;
         return $itemtype;
      }
      return "UNKNOWN";
   }
}


/**
 * Return ItemType  for a table
 *
 * @param $itemtype string: itemtype
 *
 * @return string table name corresponding to the itemtype  parameter
**/
function getTableForItemType($itemtype) {
   global $CFG_GLPI;

   if (isset($CFG_GLPI['glpitablesitemtype'][$itemtype])) {
      return $CFG_GLPI['glpitablesitemtype'][$itemtype];

   } else {
      $prefix = "glpi_";

      if ($plug=isPluginItemType($itemtype)) {
         $prefix .= "plugin_".strtolower($plug['plugin'])."_";
         $table   = strtolower($plug['class']);
      } else {
         $table = strtolower($itemtype);
      }

      if (strstr($table,'_')) {
         $split = explode('_',$table);

         foreach ($split as $key => $part) {
            $split[$key] = getPlural($part);
         }
         $table = implode('_',$split);

      } else {
         $table = getPlural($table);
      }

      $CFG_GLPI['glpitablesitemtype'][$itemtype]      = $prefix.$table;
      $CFG_GLPI['glpiitemtypetables'][$prefix.$table] = $itemtype;
      return $prefix.$table;
   }
}


/**
 * Return the plural of a string
 *
 * @param $string string: input string
 *
 * @return string plural of the parameter string
**/
function getPlural($string) {

   $rules = array(//'singular' => 'plural'
                  '([^ae])y$' => '\1ies', // special case : category (but not key)
                  '([^s])$'   => '\1s',   // Add at the end if not exists
                  'eds$'      => 'ed');   // case table without plurial

   foreach ($rules as $singular => $plural) {
      $string = preg_replace("/$singular/", "$plural", $string);
   }
   return $string;
}


/**
 * Return the singular of a string
 *
 * @param $string string: input string
 *
 * @return string singular of the parameter string
**/
function getSingular($string) {

   $rules = array(//'plural' => 'singular'
                  'ies$' => 'y', // special case : category
                  's$'   => ''); // Add at the end if not exists

   foreach ($rules as  $plural => $singular) {
      $string = preg_replace("/$plural/", "$singular", $string);
   }
   return $string;
}


/**
 * Is a table used for devices
 *
 * @param $tablename table name
 *
 * @return bool
**/
function isDeviceTable($tablename) {

   // begin by glpi_devices but Not types tables (end = types)
   return (preg_match('/^glpi_devices', $tablename) && !preg_match('/types$', $tablename));
}


/**
 * Count the number of elements in a table.
 *
 * @param $table string: table name
 * @param $condition string: condition to use
 *
 * @return int nb of elements in table
**/
function countElementsInTable($table, $condition="") {
   global $DB;

   $query = "SELECT COUNT(*) AS cpt
             FROM `$table`";

   if (!empty($condition)) {
      $query .= " WHERE $condition ";
   }

   $result =$DB->query($query);
   $ligne  = $DB->fetch_array($result);
   return $ligne['cpt'];
}


/**
 * Count the number of elements in a table for a specific entity
 *
 * @param $table string: table name
 * @param $condition string: additional condition
 *
 * @return int nb of elements in table
**/
function countElementsInTableForMyEntities($table, $condition='') {

   /// TODO clean it / maybe include when review of SQL requests
   $itemtype = getItemTypeForTable($table);
   $item     = new $itemtype();

   if (!empty($condition)) {
      $condition .= " AND ";
   }

   $condition .= getEntitiesRestrictRequest("", $table, '', '', $item->maybeRecursive());
   return countElementsInTable($table, $condition);
}


/**
 * Count the number of elements in a table for a specific entity
 *
 * @param $table string: table name
 * @param $entity integer: the entity ID
 * @param $condition string: additional condition
 *
 * @return int nb of elements in table
**/
function countElementsInTableForEntity($table,$entity,$condition='') {

   /// TODO clean it / maybe include when review of SQL requests
   $itemtype = getItemTypeForTable($table);
   $item     = new $itemtype();

   if (!empty($condition)) {
      $condition .= " AND ";
   }

   $condition .= getEntitiesRestrictRequest("", $table, '', $entity,$item->maybeRecursive());
   return countElementsInTable($table, $condition);
}


/**
 * Get datas from a table in an array : CAUTION TO USE ONLY FOR SMALL TABLES OR USING A STRICT CONDITION
 *
 * @param $table string: table name
 * @param $condition string: condition to use
 * @param $usecache boolean
 *
 * @return array containing all the datas
**/
function getAllDatasFromTable($table, $condition="", $usecache=false) {
   global $DB;
   static $cache = array();

   if (empty($condition) && $usecache && isset($cache[$table])) {
      return $cache[$table];
   }

   $datas = array();
   $query = "SELECT *
             FROM `$table` ";

   if (!empty($condition)) {
      $query .= " WHERE $condition ";
   }

   if ($result=$DB->query($query)) {
      while ($data=$DB->fetch_assoc($result)) {
         $datas[$data['id']] = $data;
      }
   }

   if (empty($condition) && $usecache) {
      $cache[$table] = $datas;
   }
   return $datas;
}


/**
 * Get the Name of the element of a Dropdown Tree table
 *
 * @param $table string: Dropdown Tree table
 * @param $ID integer: ID of the element
 * @param $withcomment boolean: 1 if you want to give the array with the comments
 *
 * @return string : name of the element
 *
 * @see getTreeValueCompleteName
**/
function getTreeLeafValueName($table, $ID, $withcomment=false) {
   global $DB, $LANG;

   $name    = "";
   $comment = "";

   if ($ID==0 && $table=="glpi_entities") {
      $name = $LANG['entity'][2];

   } else {
      $query = "SELECT *
                FROM `$table`
                WHERE `id` = '$ID'";

      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)==1) {
            $name = $DB->result($result, 0, "name");
            $comment=$DB->result($result, 0, "comment");
         }
      }
   }

   if ($withcomment) {
      return array("name"    => $name,
                   "comment" => $comment);
   }
   return $name;
}


/**
 * Get completename of a Dropdown Tree table
 *
 * @param $table string: Dropdown Tree table
 * @param $ID integer: ID of the element
 * @param $withcomment boolean: 1 if you want to give the array with the comments
 *
 * @return string : completename of the element
 *
 * @see getTreeLeafValueName
**/
function getTreeValueCompleteName($table, $ID, $withcomment=false) {
   global $DB, $LANG;

   $name    = "";
   $comment = "";

   if ($ID==0 && $table=="glpi_entities") {
      $name = $LANG['entity'][2];

   } else {
      $query = "SELECT *
                FROM `$table`
                WHERE `id` = '$ID'";

      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)==1) {
            $name    = $DB->result($result,0,"completename");
            $comment = $name." :<br>".$DB->result($result ,0, "comment");
         }
      }
   }

   if (empty($name)) {
      $name = "&nbsp;";
   }

   if ($withcomment) {
      return array("name"    => $name,
                   "comment" => $comment);
   }
   return $name;
}


/**
 * show name category
 * DO NOT DELETE THIS FUNCTION : USED IN THE UPDATE
 *
 * @param $table string: table name
 * @param $ID integer: value ID
 * @param $wholename string : current name to complete (use for recursivity)
 * @param $level integer: current level of recursion
 *
 * @return string name
**/
function getTreeValueName($table, $ID, $wholename="", $level=0) {
   global $DB, $LANG;

   $parentIDfield = getForeignKeyFieldForTable($table);

   $query = "SELECT *
             FROM `$table`
             WHERE `id` = '$ID'";
   $name = "";

   if ($result=$DB->query($query)) {
      if ($DB->numrows($result)>0) {
         $row      = $DB->fetch_array($result);
         $parentID = $row[$parentIDfield];

         if ($wholename == "") {
            $name = $row["name"];
         } else {
            $name = $row["name"] . " > ";
         }

         $level++;
         list($tmpname, $level) = getTreeValueName($table, $parentID, $name, $level);
         $name = $tmpname. $name;
      }
   }
   return array($name, $level);
}


/**
 * Get the ancestors of an item in a tree dropdown
 *
 * @param $table string: table name
 * @param $items_id integer: The ID of the item
 *
 * @return array of IDs of the ancestors
**/
function getAncestorsOf($table, $items_id) {
   global $DB;

   // IDs to be present in the final array
   $id_found      = array();
   $parentIDfield = getForeignKeyFieldForTable($table);
   $use_cache     = FieldExists($table, "ancestors_cache");

   if ($use_cache) {
      $query = "SELECT `ancestors_cache`, `$parentIDfield`
                FROM `$table`
                WHERE `id` = '$items_id'";

      if (($result=$DB->query($query)) && ($DB->numrows($result)>0)) {
         $ancestors = trim($DB->result($result, 0, 0));
         $parent    = $DB->result($result, 0, 1);

         // Return datas from cache in DB
         if (!empty($ancestors)) {
            return importArrayFromDB($ancestors, true);
         }

         // Recursive solution for table with-cache
         if ($parent>0) {
            $id_found = getAncestorsOf($table, $parent);
         }

         // ID=0 only exists for Entities
         if ($parent>0 || $table=='glpi_entities') {
            $id_found[$parent] = $parent;
         }

         // Store cache datas in DB
         $query = "UPDATE `$table`
                   SET `ancestors_cache` = '".exportArrayToDB($id_found)."'
                   WHERE `id` = '$items_id'";
         $DB->query($query);
      }

      return $id_found;
   }

   // Get the leafs of previous founded item
   // iterative solution for table without cache
   $IDf = $items_id;
   while ($IDf>0) {
      // Get next elements
      $query = "SELECT `$parentIDfield`
                FROM `$table`
                WHERE `id` = '$IDf'";

      $result = $DB->query($query);
      if ($DB->numrows($result)>0) {
         $IDf  =$DB->result($result,0,0);
      } else {
         $IDf = 0;
      }

      if (!isset($id_found[$IDf]) && ($IDf>0 || $table=='glpi_entities')) {
         $id_found[$IDf] = $IDf;
      } else {
         $IDf = 0;
      }
   }

   return $id_found;
}


/**
 * Get the sons of an item in a tree dropdown. Get datas in cache if available
 *
 * @param $table string: table name
 * @param $IDf integer: The ID of the father
 *
 * @return array of IDs of the sons
**/
function getSonsOf($table,$IDf) {
   global $DB;

   $parentIDfield = getForeignKeyFieldForTable($table);
   $use_cache     = FieldExists($table, "sons_cache");

   if ($use_cache) {
      $query = "SELECT `sons_cache`
                FROM `$table`
                WHERE `id` = '$IDf'";

      if (($result=$DB->query($query)) && ($DB->numrows($result)>0)) {
         $sons = trim($DB->result($result, 0, 0));
         if (!empty($sons)) {
            return importArrayFromDB($sons, true);
         }
      }
   }

   // IDs to be present in the final array
   $id_found[$IDf] = $IDf;
   // current ID found to be added
   $found = array();
   // First request init the  varriables
   $query = "SELECT `id`
             FROM `$table`
             WHERE `$parentIDfield` = '$IDf'
             ORDER BY `name`";

   if (($result=$DB->query($query)) && ($DB->numrows($result)>0)) {
      while ($row=$DB->fetch_array($result)) {
         $id_found[$row['id']] = $row['id'];
         $found[$row['id']]    = $row['id'];
      }
   }

   // Get the leafs of previous founded item
   while (count($found)>0) {
      $first = true;
      // Get next elements
      $query = "SELECT `id`
                FROM `$table`
                WHERE `$parentIDfield` IN ('" . implode("','",$found) . "')";

      // CLear the found array
      unset($found);
      $found = array();

      $result = $DB->query($query);
      if ($DB->numrows($result)>0) {
         while ($row=$DB->fetch_array($result)) {
            if (!isset($id_found[$row['id']])) {
               $id_found[$row['id']] = $row['id'];
               $found[$row['id']]    = $row['id'];
            }
         }
      }
   }

   // Store cache datas in DB
   if ($use_cache) {
      $query = "UPDATE `$table`
                SET `sons_cache`='".exportArrayToDB($id_found)."'
                WHERE `id` = '$IDf';";
      $DB->query($query);
   }

   return $id_found;
}


/**
 * Get the sons of an item in a tree dropdown
 *
 * @param $table string: table name
 * @param $IDf integer: The ID of the father
 *
 * @return array of IDs of the sons
**/
function getTreeForItem($table,$IDf) {
   global $DB;

   $parentIDfield = getForeignKeyFieldForTable($table);

   // IDs to be present in the final array
   $id_found = array();
   // current ID found to be added
   $found = array();

   // First request init the  varriables
   $query = "SELECT *
             FROM `$table`
             WHERE `$parentIDfield` = '$IDf'
             ORDER BY `name`";

   if (($result=$DB->query($query)) && ($DB->numrows($result)>0)) {
      while ($row=$DB->fetch_array($result)) {
         $id_found[$row['id']]['parent'] = $IDf;
         $id_found[$row['id']]['name']   = $row['name'];
         $found[$row['id']]              = $row['id'];
      }
   }

   // Get the leafs of previous founded item
   while (count($found)>0) {
      $first = true;
      // Get next elements
      $query = "SELECT *
                FROM `$table`
                WHERE `$parentIDfield` IN ('" . implode("','",$found)."')
                ORDER BY `name`";
      // CLear the found array
      unset($found);
      $found = array();

      $result = $DB->query($query);
      if ($DB->numrows($result)>0) {
         while ($row=$DB->fetch_array($result)) {
            if (!isset($id_found[$row['id']])) {
               $id_found[$row['id']]['parent'] = $row[$parentIDfield];
               $id_found[$row['id']]['name']   = $row['name'];
               $found[$row['id']]              = $row['id'];
            }
         }
      }

   }
   $tree[$IDf]['name'] = Dropdown::getDropdownName($table, $IDf);
   $tree[$IDf]['tree'] = contructTreeFromList($id_found, $IDf);
   return $tree;
}


/**
 * Construct a tree from a list structure
 *
 * @param $list array: the list
 * @param $root integer: root of the tree
 *
 * @return list of items in the tree
**/
function contructTreeFromList($list, $root) {

   $tree = array();
   foreach ($list as $ID => $data) {
      if ($data['parent']==$root) {
         unset($list[$ID]);
         $tree[$ID]['name'] = $data['name'];
         $tree[$ID]['tree'] = contructTreeFromList($list, $ID);
      }
   }
   return $tree;
}


/**
 * Construct a list from a tree structure
 *
 * @param $tree array: the tree
 * @param $parent integer: root of the tree
 *
 * @return list of items in the tree
**/
function contructListFromTree($tree, $parent=0) {

   $list = array();
   foreach ($tree as $root => $data) {
      $list[$root] = $parent;

      if (is_array($data['tree']) && count($data['tree'])) {
         foreach ($data['tree'] as $ID => $underdata) {
            $list[$ID] = $root;

            if (is_array($underdata['tree'])&&count($underdata['tree'])) {
               $list += contructListFromTree($underdata['tree'], $ID);
            }

         }
      }

   }
   return $list;
}


/**
 * Get the equivalent search query using ID of soons that the search of the father's ID argument
 *
 * @param $table string: table name
 * @param $IDf integer: The ID of the father
 * @param $reallink string: real field to link ($table.id if not set)
 *
 * @return string the query
**/
function getRealQueryForTreeItem($table, $IDf, $reallink="") {
   global $DB;

   if (empty($IDf)) {
      return "";
   }

   if (empty($reallink)) {
      $reallink = $table.".id";
   }
   $id_found = getSonsOf($table, $IDf);

   // Construct the final request
   if (count($id_found)>0) {
      $ret = " ( ";
      $i   = 0;

      foreach ($id_found as $key => $val) {
         if ($i>0) {
            $ret .= " OR ";
         }
         $ret .= "$reallink = '$val' ";
         $i++;
      }
      $ret .= ") ";
      return $ret;
   }

   return " ( $reallink = '$IDf') ";
}


/**
 * Compute all completenames of Dropdown Tree table
 *
 * @param $table : dropdown tree table to compute
 *
 * @return nothing
**/
function regenerateTreeCompleteName($table) {
   global $DB;

   $query = "SELECT `id`
             FROM `$table`";

   $result = $DB->query($query);
   if ($DB->numrows($result)>0) {
      while ($data=$DB->fetch_array($result)) {
         list($name, $level) = getTreeValueName($table, $data['id']);

         $query = "UPDATE `$table`
                   SET `completename` = '".addslashes($name)."'
                   WHERE `id` = '".$data['id']."'";
         $DB->query($query);
      }
   }
}


/**
 * Compute completename of Dropdown Tree table under the element of ID $ID
 *
 * @param $table : dropdown tree table to compute
 * @param $ID : root ID to used : regenerate all under this element
 *
 * @return nothing
**/
function regenerateTreeCompleteNameUnderID($table, $ID) {
   global $DB;

   $parentIDfield      = getForeignKeyFieldForTable($table);
   list($name, $level) = getTreeValueName($table, $ID);

   $query = "UPDATE `$table`
             SET `completename` = '".addslashes($name)."',
                 `level` = '$level'
             WHERE `id` = '$ID'";
   $DB->query($query);

   $query = "SELECT `id`
             FROM `$table`
             WHERE `$parentIDfield` = '$ID'";
   $result = $DB->query($query);

   if ($DB->numrows($result)>0) {
      while ($data=$DB->fetch_array($result)) {
         regenerateTreeCompleteNameUnderID($table, $data["id"]);
      }
   }
}


/**
 * Get the ID of the next Item
 *
 * @param $table table to search next item
 * @param $ID current ID
 * @param $condition condition to add to the search
 * @param $nextprev_item field used to sort
 *
 * @return the next ID, -1 if not exist
**/
function getNextItem($table, $ID, $condition="", $nextprev_item="name") {
   global $DB, $CFG_GLPI;

   if (empty($nextprev_item)) {
      return false;
   }

   $itemtype = getItemTypeForTable($table);
   $item     = new $itemtype();
   $search   = $ID;

   if ($nextprev_item!="id") {
      $query = "SELECT `$nextprev_item`
                FROM `$table`
                WHERE `id` = '$ID'";

      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)>0) {
            $search = addslashes($DB->result($result, 0, 0));
         } else {
            $nextprev_item = "id";
         }
      }
   }

   $LEFTJOIN = '';
   if ($table=="glpi_users") {
      $LEFTJOIN = " LEFT JOIN `glpi_profiles_users`
                           ON (`glpi_users`.`id` = `glpi_profiles_users`.`users_id`)";
   }

   $query = "SELECT `$table`.`id`
             FROM `$table`
             $LEFTJOIN
             WHERE (`$table`.`$nextprev_item` > '$search' ";

   // Same name case
   if ($nextprev_item!="id") {
      $query .= " OR (`$table`.`".$nextprev_item."` = '$search'
                      AND `$table`.`id` > '$ID') ";
   }
   $query .= ") ";

   if (!empty($condition)) {
      $query .= " AND $condition ";
   }

   if ($item->maybeDeleted()) {
      $query .= " AND `$table`.`is_deleted` = '0' ";
   }

   if ($item->maybeTemplate()) {
      $query .= " AND `$table`.`is_template` = '0' ";
   }

   // Restrict to active entities
   if ($table=="glpi_entities") {
      $query .= getEntitiesRestrictRequest("AND", $table, '', '', true);

   } else if ($item->isEntityAssign()) {
      $query .= getEntitiesRestrictRequest("AND", $table, '', '', $item->maybeRecursive());

   } else if ($table=="glpi_users") {
      $query .= getEntitiesRestrictRequest("AND", "glpi_profiles_users");
   }

   $query .= " ORDER BY `$table`.`$nextprev_item` ASC,
                        `$table`.`id` ASC";

   $result = $DB->query($query);
   if ($result && $DB->numrows($result)>0) {
      return $DB->result($result, 0, "id");
   }

   return -1;
}


/**
 * Get the ID of the previous Item
 *
 * @param $table table to search next item
 * @param $ID current ID
 * @param $condition condition to add to the search
 * @param $nextprev_item field used to sort
 *
 * @return the previous ID, -1 if not exist
**/
function getPreviousItem($table, $ID, $condition="", $nextprev_item="name") {
   global $DB, $CFG_GLPI;

   if (empty($nextprev_item)) {
      return false;
   }

   $itemtype = getItemTypeForTable($table);
   $item     = new $itemtype();
   $search   = $ID;

   if ($nextprev_item!="id") {
      $query = "SELECT `$nextprev_item`
                FROM `$table`
                WHERE `id` = '$ID'";

      $result = $DB->query($query);
      if ($DB->numrows($result)>0) {
         $search = addslashes($DB->result($result, 0, 0));
      } else {
         $nextprev_item = "id";
      }
   }

   $LEFTJOIN = '';
   if ($table=="glpi_users") {
      $LEFTJOIN = " LEFT JOIN `glpi_profiles_users`
                           ON (`glpi_users`.`id` = `glpi_profiles_users`.`users_id`)";
   }

   $query = "SELECT `$table`.`id`
             FROM `$table`
             $LEFTJOIN
             WHERE (`$table`.`$nextprev_item` < '$search' ";

   // Same name case
   if ($nextprev_item!="id") {
      $query .= " OR (`$table`.`$nextprev_item` = '$search'
                      AND `$table`.`id` < '$ID') ";
   }
   $query .= ") ";

   if (!empty($condition)) {
      $query .= " AND $condition ";
   }

   if ($item->maybeDeleted()) {
      $query .= "AND `$table`.`is_deleted` = '0'";
   }

   if ($item->maybeTemplate()) {
      $query .= "AND `$table`.`is_template` = '0'";
   }

   // Restrict to active entities
   if ($table=="glpi_entities") {
      $query .= getEntitiesRestrictRequest("AND", $table, '', '', true);

   } else if ($item->isEntityAssign()) {
      $query .= getEntitiesRestrictRequest("AND", $table, '', '', $item->maybeRecursive());

   } else if ($table=="glpi_users") {
      $query .= getEntitiesRestrictRequest("AND", "glpi_profiles_users");
   }

   $query .= " ORDER BY `$table`.`$nextprev_item` DESC,
                        `$table`.`id` DESC";

   $result = $DB->query($query);
   if ($result&&$DB->numrows($result)>0) {
      return $DB->result($result, 0, "id");
   }

   return -1;
}


/**
 * Format a user name
 *
 *@param $ID int : ID of the user.
 *@param $login string : login of the user
 *@param $realname string : realname of the user
 *@param $firstname string : firstname of the user
 *@param $link int : include link (only if $link==1)
 *@param $cut int : limit string length (0 = no limit)
 *@param $force_config boolean : force order and id_visible to use common config
 *
 *@return string : formatted username
**/
function formatUserName($ID, $login, $realname, $firstname, $link=0, $cut=0, $force_config=false) {
   global $CFG_GLPI;

   $before = "";
   $after  = "";
   $viewID = "";

   $order = $CFG_GLPI["names_format"];
   if (isset($_SESSION["glpinames_format"]) && !$force_config ) {
      $order = $_SESSION["glpinames_format"];
   }

   $id_visible = $CFG_GLPI["is_ids_visible"];
   if (isset($_SESSION["glpiis_ids_visible"]) && !$force_config ) {
      $id_visible = $_SESSION["glpiis_ids_visible"];
   }


   if (strlen($realname)>0) {
      $temp = $realname;

      if (strlen($firstname)>0) {
         if ($order==FIRSTNAME_BEFORE) {
            $temp = $firstname." ".$temp;
         } else {
            $temp .= " ".$firstname;
         }
      }

      if ($cut>0 && utf8_strlen($temp)>$cut) {
         $temp = utf8_substr($temp, 0, $cut)." ...";
      }

   } else {
      $temp = $login;
   }

   if ($ID>0
       && (strlen($temp)==0 || $id_visible)) {
      $viewID = "&nbsp;($ID)";
   }

   if ($link==1&&$ID>0) {
      $before = "<a title=\"".$temp."\" href='".$CFG_GLPI["root_doc"]."/front/user.form.php?id=".$ID."'>";
      $after  = "</a>";
   }

   $username = $before.$temp.$viewID.$after;
   return $username;
}


/**
 * Get name of the user with ID=$ID (optional with link to user.form.php)
 *
 *@param $ID int : ID of the user.
 *@param $link int : 1 = Show link to user.form.php 2 = return array with comments and link
 *
 *@return string : username string (realname if not empty and name if realname is empty).
**/
function getUserName($ID, $link=0) {
   global $DB, $CFG_GLPI, $LANG;

   $user = "";
   if ($link==2) {
      $user = array("name"    => "",
                    "link"    => "",
                    "comment" => "");
   }

   if ($ID) {
      $query = "SELECT *
                FROM `glpi_users`
                WHERE `id` = '$ID'";
      $result = $DB->query($query);

      if ($link==2) {
         $user = array("name"    => "",
                       "comment" => "",
                       "link"    => "");
      }

      if ($DB->numrows($result)==1) {
         $data     = $DB->fetch_assoc($result);
         $username = formatUserName($data["id"], $data["name"], $data["realname"],
                                    $data["firstname"], $link);

         if ($link==2) {
            $user["name"]    = $username;
            $user["link"]    = $CFG_GLPI["root_doc"]."/front/user.form.php?id=".$ID;
            $user["comment"] = $LANG['common'][16]."&nbsp;: ".$username."<br>".$LANG['setup'][18].
                               "&nbsp;: ".$data["name"]."<br>";

            if (!empty($data["email"])) {
               $user["comment"] .= $LANG['setup'][14]."&nbsp;: ".$data["email"]."<br>";
            }

            if (!empty($data["phone"])) {
               $user["comment"] .= $LANG['help'][35]."&nbsp;: ".$data["phone"]."<br>";
            }

            if (!empty($data["mobile"])) {
               $user["comment"] .= $LANG['common'][42]."&nbsp;: ".$data["mobile"]."<br>";
            }

            if ($data["locations_id"]>0) {
               $user["comment"] .= $LANG['common'][15]."&nbsp;: ".
                                   Dropdown::getDropdownName("glpi_locations",
                                                             $data["locations_id"])."<br>";
            }

            if ($data["usertitles_id"]>0) {
               $user["comment"] .= $LANG['users'][1]."&nbsp;: ".
                                   Dropdown::getDropdownName("glpi_usertitles",
                                                             $data["usertitles_id"])."<br>";
            }

            if ($data["usercategories_id"]>0) {
               $user["comment"] .= $LANG['users'][2]."&nbsp;: ".
                                   Dropdown::getDropdownName("glpi_usercategories",
                                                             $data["usercategories_id"])."<br>";
            }

         } else {
            $user = $username;
         }
      }
   }
   return $user;
}


/**
 * Verify if a DB table exists
 *
 *@param $tablename string : Name of the table we want to verify.
 *
 *@return bool : true if exists, false elseway.
**/
function TableExists($tablename) {
   global $DB;

   // Get a list of tables contained within the database.
   $result = $DB->list_tables("%".$tablename."%");

   if ($rcount = $DB->numrows($result)) {
      while ($data=$DB->fetch_row($result)) {
         if ($data[0]===$tablename) {
            return true;
         }
      }
   }

   $DB->free_result($result);
   return false;
}


/**
 * Verify if a DB field exists
 *
 *@param $table string : Name of the table we want to verify.
 *@param $field string : Name of the field we want to verify.
 *
 *@return bool : true if exists, false elseway.
**/
function FieldExists($table, $field) {
   global $DB;

   if ($fields = $DB->list_fields($table)) {
      if (isset($fields[$field])) {
         return true;
      }
      return false;
   }
   return false;
}


/**
 * Determine if an index exists in database
 *
 * @param $table string : table of the index
 * @param $field string : name of the index
 *
 * @return boolean : index exists ?
**/
function isIndex($table, $field) {
   global $DB;

   $result = $DB->query("SHOW INDEX FROM `$table`");

   if ($result && $DB->numrows($result)) {
      while ($data=$DB->fetch_assoc($result)) {
         if ($data["Key_name"]==$field) {
            return true;
         }
      }
   }
   return false;
}


/**
 * Create a new name using a autoname field defined in a template
 *
 * @param $objectName autoname template
 * @param $field field to autoname
 * @param $isTemplate true if create an object from a template
 * @param $itemtype item type
 * @param $entities_id limit generation to an entity
 *
 * @return new auto string
**/
function autoName($objectName, $field, $isTemplate, $itemtype, $entities_id=-1) {
   global $DB, $CFG_GLPI;

   $len = utf8_strlen($objectName);

   if ($isTemplate
       && $len > 8
       && utf8_substr($objectName,0,4) === '&lt;'
       && utf8_substr($objectName,$len - 4,4) === '&gt;') {

      $autoNum = utf8_substr($objectName, 4, $len - 8);
      $mask    = '';

      if (preg_match( "/\\#{1,10}/", $autoNum, $mask)) {
         $global  = strpos($autoNum, '\\g') !== false && $itemtype != 'Infocom' ? 1 : 0;
         $autoNum = str_replace(array('\\y',
                                      '\\Y',
                                      '\\m',
                                      '\\d',
                                      '_','%',
                                      '\\g'),
                                array(date('y'),
                                      date('Y'),
                                      date('m'),
                                      date('d'),
                                      '\\_',
                                      '\\%',
                                      ''),
                                $autoNum);
         $mask = $mask[0];
         $pos  = strpos($autoNum, $mask) + 1;
         $len  = utf8_strlen($mask);
         $like = str_replace('#', '_', $autoNum);

         if ($global == 1) {
            $query = "";
            $first = 1;
            $types = array('Computer', 'Monitor', 'NetworkEquipment', 'Peripheral', 'Phone',
                           'Printer');

            foreach ($types as $t) {
               $query .= ($first ? "SELECT " : " UNION SELECT  ")." $field AS code
                         FROM `$table`
                         WHERE `$field` LIKE '$like'
                               AND `is_deleted` = '0'
                               AND `is_template` = '0'";

               if ($CFG_GLPI["use_autoname_by_entity"] && $entities_id>=0) {
                  $query .=" AND `entities_id` = '$entities_id' ";
               }

               $first = 0;
            }

            $query = "SELECT CAST(SUBSTRING(code, $pos, $len) AS unsigned) AS no
                      FROM ($query) AS codes";

         } else {
            $table = getTableForItemType($itemtype);
            $query = "SELECT CAST(SUBSTRING($field, $pos, $len) AS unsigned) AS no
                      FROM `$table`
                      WHERE `$field` LIKE '$like' ";

            if ($itemtype != INFOCOM_TYPE) {
               $query .= " AND `is_deleted` = '0'
                           AND `is_template` = '0'";

               if ($CFG_GLPI["use_autoname_by_entity"] && $entities_id>=0) {
                  $query .= " AND `entities_id` = '$entities_id' ";
               }
            }
         }

         $query = "SELECT MAX(Num.no) AS lastNo
                   FROM (".$query.") AS Num";
         $resultNo = $DB->query($query);

         if ($DB->numrows($resultNo)>0) {
            $data  = $DB->fetch_array($resultNo);
            $newNo = $data['lastNo'] + 1;
         } else {
            $newNo = 0;
         }
         $objectName = str_replace(array($mask,
                                         '\\_',
                                         '\\%'),
                                   array(utf8_str_pad($newNo, $len, '0', STR_PAD_LEFT),
                                         '_',
                                         '%'),
                                   $autoNum);
      }
   }
   return $objectName;
}


/**
 * Close active DB connections
 *
 *@return nothing
**/
function closeDBConnections() {
   global $DB, $DBocs;

   // Case of not init $DB object
   if (method_exists($DB,"close")) {
      $DB->close();
      if (isset($DBocs) && method_exists($DBocs,"close")) {
         $DBocs->close();
      }
   }
}


/**
 * Format a web link adding http:// if missing
 *
 *@param $link link to format
 *
 *@return formatted link.
**/
function formatOutputWebLink($link) {

   if (!preg_match("/^https?/",$link)) {
      return "http://".$link;
   }
   return $link;
}


/**
* Clean fields if needed
*
* @param $table table name name
* @param $fields fields to set NULL : may be a string or an array (sons_cache, ancestors_cache, ...)
**/
function CleanFields($table,$fields) {
   global $DB;

   if (!is_array($fields)) {
      $fields = array($fields);
   }

   $query = '';
   foreach ($fields as $field) {
      if (FieldExists($table,$field)) {
         $query .= (empty($query)?"UPDATE `$table` SET" : ",")." `$field` = NULL ";

      }
   }

   if (!empty($query)) {
      $DB->query($query);
   }
}


/**
 * Add dates for request
 *
 * @param $field : table.field to request
 * @param $begin date : begin date
 * @param $end date : end date
 *
 * @return sql
**/
function getDateRequest($field,$begin, $end) {

   $sql = '';
   if (!empty($begin)) {
      $sql .= " $field >= '$begin' ";
   }

   if (!empty($end)) {
      if (!empty($sql)) {
         $sql .= " AND ";
      }
      $sql .= " $field <= ADDDATE('$end' , INTERVAL 1 DAY) ";
   }
   return " (".$sql.") ";
}


/**
 * Export an array to be stored in a simple field in the database
 *
 * @param $TAB Array to export / encode (one level depth)
 *
 * @return string containing encoded array
**/
function exportArrayToDB($TAB) {
   return json_encode($TAB);
}


/**
 * Import an array encoded in a simple field in the database
 *
 * @param $DATA data readed in DB to import
 *
 * @return array containing datas
**/
function importArrayFromDB($DATA) {

   $TAB = json_decode($DATA,true);

   // Use olf scheme to decode
   if (!is_array($TAB)) {
      $TAB = array();

      foreach (explode(" ", $DATA) as $ITEM) {
         $A = explode("=>", $ITEM);

         if (strlen($A[0])>0 && isset($A[1])) {
            $TAB[urldecode($A[0])] = urldecode($A[1]);
         }
      }
   }
   return $TAB;
}

?>
