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
use Session;

class ITILFollowupField extends AbstractConfigField
{
    #[Override]
    public function getLabel(): string
    {
        return _n('Followup', 'Followups', Session::getPluralNumber());
    }

    #[Override]
    public function getConfigClass(): string
    {
        return ITILFollowupFieldConfig::class;
    }

    #[Override]
    public function renderConfigForm(
        Form $form,
        JsonFieldInterface $config,
        string $input_name,
        array $display_options
    ): string {
        if (!$config instanceof ITILFollowupFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        $twig = TemplateRenderer::getInstance();
        return $twig->render('pages/admin/form/itil_config_fields/itilfollowuptemplate.html.twig', [
            // Possible configuration constant that will be used to to hide/show additional fields
            'CONFIG_SPECIFIC_VALUES'  => ITILFollowupFieldStrategy::SPECIFIC_VALUES->value,

            // General display options
            'options' => $display_options,

            // Main config field
            'main_config_field' => [
                'label'           => $this->getLabel(),
                'value'           => $config->getStrategy()->value,
                'input_name'      => $input_name . "[" . ITILFollowupFieldConfig::STRATEGY . "]",
                'possible_values' => $this->getMainConfigurationValuesforDropdown(),
            ],

            // Specific additional config for SPECIFIC_VALUES strategy
            'specific_value_extra_field' => [
                'aria_label'     => __("Select followup templates..."),
                'value'           => $config->getSpecificITILFollowupTemplatesIds() ?? [],
                'input_name'      => $input_name . "[" . ITILFollowupFieldConfig::ITILFOLLOWUPTEMPLATE_IDS . "]",
            ],
        ]);
    }

    #[Override]
    public function applyConfiguratedValueToInputUsingAnswers(
        JsonFieldInterface $config,
        array $input,
        AnswersSet $answers_set
    ): array {
        if (!$config instanceof ITILFollowupFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        // Compute value according to strategy
        $itilfollowuptemplates_ids = $config->getStrategy()->getITILFollowupTemplatesIDs($config);

        if (!empty($itilfollowuptemplates_ids)) {
            $input['_itilfollowuptemplates_id'] = $itilfollowuptemplates_ids;
        }

        return $input;
    }

    #[Override]
    public function getDefaultConfig(Form $form): ITILFollowupFieldConfig
    {
        return new ITILFollowupFieldConfig(
            ITILFollowupFieldStrategy::NO_FOLLOWUP,
        );
    }

    private function getMainConfigurationValuesforDropdown(): array
    {
        $values = [];
        foreach (ITILFollowupFieldStrategy::cases() as $strategies) {
            $values[$strategies->value] = $strategies->getLabel();
        }
        return $values;
    }

    #[Override]
    public function getWeight(): int
    {
        return 30;
    }

    #[Override]
    public function prepareInput(array $input): array
    {
        $input = parent::prepareInput($input);

        // Ensure that itilfollowuptemplate_ids is an array
        if (!is_array($input[$this->getKey()][ITILFollowupFieldConfig::ITILFOLLOWUPTEMPLATE_IDS] ?? null)) {
            unset($input[$this->getKey()][ITILFollowupFieldConfig::ITILFOLLOWUPTEMPLATE_IDS]);
        }

        return $input;
    }
}
