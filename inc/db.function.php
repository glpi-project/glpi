<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Return foreign key field name for a table
 *
 * @param $table string table name
 *
 * @return string field name used for a foreign key to the parameter table
 *
 * @deprecated 9.2 see DbUtils::getForeignKeyFieldForTable()
**/
function getForeignKeyFieldForTable($table) {
   $dbu = new DbUtils();
   return $dbu->getForeignKeyFieldForTable($table);
}


/**
 * Check if field is a foreign key field
 *
 * @since version 0.84
 *
 * @param $field string field name
 *
 * @return string field name used for a foreign key to the parameter table
 *
 * @deprecated 9.2 see DbUtils::isForeignKeyField()
**/
function isForeignKeyField($field) {
   $dbu = new DbUtils();
   return $dbu->isForeignKeyField($field);
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
 *
 * @deprecated 9.2 see DbUtils::getTableNameForForeignKeyField()
**/
function getTableNameForForeignKeyField($fkname) {
   $dbu = new DbUtils();
   return $dbu->getTableNameForForeignKeyField($fkname);
}


/**
 * Return ItemType  for a table
 *
 * @param $table string table name
 *
 * @return string itemtype corresponding to a table name parameter
 *
 * @deprecated 9.2 see DbUtils::getItemTypeForTable()
**/
function getItemTypeForTable($table) {
   $dbu = new DbUtils();
   return $dbu->getItemTypeForTable($table);
}

/**
 * Return ItemType for a foreign key
 *
 * @param string $fkname
 *
 * @return string ItemType name for the fkname parameter
 *
 * @deprecated 9.2 see DbUtils::getItemtypeForForeignKeyField()
 */
function getItemtypeForForeignKeyField($fkname) {
   $table = getTableNameForForeignKeyField($fkname);
   return getItemTypeForTable($table);
}

/**
 * Return ItemType  for a table
 *
 * @param $itemtype   string   itemtype
 *
 * @return string table name corresponding to the itemtype  parameter
 *
 * @deprecated 9.2 see DbUtils::getTableForItemType()
**/
function getTableForItemType($itemtype) {
   $dbu = new DbUtils();
   return $dbu->getTableForItemType($itemtype);
}


/**
 * Get new item objet for an itemtype
 *
 * @since version 0.83
 *
 * @param $itemtype   string   itemtype
 *
 * @return itemtype object or false if class does not exists
 *
 * @deprecated 9.2 see DbUtils::getItemForItemtype()
**/
function getItemForItemtype($itemtype) {
   $dbu = new DbUtils();
   return $dbu->getItemForItemtype($itemtype);
}


/**
 * Return the plural of a string
 *
 * @param $string   string   input string
 *
 * @return string plural of the parameter string
 *
 * @deprecated 9.2 see DbUtils::getPlural()
**/
function getPlural($string) {
   $dbu = new DbUtils();
   return $dbu->getPlural($string);
}


/**
 * Return the singular of a string
 *
 * @param $string   string   input string
 *
 * @return string singular of the parameter string
 *
 * @deprecated 9.2 see DbUtils::getSingular()
**/
function getSingular($string) {
   $dbu = new DbUtils();
   return $dbu->getSingular($string);
}


/**
 * Count the number of elements in a table.
 *
 * @param string|array $table     table name(s)
 * @param string|array $condition condition to use (default '') or array of criteria
 *
 * @return int nb of elements in table
 *
 * @deprecated 9.2 see DbUtils::countElementsInTable()
**/
function countElementsInTable($table, $condition = "") {
   $dbu = new DbUtils();
   return $dbu->countElementsInTable($table, $condition);
}

/**
 * Count the number of elements in a table.
 *
 * @param $table        string/array   table names
 * @param $field        string         field name
 * @param $condition    string         condition to use (default '')
 *
 * @return int nb of elements in table
 *
 * @deprecated 9.2 see DbUtils::countDistinctElementsInTable()
**/
function countDistinctElementsInTable($table, $field, $condition = "") {
   $dbu = new DbUtils();
   return $dbu->countDistinctElementsInTable($table, $field, $condition);
}

/**
 * Count the number of elements in a table for a specific entity
 *
 * @param $table        string         table name
 * @param $condition    string/array   additional condition (default '') or criteria
 *
 * @return int nb of elements in table
 *
 * @deprecated 9.2 see DbUtils::countElementsInTableForMyEntities()
**/
function countElementsInTableForMyEntities($table, $condition = '') {
   $dbu = new DbUtils();
   return $dbu->countElementsInTableForMyEntities($table, $condition);
}


/**
 * Count the number of elements in a table for a specific entity
 *
 * @param string  $table     table name
 * @param integer $entity    the entity ID
 * @param string  $condition additional condition (default '')
 * @param boolean $recursive Whether to recurse or not. If true, will be conditionned on item recursivity
 *
 * @return int nb of elements in table
 *
 * @deprecated 9.2 see DbUtils::countElementsInTableForEntity()
**/
function countElementsInTableForEntity($table, $entity, $condition = '', $recursive = true) {
   $dbu = new DbUtils();
   return $dbu->countElementsInTableForEntity($table, $entity, $condition, $recursive);
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
 *
 * @deprecated 9.2 see DbUtils::getAllDataFromTable()
**/
function getAllDatasFromTable($table, $condition = '', $usecache = false, $order = '') {
   $dbu = new DbUtils();
   return $dbu->getAllDataFromTable($table, $condition, $usecache, $order);
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
function getTreeLeafValueName($table, $ID, $withcomment = false, $translate = true) {
   global $DB;

   $name    = "";
   $comment = "";

   $SELECTNAME    = "`$table`.`name`, '' AS transname";
   $SELECTCOMMENT = "`$table`.`comment`, '' AS transcomment";
   $JOIN          = '';
   if ($translate) {
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
         $transname = $DB->result($result, 0, "transname");
         if ($translate && !empty($transname)) {
            $name = $transname;
         } else {
            $name = $DB->result($result, 0, "name");
         }

         $comment      = $name." :<br>";
         $transcomment = $DB->result($result, 0, "transcomment");

         if ($translate && !empty($transcomment)) {
            $comment .= nl2br($transcomment);
         } else {
            $comment .= nl2br($DB->result($result, 0, "comment"));
         }
      }
   }

   if ($withcomment) {
      return ["name"    => $name,
                   "comment" => $comment];
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
 * @param $tooltip      boolean  (true by default) returns a tooltip, else returns only 'comment'
 *
 * @return string : completename of the element
 *
 * @see getTreeLeafValueName
**/
function getTreeValueCompleteName($table, $ID, $withcomment = false, $translate = true, $tooltip = true) {
   global $DB;

   $name    = "";
   $comment = "";

   $SELECTNAME    = "`$table`.`completename`, '' AS transname";
   $SELECTCOMMENT = "`$table`.`comment`, '' AS transcomment";
   $JOIN          = '';
   if ($translate) {
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
         $transname = $DB->result($result, 0, "transname");
         if ($translate && !empty($transname)) {
            $name = $transname;
         } else {
            $name = $DB->result($result, 0, "completename");
         }
         if ($tooltip) {
            $comment  = sprintf(__('%1$s: %2$s')."<br>",
                                "<span class='b'>".__('Complete name')."</span>",
                                $name);
            $comment .= "<span class='b'>&nbsp;".__('Comments')."&nbsp;</span>";
         }
         $transcomment = $DB->result($result, 0, "transcomment");
         if ($translate && !empty($transcomment)) {
            $comment .= nl2br($transcomment);
         } else {
            $comment .= nl2br($DB->result($result, 0, "comment"));
         }
      }
   }

   if (empty($name)) {
      $name = "&nbsp;";
   }

   if ($withcomment) {
      return ["name"    => $name,
                   "comment" => $comment];
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
function getTreeValueName($table, $ID, $wholename = "", $level = 0) {
   global $DB;

   $parentIDfield = getForeignKeyFieldForTable($table);

   $iterator = $DB->request([
      'SELECT' => ['name', $parentIDfield],
      'FROM'   => $table,
      'WHERE'  => ['id' => $ID]
   ]);
   $name = "";

   if (count($iterator) > 0) {
      $row      = $iterator->current();
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
   return [$name, $level];
}


/**
 * Get the ancestors of an item in a tree dropdown
 *
 * @param string       $table    Table name
 * @param array|string $items_id The IDs of the items
 *
 * @return array of IDs of the ancestors
 *
 * @deprecated 9.2 see DbUtils::getAncestorsOf()
**/
function getAncestorsOf($table, $items_id) {
   $dbu = new DbUtils();
   return $dbu->getAncestorsOf($table, $items_id);
}


/**
 * Get the sons of an item in a tree dropdown. Get datas in cache if available
 *
 * @param $table  string   table name
 * @param $IDf    integer  The ID of the father
 *
 * @return array of IDs of the sons
 *
 * @deprecated 9.2 see DbUtils::getSonsOf()
**/
function getSonsOf($table, $IDf) {
   $dbu = new DbUtils();
   return $dbu->getSonsOf($table, $IDf);
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
 *
 * @deprecated 9.2 see DbUtils::getSonsAndAncestorsOf()
**/
function getSonsAndAncestorsOf($table, $IDf) {
   $dbu = new DbUtils();
   return $dbu->getSonsAndAncestorsOf($table, $IDf);
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
   $id_found = [];
   // current ID found to be added
   $found = [];

   // First request init the  variables
   $iterator = $DB->request([
      $table, [
         'WHERE'  => [$parentIDfield => $IDf],
         'ORDER'  => 'name'
      ]
   ]);

   while ($row = $iterator->next()) {
      $id_found[$row['id']]['parent'] = $IDf;
      $id_found[$row['id']]['name']   = $row['name'];
      $found[$row['id']]              = $row['id'];
   }

   // Get the leafs of previous founded item
   while (count($found) > 0) {
      // Get next elements
      $iterator = $DB->request([
         $table, [
            'WHERE'  => [$parentIDfield => $found],
            'ORDER'  => 'name'
         ]
      ]);

      // CLear the found array
      unset($found);
      $found = [];

      $result = $DB->query($query);
      while ($row = $iterator->next()) {
         if (!isset($id_found[$row['id']])) {
            $id_found[$row['id']]['parent'] = $row[$parentIDfield];
            $id_found[$row['id']]['name']   = $row['name'];
            $found[$row['id']]              = $row['id'];
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

   $tree = [];
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
function contructListFromTree($tree, $parent = 0) {

   $list = [];
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
function getRealQueryForTreeItem($table, $IDf, $reallink = "") {
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

   $iterator = $DB->request([
      'SELECT' => 'id',
      'FROM'   => $table
   ]);

   while ($data = $iterator->next()) {
      list($name, $level) = getTreeValueName($table, $data['id']);
      $query = "UPDATE `$table`
                  SET `completename` = '".addslashes($name)."',
                     `level` = '$level'
                  WHERE `id` = '".$data['id']."'";
      $DB->query($query);
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
function getNextItem($table, $ID, $condition = "", $nextprev_item = "name") {
   global $DB, $CFG_GLPI;

   if (empty($nextprev_item)) {
      return false;
   }

   $itemtype = getItemTypeForTable($table);
   $item     = new $itemtype();
   $search   = $ID;

   if ($nextprev_item != "id") {
      $iterator = $DB->request([
         'SELECT' => $nextprev_item,
         'FROM'   => $table,
         'WHERE'  => ['id' => $ID]
      ]);

      if (count($iterator) > 0) {
         $search = addslashes($iterator->current()[$nextprev_item]);
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
function getPreviousItem($table, $ID, $condition = "", $nextprev_item = "name") {
   global $DB, $CFG_GLPI;

   if (empty($nextprev_item)) {
      return false;
   }

   $itemtype = getItemTypeForTable($table);
   $item     = new $itemtype();
   $search   = $ID;

   if ($nextprev_item != "id") {
      $iterator = $DB->request([
         'SELECT' => $nextprev_item,
         'FROM'   => $table,
         'WHERE'  => ['id' => $ID]
      ]);

      if (count($iterator) > 0) {
         $search = addslashes($iterator->current()[$nextprev_item]);
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
function formatUserName($ID, $login, $realname, $firstname, $link = 0, $cut = 0, $force_config = false) {
   global $CFG_GLPI;

   $before = "";
   $after  = "";

   $order = isset($CFG_GLPI["names_format"]) ? $CFG_GLPI["names_format"] : User::REALNAME_BEFORE;
   if (isset($_SESSION["glpinames_format"]) && !$force_config) {
      $order = $_SESSION["glpinames_format"];
   }

   $id_visible = isset($CFG_GLPI["is_ids_visible"]) ? $CFG_GLPI["is_ids_visible"] : 0;
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
function getUserName($ID, $link = 0) {
   global $DB, $CFG_GLPI;

   $user = "";
   if ($link == 2) {
      $user = ["name"    => "",
                    "link"    => "",
                    "comment" => ""];
   }

   if ($ID) {
      $iterator = $DB->request(
         'glpi_users', [
            'WHERE' => ['id' => $ID]
         ]
      );

      if ($link == 2) {
         $user = ["name"    => "",
                       "comment" => "",
                       "link"    => ""];
      }

      if (count($iterator) == 1) {
         $data     = $iterator->next();
         $username = formatUserName($data["id"], $data["name"], $data["realname"],
                                    $data["firstname"], $link);

         if ($link == 2) {
            $user["name"]    = $username;
            $user["link"]    = $CFG_GLPI["root_doc"]."/front/user.form.php?id=".$ID;
            $user['comment'] = '';

            $comments        = [];
            $comments[]      = ['name'  => __('Name'),
                                     'value' => $username];
            // Ident only if you have right to read user
            if (session::haveRight('user', READ)) {
               $comments[]      = ['name'  => __('Login'),
                                        'value' => $data["name"]];
            }

            $email           = UserEmail::getDefaultForUser($ID);
            if (!empty($email)) {
               $comments[] = ['name'  => __('Email'),
                                   'value' => $email];
            }

            if (!empty($data["phone"])) {
               $comments[] = ['name'  => __('Phone'),
                                   'value' => $data["phone"]];
            }

            if (!empty($data["mobile"])) {
               $comments[] = ['name'  => __('Mobile phone'),
                                   'value' => $data["mobile"]];
            }

            if ($data["locations_id"] > 0) {
               $comments[] = ['name'  => __('Location'),
                                   'value' => Dropdown::getDropdownName("glpi_locations",
                                                                        $data["locations_id"])];
            }

            if ($data["usertitles_id"] > 0) {
               $comments[] = ['name'  => _x('person', 'Title'),
                                   'value' => Dropdown::getDropdownName("glpi_usertitles",
                                                                        $data["usertitles_id"])];
            }

            if ($data["usercategories_id"] > 0) {
               $comments[] = ['name'  => __('Category'),
                                   'value' => Dropdown::getDropdownName("glpi_usercategories",
                                                                        $data["usercategories_id"])];
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
 * @param $tablename string : Name of the table we want to verify.
 *
 * @return bool : true if exists, false elseway.
 *
 * @deprecated 9.2 Use DB::tableExists()
**/
function TableExists($tablename) {
   global $DB;

   Toolbox::deprecated('TableExists() function is deprecated');
   return $DB->tableExists($tablename);
}


/**
 * Verify if a DB field exists
 *
 * @param $table     String   Name of the table we want to verify.
 * @param $field     String   Name of the field we want to verify.
 * @param $usecache  Boolean  if use field list cache (default true)
 *
 * @return bool : true if exists, false elseway.
 *
 * @deprecated 9.2 Use DB::fieldExists()
**/
function FieldExists($table, $field, $usecache = true) {
   global $DB;

   Toolbox::deprecated('FieldExists() function is deprecated');
   return $DB->fieldExists($table, $field, $usecache);
}


/**
 * Determine if an index exists in database
 *
 * @param $table  string  table of the index
 * @param $field  string  name of the index
 *
 * @return boolean : index exists ?
 *
 * @deprecated 9.2 Use DbUtils::isIndex()
**/
function isIndex($table, $field) {
   $dbu = new DbUtils();
   return $dbu->isIndex($table, $field);
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
function autoName($objectName, $field, $isTemplate, $itemtype, $entities_id = -1) {
   global $DB, $CFG_GLPI;

   $len = Toolbox::strlen($objectName);

   if ($isTemplate
       && ($len > 8)
       && (Toolbox::substr($objectName, 0, 4) === '&lt;')
       && (Toolbox::substr($objectName, $len - 4, 4) === '&gt;')) {

      $autoNum = Toolbox::substr($objectName, 4, $len - 8);
      $mask    = '';

      if (preg_match( "/\\#{1,10}/", $autoNum, $mask)) {
         $global  = ((strpos($autoNum, '\\g') !== false) && ($itemtype != 'Infocom')) ? 1 : 0;
         $autoNum = str_replace(['\\y',
                                      '\\Y',
                                      '\\m',
                                      '\\d',
                                      '_','%',
                                      '\\g'],
                                [date('y'),
                                      date('Y'),
                                      date('m'),
                                      date('d'),
                                      '\\_',
                                      '\\%',
                                      ''],
                                $autoNum);
         $mask = $mask[0];
         $pos  = strpos($autoNum, $mask) + 1;
         $len  = Toolbox::strlen($mask);
         $like = str_replace('#', '_', $autoNum);

         if ($global == 1) {
            $query = "";
            $first = 1;
            $types = ['Computer', 'Monitor', 'NetworkEquipment', 'Peripheral', 'Phone',
                           'Printer'];

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
         $objectName = str_replace([$mask,
                                         '\\_',
                                         '\\%'],
                                   [Toolbox::str_pad($newNo, $len, '0', STR_PAD_LEFT),
                                         '_',
                                         '%'],
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
   if (method_exists($DB, "close")) {
      $DB->close();
   }
}


/**
 * Format a web link adding http:// if missing
 *
 * @param $link link to format
 *
 * @return formatted link.
 *
 * @deprecated 9.2 Use DbUtils::isIndex()
**/
function formatOutputWebLink($link) {
   Toolbox::deprecated('formatOutputWebLink() function is deprecated');
   return Toolbox::formatOutputWebLink($link);
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

   $TAB = json_decode($DATA, true);

   // Use old scheme to decode
   if (!is_array($TAB)) {
      $TAB = [];

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
      $RELATION = array_merge_recursive($RELATION, $plug_rel);
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
 * @param $value              entity to restrict (if not set use $_SESSION['glpiactiveentities_string']).
 *                            single item or array (default '')
 * @param $is_recursive       need to use recursive process to find item
 *                            (field need to be named recursive) (false by default)
 * @param $complete_request   need to use a complete request and not a simple one
 *                            when have acces to all entities (used for reminders)
 *                            (false by default)
 *
 * @return String : the WHERE clause to restrict
 *
 * @deprecated 9.2 see DbUtils::getEntitiesRestrictRequest()
**/
function getEntitiesRestrictRequest($separator = "AND", $table = "", $field = "", $value = '',
                                    $is_recursive = false, $complete_request = false) {
   $dbu = new DbUtils();
   return $dbu->getEntitiesRestrictRequest(
      $separator,
      $table,
      $field,
      $value,
      $is_recursive,
      $complete_request
   );
}

/**
 * Get criteria to restrict to current entities of the user
 *
 * @since 9.2
 *
 * @param $table              table where apply the limit (if needed, multiple tables queries)
 *                            (default '')
 * @param $field              field where apply the limit (id != entities_id) (default '')
 * @param $value              entity to restrict (if not set use $_SESSION['glpiactiveentities']).
 *                            single item or array (default '')
 * @param $is_recursive       need to use recursive process to find item
 *                            (field need to be named recursive) (false by default, set to auto to automatic detection)
 * @param $complete_request   need to use a complete request and not a simple one
 *                            when have acces to all entities (used for reminders)
 *                            (false by default)
 *
 * @return array of criteria
 *
 * @deprecated 9.2 see DbUtils::getEntitiesRestrictCriteria()
 **/
function getEntitiesRestrictCriteria($table = '', $field = '', $value = '',
                                     $is_recursive = false, $complete_request = false) {
   $dbu = new DbUtils();
   return $dbu->getEntitiesRestrictCriteria(
      $table,
      $field,
      $value,
      $is_recursive,
      $complete_request
   );
}

/**
 * Should APCu cache be used
 *
 * @return boolean
 *
 * @deprecated @see DbUtils::useCache()
 */
function useCache() {
   $dbu = new DbUtils();
   return $dbu->useCache();
}
