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
use InvalidArgumentException;
use Override;
use Session;
use TaskTemplate;

final class ITILTaskField extends AbstractConfigField
{
    #[Override]
    public function getLabel(): string
    {
        return _n('Task', 'Tasks', Session::getPluralNumber());
    }

    #[Override]
    public function getConfigClass(): string
    {
        return ITILTaskFieldConfig::class;
    }

    #[Override]
    public function renderConfigForm(
        Form $form,
        FormDestination $destination,
        JsonFieldInterface $config,
        string $input_name,
        array $display_options
    ): string {
        if (!$config instanceof ITILTaskFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        $twig = TemplateRenderer::getInstance();
        return $twig->render('pages/admin/form/itil_config_fields/itiltasktemplate.html.twig', [
            // Possible configuration constant that will be used to to hide/show additional fields
            'CONFIG_SPECIFIC_VALUES'  => ITILTaskFieldStrategy::SPECIFIC_VALUES->value,

            // General display options
            'options' => $display_options,

            // Specific additional config for SPECIFIC_VALUES strategy
            'specific_value_extra_field' => [
                'aria_label'     => __("Select task templates..."),
                'value'           => $config->getSpecificTaskTemplatesIds() ?? [],
                'input_name'      => $input_name . "[" . ITILTaskFieldConfig::TASKTEMPLATE_IDS . "]",
            ],
        ]);
    }

    #[Override]
    public function applyConfiguratedValueToInputUsingAnswers(
        JsonFieldInterface $config,
        array $input,
        AnswersSet $answers_set
    ): array {
        if (!$config instanceof ITILTaskFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        // Only one strategy is allowed
        $strategy = current($config->getStrategies());

        // Compute value according to strategy
        $tasktemplates_ids = $strategy->getTaskTemplatesIDs($config);

        if (!empty($tasktemplates_ids)) {
            $input['_tasktemplates_id'] = $tasktemplates_ids;
        }

        return $input;
    }

    #[Override]
    public function getDefaultConfig(Form $form): ITILTaskFieldConfig
    {
        return new ITILTaskFieldConfig(
            ITILTaskFieldStrategy::NO_TASK,
        );
    }

    public function getStrategiesForDropdown(): array
    {
        $values = [];
        foreach (ITILTaskFieldStrategy::cases() as $strategies) {
            $values[$strategies->value] = $strategies->getLabel();
        }
        return $values;
    }

    #[Override]
    public function getWeight(): int
    {
        return 20;
    }

    #[Override]
    public function prepareInput(array $input): array
    {
        $input = parent::prepareInput($input);

        // Ensure that itilfollowuptemplate_ids is an array
        if (!is_array($input[$this->getKey()][ITILTaskFieldConfig::TASKTEMPLATE_IDS] ?? null)) {
            unset($input[$this->getKey()][ITILTaskFieldConfig::TASKTEMPLATE_IDS]);
        }

        return $input;
    }

    #[Override]
    public function getCategory(): Category
    {
        return Category::TIMELINE;
    }

    #[Override]
    public function exportDynamicConfig(
        array $config,
        AbstractCommonITILFormDestination $destination,
    ): DynamicExportDataField {
        $fallback = parent::exportDynamicConfig($config, $destination);
        $requirements = [];

        // Check if templates are defined
        $template_ids = $config[ITILTaskFieldConfig::TASKTEMPLATE_IDS] ?? null;
        if ($template_ids === null) {
            return $fallback;
        }

        foreach ($template_ids as $i => $template_id) {
            $template = TaskTemplate::getById($template_id);
            if ($template) {
                // Insert template name and requirement
                $requirement = DataRequirementSpecification::fromItem($template);
                $requirements[] = $requirement;
                $config[ITILTaskFieldConfig::TASKTEMPLATE_IDS][$i] = $requirement->name;
            }
        }

        return new DynamicExportDataField($config, $requirements);
    }

    #[Override]
    public static function prepareDynamicConfigDataForImport(
        array $config,
        AbstractCommonITILFormDestination $destination,
        DatabaseMapper $mapper,
    ): array {
        // Check if templates are defined
        $template_names = $config[ITILTaskFieldConfig::TASKTEMPLATE_IDS] ?? null;
        if ($template_names === null) {
            return parent::prepareDynamicConfigDataForImport(
                $config,
                $destination,
                $mapper
            );
        }

        // Insert ids
        foreach ($template_names as $i => $template_name) {
            $id = $mapper->getItemId(TaskTemplate::class, $template_name);
            $config[ITILTaskFieldConfig::TASKTEMPLATE_IDS][$i] = $id;
        }

        return $config;
    }
}
