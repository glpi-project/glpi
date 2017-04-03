<?php
/*
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 *  GLPIPDF class extends TCPDF
 *
 *  @since version 0.85
**/
class GLPIPDF extends TCPDF {
   private $total_count;

   /**
    * Page header
    *
    * @see TCPDF::Header()
   **/
   public function Header() {
      // Title
      $this->Cell(0, 15, $this->title, 0, false, 'C', 0, '', 0, false, 'M', 'M');
   }


   /**
    * Page footer
    *
    * @see TCPDF::Footer()
   **/
   public function Footer() {

      // Position at 15 mm from bottom
      $this->SetY(-15);
      $text = "GLPI PDF export - ".Html::convDate(date("Y-m-d"));
      if ($this->total_count != null) {
         $text .= " - " . sprintf(_n('%s item', '%s items', $this->total_count), $this->total_count);
      }
      $text .= " - ".$this->getAliasNumPage()."/".$this->getAliasNbPages();

      // Page number
      $this->Cell(0, 10, $text, 0, false, 'C', 0, '', 0, false, 'T', 'M');
   }


   /**
    * Get the list of available fonts
    *
    * @return Array of "filename" => "font name"
   **/
   public static function getFontList() {

      $list = array();
      $path = TCPDF_FONTS::_getfontpath();

      foreach (glob($path.'/*.php') as $font) {
         unset($name, $type);
         include $font;
         unset($cbbox, $cidinfo, $cw, $dw);
         $font = basename($font, '.php');

         // skip subfonts
         if (((substr($font, -1) == 'b') || (substr($font, -1) == 'i'))
             && isset($list[substr($font, 0, -1)])) {
            continue;
         }
         if (((substr($font, -2) == 'bi'))
             && isset($list[substr($font, 0, -2)])) {
            continue;
         }
         if (isset($name)) {
            if (isset($type) && ($type == 'cidfont0')) {
               // cidfont often have the same name (ArialUnicodeMS)
               $list[$font] = sprintf(__('%1$s (%2$s)'), $name, $font);
            } else {
               $list[$font] = $name;
            }
         }
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
   public function setTotalCount($count) {
      $this->total_count = $count;
      return $this;
   }
}
