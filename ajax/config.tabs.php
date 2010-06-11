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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
header_nocache();

if (!isset($_POST["id"])) {
   exit();
}

checkRight("config", "r");

$config = new Config();

switch($_REQUEST['glpi_tab']) {
   case -1 :
      $config->showFormDisplay();
      $config->showFormUserPrefs($CFG_GLPI);
      $config->showFormInventory();
      $config->showFormHelpdesk();
      $config->showSystemInformations();
      if (DBConnection::isDBSlaveActive()) {
         $config->showFormDBSlave();
      }
      Plugin::displayAction($config,$_REQUEST['glpi_tab']);
      break;

   case 1 :
      $config->showFormDisplay();
      break;

   case 2 :
      $config->showFormUserPrefs($CFG_GLPI);
      break;

   case 3 :
      $config->showFormInventory();
      break;

   case 4 :
      $config->showFormHelpdesk();
      break;

   case 5 :
      $config->showSystemInformations();
      break;

   case 6 :
      $config->showFormDBSlave();
      break;


   default :
      if (!Plugin::displayAction($config,$_REQUEST['glpi_tab'])) {
         $config->showFormMain();
      }
}

ajaxFooter();
?>
