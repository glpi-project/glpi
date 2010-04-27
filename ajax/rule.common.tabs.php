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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!isset($_POST['id'])) {
   exit();
}
if (!isset($_REQUEST['glpi_tab'])) {
   exit();
}

if ($_POST['id'] >0 && $rule->can($_POST['id'],'r')) {

   switch($_REQUEST['glpi_tab']) {
      case -1 :
         $rule->getRuleWithCriteriasAndActions($_POST['id'],1,1);
         $rule->showCriteriasList($_POST["id"]);
         $rule->showActionsList($_POST["id"]);
         break;
      case 1 :
         $rule->getRuleWithCriteriasAndActions($_POST['id'],1,1);
         $rule->showCriteriasList($_POST["id"]);
         $rule->showActionsList($_POST["id"]);
         break;
      case 12 :
            Log::showForItem($rule);
         break;

      default :
         if (!Plugin::displayAction($rule, $_REQUEST['glpi_tab'])) {
         }
   }
}

ajaxFooter();

?>
