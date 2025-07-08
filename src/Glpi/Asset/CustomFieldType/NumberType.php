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
use Glpi\Asset\CustomFieldOption\NumberOption;

class NumberType extends AbstractType
{
    public static function getName(): string
    {
        return __('Number');
    }

    public function getOptions(): array
    {
        $options = parent::getOptions();
        $options[] = new NumberOption(custom_field: $this->custom_field, key: 'min', name: __('Minimum'), default_value: 0);
        $options[] = new NumberOption(custom_field: $this->custom_field, key: 'max', name: __('Maximum'), default_value: PHP_INT_MAX);
        $options[] = new NumberOption(
            custom_field: $this->custom_field,
            key: 'step',
            name: _n('Step', 'Steps', 1),
            step: 0.01,
            default_value: 1
        );
        return $options;
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
            {{ fields.numberField(name, value, label, field_options) }}
TWIG, $twig_params);
    }

    public function normalizeValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_numeric($value)) {
            throw new \InvalidArgumentException('The value must be a number');
        }
        $opts = $this->custom_field->fields['field_options'] ?? [];
        $min = $opts['min'] ?? 0;
        $max = $opts['max'] ?? PHP_INT_MAX;
        $step = $opts['step'] ?? 1;
        $is_int = is_int($min + $step);
        return max($min, min($max, $is_int ? (int) $value : (float) $value));
    }

    public function getSearchOption(): ?array
    {
        $opt = $this->getCommonSearchOptionData();
        $opt['datatype'] = 'number';
        $field_opts = $this->custom_field->fields['field_options'] ?? [];
        $opt['min'] = $field_opts['min'] ?? 0;
        $opt['max'] = $field_opts['max'] ?? PHP_INT_MAX;
        $opt['step'] = $field_opts['step'] ?? 1;
        return $opt;
    }
}
