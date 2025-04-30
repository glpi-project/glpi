<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Glpi\Asset\CustomFieldDefinition;
use Glpi\Exception\Http\BadRequestHttpException;

Session::checkRight(CustomFieldDefinition::$rightname, READ);

if (isset($_POST['action'])) {
    $field = new CustomFieldDefinition();
    if ($_POST['action'] === 'get_default_custom_field') {
        $field->fields = $_POST;
        $field->fields['default_value'] = $field->getFieldType()->normalizeValue($_POST['default_value'] ?? null);
        echo $field->getFieldType()->getDefaultValueFormInput();
    } elseif ($_POST['action'] === 'get_field_type_options') {
        $field->getFromDB($_POST['customfielddefinitions_id']);
        $field->fields['type'] = $_POST['type'];
        $field_options = $field->getFieldType()->getOptions();
        foreach ($field_options as $option) {
            echo $option->getFormInput();
        }
    } elseif ($_POST['action'] === 'purge_field') {
        $field->check($_POST['customfielddefinitions_id'], PURGE);
        $field->delete(['id' => $_POST['customfielddefinitions_id']]);
    } else {
        throw new BadRequestHttpException();
    }
} else {
    throw new BadRequestHttpException();
}
