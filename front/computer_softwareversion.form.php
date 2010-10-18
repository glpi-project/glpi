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
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------


define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

$inst = new Computer_SoftwareVersion();

// From Computer - Software tab (add form or from not installed license)
if (isset($_REQUEST["install"])) {
   checkRight("software","w");
   if (isset($_REQUEST["softwareversions_id"]) && isset($_REQUEST["computers_id"])) {
      $inst->add(array('computers_id'        => $_REQUEST["computers_id"],
                       'softwareversions_id' => $_REQUEST["softwareversions_id"]));

      Event::log($_REQUEST["computers_id"], "computers", 5, "inventory",
               $_SESSION["glpiname"]." ".$LANG['log'][110]);
   }
   glpi_header($_SERVER['HTTP_REFERER']);

// From Computer - Software tab (installed software)
} else if (isset($_GET["uninstall"])) {
   checkRight("software","w");
   $inst->delete(array('id'=>$_GET["id"]));

   Event::log($_GET["computers_id"], "computers", 5, "inventory",
              $_SESSION["glpiname"]." ".$LANG['log'][111]);
   glpi_header($_SERVER['HTTP_REFERER']);

// From Computer - Software tab  (installed software)
} else if (isset($_POST["massuninstall"])) {
   checkRight("software","w");
   foreach ($_POST as $key => $val) {
      if (preg_match("/softversion_([0-9]+)/",$key,$ereg)) {
         $inst->delete(array('id' => $ereg[1]));
      }
   }
   Event::log($_POST["computers_id"], "computers", 5, "inventory",
              $_SESSION["glpiname"]." ".$LANG['log'][112]);
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["massinstall"]) && isset($_POST["computers_id"])) {
   checkRight("software","w");
   foreach ($_POST as $key => $val) {
      if (preg_match("/softversion_([0-9]+)/",$key,$ereg)) {
         if ($ereg[1] > 0) {
            $inst->add(array('computers_id'        => $_POST["computers_id"],
                             'softwareversions_id' => $ereg[1]));
         }
      }
   }
   Event::log($_POST["computers_id"], "computers", 5, "inventory",
              $_SESSION["glpiname"]." ".$LANG['log'][113]);
   glpi_header($_SERVER['HTTP_REFERER']);

// From installation list on Software form
} else if (isset($_POST["deleteinstalls"])) {
   checkRight("software","w");

   foreach ($_POST["item"] as $key => $val) {
      if ($val == 1) {
         $inst->delete(array('id' => $key));
         Event::log($_POST["softwares_id"], "software", 5, "inventory",
                    $_SESSION["glpiname"]." ".$LANG['log'][114]);
      }
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["moveinstalls"])) {
   checkRight("software","w");
   foreach ($_POST["item"] as $key => $val) {
      if ($val == 1 && $_POST['versionID'] > 0) {
         $inst->upgrade($key, $_POST['versionID']);
         Event::log($_POST["softwares_id"], "software", 5, "inventory",
                    $_SESSION["glpiname"]." ".$LANG['log'][115]);
      }
   }
   glpi_header($_SERVER['HTTP_REFERER']);

}
displayErrorAndDie('Lost');
?>
