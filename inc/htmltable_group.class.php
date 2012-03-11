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
 * @since v ersion 0.84
**/
class HTMLTable_Group extends HTMLTable_Base {

   private $name;
   private $content;
   private $new_headers = array();
   private $table;
   private $rows = array();


   /**
    * @param $table     HTMLTable_ object
    * @param $name
    * @param $content
   **/
   function __construct(HTMLTable_ $table, $name, $content) {

      parent::__construct(false);
      $this->table      = $table;
      $this->name       = $name;
      $this->content    = $content;
   }


   /**
    * @see inc/HTMLTable_Base::getSuperHeader()
   **/
   protected function getSuperHeader($super_header_name) {
      return $this->table->getHeader($super_header_name);
   }


   function getName() {
      return $this->name;
   }


   /**
    * @param $header    HTMLTable_Header object
   **/
   function haveHeader(HTMLTable_Header $header) {
      $header->getHeaderAndSubHeaderName($header_name, $subheader_name);
      try {
         $subheaders = $this->getHeaders($header_name);
      } catch (HTMLTable_UnknownHeaders $e) {
         try {
            $subheaders = $this->table->getHeaders($header_name);
         } catch (HTMLTable_UnknownHeaders $e) {
            return false;
         }
      }
      return isset($subheaders[$subheader_name]);
   }


   /**
    * @param $super_header    HTMLTable_SuperHeader object
    * @param $name
    * @param $content
    * @param $father          HTMLTable_Header object (default NULL)
    */
   function addHeader(HTMLTable_SuperHeader $super_header, $name, $content,
                      HTMLTable_Header $father=NULL) {

      if (isset($this->ordered_headers)) {
         throw new Exception('Implementation error: must define all headers before any row');
      }
      return $this->appendHeader(new HTMLTable_SubHeader($super_header, $name, $content,
                                                         $father));
   }


   private function completeHeaders() {

      if (!isset($this->ordered_headers)) {
         $this->ordered_headers = array();

         foreach ($this->table->getHeaderOrder() as $header_name) {
            $header        = $this->table->getHeader($header_name);
            $header_names  = $this->getHeaderOrder($header_name);
            if (!$header_names) {
               $this->ordered_headers[] = $header;
            } else {
               foreach($header_names as $sub_header_name) {
                  $this->ordered_headers[] = $this->getHeader($header_name, $sub_header_name);
               }
            }
         }
      }
   }


   function createRow() {

      //$this->completeHeaders();
      $new_row      = new HTMLTable_Row($this);
      $this->rows[] = $new_row;
      return $new_row;
   }


   function prepareDisplay() {

      foreach ($this->table->getHeaderOrder() as $super_header_name) {
         $super_header = $this->table->getHeader($super_header_name);

         try {

            $sub_header_names   = $this->getHeaderOrder($super_header_name);
            $count = 0;

            foreach($sub_header_names as $sub_header_name) {
               $sub_header = $this->getHeader($super_header_name, $sub_header_name);
               if ($sub_header->hasToDisplay()) {
                  $count ++;
               }
            }

            if ($count == 0) {
               $this->ordered_headers[] = $super_header;
            } else {
               $super_header->updateNumberOfSubHeader($count);
               foreach($sub_header_names as $sub_header_name) {
                  $sub_header = $this->getHeader($super_header_name, $sub_header_name);
                  if ($sub_header->hasToDisplay()) {
                     $this->ordered_headers[]        = $sub_header;
                     $sub_header->numberOfSubHeaders = $count;
                  }
               }
            }

         } catch (HTMLTable_UnknownHeadersOrder $e) {
            $this->ordered_headers[] = $super_header;
         }
      }

      foreach ($this->rows as $row) {
         $row->prepareDisplay();
      }
   }


   /**
    * @param $totalNumberOfColumn
   **/
   function display($totalNumberOfColumn, array $params) {

      $p['display_super_for_each_group'] = true;
      $p['display_title_for_each_group'] = true;

      foreach ($params as $key => $val) {
         $p[$key] = $val;
      }

      if ($this->getNumberOfRows() > 0) {

         if (($p['display_title_for_each_group']) && (!empty($this->content))) {
            echo "\t<tr><th colspan='$totalNumberOfColumn'>".$this->content."</th></tr>\n";
         }

         if ($p['display_super_for_each_group']) {
            $this->table->displaySuperHeader();
         }

         echo "<tr>";
         foreach ($this->ordered_headers as $header) {
            if ($header instanceof HTMLTable_SubHeader) {
               $header->updateColSpan($header->numberOfSubHeaders);
               $with_content = true;
            } else {
               $with_content = false;
            }
            echo "\t\t";
            $header->displayTableHeader($with_content);
            echo "\n";
         }
         echo "</tr>";

         $previousNumberOfSubRows = 0;
         foreach ($this->rows as $row) {
            if (!$row->notEmpty()) {
               continue;
            }
            $currentNumberOfSubRow = $row->getNumberOfSubRows();
            if (($previousNumberOfSubRows * $currentNumberOfSubRow) > 1) {
               echo "<tr><td colspan='$totalNumberOfColumn'><hr></td></tr>";
            }
            $row->display($this->ordered_headers);
            $previousNumberOfSubRows = $currentNumberOfSubRow;
         }
      }
   }


   function getNumberOfRows() {

      $numberOfRows = 0;
      foreach ($this->rows as $row) {
         if ($row->notEmpty()) {
            $numberOfRows ++;
         }
      }
      return $numberOfRows;
   }
}
?>
