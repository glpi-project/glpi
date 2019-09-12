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

if (PHP_SAPI != 'cli') {
   echo "This script must be run from command line";
   exit();
}

include ('../inc/includes.php');

$DB->query("SET FOREIGN_KEY_CHECKS = '0';");
$result = $DB->list_tables();
$numtab = 0;

while ($t=$DB->fetchArray($result)) {
   $query = "ALTER TABLE `$t[0]`
             TYPE = innodb";
   $DB->query($query);
}

$relations = getDbRelations();

$query = [];
foreach ($relations as $totable => $rels) {
   foreach ($rels as $fromtable => $fromfield) {

      if ($fromtable[0]=="_") {
         $fromtable = substr($fromtable, 1);
      }

      if (!is_array($fromfield)) {
         $query[$fromtable][] = " ADD CONSTRAINT `". $fromtable."_".$fromfield."`
                                  FOREIGN KEY (`$fromfield`)
                                  REFERENCES `$totable` (`id`) ";
      } else {
         foreach ($fromfield as $f) {
            $query[$fromtable][] = " ADD CONSTRAINT `".$fromtable."_".$f."`
                                     FOREIGN KEY (`$f`)
                                     REFERENCES `$totable` (`id`) ";
         }
      }

   }
}


foreach ($query as $table => $constraints) {
   $q = "ALTER TABLE `$table` ";
   $first = true;

   foreach ($constraints as $c) {
      if ($first) {
         $first = false;
      } else {
         $q .= ", ";
      }
      $q .= $c;
   }

   echo $q."<br><br>";
   $DB->query($q) or die($q." ".$DB->error());

}

$DB->query("SET FOREIGN_KEY_CHECKS = 1;");

