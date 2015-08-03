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

/**
 * Return foreign key field name for a table
 *
 * @param $table string table name
 *
 * @return string field name used for a foreign key to the parameter table
**/
function getForeignKeyFieldForTable($table) {

   if (strpos($table,'glpi_') === false) {
      return "";
   }
   return str_replace("glpi_","",$table)."_id";
}


/**
 * Check if field is a foreign key field
 *
 * @since version 0.84
 *
 * @param $field string field name
 *
 * @return string field name used for a foreign key to the parameter table
**/
function isForeignKeyField($field) {

   // No _id drop
   if (strpos($field,'_id') === false) {
      return false;
   }

   return preg_match("/_id$/", $field) || preg_match("/_id_/", $field);
}


/**
 * Return foreign key field name for an itemtype
 *
 * @param $itemtype string itemtype
 *
 * @return string field name used for a foreign key to the parameter itemtype
**/
function getForeignKeyFieldForItemType($itemtype) {
   return getForeignKeyFieldForTable(getTableForItemType($itemtype));
}


/**
 * Return table name for a given foreign key name
 *
 * @param $fkname   string   foreign key name
 *
 * @return string table name corresponding to a foreign key name
**/
function getTableNameForForeignKeyField($fkname) {

   if (strpos($fkname,'_id') === false) {
      return "";
   }
   // If $fkname begin with _ strip it
   if ($fkname[0] == '_') {
      $fkname = substr($fkname, 1);
   }

   return "glpi_".preg_replace("/_id.*/", "", $fkname);
}


/**
 * Return ItemType  for a table
 *
 * @param $table string table name
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
         $prefix = "Plugin".Toolbox::ucfirst($matches[1]);
      }

      if (strstr($table,'_')) {
         $split = explode('_', $table);

         foreach ($split as $key => $part) {
            $split[$key] = Toolbox::ucfirst(getSingular($part));
         }
         $table = implode('_',$split);

      } else {
         $table = Toolbox::ucfirst(getSingular($table));
      }

      $itemtype = $prefix.$table;
      // Get real existence of itemtype
      if ($item = getItemForItemtype($itemtype)) {
         $itemtype                                   = get_class($item);
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
 * @param $itemtype   string   itemtype
 *
 * @return string table name corresponding to the itemtype  parameter
**/
function getTableForItemType($itemtype) {
   global $CFG_GLPI;

   // Force singular for itemtype : States case
   $itemtype = getSingular($itemtype);

   if (isset($CFG_GLPI['glpitablesitemtype'][$itemtype])) {
      return $CFG_GLPI['glpitablesitemtype'][$itemtype];

   } else {
      $prefix = "glpi_";

      if ($plug = isPluginItemType($itemtype)) {
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
 * Get new item objet for an itemtype
 *
 * @since version 0.83
 *
 * @param $itemtype   string   itemtype
 *
 * @return itemtype object or false if class does not exists
**/
function getItemForItemtype($itemtype) {

   if (class_exists($itemtype)) {
      return new $itemtype();
   }
   return false;
}


/**
 * Return the plural of a string
 *
 * @param $string   string   input string
 *
 * @return string plural of the parameter string
**/
function getPlural($string) {

   $rules = array(//'singular' => 'plural'
                  'criterias$'         =>'criterias',// Special case (criterias) when getPLural is called on already plural form
                  'ch$'                =>'ches',
                  'ches$'              =>'ches',
                  'sh$'                =>'shes',
                  'shes$'              =>'shes',
                  'sses$'              => 'sses', // Case like addresses
                  'ss$'                => 'sses', // Special case (addresses) when getSingular is called on already singular form
                  'uses$'              => 'uses', // Case like statuses
                  'us$'                => 'uses', // Case like status
                  '([^aeiou])y$'       => '\1ies', // special case : category (but not key)
                  '([^aeiou])ies$'     => '\1ies', // special case : category (but not key)
                  '([aeiou]{2})ses$'   => '\1ses', // Case like aliases
                  '([aeiou]{2})s$'     => '\1ses', // Case like aliases
                  'x$'                 =>'xes',
//                   's$'           =>'ses',
                  '([^s])$'            => '\1s',   // Add at the end if not exists
                  );

   foreach ($rules as $singular => $plural) {
      $string = preg_replace("/$singular/", "$plural", $string, -1, $count);
      if ($count > 0) {
         break;
      }
   }
   return $string;
}


/**
 * Return the singular of a string
 *
 * @param $string   string   input string
 *
 * @return string singular of the parameter string
**/
function getSingular($string) {

   $rules = array(//'plural' => 'singular'
                  'ches$'             => 'ch',
                  'ch$'               => 'ch',
                  'shes$'             => 'sh',
                  'sh$'               => 'sh',
                  'sses$'             => 'ss', // Case like addresses
                  'ss$'               => 'ss', // Special case (addresses) when getSingular is called on already singular form
                  'uses$'             => 'us', // Case like statuses
                  'us$'               => 'us', // Case like status
                  '([aeiou]{2})ses$'  => '\1s', // Case like aliases
                  'lias$'             => 'lias', // Special case (aliases) when getSingular is called on already singular form
                  '([^aeiou])ies$'    => '\1y', // special case : category
                  '([^aeiou])y$'      => '\1y', // special case : category
                  'xes$'              =>'x',
                  's$'                => ''); // Add at the end if not exists

   foreach ($rules as  $plural => $singular) {
      $string = preg_replace("/$plural/", "$singular", $string, -1, $count);
      if ($count > 0) {
         break;
      }
   }
   return $string;
}


/**
 * Count the number of elements in a table.
 *
 * @param $table        string/array   table names
 * @param $condition    string         condition to use (default '')
 *
 * @return int nb of elements in table
**/
function countElementsInTable($table, $condition="") {
   global $DB;

   if (is_array($table)) {
      $table = implode('`,`',$table);
   }

   $query = "SELECT COUNT(*) AS cpt
             FROM `$table`";

   if (!empty($condition)) {
      $query .= " WHERE $condition ";
   }

   $result = $DB->query($query);
   $ligne  = $DB->fetch_assoc($result);
   return $ligne['cpt'];
}

/**
 * Count the number of elements in a table.
 *
 * @param $table        string/array   table names
 * @param $field        string         field name
 * @param $condition    string         condition to use (default '')
 *
 * @return int nb of elements in table
**/
function countDistinctElementsInTable($table, $field, $condition="") {
   global $DB;

   if (is_array($table)) {
      $table = implode('`,`',$table);
   }

   $query = "SELECT COUNT(DISTINCT `$field`) AS cpt
             FROM `$table`";

   if (!empty($condition)) {
      $query .= " WHERE $condition ";
   }

   $result = $DB->query($query);
   $ligne  = $DB->fetch_assoc($result);
   return $ligne['cpt'];
}



/**
 * Count the number of elements in a table for a specific entity
 *
 * @param $table        string   table name
 * @param $condition    string   additional condition (default '')
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
 * @param $table        string   table name
 * @param $entity       integer  the entity ID
 * @param $condition    string   additional condition (default '')
 *
 * @return int nb of elements in table
**/
function countElementsInTableForEntity($table, $entity, $condition='') {

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
 * Get datas from a table in an array :
 * CAUTION TO USE ONLY FOR SMALL TABLES OR USING A STRICT CONDITION
 *
 * @param $table        string   table name
 * @param $condition    string   condition to use (default '')
 * @param $usecache     boolean  (false by default)
 * @param $order        string   result order (default '')
 *
 * @return array containing all the datas
**/
function getAllDatasFromTable($table, $condition='', $usecache=false, $order='') {
   global $DB;

   static $cache = array();

   if (empty($condition) && empty($order) && $usecache && isset($cache[$table])) {
      return $cache[$table];
   }

   $datas = array();
   $query = "SELECT *
             FROM `$table` ";

   if (!empty($condition)) {
      $query .= " WHERE $condition ";
   }
   if (!empty($order)) {
      $query .= " ORDER BY $order ";
   }

   if ($result = $DB->query($query)) {
      while ($data = $DB->fetch_assoc($result)) {
         $datas[$data['id']] = $data;
      }
   }

   if (empty($condition) && empty($order) && $usecache) {
      $cache[$table] = $datas;
   }
   return $datas;
}


/**
 * Get the Name of the element of a Dropdown Tree table
 *
 * @param $table        string   Dropdown Tree table
 * @param $ID           integer  ID of the element
 * @param $withcomment  boolean  1 if you want to give the array with the comments (false by default)
 * @param $translate    boolean  (true by default)
 *
 * @return string : name of the element
 *
 * @see getTreeValueCompleteName
**/
function getTreeLeafValueName($table, $ID, $withcomment=false, $translate=true) {
   global $DB;

   $name    = "";
   $comment = "";

   $SELECTNAME    = "`$table`.`name`, '' AS transname";
   $SELECTCOMMENT = "`$table`.`comment`, '' AS transcomment";
   $JOIN          = '';
   if  ($translate) {
      if (Session::haveTranslations(getItemTypeForTable($table), 'name')) {
         $SELECTNAME  = "`$table`.`name`, `namet`.`value` AS transname";
         $JOIN       .= " LEFT JOIN `glpi_dropdowntranslations` AS namet
                           ON (`namet`.`itemtype` = '".getItemTypeForTable($table)."'
                               AND `namet`.`items_id` = `$table`.`id`
                               AND `namet`.`language` = '".$_SESSION['glpilanguage']."'
                               AND `namet`.`field` = 'name')";
      }
      if (Session::haveTranslations(getItemTypeForTable($table), 'comment')) {
         $SELECTCOMMENT  = "`$table`.`comment`, `namec`.`value` AS transcomment";
         $JOIN          .= " LEFT JOIN `glpi_dropdowntranslations` AS namet
                           ON (`namec`.`itemtype` = '".getItemTypeForTable($table)."'
                               AND `namec`.`items_id` = `$table`.`id`
                               AND `namec`.`language` = '".$_SESSION['glpilanguage']."'
                               AND `namec`.`field` = 'comment')";
      }

   }

   $query = "SELECT $SELECTNAME, $SELECTCOMMENT
             FROM `$table`
             $JOIN
             WHERE `$table`.`id` = '$ID'";

   if ($result = $DB->query($query)) {
      if ($DB->numrows($result) == 1) {
         $transname = $DB->result($result,0,"transname");
         if ($translate && !empty($transname)) {
            $name = $transname;
         } else {
            $name = $DB->result($result,0,"name");
         }

         $comment      = $name." :<br>";
         $transcomment = $DB->result($result,0,"transcomment");

         if ($translate && !empty($transcomment)) {
            $comment .= nl2br($transcomment);
         } else {
            $comment .= nl2br($DB->result($result,0,"comment"));
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
 * @param $table        string   Dropdown Tree table
 * @param $ID           integer  ID of the element
 * @param $withcomment  boolean  1 if you want to give the array with the comments (false by default)
 * @param $translate    boolean  (true by default)
 *
 * @return string : completename of the element
 *
 * @see getTreeLeafValueName
**/
function getTreeValueCompleteName($table, $ID, $withcomment=false, $translate=true) {
   global $DB;

   $name    = "";
   $comment = "";

   $SELECTNAME    = "`$table`.`completename`, '' AS transname";
   $SELECTCOMMENT = "`$table`.`comment`, '' AS transcomment";
   $JOIN          = '';
   if  ($translate) {
      if (Session::haveTranslations(getItemTypeForTable($table), 'completename')) {
         $SELECTNAME  = "`$table`.`completename`, `namet`.`value` AS transname";
         $JOIN       .= " LEFT JOIN `glpi_dropdowntranslations` AS namet
                           ON (`namet`.`itemtype` = '".getItemTypeForTable($table)."'
                               AND `namet`.`items_id` = `$table`.`id`
                               AND `namet`.`language` = '".$_SESSION['glpilanguage']."'
                               AND `namet`.`field` = 'completename')";
      }
      if (Session::haveTranslations(getItemTypeForTable($table), 'comment')) {
         $SELECTCOMMENT  = "`$table`.`comment`, `namec`.`value` AS transcomment";
         $JOIN          .= " LEFT JOIN `glpi_dropdowntranslations` AS namec
                              ON (`namec`.`itemtype` = '".getItemTypeForTable($table)."'
                                  AND `namec`.`items_id` = `$table`.`id`
                                  AND `namec`.`language` = '".$_SESSION['glpilanguage']."'
                                  AND `namec`.`field` = 'comment')";
      }

   }

   $query = "SELECT $SELECTNAME, $SELECTCOMMENT
             FROM `$table`
             $JOIN
             WHERE `$table`.`id` = '$ID'";

   if ($result = $DB->query($query)) {
      if ($DB->numrows($result) == 1) {
         $transname = $DB->result($result,0,"transname");
         if ($translate && !empty($transname)) {
            $name = $transname;
         } else {
            $name = $DB->result($result,0,"completename");
         }
         $comment  = sprintf(__('%1$s: %2$s')."<br>",
                             "<span class='b'>".__('Complete name')."</span>",
                             $name);
         $comment .= "<span class='b'>&nbsp;".__('Comments')."&nbsp;</span>";

         $transcomment = $DB->result($result,0,"transcomment");
         if ($translate && !empty($transcomment)) {
            $comment .= nl2br($transcomment);
         } else {
            $comment .= nl2br($DB->result($result,0,"comment"));
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
 * @param $table        string   table name
 * @param $ID           integer  value ID
 * @param $wholename    string   current name to complete (use for recursivity) (default '')
 * @param $level        integer  current level of recursion (default 0)
 *
 * @return string name
**/
function getTreeValueName($table, $ID, $wholename="", $level=0) {
   global $DB;

   $parentIDfield = getForeignKeyFieldForTable($table);

   $query = "SELECT `name`, `$parentIDfield`
             FROM `$table`
             WHERE `id` = '$ID'";
   $name = "";

   if ($result = $DB->query($query)) {
      if ($DB->numrows($result)>0) {
         $row      = $DB->fetch_assoc($result);
         $parentID = $row[$parentIDfield];


         if ($wholename == "") {
            $name = $row["name"];
         } else {
            $name = $row["name"] . " > ";
         }

         $level++;
         list($tmpname, $level)  = getTreeValueName($table, $parentID, $name, $level);
         $name                   = $tmpname. $name;
      }
   }
   return array($name, $level);
}


/**
 * Get the ancestors of an item in a tree dropdown
 *
 * @param $table     string   table name
 * @param $items_id  integer  The ID of the item
 *
 * @return array of IDs of the ancestors
**/
function getAncestorsOf($table, $items_id) {
   global $DB;

   // IDs to be present in the final array
   $id_found      = array();
   $parentIDfield = getForeignKeyFieldForTable($table);
   $use_cache     = FieldExists($table, "ancestors_cache");

   if ($use_cache
       && ($items_id > 0)) {

      $query = "SELECT `ancestors_cache`, `$parentIDfield`
                FROM `$table`
                WHERE `id` = '$items_id'";

      if (($result = $DB->query($query))
          && ($DB->numrows($result) > 0)) {
         $ancestors = trim($DB->result($result, 0, 0));
         $parent    = $DB->result($result, 0, 1);

         // Return datas from cache in DB
         if (!empty($ancestors)) {
            return importArrayFromDB($ancestors, true);
         }

         // Recursive solution for table with-cache
         if ($parent > 0) {
            $id_found = getAncestorsOf($table, $parent);
         }

         // ID=0 only exists for Entities
         if (($parent > 0)
             || ($table == 'glpi_entities')) {
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
   while ($IDf > 0) {
      // Get next elements
      $query = "SELECT `$parentIDfield`
                FROM `$table`
                WHERE `id` = '$IDf'";

      $result = $DB->query($query);
      if ($DB->numrows($result)>0) {
         $IDf = $DB->result($result,0,0);
      } else {
         $IDf = 0;
      }

      if (!isset($id_found[$IDf])
          && (($IDf > 0) || ($table == 'glpi_entities'))) {
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
 * @param $table  string   table name
 * @param $IDf    integer  The ID of the father
 *
 * @return array of IDs of the sons
**/
function getSonsOf($table, $IDf) {
   global $DB;

   $parentIDfield = getForeignKeyFieldForTable($table);
   $use_cache     = FieldExists($table, "sons_cache");

   if ($use_cache
       && ($IDf > 0)) {

      $query = "SELECT `sons_cache`
                FROM `$table`
                WHERE `id` = '$IDf'";

      if (($result = $DB->query($query))
          && ($DB->numrows($result) > 0)) {
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

   if (($result = $DB->query($query))
       && ($DB->numrows($result) > 0)) {
      while ($row = $DB->fetch_assoc($result)) {
         $id_found[$row['id']] = $row['id'];
         $found[$row['id']]    = $row['id'];
      }
   }

   // Get the leafs of previous founded item
   while (count($found) > 0) {
      $first = true;
      // Get next elements
      $query = "SELECT `id`
                FROM `$table`
                WHERE `$parentIDfield` IN ('" . implode("','",$found) . "')";

      // CLear the found array
      unset($found);
      $found = array();

      $result = $DB->query($query);
      if ($DB->numrows($result) > 0) {
         while ($row = $DB->fetch_assoc($result)) {
            if (!isset($id_found[$row['id']])) {
               $id_found[$row['id']] = $row['id'];
               $found[$row['id']]    = $row['id'];
            }
         }
      }
   }

   // Store cache datas in DB
   if ($use_cache
       && ($IDf > 0)) {

      $query = "UPDATE `$table`
                SET `sons_cache`='".exportArrayToDB($id_found)."'
                WHERE `id` = '$IDf';";
      $DB->query($query);
   }

   return $id_found;
}


/**
 * Get the sons and the ancestors of an item in a tree dropdown. Rely on getSonsOf and getAncestorsOf
 *
 * @since version 0.84
 *
 * @param $table  string   table name
 * @param $IDf    integer  The ID of the father
 *
 * @return array of IDs of the sons and the ancestors
**/
function getSonsAndAncestorsOf($table, $IDf) {
   return getAncestorsOf($table, $IDf) + getSonsOf($table, $IDf);
}


/**
 * Get the sons of an item in a tree dropdown
 *
 * @param $table  string   table name
 * @param $IDf    integer  The ID of the father
 *
 * @return array of IDs of the sons
**/
function getTreeForItem($table, $IDf) {
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

   if (($result = $DB->query($query))
       && ($DB->numrows($result) > 0)) {

      while ($row = $DB->fetch_assoc($result)) {
         $id_found[$row['id']]['parent'] = $IDf;
         $id_found[$row['id']]['name']   = $row['name'];
         $found[$row['id']]              = $row['id'];
      }
   }

   // Get the leafs of previous founded item
   while (count($found) > 0) {
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
      if ($DB->numrows($result) > 0) {
         while ($row = $DB->fetch_assoc($result)) {
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
 * @param $list   array    the list
 * @param $root   integer  root of the tree
 *
 * @return list of items in the tree
**/
function contructTreeFromList($list, $root) {

   $tree = array();
   foreach ($list as $ID => $data) {
      if ($data['parent'] == $root) {
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
 * @param $tree   array    the tree
 * @param $parent integer  root of the tree (default =0)
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

            if (is_array($underdata['tree']) && count($underdata['tree'])) {
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
 * @param $table     string   table name
 * @param $IDf       integer  The ID of the father
 * @param $reallink  string   real field to link ($table.id if not set) (default ='')
 *
 * @return string the query
**/
function getRealQueryForTreeItem($table, $IDf, $reallink="") {
   global $DB;

   if (empty($IDf)) {
      return "";
   }

   if (empty($reallink)) {
      $reallink = "`".$table."`.`id`";
   }

   $id_found = getSonsOf($table, $IDf);

   // Construct the final request
   return $reallink." IN ('".implode("','", $id_found)."')";
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
   if ($DB->numrows($result) > 0) {
      while ($data=$DB->fetch_assoc($result)) {
         list($name, $level) = getTreeValueName($table, $data['id']);
         $query = "UPDATE `$table`
                   SET `completename` = '".addslashes($name)."',
                       `level` = '$level'
                   WHERE `id` = '".$data['id']."'";
         $DB->query($query);
      }
   }
}


/**
 * Get the ID of the next Item
 *
 * @param $table           table to search next item
 * @param $ID              current ID
 * @param $condition       condition to add to the search (default ='')
 * @param $nextprev_item   field used to sort (default ='name')
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

   if ($nextprev_item != "id") {
      $query = "SELECT `$nextprev_item`
                FROM `$table`
                WHERE `id` = '$ID'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) > 0) {
            $search = addslashes($DB->result($result, 0, 0));
         } else {
            $nextprev_item = "id";
         }
      }
   }

   $LEFTJOIN = '';
   if ($table == "glpi_users") {
      $LEFTJOIN = " LEFT JOIN `glpi_profiles_users`
                           ON (`glpi_users`.`id` = `glpi_profiles_users`.`users_id`)";
   }

   $query = "SELECT `$table`.`id`
             FROM `$table`
             $LEFTJOIN
             WHERE (`$table`.`$nextprev_item` > '$search' ";

   // Same name case
   if ($nextprev_item != "id") {
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
   if ($table == "glpi_entities") {
      $query .= getEntitiesRestrictRequest("AND", $table, '', '', true);

   } else if ($item->isEntityAssign()) {
      $query .= getEntitiesRestrictRequest("AND", $table, '', '', $item->maybeRecursive());

   } else if ($table == "glpi_users") {
      $query .= getEntitiesRestrictRequest("AND", "glpi_profiles_users");
   }

   $query .= " ORDER BY `$table`.`$nextprev_item` ASC,
                        `$table`.`id` ASC";

   $result = $DB->query($query);
   if ($result
       && ($DB->numrows($result) > 0)) {
      return $DB->result($result, 0, "id");
   }

   return -1;
}


/**
 * Get the ID of the previous Item
 *
 * @param $table           table to search next item
 * @param $ID              current ID
 * @param $condition       condition to add to the search (default ='')
 * @param $nextprev_item   field used to sort (default ='name')
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

   if ($nextprev_item != "id") {
      $query = "SELECT `$nextprev_item`
                FROM `$table`
                WHERE `id` = '$ID'";

      $result = $DB->query($query);
      if ($DB->numrows($result) > 0) {
         $search = addslashes($DB->result($result, 0, 0));
      } else {
         $nextprev_item = "id";
      }
   }

   $LEFTJOIN = '';
   if ($table == "glpi_users") {
      $LEFTJOIN = " LEFT JOIN `glpi_profiles_users`
                           ON (`glpi_users`.`id` = `glpi_profiles_users`.`users_id`)";
   }

   $query = "SELECT `$table`.`id`
             FROM `$table`
             $LEFTJOIN
             WHERE (`$table`.`$nextprev_item` < '$search' ";

   // Same name case
   if ($nextprev_item != "id") {
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
   if ($table == "glpi_entities") {
      $query .= getEntitiesRestrictRequest("AND", $table, '', '', true);

   } else if ($item->isEntityAssign()) {
      $query .= getEntitiesRestrictRequest("AND", $table, '', '', $item->maybeRecursive());

   } else if ($table == "glpi_users") {
      $query .= getEntitiesRestrictRequest("AND", "glpi_profiles_users");
   }

   $query .= " ORDER BY `$table`.`$nextprev_item` DESC,
                        `$table`.`id` DESC";

   $result = $DB->query($query);
   if ($result
       && ($DB->numrows($result) > 0)) {
      return $DB->result($result, 0, "id");
   }

   return -1;
}


/**
 * Format a user name
 *
 *@param $ID            integer  ID of the user.
 *@param $login         string   login of the user
 *@param $realname      string   realname of the user
 *@param $firstname     string   firstname of the user
 *@param $link          integer  include link (only if $link==1) (default =0)
 *@param $cut           integer  limit string length (0 = no limit) (default =0)
 *@param $force_config   boolean force order and id_visible to use common config (false by default)
 *
 *@return string : formatted username
**/
function formatUserName($ID, $login, $realname, $firstname, $link=0, $cut=0, $force_config=false) {
   global $CFG_GLPI;

   $before = "";
   $after  = "";

   $order = $CFG_GLPI["names_format"];
   if (isset($_SESSION["glpinames_format"]) && !$force_config) {
      $order = $_SESSION["glpinames_format"];
   }

   $id_visible = $CFG_GLPI["is_ids_visible"];
   if (isset($_SESSION["glpiis_ids_visible"]) && !$force_config) {
      $id_visible = $_SESSION["glpiis_ids_visible"];
   }


   if (strlen($realname) > 0) {
      $temp = $realname;

      if (strlen($firstname) > 0) {
         if ($order == User::FIRSTNAME_BEFORE) {
            $temp = $firstname." ".$temp;
         } else {
            $temp .= " ".$firstname;
         }
      }

      if (($cut > 0)
          && (Toolbox::strlen($temp) > $cut)) {
         $temp = Toolbox::substr($temp, 0, $cut)." ...";
      }

   } else {
      $temp = $login;
   }

   if ($ID > 0
       && ((strlen($temp) == 0) || $id_visible)) {
      $temp = sprintf(__('%1$s (%2$s)'), $temp, $ID);
   }

   if (($link == 1)
       && ($ID > 0)) {
      $before = "<a title=\"".$temp."\" href='".$CFG_GLPI["root_doc"]."/front/user.form.php?id=".$ID."'>";
      $after  = "</a>";
   }

   $username = $before.$temp.$after;
   return $username;
}


/**
 * Get name of the user with ID=$ID (optional with link to user.form.php)
 *
 *@param $ID   integer  ID of the user.
 *@param $link integer  1 = Show link to user.form.php 2 = return array with comments and link
 *                      (default =0)
 *
 *@return string : username string (realname if not empty and name if realname is empty).
**/
function getUserName($ID, $link=0) {
   global $DB, $CFG_GLPI;

   $user = "";
   if ($link == 2) {
      $user = array("name"    => "",
                    "link"    => "",
                    "comment" => "");
   }

   if ($ID) {
      $query  = "SELECT *
                 FROM `glpi_users`
                 WHERE `id` = '$ID'";
      $result = $DB->query($query);

      if ($link == 2) {
         $user = array("name"    => "",
                       "comment" => "",
                       "link"    => "");
      }

      if ($DB->numrows($result) == 1) {
         $data     = $DB->fetch_assoc($result);
         $username = formatUserName($data["id"], $data["name"], $data["realname"],
                                    $data["firstname"], $link);

         if ($link == 2) {
            $user["name"]    = $username;
            $user["link"]    = $CFG_GLPI["root_doc"]."/front/user.form.php?id=".$ID;
            $user['comment'] = '';

            $comments        = array();
            $comments[]      = array('name'  => __('Name'),
                                     'value' => $username);
            $comments[]      = array('name'  => __('Login'),
                                     'value' => $data["name"]);


            $email           = UserEmail::getDefaultForUser($ID);
            if (!empty($email)) {
               $comments[] = array('name'  => __('Email'),
                                   'value' => $email);
            }

            if (!empty($data["phone"])) {
               $comments[] = array('name'  => __('Phone'),
                                   'value' => $data["phone"]);
            }

            if (!empty($data["mobile"])) {
               $comments[] = array('name'  => __('Mobile phone'),
                                   'value' => $data["mobile"]);
            }

            if ($data["locations_id"] > 0) {
               $comments[] = array('name'  => __('Location'),
                                   'value' => Dropdown::getDropdownName("glpi_locations",
                                                                        $data["locations_id"]));
            }

            if ($data["usertitles_id"] > 0) {
               $comments[] = array('name'  => _x('person','Title'),
                                   'value' => Dropdown::getDropdownName("glpi_usertitles",
                                                                        $data["usertitles_id"]));
            }

            if ($data["usercategories_id"] > 0) {
               $comments[] = array('name'  => __('Category'),
                                   'value' => Dropdown::getDropdownName("glpi_usercategories",
                                                                        $data["usercategories_id"]));
            }
            if (count($comments)) {
               $user['comment'] = $user['comment'];
               foreach ($comments as $datas) {
                  // Do not use SPAN here
                  $user['comment'] .= sprintf(__('%1$s: %2$s')."<br>",
                                              "<strong>".$datas['name']."</strong>",
                                              $datas['value']);
               }
            }

            if (!empty($data['picture'])) {
               $user['comment'] = "<div class='tooltip_picture_border'>".
                                  "<img  class='tooltip_picture' src='".
                                     User::getThumbnailURLForPicture($data['picture'])."' /></div>".
                                  "<div class='tooltip_text'>".$user['comment']."</div>";
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
      while ($data = $DB->fetch_row($result)) {
         if ($data[0] === $tablename) {
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
 * @param $table     String   Name of the table we want to verify.
 * @param $field     String   Name of the field we want to verify.
 * @param $usecache  Boolean  if use field list cache (default true)
 *
 *@return bool : true if exists, false elseway.
**/
function FieldExists($table, $field, $usecache=true) {
   global $DB;

   if ($fields = $DB->list_fields($table, $usecache)) {
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
 * @param $table  string  table of the index
 * @param $field  string  name of the index
 *
 * @return boolean : index exists ?
**/
function isIndex($table, $field) {
   global $DB;

   $result = $DB->query("SHOW INDEX FROM `$table`");

   if ($result && $DB->numrows($result)) {
      while ($data = $DB->fetch_assoc($result)) {
         if ($data["Key_name"] == $field) {
            return true;
         }
      }
   }
   return false;
}


/**
 * Create a new name using a autoname field defined in a template
 *
 * @param $objectName      autoname template
 * @param $field           field to autoname
 * @param $isTemplate      true if create an object from a template
 * @param $itemtype        item type
 * @param $entities_id     limit generation to an entity (default -1)
 *
 * @return new auto string
**/
function autoName($objectName, $field, $isTemplate, $itemtype, $entities_id=-1) {
   global $DB, $CFG_GLPI;

   $len = Toolbox::strlen($objectName);

   if ($isTemplate
       && ($len > 8)
       && (Toolbox::substr($objectName,0,4) === '&lt;')
       && (Toolbox::substr($objectName,$len - 4,4) === '&gt;')) {

      $autoNum = Toolbox::substr($objectName, 4, $len - 8);
      $mask    = '';

      if (preg_match( "/\\#{1,10}/", $autoNum, $mask)) {
         $global  = ((strpos($autoNum, '\\g') !== false) && ($itemtype != 'Infocom')) ? 1 : 0;
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
         $len  = Toolbox::strlen($mask);
         $like = str_replace('#', '_', $autoNum);

         if ($global == 1) {
            $query = "";
            $first = 1;
            $types = array('Computer', 'Monitor', 'NetworkEquipment', 'Peripheral', 'Phone',
                           'Printer');

            foreach ($types as $t) {
               $table = getTableForItemType($t);
               $query .= ($first ? "SELECT " : " UNION SELECT  ")." $field AS code
                         FROM `$table`
                         WHERE `$field` LIKE '$like'
                               AND `is_deleted` = '0'
                               AND `is_template` = '0'";

               if ($CFG_GLPI["use_autoname_by_entity"]
                   && ($entities_id >= 0)) {
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

            if ($itemtype != 'Infocom') {
               $query .= " AND `is_deleted` = '0'
                           AND `is_template` = '0'";

               if ($CFG_GLPI["use_autoname_by_entity"]
                   && ($entities_id >= 0)) {
                  $query .= " AND `entities_id` = '$entities_id' ";
               }
            }
         }

         $query = "SELECT MAX(Num.no) AS lastNo
                   FROM (".$query.") AS Num";
         $resultNo = $DB->query($query);

         if ($DB->numrows($resultNo) > 0) {
            $data  = $DB->fetch_assoc($resultNo);
            $newNo = $data['lastNo'] + 1;
         } else {
            $newNo = 0;
         }
         $objectName = str_replace(array($mask,
                                         '\\_',
                                         '\\%'),
                                   array(Toolbox::str_pad($newNo, $len, '0', STR_PAD_LEFT),
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
   global $DB;

   // Case of not init $DB object
   if (method_exists($DB,"close")) {
      $DB->close();
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
 * Add dates for request
 *
 * @param $field        table.field to request
 * @param $begin  date  begin date
 * @param $end    date  end date
 *
 * @return sql
**/
function getDateRequest($field, $begin, $end) {

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

   // Use old scheme to decode
   if (!is_array($TAB)) {
      $TAB = array();

      foreach (explode(" ", $DATA) as $ITEM) {
         $A = explode("=>", $ITEM);

         if ((strlen($A[0]) > 0)
             && isset($A[1])) {
            $TAB[urldecode($A[0])] = urldecode($A[1]);
         }
      }
   }
   return $TAB;
}


/**
 * Get hour from sql
 *
 * @param $time datetime: time
 *
 * @return  array
**/
function get_hour_from_sql($time) {

   $t = explode(" ", $time);
   $p = explode(":", $t[1]);

   return $p[0].":".$p[1];
}


/**
 * Get the $RELATION array. It's defined all relations between tables in the DB.
 *
 * @return the $RELATION array
**/
function getDbRelations() {
   global $CFG_GLPI;

   include (GLPI_ROOT . "/inc/relation.constant.php");

   // Add plugins relations
   $plug_rel = Plugin::getDatabaseRelations();
   if (count($plug_rel) > 0) {
      $RELATION = array_merge_recursive($RELATION,$plug_rel);
   }
   return $RELATION;
}


/**
 * Get SQL request to restrict to current entities of the user
 *
 * @param $separator          separator in the begin of the request (default AND)
 * @param $table              table where apply the limit (if needed, multiple tables queries)
 *                            (default '')
 * @param $field              field where apply the limit (id != entities_id) (default '')
 * @param $value              entity to restrict (if not set use $_SESSION['glpiactiveentities']).
 *                            single item or array (default '')
 * @param $is_recursive       need to use recursive process to find item
 *                            (field need to be named recursive) (false by default)
 * @param $complete_request   need to use a complete request and not a simple one
 *                            when have acces to all entities (used for reminders)
 *                            (false by default)
 *
 * @return String : the WHERE clause to restrict
**/
function getEntitiesRestrictRequest($separator="AND", $table="", $field="",$value='',
                                    $is_recursive=false, $complete_request=false) {

   $query = $separator ." ( ";

   // !='0' needed because consider as empty
   if (!$complete_request
       && ($value != '0')
       && empty($value)
       && isset($_SESSION['glpishowallentities'])
       && $_SESSION['glpishowallentities']) {

      // Not ADD "AND 1" if not needed
      if (trim($separator) == "AND") {
         return "";
      }
      return $query." 1 ) ";
   }

   if (!empty($table)) {
      $query .= "`$table`.";
   }
   if (empty($field)) {
      if ($table == 'glpi_entities') {
         $field = "id";
      } else {
         $field = "entities_id";
      }
   }

   $query .= "`$field`";

   if (is_array($value)) {
      $query .= " IN ('" . implode("','",$value) . "') ";
   } else {
      if (strlen($value) == 0) {
         $query .= " IN (".$_SESSION['glpiactiveentities_string'].") ";
      } else {
         $query .= " = '$value' ";
      }
   }

   if ($is_recursive) {
      $ancestors = array();
      if (is_array($value)) {
         foreach ($value as $val) {
            $ancestors = array_unique(array_merge(getAncestorsOf("glpi_entities", $val),
                                                  $ancestors));
         }
         $ancestors = array_diff($ancestors, $value);

      } else if (strlen($value) == 0) {
         $ancestors = $_SESSION['glpiparententities'];

      } else {
         $ancestors = getAncestorsOf("glpi_entities", $value);
      }

      if (count($ancestors)) {
         if ($table == 'glpi_entities') {
            $query .= " OR `$table`.`$field` IN ('" . implode("','",$ancestors) . "')";
         } else {
            $query .= " OR (`$table`.`is_recursive`='1' ".
                           "AND `$table`.`$field` IN ('" . implode("','",$ancestors) . "'))";
         }
      }
   }
   $query .= " ) ";

   return $query;
}
?>
