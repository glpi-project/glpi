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
class HTMLTable_ extends HTMLTable_Base {


   private $groups = array();


   function __construct() {
      parent::__construct(true);
   }


   /**
    * We can define a global name for the table : this will print as header that colspan all columns
    *
    * @param $name the name to print inside the header
    *
    * @return nothing
   **/
   function setTitle($name) {
      $this->title = $name;
   }


   /**
    * @param $header_name
    * @param $content
    * @param $father          HTMLTable_Header object (default NULL)
   **/
   function addHeader($header_name, $content, HTMLTable_SuperHeader $father = NULL) {

      try {
         if (count($this->groups) > 0) {
            throw new Exception('Implementation error: must define all headers before any subgroups');
         }
         return $this->appendHeader(new HTMLTable_SuperHeader($this, $header_name, $content,
                                                              $father));
      } catch (Exception $e) {
         echo __FILE__." ".__LINE__." : ".$e->getMessage()."<br>\n";
      }
   }


   /**
    * @param $name
    * @param $content
   **/
   function createGroup($name, $content) {

      if (!empty($name)) {
         if (!isset($this->groups[$name])) {
            $this->groups[$name] = new HTMLTable_Group($this, $name, $content);
         }
      }
      return $this->getGroup($name);
   }


   /**
    * @param $group_name
   **/
   function getGroup($group_name) {

      if (isset($this->groups[$group_name])) {
         return $this->groups[$group_name];
      }
      return false;
   }


   /**
    * Display the table
    *
    * @param $table_html_id   (default '')
    *
    * @return nothing (display only)
   **/
   function display($table_html_id='') {

      $totalNumberOfRow = 0;
      foreach ($this->groups as $group) {
         $group->prepareDisplay();
         $totalNumberOfRow += $group->getNumberOfRows();
      }

      $totalNumberOfColumn = 0;
      foreach ($this->getHeaders() as $header) {
         $colspan = $header['']->getColSpan();
         $totalNumberOfColumn += $colspan;
      }

      echo "<table class='tab_cadre_fixe'";
      if (!empty($table_html_id)) {
         echo " id='$table_html_id'";
      }
      echo ">";

      echo "\t<thead>";
      if (isset($this->title)) {
         echo "\t\t<tr><th colspan='$totalNumberOfColumn'>".$this->title."</th></tr>\n";
      }

      if ($totalNumberOfRow == 0) {
         echo "\t\t<tr><td class='center' colspan='$totalNumberOfColumn'>" . __('None') .
              "</td></tr>\n";

      } else {

         echo "\t\t<tr>";
         foreach ($this->getHeaderOrder() as $header_name) {
            $header = $this->getHeader($header_name);
            echo "\t\t".$header->getTableHeader()."\n";
         }
         echo "</tr>\n";
         echo "\t</thead>\n";

         echo "\t<tfoot>";
         echo "\t\t<tr>";
         foreach ($this->getHeaderOrder() as $header_name) {
            $header = $this->getHeader($header_name);
            echo "\t\t".$header->getTableHeader()."\n";
         }
         echo "</tr>\n";
         echo "\t</tfoot>\n";

         foreach ($this->groups as $group) {
            $group->display($totalNumberOfColumn);
         }
      }

      echo "</table>\n";

   }

}
?>
