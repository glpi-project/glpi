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

use CommonDBTM;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Asset\CustomFieldOption\BooleanOption;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;

class DropdownType extends AbstractType
{
    public static function getName(): string
    {
        return _n('Dropdown', 'Dropdowns', 1);
    }

    public function getOptions(): array
    {
        $options = parent::getOptions();
        $options[] = new BooleanOption($this->custom_field, 'multiple', __('Multiple values'));
        return $options;
    }

    public function normalizeValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }
        if ($this->getOptionValues()['multiple'] ?? false) {
            if (!is_array($value)) {
                $value = [$value];
            }
            $value = array_filter($value, static fn($val) => (int) $val > 0);
            $value = array_map(static fn($val) => (int) $val, $value);
            return $value;
        }

        if (is_array($value)) {
            $value = $value[0] ?? '';
        }
        return $value !== '' ? (int) $value : '';
    }

    public function formatValueForDB(mixed $value): mixed
    {
        return $this->normalizeValue($value);
    }

    public function formatValueFromDB(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }
        $is_multiple = $this->getOptionValues()['multiple'] ?? false;
        if ($is_multiple && !is_array($value)) {
            $value = [$value];
        } elseif (!$is_multiple && is_array($value)) {
            $value = $value[0] ?? '';
        }
        return $value;
    }

    public function getFormInput(string $name, mixed $value, ?string $label = null, bool $for_default = false): string
    {
        $twig_params = [
            'itemtype' => $this->custom_field->fields['itemtype'],
            'name' => $name,
            'value' => $value ?? $this->custom_field->fields['default_value'],
            'label' => $label ?? $this->custom_field->getFriendlyName(),
            'field_options' => $this->getOptionValues($for_default),
        ];
        if ($for_default) {
            $twig_params['field_options']['full_width'] = false;
            if ($twig_params['value'] === '') {
                $twig_params['value'] = null;
            }
        }
        // language=Twig
        return TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}
            {{ fields.dropdownField(itemtype, name, value, label, field_options|merge({
                values: value|default({}),
                entity: session('glpiactiveentities'),
            })) }}
TWIG, $twig_params);
    }

    public function getSearchOption(): ?array
    {
        global $DB;

        /** @var class-string<CommonDBTM> $itemtype */
        $itemtype = $this->custom_field->fields['itemtype'];
        $multiple = $this->custom_field->fields['field_options']['multiple'] ?? false;

        $opt = [
            'id' => $this->custom_field->getSearchOptionID(),
            'name' => $this->custom_field->fields['label'],
            'itemtype' => $itemtype,
            'table' => getTableForItemType($itemtype),
            'field' => $itemtype::getNameField(),
            'linkfield' => 'custom_fields',
            'datatype' => 'itemlink',
            'itemlink_type' => $itemtype,
            'joinparams' => [
                'jointype' => 'custom_condition_only',
            ],
        ];

        if (!$multiple) {
            $opt['joinparams']['condition'] = [
                new QueryExpression(
                    'NEWTABLE.' . $DB::quoteName('id') . ' = ' . QueryFunction::jsonUnquote(
                        expression: QueryFunction::jsonExtract([
                            'REFTABLE.custom_fields',
                            new QueryExpression($DB::quoteValue('$."' . $this->custom_field->fields['id'] . '"')),
                        ])
                    )
                ),
            ];
        } else {
            $opt['joinparams']['condition'] = [
                QueryFunction::jsonContains(
                    'REFTABLE.custom_fields',
                    'NEWTABLE.id',
                    '$."' . $this->custom_field->fields['id'] . '"'
                ),
            ];
            $opt['forcegroupby'] = true;
            $opt['usehaving'] = true;
        }

        return $opt;
    }
}
