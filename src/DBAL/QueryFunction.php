<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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
use Glpi\DBAL\Function\AddDate;
use Glpi\DBAL\Function\Cast;
use Glpi\DBAL\Function\Convert;
use Glpi\DBAL\Function\Count;
use Glpi\DBAL\Function\Formatter;
use Glpi\DBAL\Function\GroupConcat;
use Glpi\DBAL\Function\Sum;

/**
 *  Query function class
 **/
class QueryFunction
{
    private string $name;

    private array $params;

    private ?string $alias;

    /**
     * Create a query expression
     *
     * @param string $name Function name
     * @param array $params Function parameters
     * @param string|null $alias Function result alias
     */
    public function __construct(string $name, array $params = [], ?string $alias = null)
    {
        global $DB;

        $this->name = $name;
        $this->params = $params;
        $this->alias = !empty($alias) ? $DB::quoteName($alias) : null;
    }

    /**
     * Query expression value
     *
     * @return string
     */
    public function getValue()
    {
        $alias = $this->alias ? ' AS ' . $this->alias : '';

        $function_string = match ($this->name) {
            'ADDDATE' => AddDate::format($this->params),
            'GROUP_CONCAT' => GroupConcat::format($this->params),
            'COUNT' => Count::format($this->params),
            'SUM' => Sum::format($this->params),
            'CAST' => Cast::format($this->params),
            'CONVERT' => Convert::format($this->params),
            default => $this->name . '(' . implode(', ', $this->params) . ')',
        };

        return $function_string . $alias;
    }

    public function __toString()
    {
        return $this->getValue();
    }

    /**
     * Build a CONCAT SQL function call
     * @param array $params Function parameters. Parameters must be already quoted if needed with {@link DBmysql::quote()} or {@link DBmysql::quoteName()}
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return static
     */
    public static function concat(array $params, ?string $alias = null): self
    {
        return new self('CONCAT', $params, $alias);
    }

    /**
     * Build an ADDDATE SQL function call
     * Parameters must be already quoted if needed with {@link DBmysql::quote()} or {@link DBmysql::quoteName()}
     * @param string $date Date to add interval to
     * @param string $interval Interval to add
     * @param string $interval_unit Interval unit
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return static
     */
    public static function addDate(string $date, string $interval, string $interval_unit, ?string $alias = null): self
    {
        return new self('ADDDATE', [$date, $interval, $interval_unit], $alias);
    }

    /**
     * Build an IF SQL function call
     * @param string|array $condition Condition to test
     * @param string $true_expression Expression to return if condition is true
     * @param string $false_expression Expression to return if condition is false
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return static
     */
    public static function if(string|array $condition, string $true_expression, string $false_expression, ?string $alias = null): self
    {
        if (is_array($condition)) {
            $condition = (new DBmysqlIterator(null))->analyseCrit($condition);
        }
        return new self('IF', [$condition, $true_expression, $false_expression], $alias);
    }

    /**
     * Build an IFNULL SQL function call
     * Parameters must be already quoted if needed with {@link DBmysql::quote()} or {@link DBmysql::quoteName()}
     * @param string $expression Expression to check
     * @param string $value Value to return if expression is null
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return static
     */
    public static function ifNull(string $expression, string $value, ?string $alias = null): self
    {
        return new self('IFNULL', [$expression, $value], $alias);
    }

    /**
     * Build a GROUP_CONCAT SQL function call
     * @param string $expression Expression to group
     * @param string|null $separator Separator to use
     * @param bool $distinct Use DISTINCT or not
     * @param string|null $order_by Order by clause
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return static
     */
    public static function groupConcat(string $expression, ?string $separator = null, bool $distinct = false, ?string $order_by = null, ?string $alias = null): self
    {
        return new self('GROUP_CONCAT', [$expression, $separator, $distinct, $order_by], $alias);
    }

    /**
     * Build a FLOOR SQL function call
     * @param string $expression Expression to floor
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return static
     */
    public static function floor(string $expression, ?string $alias = null): self
    {
        return new self('FLOOR', [$expression], $alias);
    }

    /**
     * Build a SUM SQL function call
     * @param string $expression Expression to sum
     * @param bool $distinct Use DISTINCT or not
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return static
     */
    public static function sum(string $expression, bool $distinct = false, ?string $alias = null): self
    {
        return new self('SUM', [$expression, $distinct], $alias);
    }

    /**
     * Build a COUNT SQL function call
     * @param string $expression Expression to count
     * @param bool $distinct Use DISTINCT or not
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return static
     */
    public static function count(string $expression, bool $distinct = false, ?string $alias = null): self
    {
        return new self('COUNT', [$expression, $distinct], $alias);
    }

    /**
     * Build a MIN SQL function call
     * @param string $expression Expression to get min value
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return static
     */
    public static function min(string $expression, ?string $alias = null): self
    {
        return new self('MIN', [$expression], $alias);
    }

    /**
     * Build an AVG SQL function call
     * @param string $expression Expression to get average value
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return static
     */
    public static function avg(string $expression, ?string $alias = null): self
    {
        return new self('AVG', [$expression], $alias);
    }

    /**
     * Build a CAST SQL function call
     * @param string $expression Expression to cast
     * @param string $type Type to cast to
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return static
     */
    public static function cast(string $expression, string $type, ?string $alias = null): self
    {
        return new self('CAST', [$expression, $type], $alias);
    }

    /**
     * Build a CONVERT SQL function call
     * @param string $expression Expression to convert
     * @param string $transcoding Transcoding to use
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return static
     */
    public static function convert(string $expression, string $transcoding, ?string $alias = null): self
    {
        return new self('CONVERT', [$expression, $transcoding], $alias);
    }

    /**
     * Build a NOW SQL function call
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return static
     */
    public static function now(?string $alias = null): self
    {
        return new self('NOW', [], $alias);
    }

    /**
     * Build a LOWER SQL function call
     * @param string $expression Expression to convert
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return static
     */
    public static function lower(string $expression, ?string $alias = null): self
    {
        return new self('LOWER', [$expression], $alias);
    }

    /**
     * Build a REPLACE SQL function call
     * @param string $expression Expression to search in
     * @param string $search String to search
     * @param string $replace String to replace
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return static
     */
    public static function replace(string $expression, string $search, string $replace, ?string $alias = null): self
    {
        return new self('REPLACE', [$expression, $search, $replace], $alias);
    }

    /**
     * Build a UNIX_TIMESTAMP SQL function call
     * @param string $expression Expression to convert
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return static
     */
    public static function unixTimestamp(string $expression, ?string $alias = null): self
    {
        return new self('UNIX_TIMESTAMP', [$expression], $alias);
    }

    /**
     * Build a FROM_UNIXTIME SQL function call
     * @param string $expression Expression to convert
     * @param string|null $format Function result alias
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return static
     */
    public static function fromUnixTimestamp(string $expression, ?string $format = null, ?string $alias = null): self
    {
        $params = [$expression];
        if ($format !== null) {
            $params[] = $format;
        }
        return new self('FROM_UNIXTIME', $params, $alias);
    }

    /**
     * Build a DATE_FORMAT SQL function call
     * @param string $expression Expression to format
     * @param string $format Format to use
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return static
     */
    public static function dateFormat(string $expression, string $format, ?string $alias = null): self
    {
        return new self('DATE_FORMAT', [$expression, $format], $alias);
    }

    /**
     * Build a LPAD SQL function call
     * @param string $expression Expression to pad
     * @param string $length Length to pad to
     * @param string $pad_string String to pad with
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return static
     */
    public static function lpad(string $expression, string $length, string $pad_string, ?string $alias = null): self
    {
        return new self('LPAD', [$expression, $length, $pad_string], $alias);
    }

    /**
     * Buidl a ROUND SQL function call
     * @param string $expression Expression to round
     * @param int $precision Precision to round to
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return static
     */
    public static function round(string $expression, int $precision = 0, ?string $alias = null): self
    {
        return new self('ROUND', [$expression, $precision], $alias);
    }

    /**
     * Build a NULLIF SQL function call
     * @param string $expression Expression to check
     * @param string $value Value to check against
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return static
     */
    public static function nullIf(string $expression, string $value, ?string $alias = null): self
    {
        return new self('NULLIF', [$expression, $value], $alias);
    }

    /**
     * Build a COALESCE SQL function call
     * @param array $params Parameters to check
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return static
     */
    public static function coalesce(array $params, ?string $alias = null): self
    {
        return new self('COALESCE', $params, $alias);
    }

    /**
     * Build a LEAST SQL function call
     * @param array $params Parameters to check
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return static
     */
    public static function least(array $params, ?string $alias = null): self
    {
        return new self('LEAST', $params, $alias);
    }

    /**
     * Build a TIMESTAMPDIFF SQL function call
     * @param string $unit Unit to use
     * @param string $expression1 Expression to compare
     * @param string $expression2 Expression to compare
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return static
     */
    public static function timestampDiff(string $unit, string $expression1, string $expression2, ?string $alias = null): self
    {
        return new self('TIMESTAMPDIFF', [$unit, $expression1, $expression2], $alias);
    }

    /**
     * Build a BIT_COUNT SQL function call
     * @param string $expression Expression for the function
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return static
     */
    public static function bitCount(string $expression, ?string $alias = null): self
    {
        return new self('BIT_COUNT', [$expression], $alias);
    }

    /**
     * Build a DATEDIFF SQL function call
     * @param string $expression1 Expression to compare
     * @param string $expression2 Expression to compare
     * @param string|null $alias Function result alias (will be automatically quoted)
     * @return static
     */
    public static function dateDiff(string $expression1, string $expression2, ?string $alias = null): self
    {
        return new self('DATEDIFF', [$expression1, $expression2], $alias);
    }
}
