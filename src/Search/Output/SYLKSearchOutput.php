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
final class SYLKSearchOutput extends ExportSearchOutput
{
    public static function cleanValue(string $value): string
    {
        $value = preg_replace('/\x0A/', ' ', $value);
        $value = preg_replace('/\x0D/', '', $value);
        $value = str_replace("\"", "''", $value);
        return str_replace("\n", " | ", $value);
    }

    public static function showEndLine(bool $is_header_line): string
    {
        return '';
    }

    public static function showBeginHeader(): string
    {
        return '';
    }

    public static function showHeader($rows, $cols, $fixed = 0): string
    {
        /**
         * @var array $SYLK_ARRAY
         * @var array $SYLK_HEADER
         * @var array $SYLK_SIZE
         */
        global $SYLK_ARRAY, $SYLK_HEADER, $SYLK_SIZE;
        $SYLK_ARRAY  = [];
        $SYLK_HEADER = [];
        $SYLK_SIZE   = [];
        // entetes HTTP
        header("Expires: Mon, 26 Nov 1962 00:00:00 GMT");
        header('Pragma: private'); /// IE BUG + SSL
        header('Cache-control: private, must-revalidate'); /// IE BUG + SSL
        header("Content-disposition: filename=glpi.slk");
        header('Content-type: application/octetstream');
        // entete du fichier
        echo "ID;PGLPI_EXPORT\n"; // ID;Pappli
        echo "\n";
        // formats
        echo "P;PGeneral\n";
        echo "P;P#,##0.00\n";       // P;Pformat_1 (reels)
        echo "P;P#,##0\n";          // P;Pformat_2 (entiers)
        echo "P;P@\n";              // P;Pformat_3 (textes)
        echo "\n";
        // polices
        echo "P;EArial;M200\n";
        echo "P;EArial;M200\n";
        echo "P;EArial;M200\n";
        echo "P;FArial;M200;SB\n";
        echo "\n";
        // nb lignes * nb colonnes
        echo "B;Y" . $rows;
        echo ";X" . $cols . "\n"; // B;Yligmax;Xcolmax
        echo "\n";
        return '';
    }

    public static function showHeaderItem($value, &$num, $linkto = "", $issort = 0, $order = "", $options = ""): string
    {
        /**
         * @var array $SYLK_HEADER
         * @var array $SYLK_SIZE
         */
        global $SYLK_HEADER, $SYLK_SIZE;
        $SYLK_HEADER[$num] = self::cleanValue($value);
        $SYLK_SIZE[$num]   = \Toolbox::strlen($SYLK_HEADER[$num]);
        $num++;
        return '';
    }

    public static function showItem($value, &$num, $row, $extraparam = ''): string
    {
        /**
         * @var array $SYLK_ARRAY
         * @var array $SYLK_SIZE
         */
        global $SYLK_ARRAY, $SYLK_SIZE;
        $value = DataExport::normalizeValueForTextExport($value ?? '');
        $value = preg_replace('/' . \Search::LBBR . '/', '<br>', $value);
        $value = preg_replace('/' . \Search::LBHR . '/', '<hr>', $value);
        $SYLK_ARRAY[$row][$num] = self::cleanValue($value);
        $SYLK_SIZE[$num]        = max(
            $SYLK_SIZE[$num],
            \Toolbox::strlen($SYLK_ARRAY[$row][$num])
        );
        $num++;
        return '';
    }

    public static function showFooter($title = "", $count = null): string
    {
        /**
         * @var array $SYLK_ARRAY
         * @var array $SYLK_HEADER
         * @var array $SYLK_SIZE
         */
        global $SYLK_ARRAY, $SYLK_HEADER, $SYLK_SIZE;
        // largeurs des colonnes
        $out = '';
        foreach ($SYLK_SIZE as $num => $val) {
            $out .= "F;W" . $num . " " . $num . " " . min(50, $val) . "\n";
        }
        $out .= "\n";
        // Header
        foreach ($SYLK_HEADER as $num => $val) {
            $out .= "F;SDM4;FG0C;" . ($num == 1 ? "Y1;" : "") . "X$num\n";
            $out .= "C;N;K\"$val\"\n";
            $out .= "\n";
        }
        // Data
        foreach ($SYLK_ARRAY as $row => $tab) {
            foreach ($tab as $num => $val) {
                $out .= "F;P3;FG0L;" . ($num == 1 ? "Y" . $row . ";" : "") . "X$num\n";
                $out .= "C;N;K\"$val\"\n";
            }
        }
        $out .= "E\n";
        return $out;
    }
}
