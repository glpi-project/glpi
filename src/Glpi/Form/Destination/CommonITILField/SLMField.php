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
use Override;

abstract class SLMField extends AbstractConfigField
{
    abstract public function getSLMClass(): string;
    abstract public function getType(): int;

    #[Override]
    public function getConfigClass(): string
    {
        return SLMFieldConfig::class;
    }

    #[Override]
    public function renderConfigForm(
        Form $form,
        JsonFieldInterface $config,
        string $input_name,
        array $display_options
    ): string {
        if (!$config instanceof SLMFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        $slm = new ($this->getSLMClass())();
        $twig = TemplateRenderer::getInstance();
        return $twig->render('pages/admin/form/itil_config_fields/slm.html.twig', [
            // Possible configuration constant that will be used to to hide/show additional fields
            'CONFIG_SPECIFIC_VALUE'  => SLMFieldStrategy::SPECIFIC_VALUE->value,

            // General display options
            'options' => $display_options,

            // Main config field
            'main_config_field' => [
                'label'           => $this->getLabel(),
                'value'           => $config->getStrategy()->value,
                'input_name'      => $input_name . "[" . SLMFieldConfig::STRATEGY . "]",
                'possible_values' => $this->getMainConfigurationValuesforDropdown(),
            ],

            // Specific additional config for SPECIFIC_ANSWER strategy
            'specific_value_extra_field' => [
                'slm_class' => $this->getSLMClass(),
                'empty_label'     => sprintf(__("Select a %s..."), $slm->getTypeName()),
                'value'           => $config->getSpecificSLMID() ?? 0,
                'input_name'      => $input_name . "[" . SLMFieldConfig::SLM_ID . "]",
                'type'            => $this->getType(),
            ],
        ]);
    }

    #[Override]
    public function applyConfiguratedValueToInputUsingAnswers(
        JsonFieldInterface $config,
        array $input,
        AnswersSet $answers_set
    ): array {
        if (!$config instanceof SLMFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        // Compute value according to strategy
        $slm_id = $config->getStrategy()->getSLMID($config);

        // Do not edit input if invalid value was found
        $slm_class = $this->getSLMClass();
        if (!$slm_class::getById($slm_id)) {
            return $input;
        }

        $input[$slm_class::getFieldNames($this->getType())[1]] = $slm_id;

        return $input;
    }

    #[Override]
    public function getDefaultConfig(Form $form): SLMFieldConfig
    {
        return new SLMFieldConfig(
            SLMFieldStrategy::FROM_TEMPLATE
        );
    }

    private function getMainConfigurationValuesforDropdown(): array
    {
        $values = [];
        foreach (SLMFieldStrategy::cases() as $strategies) {
            $values[$strategies->value] = $strategies->getLabel($this);
        }
        return $values;
    }
}
