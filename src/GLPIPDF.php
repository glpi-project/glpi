<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

use Com\Tecnick\Pdf\Font\FontPaths;

use function Safe\file_get_contents;
use function Safe\json_decode;

/**
 * @since 0.85
 */
class GLPIPDF extends TCPDF
{
    private int $total_count;

    private string $title = '';

    private static array $default_config = [
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

    public function __construct(array $config = [], ?int $count = null, ?string $title = null, bool $addpage = true)
    {
        if (
            isset($config['font'])
            && !in_array($config['font'], array_keys(self::getFontList()), true)
        ) {
            unset($config['font']);
        }

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
            $this->title = $title;
            $this->SetTitle($title);
            $this->SetHeaderData('', 0, $title, '');
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
        if ($addpage === true) {
            $this->AddPage();
        }
    }

    /**
     * Page header
     *
     * @see TCPDF::Header()
     *
     * @return void
    */
    public function Header()
    {
        // Title
        $this->Cell(0, $this->config['margin_bottom'], $this->title, 0, 0, 'C', false, '', 0, false, 'M', 'M');
    }


    /**
     * Page footer
     *
     * @see TCPDF::Footer()
     *
     * @return void
    */
    public function Footer()
    {
        // Position at 15 mm from bottom
        $this->SetY(-$this->config['margin_bottom']);
        $text = sprintf("GLPI PDF export - %s", Html::convDate(date("Y-m-d")));
        if ($this->total_count != null) {
            $text .= " - " . sprintf(_n('%s item', '%s items', $this->total_count), $this->total_count);
        }
        $text .= sprintf(" - %s/%s", $this->getAliasNumPage(), $this->getAliasNbPages());

        // Page number
        $this->Cell(0, $this->config['margin_footer'], $text, 0, 0, 'C', false, '', 0, false, 'T', 'M');
    }

    /**
     * Get the list of available fonts.
     *
     * Since TCPDF 7.0, fonts are provided by the `tecnickcom/tc-lib-pdf-font`
     * library as JSON definition files (the legacy PHP `.php`/`.z` descriptors
     * are gone). Definition files are searched in the tc-lib font directories
     * (and in `K_PATH_FONTS` when defined). Font assets must have been generated
     * beforehand (see the `make fonts` procedure); an empty list means no font
     * definition is available.
     *
     * @return array Array of "font key" => "font name"
     **/
    public static function getFontList()
    {
        $list = [];

        $register = static function (string $font_path) use (&$list): void {
            try {
                $definition = json_decode(file_get_contents($font_path), true);
            } catch (Throwable) {
                return; // Unreadable or invalid JSON, not a font definition file
            }

            $name = is_array($definition) ? ($definition['name'] ?? null) : null;
            $type = is_array($definition) ? ($definition['type'] ?? null) : null;
            if ($name === null || $name === '') {
                return; // Not a font definition file
            }

            $font = basename($font_path, '.json');

            // skip subfonts
            if (
                ((str_ends_with($font, 'b')) || (str_ends_with($font, 'i')))
                && isset($list[substr($font, 0, -1)])
            ) {
                return;
            }
            if (
                ((str_ends_with($font, 'bi')))
                && isset($list[substr($font, 0, -2)])
            ) {
                return;
            }

            if ($type === 'cidfont0') {
                // cidfont often have the same name (ArialUnicodeMS)
                $list[$font] = sprintf(__('%1$s (%2$s)'), $name, $font);
            } else {
                $list[$font] = $name;
            }
        };

        // Collect definition files first, then sort them so that base fonts are
        // processed before their bold/italic variants (needed by the subfont skip).
        $font_paths = [];
        foreach (FontPaths::buildAllowedPaths() as $font_dir) {
            if (!is_dir($font_dir)) {
                continue;
            }
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($font_dir, FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if ($file->isFile() && strtolower($file->getExtension()) === 'json') {
                    $font_paths[$file->getPathname()] = true;
                }
            }
        }
        $font_paths = array_keys($font_paths);
        sort($font_paths);

        foreach ($font_paths as $font_path) {
            $register($font_path);
        }

        asort($list);
        return $list;
    }

    /**
     * Set total results count
     *
     * @param int $count Total number of results
     *
     * @return GLPIPDF
     */
    public function setTotalCount($count)
    {
        $this->total_count = $count;
        return $this;
    }
}
