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

abstract class AbstractCommonITILFormDestination extends AbstractFormDestinationType
{
    #[Override]
    final public function renderConfigForm(Form $form, array $config): string
    {
        $twig = TemplateRenderer::getInstance();
        return $twig->render(
            'pages/admin/form/form_destination_commonitil_config.html.twig',
            [
                'form'   => $form,
                'item'   => $this,
                'config' => $config,
            ]
        );
    }

    #[Override]
    final public static function getTypeName($nb = 0)
    {
        return static::getTargetItemtype()::getTypeName($nb);
    }

    #[Override]
    final public function createDestinationItems(
        Form $form,
        AnswersSet $answers_set,
        array $config
    ): array {
        $typename        = static::getTypeName(1);
        $itemtype        = static::getTargetItemtype();
        $fields_to_apply = $this->getConfigurableFields();

        // Mandatory values, we must preset defaults values as it can't be
        // missing from the input.
        $input = [
            'name'    => '',
            'content' => '',
        ];

        // Template field must be computed before applying predefined fields
        $target_itemtype = static::getTargetItemtype();
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

        // We will also link the answers directly to the commonitil object
        // This allow users to see it an an associated item and known where the
        // commonitil object come from
        $link_class = $itil_object::getItemLinkClass();
        $link = new $link_class();
        $input = [
            $itil_object->getForeignKeyField() => $itil_object->getID(),
            'itemtype'                             => $answers_set::class,
            'items_id'                             => $answers_set->getID(),
        ];
        if (!$link->add($input)) {
            throw new \Exception(
                "Failed to create item link for $typename: " . json_encode($input)
            );
        }

        return [$itil_object];
    }

    #[Override]
    final public static function getFilterByAnswsersSetSearchOptionID(): int
    {
        return 120;
    }

    /**
     * List the configurable fields for this destination type.
     *
     * @return \Glpi\Form\Destination\AbstractConfigField[]
     */
    public function getConfigurableFields(): array
    {
        $target_itemtype = static::getTargetItemtype();
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
        $itemtype = static::getTargetItemtype();

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
