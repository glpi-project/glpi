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

namespace Glpi\Form\Clone;

use Glpi\DBAL\PrepareForCloneInterface;
use Glpi\Form\AccessControl\FormAccessControl;
use Glpi\Form\Comment;
use Glpi\Form\Condition\Type;
use Glpi\Form\Destination\AbstractConfigField;
use Glpi\Form\Destination\FormDestination;
use Glpi\Form\Destination\HasFieldWithDestinationId;
use Glpi\Form\Destination\HasFieldWithQuestionId;
use Glpi\Form\Form;
use Glpi\Form\Question;
use Glpi\Form\Section;
use Glpi\Toolbox\SingletonTrait;
use Ramsey\Uuid\Uuid;
use ReflectionClass;
use RuntimeException;

use function Safe\json_decode;
use function Safe\json_encode;

/**
 * Helper service that contains utilities methods that are required to be used
 * in order to clone a form properly.
 *
 * Cloning a form come with the following issues to solve:
 * - Forms, section, questions and comments have an UUID field that need to be
 *  unique, thus new values must be generated when cloning a form.
 *  - Section's UUID are referenced in the questions and comments table as the
 *  `forms_sections_uuid` field and will need to be updated with the new generated
 * uuid.
 * - The `conditions` field (section, questions, comments, destinations), the
 *  `validation_conditions` field (questions) and the `submit_button_conditions`
 *  field (forms) are json fields that contains uuid references to others sections,
 *  questions and comments.
 *  They will need to be parsed and updated.
 * - The "direct access" policy define a token that should be be unique for
 *  safety reason, a must value must thus be generated.
 * - Form destinations configuration (json fields) will contain references to
 *  other questions and destinations ids, they must be updated with the ids for
 *  the cloned questions.
 * - Some automatic creation process (first section, default destination and
 *  default security policies) must be disabled when cloning.
 */
final class FormCloneHelper
{
    use SingletonTrait;

    /** @var array<string, string> $forms_uuid_map */
    private array $forms_uuid_map = [];

    /** @var array<string, string> $sections_uuid_map */
    private array $sections_uuid_map = [];

    /** @var array<string, string> $questions_uuid_map */
    private array $questions_uuid_map = [];

    /** @var array<string, string> $comments_uuid_map */
    private array $comments_uuid_map = [];

    public function prepareFormInputForClone(array $input): array
    {
        // Generate a new UUID
        $input['uuid'] = $this->generateFormUuid($input['uuid']);

        // Reset counters
        $input['usage_count'] = 0;

        // Disable default data creation
        $input['_init_sections'] = false;
        $input['_init_destinations'] = false;
        $input['_init_access_policies'] = false;

        return $input;
    }

    public function postFormClone(Form $form): void
    {
        // We must update references to items uuid in the various conditions
        // fields.
        // This can only be done once the full form data has been cloned, which
        // is the case when we reach this method.
        $this->updateFormConditions($form);

        foreach ($form->getSections() as $section) {
            $this->updateSectionConditions($section);

            foreach ($section->getQuestions() as $question) {
                $this->updateQuestionConditions($question);
            }

            foreach ($section->getFormComments() as $comment) {
                $this->updateCommentConditions($comment);
            }
        }

        foreach ($form->getDestinations() as $destination) {
            $this->updateDestinationConditions($destination);
        }
    }

    public function prepareSectionInputForClone(array $input): array
    {
        // Generate a new UUID
        $input['uuid'] = $this->generateSectionUuid($input['uuid']);

        return $input;
    }

    public function prepareQuestionInputForClone(array $input): array
    {
        // Generate a new UUID
        $input['uuid'] = $this->generateQuestionUuid($input['uuid']);

        // Remove outdated parent section uuid reference, it will be corrected
        // automatically when empty.
        unset($input['forms_sections_uuid']);

        return $input;
    }

    public function prepareCommentInputForClone(array $input): array
    {
        // Generate a new UUID
        $input['uuid'] = $this->generateCommentUuid($input['uuid']);

        // Remove outdated parent section uuid reference, it will be corrected
        // automatically when empty.
        unset($input['forms_sections_uuid']);

        return $input;
    }

    public function prepareAccessControlInputForClone(array $input): array
    {
        // Allow config classes to interact with the input before it is cloned.
        $strategy_class = $input['strategy'];
        $strategy = (new FormAccessControl())->createStrategy($strategy_class);
        if ($strategy->getConfig() instanceof PrepareForCloneInterface) {
            $config = json_decode($input['config'], associative: true);
            $config = $strategy->getConfig()->prepareInputForClone($config);
            $input['config'] = json_encode($config);
        }

        return $input;
    }

    public function prepareDestinationInputForClone(array $input): array
    {
        // Allow destination to update their config before it is cloned
        $destination_type = FormDestination::getConcreteDestinationItemForItemtype(
            $input['itemtype']
        );
        if ($destination_type instanceof PrepareForCloneInterface) {
            $config = json_decode($input['config'], associative: true);
            $config = $destination_type->prepareInputForClone($config);
            $input['config'] = json_encode($config);
        }

        return $input;
    }

    public function prepareCommonItilDestinationFieldInputForClone(
        AbstractConfigField $field_type,
        array $input
    ): array {
        $input = $this->updateQuestionIdReferencesInCommonItilDestinationFieldInput(
            $field_type,
            $input,
        );
        $input = $this->updateDestinationIdReferencesInCommonItilDestinationFieldInput(
            $field_type,
            $input,
        );

        return $input;
    }

    public function getMappedFormUuid(string $old_uuid): string
    {
        return $this->forms_uuid_map[$old_uuid];
    }

    public function getMappedSectionUuid(string $old_uuid): string
    {
        return $this->sections_uuid_map[$old_uuid];
    }

    public function getMappedQuestionUuid(string $old_uuid): string
    {
        return $this->questions_uuid_map[$old_uuid];
    }

    public function getMappedCommentUuid(string $old_uuid): string
    {
        return $this->comments_uuid_map[$old_uuid];
    }

    public function getMappedFormId(int $id): int
    {
        $original_uuid = Form::getById($id)->fields['uuid'];
        $clone_uuid = $this->getMappedFormUuid($original_uuid);
        return Form::getByUuid($clone_uuid)->getID();
    }

    public function getMappedQuestionId(int $id): int
    {
        $original_uuid = Question::getById($id)->fields['uuid'];
        $clone_uuid = $this->getMappedQuestionUuid($original_uuid);
        return Question::getByUuid($clone_uuid)->getID();
    }

    public function getMappedDestinationId(int $id): int
    {
        // We don't have any uuid map of destinations so we must compare the form
        // data manually to find the correct index.
        $original_form_id = FormDestination::getById($id)
            ->fields[Form::getForeignKeyField()]
        ;
        $original_form = Form::getById($original_form_id);
        $cloned_form = Form::getById($this->getMappedFormId($original_form_id));

        $original_destinations = array_values(
            $original_form->getDestinations()
        );
        $index = array_find_key(
            $original_destinations,
            fn(FormDestination $fd) => $fd->getId() == $id
        );

        $cloned_destinations = array_values(
            $cloned_form->getDestinations()
        );

        return $cloned_destinations[$index]->getId();
    }

    private function generateFormUuid(string $old_uuid): string
    {
        $new_uuid = Uuid::uuid4();
        $this->forms_uuid_map[$old_uuid] = (string) $new_uuid;
        return $new_uuid;
    }

    private function generateSectionUuid(string $old_uuid): string
    {
        $new_uuid = Uuid::uuid4();
        $this->sections_uuid_map[$old_uuid] = (string) $new_uuid;
        return $new_uuid;
    }

    private function generateQuestionUuid(string $old_uuid): string
    {
        $new_uuid = Uuid::uuid4();
        $this->questions_uuid_map[$old_uuid] = (string) $new_uuid;
        return $new_uuid;
    }

    private function generateCommentUuid(string $old_uuid): string
    {
        $new_uuid = Uuid::uuid4();
        $this->comments_uuid_map[$old_uuid] = (string) $new_uuid;
        return $new_uuid;
    }

    private function getMappedUuidForConditionItem(
        Type $type,
        string $uuid,
    ): string {
        return match ($type) {
            Type::SECTION  => $this->getMappedSectionUuid($uuid),
            Type::QUESTION => $this->getMappedQuestionUuid($uuid),
            Type::COMMENT  => $this->getMappedCommentUuid($uuid),
        };
    }

    private function updateFormConditions(Form $form): void
    {
        $form_input = ['id' => $form->getID()];

        $submit_button_conditions = $form->fields['submit_button_conditions'];
        $json = $this->updateUuidsInConditionDataJson($submit_button_conditions);
        $form_input['submit_button_conditions'] = $json;

        if (!$form->update($form_input)) {
            throw new RuntimeException();
        }
    }

    private function updateSectionConditions(Section $section): void
    {
        $section_input = ['id' => $section->getID()];

        $visibility_conditions = $section->fields['conditions'];
        $json = $this->updateUuidsInConditionDataJson($visibility_conditions);
        $section_input['conditions'] = $json;

        if (!$section->update($section_input)) {
            throw new RuntimeException();
        }
    }

    private function updateQuestionConditions(Question $question): void
    {
        $question_input = ['id' => $question->getID()];

        $visibility_conditions = $question->fields['conditions'];
        $json = $this->updateUuidsInConditionDataJson($visibility_conditions);
        $question_input['conditions'] = $json;

        $validity_conditions = $question->fields['validation_conditions'];
        $json = $this->updateUuidsInConditionDataJson($validity_conditions);
        $question_input['validation_conditions'] = $json;

        if (!$question->update($question_input)) {
            throw new RuntimeException();
        }
    }

    private function updateCommentConditions(Comment $comment): void
    {
        $comment_input = ['id' => $comment->getID()];

        $visibility_conditions = $comment->fields['conditions'];
        $json = $this->updateUuidsInConditionDataJson($visibility_conditions);
        $comment_input['conditions'] = $json;

        if (!$comment->update($comment_input)) {
            throw new RuntimeException();
        }
    }

    private function updateDestinationConditions(FormDestination $destination): void
    {
        $destination_input = ['id' => $destination->getID()];

        $creation_conditions = $destination->fields['conditions'];
        $json = $this->updateUuidsInConditionDataJson($creation_conditions);
        $destination_input['conditions'] = $json;

        if (!$destination->update($destination_input)) {
            throw new RuntimeException();
        }
    }

    private function updateUuidsInConditionDataJson(string $json): string
    {
        $data = json_decode($json, associative: true);
        foreach ($data as $i => $condition_data) {
            // Ignore invalid condition, they might be empty
            if (
                !isset($condition_data["item_uuid"])
                || !isset($condition_data["item"])
            ) {
                continue;
            }

            // Read values
            $uuid = $condition_data["item_uuid"];
            $raw_type = $condition_data["item_type"];

            // Get correct uuid
            $type = Type::from($raw_type);
            $new_uuid = $this->getMappedUuidForConditionItem($type, $uuid);

            // Apply updated values
            $condition_data["item_uuid"] = $new_uuid;
            $condition_data["item"] = "$raw_type-$new_uuid";
            $data[$i] = $condition_data;
        }

        return json_encode($data);
    }

    private function handleHasFieldWithQuestionIdAttribute(
        HasFieldWithQuestionId $attribute,
        int|array $value
    ): int|array {
        // The value may be a question id or an array of questions ids
        if ($attribute->isArray()) {
            // Value is an array, iterate on each id
            foreach ($value as $i => $questions_id) {
                $value[$i] = $this->getMappedQuestionId($questions_id);
            }
        } else {
            // Value is an id, we can get the mapped value directly
            $value = $this->getMappedQuestionId($value);
        }

        return $value;
    }

    private function handleHasFieldWithDestinationIdAttribute(
        HasFieldWithDestinationId $attribute,
        int|array $value
    ): int|array {
        // The value may be a destination id or an array of destinations ids
        if ($attribute->isArray()) {
            // Value is an array, iterate on each id
            foreach ($value as $i => $destination_id) {
                $value[$i] = $this->getMappedDestinationId($destination_id);
            }
        } else {
            // Value is an id, we can get the mapped value directly
            $value = $this->getMappedDestinationId($value);
        }

        return $value;
    }

    public function updateQuestionIdReferencesInCommonItilDestinationFieldInput(
        AbstractConfigField $field_type,
        array $input
    ): array {
        // Watch for a specific data attribute that indicate we need to
        // update one or multiples questions ids.
        $reflection = new ReflectionClass($field_type->getConfigClass());
        $attributes = $reflection->getAttributes(HasFieldWithQuestionId::class);

        foreach ($attributes as $attribute) {
            /** @var HasFieldWithQuestionId attribute */
            $attribute = $attribute->newInstance();

            if ($attribute->isArrayOfStrategies()) {
                // Get input value for this field
                $strategies_field = $attribute->getListOfStrategiesField();
                $value = $input[$strategies_field] ?? null;
                if ($value == null) {
                    continue;
                }

                // Some fields like LinkedObjectField wrap the configuration
                // into another array layer, we must iterate on it.
                foreach ($value as $i => $strategy_input) {
                    $strategy_input = $this->handleHasFieldWithQuestionIdAttribute(
                        $attribute,
                        $strategy_input[$attribute->getConfigKey()],
                    );
                    $input[$strategies_field][$i][$attribute->getConfigKey()] = $strategy_input;
                }
            } else {
                // Get input value for this field
                $value = $input[$attribute->getConfigKey()] ?? null;
                if ($value == null) {
                    continue;
                }

                // Standard field, we can handle the value directly
                $field_input = $this->handleHasFieldWithQuestionIdAttribute(
                    $attribute,
                    $value,
                );

                $input[$attribute->getConfigKey()] = $field_input;
            }
        }

        return $input;
    }

    public function updateDestinationIdReferencesInCommonItilDestinationFieldInput(
        AbstractConfigField $field_type,
        array $input
    ): array {
        // Watch for a specific data attribute that indicate we need to
        // update one or multiples destinations ids.
        $reflection = new ReflectionClass($field_type->getConfigClass());
        $attributes = $reflection->getAttributes(HasFieldWithDestinationId::class);

        foreach ($attributes as $attribute) {
            /** @var HasFieldWithDestinationId attribute */
            $attribute = $attribute->newInstance();

            if ($attribute->isArrayOfStrategies()) {
                // Get input value for this field
                $strategies_field = $attribute->getListOfStrategiesField();
                $value = $input[$strategies_field] ?? null;
                if ($value == null) {
                    continue;
                }

                // Some fields like LinkedObjectField wrap the configuration
                // into another array layer, we must iterate on it.
                foreach ($value as $i => $strategy_input) {
                    $strategy_input = $this->handleHasFieldWithDestinationIdAttribute(
                        $attribute,
                        $strategy_input[$attribute->getConfigKey()],
                    );
                    $input[$strategies_field][$i][$attribute->getConfigKey()] = $strategy_input;
                }
            } else {
                // Get input value for this field
                $value = $input[$attribute->getConfigKey()] ?? null;
                if ($value == null) {
                    continue;
                }

                // Standard field, we can handle the value directly
                $field_input = $this->handleHasFieldWithDestinationIdAttribute(
                    $attribute,
                    $value,
                );

                $input[$attribute->getConfigKey()] = $field_input;
            }
        }

        return $input;
    }
}
