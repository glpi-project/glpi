<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

use DBmysql;
use DBmysqlIterator;

use function Safe\preg_match;
use function Safe\preg_replace;

/**
 *  Query function class
 *
 * Arguments that designate a database identifier should be given as a {@see QueryIdentifier};
 * passing a bare string is still supported and behaves the same way.
 * Any other {@see QueryElementInterface} ({@see QueryExpression}, {@see QueryValue}, sub-queries)
 * is rendered through its `getValue()`, and the values it binds are collected automatically.
 * Generic 'params' array parameters follow the same rules for their elements; `null` elements are ignored.
 * All aliases passed to the functions are automatically quoted as identifiers.
 * @method static QueryExpression avg(QueryElementInterface|string $expression, ?string $alias = null) Build an 'AVG' SQL function call
 * @method static QueryExpression bitAnd(QueryElementInterface|string $expression, ?string $alias = null) Build a 'BIT_AND' SQL function call
 * @method static QueryExpression bitCount(QueryElementInterface|string $expression, ?string $alias = null) Build a 'BIT_COUNT' SQL function call
 * @method static QueryExpression bitOr(QueryElementInterface|string $expression, ?string $alias = null) Build a 'BIT_OR' SQL function call
 * @method static QueryExpression bitXor(QueryElementInterface|string $expression, ?string $alias = null) Build a 'BIT_XOR' SQL function call
 * @method static QueryExpression coalesce(array<int|string, mixed> $params, ?string $alias = null) Build a 'COALESCE' function call
 * @method static QueryExpression concat(array<int|string, mixed> $params, ?string $alias = null) Build a 'CONCAT' SQL function call
 * @method static QueryExpression floor(QueryElementInterface|string $expression, ?string $alias = null) Build a 'FLOOR' function call
 * @method static QueryExpression greatest(array<int|string, mixed> $params, ?string $alias = null) Build a 'GREATEST' function call
 * @method static QueryExpression inet6Aton(QueryElementInterface|string $expression, ?string $alias = null) Build an 'INET6_ATON' function call
 * @method static QueryExpression jsonExtract(array<int|string, mixed> $params, ?string $alias = null) Build a 'JSON_EXTRACT' function call
 * @method static QueryExpression jsonUnquote(QueryElementInterface|string $expression, ?string $alias = null) Build a 'JSON_UNQUOTE' function call
 * @method static QueryExpression jsonRemove(array<int|string, mixed> $params, ?string $alias = null) Build a 'JSON_REMOVE' function call
 * @method static QueryExpression least(array<int|string, mixed> $params, ?string $alias = null) Build a 'LEAST' function call
 * @method static QueryExpression lower(QueryElementInterface|string $expression, ?string $alias = null) Build a 'LOWER' SQL function call
 * @method static QueryExpression max(QueryElementInterface|string $expression, ?string $alias = null) Build a 'MAX' SQL function call
 * @method static QueryExpression min(QueryElementInterface|string $expression, ?string $alias = null) Build a 'MIN' SQL function call
 * @method static QueryExpression upper(QueryElementInterface|string $expression, ?string $alias = null) Build an 'UPPER' SQL function call
 * @method static QueryExpression year(QueryElementInterface|string $expression, ?string $alias = null) Build a 'YEAR' SQL function call
 **/
class QueryFunction
{
    /**
     * Render a single function argument to its SQL fragment, collecting the values it binds.
     *
     * @param mixed $arg Argument to render. `null` is skipped, a {@see QueryElementInterface} is
     *                   rendered through its `getValue()`, anything else is a bare identifier.
     * @param array<int, mixed> $params Values to bind passed by reference
     * @return string|null SQL fragment, or `null` when the argument has to be skipped
     */
    private static function renderArg(mixed $arg, array &$params): ?string
    {
        if ($arg === null) {
            return null;
        }

        if ($arg instanceof QueryElementInterface) {
            $params = array_merge($params, $arg->getParams());
            return $arg->getValue();
        }

        //TODO: deprecate 13.0.0. Passing raw string is still possible but should be removed in a future version
        //Toolbox::deprecate('Passing a bare string as a function argument is deprecated, use any QueryElementInterface instead', '13.0.0');
        return DBmysql::quoteName($arg);
    }

    /**
     * Render a list of function arguments, collecting the values they bind.
     *
     * @param array<int|string, mixed> $args
     * @param array<int, mixed> $params Values to bind passed by reference
     * @return array<int, string> SQL fragments, `null` arguments removed
     */
    private static function renderArgs(array $args, array &$params): array
    {
        $rendered = [];
        foreach ($args as $arg) {
            $sql = self::renderArg($arg, $params);
            if ($sql !== null) {
                $rendered[] = $sql;
            }
        }
        return $rendered;
    }

    /**
     * Format the given data as a SQL function call.
     * The alias should not be quoted. It will be done in the returned QueryExpression when its value is evaluated.
     * @param string $func_name SQL function name
     * @param array<int|string, mixed> $func_args Function arguments
     * @param string|null $alias Unquoted alias
     * @return QueryExpression
     */
    private static function getExpression(string $func_name, array $func_args, ?string $alias = null): QueryExpression
    {
        $params = [];
        $rendered = self::renderArgs($func_args, $params);
        return new QueryExpression($func_name . '(' . implode(', ', $rendered) . ')', $alias, $params);
    }

    /**
     * Build an aggregate SQL function call that supports the DISTINCT keyword.
     * @param string $func_name SQL function name
     * @param QueryElementInterface|string $expression Expression to aggregate
     * @param bool $distinct Whether to add the DISTINCT keyword
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    private static function getAggregateExpression(string $func_name, QueryElementInterface|string $expression, bool $distinct, ?string $alias): QueryExpression
    {
        $params = [];
        $exp = $func_name . '(' . ($distinct ? 'DISTINCT ' : '') . self::renderArg($expression, $params) . ')';
        return new QueryExpression($exp, $alias, $params);
    }

    /**
     * Build a DATE_ADD or DATE_SUB SQL function call.
     * @param string $func_name SQL function name
     * @param QueryElementInterface|string $date Date to apply the interval to
     * @param QueryElementInterface|int|string $interval Interval. A bare string is a literal value, not an identifier.
     * @param string $interval_unit Interval unit. The SQL engine requires a literal here, so it cannot be parameterized.
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    private static function getDateIntervalExpression(
        string $func_name,
        QueryElementInterface|string $date,
        QueryElementInterface|int|string $interval,
        string $interval_unit,
        ?string $alias
    ): QueryExpression {
        global $DB;

        $params = [];
        $date_sql = self::renderArg($date, $params);
        if ($interval instanceof QueryElementInterface) {
            $interval_sql = self::renderArg($interval, $params);
        } else {
            // a bare string interval is a literal value (e.g. '5-1'), not an identifier
            $interval_sql = is_string($interval) ? $DB::quoteValue($interval) : (string) $interval;
        }

        $exp = sprintf('%s(%s, INTERVAL %s %s)', $func_name, $date_sql, $interval_sql, strtoupper($interval_unit));
        return new QueryExpression($exp, $alias, $params);
    }

    /**
     *
     * @param string $name
     * @param array<int|string, mixed> $arguments
     *
     * @return QueryExpression
     */
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
     * @param QueryElementInterface|string $date Date to add interval to
     * @param QueryElementInterface|int|string $interval Interval to add. A bare string is a literal value, not an identifier.
     * @param string $interval_unit Interval unit
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function dateAdd(QueryElementInterface|string $date, QueryElementInterface|int|string $interval, string $interval_unit, ?string $alias = null): QueryExpression
    {
        return self::getDateIntervalExpression('DATE_ADD', $date, $interval, $interval_unit, $alias);
    }

    /**
     * Build an DATE_SUB SQL function call
     * @param QueryElementInterface|string $date Date to subtract interval from
     * @param QueryElementInterface|int|string $interval Interval to subtract. A bare string is a literal value, not an identifier.
     * @param string $interval_unit Interval unit
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function dateSub(QueryElementInterface|string $date, QueryElementInterface|int|string $interval, string $interval_unit, ?string $alias = null): QueryExpression
    {
        return self::getDateIntervalExpression('DATE_SUB', $date, $interval, $interval_unit, $alias);
    }

    /**
     * Build an IF SQL function call
     * @param QueryElementInterface|string|array<int|string, mixed> $condition Condition to test
     * @param QueryElementInterface|string $true_expression Expression to return if condition is true
     * @param QueryElementInterface|string $false_expression Expression to return if condition is false
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function if(QueryElementInterface|string|array $condition, QueryElementInterface|string $true_expression, QueryElementInterface|string $false_expression, ?string $alias = null): QueryExpression
    {
        if (is_array($condition)) {
            $iterator = new DBmysqlIterator(null);
            $condition = new QueryExpression($iterator->analyseCrit($condition), values: $iterator->getValues());
        }
        return self::getExpression('IF', [$condition, $true_expression, $false_expression], $alias);
    }

    /**
     * Build an IFNULL SQL function call
     * @param QueryElementInterface|string $expression Expression to check
     * @param QueryElementInterface|string $value Value to return if expression is null
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function ifnull(QueryElementInterface|string $expression, QueryElementInterface|string $value, ?string $alias = null): QueryExpression
    {
        return self::getExpression('IFNULL', [$expression, $value], $alias);
    }

    /**
     * Build a GROUP_CONCAT SQL function call
     * @param QueryElementInterface|string $expression Expression to concatenate
     * @param string|null $separator Separator to use. The SQL engine requires a literal here, so it cannot be parameterized.
     * @param bool $distinct Whether to add the DISTINCT keyword
     * @param QueryElementInterface|array<int, QueryElementInterface|string>|string|null $order_by Order by clause
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function groupConcat(QueryElementInterface|string $expression, ?string $separator = null, bool $distinct = false, QueryElementInterface|array|string|null $order_by = null, ?string $alias = null): QueryExpression
    {
        global $DB;

        $params = [];
        $exp = 'GROUP_CONCAT(';
        if ($distinct) {
            $exp .= 'DISTINCT ';
        }
        $exp .= self::renderArg($expression, $params);
        if ($order_by) {
            $iterator = new DBmysqlIterator(null);
            $exp .= $iterator->handleOrderClause($order_by);
            $params = array_merge($params, $iterator->getValues());
        }
        if (!empty($separator)) {
            $exp .= ' SEPARATOR ' . $DB::quoteValue($separator);
        }
        $exp .= ')';

        return new QueryExpression($exp, $alias, $params);
    }

    /**
     * Build a SUM SQL function call
     * @param QueryElementInterface|string $expression Expression to sum
     * @param bool $distinct Whether to add the DISTINCT keyword
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function sum(QueryElementInterface|string $expression, bool $distinct = false, ?string $alias = null): QueryExpression
    {
        return self::getAggregateExpression('SUM', $expression, $distinct, $alias);
    }

    /**
     * Build a COUNT SQL function call
     * @param QueryElementInterface|string $expression Expression to count
     * @param bool $distinct Whether to add the DISTINCT keyword
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function count(QueryElementInterface|string $expression, bool $distinct = false, ?string $alias = null): QueryExpression
    {
        return self::getAggregateExpression('COUNT', $expression, $distinct, $alias);
    }

    /**
     * Build a CAST SQL function call
     * @param QueryElementInterface|string $expression Expression to cast
     * @param string $type Type to cast to. The SQL engine requires a literal here, so it cannot be parameterized.
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function cast(QueryElementInterface|string $expression, string $type, ?string $alias = null): QueryExpression
    {
        $params = [];
        $expression_sql = self::renderArg($expression, $params);
        return new QueryExpression('CAST(' . $expression_sql . ' AS ' . $type . ')', $alias, $params);
    }

    /**
     * Build a CONVERT SQL function call
     * @param QueryElementInterface|string $expression Expression to convert
     * @param string $transcoding Transcoding to use. The SQL engine requires a literal here, so it cannot be parameterized.
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function convert(QueryElementInterface|string $expression, string $transcoding, ?string $alias = null): QueryExpression
    {
        $params = [];
        $expression_sql = self::renderArg($expression, $params);
        return new QueryExpression('CONVERT(' . $expression_sql . ' USING ' . $transcoding . ')', $alias, $params);
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
     * @param QueryElementInterface|string $expression Expression to search in
     * @param QueryElementInterface|string $search String to search
     * @param QueryElementInterface|string $replace String to replace
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function replace(QueryElementInterface|string $expression, QueryElementInterface|string $search, QueryElementInterface|string $replace, ?string $alias = null): QueryExpression
    {
        return self::getExpression('REPLACE', [$expression, $search, $replace], $alias);
    }

    /**
     * Build a FROM_UNIXTIME SQL function call
     * @param QueryElementInterface|string $expression Expression to convert
     * @param QueryElementInterface|string|null $format Format to use
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function fromUnixtime(QueryElementInterface|string $expression, QueryElementInterface|string|null $format = null, ?string $alias = null): QueryExpression
    {
        return self::getExpression('FROM_UNIXTIME', [$expression, $format], $alias);
    }

    /**
     * Build a DATE_FORMAT SQL function call
     * @param QueryElementInterface|string $expression Expression to format
     * @param string $format Format to use (Automatically quoted as a value)
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function dateFormat(QueryElementInterface|string $expression, string $format, ?string $alias = null): QueryExpression
    {
        global $DB;
        return self::getExpression('DATE_FORMAT', [$expression, new QueryExpression($DB::quoteValue($format))], $alias);
    }

    /**
     * Build a LPAD SQL function call
     * @param QueryElementInterface|string $expression Expression to pad
     * @param int $length Length to pad to
     * @param string $pad_string String to pad with (Automatically quoted as a value)
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function lpad(QueryElementInterface|string $expression, int $length, string $pad_string, ?string $alias = null): QueryExpression
    {
        global $DB;
        return self::getExpression('LPAD', [
            $expression,
            new QueryExpression((string) $length),
            new QueryExpression($DB::quoteValue($pad_string)),
        ], $alias);
    }

    /**
     * Build a SUBSTRING SQL function call
     * @param QueryElementInterface|string $expression Expression
     * @param int $start Start position
     * @param int $length Length
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function substring(QueryElementInterface|string $expression, int $start, int $length, ?string $alias = null): QueryExpression
    {
        return self::getExpression('SUBSTRING', [
            $expression,
            new QueryExpression((string) $start),
            new QueryExpression((string) $length),
        ], $alias);
    }

    /**
     * Build a ROUND SQL function call
     * @param QueryElementInterface|string $expression Expression to round
     * @param int $precision Precision to round to
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function round(QueryElementInterface|string $expression, int $precision = 0, ?string $alias = null): QueryExpression
    {
        return self::getExpression('ROUND', [$expression, new QueryExpression((string) $precision)], $alias);
    }

    /**
     * Build a NULLIF SQL function call
     * @param QueryElementInterface|string $expression Expression to check
     * @param QueryElementInterface|string $value Value to check against
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function nullif(QueryElementInterface|string $expression, QueryElementInterface|string $value, ?string $alias = null): QueryExpression
    {
        return self::getExpression('NULLIF', [$expression, $value], $alias);
    }

    /**
     * Build a TIMESTAMPDIFF SQL function call
     * @param string $unit Unit to use. The SQL engine requires a literal here, so it cannot be parameterized.
     * @param QueryElementInterface|string $expression1 Expression to compare
     * @param QueryElementInterface|string $expression2 Expression to compare
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function timestampdiff(string $unit, QueryElementInterface|string $expression1, QueryElementInterface|string $expression2, ?string $alias = null): QueryExpression
    {
        return self::getExpression('TIMESTAMPDIFF', [new QueryExpression($unit), $expression1, $expression2], $alias);
    }

    /**
     * Build a DATEDIFF SQL function call
     * @param QueryElementInterface|string $expression1 Expression to compare
     * @param QueryElementInterface|string $expression2 Expression to compare
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function datediff(QueryElementInterface|string $expression1, QueryElementInterface|string $expression2, ?string $alias = null): QueryExpression
    {
        return self::getExpression('DATEDIFF', [$expression1, $expression2], $alias);
    }

    /**
     * Build a TIMEDIFF SQL function call
     * @param QueryElementInterface|string $expression1 Expression to compare
     * @param QueryElementInterface|string $expression2 Expression to compare
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function timediff(QueryElementInterface|string $expression1, QueryElementInterface|string $expression2, ?string $alias = null): QueryExpression
    {
        return self::getExpression('TIMEDIFF', [$expression1, $expression2], $alias);
    }

    /**
     * Build a UNIX_TIMSTAMP SQL function call
     * @param QueryElementInterface|string|null $expression Expression to convert. If null, the current timestamp will be used (NOW() implied at the DB level).
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function unixTimestamp(QueryElementInterface|string|null $expression = null, ?string $alias = null): QueryExpression
    {
        return self::getExpression('UNIX_TIMESTAMP', [$expression], $alias);
    }

    /**
     * Build a LOCATE SQL function call
     * @param QueryElementInterface|string $substring String to search for. Treated like a value if it's a string.
     * @param QueryElementInterface|string $expression Expression to search in
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function locate(QueryElementInterface|string $substring, QueryElementInterface|string $expression, ?string $alias = null): QueryExpression
    {
        global $DB;
        $substring = is_string($substring) ? new QueryExpression($DB::quoteValue($substring)) : $substring;
        return self::getExpression('LOCATE', [$substring, $expression], $alias);
    }

    /**
     * Build a CONCAT_WS SQL function call
     * @param QueryElementInterface|string $separator Separator to use. String values are treated as identifiers; use a QueryValue for a literal.
     * @param array<int, mixed> $params Array of expressions to concatenate. String values will be treated as identifiers, null values will be ignored.
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return QueryExpression
     */
    public static function concat_ws(QueryElementInterface|string $separator, array $params, ?string $alias = null): QueryExpression
    {
        $values = [];
        $separator_sql = self::renderArg($separator, $values);
        $rendered = self::renderArgs($params, $values);
        return new QueryExpression('CONCAT_WS(' . $separator_sql . ', ' . implode(', ', $rendered) . ')', $alias, $values);
    }

    /**
     * Build a JSON_CONTAINS SQL function call
     *
     * Searches for a value within a JSON document.
     *
     * @param QueryElementInterface|string $target JSON field or expression to search in.
     * @param QueryElementInterface|string $candidate Value to search for. String values will be treated as field identifiers.
     * @param string $path JSON path expression (e.g., '$' for root, '$.fieldname' for nested).
     * @param string|null $alias Function result alias.
     * @return QueryExpression
     *
     * @example
     * // Search for a scalar value
     * QueryFunction::jsonContains(
     *     new QueryIdentifier('users_id_guests'),
     *     new QueryValue(4),
     *     '$'
     * )
     *
     * @example
     * // Search using a column reference
     * QueryFunction::jsonContains(
     *     new QueryIdentifier('REFTABLE.custom_fields'),
     *     new QueryIdentifier('NEWTABLE.id'),
     *     '$.field_id'
     * )
     */
    public static function jsonContains(QueryElementInterface|string $target, QueryElementInterface|string $candidate, string $path, ?string $alias = null): QueryExpression
    {
        global $DB;

        return self::getExpression(
            'JSON_CONTAINS',
            [
                $target,
                $DB->getVersionAndServer()['server'] === 'MariaDB' ? $candidate : self::cast($candidate, 'JSON'),
                new QueryExpression($DB::quoteValue($path)),
            ],
            $alias
        );
    }
}
