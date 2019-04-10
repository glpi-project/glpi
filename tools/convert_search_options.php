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

/**
 * An utility script to convert old getSearchOptions array to new ones,
 * see https://github.com/glpi-project/glpi/issues/1396
 */

if (count($argv) !== 2) {
   die("Usage: {$argv[0]} ItemType");
}
$itemtype = $argv[1];

define('DO_NOT_CHECK_HTTP_REFERER', 1);

// Ensure current directory when run from crontab
chdir(__DIR__);
include ('../inc/includes.php');

/**
 * Converts old search options arry to new one
 *
 * @param array  $opts  Old fashion search options array
 * @param string $table Item's table name
 *
 * @return void
 */
function convert($opts, $table) {
   $new = [];
   foreach ($opts as $key => $opt) {
      $newopt = ['id' => $key];

      if (!is_array($opt)) {
         $newopt['name'] = "TOCHECK $opt";
      } else {
         foreach ($opt as $k => $v) {
            $newopt[$k] = $v;
         }
      }
      $new[] = $newopt;
   }

   foreach ($new as $n) {
      echo "      \$tab[] = [\n";
      display($n, $table);
      echo "      ];\n\n";
   }
}

/**
 * Display new array fashion to copy/paste
 *
 * @param array  $array New fashion search options array
 * @param int    $pad   Pad length
 * @param string $tab   Tabs lenght for visual indentation
 *
 * @return void
 */
//display for copy/paste!
function display($array, $table, $pad = 20, $tab = '         ') {
   $i = 0;
   foreach ($array as $k => $v) {
      ++$i;

      $pk = str_pad("'$k'", $pad);

      if (is_array($v)) {
         echo "$tab$pk => [\n";
         $v = display($v, $table, $pad, $tab . '   ');
         echo "$tab]";
      } else {
         switch ($k) {
            case 'table':
               if ($v == $table) {
                  $v = '$this->getTable()';
               } else {
                  $v = "'$v'";
               }
               break;
            case 'name':
               $v = "__('$v')";
               break;
            case 'massiveaction':
            case 'forcegroupby':
            case 'usehaving':
            case 'nosearch':
            case 'nosort':
            case 'htmltext':
               $v = (empty($v) ? 'false' : 'true');
               break;
            case 'min':
            case 'max':
            case 'step':
               //integers, do not quote
               break;
            default:
               $v = "'$v'";
               break;
         }
         echo "$tab$pk => $v";
      }
      echo ($i < count($array) ? ',' : '') . "\n";
   }
}

$commondbtm = new CommonDBTM();
$commonopts = $commondbtm->searchOptions();

$item = new $itemtype();
$opts = $item->searchOptions();

$commonopts[1]['table'] = $item->getTable();
//do not proceed if item class does not define its own getSearchOptions method
if ($opts != $commonopts) {
   convert($opts, $item->getTable());
}

//handle getSearchOptionsToAdd
if (method_exists($item, 'getSearchOptionsToAdd')) {
   echo "\n\nFOR GETSEARCHOPTIONSTOADD\n\n";
   convert($item->getSearchOptionsToAdd(), $item->getTable());
}
