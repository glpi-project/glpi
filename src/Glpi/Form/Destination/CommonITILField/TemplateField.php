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

namespace Glpi\Form\Destination\CommonITILField;

use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\AnswersSet;
use Glpi\Form\Destination\AbstractConfigField;
use Glpi\Form\Form;
use InvalidArgumentException;
use ITILTemplate;
use Override;

class TemplateField extends AbstractConfigField
{
    private string $itil_template_class;

    public function __construct(string $itil_template_class)
    {
        if (!is_subclass_of($itil_template_class, ITILTemplate::class)) {
            throw new InvalidArgumentException("Invalid ITIL template class");
        }

        $this->itil_template_class = $itil_template_class;
    }

    #[Override]
    public function getKey(): string
    {
        return 'template';
    }

    #[Override]
    public function getLabel(): string
    {
        return _n('Template', 'Templates', 1);
    }

    #[Override]
    public function getConfigClass(): string
    {
        return TemplateFieldConfig::class;
    }

    #[Override]
    public function renderConfigForm(
        Form $form,
        JsonFieldInterface $config,
        string $input_name,
        array $display_options
    ): string {
        if (!$config instanceof TemplateFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        $parameters = [
            // Possible configuration constant that will be used to to hide/show additional fields
            'CONFIG_SPECIFIC_TEMPLATE'  => TemplateFieldStrategy::SPECIFIC_TEMPLATE->value,

            // General display options
            'options' => $display_options,

            // Main config field
            'main_config_field' => [
                'label'           => $this->getLabel(),
                'value'           => $config->getStrategy()->value,
                'input_name'      => $input_name . "[" . TemplateFieldConfig::STRATEGY . "]",
                'possible_values' => $this->getMainConfigurationValuesforDropdown(),
            ],

            // Specific additional config for CONFIG_SPECIFIC_TEMPLATE
            'specific_template_extra_field' => [
                'empty_label'     => __("Select a template..."),
                'value'           => $config->getSpecificTemplateID(),
                'input_name'      => $input_name . "[" . TemplateFieldConfig::TEMPLATE_ID . "]",
                'possible_values' => $this->getTemplateValuesForDropdown($form),
            ],
        ];

        $template = <<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}

            {{ fields.dropdownArrayField(
                main_config_field.input_name,
                main_config_field.value,
                main_config_field.possible_values,
                main_config_field.label,
                options
            ) }}

            <div
                {% if main_config_field.value != CONFIG_SPECIFIC_TEMPLATE %}
                    class="d-none"
                {% endif %}
                data-glpi-parent-dropdown="{{ main_config_field.input_name }}"
                data-glpi-parent-dropdown-condition="{{ CONFIG_SPECIFIC_TEMPLATE }}"
            >
                {{ fields.dropdownArrayField(
                    specific_template_extra_field.input_name,
                    specific_template_extra_field.value,
                    specific_template_extra_field.possible_values,
                    "",
                    options|merge({
                        no_label: true,
                        display_emptychoice: true,
                        emptylabel: specific_template_extra_field.empty_label,
                        aria_label: specific_template_extra_field.empty_label,
                    })
                ) }}
            </div>
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, $parameters);
    }

    #[Override]
    public function applyConfiguratedValueToInputUsingAnswers(
        JsonFieldInterface $config,
        array $input,
        AnswersSet $answers_set
    ): array {
        if (!$config instanceof TemplateFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        // Compute value according to strategy
        $template_id = $config->getStrategy()->getTemplateID($config, $answers_set);

        // Do not edit the input if invalid value was found
        if (!$this->itil_template_class::getById($template_id)) {
            return $input;
        }

        // Apply value
        $input[$this->itil_template_class::getForeignKeyField()] = $template_id;
        return $input;
    }

    #[Override]
    public function getDefaultConfig(Form $form): TemplateFieldConfig
    {
        return new TemplateFieldConfig(
            TemplateFieldStrategy::DEFAULT_TEMPLATE
        );
    }

    private function getMainConfigurationValuesforDropdown(): array
    {
        $values = [];
        foreach (TemplateFieldStrategy::cases() as $strategies) {
            $values[$strategies->value] = $strategies->getLabel();
        }
        return $values;
    }

    private function getTemplateValuesForDropdown(Form $form): array
    {
        $values = [];
        $templates = (new $this->itil_template_class())->find();

        foreach ($templates as $template) {
            $values[$template['id']] = $template['name'];
        }

        return $values;
    }

    #[Override]
    public function getWeight(): int
    {
        return 30;
    }
}
