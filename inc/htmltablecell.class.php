<?php
/*
 * @version $Id$
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


class HTMLTableCellFatherSameRow         extends Exception {}
class HTMLTableCellFatherCoherentHeader  extends Exception {}
class HTMLTableCellWithoutFather         extends Exception {}

/**
 * @since version 0.84
**/
class HTMLTableCell extends HTMLTableEntity {

   private $row;
   private $header;
   private $father;
   private $sons = array();
   private $item;

   // List of rows that have specific attributs
   private  $attributForTheRow = false;

   /**
    * @param $row
    * @param $header
    * @param $content   see HTMLTableEntity#__construct()
    * @param $father    HTMLTableCell object (default NULL)
    * @param $item      CommonDBTM object: The item associated with the current cell (default NULL)
   **/
   function __construct($row, $header, $content, HTMLTableCell $father=NULL,
                        CommonDBTM $item=NULL) {

      parent::__construct($content);
      $this->row        = $row;
      $this->header     = $header;
      $this->father     = $father;

      if (!empty($item)) {
         $this->item = clone $item;
      } else {
         $this->item = NULL;
      }

      if (!is_null($this->father)) {

         if ($this->father->row != $this->row) {
            throw new HTMLTableCellSameRow();
         }

         if ($this->father->header != $this->header->getFather()) {

            if (($this->father->header instanceof HTMLTableHeader)
                && ($this->header->getFather() instanceof HTMLTableHeader)) {
               throw new HTMLTableCellFatherCoherentHeader($this->header->getFather()->getName() .
                                                            ' != ' .
                                                            $this->father->header->getName());
            }

            if ($this->father->header instanceof HTMLTableHeader) {
               throw new HTMLTableCellFatherCoherentHeader('NULL != '.
                                                            $this->father->header->getName());
            }

            if ($this->header->getFather() instanceof HTMLTableHeader) {
               throw new HTMLTableCellFatherCoherentHeader($this->header->getFather()->getName() .
                                                            ' != NULL');
            }

            throw new HTMLTableCellFatherCoherentHeader('NULL != NULL');

         }

         $this->father->addSon($this, $header);

      } else if (!is_null($this->header->getFather())) {
         throw new HTMLTableCellWithoutFather();
      }

      $this->header->checkItemType($this->item);

      $this->copyAttributsFrom($this->header);
      if (is_string($content)) {
         $string = trim($content);
         $string = str_replace('&nbsp;', '', $string);
         $string = str_replace('<br>', '', $string);
         if (!empty($string)) {
            $this->header->addCell();
         }
      } else {
         $this->header->addCell();
      }
   }


   /**
    * @param $attributForTheRow
   **/
   function setAttributForTheRow($attributForTheRow) {
      $this->attributForTheRow = $attributForTheRow;
   }


   function getHeader() {
      return $this->header;
   }


   function getItem() {

      if (!empty($this->item)) {
         return $this->item;
      }
      return false;
   }


   /**
    * @param $son          HTMLTableCell object
    * @param $sons_header  HTMLTableHeader object
   **/
   function addSon(HTMLTableCell $son, HTMLTableHeader $sons_header) {

      if (!isset($this->sons[$sons_header->getName()])) {
         $this->sons[$sons_header->getName()] = array();
      }
      $this->sons[$sons_header->getName()][] = $son;
   }


   function getNumberOfLines() {
      return $this->numberOfLines;
   }


   function computeNumberOfLines() {

      if (!isset($this->numberOfLines)) {
         $this->numberOfLines = 1;
         if (count($this->sons) > 0) {
            foreach ($this->sons as $headered_sons) {
               $numberOfLinesForHeader = 0;
               foreach ($headered_sons as $son) {
                  $son->computeNumberOfLines();
                  $numberOfLinesForHeader += $son->getNumberOfLines();
               }
               if ($this->numberOfLines < $numberOfLinesForHeader) {
                  $this->numberOfLines = $numberOfLinesForHeader;
               }
            }
         }
      }
   }


   /**
    * @param $value
   **/
   function addToNumberOfLines($value) {
      $this->numberOfLines += $value;
   }


   /**
    * @param $cells                 array
    * @param $totalNumberOflines
   **/
   static function updateCellSteps(array $cells, $totalNumberOflines) {

      $numberOfLines = 0;
      foreach ($cells as $cell) {
         $numberOfLines += $cell->getNumberOfLines();
      }

      $numberEmpty = $totalNumberOflines - $numberOfLines;
      $step        = floor($numberEmpty / (count($cells)));
      $last        = $numberEmpty % (count($cells));
      $index       = 0;

      foreach ($cells as $cell) {
         $cell->addToNumberOfLines($step + ($index < $last ? 1 : 0));
         $index ++;
      }
   }


   /**
    * @param &$start
   **/
   function computeStartEnd(&$start) {

      if (!isset($this->start)) {
         if ($this->attributForTheRow !== false) {
            $this->row->addAttributForLine($start, $this->attributForTheRow);
         }
         $this->start = $start;
         foreach ($this->sons as $sons_by_header) {

            self::updateCellSteps($sons_by_header, $this->getNumberOfLines());

            $son_start = $this->start;
            foreach ($sons_by_header as $son) {
               $son->computeStartEnd($son_start);
            }
         }
         $start += $this->numberOfLines;
      } else {
         $start = $this->start + $this->numberOfLines;
      }
   }


   /**
    * @param $index
    * @param $options   array
   **/
   function displayCell($index, array $options=array()) {

      if (($index >= $this->start)
          && ($index < ($this->start + $this->numberOfLines))) {

         if ($index == $this->start) {
            if ($this->item instanceof CommonDBTM) {
               Session::addToNavigateListItems($this->item->getType(), $this->item->getID());
            }
            echo "\t\t\t<td colspan='".$this->header->getColSpan()."'";
            if ($this->numberOfLines > 1) {
               echo " rowspan='".$this->numberOfLines."'";
            }
            $this->displayEntityAttributs($options);
            echo ">";
            $this->displayContent();
            echo "</td>\n";
         }
         return true;
      }
      return false;
   }
}
?>
