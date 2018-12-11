<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Return foreign key field name for a table
 *
 * @param $table string table name
 *
 * @return string field name used for a foreign key to the parameter table
**/
function getForeignKeyFieldForTable($table) {
   $dbu = new DbUtils();
   return $dbu->getForeignKeyFieldForTable($table);
}


/**
 * Check if field is a foreign key field
 *
 * @since 0.84
 *
 * @param $field string field name
 *
 * @return boolean
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
 */
function getItemtypeForForeignKeyField($fkname) {
   $dbu = new DbUtils();
   return $dbu->getItemtypeForForeignKeyField($fkname);
}

/**
 * Return ItemType  for a table
 *
 * @param $itemtype   string   itemtype
 *
 * @return string table name corresponding to the itemtype  parameter
**/
function getTableForItemType($itemtype) {
   $dbu = new DbUtils();
   return $dbu->getTableForItemType($itemtype);
}


/**
 * Get new item objet for an itemtype
 *
 * @since 0.83
 *
 * @param $itemtype   string   itemtype
 *
 * @return CommonDBTM|boolean itemtype object or false if class does not exists
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
**/
function getSingular($string) {
   $dbu = new DbUtils();
   return $dbu->getSingular($string);
}


/**
 * Count the number of elements in a table.
 *
 * @param string|array $table     table name(s)
 * @param array        $condition condition to use (default [])
 *
 * @return int nb of elements in table
**/
function countElementsInTable($table, $condition = []) {
   $dbu = new DbUtils();
   return $dbu->countElementsInTable($table, $condition);
}

/**
 * Count the number of elements in a table.
 *
 * @param string|array $table     table names
 * @param string       $field     field name
 * @param array        $condition condition to use (default [])
 *
 * @return int nb of elements in table
**/
function countDistinctElementsInTable($table, $field, $condition = []) {
   $dbu = new DbUtils();
   return $dbu->countDistinctElementsInTable($table, $field, $condition);
}

/**
 * Count the number of elements in a table for a specific entity
 *
 * @param string $table     table name
 * @param array  $condition additional criteria, defaults to []
 *
 * @return int nb of elements in table
**/
function countElementsInTableForMyEntities($table, $condition = []) {
   $dbu = new DbUtils();
   return $dbu->countElementsInTableForMyEntities($table, $condition);
}


/**
 * Count the number of elements in a table for a specific entity
 *
 * @param string  $table     table name
 * @param integer $entity    the entity ID
 * @param string  $condition additional condition (default [])
 * @param boolean $recursive Whether to recurse or not. If true, will be conditionned on item recursivity
 *
 * @return int nb of elements in table
**/
function countElementsInTableForEntity($table, $entity, $condition = [], $recursive = true) {
   $dbu = new DbUtils();
   return $dbu->countElementsInTableForEntity($table, $entity, $condition, $recursive);
}


/**
 * Get datas from a table in an array :
 * CAUTION TO USE ONLY FOR SMALL TABLES OR USING A STRICT CONDITION
 *
 * @param string  $table     table name
 * @param array   $condition condition to use (default [])
 * @param boolean $usecache  Use cache (false by default)
 * @param string  $order     result order (default '')
 *
 * @return array containing all the datas
**/
function getAllDatasFromTable($table, $condition = [], $usecache = false, $order = '') {
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
 * @see getTreeValueCompleteName()
**/
function getTreeLeafValueName($table, $ID, $withcomment = false, $translate = true) {
   $dbu = new DbUtils();
   return $dbu->getTreeLeafValueName($table, $ID, $withcomment, $translate);
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
 * @see getTreeLeafValueName()
**/
function getTreeValueCompleteName($table, $ID, $withcomment = false, $translate = true, $tooltip = true) {
   $dbu = new DbUtils();
   return $dbu->getTreeValueCompleteName($table, $ID, $withcomment, $translate, $tooltip);
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
   $dbu = new DbUtils();
   return $dbu->getTreeValueName($table, $ID, $wholename, $level);
}


/**
 * Get the ancestors of an item in a tree dropdown
 *
 * @param string       $table    Table name
 * @param array|string $items_id The IDs of the items
 *
 * @return array of IDs of the ancestors
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
**/
function getSonsOf($table, $IDf) {
   $dbu = new DbUtils();
   return $dbu->getSonsOf($table, $IDf);
}


/**
 * Get the sons and the ancestors of an item in a tree dropdown. Rely on getSonsOf and getAncestorsOf
 *
 * @since 0.84
 *
 * @param $table  string   table name
 * @param $IDf    integer  The ID of the father
 *
 * @return array of IDs of the sons and the ancestors
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
   $dbu = new DbUtils();
   return $dbu->getTreeForItem($table, $IDf);
}


/**
 * Construct a tree from a list structure
 *
 * @param array   $list the list
 * @param integer $root root of the tree
 *
 * @return array list of items in the tree
**/
function contructTreeFromList($list, $root) {
   $dbu = new DbUtils();
   return $dbu->constructTreeFromList($list, $root);
}


/**
 * Construct a list from a tree structure
 *
 * @param array   $tree   the tree
 * @param integer $parent root of the tree (default =0)
 *
 * @return array list of items in the tree
**/
function contructListFromTree($tree, $parent = 0) {
   $dbu = new DbUtils();
   return $dbu->constructListFromTree($tree, $parent);
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
   $dbu = new DbUtils();
   return $dbu->getRealQueryForTreeItem($table, $IDf, $reallink);
}


/**
 * Compute all completenames of Dropdown Tree table
 *
 * @param $table : dropdown tree table to compute
 *
 * @return void
**/
function regenerateTreeCompleteName($table) {
   $dbu = new DbUtils();
   return $dbu->regenerateTreeCompleteName($table);
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
   $dbu = new DbUtils();
   return $dbu->formatUserName($ID, $login, $realname, $firstname, $link, $cut, $force_config);
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
   $dbu = new DbUtils();
   return $dbu->getUserName($ID, $link);
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
   $dbu = new DbUtils();
   return $dbu->isIndex($table, $field);
}


/**
 * Create a new name using a autoname field defined in a template
 *
 * @param string  $objectName  autoname template
 * @param string  $field       field to autoname
 * @param boolean $isTemplate  true if create an object from a template
 * @param string  $itemtype    item type
 * @param integer $entities_id limit generation to an entity (default -1)
 *
 * @return string new auto string
 */
function autoName($objectName, $field, $isTemplate, $itemtype, $entities_id = -1) {
   $dbu = new DbUtils();
   return $dbu->autoName($objectName, $field, $isTemplate, $itemtype, $entities_id);
}


/**
 * Close active DB connections
 *
 * @return void
**/
function closeDBConnections() {
   $dbu = new DbUtils();
   return $dbu->closeDBConnections();
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
function getDateCriteria($field, $begin, $end) {
   $dbu = new DbUtils();
   return $dbu->getDateCriteria($field, $begin, $end);
}

/**
 * Export an array to be stored in a simple field in the database
 *
 * @param $TAB Array to export / encode (one level depth)
 *
 * @return string containing encoded array
**/
function exportArrayToDB($TAB) {
   $dbu = new DbUtils();
   return $dbu->exportArrayToDB($TAB);
}


/**
 * Import an array encoded in a simple field in the database
 *
 * @param string $DATA data readed in DB to import
 *
 * @return array containing datas
 */
function importArrayFromDB($DATA) {
   $dbu = new DbUtils();
   return $dbu->importArrayFromDB($DATA);
}


/**
 * Get hour from sql
 *
 * @param $time datetime: time
 *
 * @return  array
**/
function get_hour_from_sql($time) {
   $dbu = new DbUtils();
   return $dbu->getHourFromSql($time);
}


/**
 * Get the $RELATION array. It defines all relations between tables in the DB;
 * plugins may add their own stuff
 *
 * @return array the $RELATION array
 */
function getDbRelations() {
   $dbu = new DbUtils();
   return $dbu->getDbRelations();
}


/**
 * Get SQL request to restrict to current entities of the user
 *
 * @param string  $separator        separator in the begin of the request (default AND)
 * @param string  $table            table where apply the limit (if needed, multiple tables queries)
 *                                  (default '')
 * @param string  $field            field where apply the limit (id != entities_id) (default '')
 * @param mixed   $value            entity to restrict (if not set use $_SESSION['glpiactiveentities_string']).
 *                                  single item or array (default '')
 * @param boolean $is_recursive     need to use recursive process to find item
 *                                  (field need to be named recursive) (false by default)
 * @param boolean $complete_request need to use a complete request and not a simple one
 *                                  when have acces to all entities (used for reminders)
 *                                  (false by default)
 *
 * @return string the WHERE clause to restrict
 */
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
 * @param string $table             table where apply the limit (if needed, multiple tables queries)
 *                                  (default '')
 * @param string $field             field where apply the limit (id != entities_id) (default '')
 * @param mixed $value              entity to restrict (if not set use $_SESSION['glpiactiveentities']).
 *                                  single item or array (default '')
 * @param boolean $is_recursive     need to use recursive process to find item
 *                                  (field need to be named recursive) (false by default, set to auto to automatic detection)
 * @param boolean $complete_request need to use a complete request and not a simple one
 *                                  when have acces to all entities (used for reminders)
 *                                  (false by default)
 *
 * @return array of criteria
 */
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
