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
 *  Database iterator class for Mysql
**/
class DBmysqlIterator implements Iterator, Countable {
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

   // Current position
   private $position = 0;

   //Known query operators
   private $allowed_operators = [
      '=',
      '!=',
      '<',
      '<=',
      '>',
      '>=',
      '<>',
      'LIKE',
      'REGEXP',
      'NOT LIKE',
      'NOT REGEX',
      '&',
      '|'
   ];

   /**
    * Constructor
    *
    * @param DBmysql $dbconnexion Database Connnexion (must be a CommonDBTM object)
    *
    * @return void
    */
   function __construct ($dbconnexion) {
      $this->conn = $dbconnexion;
   }

   /**
    * Executes the query
    *
    * @param string|array $table       Table name (optional when $crit have FROM entry)
    * @param string|array $crit        Fields/values, ex array("id"=>1), if empty => all rows (default '')
    * @param boolean      $debug       To log the request (default false)
    *
    * @return DBmysqlIterator
    */
   function execute ($table, $crit = "", $debug = false) {
      $this->buildQuery($table, $crit, $debug);
      $this->res = ($this->conn ? $this->conn->query($this->sql) : false);
      $this->position = 0;
      return $this;
   }

   /**
    * Builds the query
    *
    * @param string|array $table       Table name (optional when $crit have FROM entry)
    * @param string|array $crit        Fields/values, ex array("id"=>1), if empty => all rows (default '')
    * @param boolean      $log         To log the request (default false)
    *
    * @return void
    */
   function buildQuery ($table, $crit = "", $log = false) {
      $this->sql = null;
      $this->res = false;
      $this->parameters = [];

      $is_legacy = false;

      if (is_string($table) && strpos($table, " ")) {
         $names = preg_split('/ AS /i', $table);
         if (isset($names[1]) && strpos($names[1], ' ') || !isset($names[1]) || strpos($names[0], ' ')) {
            $is_legacy = true;
         }
      }

      if ($is_legacy) {
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
         $dfield    = "";
         $orderby  = null;
         $limit    = 0;
         $start    = 0;
         $where    = '';
         $count    = '';
         $join     = '';
         $groupby  = '';
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
                     $dfield = $val;
                     unset($crit[$key]);
                     break;

                  case 'COUNT' :
                     $count = $val;
                     unset($crit[$key]);
                     break;

                  case 'ORDER' :
                  case 'ORDERBY' :
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

                  case 'GROUP' :
                  case 'GROUPBY' :
                     $groupby = $val;
                     unset($crit[$key]);
                     break;

                  case 'LEFT JOIN' :
                  case 'RIGHT JOIN' :
                  case 'INNER JOIN' :
                     if (is_array($val)) {
                        $jointype = null;
                        switch ($key) {
                           case 'JOIN':
                           case 'LEFT JOIN':
                              $jointype = 'LEFT';
                              break;
                           case 'INNER JOIN':
                              $jointype = 'INNER';
                              break;
                           case 'RIGHT JOIN':
                              $jointype = 'RIGHT';
                              break;
                        }
                        foreach ($val as $jointable => $joincrit) {
                           $joinExpressionKeys = ['FKEY' => null, 'ON' => null];
                           $subquery = array_diff_key($joincrit, $joinExpressionKeys);
                           if (count($subquery)) {
                              $subquery = new QuerySubquery($subquery);
                              $alias = DBMysql::quoteName($jointable);
                              $joincrit = array_intersect_key($joincrit, $joinExpressionKeys);
                              $join .= " $jointype JOIN (" . $subquery->getSubQuery() . ") AS $alias ON (" . $this->analyseCrit($joincrit) . ")";
                           } else {
                              $join .= " $jointype JOIN " .  DBmysql::quoteName($jointable) . " ON (" . $this->analyseCrit($joincrit) . ")";
                           }
                        }
                     } else {
                        trigger_error("BAD JOIN, value must be [ table => criteria ]", E_USER_ERROR);
                     }
                     unset($crit[$key]);
                     break;
               }
            }
         }

         $this->sql = 'SELECT ';
         $first = true;

         //check DISTINCT is not an array
         if (is_array($dfield)) {
            Toolbox::logWarning('DISTINCT selection can only take one field!');
            if (!is_array($field)) {
               $field = $dfield;
            } else {
               $field = array_merge($field, $dfield);
            }
            $dfield = '';
         }

         // SELECT field list
         if ($count) {
            $this->sql .= 'COUNT(';
            if (!empty($dfield)) {
               $this->sql .= "DISTINCT " . DBmysql::quoteName($dfield);
            } else if (!empty($field) && !is_array($field)) {
               $this->sql .= "" . DBmysql::quoteName($field);
            } else {
               $this->sql .= "*";
            }
            $this->sql .= ") AS $count";
            $first = false;
         }
         if (!$count || $count && is_array($field)) {
            if (empty($field) && empty($dfield)) {
               $this->sql .= '*';
            }
            if (!empty($dfield)) {
               $this->sql .= 'DISTINCT ' . DBmysql::quoteName($dfield);
               $first = false;
            }
            if (!empty($field)) {
               if (!is_array($field)) {
                  $field = [$field];
               }
               foreach ($field as $t => $f) {
                  if ($first) {
                     $first = false;
                  } else {
                     $this->sql .= ', ';
                  }
                  $this->sql .= $this->handleFields($t, $f);
               }
            }
         }

         // FROM table list
         if (is_array($table)) {
            if (count($table)) {
               $table = array_map([DBmysql::class, 'quoteName'], $table);
               $this->sql .= ' FROM '.implode(", ", $table);
            } else {
               trigger_error("Missing table name", E_USER_ERROR);
            }
         } else if ($table) {
            if ($table instanceof \QueryUnion) {
               $table = $table->getQuery();
            } else {
               $table = DBmysql::quoteName($table);
            }
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

         // GROUP BY field list
         if (is_array($groupby)) {
            if (count($groupby)) {
               $groupby = array_map([DBmysql::class, 'quoteName'], $groupby);
               $this->sql .= ' GROUP BY '.implode(", ", $groupby);
            } else {
               trigger_error("Missing group by field", E_USER_ERROR);
            }
         } else if ($groupby) {
            $groupby = DBmysql::quoteName($groupby);
            $this->sql .= " GROUP BY $groupby";
         }

         // ORDER BY
         if ($orderby !== null) {
            $this->sql .= $this->handleOrderClause($orderby);
         }

         //LIMIT & OFFSET
         $this->sql .= $this->handleLimits($limit, $start);
      }

      if ($log == true || defined('GLPI_SQL_DEBUG') && GLPI_SQL_DEBUG == true) {
         Toolbox::logSqlDebug("Generated query:", $this->getSql());
      }
   }

   /**
    * Handle "ORDER BY" SQL clause
    *
    * @param string|array $clause Clause parameters
    *
    * @reutn string
    */
   public function handleOrderClause($clause) {
      if (!is_array($clause)) {
         $clause = [$clause];
      }

      $cleanorderby = [];
      foreach ($clause as $o) {
         $fields = explode(',', $o);
         foreach ($fields as $field) {
            $new = '';
            $tmp = explode(' ', trim($field));
            $new .= DBmysql::quoteName($tmp[0]);
            // ASC OR DESC added
            if (isset($tmp[1]) && in_array($tmp[1], ['ASC', 'DESC'])) {
               $new .= ' '.$tmp[1];
            }
            $cleanorderby[] = $new;
         }
      }

      return " ORDER BY ".implode(", ", $cleanorderby);
   }


   /**
    * Handle LIMIT and OFFSET
    *
    * @param integer $limit  SQL LIMIT
    * @param integer $offset Start OFFSET (defaults to null)
    *
    * @return string
    */
   public function handleLimits($limit, $offset = null) {
      $limits = '';
      if (is_numeric($limit) && ($limit > 0)) {
         $limits = " LIMIT $limit";
         if (is_numeric($offset) && ($offset > 0)) {
            $limits .= " OFFSET $offset";
         }
      }
      return $limits;
   }

   /**
    * Handle fields
    *
    * @param integer|string $t Table name or function
    * @param array|string   $f Field(s) name(s)
    *
    * @return void
    */
   private function handleFields($t, $f) {
      if (is_numeric($t)) {
         if ($f instanceof \QueryUnion) {
            return $f->getQuery();
         } else {
            return DBmysql::quoteName($f);
         }
      } else {
         switch ($t) {
            case 'COUNT DISTINCT':
            case 'DISTINCT COUNT':
               if (is_array($f)) {
                  $sub_count = [];
                  foreach ($f as $sub_f) {
                     $sub_count[] = $this->handleFieldsAlias("COUNT(DISTINCT", $sub_f, ')');
                  }
                  return implode(", ", $sub_count);
               } else {
                  return $this->handleFieldsAlias("COUNT(DISTINCT", $f, ')');
               }
               break;
            case 'COUNT':
            case 'SUM':
            case 'AVG':
            case 'MAX':
            case 'MIN':
               if (is_array($f)) {
                  $sub_aggr = [];
                  foreach ($f as $sub_f) {
                     $sub_aggr[] = $this->handleFields($t, $sub_f);
                  }
                  return implode(", ", $sub_aggr);
               } else {
                  return $this->handleFieldsAlias($t, $f);
               }
               break;
            default:
               if (is_array($f)) {
                  $t = DBmysql::quoteName($t);
                  $f = array_map([DBmysql::class, 'quoteName'], $f);
                  return "$t." . implode(", $t.", $f);
               } else {
                  $t = DBmysql::quoteName($t);
                  $f = ($f == '*' ? $f : DBmysql::quoteName($f));
                  return "$t.$f";
               }
               break;
         }
      }
   }

   /**
    * Handle alias on fields
    *
    * @param string $t      Function name
    * @param string $f      Field name (with alias if any)
    * @param string $suffix Suffix to append, defaults to ''
    *
    * @return string
    */
   private function handleFieldsAlias($t, $f, $suffix = '') {
      $names = preg_split('/ AS /i', $f);
      $expr  = "$t(".$this->handleFields(0, $names[0])."$suffix)";
      if (isset($names[1])) {
         $expr .= " AS {$names[1]}";
      }

      return $expr;
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
   public function analyseCrit ($crit, $bool = "AND") {

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
            // no key and direct expression
            if ($value instanceof QueryExpression) {
               $ret .= $value->getValue();
            } else {
               // No Key case => recurse.
               $ret .= "(" . $this->analyseCrit($value, $bool) . ")";
            }

         } else if (($name === "OR") || ($name === "AND")) {
            // Binary logical operator
            $ret .= "(" . $this->analyseCrit($value, $name) . ")";

         } else if ($name === "NOT") {
            // Uninary logicial operator
            $ret .= " NOT (" . $this->analyseCrit($value, "AND") . ")";

         } else if ($name === "FKEY" || $name === 'ON') {
            // Foreign Key condition
            $ret .= $this->analyseFkey($value);
         } else if ($name === 'RAW') {
            $key = key($value);
            $value = current($value);
            $ret .= '((' . $key . ') ' . $this->analyzeCriterion($value) . ')';
         } else {
            $ret .= DBmysql::quoteName($name) . ' ' . $this->analyzeCriterion($value);
         }
      }
      return $ret;
   }

   /**
    * Analyze a criterion
    *
    * @since 9.3.1
    *
    * @param mixed $value Value to analyze
    *
    * @return string
    */
   private function analyzeCriterion($value) {
      $ret = null;
      if (is_array($value)) {
         if (count($value) == 2 && isset($value[0]) && $this->isOperator($value[0])) {
            $ret = $value[0] . ' ' . DBmysql::quoteValue($value[1]);
         } else {
            if (!count($value)) {
               throw new \RuntimeException('Empty IN are not allowed');
            }
            // Array of Values
            foreach ($value as $k => $v) {
               $value[$k] = DBmysql::quoteValue($v);
            }
            $ret = 'IN (' . implode(', ', $value) . ')';
         }
      } else if (is_null($value) || is_string($value) && strtolower($value) === 'null') {
         // NULL condition
         $ret = 'IS NULL';
      } else if ($value instanceof QueryUnion) {
         $ret = $value->getQuery();
      } else if ($value instanceof QuerySubQuery) {
         $ret = $value->getOperator() . ' (' . $value->getSubQuery() . ')';
      } else {
         $ret = "= " . DBmysql::quoteValue($value);
      }
      return $ret;
   }

   /**
    * Analyse foreign keys
    *
    * @param mixed $values Values for Foreign keys
    *
    * @return string
    */
   private function analyseFkey($values) {
      if (is_array($values)) {
         $keys = array_keys($values);
         if (count($values) == 2) {
            $t1 = $keys[0];
            $f1 = $values[$t1];
            $t2 = $keys[1];
            $f2 = $values[$t2];
            return (is_numeric($t1) ? DBmysql::quoteName($f1) : DBmysql::quoteName($t1) . '.' . DBmysql::quoteName($f1)) . ' = ' .
                     (is_numeric($t2) ? DBmysql::quoteName($f2) : DBmysql::quoteName($t2) . '.' . DBmysql::quoteName($f2));
         } else if (count($values) == 3) {
            $condition = array_pop($values);
            $fkey = $this->analyseFkey($values);
            return $fkey . ' ' . key($condition) . ' ' . $this->analyseCrit(current($condition));
         }
      }
      trigger_error("BAD FOREIGN KEY, should be [ table1 => key1, table2 => key2 ] or [ table1 => key1, table2 => key2, [criteria]]", E_USER_ERROR);
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
      $this->position = 0;
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
      return (isset($this->row["id"]) ? $this->row["id"] : $this->position - 1);
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
      ++$this->position;
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
    * @return integer
    */
   public function numrows() {
      return ($this->res ? $this->conn->numrows($this->res) : 0);
   }

   /**
    * Number of rows on a result
    *
    * @since 9.2
    *
    * @return integer
    */
   public function count() {
      return ($this->res ? $this->conn->numrows($this->res) : 0);
   }

   /**
    * Do we have an operator?
    *
    * @param string $value Value to check
    *
    * @return boolean
    */
   public function isOperator($value) {
      return in_array($value, $this->allowed_operators, true);
   }
}
