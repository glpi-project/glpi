<?php
/*
 * @version $Id: timer.class.php 15932 2011-10-25 10:53:43Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

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


class HTMLTable {

   private $headers;
   private $rows;

   function __construct() {
      $this->headers = array();
      $this->rows = array();
   }

   function addGlobalName($name) {
      $this->globalName = $name;
   }

   function addHeader($value, $name, $fathers_name = "") {
      if (count($this->rows) == 0) {
         if (($fathers_name != "") && (!isset($this->headers[$fathers_name]))) {
            return;
         }
         $this->headers[$name] = array('value'        => $value,
                                       'fathers_name' => $fathers_name);
      }
   }

   private function computeAndGetCellTotalNumberOfRows($headers_name, $cells_id) {
      $cell = $this->currentRow[$headers_name][$cells_id];
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

   function getFather(&$row, $headers_name, $cells_id) {

      if (!isset($row[$headers_name][$cells_id])) {
         return false;
      }

      $cell = &$row[$headers_name][$cells_id];

      if ($cell['fathers_id'] == 0) {
         return false;
      }

      if ((!isset($this->headers[$headers_name])
           || ($this->headers[$headers_name]['fathers_name'] == ""))) {
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

   function addElement($value, $headers_name, $cells_id = 0, $fathers_id = 0) {
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
         if ((!isset($this->currentRow[$fathers_name]))
             || (!isset($this->currentRow[$fathers_name][$fathers_id]))) {
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

}
?>
