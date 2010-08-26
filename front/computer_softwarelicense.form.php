<?php
/*
 * @license $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either license 2 of the License, or
 (at your option) any later license.

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

$csl = new Computer_SoftwareLicense();

if (isset($_POST["move"])) {
   checkRight("software","w");
   if ($_POST['softwarelicenses_id'] > 0 ){
      foreach ($_POST["item"] as $key => $val) {
         if ($val == 1) {
            $csl->upgrade($key, $_POST['softwarelicenses_id']);
            Event::log($_POST["softwares_id"], "software", 5, "inventory",
                     $_SESSION["glpiname"]." move computers from a license to another.");
         }
      }
   }
   glpi_header($_SERVER['HTTP_REFERER']);

// From association list 
} else if (isset($_POST["delete"])) {
   checkRight("software","w");

   foreach ($_POST["item"] as $key => $val) {
      if ($val == 1) {
         $csl->delete(array('id' => $key));
      }
   }
   Event::log($_POST["softwares_id"], "software", 5, "inventory",
               $_SESSION["glpiname"]." delete association with a software license for several computers.");
   glpi_header($_SERVER['HTTP_REFERER']);
}

// From Computer - Software tab (add form or from not installed license)
/*if (isset($_REQUEST["install"])){
   checkRight("software","w");
   if (isset($_REQUEST["softwarelicenses_id"]) && isset($_REQUEST["computers_id"])) {
      $inst->add(array('computers_id'        => $_REQUEST["computers_id"],
                     'softwarelicenses_id' => $_REQUEST["softwarelicenses_id"]));

      Event::log($_REQUEST["computers_id"], "computers", 5, "inventory",
               $_SESSION["glpiname"]." installed software.");
   }
   glpi_header($_SERVER['HTTP_REFERER']);

// From Computer - Software tab (installed software)
} else if (isset($_GET["uninstall"])) {
   checkRight("software","w");
   $inst->delete(array('id'=>$_GET["id"]));

   Event::log($_GET["computers_id"], "computers", 5, "inventory",
              $_SESSION["glpiname"]." uninstalled software.");
   glpi_header($_SERVER['HTTP_REFERER']);

// From Computer - Software tab  (installed software)
} else if (isset($_POST["massuninstall"])) {
   checkRight("software","w");
   foreach ($_POST as $key => $val) {
      if (preg_match("/softlicense_([0-9]+)/",$key,$ereg)) {
         $inst->delete(array('id' => $ereg[1]));
      }
   }
   Event::log($_POST["computers_id"], "computers", 5, "inventory",
              $_SESSION["glpiname"]." uninstalled software.");
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["massinstall"]) && isset($_POST["computers_id"])) {
   checkRight("software","w");
   foreach ($_POST as $key => $val) {
      if (preg_match("/softlicense_([0-9]+)/",$key,$ereg)) {
         if ($ereg[1] > 0) {
            $inst->add(array('computers_id'        => $_POST["computers_id"],
                             'softwarelicenses_id' => $ereg[1]));
         }
      }
   }
   Event::log($_POST["computers_id"], "computers", 5, "inventory",
              $_SESSION["glpiname"]." installed software.");
   glpi_header($_SERVER['HTTP_REFERER']);

// From installation list on Software form
} else if (isset($_POST["deleteinstalls"])) {
   checkRight("software","w");

   foreach ($_POST["item"] as $key => $val) {
      if ($val == 1) {
         $inst->delete(array('id' => $key));
         Event::log($_POST["softwares_id"], "software", 5, "inventory",
                    $_SESSION["glpiname"]." uninstalled software for several computers.");
      }
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["moveinstalls"])) {
   checkRight("software","w");
   foreach ($_POST["item"] as $key => $val) {
      if ($val == 1 && $_POST['licenseID'] > 0) {
         $inst->upgrade($key, $_POST['licenseID']);
         Event::log($_POST["softwares_id"], "software", 5, "inventory",
                    $_SESSION["glpiname"]." change license of licenses installed on computers.");
      }
   }
   glpi_header($_SERVER['HTTP_REFERER']);

}*/
displayErrorAndDie('Lost');
?>
