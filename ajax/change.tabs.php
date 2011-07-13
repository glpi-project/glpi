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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
header_nocache();

if (!isset($_POST["id"])) {
   exit();
}
if (!isset($_REQUEST['glpi_tab'])) {
   exit();
}

$change = new Change();

if ($_POST["id"]>0 && $change->getFromDB($_POST["id"])) {

   switch($_REQUEST['glpi_tab']) {
      case -1 :
         Change_Problem::showForChange($change);
         Change_Ticket::showForChange($change);
         $change->showAnalysisForm();
         $task    = new ChangeTask();
         $task->showSummary($change);
         Change_Item::showForChange($change);
         Document::showAssociated($change);
         $change->showSolutionForm();
         Log::showForItem($change);
         Plugin::displayAction($change, $_REQUEST['glpi_tab']);
         break;

      case 3 :
         $change->showAnalysisForm();
         break;

      case 4 :
         if (!isset($_POST['load_kb_sol'])) {
            $_POST['load_kb_sol'] = 0;
         }
         $change->showSolutionForm($_POST['load_kb_sol']);
         break;

      case 5 :
         $change->showPlanForm();
         break;

      case 7 :
         Change_Item::showForChange($change);
         break;

      default :
         if (!CommonGLPI::displayStandardTab($change, $_REQUEST['glpi_tab'])) {
         }
   }
}

ajaxFooter();
?>
