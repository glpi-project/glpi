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
use Glpi\Asset\CustomFieldDefinition;

class NumberOption extends AbstractOption
{
    protected float $step;

    public function __construct(CustomFieldDefinition $custom_field, string $key, string $name, float $step = 1, bool $apply_to_default = true, mixed $default_value = null)
    {
        parent::__construct($custom_field, $key, $name, $apply_to_default, $default_value);
        $this->step = $step;
    }

    public function getFormInput(): string
    {
        $twig_params = [
            'item' => $this->custom_field,
            'key' => $this->getKey(),
            'label' => $this->getName(),
            'step' => $this->step,
            'value' => $this->getValue(),
        ];
        // language=Twig
        return TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}
            {{ fields.numberField('field_options[' ~ key ~ ']', value, label, {
                step: step
            }) }}
        TWIG, $twig_params);
    }
}
