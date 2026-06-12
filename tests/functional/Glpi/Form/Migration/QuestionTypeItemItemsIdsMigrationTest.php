<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace tests\units\Glpi\Form\Migration;

use Computer;
use Glpi\Form\Form;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypeItem;
use Glpi\Form\QuestionType\QuestionTypeItemDropdown;
use Glpi\Form\QuestionType\QuestionTypeItemDropdownExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeItemExtraDataConfig;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use ITILCategory;

/**
 * Tests for the data migration that converts the legacy `items_id` (scalar)
 * key to `items_ids` (array) in QuestionTypeItem stored JSON.
 *
 * Three DB columns are covered:
 *   - glpi_forms_questions.default_value
 *   - glpi_forms_answerssets.answers  (raw_answer payload)
 *   - glpi_forms_questions.conditions / validation_conditions  (condition value)
 */
final class QuestionTypeItemItemsIdsMigrationTest extends DbTestCase
{
    use FormTesterTrait;

    private string $migrationFile = GLPI_ROOT . '/install/migrations/update_11.0.x_to_12.0.0/form_question_item_multiple_items.php';

    private function runMigration(): void
    {
        global $DB;
        require $this->migrationFile;
    }

    public function testMigratesLegacyDefaultValue(): void
    {
        global $DB;

        $this->login();

        $computer = $this->createItem(Computer::class, [
            'name'        => 'TestPC',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        // Create a proper form structure and retrieve the question id
        $builder = new FormBuilder();
        $builder->addQuestion(
            name: 'Pick an asset',
            type: QuestionTypeItem::class,
            extra_data: json_encode(
                (new QuestionTypeItemExtraDataConfig(itemtype: Computer::class))->jsonSerialize()
            ),
        );
        $form     = $this->createForm($builder);
        $question = $this->getQuestion($form, 'Pick an asset');

        // Rewrite default_value to legacy format (simulates pre-migration DB state)
        $DB->update(
            'glpi_forms_questions',
            ['default_value' => json_encode(['items_id' => $computer->getID()])],
            ['id' => $question->getID()]
        );

        $this->runMigration();

        // Reload raw data
        $row = $DB->request([
            'FROM'  => 'glpi_forms_questions',
            'WHERE' => ['id' => $question->getID()],
        ])->current();

        $default_value = json_decode($row['default_value'], true);
        $this->assertArrayNotHasKey('items_id', $default_value, 'Legacy items_id key must be removed');
        $this->assertArrayHasKey('items_ids', $default_value, 'New items_ids key must be present');
        $this->assertSame([$computer->getID()], $default_value['items_ids']);
    }

    public function testDefaultValueMigrationIsIdempotent(): void
    {
        global $DB;

        $this->login();

        $computer = $this->createItem(Computer::class, [
            'name'        => 'TestPC-Idempotent',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        $builder = new FormBuilder();
        $builder->addQuestion(
            name: 'Pick an asset',
            type: QuestionTypeItem::class,
            extra_data: json_encode(
                (new QuestionTypeItemExtraDataConfig(itemtype: Computer::class))->jsonSerialize()
            ),
        );
        $form     = $this->createForm($builder);
        $question = $this->getQuestion($form, 'Pick an asset');

        $DB->update(
            'glpi_forms_questions',
            ['default_value' => json_encode(['items_id' => $computer->getID()])],
            ['id' => $question->getID()]
        );

        // Run twice
        $this->runMigration();
        $this->runMigration();

        $row = $DB->request([
            'FROM'  => 'glpi_forms_questions',
            'WHERE' => ['id' => $question->getID()],
        ])->current();

        $default_value = json_decode($row['default_value'], true);
        $this->assertSame([$computer->getID()], $default_value['items_ids']);
    }

    public function testAlreadyMigratedDefaultValueIsUntouched(): void
    {
        global $DB;

        $this->login();

        $computer = $this->createItem(Computer::class, [
            'name'        => 'TestPC-AlreadyMigrated',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        $builder = new FormBuilder();
        $builder->addQuestion(
            name: 'Pick an asset',
            type: QuestionTypeItem::class,
            extra_data: json_encode(
                (new QuestionTypeItemExtraDataConfig(itemtype: Computer::class))->jsonSerialize()
            ),
        );
        $form     = $this->createForm($builder);
        $question = $this->getQuestion($form, 'Pick an asset');

        // Already in new format
        $new_format = json_encode(['items_ids' => [$computer->getID()]]);
        $DB->update(
            'glpi_forms_questions',
            ['default_value' => $new_format],
            ['id' => $question->getID()]
        );

        $this->runMigration();

        $row = $DB->request([
            'FROM'  => 'glpi_forms_questions',
            'WHERE' => ['id' => $question->getID()],
        ])->current();

        $this->assertSame($new_format, $row['default_value']);
    }

    public function testMigratesLegacyAnswerPayload(): void
    {
        global $DB;

        $this->login();

        $computer = $this->createItem(Computer::class, [
            'name'        => 'TestPC-Answer',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        $builder = new FormBuilder();
        $builder->addQuestion(
            name: 'Pick an asset',
            type: QuestionTypeItem::class,
            extra_data: json_encode(
                (new QuestionTypeItemExtraDataConfig(itemtype: Computer::class))->jsonSerialize()
            ),
        );
        $form = $this->createForm($builder);

        // Submit with new format first to get a valid AnswersSet row
        $answers_set = $this->sendFormAndGetAnswerSet($form, [
            'Pick an asset' => ['itemtype' => Computer::class, 'items_ids' => $computer->getID()],
        ]);

        // Rewrite the raw_answer to legacy format inside the JSON column
        $raw_answers = json_decode(
            $DB->request(['FROM' => 'glpi_forms_answerssets', 'WHERE' => ['id' => $answers_set->getID()]])->current()['answers'],
            true
        );
        foreach ($raw_answers as &$answer) {
            if ($answer['raw_question_type'] === QuestionTypeItem::class) {
                $answer['raw_answer'] = [
                    'itemtype' => Computer::class,
                    'items_id' => $computer->getID(),
                ];
            }
        }
        unset($answer);

        $DB->update(
            'glpi_forms_answerssets',
            ['answers' => json_encode($raw_answers)],
            ['id' => $answers_set->getID()]
        );

        $this->runMigration();

        $updated_row = $DB->request([
            'FROM'  => 'glpi_forms_answerssets',
            'WHERE' => ['id' => $answers_set->getID()],
        ])->current();

        $updated_answers = json_decode($updated_row['answers'], true);
        foreach ($updated_answers as $answer) {
            if ($answer['raw_question_type'] === QuestionTypeItem::class) {
                $this->assertArrayNotHasKey('items_id', $answer['raw_answer'], 'Legacy items_id must be removed');
                $this->assertArrayHasKey('items_ids', $answer['raw_answer'], 'New items_ids must be present');
                $this->assertSame([$computer->getID()], $answer['raw_answer']['items_ids']);
            }
        }
    }

    public function testAnswerMigrationIsIdempotent(): void
    {
        global $DB;

        $this->login();

        $computer = $this->createItem(Computer::class, [
            'name'        => 'TestPC-AnswerIdem',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        $builder = new FormBuilder();
        $builder->addQuestion(
            name: 'Pick an asset',
            type: QuestionTypeItem::class,
            extra_data: json_encode(
                (new QuestionTypeItemExtraDataConfig(itemtype: Computer::class))->jsonSerialize()
            ),
        );
        $form = $this->createForm($builder);

        $answers_set = $this->sendFormAndGetAnswerSet($form, [
            'Pick an asset' => ['itemtype' => Computer::class, 'items_ids' => $computer->getID()],
        ]);

        // Force legacy format
        $raw_answers = json_decode(
            $DB->request(['FROM' => 'glpi_forms_answerssets', 'WHERE' => ['id' => $answers_set->getID()]])->current()['answers'],
            true
        );
        foreach ($raw_answers as &$answer) {
            if ($answer['raw_question_type'] === QuestionTypeItem::class) {
                $answer['raw_answer'] = ['itemtype' => Computer::class, 'items_id' => $computer->getID()];
            }
        }
        unset($answer);
        $DB->update('glpi_forms_answerssets', ['answers' => json_encode($raw_answers)], ['id' => $answers_set->getID()]);

        // Run twice
        $this->runMigration();
        $this->runMigration();

        $updated_answers = json_decode(
            $DB->request(['FROM' => 'glpi_forms_answerssets', 'WHERE' => ['id' => $answers_set->getID()]])->current()['answers'],
            true
        );
        foreach ($updated_answers as $answer) {
            if ($answer['raw_question_type'] === QuestionTypeItem::class) {
                $this->assertSame([$computer->getID()], $answer['raw_answer']['items_ids']);
            }
        }
    }

    public function testMigratesLegacyConditionValueOnQuestion(): void
    {
        global $DB;

        $this->login();

        $computer = $this->createItem(Computer::class, [
            'name'        => 'TestPC-Cond',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        $builder = new FormBuilder();
        $builder->addQuestion(
            name: 'Pick an asset',
            type: QuestionTypeItem::class,
            extra_data: json_encode(
                (new QuestionTypeItemExtraDataConfig(itemtype: Computer::class))->jsonSerialize()
            ),
        );
        $form     = $this->createForm($builder);
        $question = $this->getQuestion($form, 'Pick an asset');

        // Inject a legacy-format condition value into the question's conditions
        $legacy_conditions = json_encode([
            [
                'item_uuid'      => $question->fields['uuid'],
                'item_type'      => 'Glpi\\Form\\Condition\\Type\\Question',
                'value_operator' => 'equals',
                'logic_operator' => 'and',
                'value'          => [
                    'itemtype' => Computer::class,
                    'items_id' => $computer->getID(),
                ],
            ],
        ]);
        $DB->update(
            'glpi_forms_questions',
            ['conditions' => $legacy_conditions],
            ['id' => $question->getID()]
        );

        $this->runMigration();

        $row = $DB->request([
            'FROM'  => 'glpi_forms_questions',
            'WHERE' => ['id' => $question->getID()],
        ])->current();

        $conditions = json_decode($row['conditions'], true);
        $this->assertCount(1, $conditions);
        $value = $conditions[0]['value'];
        $this->assertArrayNotHasKey('items_id', $value, 'Legacy items_id must be removed from condition value');
        $this->assertArrayHasKey('items_ids', $value, 'New items_ids must be present in condition value');
        $this->assertSame([$computer->getID()], $value['items_ids']);
    }

    public function testMigratesLegacyDefaultValueForDropdown(): void
    {
        global $DB;

        $this->login();

        $category = $this->createItem(ITILCategory::class, [
            'name'        => 'TestCategory',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        $builder = new FormBuilder();
        $builder->addQuestion(
            name: 'Pick a category',
            type: QuestionTypeItemDropdown::class,
            extra_data: json_encode(
                (new QuestionTypeItemDropdownExtraDataConfig(itemtype: ITILCategory::class))->jsonSerialize()
            ),
        );
        $form     = $this->createForm($builder);
        $question = $this->getQuestion($form, 'Pick a category');

        $DB->update(
            'glpi_forms_questions',
            ['default_value' => json_encode(['items_id' => $category->getID()])],
            ['id' => $question->getID()]
        );

        $this->runMigration();

        $row           = $DB->request(['FROM' => 'glpi_forms_questions', 'WHERE' => ['id' => $question->getID()]])->current();
        $default_value = json_decode($row['default_value'], true);
        $this->assertArrayNotHasKey('items_id', $default_value);
        $this->assertSame([$category->getID()], $default_value['items_ids']);
    }

    public function testMigratesLegacyAnswerPayloadForDropdown(): void
    {
        global $DB;

        $this->login();

        $category = $this->createItem(ITILCategory::class, [
            'name'        => 'TestCategory-Answer',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        $builder = new FormBuilder();
        $builder->addQuestion(
            name: 'Pick a category',
            type: QuestionTypeItemDropdown::class,
            extra_data: json_encode(
                (new QuestionTypeItemDropdownExtraDataConfig(itemtype: ITILCategory::class))->jsonSerialize()
            ),
        );
        $form = $this->createForm($builder);

        $answers_set = $this->sendFormAndGetAnswerSet($form, [
            'Pick a category' => ['itemtype' => ITILCategory::class, 'items_ids' => $category->getID()],
        ]);

        // Force legacy format in DB
        $raw_answers = json_decode(
            $DB->request(['FROM' => 'glpi_forms_answerssets', 'WHERE' => ['id' => $answers_set->getID()]])->current()['answers'],
            true
        );
        foreach ($raw_answers as &$answer) {
            if ($answer['raw_question_type'] === QuestionTypeItemDropdown::class) {
                $answer['raw_answer'] = ['itemtype' => ITILCategory::class, 'items_id' => $category->getID()];
            }
        }
        unset($answer);
        $DB->update('glpi_forms_answerssets', ['answers' => json_encode($raw_answers)], ['id' => $answers_set->getID()]);

        $this->runMigration();

        $updated_answers = json_decode(
            $DB->request(['FROM' => 'glpi_forms_answerssets', 'WHERE' => ['id' => $answers_set->getID()]])->current()['answers'],
            true
        );
        foreach ($updated_answers as $answer) {
            if ($answer['raw_question_type'] === QuestionTypeItemDropdown::class) {
                $this->assertArrayNotHasKey('items_id', $answer['raw_answer']);
                $this->assertSame([$category->getID()], $answer['raw_answer']['items_ids']);
            }
        }
    }

    public function testMigratesLegacyConditionValueOnForm(): void
    {
        global $DB;

        $this->login();

        $computer = $this->createItem(Computer::class, [
            'name'        => 'TestPC-Cond-Form',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        $builder = new FormBuilder();
        $builder->addQuestion(
            name: 'Pick an asset',
            type: QuestionTypeItem::class,
            extra_data: json_encode(
                (new QuestionTypeItemExtraDataConfig(itemtype: Computer::class))->jsonSerialize()
            ),
        );
        $form     = $this->createForm($builder);
        $question = $this->getQuestion($form, 'Pick an asset');

        // Inject a legacy-format condition value into the form submit button conditions
        $DB->update(
            'glpi_forms_forms',
            ['submit_button_conditions' => $this->getLegacyConditions($question, $computer)],
            ['id' => $form->getID()]
        );

        $this->runMigration();

        $row = $DB->request([
            'FROM'  => 'glpi_forms_forms',
            'WHERE' => ['id' => $form->getID()],
        ])->current();

        $this->assertConditionValueMigrated($row['submit_button_conditions'], $computer->getID());
    }

    public function testMigratesLegacyConditionValueOnSection(): void
    {
        global $DB;

        $this->login();

        $computer = $this->createItem(Computer::class, [
            'name'        => 'TestPC-Cond-Section',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        $builder = new FormBuilder();
        $builder->addQuestion(
            name: 'Pick an asset',
            type: QuestionTypeItem::class,
            extra_data: json_encode(
                (new QuestionTypeItemExtraDataConfig(itemtype: Computer::class))->jsonSerialize()
            ),
        );
        $form     = $this->createForm($builder);
        $question = $this->getQuestion($form, 'Pick an asset');

        $section = current($form->getSections());

        // Inject a legacy-format condition value into the section conditions
        $DB->update(
            'glpi_forms_sections',
            ['conditions' => $this->getLegacyConditions($question, $computer)],
            ['id' => $section->getID()]
        );

        $this->runMigration();

        $row = $DB->request([
            'FROM'  => 'glpi_forms_sections',
            'WHERE' => ['id' => $section->getID()],
        ])->current();

        $this->assertConditionValueMigrated($row['conditions'], $computer->getID());
    }

    public function testMigratesLegacyConditionValueOnComment(): void
    {
        global $DB;

        $this->login();

        $computer = $this->createItem(Computer::class, [
            'name'        => 'TestPC-Cond-Comment',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        $builder = new FormBuilder();
        $builder->addQuestion(
            name: 'Pick an asset',
            type: QuestionTypeItem::class,
            extra_data: json_encode(
                (new QuestionTypeItemExtraDataConfig(itemtype: Computer::class))->jsonSerialize()
            ),
        );
        $builder->addComment(name: 'A comment');
        $form     = $this->createForm($builder);
        $question = $this->getQuestion($form, 'Pick an asset');

        $comment = current($form->getFormComments());

        // Inject a legacy-format condition value into the comment conditions
        $DB->update(
            'glpi_forms_comments',
            ['conditions' => $this->getLegacyConditions($question, $computer)],
            ['id' => $comment->getID()]
        );

        $this->runMigration();

        $row = $DB->request([
            'FROM'  => 'glpi_forms_comments',
            'WHERE' => ['id' => $comment->getID()],
        ])->current();

        $this->assertConditionValueMigrated($row['conditions'], $computer->getID());
    }

    public function testMigratesLegacyConditionValueOnDestination(): void
    {
        global $DB;

        $this->login();

        $computer = $this->createItem(Computer::class, [
            'name'        => 'TestPC-Cond-Destination',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        $builder = new FormBuilder();
        $builder->addQuestion(
            name: 'Pick an asset',
            type: QuestionTypeItem::class,
            extra_data: json_encode(
                (new QuestionTypeItemExtraDataConfig(itemtype: Computer::class))->jsonSerialize()
            ),
        );
        $form     = $this->createForm($builder);
        $question = $this->getQuestion($form, 'Pick an asset');

        // A default destination is created automatically with the form
        $destination = current($form->getDestinations());

        // Inject a legacy-format condition value into the destination conditions
        $DB->update(
            'glpi_forms_destinations_formdestinations',
            ['conditions' => $this->getLegacyConditions($question, $computer)],
            ['id' => $destination->getID()]
        );

        $this->runMigration();

        $row = $DB->request([
            'FROM'  => 'glpi_forms_destinations_formdestinations',
            'WHERE' => ['id' => $destination->getID()],
        ])->current();

        $this->assertConditionValueMigrated($row['conditions'], $computer->getID());
    }

    /**
     * Build a legacy-format ({"itemtype":X,"items_id":Y}) conditions JSON string
     * referencing the given item question.
     */
    private function getLegacyConditions(Question $question, Computer $computer): string
    {
        return json_encode([
            [
                'item_uuid'      => $question->fields['uuid'],
                'item_type'      => 'Glpi\\Form\\Condition\\Type\\Question',
                'value_operator' => 'equals',
                'logic_operator' => 'and',
                'value'          => [
                    'itemtype' => Computer::class,
                    'items_id' => $computer->getID(),
                ],
            ],
        ]);
    }

    /**
     * Assert that the first condition's value has been migrated from the legacy
     * `items_id` scalar to the new `items_ids` array format.
     */
    private function assertConditionValueMigrated(string $raw_conditions, int $expected_id): void
    {
        $conditions = json_decode($raw_conditions, true);
        $this->assertCount(1, $conditions);
        $value = $conditions[0]['value'];
        $this->assertArrayNotHasKey('items_id', $value, 'Legacy items_id must be removed from condition value');
        $this->assertArrayHasKey('items_ids', $value, 'New items_ids must be present in condition value');
        $this->assertSame([$expected_id], $value['items_ids']);
    }

    private function getQuestion(Form $form, string $name): Question
    {
        foreach ($form->getQuestions() as $question) {
            if ($question->fields['name'] === $name) {
                return $question;
            }
        }
        $this->fail("Question '$name' not found in form");
    }
}
