<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT."/inc/includes.php");

$DB->query("SET FOREIGN_KEY_CHECKS = '0';");
$result = $DB->list_tables();
$numtab = 0;

while ($t=$DB->fetch_array($result)) {

   // on se  limite aux tables prefixees _glpi
   if (strstr($t[0],"glpi_")) {
      $query = "ALTER TABLE `$t[0]`
                TYPE = innodb";
      $DB->query($query);
   }
}

$relations = getDbRelations();

$query = array();
foreach ( $relations as $totable => $rels) {
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

?>
