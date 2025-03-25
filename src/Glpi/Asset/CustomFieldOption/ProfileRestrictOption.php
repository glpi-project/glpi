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

namespace Glpi\Asset\CustomFieldOption;

use Glpi\Application\View\TemplateRenderer;

class ProfileRestrictOption extends AbstractOption
{
    public function getFormInput(): string
    {
        $value = parent::getValue();
        if (!is_array($value)) {
            $value = [$value];
        }
        $twig_params = [
            'item' => $this->custom_field,
            'key' => $this->getKey(),
            'label' => $this->getName(),
            'value' => array_filter($value),
            'all_label' => __('All'),
        ];
        // language=Twig
        return TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}
            {{ fields.dropdownField('Profile', 'field_options[' ~ key ~ ']', value, label, {
                multiple: true,
                to_add: {
                    '-1': all_label
                },
                condition: {
                    'interface': 'central'
                }
            }) }}
        TWIG, $twig_params);
    }

    public function getValue(): bool
    {
        $value = parent::getValue() ?? [];

        if (!is_array($value)) {
            $value = [$value];
        }

        // Handle special 'All' value
        if (in_array(-1, $value, true)) {
            return true;
        }

        $active_profile = $_SESSION['glpiactiveprofile']['id'] ?? null;
        if ($active_profile === null) {
            return false;
        }
        return in_array($active_profile, $value, false);
    }
}
