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

class NumberOption extends ConfigOption
{
    /**
     * @param array $scopes
     * @param ConfigSection $section
     * @param string $name
     * @param string $label
     * @param InputType $input_type
     * @param string $context
     * @param float $min
     * @param float $max
     * @param float $step
     * @param string $unit
     * @param array $toadd
     * @param array $used
     * @param string $on_change
     */
    public function __construct(
        array $scopes,
        ConfigSection $section,
        string $name,
        string $label,
        InputType $input_type = InputType::NUMBER,
        string $context = 'core',
        float $min = 0,
        float $max = 100,
        float $step = 1,
        string $unit = '',
        array $toadd = [],
        array $used = [],
        string $on_change = '',
    ) {
        $type_options = [
            'min' => $min,
            'max' => $max,
            'step' => $step,
            'unit' => $unit,
            'toadd' => $toadd,
            'used' => $used,
            'on_change' => $on_change,
        ];
        parent::__construct($scopes, $section, $name, $label, $input_type, $type_options, $context);
    }

    public function renderInput(ConfigScope $scope, array $scope_params = [], array $input_params = []): void
    {
        $template_content = <<<TWIG
        {% import 'components/form/fields_macros.html.twig' as fields %}
TWIG;
        $name = $this->getName();
        $value = $this->getValue($scope, null, $scope_params);
        $label = $this->getLabel();
        $field_options = array_merge($this->getTypeOptions(), $input_params);

        switch ($this->getType()) {
            case InputType::NUMBER:
                $template_content .= '{{ fields.numberField(name, value, label, field_options) }}';
                break;
            case InputType::DROPDOWN_NUMBER:
                $template_content .= '{{ fields.dropdownNumberField(name, value, label, field_options) }}';
                break;
        }

        if ($field_options['multiple'] ?? false) {
            $field_options['values'] = $value;
            $value = '';
        }

        TemplateRenderer::getInstance()->displayFromStringTemplate($template_content, [
            'name' => $name,
            'value' => $value,
            'label' => $label,
            'field_options' => $field_options
        ]);
    }
}
