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

ini_set("memory_limit", "-1");
ini_set("max_execution_time", "0");

if ($argv) {
   for ($i=1; $i<count($argv); $i++) {
      //To be able to use = in search filters, enter \= instead in command line
      //Replace the \= by ° not to match the split function
      $arg   = str_replace('\=', '°', $argv[$i]);
      $it    = explode("=", $arg);
      $it[0] = preg_replace('/^--/', '', $it[0]);

      //Replace the ° by = the find the good filter
      $it           = str_replace('°', '=', $it);
      $_GET[$it[0]] = $it[1];
   }
}

include ('../inc/includes.php');

// No debug mode
$_SESSION['glpi_use_mode'] == Session::NORMAL_MODE;

if (isset($_GET["dictionnary"])) {
   $rulecollection = RuleCollection::getClassByType($_GET["dictionnary"]);
   if ($rulecollection) {
      if ($_GET["dictionnary"]=='RuleDictionnarySoftware' && isset($_GET["manufacturer"])) {
         $rulecollection->replayRulesOnExistingDB(0, 0, [], $_GET["manufacturer"]);
      } else {
         $rulecollection->replayRulesOnExistingDB();
      }
   }

} else {
   echo "Usage : php -q -f compute_dictionnary.php dictionnary=<option>  [ manufacturer=ID ]\n";
   echo "Options values :\n";
   echo "RuleDictionnarySoftware : softwares\n";
   echo "RuleDictionnaryManufacturer : manufacturers\n";
   echo "RuleDictionnaryPrinter : printers\n";

   echo "--- Models ---\n";
   echo "RuleDictionnaryComputerModel : computers\n";
   echo "RuleDictionnaryMonitorModel : monitors\n";
   echo "RuleDictionnaryPeripheralModel : peripherals\n";
   echo "RuleDictionnaryNetworkEquipmentModel : networking\n";
   echo "RuleDictionnaryPrinterModel : printers\n";
   echo "RuleDictonnaryPhoneModel : phones\n";

   echo "--- Types ---\n";
   echo "RuleDictionnaryComputerType : computers\n";
   echo "RuleDictionnaryMonitorType : monitors\n";
   echo "RuleDictionnaryPeripheralType : peripherals\n";
   echo "RuleDictionnaryNetworkEquipmentType : networking\n";
   echo "RuleDictionnaryPrinterType : printers\n";
   echo "RuleDictionnaryPhoneType : phones\n";

   echo "--- Operating System ---\n";
   echo "RuleDictionnaryOperatingSystem : OS\n";
   echo "RuleDictionnaryOperatingSystemVersion : OS Version\n";
   echo "RuleDictionnaryOperatingSystemServicePack : OS Service Pack\n";
   echo "RuleDictionnaryOperatingSystemArchitecture : OS Architecture\n";
}
