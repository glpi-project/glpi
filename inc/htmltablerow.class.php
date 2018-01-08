<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}


/**
 * @since 0.84
**/
class HTMLTableRow extends HTMLTableEntity {

   private $group;
   private $empty              = true;
   private $cells              = [];
   private $numberOfSubRows    = 1;
   private $linesWithAttributs = [];


   /**
    * @param $group
   **/
   function __construct($group) {
      $this->group = $group;
   }


   function getGroup() {
      return $this->group;
   }


   function notEmpty() {
      return !$this->empty;
   }


   function getNumberOfsubRows() {
      return $this->numberOfSubRows;
   }


   function createAnotherRow() {
      return $this->group->createRow();
   }


   /**
    * @param $lineIndex
    * @param $attributs
   **/
   function addAttributForLine($lineIndex, $attributs) {
      $this->linesWithAttributs[$lineIndex] = $attributs;
   }


   /**
    * @param $header    HTMLTableHeader object
    * @param $content
    * @param $father    HTMLTableCell object (default NULL)
    * @param $item      CommonDBTM object: The item associated with the current cell (default NULL)
   **/
   function addCell(HTMLTableHeader $header, $content, HTMLTableCell $father = null,
                    CommonDBTM $item = null) {

      if (!$this->group->haveHeader($header)) {
         throw new Exception('Unavailable header !');
      }

      $header_name = $header->getCompositeName();
      if (!isset($this->cells[$header_name])) {
         $this->cells[$header_name] = [];
      }

      $cell = new HTMLTableCell($this, $header, $content, $father, $item);
      $this->cells[$header_name][] = $cell;
      $this->empty = false;
      return $cell;
   }


   function prepareDisplay() {

      if ($this->empty) {
         return false;
      }

      // First, compute the total nomber of rows ...
      $this->numberOfSubRows = 0;
      foreach ($this->cells as $cellsOfHeader) {

         if (isset($cellsOfHeader[0])) {
            $header = $cellsOfHeader[0]->getHeader();
            if (is_null($header->getFather())) {

               $numberOfSubRowsPerHeader = 0;
               foreach ($cellsOfHeader as $cell) {
                  $cell->computeNumberOfLines();
                  $numberOfSubRowsPerHeader += $cell->getNumberOfLines();
               }
               if ($this->numberOfSubRows < $numberOfSubRowsPerHeader) {
                  $this->numberOfSubRows = $numberOfSubRowsPerHeader;
               }
            }
         }
      }

      // Then notify each cell and compute its starting row
      foreach ($this->cells as $cellsOfHeader) {

         if (isset($cellsOfHeader[0])) {
            $header = $cellsOfHeader[0]->getHeader();

            // Only do this for cells that don't have father: they will propagate this to there sons
            if (is_null($header->getFather())) {

               HTMLTableCell::updateCellSteps($cellsOfHeader, $this->numberOfSubRows);

               $start = 0;
               foreach ($cellsOfHeader as $cell) {
                  $cell->computeStartEnd($start);
               }
            }
         }
      }

      return true;
   }


   /**
    * @param $headers
   **/
   function displayRow($headers) {

      echo "\t<tbody";
      $this->displayEntityAttributs();
      echo ">\n";
      for ($i = 0; $i < $this->numberOfSubRows; $i++) {
         if (isset($this->linesWithAttributs[$i])) {
            $options = $this->linesWithAttributs[$i];
         } else {
            $options = [];
         }
         echo "\t\t<tr class='tab_bg_1'>\n";
         foreach ($headers as $header) {
            $header_name = $header->getCompositeName();
            if (isset($this->cells[$header_name])) {
               $display = false;
               foreach ($this->cells[$header_name] as $cell) {
                  $display |= $cell->displayCell($i, $options);
               }
               if (!$display) {
                  echo "\t\t\t<td colspan='".$header->getColSpan()."'";
                  $header->displayEntityAttributs($options);
                  echo "></td>\n";
               }
            } else {
               echo "\t\t\t<td colspan='".$header->getColSpan()."'";
               $header->displayEntityAttributs($options);
               echo "></td>\n";
            }
         }
         echo "\t\t</tr>\n";
      }
      echo "\t</tbody>\n";
   }


   /**
    * @param $name
    * @param $sub_name  (default NULL)
   */
   function getHeaderByName($name, $sub_name = null) {
      return $this->group->getHeaderByName($name, $sub_name);
   }
}
