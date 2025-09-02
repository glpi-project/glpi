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

namespace Glpi\Form\Destination\CommonITILField;

use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\AnswersSet;
use Glpi\Form\Destination\AbstractCommonITILFormDestination;
use Glpi\Form\Destination\AbstractConfigField;
use Glpi\Form\Destination\FormDestination;
use Glpi\Form\Export\Context\DatabaseMapper;
use Glpi\Form\Export\Serializer\DynamicExportDataField;
use Glpi\Form\Export\Specification\DataRequirementSpecification;
use Glpi\Form\Form;
use Glpi\Form\Migration\DestinationFieldConverterInterface;
use Glpi\Form\Migration\FormMigration;
use InvalidArgumentException;
use LevelAgreement;
use Override;

abstract class SLMField extends AbstractConfigField implements DestinationFieldConverterInterface
{
    abstract public function getSLM(): LevelAgreement;
    abstract public function getType(): int;
    /** @return class-string<SLMFieldConfig> */
    abstract public function getConfigClass(): string;
    abstract protected function getFieldNameToConvertSpecificSLMID(): string;

    final public function __construct() {}

    #[Override]
    public function renderConfigForm(
        Form $form,
        FormDestination $destination,
        JsonFieldInterface $config,
        string $input_name,
        array $display_options
    ): string {
        if (!$config instanceof SLMFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        $slm = $this->getSLM();
        $twig = TemplateRenderer::getInstance();
        return $twig->render('pages/admin/form/itil_config_fields/slm.html.twig', [
            // Possible configuration constant that will be used to to hide/show additional fields
            'CONFIG_SPECIFIC_VALUE'  => SLMFieldStrategy::SPECIFIC_VALUE->value,

            // General display options
            'options' => $display_options,

            // Specific additional config for SPECIFIC_ANSWER strategy
            'specific_value_extra_field' => [
                'slm_class'   => $this->getSLM()::class,
                'empty_label' => sprintf(__("Select a %s..."), $slm->getTypeName()),
                'value'       => $config->getSpecificSLMID() ?? 0,
                'input_name'  => $input_name . "[" . SLMFieldConfig::SLM_ID . "]",
                'type'        => $this->getType(),
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

        // Only one strategy is allowed
        $strategy = current($config->getStrategies());

        // Compute value according to strategy
        $slm_id = $strategy->getSLMID($config);

        // Do not edit input if invalid value was found
        $slm = $this->getSLM();
        if (!$slm::getById($slm_id)) {
            return $input;
        }

        $input[$slm::getFieldNames($this->getType())[1]] = $slm_id;

        return $input;
    }

    #[Override]
    public function getDefaultConfig(Form $form): JsonFieldInterface
    {
        return $this->getConfig($form, [$this->getKey() => [
            SLMFieldConfig::STRATEGY => SLMFieldStrategy::FROM_TEMPLATE->value,
        ]]);
    }

    #[Override]
    public function convertFieldConfig(FormMigration $migration, Form $form, array $rawData): JsonFieldInterface
    {
        switch ($rawData['sla_rule']) {
            case 1: // PluginFormcreatorAbstractItilTarget::SLA_RULE_NONE
                return $this->getConfig($form, [$this->getKey() => [
                    SLMFieldConfig::STRATEGY => SLMFieldStrategy::FROM_TEMPLATE->value,
                ]]);
            case 2: // PluginFormcreatorAbstractItilTarget::SLA_RULE_SPECIFIC
                return $this->getConfig($form, [$this->getKey() => [
                    SLMFieldConfig::STRATEGY => SLMFieldStrategy::SPECIFIC_VALUE->value,
                    SLMFieldConfig::SLM_ID => $rawData[$this->getFieldNameToConvertSpecificSLMID()] ?? null,
                ]]);
        }

        return $this->getDefaultConfig($form);
    }

    public function getStrategiesForDropdown(): array
    {
        $values = [];
        foreach (SLMFieldStrategy::cases() as $strategies) {
            $values[$strategies->value] = $strategies->getLabel($this);
        }
        return $values;
    }

    #[Override]
    public function getCategory(): Category
    {
        return Category::SERVICE_LEVEL;
    }

    #[Override]
    public function exportDynamicConfig(
        array $config,
        AbstractCommonITILFormDestination $destination,
    ): DynamicExportDataField {
        $fallback = parent::exportDynamicConfig($config, $destination);

        // Check if a service level is defined
        $slm_id = $config[SLMFieldConfig::SLM_ID] ?? null;
        if ($slm_id === null) {
            return $fallback;
        }

        // Try to load service level
        $slm = $this->getSLM()::getById($slm_id);
        if (!$slm) {
            return $fallback;
        }

        // Insert service level name and requirement
        $requirement = DataRequirementSpecification::fromItem($slm);
        $config[SLMFieldConfig::SLM_ID] = $requirement->name;

        return new DynamicExportDataField($config, [$requirement]);
    }

    #[Override]
    public static function prepareDynamicConfigDataForImport(
        array $config,
        AbstractCommonITILFormDestination $destination,
        DatabaseMapper $mapper,
    ): array {
        // Check if a service level is defined
        if (!isset($config[SLMFieldConfig::SLM_ID])) {
            return parent::prepareDynamicConfigDataForImport(
                $config,
                $destination,
                $mapper,
            );
        }

        // Insert id
        $slm = (new static())->getSLM();
        $config[SLMFieldConfig::SLM_ID] = $mapper->getItemId(
            $slm::class,
            $config[SLMFieldConfig::SLM_ID],
        );

        return $config;
    }
}
