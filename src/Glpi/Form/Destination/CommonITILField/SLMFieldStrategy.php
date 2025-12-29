<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
use Glpi\Form\AnswersSet;
use Glpi\Form\Form;
use Override;

enum SLMFieldStrategy: string implements SLMFieldStrategyInterface
{
    case FROM_TEMPLATE = 'from_template';
    case SPECIFIC_VALUE = 'specific_value';

    #[Override]
    public function getKey(): string
    {
        return $this->value;
    }

    #[Override]
    public function getLabel(SLMField $field): string
    {
        return match ($this) {
            self::FROM_TEMPLATE  => __("From template"),
            self::SPECIFIC_VALUE => sprintf(__("Specific %s"), $field->getSLM()->getTypeName(1)),
        };
    }

    #[Override]
    public function applyStrategyToInput(
        SLMField $field,
        SLMFieldConfig $config,
        array $input,
        AnswersSet $answers_set
    ): array {
        $slm_id = match ($this) {
            self::FROM_TEMPLATE  => null,
            self::SPECIFIC_VALUE => $config->getSpecificSLMID(),
        };

        return $field->applySlmIdToInput($slm_id, $input);
    }

    /**
     * @param array<string, mixed> $display_options
     */
    #[Override]
    public function renderExtraConfigFields(
        Form $form,
        SLMField $field,
        SLMFieldConfig $config,
        string $input_name,
        array $display_options
    ): string {
        return match ($this) {
            self::FROM_TEMPLATE  => '',
            self::SPECIFIC_VALUE => $this->renderSpecificValueExtraFields(
                $field,
                $config,
                $input_name,
                $display_options
            ),
        };
    }

    #[Override]
    public function getExtraConfigKeys(): array
    {
        return match ($this) {
            self::FROM_TEMPLATE  => [],
            self::SPECIFIC_VALUE => [SLMFieldConfig::SLM_ID],
        };
    }

    #[Override]
    public function getWeight(): int
    {
        return match ($this) {
            self::FROM_TEMPLATE  => 10,
            self::SPECIFIC_VALUE => 20,
        };
    }

    /**
     * @param array<string, mixed> $display_options
     */
    private function renderSpecificValueExtraFields(
        SLMField $field,
        SLMFieldConfig $config,
        string $input_name,
        array $display_options
    ): string {
        $slm = $field->getSLM();
        $twig = TemplateRenderer::getInstance();

        return $twig->render('pages/admin/form/itil_config_fields/slm.html.twig', [
            'CONFIG_SPECIFIC_VALUE' => self::SPECIFIC_VALUE->value,
            'options' => $display_options,
            'specific_value_extra_field' => [
                'slm_class'   => $slm::class,
                'empty_label' => sprintf(__("Select a %s..."), $slm->getTypeName()),
                'value'       => $config->getSpecificSLMID() ?? 0,
                'input_name'  => $input_name . "[" . SLMFieldConfig::SLM_ID . "]",
                'type'        => $field->getType(),
            ],
        ]);
    }
}
