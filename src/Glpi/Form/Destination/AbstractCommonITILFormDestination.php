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

namespace Glpi\Form\Destination;

use CommonITILObject;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Form\AnswersSet;
use Glpi\Form\Destination\CommonITILField\AssigneeField;
use Glpi\Form\Destination\CommonITILField\AssociatedItemsField;
use Glpi\Form\Destination\CommonITILField\ContentField;
use Glpi\Form\Destination\CommonITILField\EntityField;
use Glpi\Form\Destination\CommonITILField\ITILCategoryField;
use Glpi\Form\Destination\CommonITILField\ITILFollowupField;
use Glpi\Form\Destination\CommonITILField\LocationField;
use Glpi\Form\Destination\CommonITILField\RequestSourceField;
use Glpi\Form\Destination\CommonITILField\TemplateField;
use Glpi\Form\Destination\CommonITILField\ITILTaskField;
use Glpi\Form\Destination\CommonITILField\ObserverField;
use Glpi\Form\Destination\CommonITILField\RequesterField;
use Glpi\Form\Destination\CommonITILField\TitleField;
use Glpi\Form\Destination\CommonITILField\UrgencyField;
use Glpi\Form\Destination\CommonITILField\ValidationField;
use Glpi\Form\Form;
use Override;
use Ticket;

abstract class AbstractCommonITILFormDestination implements FormDestinationInterface
{
    /** @return class-string<\CommonITILObject>   */
    abstract public function getTargetItemtype(): string;

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
    final public function getLabel(): string
    {
        return $this->getTargetItemtype()::getTypeName(1);
    }

    #[Override]
    final public function getIcon(): string
    {
        return $this->getTargetItemtype()::getIcon();
    }

    #[Override]
    final public function createDestinationItems(
        Form $form,
        AnswersSet $answers_set,
        array $config,
    ): array {
        $typename        = $this->getLabel();
        $itemtype        = $this->getTargetItemtype();
        $fields_to_apply = $this->getConfigurableFields();

        // Mandatory values, we must preset defaults values as it can't be
        // missing from the input.
        $input = [
            'name'    => '',
            'content' => '',
        ];

        // Template field must be computed before applying predefined fields
        $target_itemtype = $this->getTargetItemtype();
        $template_class = (new $target_itemtype())->getTemplateClass();
        $template_field = new TemplateField($template_class);
        $input = $template_field->applyConfiguratedValueToInputUsingAnswers(
            $template_field->getConfig($form, $config),
            $input,
            $answers_set
        );

        // Remove template field from fields to apply
        $fields_to_apply = array_filter(
            $fields_to_apply,
            fn($field) => !$field instanceof TemplateField
        );

        // ITILCategory field must be computed before applying predefined fields
        $itilcategory_field = new ITILCategoryField();
        $input = $itilcategory_field->applyConfiguratedValueToInputUsingAnswers(
            $itilcategory_field->getConfig($form, $config),
            $input,
            $answers_set
        );

        // Compute and apply template predefined template fields
        $input = $this->applyPredefinedTemplateFields($input);

        // Compute input from fields configuration
        foreach ($this->getConfigurableFields() as $field) {
            $input = $field->applyConfiguratedValueToInputUsingAnswers(
                $field->getConfig($form, $config),
                $input,
                $answers_set
            );
        }

        // Add linked items
        $input = $this->setFilesInput($input, $answers_set);

        // Create commonitil object
        $itil_object = new $itemtype();

        // It is safer to ignore this phpstan error as plugin code may not be
        // statically analyzed and we don't want it to create unexpected issues.
        // @phpstan-ignore-next-line instanceof.alwaysTrue
        if (!($itil_object instanceof CommonITILObject)) {
            throw new \RuntimeException(
                "The target itemtype must be an instance of CommonITILObject"
            );
        }
        if (!$itil_object->add($input)) {
            throw new \Exception(
                "Failed to create $typename: " . json_encode($input)
            );
        }

        // If requested, link the form directly to the commonitil object
        // This allow users to see it an an associated item and known where the
        // commonitil object come from
        $link_class = $itil_object::getItemLinkClass();
        $link = new $link_class();
        $input = [
            $itil_object->getForeignKeyField() => $itil_object->getID(),
            'itemtype'                         => $form::class,
            'items_id'                         => $form->getID(),
        ];
        if (!$link->add($input)) {
            throw new \Exception(
                "Failed to create item link for $typename: " . json_encode($input)
            );
        }

        return [$itil_object];
    }

    /**
     * Get the sorted configurable fields for this destination type.
     *
     * @return \Glpi\Form\Destination\AbstractConfigField[]
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
     * @return \Glpi\Form\Destination\AbstractConfigField[]
     */
    protected function defineConfigurableFields(): array
    {
        $target_itemtype = $this->getTargetItemtype();
        $template_class = (new $target_itemtype())->getTemplateClass();

        return [
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
        ];
    }

    /**
     * Get a configurable field by its key.
     *
     * @param string $key
     * @return \Glpi\Form\Destination\AbstractConfigField|null
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

    private function applyPredefinedTemplateFields(array $input): array
    {
        $itemtype = $this->getTargetItemtype();

        /** @var \CommonITILObject $itil */
        $itil = new $itemtype();
        $template = $itil->getITILTemplateToUse(
            entities_id: $_SESSION["glpiactive_entity"],
            itilcategories_id: $input['itilcategories_id'] ?? 0,
            type: $input['type'] ?? (isset($input['itilcategories_id']) ? Ticket::INCIDENT_TYPE : null)
        );
        $template_foreign_key = $template::getForeignKeyField();

        if (isset($input[$template_foreign_key])) {
            $template->getFromDB($input[$template_foreign_key]);
        } else {
            $input[$template_foreign_key] = $template->getID();
        }

        $predefined_fields_class = $itemtype . "TemplatePredefinedField";

        /** @var \ITILTemplatePredefinedField $predefined_fields */
        $predefined_fields = new $predefined_fields_class();

        $fields = $predefined_fields->getPredefinedFields($template->fields['id']);
        foreach ($fields as $field => $value) {
            $input[$field] = $value;
        }

        return $input;
    }

    private function setFilesInput(array $input, AnswersSet $answers_set): array
    {
        $files = $answers_set->getSubmittedFiles();
        if (empty($files) || empty($files['filename'])) {
            return $input;
        }

        $input['_filename']        = $files['filename'];
        $input['_prefix_filename'] = $files['prefix'];
        $input['_tag_filename']    = $files['tag'];

        return $input;
    }
}
