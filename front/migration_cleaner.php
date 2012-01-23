<?php

/*
 * @version $Id: migration_cleaner.php -1   $
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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Damien Touraine
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if (!TableExists('glpi_networkportmigrations')) {
   Session::addMessageAfterRedirect(__('You don\'t need the "migration cleaner" tool anymore ...'));
   Html::redirect($CFG_GLPI["root_doc"]."/front/central.php");
}

if (isset($_GET['action'])) {
   switch ($_GET['action']) {

   case 'reinit_network':
      IPNetwork::recreateTree();
      Session::addMessageAfterRedirect(__('Successfully recreated network tree !'));
      break;
   case 'recreate_network_connexities':
      NetworkName_IPNetwork::recreateAllConnexities();
      Session::addMessageAfterRedirect(__('Successfully recreated connexities between ' .
                                          'IPNetwork and NetworkName !'));
      break;
  }


   Html::back();
}

Html::header(__('migration cleaner'), $_SERVER['PHP_SELF'], "utils","migration");

echo "<div class='spaced' id='tabsbody'>";
echo "<table class='tab_cadre_fixe'>";

echo "<tr><th>" . __('"Migration cleaner" tool') . "</td></tr>";

echo "<tr><td class='center'><a href='".$_SERVER['PHP_SELF']."?action=reinit_network'>".
     __('Reinit the network topology') . "</a></td></tr>";

/*
echo "<tr><td class='center'><a href='".$_SERVER['PHP_SELF'] .
     "?action=recreate_network_connexities'>".
     __('Re-create all connexities between IPNetwork and NetworkName') . "</a></td></tr>";
*/

echo "<tr><td class='center'><a href='".$CFG_GLPI['root_doc']."/front/networkportmigration.php'>".
     __('Clean the networkport migration errors') . "</a></td></tr>";

echo "</table>";
echo "</div>";


Html::footer();
?>