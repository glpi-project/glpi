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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   define('GLPI_ROOT', '..');
   include (GLPI_ROOT . "/inc/includes.php");
}

if (!strpos($_SERVER['PHP_SELF'],"popup")) {
   Html::header($LANG['common'][12],$_SERVER['PHP_SELF'],"config","display");
}

Session::checkSeveralRightsOr(array("search_config_global" => "w",
                                    "search_config"        => "w"));

$setupdisplay = new DisplayPreference();

if (isset($_POST["activate"])) {
   $setupdisplay->activatePerso($_POST);

} else if (isset($_POST["add"])) {
   $setupdisplay->add($_POST);

} else if (isset($_POST["delete"]) || isset($_POST["delete_x"])) {
   $setupdisplay->delete($_POST);

} else if (isset($_POST["up"]) || isset($_POST["up_x"])) {
   $setupdisplay->orderItem($_POST,'up');

} else if (isset($_POST["down"]) || isset($_POST["down_x"])) {
   $setupdisplay->orderItem($_POST,'down');

} else if (isset($_POST['delete_for_user'])) {
   foreach ($_POST['itemtype'] as $itemtype => $val) {
      $crit = array('users_id' => $_POST['users_id'],
                    'itemtype' => $itemtype);
      $setupdisplay->deleteByCriteria($crit);
   }
   Html::back();
}

if ((strpos($_SERVER['PHP_SELF'],"popup") && $_REQUEST["itemtype"])) {
   $setupdisplay->showTabs(array('displaytype' => $_REQUEST['itemtype']));
   echo "<div id='tabcontent'>&nbsp;</div>";
   echo "<script type='text/javascript'>loadDefaultTab();</script>";
}

if (!strpos($_SERVER['PHP_SELF'],"popup")) {
   Html::footer();
}
?>