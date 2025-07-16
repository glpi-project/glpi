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

namespace Glpi\Asset\CustomFieldType;

use Glpi\Application\View\TemplateRenderer;
use Glpi\Asset\CustomFieldOption\BooleanOption;
use InvalidArgumentException;

class BooleanType extends AbstractType
{
    public static function getName(): string
    {
        return __('Yes/No');
    }

    public function getFormInput(string $name, mixed $value, ?string $label = null, bool $for_default = false): string
    {
        $twig_params = [
            'name' => $name,
            'value' => $value ?? $this->custom_field->fields['default_value'],
            'label' => $label ?? $this->custom_field->getFriendlyName(),
            'field_options' => $this->getOptionValues($for_default),
        ];
        // language=Twig
        return TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}
            {% if field_options.display_as_slider %}
                {{ fields.sliderField(name, value, label, field_options) }}
            {% else %}
                {{ fields.dropdownYesNo(name, value, label, field_options) }}
            {% endif %}
TWIG, $twig_params);
    }

    public function normalizeValue(mixed $value): ?bool
    {
        if ($value === null) {
            return null;
        }
        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($value === null) {
            throw new InvalidArgumentException('The value must be a boolean');
        }
        return $value;
    }

    public function getSearchOption(): ?array
    {
        $opt = $this->getCommonSearchOptionData();
        $opt['datatype'] = 'bool';
        return $opt;
    }

    public function getOptions(): array
    {
        $opts = parent::getOptions();
        $opts[] = new BooleanOption($this->custom_field, 'display_as_slider', __('Display as slider'));
        return $opts;
    }
}
