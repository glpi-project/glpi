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

namespace Glpi\Config\Option;

use Glpi\Application\View\TemplateRenderer;
use Glpi\Config\ConfigOption;
use Glpi\Config\ConfigScope;
use Glpi\Config\ConfigSection;
use Glpi\Config\InputType;

class ArrayOption extends ConfigOption
{
    /**
     * @param array $scopes
     * @param ConfigSection $section
     * @param string $name
     * @param string $label
     * @param string $context
     * @param bool $comments
     * @param array $toadd
     * @param array $toupdate
     * @phpstan-param array{value_fieldname: string, to_update: string, url: string} $toupdate
     * @param array $used
     * @param string $on_change
     * @param string|int|null $rand
     * @param array $condition
     * @param array $displaywith
     * @param string $emptylabel
     * @param bool $display_emptychoice
     * @param bool $permit_select_parent
     * @param array $specific_tags
     * @param string $class
     * @param string $url
     * @param bool $display_dc_position
     * @param string|null $parent_id_field
     * @param bool $multiple
     * @param string|false $other
     * @param string $tooltip
     * @param array $option_tooltips
     * @param string $templateResult
     * @param string $templateSelection
     * @param string $escapeMarkup
     */
    public function __construct(
        array $scopes,
        ConfigSection $section,
        string $name,
        string $label,
        array $elements = [],
        string $context = 'core',
        bool $comments = true,
        array $toadd = [],
        array $toupdate = [],
        array $used = [],
        string $on_change = '',
        string|int|null $rand = null,
        array $condition = [],
        array $displaywith = [],
        string $emptylabel = \Dropdown::EMPTY_VALUE,
        bool $display_emptychoice = true,
        bool $permit_select_parent = false,
        array $specific_tags = [],
        string $class = '',
        string $url = '',
        bool $display_dc_position = false,
        string|null $parent_id_field = null,
        bool $multiple = false,
        string|false $other = false,
        string $tooltip = '',
        array $option_tooltips = [],
        string $templateResult = '',
        string $templateSelection = '',
        string $escapeMarkup = 'escapeMarkup'
    ) {
        $type_options = [
            'elements' => $elements,
            'comments' => $comments,
            'to_add' => $toadd,
            'to_update' => $toupdate,
            'used' => $used,
            'on_change' => $on_change,
            'rand' => $rand,
            'condition' => $condition,
            'displaywith' => $displaywith,
            'emptylabel' => $emptylabel,
            'display_emptychoice' => $display_emptychoice,
            'permit_select_parent' => $permit_select_parent,
            'specific_tags' => $specific_tags,
            'class' => $class,
            'url' => $url,
            'display_dc_position' => $display_dc_position,
            'parent_id_field' => $parent_id_field,
            'multiple' => $multiple,
            'other' => $other,
            'tooltip' => $tooltip,
            'option_tooltips' => $option_tooltips,
            'templateResult' => $templateResult,
            'templateSelection' => $templateSelection,
            'escapeMarkup' => $escapeMarkup
        ];
        parent::__construct($scopes, $section, $name, $label, InputType::DROPDOWN_ARRAY, $type_options, $context);
    }

    public function renderInput(ConfigScope $scope, array $scope_params = [], array $input_params = []): void
    {
        $template_content = <<<TWIG
        {% import 'components/form/fields_macros.html.twig' as fields %}
        {{ fields.dropdownArrayField(name, value, elements, label, field_options) }}
TWIG;
        $name = $this->getName();
        $value = $this->getValue($scope, null, $scope_params);
        $label = $this->getLabel();
        $field_options = array_merge($this->getTypeOptions(), $input_params);
        $elements = $this->getTypeOptions()['elements'] ?? [];

        if ($field_options['multiple'] ?? false) {
            $field_options['values'] = $value;
            $value = '';
        }

        TemplateRenderer::getInstance()->displayFromStringTemplate($template_content, [
            'name' => $name,
            'value' => $value,
            'label' => $label,
            'field_options' => $field_options,
            'elements' => $elements
        ]);
    }

    public function getDisplayValue(mixed $raw_value): mixed
    {
        if ($raw_value === null) {
            return null;
        }
        if (!is_array($raw_value)) {
            return $this->getTypeOptions['elements'][$raw_value] ?? $raw_value;
        }
        return $raw_value;
    }
}
