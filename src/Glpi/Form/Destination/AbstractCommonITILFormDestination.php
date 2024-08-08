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

namespace Glpi\Form\Destination;

use CommonITILObject;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Form\AnswersSet;
use Glpi\Form\Destination\CommonITILField\ContentField;
use Glpi\Form\Destination\CommonITILField\LocationField;
use Glpi\Form\Destination\CommonITILField\TitleField;
use Glpi\Form\Form;
use Override;

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
        $typename = static::getTypeName(1);
        $itemtype = static::getTargetItemtype();

        // Mandatory values, we must preset defaults values as it can't be
        // missing from the input.
        $input = [
            'name'    => '',
            'content' => '',
            // Temporary as entity configuration is not yet available
            'entities_id' => $form->fields['entities_id']
        ];

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
        return [
            new TitleField(),
            new ContentField(),
            new LocationField(),
        ];
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
            entities_id: $_SESSION["glpiactive_entity"]
        );

        $predefined_fields_class = $itemtype . "TemplatePredefinedField";

        /** @var \ITILTemplatePredefinedField $predefined_fields */
        $predefined_fields = new $predefined_fields_class();

        $fields = $predefined_fields->getPredefinedFields($template->fields['id']);
        foreach ($fields as $field => $value) {
            $input[$field] = $value;
        }

        return $input;
    }
}
