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


include ('../inc/includes.php');

Session::checkRight("software", UPDATE);
$inst = new Computer_SoftwareVersion();

// From Computer - Software tab (add form)
if (isset($_POST["add"])) {
   if (isset($_POST["computers_id"]) && $_POST["computers_id"]
       && isset($_POST["softwareversions_id"]) && $_POST["softwareversions_id"]) {

      if ($newID = $inst->add(array('computers_id'        => $_POST["computers_id"],
                                    'softwareversions_id' => $_POST["softwareversions_id"]))) {

         Event::log($_POST["computers_id"], "computers", 5, "inventory",
                    //TRANS: %s is the user login
                    sprintf(__('%s installs software'), $_SESSION["glpiname"]));
      }
   }
   Html::back();

}
Html::displayErrorAndDie('Lost');
?>