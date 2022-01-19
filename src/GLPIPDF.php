<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
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

use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

/**
 * @since 0.85
 */
class GLPIPDF extends Mpdf
{
    /**
     * @var int
     */
    private $total_count;

    private static $default_config = [
        'mode'               => 'utf-8',
        'format'             => 'A4',
        'margin_left'        => 10,
        'margin_right'       => 10,
        'margin_top'         => 15,
        'margin_bottom'      => 15,
        'margin_header'      => 7,
        'margin_footer'      => 7,

        'useSubstitutions'   => true, // Substitute chars that are not available in current font
        'packTableData'      => true, // Reduce memory usage processing tables

        'tempDir'            => GLPI_TMP_DIR,
    ];

    public function __construct(array $config = [])
    {
        $config += self::$default_config;

        parent::__construct($config);

        $this->SetCreator('GLPI');
        $this->SetAuthor('GLPI');
        $this->SetAutoPageBreak(true, 15);

        $this->defineHeaderTemplate();
        $this->definesFooterTemplate();
    }

    public function SetTitle($title)
    {
        parent::SetTitle($title);
        $this->defineHeaderTemplate();
    }

    /**
     * Get the list of available fonts.
     *
     * @return Array of "font key" => "font name"
     **/
    public static function getFontList()
    {

        $list = [];

        $mpdf = new Mpdf(self::$default_config);

       // Extract PDF core fonts
        foreach ($mpdf->CoreFonts as $key => $name) {
            if (preg_match('/(B|I)$/', $key)) {
                continue; // Ignore Bold / Italic variants
            }
            $key = preg_replace('/^c/', '', $key);
            $list[$key] = $name;
        }

       // Extract embedded fonts
        $default_font_config = (new FontVariables())->getDefaults();
        foreach (array_keys($default_font_config['fontdata']) as $font_key) {
            try {
                $mpdf->AddFont($font_key);
            } catch (\Exception $e) {
                continue; // Ignore fonts that cannot be loaded.
            }
        }
        foreach ($mpdf->fonts as $key => $font) {
            $list[$key] = $font['name'];
        }

        asort($list);

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
        $this->definesFooterTemplate();
        return $this;
    }

    /**
     * Defines the header template.
     *
     * @return void
     */
    private function defineHeaderTemplate(): void
    {

        $html = <<<HTML
<table width="100%">
   <tr>
      <td align="center">
         <strong>{$this->title}</strong>
      </td>
   </tr>
</table>
HTML;

        $this->SetHTMLHeader($html);
    }

    /**
     * Defines the footer template.
     *
     * @return void
     */
    private function definesFooterTemplate(): void
    {

        $date = Html::convDate(date("Y-m-d"));
        $count = $this->total_count != null
         ? ' - ' . sprintf(_n('%s item', '%s items', $this->total_count), $this->total_count)
         : '';

        $html = <<<HTML
<table width="100%">
   <tr>
      <td align="center">
         <strong>GLPI PDF export - {$date} {$count} - {PAGENO}/{nbpg}</strong>
      </td>
   </tr>
</table>
HTML;

        $this->SetHTMLFooter($html);
    }
}
