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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}


/**
 * Database utilities
 *
 * @since 9.2
 */
final class DbUtils {

   /**
    * Return foreign key field name for a table
    *
    * @param string $table table name
    *
    * @return string field name used for a foreign key to the parameter table
    */
   public function getForeignKeyFieldForTable($table) {
      if (!Toolbox::startsWith($table, 'glpi_')) {
         return "";
      }
      return str_replace("glpi_", "", $table)."_id";
   }


   /**
    * Check if field is a foreign key field
    *
    * @param string $field field name
    *
    * @return boolean
    */
   public function isForeignKeyField($field) {
      return preg_match("/._id$/", $field) || preg_match("/._id_/", $field);
   }


   /**
    * Return table name for a given foreign key name
    *
    * @param string $fkname foreign key name
    *
    * @return string table name corresponding to a foreign key name
    */
   public function getTableNameForForeignKeyField($fkname) {
      if (!$this->isForeignKeyField($fkname)) {
         return '';
      }

      // If $fkname begin with _ strip it
      if (Toolbox::startsWith($fkname, '_')) {
         $fkname = substr($fkname, 1);
      }

      return "glpi_".preg_replace("/_id.*/", "", $fkname);
   }

   /**
    * Return the plural of a string
    *
    * @param string $string input string
    *
    * @return string plural of the parameter string
    */
   public function getPlural($string) {
      $rules = [
         //'singular'         => 'plural'
         //FIXME: singular is criterion, plural is criteria
         'criterias$'         => 'criterias',// Special case (criterias) when getPlural is called on already plural form
         'ch$'                => 'ches',
         'ches$'              => 'ches',
         'sh$'                => 'shes',
         'shes$'              => 'shes',
         'sses$'              => 'sses', // Case like addresses
         'ss$'                => 'sses', // Special case (addresses) when getSingular is called on already singular form
         'uses$'              => 'uses', // Case like statuses
         'us$'                => 'uses', // Case like status
         '([^aeiou])y$'       => '\1ies', // special case : category (but not key)
         '([^aeiou])ies$'     => '\1ies', // special case : category (but not key)
         '([aeiou]{2})ses$'   => '\1ses', // Case like aliases
         '([aeiou]{2})s$'     => '\1ses', // Case like aliases
         'x$'                 => 'xes',
         // 's$'              =>'ses',
         '([^s])$'            => '\1s',   // Add at the end if not exists
      ];

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
    * @param string $string input string
    *
    * @return string singular of the parameter string
    */
   public function getSingular($string) {

      $rules = [
         //'plural'           => 'singular'
         'ches$'              => 'ch',
         'ch$'                => 'ch',
         'shes$'              => 'sh',
         'sh$'                => 'sh',
         'sses$'              => 'ss', // Case like addresses
         'ss$'                => 'ss', // Special case (addresses) when getSingular is called on already singular form
         'uses$'              => 'us', // Case like statuses
         'us$'                => 'us', // Case like status
         '([aeiou]{2})ses$'   => '\1s', // Case like aliases
         'lias$'              => 'lias', // Special case (aliases) when getSingular is called on already singular form
         '([^aeiou])ies$'     => '\1y', // special case : category
         '([^aeiou])y$'       => '\1y', // special case : category
         'xes$'               => 'x',
         's$'                 => ''
      ]; // Add at the end if not exists

      foreach ($rules as  $plural => $singular) {
         $string = preg_replace("/$plural/", "$singular", $string, -1, $count);
         if ($count > 0) {
            break;
         }
      }
      return $string;
   }


   /**
    * Return table name for an item type
    *
    * @param string $itemtype itemtype
    *
    * @return string table name corresponding to the itemtype  parameter
    */
   public function getTableForItemType($itemtype) {
      global $CFG_GLPI;

      // Force singular for itemtype : States case
      $itemtype = $this->getSingular($itemtype);

      if (isset($CFG_GLPI['glpitablesitemtype'][$itemtype])) {
         return $CFG_GLPI['glpitablesitemtype'][$itemtype];

      } else {
         $prefix = "glpi_";

         if ($plug = isPluginItemType($itemtype)) {
            /* PluginFooBar   => glpi_plugin_foos_bars */
            /* GlpiPlugin\Foo\Bar => glpi_plugin_foos_bars */
            $prefix .= "plugin_".strtolower($plug['plugin'])."_";
            $table   = strtolower($plug['class']);

         } else {
            $table = strtolower($itemtype);
            if (substr($itemtype, 0, \strlen(NS_GLPI)) === NS_GLPI) {
               $table = substr($table, \strlen(NS_GLPI));
            }
         }
         $table = str_replace('\\', '_', $table);
         if (strstr($table, '_')) {
            $split = explode('_', $table);

            foreach ($split as $key => $part) {
               $split[$key] = getPlural($part);
            }
            $table = implode('_', $split);

         } else {
            $table = $this->getPlural($table);
         }

         $CFG_GLPI['glpitablesitemtype'][$itemtype]      = $prefix.$table;
         $CFG_GLPI['glpiitemtypetables'][$prefix.$table] = $itemtype;
         return $prefix.$table;
      }
   }


   /**
    * Return ItemType  for a table
    *
    * @param string $table table name
    *
    * @return string itemtype corresponding to a table name parameter
    */
   public function getItemTypeForTable($table) {
      global $CFG_GLPI;

      if (isset($CFG_GLPI['glpiitemtypetables'][$table])) {
         return $CFG_GLPI['glpiitemtypetables'][$table];

      } else {
         $inittable = $table;
         $table     = str_replace("glpi_", "", $table);
         $prefix    = "";
         $pref2     = NS_GLPI;

         if (preg_match('/^plugin_([a-z0-9]+)_/', $table, $matches)) {
            $table  = preg_replace('/^plugin_[a-z0-9]+_/', '', $table);
            $prefix = "Plugin".Toolbox::ucfirst($matches[1]);
            $pref2  = NS_PLUG . ucfirst($matches[1]) . '\\';
         }

         if (strstr($table, '_')) {
            $split = explode('_', $table);

            foreach ($split as $key => $part) {
               $split[$key] = Toolbox::ucfirst($this->getSingular($part));
            }
            $table = implode('_', $split);

         } else {
            $table = Toolbox::ucfirst($this->getSingular($table));
         }

         $itemtype = $prefix.$table;
         // Get real existence of itemtype
         if (($item = $this->getItemForItemtype($itemtype))) {
            $itemtype                                   = get_class($item);
            $CFG_GLPI['glpiitemtypetables'][$inittable] = $itemtype;
            $CFG_GLPI['glpitablesitemtype'][$itemtype]  = $inittable;
            return $itemtype;
         }
         // Namespaced item
         $itemtype = $pref2 . str_replace('_', '\\', $table);
         if (($item = $this->getItemForItemtype($itemtype))) {
            $itemtype                                   = get_class($item);
            $CFG_GLPI['glpiitemtypetables'][$inittable] = $itemtype;
            $CFG_GLPI['glpitablesitemtype'][$itemtype]  = $inittable;
            return $itemtype;
         }
         return "UNKNOWN";
      }
   }


   /**
    * Get new item objet for an itemtype
    *
    * @param string $itemtype itemtype
    *
    * @return object|false itemtype instance or false if class does not exists
    */
   public function getItemForItemtype($itemtype) {
      if ($itemtype === 'Event') {
         //to avoid issues when pecl-event is installed...
         $itemtype = 'Glpi\\Event';
      }
      if (class_exists($itemtype)) {
         return new $itemtype();
      }
      return false;
   }

   /**
    * Count the number of elements in a table.
    *
    * @param string|array $table     table name(s)
    * @param string|array $condition condition to use (default '') or array of criteria
    *
    * @return integer Number of elements in table
    */
   public function countElementsInTable($table, $condition = "") {
      global $DB;

      if (!is_array($table)) {
         $table = [$table];
      }

      /*foreach ($table as $t) {
         if (!$DB->tableExists($table)) {
            throw new \RuntimeException("$t is not an existing table!");
         }
      }*/

      if (!is_array($condition)) {
         if (empty($condition)) {
            $condition = [];
         } else {
            //TODO throw a warning?
            $condition = ['WHERE' => $condition]; // Deprecated use case
         }
      }
      $condition['COUNT'] = 'cpt';

      $row = $DB->request($table, $condition)->next();
      return ($row ? (int)$row['cpt'] : 0);
   }

   /**
    * Count the number of elements in a table.
    *
    * @param string|array $table        table name(s)
    * @param string       $field        field name
    * @param string|array $condition condition to use (default '') or array of criteria
    *
    * @return int nb of elements in table
    */
   public function countDistinctElementsInTable($table, $field, $condition = "") {
      global $DB;

      if (!is_array($condition)) {
         if (empty($condition)) {
            $condition = [];
         } else {
            $condition = ['WHERE' => $condition]; // Deprecated use case
         }
      }
      $condition['COUNT'] = 'cpt';
      $condition['SELECT DISTINCT'] = $field;

      return $this->countElementsInTable($table, $condition);
   }

   /**
    * Count the number of elements in a table for a specific entity
    *
    * @param string|array $table        table name(s)
    * @param string|array $condition condition to use (default '') or array of criteria
    *
    * @return integer Number of elements in table
    */
   public function countElementsInTableForMyEntities($table, $condition = '') {

      /// TODO clean it / maybe include when review of SQL requests
      $itemtype = $this->getItemTypeForTable($table);
      $item     = new $itemtype();

      $criteria = $this->getEntitiesRestrictCriteria($table, '', '', $item->maybeRecursive());
      if (is_array($condition)) {
         $criteria = array_merge($condition, $criteria);
      } else if ($condition) {
         $criteria[] = $condition;
      }
      return $this->countElementsInTable($table, $criteria);
   }


   /**
    * Count the number of elements in a table for a specific entity
    *
    * @param string|array $table     table name(s)
    * @param integer      $entity    the entity ID
    * @param string|array $condition condition to use (default '') or array of criteria
    * @param boolean      $recursive Whether to recurse or not. If true, will be conditionned on item recursivity
    *
    * @return int nb of elements in table
    */
   public function countElementsInTableForEntity($table, $entity, $condition = '', $recursive = true) {

      /// TODO clean it / maybe include when review of SQL requests
      $itemtype = $this->getItemTypeForTable($table);
      $item     = new $itemtype();

      if ($recursive) {
         $recursive = $item->maybeRecursive();
      }

      $criteria = $this->getEntitiesRestrictCriteria($table, '', $entity, $recursive);
      if (is_array($condition)) {
         $criteria = array_merge($condition, $criteria);
      } else if ($condition) {
         $criteria[] = $condition;
      }
      return $this->countElementsInTable($table, $criteria);
   }

   /**
    * Get datas from a table in an array :
    * CAUTION TO USE ONLY FOR SMALL TABLES OR USING A STRICT CONDITION
    *
    * @param string       $table     Table name
    * @param string|array $condition Condition to use (default '') or array of criteria
    * @param boolean      $usecache  Use cache (false by default)
    * @param string       $order     Result order (default '')
    *
    * @return array containing all the datas
    */
   public function getAllDataFromTable($table, $condition = '', $usecache = false, $order = '') {
      global $DB;

      static $cache = [];

      if (empty($condition) && empty($order) && $usecache && isset($cache[$table])) {
         return $cache[$table];
      }

      $data = [];

      if (!is_array($condition)) {
         if (empty($condition)) {
            $condition = [];
         } else {
            $condition = ['WHERE' => $condition]; // Deprecated use case
         }
      }

      if (!empty($order)) {
         $condition['ORDER'] = $order; // Deprecated use case
      }

      $iterator = $DB->request($table, $condition);

      while ($row = $iterator->next()) {
         $data[$row['id']] = $row;
      }

      if (empty($condition) && empty($order) && $usecache) {
         $cache[$table] = $data;
      }
      return $data;
   }

   /**
    * Determine if an index exists in database
    *
    * @param string $table table of the index
    * @param string $field name of the index
    *
    * @return boolean
    */
   public function isIndex($table, $field) {
      global $DB;

      if (!$DB->tableExists($table)) {
         trigger_error("Table $table does not exists", E_USER_WARNING);
         return false;
      }

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
    */
   public function getEntitiesRestrictRequest($separator = "AND", $table = "", $field = "", $value = '',
                                       $is_recursive = false, $complete_request = false) {

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

      if (empty($field)) {
         if ($table == 'glpi_entities') {
            $field = "id";
         } else {
            $field = "entities_id";
         }
      }
      if (empty($table)) {
         $field = "`$field`";
      } else {
         $field = "`$table`.`$field`";
      }

      $query .= "$field";

      if (is_array($value)) {
         $query .= " IN ('" . implode("','", $value) . "') ";
      } else {
         if (strlen($value) == 0 && !isset($_SESSION['glpiactiveentities_string'])) {
            //set root entity if not set
            $value = 0;
         }
         if (strlen($value) == 0) {
            $query .= " IN (".$_SESSION['glpiactiveentities_string'].") ";
         } else {
            $query .= " = '$value' ";
         }
      }

      if ($is_recursive) {
         $ancestors = [];
         if (isset($_SESSION['glpiactiveentities']) && $value == $_SESSION['glpiactiveentities']) {
            $ancestors = $_SESSION['glpiparententities'];
         } else {
            if (is_array($value)) {
               $ancestors = $this->getAncestorsOf("glpi_entities", $value);
               $ancestors = array_diff($ancestors, $value);

            } else if (strlen($value) == 0 && isset($_SESSION['glpiparententities'])) {
               $ancestors = $_SESSION['glpiparententities'];
            } else {
               $ancestors = $this->getAncestorsOf("glpi_entities", $value);
            }
         }

         if (count($ancestors)) {
            if ($table == 'glpi_entities') {
               $query .= " OR $field IN ('" . implode("','", $ancestors) . "')";
            } else {
               $recur = (empty($table) ? '`is_recursive`' : "`$table`.`is_recursive`");
               $query .= " OR ($recur='1' AND $field IN ('" . implode("','", $ancestors) . "'))";
            }
         }
      }
      $query .= " ) ";

      return $query;
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
    */
   public function getEntitiesRestrictCriteria($table = '', $field = '', $value = '',
                                       $is_recursive = false, $complete_request = false) {

      // !='0' needed because consider as empty
      if (!$complete_request
         && ($value != '0')
         && empty($value)
         && isset($_SESSION['glpishowallentities'])
         && $_SESSION['glpishowallentities']) {

         return [];
      }

      if (empty($field)) {
         if ($table == 'glpi_entities') {
            $field = "id";
         } else {
            $field = "entities_id";
         }
      }
      if (!empty($table)) {
         $field = "$table.$field";
      }

      if (!is_array($value) && strlen($value) == 0) {
         $value = $_SESSION['glpiactiveentities'];
      }

      $crit = [$field => $value];

      if ($is_recursive === 'auto' && !empty($table) && $table != 'glpi_entities') {
         $item = $this->getItemForItemtype($this->getItemTypeForTable($table));
         if ($item !== false) {
            $is_recursive = $item->maybeRecursive();
         }
      }

      if ($is_recursive) {
         $ancestors = [];
         if (is_array($value)) {
            foreach ($value as $val) {
               $ancestors = array_unique(array_merge($this->getAncestorsOf('glpi_entities', $val),
                     $ancestors));
            }
            $ancestors = array_diff($ancestors, $value);

         } else if (strlen($value) == 0) {
            $ancestors = $_SESSION['glpiparententities'];

         } else {
            $ancestors = $this->getAncestorsOf('glpi_entities', $value);
         }

         if (count($ancestors)) {
            if ($table == 'glpi_entities') {
               if (!is_array($value)) {
                  $value = [$value => $value];
               }
               $crit = ['OR' => [$field => $value + $ancestors]];
            } else {
               $recur = (empty($table) ? 'is_recursive' : "$table.is_recursive");
               $crit = ['OR' => [$field => $value,
                                 'AND' => [$recur => 1,
                                          $field => $ancestors]]];
            }
         }
      }
      return $crit;
   }

   /**
    * Get the sons of an item in a tree dropdown. Get datas in cache if available
    *
    * @param $table  string   table name
    * @param $IDf    integer  The ID of the father
    *
    * @return array of IDs of the sons
    */
   public function getSonsOf($table, $IDf) {
      global $DB;

      $ckey = $table . '_sons_cache_' . $IDf;
      $sons = [];

      if (Toolbox::useCache()) {
         if (apcu_exists($ckey)) {
            $sons = apcu_fetch($ckey);
            if ($sons) {
               return $sons;
            }
         }
      }

      $parentIDfield = $this->getForeignKeyFieldForTable($table);
      $use_cache     = $DB->fieldExists($table, "sons_cache");

      if ($use_cache
         && ($IDf > 0)) {

         $iterator = $DB->request([
            'SELECT' => 'sons_cache',
            'FROM'   => $table,
            'WHERE'  => ['id' => $IDf]
         ]);

         if (count($iterator) > 0) {
            $db_sons = trim($iterator->current()['sons_cache']);
            if (!empty($db_sons)) {
               $sons = $this->importArrayFromDB($db_sons, true);
            }
         }
      }

      if (!count($sons)) {
         // IDs to be present in the final array
         $sons[$IDf] = "$IDf";
         // current ID found to be added
         $found = [];
         // First request init the  varriables
         $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => $table,
            'WHERE'  => [$parentIDfield => $IDf],
            'ORDER'  => 'name'
         ]);

         if (count($iterator) > 0) {
            while ($row = $iterator->next()) {
               $sons[$row['id']]    = $row['id'];
               $found[$row['id']]   = $row['id'];
            }
         }

         // Get the leafs of previous found item
         while (count($found) > 0) {
            // Get next elements
            $iterator = $DB->request([
               'SELECT' => 'id',
               'FROM'   => $table,
               'WHERE'  => [$parentIDfield => $found]
            ]);

            // CLear the found array
            unset($found);
            $found = [];

            if (count($iterator) > 0) {
               while ($row = $iterator->next()) {
                  if (!isset($sons[$row['id']])) {
                     $sons[$row['id']]    = $row['id'];
                     $found[$row['id']]   = $row['id'];
                  }
               }
            }
         }

         // Store cache data in DB
         if ($use_cache
            && ($IDf > 0)) {

            $query = "UPDATE `$table`
                     SET `sons_cache`='".exportArrayToDB($sons)."'
                     WHERE `id` = '$IDf';";
            $DB->query($query);
         }
      }

      if (Toolbox::useCache()) {
         apcu_store($ckey, $sons);
      }

      return $sons;
   }

   /**
    * Get the ancestors of an item in a tree dropdown
    *
    * @param string       $table    Table name
    * @param array|string $items_id The IDs of the items
    *
    * @return array of IDs of the ancestors
    */
   public function getAncestorsOf($table, $items_id) {
      global $DB;

      $ckey = $table . '_ancestors_cache_';
      if (is_array($items_id)) {
         $ckey .= implode('|', $items_id);
      } else {
         $ckey .= $items_id;
      }
      $ancestors = [];

      if (Toolbox::useCache()) {

         if (apcu_exists($ckey)) {
            $ancestors = apcu_fetch($ckey);
            if ($ancestors) {
               return $ancestors;
            }
         }
      }

      // IDs to be present in the final array
      $parentIDfield = $this->getForeignKeyFieldForTable($table);
      $use_cache     = $DB->fieldExists($table, "ancestors_cache");

      if (!is_array($items_id)) {
         $items_id = (array)$items_id;
      }

      if ($use_cache) {
         $iterator = $DB->request([
            'SELECT' => ['id', 'ancestors_cache', $parentIDfield],
            'FROM'   => $table,
            'WHERE'  => ['id' => $items_id]
         ]);

         while ($row = $iterator->next()) {
            if ($row['id'] > 0) {
               $rancestors = $row['ancestors_cache'];
               $parent     = $row[$parentIDfield];

               // Return datas from cache in DB
               if (!empty($rancestors)) {
                  $ancestors = array_replace($ancestors, importArrayFromDB($rancestors, true));
               } else {
                  $loc_id_found = [];
                  // Recursive solution for table with-cache
                  if ($parent > 0) {
                     $loc_id_found = $this->getAncestorsOf($table, $parent);
                  }

                  // ID=0 only exists for Entities
                  if (($parent > 0)
                     || ($table == 'glpi_entities')) {
                     $loc_id_found[$parent] = $parent;
                  }

                  // Store cache datas in DB
                  $query = "UPDATE `$table`
                        SET `ancestors_cache` = '".exportArrayToDB($loc_id_found)."'
                        WHERE `id` = '".$row['id']."'";
                  $DB->query($query);

                  $ancestors = array_replace($ancestors, $loc_id_found);
               }
            }
         }
      } else {

         // Get the ancestors
         // iterative solution for table without cache
         foreach ($items_id as $id) {
            $IDf = $id;
            while ($IDf > 0) {
               // Get next elements
               $iterator = $DB->request([
                  'SELECT' => [$parentIDfield],
                  'FROM'   => $table,
                  'WHERE'  => ['id' => $IDf]
               ]);

               if (count($iterator) > 0) {
                  $result = $iterator->current();
                  $IDf = $result[$parentIDfield];
               } else {
                  $IDf = 0;
               }

               if (!isset($ancestors[$IDf])
                     && (($IDf > 0) || ($table == 'glpi_entities'))) {
                  $ancestors[$IDf] = $IDf;
               } else {
                  $IDf = 0;
               }
            }
         }
      }

      if (Toolbox::useCache()) {
         apcu_store($ckey, $ancestors);
      }

      return $ancestors;
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
      return $this->getAncestorsOf($table, $IDf) + $this->getSonsOf($table, $IDf);
   }
}
