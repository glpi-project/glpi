<?php
/*
 * @version $Id: HEADER 15930 2011-10-25 10:47:55Z jmd $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2012 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
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

// ----------------------------------------------------------------------
// Original Author of file: Damien Touraine
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}


/**
 * @since version 0.84
**/
class HTMLTable_Row extends HTMLTable_Entity {

   private $group;
   private $empty = true;
   private $cells = array();
   private $numberOfSubRows = 1;


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
    * @param $header    HTMLTable_Header object
    * @param $content
    * @param $father    HTMLTable_Cell object (default NULL)
    * @param $item      The item associated with the current cell (default NULL)
    */
   function addCell(HTMLTable_Header $header, $content, HTMLTable_Cell $father=NULL,
                    CommonDBTM $item = NULL) {

      if (!$this->group->haveHeader($header)) {
         throw new Exception('Unavailable header !');
      }

      $header_name = $header->getCompositeName();
      if (!isset($this->cells[$header_name])) {
         $this->cells[$header_name] = array();
      }

      $cell = new HTMLTable_Cell($this, $header, $content, $father, $item);
      $this->cells[$header_name][] = $cell;
      $this->empty = false;
      return $cell;
   }


   function prepareDisplay() {

      if ($this->empty) {
         return false;
      }

      $this->numberOfSubRows = 0;
      foreach ($this->cells as $cellsOfHeader) {
         $numberOfSubRowsPerHeader = 0;
         foreach ($cellsOfHeader as $cell) {
            $numberOfSubRowsPerHeader += $cell->computeNumberOfLines();
         }
         if ($this->numberOfSubRows < $numberOfSubRowsPerHeader) {
            $this->numberOfSubRows = $numberOfSubRowsPerHeader;
         }
      }

      foreach ($this->cells as $cellsOfHeader) {
         $start = 0;
         foreach ($cellsOfHeader as $cell) {
            $cell->computeStartEnd($start);
         }
      }

      return true;
   }


   /**
    * @param $headers
   **/
   function display($headers) {

      echo "\t\t<tbody";
      $this->displayEntityAttributs();
      echo ">";
      for ($i = 0 ; $i < $this->numberOfSubRows ; $i++) {
         echo "\t\t<tr>";
         foreach ($headers as $header) {
            $header_name = $header->getCompositeName();
            if (isset($this->cells[$header_name])) {
               $display = false;
               foreach ($this->cells[$header_name] as $cell) {
                  $display |= $cell->display($i);
               }
               if (!$display) {
                  echo "\t\t\t<td colspan='".$header->getColSpan()."'></td>\n";
               }
            } else {
               echo "\t\t\t<td colspan='".$header->getColSpan()."'></td>\n";
            }
         }
         echo "\t\t</tr>";
      }
      echo "\t\t</tbody>\n";
   }

   function getHeader($header_name, $sub_header_name) {
      return $this->group->getHeader($header_name, $sub_header_name);
   }
}
?>
