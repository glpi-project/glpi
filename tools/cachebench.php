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

define('PER_LEVEL', 8);
define('COUNT', 1024);

require __DIR__ . '/../inc/includes.php';

// To bypass various right checks
$_SESSION['glpishowallentities'] = 1;
$_SESSION['glpicronuserrunning'] = "cron_phpunit";
$_SESSION['glpi_use_mode']       = Session::NORMAL_MODE;
$_SESSION['glpiactiveentities']  = [0];
$_SESSION['glpiactiveentities_string'] = "'0'";
$CFG_GLPI['root_doc']            = '/glpi';

$ent = new Entity();
$nb = countElementsInTable('glpi_entities');
if ($nb < 100000) {
   echo "+ Generate some entities\n";
   for ($a = 0; $a < PER_LEVEL; $a++) {
      $ida = $ent->add(['entities_id' => 0, 'name' => "ent $a"]);
      echo "$ida\r";
      for ($b = 0; $b < PER_LEVEL; $b++) {
         $idb = $ent->add(['entities_id' => $ida, 'name' => "s-ent $b"]);
         echo "$idb\r";
         for ($c = 0; $c < PER_LEVEL; $c++) {
            $idc = $ent->add(['entities_id' => $idb, 'name' => "ss-ent $c"]);
            //echo "$idc\r";
            for ($d = 0; $d < PER_LEVEL; $d++) {
               $idd = $ent->add(['entities_id' => $idc, 'name' => "sss-ent $d"]);
               //echo "$idd\r";
               for ($e = 0; $e < PER_LEVEL; $e++) {
                  $ide = $ent->add(['entities_id' => $idd, 'name' => "sss-ent $e"]);
                  //echo "$ide\r";
                  for ($f = 0; $f < PER_LEVEL; $f++) {
                     $idf = $ent->add(['entities_id' => $ide, 'name' => "sss-ent $f"]);
                     //echo "$idf\r";
                  }
               }
            }
         }
      }
   }

   //echo "Regenerate tree\n";
   //regenerateTreeCompleteName('glpi_entities');

   $nb = countElementsInTable('glpi_entities');
}
echo "+ Entities: $nb\n";

global $CONTAINER;
$cache_storage = $CONTAINER->get('application_cache')->getStorage();
echo "+ Cache: " . get_class($cache_storage) . "\n";

echo "+ Clear sons cache\n";
$DB->update(
   'glpi_entities',
   ['sons_cache' => null],
   [true]
);

$tps = microtime(true);
echo "+ Run with empty cache\n";
for ($i=0; $i<COUNT; $i++) {
   if (($i & 0x7f)==0) {
      echo "$i\r";
   }
   $t[$i] = $id = mt_rand(0, $nb);
   //$x = getSonsOf('glpi_entities', $id);
   $x = getAncestorsOf('glpi_entities', $id);
}
$tps = microtime(true) - $tps;
printf("> time: %.4f\n", $tps);

$tps = microtime(true);
echo "+ Run with populated cache\n";
for ($i=0; $i<COUNT; $i++) {
   if (($i & 0x7f)==0) {
      echo "$i\r";
   }
   $id = $t[$i];
   //$x = getSonsOf('glpi_entities', $id);
   $x = getAncestorsOf('glpi_entities', $id);
}
$tps = microtime(true) - $tps;
printf("> time: %.4f\n", $tps);

echo "+ Done\n";

/*

+ Entities: 299598
+ Cache: disabled
+ Clear sons cache
+ Run with empty cache
> time: 2.7468
+ Run with populated cache
> time: 2.4121
+ Done

+ Entities: 299598
+ Cache: Zend\Cache\Storage\Adapter\Apcu
+ Clear sons cache
+ Run with empty cache
> time: 2.8290
+ Run with populated cache
> time: 0.0335
+ Done

+ Entities: 299598
+ Cache: Zend\Cache\Storage\Adapter\Memcache
+ Clear sons cache
+ Run with empty cache
> time: 3.0366
+ Run with populated cache
> time: 0.1195
+ Done

+ Entities: 299598
+ Cache: Zend\Cache\Storage\Adapter\Redis
+ Clear sons cache
+ Run with empty cache
> time: 2.9524
+ Run with populated cache
> time: 0.1247
+ Done

+ Entities: 299593
+ Cache: Zend\Cache\Storage\Adapter\WinCache
+ Clear sons cache
+ Run with empty cache
> time: 5.1352
+ Run with populated cache
> time: 0.1560
+ Done

*/
