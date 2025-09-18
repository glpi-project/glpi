<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryParam;
use Glpi\DBAL\QuerySubQuery;

use function Safe\preg_replace;
use function Safe\preg_split;

/**
 *  Database iterator class for Mysql
 **/
class DBmysqlIterator implements SeekableIterator, Countable
{
    /**
     * DBmysql object
     * @var ?DBmysql
     */
    private $conn;
    // Current SQL query
    private $sql;
    // Current result
    private $res = false;

    /**
     * Total number of rows.
     * @var int
     */
    private $count;

    /**
     * Current row value.
     * @var mixed
     */
    private $row;

    /**
     * Current pointer position.
     * @var int
     */
    private $position = null;

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
        'LIKE BINARY',
        'REGEXP',
        'NOT LIKE',
        'NOT LIKE BINARY',
        'NOT REGEX',
        '&',
        '|',
        'IN',
        'NOT IN',
    ];

    /**
     * Constructor
     *
     * @param ?DBmysql $dbconnexion Database connection (must be a CommonDBTM object)
     *
     * @return void
     */
    public function __construct($dbconnexion)
    {
        $this->conn = $dbconnexion;
    }

    /**
     * Executes the query
     *
     * @param array   $criteria Query criteria
     *
     * @return DBmysqlIterator
     *
     * @since 11.0.0 The `$debug` parameter has been removed.
     */
    public function execute($criteria): self
    {
        $criteria = $this->convertOldRequestArgsToCriteria(func_get_args(), __METHOD__);

        $this->buildQuery($criteria);
        $this->res = $this->conn ? $this->conn->doQuery($this->sql) : false;
        $this->count = $this->res instanceof mysqli_result ? $this->conn->numrows($this->res) : 0;
        $this->setPosition(0);
        return $this;
    }

    /**
     * Builds the query
     *
     * @param array   $criteria Query criteria
     *
     * @return void
     *
     * @since 11.0.0 The `$log` parameter has been removed.
     */
    public function buildQuery($criteria): void
    {
        $criteria = $this->convertOldRequestArgsToCriteria(func_get_args(), __METHOD__);

        $this->sql = null;
        $this->res = false;

        $table = $criteria['FROM'] ?? null;
        unset($criteria['FROM']);

        // Check field, orderby, limit, start in criteria
        $field    = "";
        $distinct = false;
        $orderby  = null;
        $limit    = 0;
        $start    = 0;
        $where    = [];
        $count    = '';
        $join     = [];
        $groupby  = '';
        $having   = [];
        if (count($criteria)) {
            foreach ($criteria as $key => $val) {
                switch ((string) $key) {
                    case 'SELECT':
                    case 'FIELDS':
                        $field = $val;
                        unset($criteria[$key]);
                        break;

                    case 'DISTINCT':
                        if ($val) {
                            $distinct = true;
                        }
                        unset($criteria[$key]);
                        break;

                    case 'COUNT':
                        $count = $val;
                        unset($criteria[$key]);
                        break;

                    case 'ORDER':
                    case 'ORDERBY':
                        $orderby = $val;
                        unset($criteria[$key]);
                        break;

                    case 'LIMIT':
                        $limit = $val;
                        unset($criteria[$key]);
                        break;

                    case 'START':
                    case 'OFFSET':
                        $start = $val;
                        unset($criteria[$key]);
                        break;

                    case 'WHERE':
                        $where = $val;
                        unset($criteria[$key]);
                        break;

                    case 'HAVING':
                        $having = $val;
                        unset($criteria[$key]);
                        break;

                    case 'GROUP':
                    case 'GROUPBY':
                        $groupby = $val;
                        unset($criteria[$key]);
                        break;

                    case 'JOIN':
                    case 'LEFT JOIN':
                    case 'RIGHT JOIN':
                    case 'INNER JOIN':
                        $join[$key] = $val;
                        unset($criteria[$key]);
                        break;
                }
            }
        }

        $this->sql = 'SELECT ';
        $first = true;

        // SELECT field list
        if ($count) {
            $this->sql .= 'COUNT(';
            if ($distinct) {
                $this->sql .= 'DISTINCT ';
            }
            if (!empty($field) && !is_array($field)) {
                $this->sql .= DBmysql::quoteName($field);
            } else {
                if ($distinct) {
                    throw new LogicException("With COUNT and DISTINCT, you must specify exactly one field, or use 'COUNT DISTINCT'.");
                }
                $this->sql .= "*";
            }
            $this->sql .= ") AS $count";
            $first = false;
        }
        if (!$count || is_array($field)) {
            if ($distinct && !$count) {
                $this->sql .= 'DISTINCT ';
            }
            if (empty($field)) {
                $this->sql .= '*';
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
                $this->sql .= ' FROM ' . implode(", ", $table);
            } else {
                throw new LogicException("Missing table name.");
            }
        } elseif ($table) {
            if ($table instanceof AbstractQuery) {
                $query = $table;
                $table = $query->getQuery();
            } elseif ($table instanceof QueryExpression) {
                $table = $table->getValue();
            } else {
                $table = DBmysql::quoteName($table);
            }
            $this->sql .= " FROM $table";
        } else {
            /*
             * TODO filter with if ($where || !empty($criteria)) {
             * but not useful for now, as we CANNOT write something like "SELECT NOW()"
             */
            throw new LogicException("Missing table name.");
        }

        // JOIN
        if ($join !== []) {
            $this->sql .= $this->analyseJoins($join);
        }

        // WHERE criteria list
        if ($criteria !== []) {
            $this->sql .= " WHERE " . $this->analyseCrit($criteria);
            if ($where) {
                trigger_error(
                    'Criteria found both inside and outside "WHERE" key. Some of them will be ignored',
                    E_USER_WARNING
                );
            }
        } elseif ($where) {
            $this->sql .= " WHERE " . $this->analyseCrit($where);
        }

        // GROUP BY field list
        if (is_array($groupby)) {
            if (count($groupby)) {
                $groupby = array_map([DBmysql::class, 'quoteName'], $groupby);
                $this->sql .= ' GROUP BY ' . implode(", ", $groupby);
            } else {
                throw new LogicException("Missing group by field.");
            }
        } elseif ($groupby) {
            $groupby = DBmysql::quoteName($groupby);
            $this->sql .= " GROUP BY $groupby";
        }

        // HAVING criteria list
        if ($having) {
            $this->sql .= " HAVING " . $this->analyseCrit($having);
        }

        // ORDER BY
        if ($orderby !== null) {
            $this->sql .= $this->handleOrderClause($orderby);
        }

        //LIMIT & OFFSET
        $this->sql .= $this->handleLimits($limit, $start);
    }

    /**
     * Handle "ORDER BY" SQL clause
     *
     * @param string|array $clause Clause parameters
     *
     * @reutn string
     */
    public function handleOrderClause($clause)
    {
        if (!is_array($clause)) {
            $clause = [$clause];
        }

        $cleanorderby = [];
        foreach ($clause as $o) {
            if (is_string($o)) {
                $fields = explode(',', $o);
                foreach ($fields as $field) {
                    $new = '';
                    $tmp = explode(' ', trim($field));
                    $new .= DBmysql::quoteName($tmp[0]);
                    // ASC OR DESC added
                    if (isset($tmp[1]) && in_array($tmp[1], ['ASC', 'DESC'])) {
                        $new .= ' ' . $tmp[1];
                    }
                    $cleanorderby[] = $new;
                }
            } elseif ($o instanceof QueryExpression) {
                $cleanorderby[] = $o->getValue();
            } else {
                throw new LogicException("Invalid order clause.");
            }
        }

        return " ORDER BY " . implode(", ", $cleanorderby);
    }


    /**
     * Handle LIMIT and OFFSET
     *
     * @param integer $limit  SQL LIMIT
     * @param integer $offset Start OFFSET (defaults to null)
     *
     * @return string
     */
    public function handleLimits($limit, $offset = null)
    {
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
     * @return string
     */
    private function handleFields($t, $f)
    {
        if (is_numeric($t)) {
            if ($f instanceof AbstractQuery) {
                return $f->getQuery();
            } elseif ($f instanceof QueryExpression) {
                return $f->getValue();
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
                    // no break
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
                    // no break
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
    private function handleFieldsAlias($t, $f, $suffix = '')
    {
        $names = preg_split('/\s+AS\s+/i', $f);
        $expr  = "$t(" . $this->handleFields(0, $names[0]) . "$suffix)";
        if (isset($names[1])) {
            $expr .= " AS " . DBmysql::quoteName($names[1]);
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
    public function getSql()
    {
        return preg_replace('/ +/', ' ', $this->sql);
    }

    /**
     * Destructor
     *
     * @return void
     */
    public function __destruct()
    {
        if ($this->res instanceof mysqli_result) {
            $this->conn->freeResult($this->res);
        }
    }

    /**
     * Generate the SQL statement for a array of criteria
     *
     * @param array|string $crit Criteria
     * @param string   $bool Boolean operator (default AND)
     *
     * @return string
     */
    public function analyseCrit($crit, $bool = "AND")
    {
        if (is_string($crit)) {
            Toolbox::deprecated(
                sprintf(
                    'Passing SQL request criteria as strings is deprecated for security reasons. Criteria was `` %s ``.',
                    $crit
                ),
                version: '11.1'
            );

            /**
             * Delegate the safeness check to the caller.
             * There is no such usage in GLPI, it is the plugin developer responsibility to switch to safer criteria specs.
             * @psalm-taint-escape sql
             */
            $safe_crit = $crit;

            return $safe_crit;
        }

        if (!is_array($crit)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid criteria type. Expected `array`, `%s` received.',
                    get_debug_type($crit)
                )
            );
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
                } elseif ($value instanceof QuerySubQuery) {
                    $ret .= $value->getQuery();
                } elseif (in_array($value, [1, 0, '1', '0', true, false], true)) {
                    Toolbox::deprecated(
                        sprintf(
                            'Passing SQL request criteria as booleans is deprecated. Please use `new \Glpi\DBAL\QueryExpression("%s");`.',
                            $value ? 'true' : 'false'
                        ),
                        version: '11.1'
                    );
                    $ret .= $value ? 'true' : 'false';
                } else {
                    // No Key case => recurse.
                    $ret .= "(" . $this->analyseCrit($value) . ")";
                }
            } elseif (($name === "OR") || ($name === "AND")) {
                // Binary logical operator
                $ret .= "(" . $this->analyseCrit($value, $name) . ")";
            } elseif ($name === "NOT") {
                // Uninary logicial operator
                $ret .= " NOT (" . $this->analyseCrit($value) . ")";
            } elseif ($name === "FKEY" || $name === 'ON') {
                // Foreign Key condition
                $ret .= $this->analyseFkey($value);
            } elseif ($name === 'RAW') {
                $key = key($value);
                $value = current($value);
                $ret .= '((' . $key . ') ' . $this->analyseCriterion($value) . ')';
            } else {
                $ret .= DBmysql::quoteName($name) . ' ' . $this->analyseCriterion($value);
            }
        }
        return $ret;
    }

    /**
     * analyse a criterion
     *
     * @since 9.3.1
     *
     * @param mixed $value Value to analyse
     *
     * @return string
     */
    private function analyseCriterion($value)
    {
        $criterion = null;

        if (is_null($value) || (is_string($value) && strtolower($value) === 'null')) {
            // NULL condition
            $criterion = 'IS NULL';
        } else {
            if (is_array($value)) {
                if (count($value) == 2 && isset($value[0]) && $this->isOperator($value[0])) {
                    /**
                     * Is a safe operator.
                     * @psalm-taint-escape sql
                     */
                    $comparison = $value[0];

                    $criterion_value = $value[1];
                } else {
                    if (!count($value)) {
                        throw new RuntimeException('Empty IN are not allowed');
                    }
                    // Array of Values
                    return "IN " . $this->analyseCriterionValue($value);
                }
            } else {
                $comparison = ($value instanceof AbstractQuery ? 'IN' : '=');
                $criterion_value = $value;
            }
            $criterion = "$comparison " . $this->getCriterionValue($criterion_value);
        }

        return $criterion;
    }

    /**
     * Handle a criterion value
     *
     * @since 9.5.0
     *
     * @param mixed $value The value to handle. This may be:
     *                      - an instance of AbstractQuery
     *                      - a QueryExpression
     *                      - a value quoted as a name in the db engine
     *                      - a QueryParam
     *                      - a value or an array of values
     *
     * @return string
     */
    private function getCriterionValue($value)
    {
        return match (true) {
            $value instanceof AbstractQuery => $value->getQuery(),
            $value instanceof QueryExpression => $value->getValue(),
            $value instanceof QueryParam => $value->getValue(),
            default => $this->analyseCriterionValue($value)
        };
    }

    private function analyseCriterionValue($value)
    {
        $crit_value = null;
        if (is_array($value)) {
            $values = [];
            foreach ($value as $v) {
                $values[] = DBmysql::quoteValue($v);
            }
            $crit_value = '(' . implode(', ', $values) . ')';
        } else {
            $crit_value = DBmysql::quoteValue($value);
        }
        return $crit_value;
    }

    /**
     * analyse an array of joins criteria
     *
     * @since 9.4.0
     *
     * @param array $joinarray Array of joins to analyse
     *       [jointype => [table => criteria]]
     *
     * @return string
     */
    public function analyseJoins(array $joinarray)
    {
        $query = '';
        foreach ($joinarray as $jointype => $jointables) {
            if (!in_array($jointype, ['JOIN', 'LEFT JOIN', 'INNER JOIN', 'RIGHT JOIN'])) {
                throw new LogicException(sprintf('Invalid JOIN type `%s`.', $jointype));
            }

            if ($jointype == 'JOIN') {
                $jointype = 'LEFT JOIN';
            }

            if (!is_array($jointables)) {
                throw new LogicException("BAD JOIN, value must be [ table => criteria ].");
            }

            foreach ($jointables as $jointablekey => $jointablecrit) {
                // QueryExpression support, can be removed once Search::getDefaultJoin no longer returns raw SQL
                if ($jointablecrit instanceof QueryExpression) {
                    $query .= $jointablecrit->getValue();
                    continue;
                }

                if (isset($jointablecrit['TABLE'])) {
                    //not a "simple" FKEY
                    $jointablekey = $jointablecrit['TABLE'];
                    unset($jointablecrit['TABLE']);
                } elseif (is_numeric($jointablekey) || $jointablekey == 'FKEY' || $jointablekey == 'ON') {
                    throw new LogicException('BAD JOIN');
                }

                if ($jointablekey instanceof QuerySubQuery) {
                    $jointablekey = $jointablekey->getQuery();
                } else {
                    $jointablekey = DBmysql::quoteName($jointablekey);
                }

                $query .= " $jointype $jointablekey ON (" . $this->analyseCrit($jointablecrit) . ")";
            }
        }
        return $query;
    }

    /**
     * Analyse foreign keys
     *
     * @param mixed $values Values for Foreign keys
     *
     * @return string
     */
    private function analyseFkey($values)
    {
        if (is_array($values)) {
            $keys = array_keys($values);
            if (count($values) == 2) {
                $t1 = $keys[0];
                $f1 = $values[$t1];
                $left_value = $f1 instanceof QuerySubQuery || $f1 instanceof QueryExpression
                    ? (string) $f1
                    : (is_numeric($t1) ? DBmysql::quoteName($f1) : DBmysql::quoteName($t1) . '.' . DBmysql::quoteName($f1));

                $t2 = $keys[1];
                $f2 = $values[$t2];
                $right_value = $f2 instanceof QuerySubQuery || $f2 instanceof QueryExpression
                    ? (string) $f2
                    : (is_numeric($t2) ? DBmysql::quoteName($f2) : DBmysql::quoteName($t2) . '.' . DBmysql::quoteName($f2));

                return $left_value . ' = ' . $right_value;
            } elseif (count($values) == 3) {
                $real_values = [];
                foreach ($values as $k => $v) {
                    if (is_array($v)) {
                        $condition = $v;
                    } else {
                        $real_values[$k] = $v;
                    }
                }

                if (!isset($condition)) {
                    //in theory, should never happen
                    $condition = array_pop($real_values);
                }

                $fkey = $this->analyseFkey($real_values);
                $condition_value = $this->analyseCrit(current($condition));
                if (!empty(trim($condition_value))) {
                    return $fkey . ' ' . key($condition) . ' ' . $condition_value;
                }
                return $fkey;
            }
        } elseif ($values instanceof QueryExpression) {
            return $values->getValue();
        }
        throw new LogicException('BAD FOREIGN KEY, should be [ table1 => key1, table2 => key2 ] or [ table1 => key1, table2 => key2, [criteria]].');
    }

    /**
     * Rewind the Iterator to the first element
     *
     * @return void
     */
    public function rewind(): void
    {
        $this->setPosition(0);
    }

    /**
     * Return the current element
     *
     * @return mixed
     */
    public function current(): mixed
    {
        return $this->row;
    }

    /**
     * Return the key of the current element
     *
     * @return mixed
     */
    public function key(): mixed
    {
        return $this->row !== null ? ($this->row["id"] ?? $this->position) : null;
    }

    /**
     * Move forward to next element
     *
     * @return void
     */
    public function next(): void
    {
        $this->setPosition($this->position + 1);
    }

    /**
     * Checks if current position is valid
     *
     * @return bool
     */
    public function valid(): bool
    {
        return !$this->isFailed() && $this->position < $this->count;
    }

    /**
     * Check if the current result is not a {@link \mysqli_result}, indicating a failure.
     * @return bool
     */
    public function isFailed(): bool
    {
        return !($this->res instanceof mysqli_result);
    }

    /**
     * Number of rows on a result
     *
     * @return integer
     */
    public function numrows()
    {
        return $this->count;
    }

    /**
     * Number of rows on a result
     *
     * @since 9.2
     *
     * @return int
     */
    public function count(): int
    {
        return $this->count;
    }

    public function seek($position): void
    {
        if ($position < 0 || $position + 1 > $this->count) {
            throw new OutOfBoundsException();
        }
        $this->setPosition($position);
    }

    /**
     * Change pointer position, and fetch corresponding row value.
     *
     * @param int $position
     *
     * @return void
     */
    private function setPosition(int $position): void
    {
        if (!($this->res instanceof mysqli_result)) {
            // Result is not valid, nothing to do.
            return;
        }

        if ($position === $this->position) {
            // Position does not changed, nothing to do
            return;
        }

        if ($position === 0 && $this->position !== null || $position !== $this->position + 1) {
            //    position is set to 0 and was set previously (rewind case)
            // OR position is not moved to next element
            // => seek to requested position
            $this->conn->dataSeek($this->res, $position);
        }

        $this->position = $position;

        $data = $this->conn->fetchAssoc($this->res);

        $this->row = $data;
    }

    /**
     * Do we have an operator?
     *
     * @param string $value Value to check
     *
     * @return boolean
     */
    public function isOperator($value)
    {
        return in_array($value, $this->allowed_operators, true);
    }

    public function fetchFields(): array
    {
        return $this->res->fetch_fields();
    }

    /**
     * Convert arguments used for `DBmysql::request()`, `DBmysqlIterator::buildQuery()` and `DBmysqlIterator::execute()` methods
     * from old signature to new signature.
     * For security reasons, an exception is thrown whenever the arguments contains a direct raw query.
     *
     * @param array $args
     * @return array
     */
    private function convertOldRequestArgsToCriteria(array $args, string $method): array
    {
        if (is_string($args[0]) && str_contains($args[0], " ")) {
            $names = preg_split('/\s+AS\s+/i', $args[0]);
            if (isset($names[1]) && strpos($names[1], ' ') || !isset($names[1]) || strpos($names[0], ' ')) {
                throw new InvalidArgumentException(
                    sprintf('Building and executing raw queries with the `%s()` method is prohibited.', $method)
                );
            }
        }

        if (is_array($args[0])) {
            // The new signature ($criteria, $debug = false) is already used
            $criteria = $args[0];
        } else {
            // The old signature ($tableorsql, $crit = "", $debug = false) is still used
            Toolbox::deprecated(
                sprintf('The `%s()` method signature changed. Its previous signature is deprecated.', $method)
            );
            $criteria = $args[1] ?? [];
            if (is_string($criteria)) {
                $criteria = $criteria !== ''
                    ? ['WHERE' => [new QueryExpression($criteria)]]
                    : [];
            }
            $criteria['FROM'] = $args[0];
        }

        return $criteria;
    }
}
