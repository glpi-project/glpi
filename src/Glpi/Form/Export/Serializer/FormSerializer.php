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
use Glpi\Form\Export\Context\DatabaseMapper;
use Glpi\Form\Export\Result\ImportError;
use Glpi\Form\Export\Result\ImportResult;
use Glpi\Form\Export\Result\ImportResultPreview;
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
            // Add forms to the main export spec
            $form_spec = $this->exportFormToSpec($form);
            $export_specification->addForm($form_spec);
        }

        return $this->serialize($export_specification);
    }

    public function previewImport(
        string $json,
        DatabaseMapper $mapper = new DatabaseMapper(),
    ): ImportResultPreview {
        $export_specification = $this->deserialize($json);

        // Validate version
        if ($export_specification->version !== $this->getVersion()) {
            throw new \InvalidArgumentException("Unsupported version");
        }

        // Validate each forms
        $results = new ImportResultPreview();
        foreach ($export_specification->forms as $form_spec) {
            $requirements = $form_spec->data_requirements;
            $mapper->mapExistingItemsForRequirements($requirements);

            $form_name = $form_spec->name;
            if ($mapper->validateRequirements($requirements)) {
                $results->addValidForm($form_name);
            } else {
                $results->addInvalidForm($form_name);
            }
        }

        return $results;
    }

    public function importFormsFromJson(
        string $json,
        DatabaseMapper $mapper = new DatabaseMapper(),
    ): ImportResult {
        $export_specification = $this->deserialize($json);

        // Validate version
        if ($export_specification->version !== $this->getVersion()) {
            throw new \InvalidArgumentException("Unsupported version");
        }

        // Import each forms
        $result = new ImportResult();
        foreach ($export_specification->forms as $form_spec) {
            $requirements = $form_spec->data_requirements;
            $mapper->mapExistingItemsForRequirements($requirements);

            if (!$mapper->validateRequirements($requirements)) {
                $result->addFailedFormImport(
                    $form_spec->name,
                    ImportError::MISSING_DATA_REQUIREMENT
                );
                continue;
            }

            $form = $this->importFormFromSpec($form_spec, $mapper);
            $result->addImportedForm($form);
        }

        return $result;
    }

    private function exportFormToSpec(Form $form): FormContentSpecification
    {
        // TODO: questions, sections, ...
        $form_spec = $this->exportBasicFormProperties($form);

        return $form_spec;
    }

    private function importFormFromSpec(
        FormContentSpecification $form_spec,
        DatabaseMapper $mapper = new DatabaseMapper(),
    ): Form {
        /** @var \DBmysql $DB */
        global $DB;

        $use_transaction = !$DB->inTransaction();

        if ($use_transaction) {
            $DB->beginTransaction();
            $forms = $this->doImportFormFormSpecs($form_spec, $mapper);
            $DB->commit();
        } else {
            $forms = $this->doImportFormFormSpecs($form_spec, $mapper);
        }

        return $forms;
    }

    private function doImportFormFormSpecs(
        FormContentSpecification $form_spec,
        DatabaseMapper $mapper = new DatabaseMapper(),
    ): Form {
        // TODO: questions, sections, ...
        $form = $this->importBasicFormProperties($form_spec, $mapper);

        return $form;
    }

    private function exportBasicFormProperties(
        Form $form
    ): FormContentSpecification {
        $spec               = new FormContentSpecification();
        $spec->name         = $form->fields['name'];
        $spec->header       = $form->fields['header'] ?? "";
        $spec->is_recursive = $form->fields['is_recursive'];

        $entity = Entity::getById($form->fields['entities_id']);
        $spec->entity_name = $entity->fields['name'];
        $spec->addDataRequirement(Entity::class, $entity->fields['name']);

        return $spec;
    }

    private function importBasicFormProperties(
        FormContentSpecification $spec,
        DatabaseMapper $mapper = new DatabaseMapper(),
    ): Form {
        if (!($spec instanceof FormContentSpecification)) {
            throw new \InvalidArgumentException("Unsupported version");
        }

        // Get ids from mapper
        $entities_id = $mapper->getItemId(Entity::class, $spec->entity_name);

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
