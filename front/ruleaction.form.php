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
* @since version 0.85
*/
include ('../inc/includes.php');

$rule = new Rule;
$rule->getFromDB(intval($_POST['rules_id']));

$action = new RuleAction($rule->fields['sub_type']);

if (isset($_POST["add"])) {
   $action->check(-1, CREATE, $_POST);
   $action->add($_POST);

   Html::back();
} else if (isset($_POST["update"])) {
   $action->check($_POST['id'], UPDATE);
   $action->update($_POST);

   Html::back();
} else if (isset($_POST["purge"])) {
   $action->check($_POST['id'], PURGE);
   $action->delete($_POST, 1);

   Html::back();
}

?>