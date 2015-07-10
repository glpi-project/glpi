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

Session::checkCentralAccess();

$link          = new Link();
$link_itemtype = new Link_ItemType();

if (isset($_POST["add"])) {
   $link->check(-1, CREATE, $_POST);

   if ($link_itemtype->add($_POST)) {
    Event::log($_POST["links_id"], "links", 4, "setup",
               //TRANS: %s is the user login
               sprintf(__('%s adds a link with an item'), $_SESSION["glpiname"]));
   }
   Html::redirect($CFG_GLPI["root_doc"]."/front/link.form.php?id=".$_POST["links_id"]);
}
?>
