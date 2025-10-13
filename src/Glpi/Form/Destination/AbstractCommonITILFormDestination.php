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

namespace Glpi\Form\Destination;

use CommonITILObject;
use DBmysql;
use Exception;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\PrepareForCloneInterface;
use Glpi\Form\AnswersSet;
use Glpi\Form\Clone\FormCloneHelper;
use Glpi\Form\Destination\CommonITILField\AssigneeField;
use Glpi\Form\Destination\CommonITILField\AssociatedItemsField;
use Glpi\Form\Destination\CommonITILField\ContentField;
use Glpi\Form\Destination\CommonITILField\EntityField;
use Glpi\Form\Destination\CommonITILField\ITILCategoryField;
use Glpi\Form\Destination\CommonITILField\ITILFollowupField;
use Glpi\Form\Destination\CommonITILField\ITILTaskField;
use Glpi\Form\Destination\CommonITILField\LinkedITILObjectsField;
use Glpi\Form\Destination\CommonITILField\LocationField;
use Glpi\Form\Destination\CommonITILField\ObserverField;
use Glpi\Form\Destination\CommonITILField\RequesterField;
use Glpi\Form\Destination\CommonITILField\RequestSourceField;
use Glpi\Form\Destination\CommonITILField\TemplateField;
use Glpi\Form\Destination\CommonITILField\TitleField;
use Glpi\Form\Destination\CommonITILField\UrgencyField;
use Glpi\Form\Destination\CommonITILField\ValidationField;
use Glpi\Form\Export\Context\DatabaseMapper;
use Glpi\Form\Export\Serializer\DynamicExportDataField;
use Glpi\Form\Form;
use Glpi\Form\Tag\FormTagsManager;
use Override;
use ReflectionClass;
use Session;
use Ticket;

use function Safe\json_encode;

abstract class AbstractCommonITILFormDestination implements FormDestinationInterface, PrepareForCloneInterface
{
    abstract public function getTarget(): CommonITILObject;

    final public function __construct() {}

    #[Override]
    final public function renderConfigForm(
        Form $form,
        FormDestination $destination,
        array $config
    ): string {
        $twig = TemplateRenderer::getInstance();
        return $twig->render(
            'pages/admin/form/form_destination_commonitil_config.html.twig',
            [
                'form'        => $form,
                'item'        => $this,
                'config'      => $config,
                'destination' => $destination,
                'can_update'  => FormDestination::canUpdate(),
            ]
        );
    }

    #[Override]
    public function useDefaultConfigLayout(): bool
    {
        return false;
    }

    #[Override]
    final public function getLabel(): string
    {
        return $this->getTarget()::getTypeName(1);
    }

    #[Override]
    final public function getIcon(): string
    {
        return $this->getTarget()::getIcon();
    }

    #[Override]
    final public function createDestinationItems(
        Form $form,
        AnswersSet $answers_set,
        array $config,
    ): array {
        $typename               = $this->getLabel();
        $itil_object            = $this->getTarget();
        $fields_to_apply        = $this->getConfigurableFields();
        $already_applied_fields = [];

        // Mandatory values, we must preset defaults values as it can't be
        // missing from the input.
        $input = [
            'name'    => '',
            'content' => '',
        ];

        // Entity must be computed first as it will be used to pick the correct template
        $entity_field = new EntityField();
        $input = $entity_field->applyConfiguratedValueToInputUsingAnswers(
            $entity_field->getConfig($form, $config),
            $input,
            $answers_set
        );
        $already_applied_fields[] = EntityField::class;

        // Template field must be computed before applying predefined fields
        $target_itemtype = $this->getTarget();
        $template_class = (new $target_itemtype())->getTemplateClass();
        $template_field = new TemplateField($template_class);
        $input = $template_field->applyConfiguratedValueToInputUsingAnswers(
            $template_field->getConfig($form, $config),
            $input,
            $answers_set
        );
        $already_applied_fields[] = TemplateField::class;

        // ITILCategory field must be computed before applying predefined fields
        $itilcategory_field = new ITILCategoryField();
        $input = $itilcategory_field->applyConfiguratedValueToInputUsingAnswers(
            $itilcategory_field->getConfig($form, $config),
            $input,
            $answers_set
        );
        $already_applied_fields[] = ITILCategoryField::class;

        // Compute and apply template predefined template fields
        $input = $this->applyPredefinedTemplateFields($input);

        // Remove already applied fields from fields to apply
        $fields_to_apply = array_filter(
            $fields_to_apply,
            fn($field) => !in_array(get_class($field), $already_applied_fields)
        );

        // Compute input from fields configuration
        foreach ($fields_to_apply as $field) {
            $input = $field->applyConfiguratedValueToInputUsingAnswers(
                $field->getConfig($form, $config, $answers_set),
                $input,
                $answers_set
            );
        }

        // Add linked items
        $input = $this->setFilesInput($input, $answers_set);

        // Create commonitil object
        // We use 'callAsSystem' here because Ticket::prepareInputForAdd() has
        // rights checks for some features (SLA, ...) and will yield different
        // results depending on the current user rights
        $id = Session::callAsSystem(fn() => $itil_object->add($input));
        if (!$id) {
            throw new Exception(
                "Failed to create $typename: " . json_encode($input)
            );
        }

        // If requested, link the form directly to the commonitil object
        // This allow users to see it an an associated item and known where the
        // commonitil object come from
        $link = getItemForItemtype($itil_object::getItemLinkClass());
        $input = [
            $itil_object->getForeignKeyField() => $itil_object->getID(),
            'itemtype'                         => $form::class,
            'items_id'                         => $form->getID(),
        ];
        if (!$link->add($input)) {
            throw new Exception(
                "Failed to create item link for $typename: " . json_encode($input)
            );
        }

        return [$itil_object];
    }

    #[Override]
    public function postCreateDestinationItems(
        Form $form,
        AnswersSet $answers_set,
        FormDestination $destination,
        array $created_items,
    ): void {
        foreach ($this->getConfigurableFields() as $field) {
            $field->applyConfiguratedValueAfterDestinationCreation(
                $destination,
                $field->getConfig($form, $destination->getConfig()),
                $answers_set,
                $created_items
            );
        }
    }

    #[Override]
    public function prepareInputForClone(array $data): array
    {
        $fields = $this->defineConfigurableFields();
        foreach ($fields as $field) {
            if (!isset($data[$field::getKey()])) {
                continue;
            }

            $data[$field::getKey()] = FormCloneHelper::getInstance()
                ->prepareCommonItilDestinationFieldInputForClone(
                    $field,
                    $data[$field::getKey()]
                )
            ;
        }

        return $data;
    }

    /**
     * Get the sorted configurable fields for this destination type.
     *
     * @return AbstractConfigField[]
     */
    final public function getConfigurableFields(): array
    {
        $fields = $this->defineConfigurableFields();
        usort($fields, function (AbstractConfigField $a, AbstractConfigField $b): int {
            if ($a->getCategory() == $b->getCategory()) {
                return $a->getWeight() <=> $b->getWeight();
            } else {
                return $a->getCategory()->getWeight() <=> $b->getCategory()->getWeight();
            }
        });

        return $fields;
    }

    /**
     * Get the sorted configurable fields for this destination type, grouped by category
     */
    final public function getConfigurableFieldsGroupedByCategory(): array
    {
        $fields = $this->getConfigurableFields();
        $categories = [];
        foreach ($fields as $field) {
            $category = $field->getCategory();
            if (!isset($categories[$category->value])) {
                $categories[$category->value] = [
                    'label'  => $category->getLabel(),
                    'icon'   => $category->getIcon(),
                    'fields' => [],
                ];
            }

            $categories[$category->value]['fields'][] = $field;
        }

        return $categories;
    }

    /**
     * List the configurable fields for this destination type.
     *
     * @return AbstractConfigField[]
     */
    protected function defineConfigurableFields(): array
    {
        $template_class = $this->getTarget()->getTemplateClass();

        $core_fields = [
            new TitleField(),
            new ContentField(),
            new TemplateField($template_class),
            new UrgencyField(),
            new ITILCategoryField(),
            new EntityField(),
            new LocationField(),
            new AssociatedItemsField(),
            new ITILFollowupField(),
            new RequestSourceField(),
            new ValidationField(),
            new ITILTaskField(),
            new RequesterField(),
            new ObserverField(),
            new AssigneeField(),
            new LinkedITILObjectsField(),
        ];

        // Add plugin config fields specific to this common ITIL destination type
        $plugin_fields = FormDestinationManager::getInstance()
            ->getPluginCommonITILConfigFields(static::class);

        return array_merge($core_fields, $plugin_fields);
    }

    /**
     * Get a configurable field by its key.
     *
     * @param string $key
     * @return AbstractConfigField|null
     */
    public function getConfigurableFieldByKey(string $key): ?AbstractConfigField
    {
        foreach ($this->getConfigurableFields() as $field) {
            if ($field::getKey() === $key) {
                return $field;
            }
        }

        return null;
    }

    final public function formatConfigInputName(string $field_key): string
    {
        // Handle array fields
        if (str_ends_with($field_key, '[]')) {
            return "config[" . rtrim($field_key, '[]') . "][]";
        }

        return "config[$field_key]";
    }

    #[Override]
    final public function exportDynamicConfig(
        array $config
    ): DynamicExportDataField {
        $requirements = [];
        foreach ($config as $field_key => $field_config_data) {
            $field = $this->getConfigurableFieldByKey($field_key);
            if (!$field instanceof AbstractConfigField) {
                continue;
            }
            $export_data = $field->exportDynamicConfig($field_config_data, $this);

            // Apply config for this field
            $config[$field_key] = $export_data->getData();
            array_push($requirements, ...$export_data->getRequirements());
        }

        return new DynamicExportDataField($config, $requirements);
    }

    #[Override]
    final public static function prepareDynamicConfigDataForImport(
        array $config,
        DatabaseMapper $mapper,
    ): array {
        foreach ($config as $field_key => $field_config_data) {
            $destination = new static();
            $field = $destination->getConfigurableFieldByKey($field_key);
            if (!$field instanceof AbstractConfigField) {
                continue;
            }

            // Prepare field config for import
            $config[$field_key] = $field->prepareDynamicConfigDataForImport(
                $field_config_data,
                $destination,
                $mapper,
            );

            // Handle form tags if needed
            $reflection = new ReflectionClass($field);
            $attributes = $reflection->getAttributes(HasFormTags::class);
            if (!count($attributes)) {
                continue;
            }

            $tags_manager = (new FormTagsManager());
            $config[$field_key]['value'] = $tags_manager->replaceIdsInTags(
                $config[$field_key]['value'],
                $mapper,
            );
        }

        return $config;
    }

    private function applyPredefinedTemplateFields(array $input): array
    {
        /** @var DBmysql $DB */
        global $DB;

        $itil = $this->getTarget();
        $fields_definition = $DB->listFields($itil::getTable());
        $template = $itil->getITILTemplateToUse(
            entities_id: $input['entities_id'],
            itilcategories_id: $input['itilcategories_id'] ?? 0,
            type: $input['type'] ?? (isset($input['itilcategories_id']) ? Ticket::INCIDENT_TYPE : null)
        );
        $template_foreign_key = $template::getForeignKeyField();

        if (!isset($template->fields['id'])) {
            // No template found
            return $input;
        }

        if (isset($input[$template_foreign_key])) {
            $template->getFromDB($input[$template_foreign_key]);
        } else {
            $input[$template_foreign_key] = $template->getID();
        }

        $predefined_fields = $itil->getTemplateClass()::getPredefinedFields();

        $fields = $predefined_fields->getPredefinedFields($template->fields['id']);
        foreach ($fields as $field => $value) {
            $field_definition = $fields_definition[$field] ?? null;
            if (
                $field_definition
                && $value === "NOW"
                && (
                    $field_definition['Type'] == "timestamp"
                    || $field_definition['Type'] == "date"
                    || $field_definition['Type'] == "datetime"
                )
            ) {
                // Handle specific "NOW" value
                // Note: this should probably be handled directly by
                // getPredefinedFields, but the change should not be done in a
                // bugfixe releases as it might impact other features
                if ($field_definition['Type'] == "date") {
                    $input[$field] = Session::getCurrentDate();
                } else {
                    $input[$field] = Session::getCurrentTime();
                }
            } else {
                $input[$field] = $value;
            }
        }

        return $input;
    }

    private function setFilesInput(array $input, AnswersSet $answers_set): array
    {
        $files = $answers_set->getSubmittedFiles();
        if ($files === [] || empty($files['filename'])) {
            return $input;
        }

        $input['_filename']        = $files['filename'];
        $input['_prefix_filename'] = $files['prefix'];
        $input['_tag_filename']    = $files['tag'];

        return $input;
    }
}
