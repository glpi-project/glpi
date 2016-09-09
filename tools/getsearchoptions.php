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

define('DO_NOT_CHECK_HTTP_REFERER', 1);

// Ensure current directory when run from crontab
chdir(dirname($_SERVER["SCRIPT_FILENAME"]));

if (isset($_SERVER['argv'])) {
   for ($i=1 ; $i<$_SERVER['argc'] ; $i++) {
      $it = explode("=",$_SERVER['argv'][$i],2);
      $it[0] = preg_replace('/^--/','',$it[0]);

      $_GET[$it[0]] = (isset($it[1]) ? $it[1] : true);
   }
}

if (isset($_GET['help'])) {
   echo "\nUsage : php getsearchoptions.php --type=<itemtype> [ --lang=<locale> ]\n\n";
   exit (0);
}

include ('../inc/includes.php');

if (!isset($_GET['type'])) {
   die("** mandatory option 'type' is missing\n");
}
if (!class_exists($_GET['type'])) {
   die("** unknown type\n");
}
if (isset($_GET['lang'])) {
   Session::loadLanguage($_GET['lang']);
}

$opts = &Search::getOptions($_GET['type']);
$sort = array();
$group = 'N/A';

foreach ($opts as $ref => $opt) {
   if (is_array($opt)) {
      $sort[$ref] = $group . " / " . $opt['name'];
   } else {
      $group = $opt;
   }
}
ksort($sort);
if (!isCommandLine()) {
   header("Content-type: text/plain");
}
print_r($sort);
?>
