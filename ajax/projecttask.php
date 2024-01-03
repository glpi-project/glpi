<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

/**
 * @since 9.2
 */

use Glpi\Toolbox\Sanitizer;

$AJAX_INCLUDE = 1;

include('../inc/includes.php');
header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

if (isset($_POST['projecttasktemplates_id']) && ($_POST['projecttasktemplates_id'] > 0)) {
    $template = new ProjectTaskTemplate();
    $template->getFromDB($_POST['projecttasktemplates_id']);

    if (DropdownTranslation::isDropdownTranslationActive()) {
        $template->fields['description'] = DropdownTranslation::getTranslatedValue(
            $template->getID(),
            $template->getType(),
            'description',
            $_SESSION['glpilanguage'],
            $template->fields['description']
        );
    }

    $template->fields = Sanitizer::decodeHtmlSpecialCharsRecursive($template->fields);
    echo json_encode($template->fields);
}
