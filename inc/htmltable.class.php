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


/// HTMLTable class
/// Create a smart HTML table. The table allows cells to depend on other ones. As such, it is
/// possible to have rowspan for cells that are "father" of other ones. If a "father" has several
/// sons, then, it "rowspans" on all.
/// Each column has a header defined by its value (ie. : what is printed inside the "th" cell), its
/// name (the one that is used by a cell to know where to put it) and a fathers_name, that is the
/// name of the column that is the father.
/// We work on "active" row. We can add elements in the active row. But we must "closeRow()" the
/// active row each time we skip to next row
///
/// Each cell of the table is define by a column name and its ID inside the column.
///
/// For further explaination, refer to NetworkPort and all its dependencies (NetworkName, IPAddress,
/// IPNetwork, ...)
/// @since 0.84
class HTMLTable {

   // The headers of each column
   private $headers;
   // All rows as an array
   private $rows;


   function __construct() {

      $this->headers = array();
      $this->rows    = array();
   }


   /**
    * We can define a global name for the table : this will print as header that colspan all columns
    *
    * @param $name the name to print inside the header
    *
    * @return nothing
   **/
   function addGlobalName($name) {
      $this->globalName = $name;
   }


   /**
    * Define a new columne by its header
    *
    * @param $value                 the value to print inside the column header
    * @param $name                  the name of the column
    * @param $fathers_name          the name of the father of the column.
    *                               Use "" if there is no father for the current column (default '')
    * @param $itemtype_forListItems (default '')
    *
    * @return nothing
   **/
   function addHeader($value, $name, $fathers_name="", $itemtype_forListItems="") {

      if (count($this->rows) == 0) {
         if (($fathers_name != "") && (!isset($this->headers[$fathers_name]))) {
            return;
         }
         $this->headers[$name] = array('value'                 => $value,
                                       'fathers_name'          => $fathers_name,
                                       'itemtype_forListItems' => $itemtype_forListItems);
      }
   }


   /**
    * Recursively compute the total number of rows of a given cell
    * (ie.: intelligent cumulation of each sons number of row)
    *
    * @param $headers_name  the name of the column
    * @param $cells_id      the id of the cell inside the column
    *
    * @return the number of rows of the current cell
   **/
   private function computeAndGetCellTotalNumberOfRows($headers_name, $cells_id) {

      $cell          = $this->currentRow[$headers_name][$cells_id];
      $numberOfLines = 1;
      foreach ($cell['sons'] as $sons_header => $sons) {
         $sonsNumberOfLines = 0;
         foreach ($sons as $son) {
            $sonsNumberOfLines += $this->computeAndGetCellTotalNumberOfRows($sons_header, $son);
         }
         if ($numberOfLines < $sonsNumberOfLines) {
            $numberOfLines = $sonsNumberOfLines;
         }
      }
      $this->currentRow[$headers_name][$cells_id]['numberOfLines'] = $numberOfLines;
      return $numberOfLines;
   }


   /**
    * Get the father of a given cell
    *
    * @param &$row          the current row (ie : when we use it during the displau of the table)
    * @param $headers_name  the name of the column
    * @param $cells_id      the id of the cell inside the column
    *
    * @return false if there is no father, or the father cell
   **/
   private function getFather(&$row, $headers_name, $cells_id) {

      if (!isset($row[$headers_name][$cells_id])) {
         return false;
      }

      $cell = &$row[$headers_name][$cells_id];

      if ($cell['fathers_id'] == 0) {
         return false;
      }

      if (!isset($this->headers[$headers_name])
          || $this->headers[$headers_name]['fathers_name'] == "") {
         return false;
      }

      $fathers_name = $this->headers[$headers_name]['fathers_name'];

      if (!isset($row[$fathers_name])) {
         return false;
      }

      $fathers_columns = $row[$fathers_name];

      if (isset($fathers_columns[$cell['fathers_id']])) {
         return $fathers_columns[$cell['fathers_id']];
      }
      return false;
   }


   /**
    * Add a cell in the current row
    *
    * @param $value         the value to print inside the cell or the method to call
    * @param $headers_name  the name of the column
    * @param $cells_id      the id of the cell inside the column (default 0)
    * @param $fathers_id    the id of the father inside its column (the column of the father is
    *                       given during the definition of the columne), 0 if there is no father
    *                       (default 0)
    *
    * @return nothing
   **/
   function addElement($value, $headers_name, $cells_id=0, $fathers_id=0) {

      if (!isset($this->currentRow)) {
         $this->currentRow = array();
      }

      if (!isset($this->headers[$headers_name])) {
         return;
      }

      $header = $this->headers[$headers_name];

      if (!isset($this->currentRow[$headers_name])) {
         $this->currentRow[$headers_name] = array();
      }

      if ($header['fathers_name'] != '') {
         $fathers_name = $header['fathers_name'];
         if (!isset($this->currentRow[$fathers_name])
             || !isset($this->currentRow[$fathers_name][$fathers_id])) {
            return;
         }
         if (!isset($this->currentRow[$fathers_name][$fathers_id]['sons'][$headers_name])) {
            $this->currentRow[$fathers_name][$fathers_id]['sons'][$headers_name] = array();
         }
         $this->currentRow[$fathers_name][$fathers_id]['sons'][$headers_name][] += $cells_id;
      }

      $this->currentRow[$headers_name][$cells_id] = array('value'       => $value,
                                                          'fathers_id'  => $fathers_id,
                                                          'sons'        => array());
   }


   /**
    * close the current row
    *
    * Used at the end of a line. computer all rendering elements such as rowspan or total number of
    * lines used by the row
    *
    * @return nothing
   **/
   function closeRow() {

      $numberOfLines = 0;
      foreach ($this->currentRow as $headers_name => $cells) {
         $start = 0;
         foreach ($cells as $cells_id => $cell) {
            $cellNumberOfLines = $this->computeAndGetCellTotalNumberOfRows($headers_name,
                                                                           $cells_id);
            if ($numberOfLines < $cellNumberOfLines) {
               $numberOfLines = $cellNumberOfLines;
            }
         }
      }
      foreach ($this->currentRow as $headers_name => $cells) {
         $endLine = 0;
         foreach($this->currentRow[$headers_name] as $cells_id => $cell) {
            $startLine = $endLine;
            $father    = $this->getFather($this->currentRow, $headers_name, $cells_id);
            if ($father !== false) {
               if ($startLine < $father['start']) {
                  $startLine = $father['start'];
               }
            }
            $rowspan = $this->currentRow[$headers_name][$cells_id]['numberOfLines'];
            // TODO : enhance the presentation by setting the rowspan according to the number of identical cells and so on
            if ($father === false) {
               if (count($cells) == 1) {
                  $rowspan = $numberOfLines;
               }
            } else {
               if (count($father['sons'][$headers_name]) == 1) {
                  $rowspan = $father['rowspan'];
               }
            }
            $endLine = $startLine + $rowspan;

            $this->currentRow[$headers_name][$cells_id]['start']   = $startLine;
            $this->currentRow[$headers_name][$cells_id]['rowspan'] = $rowspan;
            $this->currentRow[$headers_name][$cells_id]['end']     = $endLine;
         }
      }
      $this->rows[] = array('numberOfLines' => $numberOfLines,
                            'elements'      => $this->currentRow);
      unset($this->currentRow);
   }


   /**
    * Display the table
    *
    * @return nothing (display only)
   **/
   function display() {

      if (!isset($this->headers)) {
         return;
      }

      echo "<table class='tab_cadre_fixe'>";

      if (isset($this->globalName)) {
         echo "<tr><th colspan='".count($this->headers)."'>".$this->globalName."</th></tr>";
      }

      echo "<tr>";
      foreach ($this->headers as $header) {
         echo "<th>".$header['value']."</th>";
      }
      echo "</tr>\n";

      $previousNumberOfLines = 0;

      foreach ($this->rows as $row) {
         if (($previousNumberOfLines * $row['numberOfLines']) > 1) {
            echo "<tr><td colspan='".count($this->headers)."'><hr></td></tr>";
         }
         for ($i = 0 ; $i < $row['numberOfLines'] ; $i++) {
            echo "<tr>";
            foreach ($this->headers as $name => $header) {
               $display = false;
               if (isset($row['elements'][$name])) {
                  $cells = $row['elements'][$name];
                  foreach ($cells as $cells_id => $cell) {
                     if ($cell['start'] == $i) {
                        if (!empty($header['itemtype_forListItems'])) {
                           Session::addToNavigateListItems($header['itemtype_forListItems'],
                                                           $cells_id);
                        }
                        echo "<td";
                        if ($cell['rowspan'] > 1) {
                           echo " rowspan='".$cell['rowspan']."'";
                        }
                        echo ">";
                        $value = $cell['value'];
                        if (is_array($value) && isset($value['function'])
                            && isset($value['parameters'])) {
                           call_user_func_array ($value['function'], $value['parameters']);
                        } else {
                           echo $value;
                        }
                        echo "</td>\n";
                     }
                     if (($cell['start'] <= $i) && ($i < $cell['end'])) {
                        $display = true;
                        break;
                     }
                  }
               }
               if (!$display) {
                  echo "<td>&nbsp;</td>";
               }
            }
            echo "</tr>\n";
         }
         $previousNumberOfLines = $row['numberOfLines'];
      }
     echo "</table>\n";
   }


   /**
    * Get the number of columns (ie. : number of "headers")
    *
    * @return number of columns
   **/
   function getNumberOfColumns() {
      return count($this->headers);
   }


   /**
    * Get the number of rows. Be carefull : one row can have several lines, according to the
    * number of children one cell have
    *
    * @return number of rows
   **/
   function getNumberOfRows() {
      return count($this->rows);
   }


   /**
    * Get the order of the columns
    *
    * @return an array of the names of the columns
   **/
   function getColumnOrder() {
      return array_keys($this->headers);
   }


   /**
    * Modify the order of the columns. If a known header (ie defined by addHeader()) is not
    * present in the given order, then the column will be destroy from the table. No new header
    * will be added (ie : a name that is not known will not generate a new header).
    *
    * @param $order  array of the names of the columns
    *
    * @return (nothing)
   **/
    function setColumnOrder($order=array()) {

      if (!is_array($order)) {
         return;
      }

      $old_headers   = $this->headers;
      $this->headers = array();
      foreach ($order as $column) {
         if (isset($old_headers[$column])) {
            $this->headers[$column] = $old_headers[$column];
         }
      }
      unset($old_headers);
   }

}
?>
