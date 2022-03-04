<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
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
 * @since 9.2
 */

$AJAX_INCLUDE = 1;

include ('../inc/includes.php');
header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

if (isset($_POST['projecttasktemplates_id']) && ($_POST['projecttasktemplates_id'] > 0)) {
   $template = new ProjectTaskTemplate();
   $template->getFromDB($_POST['projecttasktemplates_id']);

   if (DropdownTranslation::isDropdownTranslationActive()) {
      $template->fields['content'] = DropdownTranslation::getTranslatedValue(
         $template->getID(),
         $template->getType(),
         'content',
         $_SESSION['glpilanguage'],
         $template->fields['content']
      );
   }

   $template->fields = array_map('html_entity_decode', $template->fields);
   echo json_encode($template->fields);
}
