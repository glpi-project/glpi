#!/usr/bin/php
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
* @brief
*/

$cmd = $_SERVER["argv"][0];

function checkOne ($name, $tab="") {
   global $cmd;

   if (empty($tab)) {
      $tab = Toolbox::strtoupper("LANG$name");
   }

   $old = getcwd();

   if (is_dir($name."/trunk/locales") && is_file($name."/trunk/locales/fr_FR.php")) {
      echo "+ ----- $name -----\n";
      $dir = opendir($name."/trunk/locales");
      while (($file = readdir($dir)) !== false) {
         if (strpos($file, ".php") && $file!="fr_FR.php") {
            passthru("php $cmd $name/trunk/locales/fr_FR.php $name/trunk/locales/$file $tab\n");
         }
      }
      closedir($dir);

   } else {
      echo ("no $name/trunk/locales/fr_FR.php\n");
   }
   chdir($old);
}


function diffTab ($from, $dest, $name) {

   $nb = 0;

   if (is_array($from)) {
      foreach ($from as $ligne => $value) {
         if (isset($dest[$ligne])) {
            $nb += diffTab($from[$ligne], $dest[$ligne], $name."['$ligne']");
         } else {
            echo $name."['$ligne'] absent ($value)\n";
            $nb++;
         }
      }
   }
   //else  echo "$name ok (".$from." => ".$dest.")\n";

   return $nb;
}


if (isset($_SERVER["argc"]) && $_SERVER["argc"]==2 && $_SERVER["argv"][1]=="all") {

   // For 0.71 plugin only
   $exception = array("data_injection"  => "DATAINJECTIONLANG",
                      "hole"            => "LANG_HOLE",
                      "backups"         => "LANGBACKUP",
                      "reports"         => "GEDIFFREPORTLANG",
                      "mass_ocs_import" => "OCSMASSIMPORTLANG");

   $dir = opendir(".");
   while (($file = readdir($dir)) !== false) {
      if (is_dir($file) && substr($file,0,1)!=".") {
         checkOne($file,
                  (is_file($file."/trunk/hook.php") ? "LANG" : (isset($exception[$file])
                                                                     ? $exception[$file] : "")));
      }
   }
   closedir($dir);

} else if (isset($_SERVER["argc"]) && $_SERVER["argc"]>=3) {

   $nomtab = ($_SERVER["argc"]>=4 ? $_SERVER["argv"][3] : "LANG");

   require $_SERVER["argv"][1];
   isset($GLOBALS[$nomtab]) or die ($nomtab . " not defined in " . $_SERVER["argv"][1] . "\n");
   $from = $GLOBALS[$nomtab];

   unset ($GLOBALS[$nomtab]);

   require $_SERVER["argv"][2];
   isset($GLOBALS[$nomtab]) or die ($nomtab . " not defined in " . $_SERVER["argv"][2] . "\n");
   $dest = $GLOBALS[$nomtab];

   $nb = 0;

   printf ("Contrôle %s dans %s\n", $nomtab, $_SERVER["argv"][2]);
   $nb += diffTab($from, $dest, '$'.$nomtab);
   printf ("Contrôle %s dans %s\n", $nomtab, $_SERVER["argv"][1]);
   $nb += diffTab($dest, $from, '$'.$nomtab);

   if ($nb) {
      echo "$nb erreur(s) détectée(s) : au boulot !\n";
   } else {
      echo "C'est bon :)\n";
   }

} else {
   echo "\nusage $cmd  langue1   langue2   [ nomtableau | LANG ]\n";
   echo "\nusage $cmd  all\n\n";
}
?>