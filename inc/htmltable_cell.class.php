<?php
/*
 * @version $Id$
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


class HTMLTable_CellFatherSameRow         extends Exception {}
class HTMLTable_CellFatherCoherentHeader  extends Exception {}
class HTMLTable_CellWithoutFather         extends Exception {}

/**
 * @since version 0.84
**/
class HTMLTable_Cell extends HTMLTable_Entity {

   private $row;
   private $header;
   private $father;
   private $sons = array();
   private $item;


   /**
    * @param $row
    * @param $header
    * @param $content   see HTMLTable_Entity#__construct()
    * @param $father    HTMLTable_Cell object (default NULL)
    * @param $item      CommonDBTM object: The item associated with the current cell (default NULL)
   **/
   function __construct($row, $header, $content, HTMLTable_Cell $father=NULL,
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
      $this->itemtype = $this->header->getItemType();

      if (!is_null($this->father)) {

         if ($this->father->row != $this->row) {
            throw new HTMLTable_CellSameRow();
         }

         if ($this->father->header != $this->header->getFather()) {

            if (($this->father->header instanceof HTMLTable_Header)
                && ($this->header->getFather() instanceof HTMLTable_Header)) {
               throw new HTMLTable_CellFatherCoherentHeader($this->header->getFather()->getName() .
                                                            ' != ' .
                                                            $this->father->header->getName());
            }

            if ($this->father->header instanceof HTMLTable_Header) {
               throw new HTMLTable_CellFatherCoherentHeader('NULL != '.
                                                            $this->father->header->getName());
            }

            if ($this->header->getFather() instanceof HTMLTable_Header) {
               throw new HTMLTable_CellFatherCoherentHeader($this->header->getFather()->getName() .
                                                            ' != NULL');
            }

            throw new HTMLTable_CellFatherCoherentHeader('NULL != NULL');

         }

         $this->father->addSon($this, $header);

      } else if (!is_null($this->header->getFather())) {
         throw HTMLTable_CellWithoutFather();
      }

      if (!empty($this->itemtype)) {
         if (empty($this->item)) {
            throw new Exception('Implementation error: header requires an item');
         }
         if (!($this->item instanceof $this->itemtype)) {
            throw new Exception('Implementation error: type mismatch between header and cell');
         }
      }

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
    * @param $son          HTMLTable_Cell object
    * @param $sons_header  HTMLTable_Header object
   **/
   function addSon(HTMLTable_Cell $son, HTMLTable_Header $sons_header) {

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
   **/
   function display($index) {

      if (($index >= $this->start)
          && ($index < $this->start + $this->numberOfLines)) {

         if ($index == $this->start) {
            if (!empty($this->itemtype)) {
               Session::addToNavigateListItems($this->item->getType(), $this->item->getID());
            }
            echo "\t\t\t<td colspan='".$this->header->getColSpan()."'";
            if ($this->numberOfLines > 1) {
               echo " rowspan='".$this->numberOfLines."'";
            }
            $this->displayEntityAttributs();
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
