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

namespace Glpi\Form\Export\Serializer;

use Entity;
use Glpi\Form\AccessControl\FormAccessControl;
use Glpi\Form\Category;
use Glpi\Form\Comment;
use Glpi\Form\Condition\ConditionData;
use Glpi\Form\Condition\FormData;
use Glpi\Form\Destination\FormDestination;
use Glpi\Form\Export\Context\DatabaseMapper;
use Glpi\Form\Export\Context\ConfigWithForeignKeysInterface;
use Glpi\Form\Export\Context\ForeignKey\CommentForeignKeyHandler;
use Glpi\Form\Export\Context\ForeignKey\QuestionForeignKeyHandler;
use Glpi\Form\Export\Context\ForeignKey\SectionForeignKeyHandler;
use Glpi\Form\Export\Result\ExportResult;
use Glpi\Form\Export\Result\ImportError;
use Glpi\Form\Export\Result\ImportResult;
use Glpi\Form\Export\Result\ImportResultIssues;
use Glpi\Form\Export\Result\ImportResultPreview;
use Glpi\Form\Export\Specification\AccesControlPolicyContentSpecification;
use Glpi\Form\Export\Specification\CommentContentSpecification;
use Glpi\Form\Export\Specification\ConditionDataSpecification;
use Glpi\Form\Export\Specification\DestinationContentSpecification;
use Glpi\Form\Export\Specification\ExportContentSpecification;
use Glpi\Form\Export\Specification\FormContentSpecification;
use Glpi\Form\Export\Specification\QuestionContentSpecification;
use Glpi\Form\Export\Specification\SectionContentSpecification;
use Glpi\Form\Export\Specification\TranslationContentSpecification;
use Glpi\Form\Form;
use Glpi\Form\FormTranslation;
use Glpi\Form\Question;
use Glpi\Form\Section;
use Glpi\ItemTranslation\ItemTranslation;
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
        $form_spec = $this->exportQuestions($form, $form_spec);
        $form_spec = $this->exportAccesControlPolicies($form, $form_spec);
        $form_spec = $this->exportDestinations($form, $form_spec);
        $form_spec = $this->exportTranslations($form, $form_spec);

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
        $form = $this->importSections($form, $form_spec, $mapper);
        $form = $this->importComments($form, $form_spec, $mapper);
        $form = $this->importQuestions($form, $form_spec, $mapper);
        $form = $this->importAccessControlPolicices($form, $form_spec, $mapper);
        $form = $this->importDestinations($form, $form_spec, $mapper);
        $form = $this->importQuestionConditions($form, $form_spec, $mapper);
        $form = $this->importTranslations($form, $form_spec, $mapper);

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
        $spec->header       = $form->fields['header'];
        $spec->description  = $form->fields['description'];
        $spec->illustration = $form->fields['illustration'];
        $spec->is_recursive = $form->fields['is_recursive'];
        $spec->is_active    = $form->fields['is_active'];

        $entity = Entity::getById($form->fields['entities_id']);
        $spec->entity_name = $entity->fields['name'];
        $spec->addDataRequirement(Entity::class, $entity->fields['name']);

        $category = new Category();
        if ($category->getFromDB($form->fields[Category::getForeignKeyField()])) {
            $spec->category_name = $category->fields['name'];
            $spec->addDataRequirement(Category::class, $category->fields['name']);
        }

        return $spec;
    }

    private function importBasicFormProperties(
        FormContentSpecification $spec,
        DatabaseMapper $mapper,
    ): Form {
        // Get ids from mapper
        $entities_id   = $mapper->getItemId(Entity::class, $spec->entity_name);
        if (!empty($spec->category_name)) {
            $categories_id = $mapper->getItemId(Category::class, $spec->category_name);
        }

        $form = new Form();
        $id = $form->add([
            '_from_import'          => true,
            'name'                  => $spec->name,
            'header'                => $spec->header ?? null,
            'description'           => $spec->description ?? null,
            'illustration'          => $spec->illustration,
            'forms_categories_id'   => $categories_id ?? 0,
            'entities_id'           => $entities_id,
            'is_recursive'          => $spec->is_recursive,
            'is_active'             => $spec->is_active,
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
            $section_spec->description = $section->fields['description'];

            $form_spec->sections[] = $section_spec;
        }

        return $form_spec;
    }

    private function importSections(
        Form $form,
        FormContentSpecification $form_spec,
        DatabaseMapper $mapper,
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

            // Sections can be required for other items, so we need to map them
            $mapper->addMappedItem(
                Section::class,
                $section->getUniqueIDInForm(),
                $id
            );
        };

        // Reload to clear lazy loaded data
        $form->getFromDB($form->getId());
        return $form;
    }

    private function exportComments(
        Form $form,
        FormContentSpecification $form_spec,
    ): FormContentSpecification {
        foreach ($form->getFormComments() as $comment) {
            $comment_spec = new CommentContentSpecification();
            $comment_spec->name = $comment->fields['name'];
            $comment_spec->vertical_rank = $comment->fields['vertical_rank'];
            $comment_spec->horizontal_rank = $comment->fields['horizontal_rank'];
            $comment_spec->description = $comment->fields['description'];
            $comment_spec->section_rank = $form->getSections()[$comment->fields['forms_sections_id']]->fields['rank'];

            $form_spec->comments[] = $comment_spec;
        }

        return $form_spec;
    }

    private function importComments(
        Form $form,
        FormContentSpecification $form_spec,
        DatabaseMapper $mapper,
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
                'vertical_rank'      => $comment_spec->vertical_rank,
                'horizontal_rank'    => $comment_spec->horizontal_rank,
                'forms_sections_id'  => $section->fields['id'],
            ]);

            if (!$id) {
                throw new RuntimeException("Failed to create comment");
            }

            // Comments can be required for other items, so we need to map them
            $mapper->addMappedItem(
                Comment::class,
                $comment->getUniqueIDInForm(),
                $id
            );
        }

        // Reload to clear lazy loaded data
        $form->getFromDB($form->getId());
        return $form;
    }

    private function exportQuestions(
        Form $form,
        FormContentSpecification $form_spec,
    ): FormContentSpecification {
        foreach ($form->getQuestions() as $question) {
            $question_spec                      = new QuestionContentSpecification();
            $question_spec->name                = $question->fields['name'];
            $question_spec->type                = $question->fields['type'];
            $question_spec->is_mandatory        = $question->fields['is_mandatory'];
            $question_spec->vertical_rank       = $question->fields['vertical_rank'];
            $question_spec->horizontal_rank     = $question->fields['horizontal_rank'];
            $question_spec->description         = $question->fields['description'];
            $question_spec->default_value       = $question->fields['default_value'];
            $question_spec->extra_data          = $question->fields['extra_data'];
            $question_spec->section_rank        = $form->getSections()[$question->fields['forms_sections_id']]->fields['rank'];
            $question_spec->visibility_strategy = $question->fields['visibility_strategy'];
            $question_spec->conditions          = [];

            $question_type = new $question_spec->type();
            if ($question_type->getDefaultValueConfigClass() !== null) {
                $default_value_config = $question_type->getDefaultValueConfig(
                    json_decode($question_spec->default_value ?? "[]", true)
                );
                if ($default_value_config !== null) {
                    $serialized_default_value = $default_value_config->jsonSerialize();
                    if (
                        $default_value_config instanceof ConfigWithForeignKeysInterface
                    ) {
                        $requirements = $this->extractDataRequirementsFromSerializedJsonConfig(
                            $default_value_config::listForeignKeysHandlers($question_spec),
                            $serialized_default_value
                        );
                        array_push($form_spec->data_requirements, ...$requirements);

                        $question_spec->default_value = json_encode(
                            $this->replaceForeignKeysByNameInSerializedJsonConfig(
                                $default_value_config::listForeignKeysHandlers($question_spec),
                                $serialized_default_value
                            )
                        );
                    }
                }
            }

            foreach ($question->getConfiguredConditionsData() as $condition_data) {
                $condition_spec                 = new ConditionDataSpecification();
                $condition_spec->item_uuid      = $condition_data->getItemUuid();
                $condition_spec->item_type      = $condition_data->getItemType()->value;
                $condition_spec->value_operator = $condition_data->getValueOperator()->value;
                $condition_spec->logic_operator = $condition_data->getLogicOperator()->value;
                $condition_spec->value          = $condition_data->getValue();

                $requirements = $this->extractDataRequirementsFromSerializedJsonConfig(
                    $condition_data::listForeignKeysHandlers($condition_spec),
                    $condition_data->jsonSerialize()
                );
                array_push($form_spec->data_requirements, ...$requirements);

                $question_spec->conditions[] = json_encode(
                    $this->replaceForeignKeysByNameInSerializedJsonConfig(
                        $condition_data::listForeignKeysHandlers($condition_spec),
                        $condition_data->jsonSerialize()
                    )
                );
            }

            $form_spec->questions[] = $question_spec;
        }

        return $form_spec;
    }

    private function importQuestions(
        Form $form,
        FormContentSpecification $form_spec,
        DatabaseMapper $mapper,
    ): Form {
        /** @var QuestionContentSpecification $question_spec */
        foreach ($form_spec->questions as $question_spec) {
            // Retrieve section from their rank
            $section = current(array_filter(
                $form->getSections(),
                fn (Section $section) => $section->fields['rank'] === $question_spec->section_rank
            ));

            $question_type = new $question_spec->type();
            if ($question_type->getDefaultValueConfigClass() !== null) {
                $default_value_config = $question_type->getDefaultValueConfig(
                    json_decode($question_spec->default_value ?? "[]", true)
                );
                if ($default_value_config !== null) {
                    $serialized_default_value = json_decode($question_spec->default_value, true);
                    if (
                        $default_value_config instanceof ConfigWithForeignKeysInterface
                    ) {
                        $serialized_default_value = $this->replaceNamesByForeignKeysInSerializedJsonConfig(
                            $default_value_config::listForeignKeysHandlers($question_spec),
                            $serialized_default_value,
                            $mapper
                        );
                    }
                    $question_spec->default_value = json_encode($serialized_default_value);
                }
            }

            $question = new Question();
            $id = $question->add([
                '_from_import'      => true,
                'name'              => $question_spec->name,
                'type'              => $question_spec->type,
                'is_mandatory'      => $question_spec->is_mandatory,
                'vertical_rank'     => $question_spec->vertical_rank,
                'horizontal_rank'   => $question_spec->horizontal_rank,
                'description'       => $question_spec->description,
                'default_value'     => $question_spec->default_value,
                'extra_data'        => $question_spec->extra_data,
                'forms_sections_id' => $section->fields['id'],
            ]);

            if (!$id || $question->getFromDB($id) === false) {
                throw new RuntimeException("Failed to create question");
            }

            // Questions can be required for other items, so we need to map them
            $mapper->addMappedItem(
                Question::class,
                $question->getUniqueIDInForm(),
                $id
            );
        }

        // Reload form to clear lazy loaded data
        $form->getFromDB($form->getID());
        return $form;
    }

    private function importQuestionConditions(
        Form $form,
        FormContentSpecification $form_spec,
        DatabaseMapper $mapper,
    ): Form {
        /** @var QuestionContentSpecification $question_spec */
        foreach ($form_spec->questions as $question_spec) {
            $question = current(array_filter(
                $form->getQuestions(),
                fn (Question $question) => $question->fields['name'] === $question_spec->name
            ));

            $conditions = [];
            foreach ($question_spec->conditions as $raw_condition_data) {
                $raw_condition_data             = json_decode($raw_condition_data, true);
                $condition_spec                 = new ConditionDataSpecification();
                $condition_spec->item_uuid      = $raw_condition_data['item_uuid'];
                $condition_spec->item_type      = $raw_condition_data['item_type'];
                $condition_spec->value_operator = $raw_condition_data['value_operator'];
                $condition_spec->value          = $raw_condition_data['value'];
                $condition_spec->logic_operator = $raw_condition_data['logic_operator'];

                // Replace names by UUID
                $raw_condition_data = $this->replaceNamesByForeignKeysInSerializedJsonConfig(
                    ConditionData::listForeignKeysHandlers($condition_spec),
                    $raw_condition_data,
                    $mapper
                );

                // We need to re-create the condition data object to generate the `item` key
                $condition_data = new ConditionData(
                    $raw_condition_data['item_uuid'],
                    $raw_condition_data['item_type'],
                    $raw_condition_data['value_operator'],
                    $raw_condition_data['value'],
                    $raw_condition_data['logic_operator']
                );
                $conditions[] = $condition_data->jsonSerialize();
            }

            $question->update([
                '_from_import'        => true,
                'id'                  => $question->getID(),
                'visibility_strategy' => $question_spec->visibility_strategy,
                'conditions'          => json_encode($conditions),
            ]);

            if ($question->getFromDB($question->getID()) === false) {
                throw new RuntimeException("Failed to set visibility strategy and conditions for question");
            }
        }

        // Reload form to clear lazy loaded data
        $form->getFromDB($form->getID());
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
                    $config::listForeignKeysHandlers($policy_spec),
                    $serialized_config
                );
                array_push($form_spec->data_requirements, ...$requirements);

                $serialized_config = $this->replaceForeignKeysByNameInSerializedJsonConfig(
                    $config::listForeignKeysHandlers($policy_spec),
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
                    $config_class::listForeignKeysHandlers($policy_spec),
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

    private function exportDestinations(
        Form $form,
        FormContentSpecification $form_spec,
    ): FormContentSpecification {
        foreach ($form->getDestinations() as $destination) {
            $destination_spec               = new DestinationContentSpecification();
            $destination_spec->itemtype     = $destination->fields['itemtype'];
            $destination_spec->name         = $destination->fields['name'];
            $destination_spec->config       = $destination->getConfig();
            $destination_spec->is_mandatory = $destination->fields['is_mandatory'];

            $config = $destination->getConfig();
            foreach ($config as $field_key => $field_config_data) {
                $field = (new $destination->fields['itemtype']())->getConfigurableFieldByKey($field_key);
                if ($field === null) {
                    continue;
                }

                $field_config_class = $field->getConfigClass();
                $field_config = $field_config_class::jsonDeserialize($field_config_data);
                if ($field_config instanceof ConfigWithForeignKeysInterface) {
                    $requirements = $this->extractDataRequirementsFromSerializedJsonConfig(
                        $field_config::listForeignKeysHandlers($destination_spec),
                        $field_config_data
                    );
                    array_push($form_spec->data_requirements, ...$requirements);

                    $destination_spec->config[$field_key] = $this->replaceForeignKeysByNameInSerializedJsonConfig(
                        $field_config::listForeignKeysHandlers($destination_spec),
                        $field_config_data
                    );
                }
            }

            $form_spec->destinations[] = $destination_spec;
        }

        return $form_spec;
    }

    private function importDestinations(
        Form $form,
        FormContentSpecification $form_spec,
        DatabaseMapper $mapper,
    ): Form {
        foreach ($form_spec->destinations as $destination_spec) {
            $config = $destination_spec->config;
            foreach ($config as $field_key => $field_config_data) {
                $field = (new $destination_spec->itemtype())->getConfigurableFieldByKey($field_key);
                if ($field === null) {
                    continue;
                }

                $field_config_class = $field->getConfigClass();
                if (is_a($field_config_class, ConfigWithForeignKeysInterface::class, true)) {
                    $field_config_data = $this->replaceNamesByForeignKeysInSerializedJsonConfig(
                        $field_config_class::listForeignKeysHandlers($destination_spec),
                        $field_config_data,
                        $mapper
                    );
                    $config[$field_key] = $field_config_data;
                }
            }

            $destination = new FormDestination();
            $id = $destination->add([
                '_from_import'             => true,
                'itemtype'                 => $destination_spec->itemtype,
                'name'                     => $destination_spec->name,
                'config'                   => $config,
                'is_mandatory'             => $destination_spec->is_mandatory,
                Form::getForeignKeyField() => $form->getID(),
            ]);

            if (!$id) {
                throw new RuntimeException("Failed to create destination");
            }
        }

        // Reload form to clear lazy loaded data
        $form->getFromDB($form->getID());
        return $form;
    }

    private function exportTranslations(
        Form $form,
        FormContentSpecification $form_spec,
    ): FormContentSpecification {
        foreach (FormTranslation::getTranslationsForForm($form) as $translation) {
            if ($translation->fields['itemtype'] === Question::class) {
                $requirements = $this->extractDataRequirementsFromSerializedJsonConfig(
                    [new QuestionForeignKeyHandler(FormTranslation::$items_id)],
                    $translation->fields
                );
                array_push($form_spec->data_requirements, ...$requirements);

                $translation->fields = $this->replaceForeignKeysByNameInSerializedJsonConfig(
                    [new QuestionForeignKeyHandler(FormTranslation::$items_id)],
                    $translation->fields
                );
            } elseif ($translation->fields['itemtype'] === Comment::class) {
                $requirements = $this->extractDataRequirementsFromSerializedJsonConfig(
                    [new CommentForeignKeyHandler(FormTranslation::$items_id)],
                    $translation->fields
                );
                array_push($form_spec->data_requirements, ...$requirements);

                $translation->fields = $this->replaceForeignKeysByNameInSerializedJsonConfig(
                    [new CommentForeignKeyHandler(FormTranslation::$items_id)],
                    $translation->fields
                );
            } elseif ($translation->fields['itemtype'] === Section::class) {
                $requirements = $this->extractDataRequirementsFromSerializedJsonConfig(
                    [new SectionForeignKeyHandler(FormTranslation::$items_id)],
                    $translation->fields
                );
                array_push($form_spec->data_requirements, ...$requirements);

                $translation->fields = $this->replaceForeignKeysByNameInSerializedJsonConfig(
                    [new SectionForeignKeyHandler(FormTranslation::$items_id)],
                    $translation->fields
                );
            }

            $translation_spec = new TranslationContentSpecification();
            $translation_spec->itemtype = $translation->fields['itemtype'];
            $translation_spec->items_id = $translation->fields['items_id'];
            $translation_spec->key = $translation->fields['key'];
            $translation_spec->language = $translation->fields['language'];
            $translation_spec->translations = json_decode($translation->fields['translations'], true);

            $form_spec->translations[] = $translation_spec;
        }

        return $form_spec;
    }

    private function importTranslations(
        Form $form,
        FormContentSpecification $form_spec,
        DatabaseMapper $mapper,
    ): Form {
        foreach ($form_spec->translations as $translation_spec) {
            if ($translation_spec->itemtype === Form::class) {
                $translation_spec->items_id = (string) $form->getID();
            } elseif ($translation_spec->itemtype === Question::class) {
                $translation_spec->items_id = $this->replaceNamesByForeignKeysInSerializedJsonConfig(
                    [new QuestionForeignKeyHandler(FormTranslation::$items_id)],
                    [FormTranslation::$items_id => $translation_spec->items_id],
                    $mapper
                )[FormTranslation::$items_id];
            } elseif ($translation_spec->itemtype === Comment::class) {
                $translation_spec->items_id = $this->replaceNamesByForeignKeysInSerializedJsonConfig(
                    [new CommentForeignKeyHandler(FormTranslation::$items_id)],
                    [FormTranslation::$items_id => $translation_spec->items_id],
                    $mapper
                )[FormTranslation::$items_id];
            } elseif ($translation_spec->itemtype === Section::class) {
                $translation_spec->items_id = $this->replaceNamesByForeignKeysInSerializedJsonConfig(
                    [new SectionForeignKeyHandler(FormTranslation::$items_id)],
                    [FormTranslation::$items_id => $translation_spec->items_id],
                    $mapper
                )[FormTranslation::$items_id];
            }

            $translation = new FormTranslation();
            $id = $translation->add([
                'itemtype'     => $translation_spec->itemtype,
                'items_id'     => $translation_spec->items_id,
                'key'          => $translation_spec->key,
                'language'     => $translation_spec->language,
                'translations' => $translation_spec->translations,
            ]);

            if (!$id) {
                throw new RuntimeException("Failed to create translation");
            }
        }

        // Reload form to clear lazy loaded data
        $form->getFromDB($form->getID());
        return $form;
    }
}
