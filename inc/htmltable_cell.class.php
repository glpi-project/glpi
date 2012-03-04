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


class HTMLTable_Cell extends HTMLTable_Entity {

   private $row;
   private $header;
   private $father;
   private $items_id;
   private $sons = array();

   function __construct($row, $header, $content, HTMLTable_Cell $father = NULL, $items_id = 0) {
      parent::__construct($content);
      $this->row = $row;
      $this->header = $header;
      $this->father = $father;
      $this->items_id = $items_id;

      $this->itemtype = $this->header->getItemType();
      if ((!empty($this->itemtype)) && ($this->items_id == 0)) {
         throw new Exception(__('Implementation error : header requires an item id'));
      }

      if (!is_null($this->father)) {

         if ($this->father->row != $this->row) {
            throw new Exception(__('Implementation error : cell and its father must have the same row'));
         }

         if ($this->father->header != $this->header->getFather()) {
            throw new Exception(__('Implementation error : cell and its father are not coherent regarding headers'));
         }

        $this->father->addSon($this, $header);
      } else if (!is_null($this->header->getFather())) {
         throw new Exception(__('Implementation error : cell must have a father'));
      }
   }

   function getHeader() {
      return $this->header;
   }

   function addSon(HTMLTable_Cell $son, HTMLTable_Header $sons_header) {
      if (!isset($this->sons[$sons_header->getName()])) {
         $this->sons[$sons_header->getName()] = array();
      }
      $this->sons[$sons_header->getName()][] = $son;
   }

   function computeNumberOfLines() {
      if (!isset($this->numberOfLines)) {
         $this->numberOfLines = 1;
         if (count($this->sons) > 0) {
            foreach ($this->sons as $headered_sons) {
               $numberOfLinesForHeader = 0;
               foreach ($headered_sons as $son) {
                  $numberOfLinesForHeader += $son->computeNumberOfLines();
               }
               if ($this->numberOfLines < $numberOfLinesForHeader) {
                  $this->numberOfLines = $numberOfLinesForHeader;
               }
            }
         }
      }
      return $this->numberOfLines;
   }


   function computeStartEnd(&$start) {
      if (!isset($this->start)) {
         $this->start = $start;
         foreach ($this->sons as $sons_by_header) {
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


   function display($index) {
      if (($index >= $this->start) && ($index < $this->start + $this->numberOfLines)) {
         if ($index == $this->start) {
            if (!empty($this->itemtype)) {
               Session::addToNavigateListItems($this->itemtype, $this->items_id);
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
