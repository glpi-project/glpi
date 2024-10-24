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
use Glpi\Form\AccessControl\FormAccessControl;
use Glpi\Form\Comment;
use Glpi\Form\Export\Context\DatabaseMapper;
use Glpi\Form\Export\Context\ConfigWithForeignKeysInterface;
use Glpi\Form\Export\Result\ExportResult;
use Glpi\Form\Export\Result\ImportError;
use Glpi\Form\Export\Result\ImportResult;
use Glpi\Form\Export\Result\ImportResultIssues;
use Glpi\Form\Export\Result\ImportResultPreview;
use Glpi\Form\Export\Specification\AccesControlPolicyContentSpecification;
use Glpi\Form\Export\Specification\CommentContentSpecification;
use Glpi\Form\Export\Specification\ExportContentSpecification;
use Glpi\Form\Export\Specification\FormContentSpecification;
use Glpi\Form\Export\Specification\SectionContentSpecification;
use Glpi\Form\Form;
use Glpi\Form\Section;
use InvalidArgumentException;
use RuntimeException;
use Session;

final class FormSerializer extends AbstractFormSerializer
{
    public function getVersion(): int
    {
        return 1;
    }

    /** @param array<Form> $forms */
    public function exportFormsToJson(array $forms): ExportResult
    {
        $export_specification = new ExportContentSpecification();
        $export_specification->version = $this->getVersion();

        foreach ($forms as $index => $form) {
            // Add forms to the main export spec
            $form_spec = $this->exportFormToSpec($form, $index);
            $export_specification->addForm($form_spec);
        }

        return new ExportResult(
            filename: $this->computeJsonFileName($forms),
            json_content: $this->serialize($export_specification),
        );
    }

    public function previewImport(
        string $json,
        DatabaseMapper $mapper,
        array $skipped_forms = [],
    ): ImportResultPreview {
        $export_specification = $this->deserialize($json);

        // Validate version
        if ($export_specification->version !== $this->getVersion()) {
            throw new InvalidArgumentException("Unsupported version");
        }

        // Validate each forms
        $results = new ImportResultPreview();
        foreach ($export_specification->forms as $form_spec) {
            $requirements = $form_spec->data_requirements;
            $mapper->mapExistingItemsForRequirements($requirements);

            $form_id = $form_spec->id;
            $form_name = $form_spec->name;
            if (in_array($form_id, $skipped_forms)) {
                $results->addSkippedForm($form_id, $form_name);
                continue;
            }

            if ($mapper->validateRequirements($requirements)) {
                $results->addValidForm($form_id, $form_name);
            } else {
                $results->addInvalidForm($form_id, $form_name);
            }
        }

        return $results;
    }

    public function listIssues(
        DatabaseMapper $mapper,
        string $json
    ): ImportResultIssues {
        $export_specification = $this->deserialize($json);

        // Validate version
        if ($export_specification->version !== $this->getVersion()) {
            throw new InvalidArgumentException("Unsupported version");
        }

        $results = new ImportResultIssues();
        foreach ($export_specification->forms as $form_spec) {
            $requirements = $form_spec->data_requirements;
            $mapper->mapExistingItemsForRequirements($requirements);

            $results->addIssuesForForm(
                $form_spec->id,
                $mapper->getInvalidRequirements($requirements)
            );
        }

        return $results;
    }

    public function importFormsFromJson(
        string $json,
        DatabaseMapper $mapper,
        array $skipped_forms = [],
    ): ImportResult {
        $export_specification = $this->deserialize($json);

        // Validate version
        if ($export_specification->version !== $this->getVersion()) {
            throw new InvalidArgumentException("Unsupported version");
        }

        // Import each forms
        $result = new ImportResult();
        foreach ($export_specification->forms as $form_spec) {
            $requirements = $form_spec->data_requirements;
            $mapper->mapExistingItemsForRequirements($requirements);

            $form_id = $form_spec->id;
            if (in_array($form_id, $skipped_forms)) {
                continue;
            }

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

    /** @param array<Form> $forms */
    private function computeJsonFileName(array $forms): string
    {
        $date = Session::getCurrentDate();

        if (count($forms) === 1) {
            $form = current($forms);
            $formatted_name = \Toolbox::slugify($form->fields['name']);
            $filename = "$formatted_name-$date";
        } else {
            // When exporting multiple forms, we compute an additionnal checksum
            // to make sure two different exports with the same number of forms
            // have a different file name.
            $ids = array_map(fn (Form $form) => $form->getID(), $forms);
            $checksum = crc32(json_encode($ids));

            $nb = count($forms);
            $filename = "export-of-$nb-forms-$date-$checksum";
        }

        return $filename . ".json";
    }

    private function exportFormToSpec(Form $form, int $form_export_id): FormContentSpecification
    {
        // TODO: questions, ...
        $form_spec = $this->exportBasicFormProperties($form, $form_export_id);
        $form_spec = $this->exportSections($form, $form_spec);
        $form_spec = $this->exportComments($form, $form_spec);
        $form_spec = $this->exportAccesControlPolicies($form, $form_spec);

        return $form_spec;
    }

    private function importFormFromSpec(
        FormContentSpecification $form_spec,
        DatabaseMapper $mapper,
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
        DatabaseMapper $mapper,
    ): Form {
        // TODO: questions, ...
        $form = $this->importBasicFormProperties($form_spec, $mapper);
        $form = $this->importSections($form, $form_spec);
        $form = $this->importComments($form, $form_spec);
        $form = $this->importAccessControlPolicices($form, $form_spec, $mapper);

        return $form;
    }

    private function extractDataRequirementsFromSerializedJsonConfig(
        array $fkeys_handlers,
        array $serialized_data,
    ): array {
        $requirements = [];
        foreach ($fkeys_handlers as $fkey_handler) {
            array_push(
                $requirements,
                ...$fkey_handler->getDataRequirements($serialized_data)
            );
        }

        return $requirements;
    }

    private function replaceForeignKeysByNameInSerializedJsonConfig(
        array $fkeys_handlers,
        array $serialized_data,
    ): array {
        foreach ($fkeys_handlers as $fkey_handler) {
            $serialized_data = $fkey_handler->replaceForeignKeysByNames($serialized_data);
        }

        return $serialized_data;
    }

    private function replaceNamesByForeignKeysInSerializedJsonConfig(
        array $fkeys_handlers,
        array $serialized_data,
        DatabaseMapper $mapper,
    ): array {
        foreach ($fkeys_handlers as $fkey_handler) {
            $serialized_data = $fkey_handler->replaceNamesByForeignKeys($serialized_data, $mapper);
        }

        return $serialized_data;
    }

    private function exportBasicFormProperties(
        Form $form,
        int $form_export_id
    ): FormContentSpecification {
        $spec               = new FormContentSpecification();
        $spec->id           = $form_export_id;
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
        DatabaseMapper $mapper,
    ): Form {
        // Get ids from mapper
        $entities_id = $mapper->getItemId(Entity::class, $spec->entity_name);

        $form = new Form();
        $id = $form->add([
            'name'                  => $spec->name,
            'header'                => $spec->header,
            'entities_id'           => $entities_id,
            'is_recursive'          => $spec->is_recursive,
            '_do_not_init_sections' => true,
        ]);
        if (!$form->getFromDB($id)) {
            throw new RuntimeException("Failed to create form");
        }

        return $form;
    }

    private function exportSections(
        Form $form,
        FormContentSpecification $form_spec,
    ): FormContentSpecification {
        foreach ($form->getSections() as $section) {
            $section_spec = new SectionContentSpecification();
            $section_spec->name = $section->fields['name'];
            $section_spec->rank = $section->fields['rank'];
            $section_spec->description = $section->fields['description'] ?? "";

            $form_spec->sections[] = $section_spec;
        }

        return $form_spec;
    }

    private function importSections(
        Form $form,
        FormContentSpecification $form_spec,
    ): Form {
        /** @var SectionContentSpecification $section_spec */
        foreach ($form_spec->sections as $section_spec) {
            $section = new Section();
            $id = $section->add([
                'name'        => $section_spec->name,
                'description' => $section_spec->description,
                'rank'        => $section_spec->rank,
                Form::getForeignKeyField() => $form->fields['id'],
            ]);

            if (!$id) {
                throw new RuntimeException("Failed to create section");
            }
        };

        // Reload to clear lazy loaded data
        $form->getFromDB($form->getId());
        return $form;
    }

    private function exportComments(
        Form $form,
        FormContentSpecification $form_spec,
    ): FormContentSpecification {
        foreach ($form->getComments() as $comment) {
            $comment_spec = new CommentContentSpecification();
            $comment_spec->name = $comment->fields['name'];
            $comment_spec->rank = $comment->fields['rank'];
            $comment_spec->description = $comment->fields['description'];
            $comment_spec->section_rank = $form->getSections()[$comment->fields['forms_sections_id']]->fields['rank'];

            $form_spec->comments[] = $comment_spec;
        }

        return $form_spec;
    }

    private function importComments(
        Form $form,
        FormContentSpecification $form_spec,
    ): Form {
        /** @var CommentContentSpecification $comment_spec */
        foreach ($form_spec->comments as $comment_spec) {
            // Retrieve section from their rank
            $section = current(array_filter(
                $form->getSections(),
                fn (Section $section) => $section->fields['rank'] === $comment_spec->section_rank
            ));

            $comment = new Comment();
            $id = $comment->add([
                'name'               => $comment_spec->name,
                'description'        => $comment_spec->description,
                'rank'               => $comment_spec->rank,
                'forms_sections_id'  => $section->fields['id'],
            ]);

            if (!$id) {
                throw new RuntimeException("Failed to create comment");
            }
        }

        // Reload to clear lazy loaded data
        $form->getFromDB($form->getId());
        return $form;
    }

    private function exportAccesControlPolicies(
        Form $form,
        FormContentSpecification $form_spec,
    ): FormContentSpecification {
        foreach ($form->getAccessControls() as $policy) {
            // Compute strategy and config
            $strategy = $policy->getStrategy()::class;
            $config = $policy->getConfig();

            // Read simple fields
            $policy_spec = new AccesControlPolicyContentSpecification();
            $policy_spec->strategy = $strategy;
            $policy_spec->is_active = $policy->fields['is_active'];

            // Serialize config
            $serialized_config = $config->jsonSerialize();
            if ($config instanceof ConfigWithForeignKeysInterface) {
                $requirements = $this->extractDataRequirementsFromSerializedJsonConfig(
                    $config::listForeignKeysHandlers(),
                    $serialized_config
                );
                array_push($form_spec->data_requirements, ...$requirements);

                $serialized_config = $this->replaceForeignKeysByNameInSerializedJsonConfig(
                    $config::listForeignKeysHandlers(),
                    $serialized_config,
                );
            }
            $policy_spec->config_data = $serialized_config;

            // Add to form spec
            $form_spec->policies[] = $policy_spec;
        }

        return $form_spec;
    }

    private function importAccessControlPolicices(
        Form $form,
        FormContentSpecification $spec,
        DatabaseMapper $mapper,
    ): Form {
        foreach ($spec->policies as $policy_spec) {
            $policy = new FormAccessControl();

            // Load strategy
            $strategy_class = $policy_spec->strategy;
            if (!$policy->isValidStrategy($strategy_class)) {
                throw new InvalidArgumentException();
            }
            $strategy = new $strategy_class();

            $config_class = $strategy->getConfigClass();
            $serialized_config = $policy_spec->config_data;
            if (is_a($config_class, ConfigWithForeignKeysInterface::class, true)) {
                $serialized_config = $this->replaceNamesByForeignKeysInSerializedJsonConfig(
                    $config_class::listForeignKeysHandlers(),
                    $serialized_config,
                    $mapper
                );
            }
            $config = $config_class::jsonDeserialize($serialized_config);

            // Insert data
            $id = $policy->add([
                'strategy'  => $strategy_class,
                'is_active' => $policy_spec->is_active,
                '_config'   => $config,
                Form::getForeignKeyField() => $form->getID(),
            ]);

            if (!$id) {
                throw new RuntimeException("Failed to create access control");
            }
        }

        // Reload form to clear lazy loaded data
        $form->getFromDB($form->getID());
        return $form;
    }
}
