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

use Glpi\Asset\CustomFieldDefinition;
use Glpi\Asset\CustomFieldOption\BooleanOption;
use Glpi\Asset\CustomFieldOption\ProfileRestrictOption;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;

abstract class AbstractType implements TypeInterface
{
    public function __construct(
        protected CustomFieldDefinition $custom_field
    ) {}

    public function getLabel(): string
    {
        return $this->custom_field->getFriendlyName();
    }

    public function normalizeValue(mixed $value): mixed
    {
        return $value;
    }

    public function formatValueForDB(mixed $value): mixed
    {
        return $this->normalizeValue($value);
    }

    public function formatValueFromDB(mixed $value): mixed
    {
        return $this->normalizeValue($value);
    }

    public function getOptions(): array
    {
        return [
            new BooleanOption($this->custom_field, 'full_width', __('Full width'), false),
            new BooleanOption($this->custom_field, 'required', __('Mandatory'), false),
            new ProfileRestrictOption($this->custom_field, 'readonly', __('Readonly for these profiles'), false),
            new ProfileRestrictOption($this->custom_field, 'hidden', __('Hidden for these profiles'), false),
        ];
    }

    public function getOptionValues(bool $default_field = false): array
    {
        $values = [];
        foreach ($this->getOptions() as $option) {
            if ($default_field && !$option->getApplyToDefault()) {
                continue;
            }
            $values[$option->getKey()] = $option->getValue();
        }
        return $values;
    }

    public function setDefaultValue(mixed $value): void
    {
        $this->custom_field->fields['default_value'] = $this->normalizeValue($value);
    }

    public function getDefaultValue(): mixed
    {
        return $this->custom_field->fields['default_value'];
    }

    public function getDefaultValueFormInput(): string
    {
        return $this->getFormInput(
            name: 'default_value',
            value: $this->custom_field->fields['default_value'],
            label: __('Default value'),
            for_default: true
        );
    }

    protected function getCommonSearchOptionData(): array
    {
        global $DB;

        return [
            'id' => $this->custom_field->getSearchOptionID(),
            'name' => $this->custom_field->fields['label'],
            'table' => 'glpi_assets_assets',
            'field' => 'value',
            'computation' => QueryFunction::coalesce([
                QueryFunction::jsonUnquote(
                    expression: QueryFunction::jsonExtract([
                        'glpi_assets_assets.custom_fields',
                        new QueryExpression($DB::quoteValue('$."' . $this->custom_field->fields['id'] . '"')),
                    ])
                ),
                new QueryExpression($DB::quoteValue($this->custom_field->fields['default_value'])),
            ]),
            'nometa' => true,
            'field_definition' => $this->custom_field,
        ];
    }

    public function getSearchOption(): ?array
    {
        return null;
    }
}
