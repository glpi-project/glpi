<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

chdir(__DIR__);
include ('../inc/includes.php');

$args = [ 'sql' => false ];
if ($_SERVER['argc']>1) {
   for ($i=1; $i<count($_SERVER['argv']); $i++) {
      $it           = explode("=", $argv[$i], 2);
      $it[0]        = preg_replace('/^--/', '', $it[0]);
      $args[$it[0]] = (isset($it[1]) ? $it[1] : true);
   }
}

if (isset($args['item'])) {
   $type = $args['item'];

   class_exists($type) or die("** class $type is not found\n");
   is_subclass_of($type, 'CommonDBTM') or die("** $type not a persistent object\n");

   if ($args['sql']) {
      echo PHP_EOL . Migration::getCreateTable($type) . PHP_EOL;
   } else {
      var_dump($type::getSchema());
   }
} else {
   echo <<< EOT

usage    {$_SERVER['argv'][0]}  [ options ... ]

     --item=name         display schema for table used for 'name' class


EOT;
}