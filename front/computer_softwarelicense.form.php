<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------


define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

Session::checkRight("software", "w");
$csl = new Computer_SoftwareLicense();

if (isset($_REQUEST["add"])) {
   if ($_REQUEST['softwarelicenses_id'] > 0 ) {
      $csl->add($_REQUEST);
      Event::log($_REQUEST['softwarelicenses_id'], "softwarelicense", 4, "inventory",
               $_SESSION["glpiname"]." ".$LANG['log'][116]);

   }
   Html::back();

// From association list
} else if (isset($_REQUEST["move"])) {
   if ($_REQUEST['softwarelicenses_id'] > 0 ) {
      foreach ($_REQUEST["item"] as $key => $val) {
         if ($val == 1) {
            $csl->upgrade($key, $_REQUEST['softwarelicenses_id']);
            Event::log($_REQUEST["softwarelicenses_id"], "softwarelicense", 5, "inventory",
                       $_SESSION["glpiname"]." ".$LANG['log'][117]);
         }
      }
   }
   Html::back();

// From association list
} else if (isset($_REQUEST["delete"])) {
   foreach ($_REQUEST["item"] as $key => $val) {
      if ($val == 1) {
         $csl->delete(array('id' => $key));
      }
   }
   Event::log($_REQUEST["softwarelicenses_id"], "softwarelicense", 5, "inventory",
              $_SESSION["glpiname"]." ".$LANG['log'][118]);

   Html::back();
}
Html::displayErrorAndDie('Lost');
?>