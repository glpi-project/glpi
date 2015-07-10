<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

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

// Redirect management
if (isset($_GET["redirect"])) {
   Toolbox::manageRedirect($_GET["redirect"]);
}

//*******************
// Affichage Module FAQ
//******************

$name = "";
Session:: checkFaqAccess();

if (Session::getLoginUserID()) {
   Html::helpHeader(__('FAQ'), $_SERVER['PHP_SELF'], $_SESSION["glpiname"]);
} else {
   $_SESSION["glpilanguage"] = $CFG_GLPI['language'];
   // Anonymous FAQ
   Html::simpleHeader(__('FAQ'),
                      array(__('Authentication') => $CFG_GLPI['root_doc'].'/',
                            __('FAQ')            => $CFG_GLPI['root_doc'].'/front/helpdesk.faq.php'));
}

if (isset($_GET["id"])) {
   $kb = new KnowbaseItem();
   if ($kb->getFromDB($_GET["id"])) {
      $kb->showFull();
   }

} else {
   // Manage forcetab : non standard system (file name <> class name)
   if (isset($_GET['forcetab'])) {
      Session::setActiveTab('Knowbase', $_GET['forcetab']);
      unset($_GET['forcetab']);
   }

   $kb = new Knowbase();
   $kb->display($_GET);
}

Html::helpFooter();
?>