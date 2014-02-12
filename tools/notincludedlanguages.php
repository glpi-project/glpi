<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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

/** @file
* @brief Get all po files not used in GLPI 
*/

include ('../inc/includes.php');

// Control to clean lang file datas
foreach ($CFG_GLPI['languages'] as $key => $val) {
   if ($key.'.mo' != $val[1]) {
      echo $key.": not same key and filename\n";
   }
}


// Get missing
$dir   = opendir(GLPI_ROOT.'/locales');
$files = array();
while ($file = readdir($dir)) {
   if (($file != ".") && ($file != "..")) {
       if (preg_match("/(.*)\.mo$/i",$file,$reg)) {
         $lang = $reg[1];
         if (!isset($CFG_GLPI['languages'][$lang])) {
            echo $lang." is missing\n";
         }

       }

   }
}

?>
