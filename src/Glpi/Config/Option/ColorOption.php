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

class ColorOption extends ConfigOption
{
    public function __construct(
        array $scopes,
        ConfigSection $section,
        string $name,
        string $label,
        string $context = 'core'
    ) {
        parent::__construct($scopes, $section, $name, $label, InputType::COLOR, [], $context);
    }

    public function renderInput(ConfigScope $scope, array $scope_params = [], array $input_params = []): void
    {
        $template_content = <<<TWIG
        {% import 'components/form/fields_macros.html.twig' as fields %}
        {{ fields.colorField(name, value, label, field_options) }}
TWIG;
        $name = $this->getName();
        $value = $this->getValue($scope, null, $scope_params);
        $label = $this->getLabel();
        $field_options = array_merge($this->getTypeOptions(), $input_params);

        TemplateRenderer::getInstance()->displayFromStringTemplate($template_content, [
            'name' => $name,
            'value' => $value,
            'label' => $label,
            'field_options' => $field_options
        ]);
    }
}
