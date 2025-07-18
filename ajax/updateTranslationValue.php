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

use Glpi\Application\View\TemplateRenderer;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkRight("dropdown", UPDATE);

$matching_field = null;

if (isset($_POST['itemtype'], $_POST['field']) && is_a($_POST['itemtype'], CommonDropdown::class, true)) {
    $itemtype = getItemForItemtype($_POST['itemtype']);
    $matching_field = $itemtype->getAdditionalField($_POST['field']);
}

$field_type = $matching_field['type'] ?? null;

// language=twig
echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
    {% import 'components/form/basic_inputs_macros.html.twig' as inputs %}
    {% if field_type == 'tinymce' %}
        {{ inputs.textarea('value', '', {
            enable_richtext: true,
            enable_images: false,
            enable_fileupload: false,
        }) }}
    {% else %}
        {{ inputs.text('value', '') }}
    {% endif %}
TWIG, ['field_type' => $field_type]);
