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

namespace tests\units\Glpi\Form\Migration;

use Computer;
use DbTestCase;
use Glpi\Form\Category;
use Glpi\Form\Form;
use Glpi\Form\Migration\FormMigration;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypeActorsDefaultValueConfig;
use Glpi\Form\QuestionType\QuestionTypeActorsExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeCheckbox;
use Glpi\Form\QuestionType\QuestionTypeDateTime;
use Glpi\Form\QuestionType\QuestionTypeDateTimeExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeDropdown;
use Glpi\Form\QuestionType\QuestionTypeDropdownExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeEmail;
use Glpi\Form\QuestionType\QuestionTypeItem;
use Glpi\Form\QuestionType\QuestionTypeItemDefaultValueConfig;
use Glpi\Form\QuestionType\QuestionTypeItemDropdown;
use Glpi\Form\QuestionType\QuestionTypeItemExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeLongText;
use Glpi\Form\QuestionType\QuestionTypeNumber;
use Glpi\Form\QuestionType\QuestionTypeRadio;
use Glpi\Form\QuestionType\QuestionTypeRequester;
use Glpi\Form\QuestionType\QuestionTypeRequestType;
use Glpi\Form\QuestionType\QuestionTypeSelectableExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Form\QuestionType\QuestionTypeUrgency;
use Glpi\Form\Section;
use Glpi\Migration\PluginMigrationResult;
use Glpi\Tests\FormTesterTrait;
use Location;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;

final class FormMigrationTest extends DbTestCase
{
    use FormTesterTrait;

    public static function setUpBeforeClass(): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        parent::setUpBeforeClass();

        $queries = $DB->getQueriesFromFile(sprintf('%s/tests/glpi-formcreator-migration-data.sql', GLPI_ROOT));
        foreach ($queries as $query) {
            $DB->doQuery($query);
        }
    }

    public static function tearDownAfterClass(): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        $tables = $DB->listTables('glpi\_plugin\_formcreator\_%');
        foreach ($tables as $table) {
            $DB->dropTable($table['TABLE_NAME']);
        }

        parent::tearDownAfterClass();
    }

    private static function getSectionIDFromFormName($form_name, $section_name)
    {
        $section = new Section();
        $form_id = getItemByTypeName(Form::class, $form_name, true);

        Assert::assertNotFalse($section->getFromDBByCrit([
            Form::getForeignKeyField() => $form_id,
            'name'                     => $section_name,
        ]));

        return $section->getID();
    }

    public static function provideFormMigrationFormCategories(): iterable
    {
        yield 'Root form category' => [
            [
                'name'                => 'Root form category',
                'description'         => '',
                'illustration'        => '',
                'forms_categories_id' => 0,
            ]
        ];

        yield 'My test form category' => [
            [
                'name'                => 'My test form category',
                'description'         => '',
                'illustration'        => '',
                'forms_categories_id' => fn () => getItemByTypeName(Category::class, 'Root form category', true),
            ]
        ];
    }

    #[DataProvider('provideFormMigrationFormCategories')]
    public function testFormMigrationFormCategories($data): void
    {
        /**
         * @var \DBmysql $DB
         * LoggerInterface $PHPLOGGER
         */
        global $DB, $PHPLOGGER;
        $migration = new FormMigration($DB, $PHPLOGGER);
        $this->setPrivateProperty($migration, 'result', new PluginMigrationResult());
        $this->assertTrue($this->callPrivateMethod($migration, 'processMigration'));

        $formCategory = getItemByTypeName(Category::class, $data['name']);
        $this->assertEquals(
            array_map(fn($value) => is_callable($value) ? $value() : $value, $data),
            array_intersect_key(
                $formCategory->fields,
                $data
            )
        );
    }

    public static function provideFormMigrationBasicProperties(): iterable
    {
        yield 'Basic properties' => [
            [
                'name'                => 'Test form migration for basic properties',
                'entities_id'         => 0,
                'is_recursive'        => 0,
                'is_active'           => 1,
                'is_deleted'          => 0,
                'is_draft'            => 0,
                'header'              => '',
                'illustration'        => '',
                'description'         => '',
                'forms_categories_id' => 0,
            ]
        ];

        yield 'Basic properties with form category' => [
            [
                'name'                => 'Test form migration for basic properties with form category',
                'entities_id'         => 0,
                'is_recursive'        => 0,
                'is_active'           => 1,
                'is_deleted'          => 0,
                'is_draft'            => 0,
                'header'              => '',
                'illustration'        => '',
                'description'         => '',
                'forms_categories_id' => fn () => getItemByTypeName(Category::class, 'My test form category', true),
            ]
        ];
    }

    #[DataProvider('provideFormMigrationBasicProperties')]
    public function testFormMigrationBasicProperties($data): void
    {
        /**
         * @var \DBmysql $DB
         * LoggerInterface $PHPLOGGER
         */
        global $DB, $PHPLOGGER;
        $migration = new FormMigration($DB, $PHPLOGGER);
        $this->setPrivateProperty($migration, 'result', new PluginMigrationResult());
        $this->assertTrue($this->callPrivateMethod($migration, 'processMigration'));

        $form = getItemByTypeName(Form::class, $data['name']);
        $this->assertEquals(
            array_map(fn($value) => is_callable($value) ? $value() : $value, $data),
            array_intersect_key(
                $form->fields,
                $data
            )
        );
    }

    public static function provideFormMigrationSections(): iterable
    {
        yield 'First section' => [
            [
                'name'           => 'First section',
                'forms_forms_id' => fn () => getItemByTypeName(Form::class, 'Test form migration for sections', true),
                'description'    => '',
                'rank'           => 0,
            ]
        ];

        yield 'Second section' => [
            [
                'name'           => 'Second section',
                'forms_forms_id' => fn () => getItemByTypeName(Form::class, 'Test form migration for sections', true),
                'description'    => '',
                'rank'           => 1,
            ]
        ];
    }

    #[DataProvider('provideFormMigrationSections')]
    public function testFormMigrationSections($data): void
    {
        /**
         * @var \DBmysql $DB
         * LoggerInterface $PHPLOGGER
         */
        global $DB, $PHPLOGGER;
        $migration = new FormMigration($DB, $PHPLOGGER);
        $this->setPrivateProperty($migration, 'result', new PluginMigrationResult());
        $this->assertTrue($this->callPrivateMethod($migration, 'processMigration'));

        $section = new Section();
        $this->assertNotFalse($section->getFromDBByCrit([
            'name'           => $data['name'],
            'forms_forms_id' => $data['forms_forms_id'](),
        ]));

        $this->assertEquals(
            array_map(fn($value) => is_callable($value) ? $value() : $value, $data),
            array_intersect_key(
                $section->fields,
                $data
            )
        );
    }

    public static function provideFormMigrationQuestions(): iterable
    {
        $section_id = fn () => self::getSectionIDFromFormName('Test form migration for questions', 'Section');

        $default_value = new QuestionTypeActorsDefaultValueConfig([2]);
        $extra_data = new QuestionTypeActorsExtraDataConfig(true);
        yield 'Actor question type' => [
            [
                Section::getForeignKeyField() => $section_id,
                'name'                        => 'Test form migration for questions - Actor',
                'type'                        => QuestionTypeRequester::class,
                'is_mandatory'                => 0,
                'vertical_rank'               => 0,
                'horizontal_rank'             => 2,
                'description'                 => null,
                'default_value'               => json_encode($default_value->jsonSerialize()),
                'extra_data'                  => json_encode($extra_data->jsonSerialize())
            ]
        ];

        $extra_data = new QuestionTypeSelectableExtraDataConfig(array_map(fn ($i) => "Option $i", range(1, 7)));
        yield 'Checkboxes question type' => [
            [
                Section::getForeignKeyField() => $section_id,
                'name'                        => 'Test form migration for questions - Checkboxes',
                'type'                        => QuestionTypeCheckbox::class,
                'is_mandatory'                => 0,
                'vertical_rank'               => 1,
                'horizontal_rank'             => 0,
                'description'                 => null,
                'default_value'               => '1,4',
                'extra_data'                  => json_encode($extra_data->jsonSerialize())
            ]
        ];

        $extra_data = new QuestionTypeDateTimeExtraDataConfig(
            is_default_value_current_time: false,
            is_date_enabled: true,
            is_time_enabled: false,
        );
        yield 'Date question type' => [
            [
                Section::getForeignKeyField() => $section_id,
                'name'                        => 'Test form migration for questions - Date',
                'type'                        => QuestionTypeDateTime::class,
                'is_mandatory'                => 0,
                'vertical_rank'               => 1,
                'horizontal_rank'             => 1,
                'description'                 => null,
                'default_value'               => '2025-01-29',
                'extra_data'                  => json_encode($extra_data->jsonSerialize())
            ]
        ];

        $extra_data = new QuestionTypeDateTimeExtraDataConfig(
            is_default_value_current_time: false,
            is_date_enabled: true,
            is_time_enabled: true,
        );
        yield 'Date and time question type' => [
            [
                Section::getForeignKeyField() => $section_id,
                'name'                        => 'Test form migration for questions - Date and time',
                'type'                        => QuestionTypeDateTime::class,
                'is_mandatory'                => 0,
                'vertical_rank'               => 1,
                'horizontal_rank'             => 2,
                'description'                 => null,
                'default_value'               => '2025-01-29 12:00:00',
                'extra_data'                  => json_encode($extra_data->jsonSerialize())
            ]
        ];

        $default_value = new QuestionTypeItemDefaultValueConfig(1);
        $extra_data = new QuestionTypeItemExtraDataConfig(Location::getType());
        yield 'Item Dropdown question type' => [
            [
                Section::getForeignKeyField() => $section_id,
                'name'                        => 'Test form migration for questions - Dropdown',
                'type'                        => QuestionTypeItemDropdown::class,
                'is_mandatory'                => 0,
                'vertical_rank'               => 2,
                'horizontal_rank'             => null,
                'description'                 => null,
                'default_value'               => json_encode($default_value->jsonSerialize()),
                'extra_data'                  => json_encode($extra_data->jsonSerialize())
            ]
        ];

        yield 'Email question type' => [
            [
                Section::getForeignKeyField() => $section_id,
                'name'                        => 'Test form migration for questions - Email',
                'type'                        => QuestionTypeEmail::class,
                'is_mandatory'                => 0,
                'vertical_rank'               => 3,
                'horizontal_rank'             => null,
                'description'                 => null,
                'default_value'               => 'test@test.fr',
                'extra_data'                  => null
            ]
        ];

        yield 'Float question type' => [
            [
                Section::getForeignKeyField() => $section_id,
                'name'                        => 'Test form migration for questions - Float',
                'type'                        => QuestionTypeNumber::class,
                'is_mandatory'                => 0,
                'vertical_rank'               => 5,
                'horizontal_rank'             => null,
                'description'                 => null,
                'default_value'               => '8,45',
                'extra_data'                  => null
            ]
        ];

        $default_value = new QuestionTypeItemDefaultValueConfig(1);
        $extra_data = new QuestionTypeItemExtraDataConfig(Computer::getType());
        yield 'GLPI Object question type' => [
            [
                Section::getForeignKeyField() => $section_id,
                'name'                        => 'Test form migration for questions - Glpi Object',
                'type'                        => QuestionTypeItem::class,
                'is_mandatory'                => 0,
                'vertical_rank'               => 6,
                'horizontal_rank'             => null,
                'description'                 => null,
                'default_value'               => json_encode($default_value->jsonSerialize()),
                'extra_data'                  => json_encode($extra_data->jsonSerialize())
            ]
        ];

        yield 'Integer question type' => [
            [
                Section::getForeignKeyField() => $section_id,
                'name'                        => 'Test form migration for questions - Integer',
                'type'                        => QuestionTypeNumber::class,
                'is_mandatory'                => 0,
                'vertical_rank'               => 10,
                'horizontal_rank'             => null,
                'description'                 => null,
                'default_value'               => '78',
                'extra_data'                  => null
            ]
        ];

        $extra_data = new QuestionTypeDropdownExtraDataConfig(array_map(fn ($i) => "Option $i", range(1, 5)), true);
        yield 'Dropdown multiple question type' => [
            [
                Section::getForeignKeyField() => $section_id,
                'name'                        => 'Test form migration for questions - Multiselect',
                'type'                        => QuestionTypeDropdown::class,
                'is_mandatory'                => 0,
                'vertical_rank'               => 12,
                'horizontal_rank'             => null,
                'description'                 => null,
                'default_value'               => '2,3',
                'extra_data'                  => json_encode($extra_data->jsonSerialize())
            ]
        ];

        $extra_data = new QuestionTypeSelectableExtraDataConfig(array_map(fn ($i) => "Option $i", range(1, 4)));
        yield 'Radios question type' => [
            [
                Section::getForeignKeyField() => $section_id,
                'name'                        => 'Test form migration for questions - Radios',
                'type'                        => QuestionTypeRadio::class,
                'is_mandatory'                => 0,
                'vertical_rank'               => 13,
                'horizontal_rank'             => null,
                'description'                 => null,
                'default_value'               => '1',
                'extra_data'                  => json_encode($extra_data->jsonSerialize())
            ]
        ];

        yield 'Request type question type' => [
            [
                Section::getForeignKeyField() => $section_id,
                'name'                        => 'Test form migration for questions - Request type',
                'type'                        => QuestionTypeRequestType::class,
                'is_mandatory'                => 0,
                'vertical_rank'               => 14,
                'horizontal_rank'             => null,
                'description'                 => null,
                'default_value'               => '2',
                'extra_data'                  => null
            ]
        ];

        $extra_data = new QuestionTypeDropdownExtraDataConfig(['Option 1', 'Option 2'], false);
        yield 'Dropdown simple question type' => [
            [
                Section::getForeignKeyField() => $section_id,
                'name'                        => 'Test form migration for questions - Select',
                'type'                        => QuestionTypeDropdown::class,
                'is_mandatory'                => 0,
                'vertical_rank'               => 15,
                'horizontal_rank'             => null,
                'description'                 => null,
                'default_value'               => '0',
                'extra_data'                  => json_encode($extra_data->jsonSerialize())
            ]
        ];

        yield 'Text question type' => [
            [
                Section::getForeignKeyField() => $section_id,
                'name'                        => 'Test form migration for questions - Text',
                'type'                        => QuestionTypeShortText::class,
                'is_mandatory'                => 0,
                'vertical_rank'               => 17,
                'horizontal_rank'             => null,
                'description'                 => null,
                'default_value'               => 'Test default text value',
                'extra_data'                  => null
            ]
        ];

        yield 'Textarea question type' => [
            [
                Section::getForeignKeyField() => $section_id,
                'name'                        => 'Test form migration for questions - Textarea',
                'type'                        => QuestionTypeLongText::class,
                'is_mandatory'                => 0,
                'vertical_rank'               => 16,
                'horizontal_rank'             => null,
                'description'                 => null,
                'default_value'               => '<p>Test <span style="color: #2dc26b; background-color: #843fa1;">default value</span> text <strong>area</strong></p>',
                'extra_data'                  => null
            ]
        ];

        $extra_data = new QuestionTypeDateTimeExtraDataConfig(
            is_default_value_current_time: false,
            is_date_enabled: false,
            is_time_enabled: true,
        );
        yield 'Time question type' => [
            [
                Section::getForeignKeyField() => $section_id,
                'name'                        => 'Test form migration for questions - Time',
                'type'                        => QuestionTypeDateTime::class,
                'is_mandatory'                => 0,
                'vertical_rank'               => 19,
                'horizontal_rank'             => null,
                'description'                 => null,
                'default_value'               => '12:00:00',
                'extra_data'                  => json_encode($extra_data->jsonSerialize())
            ]
        ];

        yield 'Urgency question type' => [
            [
                Section::getForeignKeyField() => $section_id,
                'name'                        => 'Test form migration for questions - Urgency',
                'type'                        => QuestionTypeUrgency::class,
                'is_mandatory'                => 0,
                'vertical_rank'               => 20,
                'horizontal_rank'             => null,
                'description'                 => null,
                'default_value'               => '2',
                'extra_data'                  => null
            ]
        ];
    }

    #[DataProvider('provideFormMigrationQuestions')]
    public function testFormMigrationQuestions($data): void
    {
        /**
         * @var \DBmysql $DB
         * LoggerInterface $PHPLOGGER
         */
        global $DB, $PHPLOGGER;
        $migration = new FormMigration($DB, $PHPLOGGER);
        $this->setPrivateProperty($migration, 'result', new PluginMigrationResult());
        $this->assertTrue($this->callPrivateMethod($migration, 'processMigration'));

        $question = getItemByTypeName(Question::class, $data['name']);
        $this->assertSame(
            array_map(fn($value) => is_callable($value) ? $value() : $value, $data),
            array_intersect_key(
                $question->fields,
                $data
            )
        );
    }

    public function testFormMigrationAllExportableQuestionsHaveBeenMigrated(): void
    {
        /**
         * @var \DBmysql $DB
         * LoggerInterface $PHPLOGGER
         */
        global $DB, $PHPLOGGER;
        $migration = new FormMigration($DB, $PHPLOGGER);
        $this->setPrivateProperty($migration, 'result', new PluginMigrationResult());
        $this->assertTrue($this->callPrivateMethod($migration, 'processMigration'));

        $form = getItemByTypeName(Form::class, 'Test form migration for questions');
        $this->assertNotFalse($form);

        /** @var Form $form */
        $questions = $form->getQuestions();
        $exportable_questions = array_filter($questions, function ($question) use ($migration) {
            return in_array(
                $question->getQuestionType()::class,
                array_values($migration->getTypesConvertMap())
            );
        });

        $this->assertSameSize(
            array_filter(array_values($migration->getTypesConvertMap())),
            $exportable_questions
        );
    }

    public function testFormMigrationQuestionOfTypeDescriptionAsBeenMigratedAsComment(): void
    {
        /**
         * @var \DBmysql $DB
         * LoggerInterface $PHPLOGGER
         */
        global $DB, $PHPLOGGER;
        $migration = new FormMigration($DB, $PHPLOGGER);
        $this->setPrivateProperty($migration, 'result', new PluginMigrationResult());
        $this->assertTrue($this->callPrivateMethod($migration, 'processMigration'));

        $form = getItemByTypeName(Form::class, 'Test form migration for questions');
        $this->assertNotFalse($form);

        /** @var Form $form */
        $comments = $form->getFormComments();
        $comment_imported_data = [
            Section::getForeignKeyField() => self::getSectionIDFromFormName('Test form migration for questions', 'Section'),
            'name'                        => 'Test form migration for questions - Description',
            'description'                 => '<p>This is a description question type</p>',
            'vertical_rank'               => 1,
        ];

        $this->assertCount(1, $comments);
        $this->assertEquals(
            $comment_imported_data,
            array_intersect_key(
                current($comments)->fields,
                $comment_imported_data
            )
        );
    }
}
