<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Glpi\Asset\CustomFieldDefinition;

$custom_field = new CustomFieldDefinition();

if (isset($_POST["add"])) {
    $custom_field->check(-1, CREATE, $_POST);
    $custom_field->add($_POST);
} else if (isset($_POST["update"])) {
    $custom_field->check($_POST['id'], UPDATE);
    $custom_field->update($_POST);
} else if (isset($_POST["purge"])) {
    $custom_field->check($_POST['id'], PURGE);
    $custom_field->delete(['id' => $_POST['id']]);
}
Html::back();