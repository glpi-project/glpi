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
 *  Database iterator class for Mysql
**/
class DBmysqlIterator implements Iterator {
   /**
    * DBmysql object
    * @var DBmysql
    */
   private $conn;
   // Current SQL query
   private $sql;
   // Current result
   private $res = false;
   // Current row
   private $row;

   /**
    * Constructor
    *
    * @param DBmysql      $dbconnexion Database Connnexion (must be a CommonDBTM object)
    * @param string|array $table       Table name (optional when $crit have FROM entry)
    * @param string|array $crit        Fields/values, ex array("id"=>1), if empty => all rows (default '')
    * @param boolean      $debug       To log the request (default false)
    *
    * @return void
    */
   function __construct ($dbconnexion, $table, $crit="", $debug=false) {

      $this->conn = $dbconnexion;
      if (is_string($table) && strpos($table, " ")) {
         //if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
         //   trigger_error("Deprecated usage of SQL in DB/request (full query)", E_USER_DEPRECATED);
         //}
         $this->sql = $table;
      } else {
         // Modern way
         if (is_array($table) && isset($table['FROM'])) {
            // Shift the args
            $debug = $crit;
            $crit  = $table;
            $table = $crit['FROM'];
            unset($crit['FROM']);
         }

         // Check field, orderby, limit, start in criterias
         $field    = "";
         $orderby  = "";
         $limit    = 0;
         $start    = 0;
         $distinct = '';
         $where    = '';
         $count    = '';
         $join     = '';
         if (is_array($crit) && count($crit)) {
            foreach ($crit as $key => $val) {
               switch ((string)$key) {
                  case 'SELECT' :
                  case 'FIELDS' :
                     $field = $val;
                     unset($crit[$key]);
                     break;

                  case 'SELECT DISTINCT' :
                  case 'DISTINCT FIELDS' :
                     $field = $val;
                     $distinct = "DISTINCT";
                     unset($crit[$key]);
                     break;

                  case 'COUNT' :
                     $count = $val;
                     unset($crit[$key]);
                     break;

                  case 'ORDER' :
                     $orderby = $val;
                     unset($crit[$key]);
                     break;

                  case 'LIMIT' :
                     $limit = $val;
                     unset($crit[$key]);
                     break;

                  case 'START' :
                     $start = $val;
                     unset($crit[$key]);
                     break;

                  case 'WHERE' :
                     $where = $val;
                     unset($crit[$key]);
                     break;

                  case 'JOIN' : // deprecated
                  case 'LEFT JOIN' :
                  case 'INNER JOIN' :
                     if (is_array($val)) {
                        $jointype = ($key == 'INNER JOIN' ? 'INNER' : 'LEFT');
                        foreach ($val as $jointable => $joincrit) {
                           $join .= " $jointype JOIN " .  self::quoteName($jointable) . " ON (" . $this->analyseCrit($joincrit) . ")";
                        }
                     } else {
                        trigger_error("BAD JOIN, value must be [ table => criteria ]", E_USER_ERROR);
                     }
                     unset($crit[$key]);
                     break;
               }
            }
         }

         // SELECT field list
         if ($count) {
            $this->sql = "SELECT COUNT(*) AS $count";
         } else if (is_array($field)) {
            $this->sql = "";
            foreach ($field as $t => $f) {
               if (is_numeric($t)) {
                  $this->sql .= (empty($this->sql) ? 'SELECT ' : ', ') . self::quoteName($f);
               } else if (is_array($f)) {
                  $t = self::quoteName($t);
                  $f = array_map([__CLASS__, 'quoteName'], $f);
                  $this->sql .= (empty($this->sql) ? "SELECT $t." : ",$t.") . implode(", $t.", $f);
               } else {
                  $t = self::quoteName($t);
                  $f = ($f == '*' ? $f : self::quoteName($f));
                  $this->sql .= (empty($this->sql) ? 'SELECT ' : ', ') . "$t.$f";
               }
            }
         } else if (empty($field)) {
            $this->sql = "SELECT *";
         } else {
            $this->sql = "SELECT $distinct " . self::quoteName($field);
         }

         // FROM table list
         if (is_array($table)) {
            if (count($table)) {
               $table = array_map([__CLASS__, 'quoteName'], $table);
               $this->sql .= ' FROM '.implode(", ", $table);
            } else {
               trigger_error("Missing table name", E_USER_ERROR);
            }
         } else if ($table) {
            $table = self::quoteName($table);
            $this->sql .= " FROM $table";
         } else {
            /*
             * TODO filter with if ($where || !empty($crit)) {
             * but not usefull for now, as we CANNOT write somthing like "SELECT NOW()"
             */
            trigger_error("Missing table name", E_USER_ERROR);
         }

         // JOIN
         $this->sql .= $join;

         // WHERE criteria list
         if (!empty($crit)) {
            $this->sql .= " WHERE ".$this->analyseCrit($crit);
         } else if ($where) {
            $this->sql .= " WHERE ".$this->analyseCrit($where);
         }

         // ORDER BY
         if (is_array($orderby)) {
            $cleanorderby = array();
            foreach ($orderby as $o) {
               $new = '';
               $tmp = explode(' ', $o);
               $new .= self::quoteName($tmp[0]);
               // ASC OR DESC added
               if (isset($tmp[1]) && in_array($tmp[1], array('ASC', 'DESC'))) {
                  $new .= ' '.$tmp[1];
               }
               $cleanorderby[] = $new;
            }

            $this->sql .= " ORDER BY ".implode(", ", $cleanorderby);
         } else if (!empty($orderby)) {
            $this->sql .= " ORDER BY ";
            $tmp = explode(' ', $orderby);
            $this->sql .= self::quoteName($tmp[0]);
            // ASC OR DESC added
            if (isset($tmp[1]) && in_array($tmp[1], array('ASC', 'DESC'))) {
               $this->sql .= ' '.$tmp[1];
            }
         }

         if (is_numeric($limit) && ($limit > 0)) {
            $this->sql .= " LIMIT $limit";
            if (is_numeric($start) && ($start > 0)) {
               $this->sql .= " OFFSET $start";
            }
         }
      }
      if ($debug) {
         Toolbox::logDebug("Generated query:", $this->getSql());
      }
      $this->res = ($this->conn ? $this->conn->query($this->sql) : false);
   }


   /**
    * Quote field name
    *
    * @since 9.1
    *
    * @param string $name of field to quote (or table.field)
    *
    * @return string
    */
   private static function quoteName($name) {
      if (strpos($name, '.')) {
         $n = explode('.', $name, 2);
         return self::quoteName($n[0]) . '.' . self::quoteName($n[1]);
      }
      return ($name[0]=='`' ? $name : "`$name`");
   }


   /**
    * Retrieve the SQL statement
    *
    * @since 9.1
    *
    * @return string
    */
   public function getSql() {
      return preg_replace('/ +/', ' ', $this->sql);
   }

   /**
    * Destructor
    *
    * @return void
    */
   function __destruct () {
      if ($this->res) {
         $this->conn->free_result($this->res);
      }
   }

   /**
    * Generate the SQL statement for a array of criteria
    *
    * @param string[] $crit Criteria
    * @param string   $bool Boolean operator (default AND)
    *
    * @return string
    */
   private function analyseCrit ($crit, $bool="AND") {
      static $operators = ['=', '<', '<=', '>', '>=', 'LIKE', 'REGEXP', 'NOT LIKE', 'NOT REGEX'];

      if (!is_array($crit)) {
         //if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
         //  trigger_error("Deprecated usage of SQL in DB/request (criteria)", E_USER_DEPRECATED);
         //}
         return $crit;
      }
      $ret = "";
      foreach ($crit as $name => $value) {
         if (!empty($ret)) {
            $ret .= " $bool ";
         }
         if (is_numeric($name)) {
            // No Key case => recurse.
            $ret .= "(" . $this->analyseCrit($value, $bool) . ")";

         } else if (($name === "OR") || ($name === "AND")) {
            // Binary logical operator
            $ret .= "(" . $this->analyseCrit($value, $name) . ")";

         } else if ($name === "NOT") {
            // Uninary logicial operator
            $ret .= " NOT (" . $this->analyseCrit($value, "AND") . ")";

         } else if ($name === "FKEY") {
            // Foreign Key condition
            if (is_array($value) && (count($value) == 2)) {
               reset($value);
               list($t1,$f1) = each($value);
               list($t2,$f2) = each($value);
               $ret .= (is_numeric($t1) ? self::quoteName($f1) : self::quoteName($t1) . '.' . self::quoteName($f1)) . ' = ' .
                       (is_numeric($t2) ? self::quoteName($f2) : self::quoteName($t2) . '.' . self::quoteName($f2));
            } else {
               trigger_error("BAD FOREIGN KEY, should be [ key1, key2 ]", E_USER_ERROR);
            }

         } else if (is_array($value)) {
            if (count($value) == 2 && in_array($value[0], $operators)) {
               if (is_numeric($value[1]) || preg_match("/^`.*?`$/", $value[1])) {
                  $ret .= self::quoteName($name) . " {$value[0]} {$value[1]}";
               } else {
                  $ret .= self::quoteName($name) . " {$value[0]} '{$value[1]}'";
               }
            } else {
               // Array of Values
               foreach ($value as $k => $v) {
                  if (!is_numeric($v)) {
                     $value[$k] = "'$v'";
                  }
               }
               $ret .= self::quoteName($name) . ' IN (' . implode(', ', $value) . ')';
            }
         } else if (is_null($value)) {
            // NULL condition
            $ret .= self::quoteName($name) . " IS NULL";

         } else if (is_numeric($value) || preg_match("/^`.*?`$/", $value)) {
            // Integer or field name
            $ret .= self::quoteName($name) . " = $value";

         } else {
            // String
            $ret .= self::quoteName($name) . " = '$value'";
         }
      }
      return $ret;
   }

   /**
    * Reset rows parsing (go to first offset) & provide first row
    *
    * @return string[]|null fetch_assoc() of first results row
    */
   public function rewind() {
      if ($this->res && $this->conn->numrows($this->res)) {
         $this->conn->data_seek($this->res, 0);
      }
      return $this->next();
   }

   /**
    * Provide actual row
    *
    * @return mixed
    */
   public function current() {
      return $this->row;
   }

   /**
    * Get current key value
    *
    * @return mixed
    */
   public function key() {
      return (isset($this->row["id"]) ? $this->row["id"] : 0);
   }

   /**
    * Return next row of query results
    *
    * @return string[]|null fetch_assoc() of first results row
    */
   public function next() {
      if (!$this->res) {
         return false;
      }
      $this->row = $this->conn->fetch_assoc($this->res);
      return $this->row;
   }

   /**
    * @todo phpdoc...
    *
    * @return boolean
    */
   public function valid() {
      return $this->res && $this->row;
   }

   /**
    * Number of rows on a result
    *
    * @return int
    */
   public function numrows() {
      return ($this->res ? $this->conn->numrows($this->res) : 0);
   }
}
