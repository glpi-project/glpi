<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

/**
 * @since 9.1
 */

$AJAX_INCLUDE = 1;

include ('../inc/includes.php');
header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

if (isset($_POST['tasktemplates_id']) && ($_POST['tasktemplates_id'] > 0)) {
   $template = new TaskTemplate();
   $template->getFromDB($_POST['tasktemplates_id']);

   $fields = $template->fields;
   if ($fields['taskcategories_id']) {
      $cat = new TaskCategory();
      $cat->getFromDB($fields['taskcategories_id']);
      $fields['taskcategories_name'] = $cat->fields['name'];
   }

   if ($fields['users_id_tech']) {
      $user = new User();
      $user->getFromDB($fields['users_id_tech']);
      $fields['users_id_tech_name'] = $user->getRawName();
   }

   if ($fields['groups_id_tech']) {
      $group = new Group();
      $group->getFromDB($fields['groups_id_tech']);
      $fields['groups_id_tech_name'] = $group->fields['name'];
   }

   $fields = array_map('html_entity_decode', $fields);
   echo json_encode($fields);
}
