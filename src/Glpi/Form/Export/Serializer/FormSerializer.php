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

namespace Glpi\Form\Export\Serializer;

use Entity;
use Glpi\Form\Export\Context\DatabaseContext;
use Glpi\Form\Export\Specification\ExportContentSpecification;
use Glpi\Form\Export\Specification\FormContentSpecification;
use Glpi\Form\Form;

final class FormSerializer extends AbstractFormSerializer
{
    public function getVersion(): int
    {
        return 1;
    }

    /** @property Form[] $forms */
    public function exportFormsToJson(array $forms): string
    {
        $export_specification = new ExportContentSpecification();
        $export_specification->version = $this->getVersion();

        foreach ($forms as $form) {
            $form_spec = $this->exportFormToSpec($form);
            $form_requirements = $form_spec->getDataRequirements();

            // Add forms and its requirements to the main export spec
            $export_specification->addForm($form_spec);
            $export_specification->addDataRequirements($form_requirements);
        }

        return $this->serialize($export_specification);
    }

    /** @return Form[] */
    public function importFormsFromJson(
        string $json,
        DatabaseContext $context = new DatabaseContext(),
    ): array {
        $export_specification = $this->deserialize($json);

        // Validate version
        if ($export_specification->version !== $this->getVersion()) {
            throw new \InvalidArgumentException("Unsupported version");
        }

        // Validate database context
        $requirements = $export_specification->data_requirements;
        $context->loadExistingContextForRequirements($requirements);
        if (!$context->validateRequirements($requirements)) {
            throw new \InvalidArgumentException("Missing required data");
        }

        // Import each forms
        $forms = [];
        foreach ($export_specification->forms as $form_spec) {
            $forms[] = $this->importFormFromSpec($form_spec, $context);
        }

        return $forms;
    }

    private function exportFormToSpec(Form $form): FormContentSpecification
    {
        // TODO: questions, sections, ...
        $form_spec = $this->exportBasicFormProperties($form);

        return $form_spec;
    }

    private function importFormFromSpec(
        FormContentSpecification $form_spec,
        DatabaseContext $context = new DatabaseContext(),
    ): Form {
        // TODO: questions, sections, ...
        $form = $this->importBasicFormProperties($form_spec, $context);

        return $form;
    }

    private function exportBasicFormProperties(
        Form $form
    ): FormContentSpecification {
        $spec               = new FormContentSpecification();
        $spec->name         = $form->fields['name'];
        $spec->header       = $form->fields['header'];
        $spec->is_recursive = $form->fields['is_recursive'];

        $entity = Entity::getById($form->fields['entities_id']);
        $spec->entity_name = $entity->fields['name'];
        $spec->addDataRequirement(Entity::class, $entity->fields['name']);

        return $spec;
    }

    private function importBasicFormProperties(
        FormContentSpecification $spec,
        DatabaseContext $context = new DatabaseContext(),
    ): Form {
        if (!($spec instanceof FormContentSpecification)) {
            throw new \InvalidArgumentException("Unsupported version");
        }

        // Get ids from context
        $entities_id = $context->getItemId(Entity::class, $spec->entity_name);

        $form = new Form();
        $id = $form->add([
            'name'         => $spec->name,
            'header'       => $spec->header,
            'entities_id'  => $entities_id,
            'is_recursive' => $spec->is_recursive,
        ]);
        $form->getFromDB($id);

        return $form;
    }
}
