<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------


define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

$compdev = new Computer_Device();

if (isset($_POST["add"])) {
   $compdev->check(-1, 'w', $_POST);
   $compdev->add($_POST);
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["updateall"])) {
   $compdev->check(-1, 'w', $_POST);
   $compdev->updateAll($_POST);
   Event::log($_POST["computers_id"], "computers", 4, "inventory",
              $_SESSION["glpiname"] ." ".$LANG['log'][28]);
   glpi_header($_SERVER['HTTP_REFERER']);

}
displayErrorAndDie('Lost');
?>
