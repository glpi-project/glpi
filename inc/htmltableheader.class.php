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
abstract class HTMLTableHeader extends HTMLTableEntity {

   private $name;
   private $father;
   private $itemtypes   = [];
   private $colSpan     = 1;
   private $numberCells = 0;


   /**
    * get the table of the header (for a subheader, it is the table of its super header)
    *
    * @return HTMLTableMain the table owning the current header
   **/
   abstract protected function getTable();


   /**
    * get its name and subname : usefull for instance to create an index for arrays
    *
    * @param $header_name [out]     string   header name
    * @param $subheader_name [out]  string   sub header name ( = '' in case of super header)
    *
    * @return nothing
   **/
   abstract function getHeaderAndSubHeaderName(&$header_name, &$subheader_name);


   /**
    * check to see if it is a super header or not
    *
    * @return true if this is a super header
   **/
   abstract function isSuperHeader();


   /**
    * @param $name      string   the name of the header
    * @param $content            see HTMLTableEntity#__construct()
    * @param $father             HTMLTableHeader object:
    *                            the father of the current column (default NULL)
   **/
   function __construct($name, $content, HTMLTableHeader $father = null) {

      parent::__construct($content);

      $this->name           = $name;
      $this->father         = $father;
   }


   /**
    * @param $itemtype
    * @param $title         (default '')
   **/
   function setItemType($itemtype, $title = '') {
      $this->itemtypes[$itemtype] = $title;
   }


   /**
    * @param $item      CommonDBTM object (default NULL)
   **/
   function checkItemType(CommonDBTM $item = null) {

      if (($item === null) && (count($this->itemtypes) > 0)) {
         throw new Exception('Implementation error: header requires an item');
      }
      if ($item !== null) {
         if (!isset($this->itemtypes[$item->getType()])) {
            throw new Exception('Implementation error: type mismatch between header and cell');
         }
         $this->getTable()->addItemType($item->getType(), $this->itemtypes[$item->getType()]);
      }
   }


   function getName() {
      return $this->name;
   }


   /**
    * @param $colSpan
   **/
   function setColSpan($colSpan) {
      $this->colSpan = $colSpan;
   }


   function addCell() {
      $this->numberCells++;
   }


   function hasToDisplay() {
      return ($this->numberCells > 0);
   }


   function getColSpan() {
      return $this->colSpan;
   }


   /**
    * @param $with_content do we displaye the content ?
    * @param $main_header  main header (from table) or secondary (from group) ? (true by default)
   **/
   function displayTableHeader($with_content, $main_header = true) {

      if ($main_header) {
         echo "<th";
      } else {
         echo "<td class='subheader'";
      }
      echo " colspan='".$this->colSpan."'>";
      if ($with_content) {
         $this->displayContent();
      } else {
         echo "&nbsp;";
      }
      if ($main_header) {
         echo "</th>";
      } else {
         echo "</td>";
      }
   }


   function getFather() {
      return $this->father;
   }
}
