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

namespace Glpi\Form\Export\Serializer;

use CommonDBTM;
use Entity;
use Glpi\Form\AccessControl\FormAccessControl;
use Glpi\Form\Category;
use Glpi\Form\Comment;
use Glpi\Form\Condition\ConditionableInterface;
use Glpi\Form\Condition\ConditionData;
use Glpi\Form\Condition\Type;
use Glpi\Form\Destination\FormDestination;
use Glpi\Form\Destination\FormDestinationInterface;
use Glpi\Form\Export\Context\DatabaseMapper;
use Glpi\Form\Export\Result\ExportResult;
use Glpi\Form\Export\Result\ImportError;
use Glpi\Form\Export\Result\ImportResult;
use Glpi\Form\Export\Result\ImportResultIssues;
use Glpi\Form\Export\Result\ImportResultPreview;
use Glpi\Form\Export\Specification\AccesControlPolicyContentSpecification;
use Glpi\Form\Export\Specification\CommentContentSpecification;
use Glpi\Form\Export\Specification\ConditionDataSpecification;
use Glpi\Form\Export\Specification\CustomIllustrationContentSpecification;
use Glpi\Form\Export\Specification\DataRequirementSpecification;
use Glpi\Form\Export\Specification\DestinationContentSpecification;
use Glpi\Form\Export\Specification\ExportContentSpecification;
use Glpi\Form\Export\Specification\FormContentSpecification;
use Glpi\Form\Export\Specification\QuestionContentSpecification;
use Glpi\Form\Export\Specification\SectionContentSpecification;
use Glpi\Form\Export\Specification\TranslationContentSpecification;
use Glpi\Form\Form;
use Glpi\Form\FormTranslation;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypeInterface;
use Glpi\Form\Section;
use Glpi\UI\IllustrationManager;
use InvalidArgumentException;
use RuntimeException;
use Session;
use Throwable;
use Toolbox;

use function Safe\base64_decode;
use function Safe\file_get_contents;
use function Safe\file_put_contents;
use function Safe\json_decode;
use function Safe\json_encode;
use function Safe\md5_file;

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

        foreach ($forms as $form) {
            // Add forms to the main export spec
            $form_spec = $this->exportFormToSpec($form);
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

            $form_id   = $form_spec->id;
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
            $formatted_name = Toolbox::slugify($form->fields['name']);
            $filename = "$formatted_name-$date";
        } else {
            // When exporting multiple forms, we compute an additionnal checksum
            // to make sure two different exports with the same number of forms
            // have a different file name.
            $ids = array_map(fn(Form $form) => $form->getID(), $forms);
            $checksum = crc32(json_encode($ids));

            $nb = count($forms);
            $filename = "export-of-$nb-forms-$date-$checksum";
        }

        return $filename . ".json";
    }

    private function exportFormToSpec(Form $form): FormContentSpecification
    {
        $form_spec = $this->exportBasicFormProperties($form);
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
        global $DB;

        $DB->beginTransaction();
        try {
            $forms = $this->doImportFormFormSpecs($form_spec, $mapper);
            $DB->commit();
        } catch (Throwable $e) {
            $DB->rollback();
            throw $e;
        }
        return $forms;
    }

    private function doImportFormFormSpecs(
        FormContentSpecification $form_spec,
        DatabaseMapper $mapper,
    ): Form {
        $form = $this->importBasicFormProperties($form_spec, $mapper);
        $form = $this->importSections($form, $form_spec, $mapper);
        $form = $this->importComments($form, $form_spec, $mapper);
        $form = $this->importQuestions($form, $form_spec, $mapper);
        $form = $this->importAccessControlPolicices($form, $form_spec, $mapper);
        $form = $this->importDestinations($form, $form_spec, $mapper);
        $form = $this->importDestinationsConfig($form, $form_spec, $mapper);
        $form = $this->importConditions($form, $form_spec, $mapper);
        $form = $this->importTranslations($form, $form_spec, $mapper);

        return $form;
    }

    private function exportBasicFormProperties(
        Form $form,
    ): FormContentSpecification {
        $illustration = $this->prepareIllustrationDataForExport(
            $form->fields['illustration'],
        );
        $spec                                    = new FormContentSpecification();
        $spec->id                                = $form->fields['id'];
        $spec->uuid                              = $form->fields['uuid'];
        $spec->name                              = $form->fields['name'];
        $spec->header                            = $form->fields['header'];
        $spec->description                       = $form->fields['description'];
        $spec->illustration                      = $illustration;
        $spec->is_recursive                      = $form->fields['is_recursive'];
        $spec->is_active                         = $form->fields['is_active'];
        $spec->submit_button_visibility_strategy = $form->fields['submit_button_visibility_strategy'];
        $spec->submit_button_conditions          = $this->prepareConditionDataForExport($form);

        // Export entity
        $entity = Entity::getById($form->fields['entities_id']);
        $requirement = DataRequirementSpecification::fromItem($entity);
        $spec->addDataRequirement($requirement);
        $spec->entity_name = $requirement->name;

        // Export category
        $category = new Category();
        if ($category->getFromDB($form->fields[Category::getForeignKeyField()])) {
            $requirement = DataRequirementSpecification::fromItem($category);
            $spec->addDataRequirement($requirement);
            $spec->category_name = $requirement->name;
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
        $illustration = $this->prepareIllustrationDataForImport(
            $spec->illustration,
        );
        $id = $form->add([
            '_from_import'                      => true,
            'name'                              => $spec->name,
            'header'                            => $spec->header ?? null,
            'description'                       => $spec->description ?? null,
            'illustration'                      => $illustration,
            'forms_categories_id'               => $categories_id ?? 0,
            'entities_id'                       => $entities_id,
            'is_recursive'                      => $spec->is_recursive,
            'is_active'                         => $spec->is_active,
            'submit_button_visibility_strategy' => $spec->submit_button_visibility_strategy,
            '_init_sections'                    => false,
        ]);
        if (!$form->getFromDB($id)) {
            throw new RuntimeException("Failed to create form");
        }

        // The translations system will have a reference to the current form,
        // add it to the mapper for convenience.
        $mapper->addMappedItem(
            Form::class,
            $spec->id,
            $id
        );
        $mapper->addMappedItem(
            Form::class,
            $spec->uuid,
            $id
        );

        return $form;
    }

    private function exportSections(
        Form $form,
        FormContentSpecification $form_spec,
    ): FormContentSpecification {
        foreach ($form->getSections() as $section) {
            $spec                      = new SectionContentSpecification();
            $spec->id                  = $section->fields['id'];
            $spec->uuid                = $section->fields['uuid'];
            $spec->name                = $section->fields['name'];
            $spec->rank                = $section->fields['rank'];
            $spec->description         = $section->fields['description'];
            $spec->visibility_strategy = $section->fields['visibility_strategy'];
            $spec->conditions          = $this->prepareConditionDataForExport($section);
            $form_spec->sections[] = $spec;
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
                'name'                     => $section_spec->name,
                'description'              => $section_spec->description,
                'rank'                     => $section_spec->rank,
                'visibility_strategy'      => $section_spec->visibility_strategy,
                Form::getForeignKeyField() => $form->fields['id'],
            ]);

            if (!$id) {
                throw new RuntimeException("Failed to create section");
            }

            // Sections can be required for other items, so we need to map them.
            // Some items use the ID while others the UUID (conditions).
            $mapper->addMappedItem(
                Section::class,
                $section_spec->id,
                $id
            );
            $mapper->addMappedItem(
                Section::class,
                $section_spec->uuid,
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
            $spec                      = new CommentContentSpecification();
            $spec->id                  = $comment->fields['id'];
            $spec->uuid                = $comment->fields['uuid'];
            $spec->name                = $comment->fields['name'];
            $spec->vertical_rank       = $comment->fields['vertical_rank'];
            $spec->horizontal_rank     = $comment->fields['horizontal_rank'];
            $spec->description         = $comment->fields['description'];
            $spec->section_id          = $comment->fields['forms_sections_id'];
            $spec->visibility_strategy = $comment->fields['visibility_strategy'];
            $spec->conditions          = $this->prepareConditionDataForExport($comment);
            $form_spec->comments[]     = $spec;
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
            $comment = new Comment();
            $id = $comment->add([
                'name'                => $comment_spec->name,
                'description'         => $comment_spec->description,
                'vertical_rank'       => $comment_spec->vertical_rank,
                'horizontal_rank'     => $comment_spec->horizontal_rank,
                'visibility_strategy' => $comment_spec->visibility_strategy,
                'forms_sections_id'   => $mapper->getItemId(
                    Section::class,
                    $comment_spec->section_id,
                ),
            ]);

            if (!$id) {
                throw new RuntimeException("Failed to create comment");
            }

            // Comments can be required for other items, so we need to map them.
            // Some items use the ID while others the UUID (conditions).
            $mapper->addMappedItem(
                Comment::class,
                $comment_spec->id,
                $id
            );
            $mapper->addMappedItem(
                Comment::class,
                $comment_spec->uuid,
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
            $spec                        = new QuestionContentSpecification();
            $spec->id                    = $question->fields['id'];
            $spec->uuid                  = $question->fields['uuid'];
            $spec->name                  = $question->fields['name'];
            $spec->type                  = $question->fields['type'];
            $spec->is_mandatory          = $question->fields['is_mandatory'];
            $spec->vertical_rank         = $question->fields['vertical_rank'];
            $spec->horizontal_rank       = $question->fields['horizontal_rank'];
            $spec->description           = $question->fields['description'];
            $spec->section_id            = $question->fields['forms_sections_id'];
            $spec->visibility_strategy   = $question->fields['visibility_strategy'];
            $spec->validation_strategy   = $question->fields['validation_strategy'];
            $spec->conditions            = $this->prepareConditionDataForExport($question);
            $spec->validation_conditions = $this->prepareValidationConditionDataForExport($question);

            // Handle dynamic fields, we can't know the values that need to be mapped
            // here so we need to let the question object handle it itself.
            $dynamic_data = $question->exportDynamicData();
            $form_spec->addRequirementsFromDynamicData($dynamic_data);
            $spec->default_value = $dynamic_data->getFieldData('default_value');
            $spec->extra_data    = $dynamic_data->getFieldData('extra_data');

            // Insert into main spec
            $form_spec->questions[] = $spec;
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
            $input = [
                'name'                => $question_spec->name,
                'type'                => $question_spec->type,
                'is_mandatory'        => $question_spec->is_mandatory,
                'vertical_rank'       => $question_spec->vertical_rank,
                'horizontal_rank'     => $question_spec->horizontal_rank,
                'description'         => $question_spec->description,
                'default_value'       => $question_spec->default_value,
                'extra_data'          => $question_spec->extra_data,
                'visibility_strategy' => $question_spec->visibility_strategy,
                'validation_strategy' => $question_spec->validation_strategy,
                'forms_sections_id'   => $mapper->getItemId(
                    Section::class,
                    $question_spec->section_id,
                ),
            ];

            // Validate type
            $question_type = $question_spec->type;
            if (!is_a($question_type, QuestionTypeInterface::class, true)) {
                $message = "Invalid type: {$question_type}";
                throw new RuntimeException($message);
            }
            $question_type = new $question_type();

            // Handle dynamic fields, we can't know the values that need to be
            // mapped here so we need to let the question object handle the data.
            $input = Question::prepareDynamicImportData(
                $question_type,
                $input,
                $mapper
            );

            // Add question
            $question = new Question();
            $id = $question->add($input);
            if (!$id) {
                $message = "Failed to create question: " . json_encode($input);
                throw new RuntimeException($message);
            }

            // Questions can be required for other items, so we need to map them.
            // Some items use the ID while others the UUID (conditions).
            $mapper->addMappedItem(
                Question::class,
                $question_spec->id,
                $id
            );
            $mapper->addMappedItem(
                Question::class,
                $question_spec->uuid,
                $id
            );
        }

        // Reload form to clear lazy loaded data
        $form->getFromDB($form->getID());
        return $form;
    }

    /** @return ConditionDataSpecification[] */
    private function prepareConditionDataForExport(
        ConditionableInterface $item
    ): array {
        $specs = [];
        foreach ($item->getConfiguredConditionsData() as $data) {
            $spec                 = new ConditionDataSpecification();
            $spec->item_uuid      = $data->getItemUuid();
            $spec->item_type      = $data->getItemType()->value;
            $spec->value_operator = $data->getValueOperator()->value;
            $spec->logic_operator = $data->getLogicOperator()->value;
            $spec->value          = $data->getValue();

            $specs[] = $spec;
        }

        return $specs;
    }

    /** @return ConditionDataSpecification[] */
    private function prepareValidationConditionDataForExport(
        Question $question
    ): array {
        $specs = [];
        foreach ($question->getConfiguredValidationConditionsData() as $data) {
            $spec                 = new ConditionDataSpecification();
            $spec->item_uuid      = $data->getItemUuid();
            $spec->item_type      = $data->getItemType()->value;
            $spec->value_operator = $data->getValueOperator()->value;
            $spec->logic_operator = $data->getLogicOperator()->value;
            $spec->value          = $data->getValue();

            $specs[] = $spec;
        }

        return $specs;
    }

    /**
     * @param ConditionDataSpecification[] $conditions_specs
     * @return ConditionData[]
     */
    private function prepareConditionsForImport(
        array $conditions_specs,
        DatabaseMapper $mapper,
    ): array {
        $data = [];
        foreach ($conditions_specs as $condition_spec) {
            $type     = Type::from($condition_spec->item_type);
            $itemtype = $type->getItemtype();
            $id       = $mapper->getItemId($itemtype, $condition_spec->item_uuid);
            $item     = getItemForItemtype($itemtype);
            if (!$item || !$item->getFromDB($id)) {
                $message = "Failed to find item for condition: $itemtype::$id";
                throw new RuntimeException($message);
            }

            $data[] = new ConditionData(
                item_type     : $type->value,
                item_uuid     : $item->fields['uuid'],
                value_operator: $condition_spec->value_operator,
                value         : $condition_spec->value,
                logic_operator: $condition_spec->logic_operator
            );
        }

        return $data;
    }

    /**
     * @param ConditionDataSpecification[] $conditions_specs
     * @return ConditionData[]
     */
    private function prepareValidationConditionsForImport(
        array $conditions_specs,
        DatabaseMapper $mapper,
    ): array {
        $data = [];
        foreach ($conditions_specs as $condition_spec) {
            $type     = Type::from($condition_spec->item_type);
            $itemtype = $type->getItemtype();
            $id       = $mapper->getItemId($itemtype, $condition_spec->item_uuid);
            $item     = getItemForItemtype($itemtype);
            if (!$item || !$item->getFromDB($id)) {
                $message = "Failed to find item for condition: $itemtype::$id";
                throw new RuntimeException($message);
            }

            $data[] = new ConditionData(
                item_type     : $type->value,
                item_uuid     : $item->fields['uuid'],
                value_operator: $condition_spec->value_operator,
                value         : $condition_spec->value,
                logic_operator: $condition_spec->logic_operator
            );
        }

        return $data;
    }

    private function importConditions(
        Form $form,
        FormContentSpecification $form_spec,
        DatabaseMapper $mapper,
    ): Form {
        $this->importCondition(
            id: $mapper->getItemId(Form::class, $form_spec->id),
            itemtype: new Form(),
            conditions: $this->prepareConditionsForImport(
                $form_spec->submit_button_conditions,
                $mapper,
            )
        );

        foreach ($form_spec->sections as $section_spec) {
            $this->importCondition(
                id: $mapper->getItemId(Section::class, $section_spec->id),
                itemtype: new Section(),
                conditions: $this->prepareConditionsForImport(
                    $section_spec->conditions,
                    $mapper,
                )
            );
        }
        foreach ($form_spec->questions as $question_spec) {
            $this->importCondition(
                id: $mapper->getItemId(Question::class, $question_spec->id),
                itemtype: new Question(),
                conditions: $this->prepareConditionsForImport(
                    $question_spec->conditions,
                    $mapper,
                )
            );

            $this->importValidationCondition(
                id: $mapper->getItemId(Question::class, $question_spec->id),
                itemtype: new Question(),
                conditions: $this->prepareValidationConditionsForImport(
                    $question_spec->validation_conditions,
                    $mapper,
                )
            );
        }
        foreach ($form_spec->comments as $comment_spec) {
            $this->importCondition(
                id: $mapper->getItemId(Comment::class, $comment_spec->id),
                itemtype: new Comment(),
                conditions: $this->prepareConditionsForImport(
                    $comment_spec->conditions,
                    $mapper,
                )
            );
        }
        foreach ($form_spec->destinations as $destination_spec) {
            $this->importCondition(
                id: $mapper->getItemId(FormDestination::class, $destination_spec->id),
                itemtype: new FormDestination(),
                conditions: $this->prepareConditionsForImport(
                    $destination_spec->conditions,
                    $mapper,
                )
            );
        }

        // Reload form to clear lazy loaded data
        $form->getFromDB($form->getID());
        return $form;
    }

    private function importCondition(
        CommonDBTM $itemtype,
        int $id,
        array $conditions,
    ): void {
        $update_input = [
            'id'           => $id,
            '_conditions'  => $conditions,
        ];

        if (!$itemtype->update($update_input)) {
            $message = "Failed to import condition: " . json_encode($update_input);
            throw new RuntimeException($message);
        }
    }

    private function importValidationCondition(
        CommonDBTM $itemtype,
        int $id,
        array $conditions,
    ): void {
        $update_input = [
            'id'                     => $id,
            '_validation_conditions' => $conditions,
        ];

        if (!$itemtype->update($update_input)) {
            $message = "Failed to import validation condition: " . json_encode($update_input);
            throw new RuntimeException($message);
        }
    }

    private function exportAccesControlPolicies(
        Form $form,
        FormContentSpecification $form_spec,
    ): FormContentSpecification {
        foreach ($form->getAccessControls() as $policy) {
            // Compute simple fields
            $spec = new AccesControlPolicyContentSpecification();
            $spec->strategy = $policy->getStrategy()::class;
            $spec->is_active = $policy->fields['is_active'];

            // Handle dynamic config, we can't know the values that need to be
            // mapped here so we need to let the policy object handle it itself.
            $dynamic_data = $policy->exportDynamicData();
            $form_spec->addRequirementsFromDynamicData($dynamic_data);
            $spec->config = $dynamic_data->getFieldData('config');

            // Add to form spec
            $form_spec->policies[] = $spec;
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
            $strategy = $policy->createStrategy($strategy_class);

            // Prepare basic input
            $input = [
                'strategy'  => $strategy_class,
                'is_active' => $policy_spec->is_active,
                '_config'   => $policy_spec->config,
                Form::getForeignKeyField() => $form->getID(),
            ];

            // Handle dynamic config, we can't know the values that need to be
            // mapped here so we need to let the policy object handle it itself.
            $input = FormAccessControl::prepareDynamicImportData(
                $strategy,
                $input,
                $mapper
            );

            // Insert data
            if (!$policy->add($input)) {
                $message = "Failed to create access control: " . json_encode($input);
                throw new RuntimeException($message);
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
            // Compute simple fields
            $spec                    = new DestinationContentSpecification();
            $spec->id                = $destination->fields['id'];
            $spec->itemtype          = $destination->fields['itemtype'];
            $spec->name              = $destination->fields['name'];
            $spec->creation_strategy = $destination->fields['creation_strategy'];
            $spec->conditions        = $this->prepareConditionDataForExport($destination);

            // Handle dynamic config, we can't know the values that need to be
            // mapped here so we need to let the destination object handle it
            // itself.
            $dynamic_data = $destination->exportDynamicData();
            $form_spec->addRequirementsFromDynamicData($dynamic_data);
            $spec->config = $dynamic_data->getFieldData('config');

            $form_spec->destinations[] = $spec;
        }

        return $form_spec;
    }

    private function importDestinations(
        Form $form,
        FormContentSpecification $form_spec,
        DatabaseMapper $mapper,
    ): Form {
        foreach ($form_spec->destinations as $destination_spec) {
            $destination = new FormDestination();

            // Prepare basic input
            $input = [
                '_from_import'             => true,
                'itemtype'                 => $destination_spec->itemtype,
                'name'                     => $destination_spec->name,
                'creation_strategy'        => $destination_spec->creation_strategy,
                Form::getForeignKeyField() => $form->getID(),
            ];

            // Validate destination type
            $destination_type = $destination_spec->itemtype;
            if (!(is_a($destination_type, FormDestinationInterface::class, true))) {
                $message = "Invalid type: {$destination_spec->itemtype}";
                throw new RuntimeException($message);
            }
            $destination_type = new $destination_type();

            $id = $destination->add($input);
            if (!$id) {
                $message = "Failed to create destination: " . json_encode($input);
                throw new RuntimeException($message);
            }

            // Destinations can be required for other items, so we need to map them.
            $mapper->addMappedItem(
                FormDestination::class,
                $destination_spec->id,
                $id
            );
        }

        // Reload form to clear lazy loaded data
        $form->getFromDB($form->getID());
        return $form;
    }

    /**
     * Import the configuration of each destination.
     * This is done in a separate step after the initial creation
     * to ensure that all destinations are created before we try to
     * import their configuration.
     *
     * Some configuration may reference other destinations
     *
     * @param Form $form
     * @param FormContentSpecification $form_spec
     * @param DatabaseMapper $mapper
     * @return Form The updated form
     * @throws RuntimeException if a destination cannot be found or updated
     */
    private function importDestinationsConfig(
        Form $form,
        FormContentSpecification $form_spec,
        DatabaseMapper $mapper,
    ): Form {
        foreach ($form_spec->destinations as $destination_spec) {
            $destination = new FormDestination();
            $config = $destination_spec->config;
            $id = $mapper->getItemId(FormDestination::class, $destination_spec->id);
            if (!$destination->getFromDB($id)) {
                $message = "Failed to find destination for fields import: " . json_encode($destination_spec);
                throw new RuntimeException($message);
            }

            $input = FormDestination::prepareDynamicImportData(
                $destination->getConcreteDestinationItem(),
                ['_from_import' => true, 'id' => $id, 'config' => $config],
                $mapper
            );

            if (!$destination->update($input)) {
                $message = "Failed to update destination for fields import: " . json_encode($input);
                throw new RuntimeException($message);
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
            $spec               = new TranslationContentSpecification();
            $spec->itemtype     = $translation->fields['itemtype'];
            $spec->items_id     = $translation->fields['items_id'];
            $spec->key          = $translation->fields['key'];
            $spec->language     = $translation->fields['language'];
            $spec->translations = json_decode(
                $translation->fields['translations'],
                associative: true
            );

            $form_spec->translations[] = $spec;
        }

        return $form_spec;
    }

    private function importTranslations(
        Form $form,
        FormContentSpecification $form_spec,
        DatabaseMapper $mapper,
    ): Form {
        foreach ($form_spec->translations as $translation_spec) {
            $translation = new FormTranslation();
            $input = [
                'itemtype'     => $translation_spec->itemtype,
                'items_id'     => $mapper->getItemId(
                    $translation_spec->itemtype,
                    $translation_spec->items_id,
                ),
                'key'          => $translation_spec->key,
                'language'     => $translation_spec->language,
                'translations' => $translation_spec->translations,
            ];
            if (!$translation->add($input)) {
                $message = "Failed to create translation: " . json_encode($input);
                throw new RuntimeException($message);
            }
        }

        // Reload form to clear lazy loaded data
        $form->getFromDB($form->getID());
        return $form;
    }

    private function prepareIllustrationDataForExport(
        string $illustration,
    ): string|CustomIllustrationContentSpecification {
        // Stop here if this illustration is native
        $prefix = IllustrationManager::CUSTOM_ILLUSTRATION_PREFIX;
        $manager = new IllustrationManager();
        if (!str_starts_with($illustration, $prefix)) {
            return $illustration;
        }

        // Add base64 data and md5sum
        $specification = new CustomIllustrationContentSpecification();
        $key = substr($illustration, strlen($prefix));
        $file = $manager->getCustomIllustrationFile($key);
        $specification->key = $key;
        $specification->data = base64_encode(file_get_contents($file));
        $specification->checksum = md5_file($file);

        return $specification;
    }

    private function prepareIllustrationDataForImport(
        string|CustomIllustrationContentSpecification $illustration,
    ): string {
        // Stop here if this illustration is native
        if (is_string($illustration)) {
            return $illustration;
        }

        $prefix = IllustrationManager::CUSTOM_ILLUSTRATION_PREFIX;

        // Check if file already exist
        $manager = new IllustrationManager();
        $file = $manager->getCustomIllustrationFile($illustration->key);
        if ($file !== null) {
            // File exist, validate checksum
            if (md5_file($file) === $illustration->checksum) {
                return $prefix . $illustration->key;
            } else {
                $message = "Checksum don't match for exisiting file: $illustration->key";
                throw new RuntimeException($message);
            }
        }

        // Save file
        $data = base64_decode($illustration->data);
        $tmp_path = GLPI_TMP_DIR . "/" . $illustration->key;
        file_put_contents($tmp_path, $data);
        $manager->saveCustomIllustration($illustration->key, $tmp_path);
        $file = $manager->getCustomIllustrationFile($illustration->key);
        if (md5_file($file) !== $illustration->checksum) {
            $message = "Checksum don't match for new file: $illustration->key";
            throw new RuntimeException($message);
        }

        return $prefix . $illustration->key;
    }
}
