<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

use CommonITILObject;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\AnswersSet;
use Glpi\Form\Destination\AbstractCommonITILFormDestination;
use Glpi\Form\Destination\AbstractConfigField;
use Glpi\Form\Destination\FormDestination;
use Glpi\Form\Destination\HasFormTags;
use Glpi\Form\Export\Context\DatabaseMapper;
use Glpi\Form\Export\Serializer\DynamicExportDataField;
use Glpi\Form\Export\Specification\DataRequirementSpecification;
use Glpi\Form\Form;
use Glpi\Form\Tag\FormTagsManager;
use InvalidArgumentException;
use ITILFollowup;
use ITILFollowupTemplate;
use Override;
use RuntimeException;
use Session;

use function Safe\json_encode;
use function Safe\preg_match;

#[HasFormTags]
final class ITILFollowupField extends AbstractConfigField
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
        FormDestination $destination,
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
            'CONFIG_SPECIFIC_CONTENT' => ITILFollowupFieldStrategy::SPECIFIC_CONTENT->value,

            // General display options
            'options' => $display_options,

            // Specific additional config for SPECIFIC_VALUES strategy
            'specific_value_extra_field' => [
                'aria_label'     => __("Select followup templates..."),
                'value'           => $config->getSpecificITILFollowupTemplatesIds() ?? [],
                'input_name'      => $input_name . "[" . ITILFollowupFieldConfig::ITILFOLLOWUPTEMPLATE_IDS . "]",
            ],

            // Specific additional config for SPECIFIC_CONTENT strategy
            'specific_content_extra_field' => [
                'aria_label' => __("Followup content"),
                'value'      => (new FormTagsManager())->refreshTagsContent($config->getSpecificContent()),
                'input_name' => $input_name . "[" . ITILFollowupFieldConfig::SPECIFIC_CONTENT . "]",
                'form_id'    => $form->getID(),
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

        // Only one strategy is allowed
        $strategy = current($config->getStrategies());

        // Compute value according to strategy
        $itilfollowuptemplates_ids = $strategy->getITILFollowupTemplatesIDs($config);

        if (!empty($itilfollowuptemplates_ids)) {
            $input['_itilfollowuptemplates_id'] = $itilfollowuptemplates_ids;
        }

        return $input;
    }

    #[Override]
    public function applyConfiguratedValueAfterDestinationCreation(
        FormDestination $destination,
        JsonFieldInterface $config,
        AnswersSet $answers_set,
        array $created_objects
    ): void {
        if (!$config instanceof ITILFollowupFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        // Only one strategy is allowed
        $strategy = current($config->getStrategies());

        // Compute value according to strategy
        $content = $strategy->getITILFollowupContent($config);
        if ($content === null) {
            return;
        }

        // Tags may resolve to empty answers, a followup can't be blank
        $content = (new FormTagsManager())->insertTagsContent($content, $answers_set);
        if (!preg_match('/[^\s\x{00A0}]/u', html_entity_decode(strip_tags($content)))) {
            return;
        }

        foreach ($created_objects[$destination->getID()] ?? [] as $item) {
            if (!$item instanceof CommonITILObject) {
                continue;
            }

            // Same as the ITIL object creation, rights must not depend on the
            // user who submitted the form
            $input = [
                'itemtype' => $item::class,
                'items_id' => $item->getID(),
                'content'  => $content,
                // The status and take into account are set by the form, not
                // by its followup
                '_do_not_compute_status'          => true,
                '_do_not_compute_takeintoaccount' => true,
            ];
            if (!Session::callAsSystem(fn() => (new ITILFollowup())->add($input))) {
                throw new RuntimeException("Failed to create followup: " . json_encode($input));
            }
        }
    }

    #[Override]
    public function getDefaultConfig(Form $form): ITILFollowupFieldConfig
    {
        return new ITILFollowupFieldConfig(
            ITILFollowupFieldStrategy::NO_FOLLOWUP,
        );
    }

    public function getStrategiesForDropdown(): array
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
        return 10;
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
        $template_ids = $config[ITILFollowupFieldConfig::ITILFOLLOWUPTEMPLATE_IDS] ?? null;
        if ($template_ids === null) {
            return $fallback;
        }

        foreach ($template_ids as $i => $template_id) {
            $template = ITILFollowupTemplate::getById($template_id);
            if ($template) {
                // Insert template name and requirement
                $requirement = DataRequirementSpecification::fromItem($template);
                $requirements[] = $requirement;
                $config[ITILFollowupFieldConfig::ITILFOLLOWUPTEMPLATE_IDS][$i] = $requirement->name;
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
        $template_names = $config[ITILFollowupFieldConfig::ITILFOLLOWUPTEMPLATE_IDS] ?? null;
        if ($template_names === null) {
            return parent::prepareDynamicConfigDataForImport(
                $config,
                $destination,
                $mapper,
            );
        }

        // Insert ids
        foreach ($template_names as $i => $template_name) {
            $id = $mapper->getItemId(ITILFollowupTemplate::class, $template_name);
            $config[ITILFollowupFieldConfig::ITILFOLLOWUPTEMPLATE_IDS][$i] = $id;
        }

        return $config;
    }
}
