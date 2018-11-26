<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

/** @file
 * @brief
 */

include ('../inc/includes.php');
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

if (isset($_POST["projects_id"])) {
   $condition = ['glpi_projecttasks.projectstates_id' => ['<>', 3]];

   if ($_POST["projects_id"] > 0) {
      $condition['glpi_projecttasks.projects_id'] = $_POST['projects_id'];
   }

   $p = ['itemtype'     => ProjectTask::getType(),
         'entity_restrict' => $_POST['entity_restrict'],
         'myname'          => $_POST["myname"],
         'condition'       => $condition,
         'rand'            => $_POST["rand"]];

   if (isset($_POST["used"]) && !empty($_POST["used"])) {
      if (isset($_POST["used"])) {
         $p["used"] = $_POST["used"];
      }
   }

   ProjectTask::dropdown($p);

}
