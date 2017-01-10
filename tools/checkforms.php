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
* @brief Check forms opened / closed
*/

// since version 0.83.3
define('GLPI_ROOT', realpath('..'));

$dirs = array(GLPI_ROOT,GLPI_ROOT.'/inc/',
              GLPI_ROOT.'/ajax/',
              GLPI_ROOT.'/front/',
              GLPI_ROOT.'/install/');

foreach ($dirs as $dir) {
   if ($handle = opendir($dir)) {

      /* Ceci est la façon correcte de traverser un dossier. */
      while (false !== ($file = readdir($handle))) {
         if (($file != ".") && ($file != "..")
             && preg_match('/\.php$/',$file)) {
            checkFormsInFile($dir.'/'.$file);
         }
      }

      closedir($handle);
   }
}


function checkFormsInFile($file) {

   $inform =false;
   $handle = fopen($file, "r");
   $i      = 0;
   while (!feof($handle)) {
      $line = fgets($handle);
      $i++;
//       echo $i.$line;
      if ((stripos($line, '<form ') !== FALSE)
          || (stripos($line, 'Html::openMassiveActionsForm(') !== FALSE)
          || (stripos($line, 'showFormHeader(') !== FALSE)) {
         $lastopen = $i;
         if ($inform) {
            echo "$file line $i : open form in form\n";
         }
         $inform = true;
      }
      if ((stripos($line, 'Html::closeForm(') !== FALSE)
          || (stripos($line, 'showFormButtons(') !== FALSE)) {
         if (!$inform) {
            echo "$file line $i : close not opened form\n";
         }
         $inform = false;
      }

   }

   if ($inform) {
      echo "$file : form opened on line $lastopen but not closed\n";
   }
}

?>