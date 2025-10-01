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

namespace Glpi\DBAL;

use DBmysqlIterator;

use function Safe\preg_match;
use function Safe\preg_replace;

/**
 *  Query function class
 *
 * If a parameter takes a string or a QueryExpression, a string is an unquoted identifier while a QueryExpression is used for everything else.
 * Generic 'params' array parameters follow the same rules for their elements.
 * All aliases passed to the functions are automatically quoted as identifiers.
 * @method static QueryExpression avg(string|QueryExpression $expression, ?string $alias = null) Build an 'AVG' SQL function call
 * @method static QueryExpression bitAnd(string|QueryExpression $expression, ?string $alias = null) Build a 'BIT_AND' SQL function call
 * @method static QueryExpression bitCount(string|QueryExpression $expression, ?string $alias = null) Build a 'BIT_COUNT' SQL function call
 * @method static QueryExpression bitOr(string|QueryExpression $expression, ?string $alias = null) Build a 'BIT_OR' SQL function call
 * @method static QueryExpression bitXor(string|QueryExpression $expression, ?string $alias = null) Build a 'BIT_XOR' SQL function call
 * @method static QueryExpression coalesce(array $params, ?string $alias = null) Build a 'COALESCE' function call
 * @method static QueryExpression concat(array $params, ?string $alias = null) Build a 'CONCAT' SQL function call
 * @method static QueryExpression floor(string|QueryExpression $expression, ?string $alias = null) Build a 'FLOOR' function call
 * @method static QueryExpression greatest(array $params, ?string $alias = null) Build a 'GREATEST' function call
 * @method static QueryExpression jsonExtract(array $params, ?string $alias = null) Build a 'JSON_EXTRACT' function call
 * @method static QueryExpression jsonUnquote(string|QueryExpression $expression, ?string $alias = null) Build a 'JSON_UNQUOTE' function call
 * @method static QueryExpression jsonRemove(array $params, ?string $alias = null) Build a 'JSON_REMOVE' function call
 * @method static QueryExpression least(array $params, ?string $alias = null) Build a 'LEAST' function call
 * @method static QueryExpression lower(string|QueryExpression $expression, ?string $alias = null) Build a 'LOWER' SQL function call
 * @method static QueryExpression max(string|QueryExpression $expression, ?string $alias = null) Build a 'MAX' SQL function call
 * @method static QueryExpression min(string|QueryExpression $expression, ?string $alias = null) Build a 'MIN' SQL function call
 * @method static QueryExpression upper(string|QueryExpression $expression, ?string $alias = null) Build a 'UPPER' SQL function call
 * @method static QueryExpression year(string|QueryExpression $expression, ?string $alias = null) Build a 'YEAR' SQL function call
 **/
class QueryFunction
{
    /**
     * Format the given data as a SQL function call.
     * The alias should not be quoted. It will be done in the returned QueryExpression when its value is evaluated.
     * @param string $func_name SQL function name
     * @param array $params Array of quoted identifiers or QueryExpressions
     * @param string|null $alias Unquoted alias
     * @return QueryExpression
     */
    private static function getExpression(string $func_name, array $params, ?string $alias = null): QueryExpression
    {
        global $DB;
        $params = array_map(static fn($p) => $p instanceof QueryExpression || $p === null ? $p : $DB::quoteName($p), $params);
        return new QueryExpression($func_name . '(' . implode(', ', $params) . ')', $alias);
    }

    public static function __callStatic(string $name, array $arguments)
    {
        $args = array_values($arguments);
        $params = $args[0];
        if (!is_array($params)) {
            $params = [$params];
        }

        // Map camelCase function names to SQL function names
        $func_name = match (true) {
            // if the name is in camelCase, convert camelCase to snake_case
            (bool) preg_match('/[A-Z]/', $name) => preg_replace('/(?<!^)[A-Z]/', '_$0', $name),
            // Place any special formatting cases here
            default => $name,
        };
        $func_name = strtoupper($func_name);
        return self::getExpression($func_name, $params, $args[1] ?? null);
    }

    /**
     * Build an DATE_ADD SQL function call
     * @param string|QueryExpression $date Date to add interval to
     * @param int|string|QueryExpression $interval Interval to add
     * @param string $interval_unit Interval unit
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function dateAdd(string|QueryExpression $date, int|string|QueryExpression $interval, string $interval_unit, ?string $alias = null): QueryExpression
    {
        global $DB;
        $date = $date instanceof QueryExpression ? $date : $DB::quoteName($date);
        $interval = is_string($interval) ? $DB::quoteValue($interval) : $interval;
        $exp = sprintf('DATE_ADD(%s, INTERVAL %s %s)', $date, $interval, strtoupper($interval_unit));
        return new QueryExpression($exp, $alias);
    }

    /**
     * Build an DATE_SUB SQL function call
     * @param string|QueryExpression $date Date to add interval to
     * @param int|string|QueryExpression $interval Interval to add
     * @param string $interval_unit Interval unit
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function dateSub(string|QueryExpression $date, int|string|QueryExpression $interval, string $interval_unit, ?string $alias = null): QueryExpression
    {
        global $DB;
        $date = $date instanceof QueryExpression ? $date : $DB::quoteName($date);
        $interval = is_string($interval) ? $DB::quoteValue($interval) : $interval;
        $exp = sprintf('DATE_SUB(%s, INTERVAL %s %s)', $date, $interval, strtoupper($interval_unit));
        return new QueryExpression($exp, $alias);
    }

    /**
     * Build an IF SQL function call
     * @param string|QueryExpression|array $condition Condition to test
     * @param string|QueryExpression $true_expression Expression to return if condition is true
     * @param string|QueryExpression $false_expression Expression to return if condition is false
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function if(string|QueryExpression|array $condition, string|QueryExpression $true_expression, string|QueryExpression $false_expression, ?string $alias = null): QueryExpression
    {
        if (is_array($condition)) {
            $condition = new QueryExpression((new DBmysqlIterator(null))->analyseCrit($condition));
        }
        return self::getExpression('IF', [$condition, $true_expression, $false_expression], $alias);
    }

    /**
     * Build an IFNULL SQL function call
     * @param string|QueryExpression $expression Expression to check
     * @param string|QueryExpression $value Value to return if expression is null
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function ifnull(string|QueryExpression $expression, string|QueryExpression $value, ?string $alias = null): QueryExpression
    {
        return self::getExpression('IFNULL', [$expression, $value], $alias);
    }

    /**
     * Build a GROUP_CONCAT SQL function call
     * @param string|QueryExpression $expression Expression to group
     * @param string|null $separator Separator to use (will be automatically quoted as a value)
     * @param bool $distinct Use DISTINCT or not
     * @param array|string|null $order_by Order by clause
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function groupConcat(string|QueryExpression $expression, ?string $separator = null, bool $distinct = false, array|string|null $order_by = null, ?string $alias = null): QueryExpression
    {
        global $DB;

        $expression = $expression instanceof QueryExpression ? $expression : $DB::quoteName($expression);
        $exp = 'GROUP_CONCAT(';
        if ($distinct) {
            $exp .= 'DISTINCT ';
        }
        $exp .= $expression;
        if ($order_by) {
            $exp .= (new DBmysqlIterator(null))->handleOrderClause($order_by);
        }
        if (!empty($separator)) {
            $exp .= ' SEPARATOR ' . $DB::quoteValue($separator);
        }
        $exp .= ')';
        return new QueryExpression($exp, $alias);
    }

    /**
     * Build a SUM SQL function call
     * @param string|QueryExpression $expression Expression to sum
     * @param bool $distinct Use DISTINCT or not
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function sum(string|QueryExpression $expression, bool $distinct = false, ?string $alias = null): QueryExpression
    {
        global $DB;

        $expression = $expression instanceof QueryExpression ? $expression : $DB::quoteName($expression);
        $exp = 'SUM(';
        if ($distinct) {
            $exp .= 'DISTINCT ';
        }
        $exp .= $expression . ')';
        return new QueryExpression($exp, $alias);
    }

    /**
     * Build a COUNT SQL function call
     * @param string|QueryExpression $expression Expression to count
     * @param bool $distinct Use DISTINCT or not
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function count(string|QueryExpression $expression, bool $distinct = false, ?string $alias = null): QueryExpression
    {
        global $DB;

        $expression = $expression instanceof QueryExpression ? $expression : $DB::quoteName($expression);
        $exp = 'COUNT(';
        if ($distinct) {
            $exp .= 'DISTINCT ';
        }
        $exp .= $expression . ')';
        return new QueryExpression($exp, $alias);
    }

    /**
     * Build a CAST SQL function call
     * @param string|QueryExpression $expression Expression to cast
     * @param string $type Type to cast to
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function cast(string|QueryExpression $expression, string $type, ?string $alias = null): QueryExpression
    {
        global $DB;

        $expression = $expression instanceof QueryExpression ? $expression : $DB::quoteName($expression);
        return new QueryExpression('CAST(' . $expression . ' AS ' . $type . ')', $alias);
    }

    /**
     * Build a CONVERT SQL function call
     * @param string|QueryExpression $expression Expression to convert
     * @param string $transcoding Transcoding to use
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function convert(string|QueryExpression $expression, string $transcoding, ?string $alias = null): QueryExpression
    {
        global $DB;
        $expression = $expression instanceof QueryExpression ? $expression : $DB::quoteName($expression);
        return new QueryExpression('CONVERT(' . $expression . ' USING ' . $transcoding . ')', $alias);
    }

    /**
     * Build a NOW SQL function call
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function now(?string $alias = null): QueryExpression
    {
        return new QueryExpression('NOW()', $alias);
    }

    /**
     * Build a CURDATE SQL function call
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function curdate(?string $alias = null): QueryExpression
    {
        return new QueryExpression('CURDATE()', $alias);
    }

    /**
     * Build a REPLACE SQL function call
     * @param string|QueryExpression $expression Expression to search in
     * @param string|QueryExpression $search String to search
     * @param string|QueryExpression $replace String to replace
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function replace(string|QueryExpression $expression, string|QueryExpression $search, string|QueryExpression $replace, ?string $alias = null): QueryExpression
    {
        return self::getExpression('REPLACE', [$expression, $search, $replace], $alias);
    }

    /**
     * Build a FROM_UNIXTIME SQL function call
     * @param string|QueryExpression $expression Expression to convert
     * @param string|QueryExpression|null $format Function result alias
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function fromUnixtime(string|QueryExpression $expression, string|QueryExpression|null $format = null, ?string $alias = null): QueryExpression
    {
        $params = [$expression];
        if ($format !== null) {
            $params[] = $format;
        }
        return self::getExpression('FROM_UNIXTIME', $params, $alias);
    }

    /**
     * Build a DATE_FORMAT SQL function call
     * @param string|QueryExpression $expression Expression to format
     * @param string $format Format to use (Automatically quoted as a value)
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function dateFormat(string|QueryExpression $expression, string $format, ?string $alias = null): QueryExpression
    {
        global $DB;
        $format = new QueryExpression($DB::quoteValue($format));
        return self::getExpression('DATE_FORMAT', [$expression, $format], $alias);
    }

    /**
     * Build a LPAD SQL function call
     * @param string|QueryExpression $expression Expression to pad
     * @param int $length Length to pad to
     * @param string $pad_string String to pad with (Automatically quoted as a value)
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function lpad(string|QueryExpression $expression, int $length, string $pad_string, ?string $alias = null): QueryExpression
    {
        global $DB;
        $length = new QueryExpression((string) $length);
        $pad_string = new QueryExpression($DB::quoteValue($pad_string));
        return self::getExpression('LPAD', [$expression, $length, $pad_string], $alias);
    }

    /**
     * Build a SUBSTRING SQL function call
     * @param string|QueryExpression $expression Expression
     * @param int $start Start position
     * @param int $length Length
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function substring(string|QueryExpression $expression, int $start, int $length, ?string $alias = null): QueryExpression
    {
        return self::getExpression('SUBSTRING', [
            $expression, new QueryExpression((string) $start), new QueryExpression((string) $length),
        ], $alias);
    }

    /**
     * Buidl a ROUND SQL function call
     * @param string|QueryExpression $expression Expression to round
     * @param int $precision Precision to round to
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function round(string|QueryExpression $expression, int $precision = 0, ?string $alias = null): QueryExpression
    {
        $precision = new QueryExpression((string) $precision);
        return self::getExpression('ROUND', [$expression, $precision], $alias);
    }

    /**
     * Build a NULLIF SQL function call
     * @param string|QueryExpression $expression Expression to check
     * @param string|QueryExpression $value Value to check against
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function nullif(string|QueryExpression $expression, string|QueryExpression $value, ?string $alias = null): QueryExpression
    {
        return self::getExpression('NULLIF', [$expression, $value], $alias);
    }

    /**
     * Build a TIMESTAMPDIFF SQL function call
     * @param string $unit Unit to use
     * @param string|QueryExpression $expression1 Expression to compare
     * @param string|QueryExpression $expression2 Expression to compare
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function timestampdiff(string $unit, string|QueryExpression $expression1, string|QueryExpression $expression2, ?string $alias = null): QueryExpression
    {
        return self::getExpression('TIMESTAMPDIFF', [new QueryExpression($unit), $expression1, $expression2], $alias);
    }

    /**
     * Build a DATEDIFF SQL function call
     * @param string|QueryExpression $expression1 Expression to compare
     * @param string|QueryExpression $expression2 Expression to compare
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function datediff(string|QueryExpression $expression1, string|QueryExpression $expression2, ?string $alias = null): QueryExpression
    {
        return self::getExpression('DATEDIFF', [$expression1, $expression2], $alias);
    }

    /**
     * Build a TIMEDIFF SQL function call
     * @param string|QueryExpression $expression1 Expression to compare
     * @param string|QueryExpression $expression2 Expression to compare
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function timediff(string|QueryExpression $expression1, string|QueryExpression $expression2, ?string $alias = null): QueryExpression
    {
        return self::getExpression('TIMEDIFF', [$expression1, $expression2], $alias);
    }

    /**
     * Build a UNIX_TIMSTAMP SQL function call
     * @param string|QueryExpression|null $expression Expression to convert. If null, the current timestamp will be used (NOW() implied at the DB level).
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function unixTimestamp(string|QueryExpression|null $expression = null, ?string $alias = null): QueryExpression
    {
        $params = [];
        if ($expression !== null) {
            $params = [$expression];
        }
        return self::getExpression('UNIX_TIMESTAMP', $params, $alias);
    }

    /**
     * Build a LOCATE SQL function call
     * @param string|QueryExpression $substring String to search for. Treated like a value if it's a string.
     * @param string|QueryExpression $expression Expression to search in
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function locate(string|QueryExpression $substring, string|QueryExpression $expression, ?string $alias = null): QueryExpression
    {
        global $DB;
        $substring = is_string($substring) ? new QueryExpression($DB::quoteValue($substring)) : $substring;
        return self::getExpression('LOCATE', [$substring, $expression], $alias);
    }

    public static function concat_ws(string|QueryExpression $separator, array $params, ?string $alias = null): QueryExpression
    {
        global $DB;
        $params = array_map(static fn($p) => $p instanceof QueryExpression || $p === null ? $p : $DB::quoteName($p), $params);
        $separator = $separator instanceof QueryExpression ? $separator : $DB::quoteName($separator);
        return new QueryExpression('CONCAT_WS(' . $separator . ', ' . implode(', ', $params) . ')', $alias);
    }

    public static function jsonContains(string|QueryExpression $target, string|QueryExpression $candidate, string $path, ?string $alias = null): QueryExpression
    {
        global $DB;

        if (is_string($target)) {
            $target = new QueryExpression($DB::quoteName($target));
        }

        if (is_string($candidate)) {
            $candidate = preg_match('/-MariaDB/', $DB->getVersion())
                ? new QueryExpression($DB::quoteName($candidate))
                : QueryFunction::cast($candidate, 'JSON')
            ;
        }

        $path = new QueryExpression($DB::quoteValue($path));

        return self::getExpression('JSON_CONTAINS', [$target, $candidate, $path], $alias);
    }
}
