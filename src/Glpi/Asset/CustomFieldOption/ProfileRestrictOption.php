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
            'value' => $value,
            'inverted' => $this->getInverted(),
        ];
        // language=Twig
        return TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}
            {% set match_invert_field %}
                {{ fields.sliderField('field_options[' ~ key ~ '_invert]', inverted, __('Restrict to all but these profiles'), {
                    field_class: 'col-12',
                    label_class: 'col-xxl-10',
                    input_class: 'col-xxl-2'
                }) }}
            {% endset %}
            {{ fields.dropdownField('Profile', 'field_options[' ~ key ~ ']', value, label, {
                multiple: true,
                add_field_html: match_invert_field,
                to_add: {
                    '-1': __('All')
                },
                condition: {
                    'interface': 'central'
                }
            }) }}
        TWIG, $twig_params);
    }

    protected function getInverted(): bool
    {
        return (bool) ($this->custom_field->fields['field_options'][$this->getKey() . '_invert'] ?? false);
    }

    public function getValue(): bool
    {
        $inverted = $this->getInverted();
        $value = parent::getValue() ?? [];

        if (!is_array($value)) {
            $value = [$value];
        }

        // Handle special 'All' value
        if (in_array(-1, $value, true)) {
            return !$inverted;
        }

        $active_profile = $_SESSION['glpiactiveprofile']['id'] ?? null;
        if ($active_profile === null) {
            return false;
        }
        return $inverted ? !in_array($active_profile, $value, false) : in_array($active_profile, $value, false);
    }
}
