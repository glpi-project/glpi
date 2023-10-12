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

namespace Glpi\Search\Output;

use Glpi\Toolbox\DataExport;

/**
 *
 * @internal Not for use outside {@link Search} class and the "Glpi\Search" namespace.
 */
final class CSVSearchOutput extends ExportSearchOutput
{
    public static function cleanValue(string $value): string
    {
        return str_replace("\"", "''", $value);
    }

    public static function showEndLine(bool $is_header_line): string
    {
        return "\n";
    }

    public static function showBeginHeader(): string
    {
        return '';
    }

    public static function showHeader($rows, $cols, $fixed = 0): string
    {
        header("Expires: Mon, 26 Nov 1962 00:00:00 GMT");
        header('Pragma: private'); /// IE BUG + SSL
        header('Cache-control: private, must-revalidate'); /// IE BUG + SSL
        header("Content-disposition: filename=glpi.csv");
        header('Content-type: text/csv');
        // zero width no break space (for excel)
        echo"\xEF\xBB\xBF";
        return '';
    }

    public static function showHeaderItem($value, &$num, $linkto = "", $issort = 0, $order = "", $options = ""): string
    {
        $out = "\"" . self::cleanValue($value) . "\"" . $_SESSION["glpicsv_delimiter"];
        $num++;
        return $out;
    }

    public static function showItem($value, &$num, $row, $extraparam = ''): string
    {
        $value = DataExport::normalizeValueForTextExport($value ?? '');
        $value = preg_replace('/' . \Search::LBBR . '/', '<br>', $value);
        $value = preg_replace('/' . \Search::LBHR . '/', '<hr>', $value);
        $out   = "\"" . self::cleanValue($value) . "\"" . $_SESSION["glpicsv_delimiter"];
        $num++;
        return $out;
    }

    public static function showFooter($title = "", $count = null): string
    {
        return '';
    }
}
