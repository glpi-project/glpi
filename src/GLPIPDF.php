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

/**
 * @since 0.85
 */
class GLPIPDF extends TCPDF
{
    /**
     * @var int
     */
    private $total_count;

    private static $default_config = [
        'orientation'        => 'P',
        'unit'               => 'mm',
        'mode'               => 'UTF-8',
        'format'             => 'A4',
        'font_size'          => 8,
        'font'               => 'helvetica',

        'margin_left'        => 10,
        'margin_right'       => 10,
        'margin_top'         => 15,
        'margin_bottom'      => 15,
        'margin_header'      => 7,
        'margin_footer'      => 7,
    ];
    private array $config = [];

    public function __construct(array $config = [], int $count = null, string $title = null)
    {
        $config += self::$default_config;
        $this->config = $config;
        parent::__construct(
            $config['orientation'],
            $config['unit'],
            $config['format'],
            true,
            $config['mode']
        );

        if ($count !== null) {
            $this->setTotalCount($count);
        }

        if ($title !== null) {
            $this->SetTitle($title);
            $this->SetHeaderData('', '', $title, '');
        }

        $this->SetCreator('GLPI');
        $this->SetAuthor('GLPI');

        $this->SetFont($config['font'], '', $config['font_size']);
        $this->setHeaderFont([$config['font'], 'B', $config['font_size']]);
        $this->setFooterFont([$config['font'], 'B', $config['font_size']]);

        //set margins
        $this->SetMargins($config['margin_left'], $config['margin_top'], $config['margin_right']);
        $this->SetHeaderMargin($config['margin_header']);
        $this->SetFooterMargin($config['margin_footer']);

        //set auto page breaks
        $this->SetAutoPageBreak(true, $config['margin_bottom']);
        $this->AddPage();
    }

    /**
     * Page header
     *
     * @see TCPDF::Header()
    **/
    public function Header() // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        // Title
        $this->Cell(0, $this->config['margin_bottom'], $this->title, 0, false, 'C', 0, '', 0, false, 'M', 'M');
    }


    /**
     * Page footer
     *
     * @see TCPDF::Footer()
    **/
    public function Footer() // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        // Position at 15 mm from bottom
        $this->SetY(-$this->config['margin_bottom']);
        $text = sprintf("GLPI PDF export - %s", Html::convDate(date("Y-m-d")));
        if ($this->total_count != null) {
            $text .= " - " . sprintf(_n('%s item', '%s items', $this->total_count), $this->total_count);
        }
        $text .= sprintf(" - %s/%s", $this->getAliasNumPage(), $this->getAliasNbPages());

        // Page number
        $this->Cell(0, $this->config['margin_footer'], $text, 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }

    /**
     * Get the list of available fonts.
     *
     * @return array Array of "filename" => "font name"
     **/
    public static function getFontList()
    {

        $list = [];

        $path = TCPDF_FONTS::_getfontpath();

        // Includes will be made inside a function to ensure that declared variables are
        // only available inside the function scope, and will so not affect other elements from loop.
        // Also, varibales declared in font file will be automatically garbage collected (some are huge).
        $include_fct = function ($font_path) use (&$list) {
            $name = null;
            $type = null;

            include $font_path;

            if ($name === null) {
                return; // Not a font file
            }

            $font = basename($font_path, '.php');

            // skip subfonts
            if (
                ((substr($font, -1) == 'b') || (substr($font, -1) == 'i'))
                && isset($list[substr($font, 0, -1)])
            ) {
                return;
            }
            if (
                ((substr($font, -2) == 'bi'))
                && isset($list[substr($font, 0, -2)])
            ) {
                return;
            }

            if ($type == 'cidfont0') {
                // cidfont often have the same name (ArialUnicodeMS)
                $list[$font] = sprintf(__('%1$s (%2$s)'), $name, $font);
            } else {
                $list[$font] = $name;
            }
        };

        foreach (glob($path . '/*.php') as $font_path) {
            $include_fct($font_path);
        }
        return $list;
    }

    /**
     * Set total results count
     *
     * @param integer $count Total number of results
     *
     * @return GLPIPDF
     */
    public function setTotalCount($count)
    {
        $this->total_count = $count;
        return $this;
    }
}
