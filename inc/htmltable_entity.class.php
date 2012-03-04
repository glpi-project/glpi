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


abstract class HTMLTable_Entity {

   private $html_id;
   private $html_style;
   private $html_class;


   function setHTMLID($html_id) {
      $this->html_id = $html_id;
   }


   function setHTMLStyle($html_style) {
      $this->html_style = $html_style;
   }


   function setHTMLClass($html_class) {
      $this->html_class = $html_class;
   }


   function displayEntityAttributs() {
      if (!empty($this->html_id)) {
         echo " id='".$this->html_id."'";
      }
      if (!empty($this->html_style)) {
         echo " style='".$this->html_style."'";
      }
      if (!empty($this->html_class)) {
         echo " class='".$this->html_class."'";
      }
   }
}

?>
