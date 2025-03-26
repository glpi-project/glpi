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
use Glpi\Form\AccessControl\ControlType\AllowList;
use Glpi\Form\AccessControl\ControlType\AllowListConfig;
use Glpi\Form\AccessControl\ControlType\DirectAccess;
use Glpi\Form\AccessControl\ControlType\DirectAccessConfig;
use Glpi\Form\AccessControl\FormAccessControlManager;
use Glpi\Form\Category;
use Glpi\Form\Comment;
use Glpi\Form\Form;
use Glpi\Form\FormTranslation;
use Glpi\Form\Migration\FormMigration;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\AbstractQuestionTypeSelectable;
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
use Glpi\Message\MessageType;
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
         */
        global $DB;

        $migration = new FormMigration($DB, FormAccessControlManager::getInstance());
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
         */
        global $DB;

        $migration = new FormMigration($DB, FormAccessControlManager::getInstance());
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
         */
        global $DB;

        $migration = new FormMigration($DB, FormAccessControlManager::getInstance());
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
                'default_value'               => json_encode($default_value),
                'extra_data'                  => json_encode($extra_data)
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
                'extra_data'                  => json_encode($extra_data)
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
                'extra_data'                  => json_encode($extra_data)
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
                'extra_data'                  => json_encode($extra_data)
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
                'default_value'               => json_encode($default_value),
                'extra_data'                  => json_encode($extra_data)
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
                'default_value'               => json_encode($default_value),
                'extra_data'                  => json_encode($extra_data)
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
                'extra_data'                  => json_encode($extra_data)
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
                'extra_data'                  => json_encode($extra_data)
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
                'extra_data'                  => json_encode($extra_data)
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
                'extra_data'                  => json_encode($extra_data)
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
         */
        global $DB;

        $migration = new FormMigration($DB, FormAccessControlManager::getInstance());
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
         */
        global $DB;

        $migration = new FormMigration($DB, FormAccessControlManager::getInstance());
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
         */
        global $DB;

        $migration = new FormMigration($DB, FormAccessControlManager::getInstance());
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

    public static function provideFormMigrationWithAccessTypes(): iterable
    {
        $access_config = new DirectAccessConfig(
            allow_unauthenticated: true
        );
        yield 'Test form migration for access types with public access' => [
            'form_name' => 'Test form migration for access types with public access',
            'active_access_control_data' => [
                Form::getForeignKeyField() => fn () => getItemByTypeName(
                    Form::class,
                    'Test form migration for access types with public access',
                    true
                ),
                'strategy'                 => DirectAccess::class ,
                'config'                   => json_encode($access_config->jsonSerialize()),
                'is_active'                => 1
            ]
        ];

        $access_config = new DirectAccessConfig(
            allow_unauthenticated: false
        );
        yield 'Test form migration for access types with private access' => [
            'form_name' => 'Test form migration for access types with private access',
            'active_access_control_data' => [
                Form::getForeignKeyField() => fn () => getItemByTypeName(
                    Form::class,
                    'Test form migration for access types with private access',
                    true
                ),
                'strategy'                 => DirectAccess::class ,
                'config'                   => json_encode($access_config->jsonSerialize()),
                'is_active'                => 1
            ]
        ];

        $access_config = new AllowListConfig(
            user_ids: [2],
            profile_ids: [4, 1],
            group_ids: []
        );
        yield 'Test form migration for access types with restricted access' => [
            'form_name' => 'Test form migration for access types with restricted access',
            'active_access_control_data' => [
                Form::getForeignKeyField() => fn () => getItemByTypeName(
                    Form::class,
                    'Test form migration for access types with restricted access',
                    true
                ),
                'strategy'                 => AllowList::class ,
                'config'                   => json_encode($access_config->jsonSerialize()),
                'is_active'                => 1
            ]
        ];
    }

    #[DataProvider('provideFormMigrationWithAccessTypes')]
    public function testFormMigrationWithAccessTypes($form_name, $active_access_control_data): void
    {
        /**
         * @var \DBmysql $DB
         */
        global $DB;

        $migration = new FormMigration($DB, FormAccessControlManager::getInstance());
        $this->setPrivateProperty($migration, 'result', new PluginMigrationResult());
        $this->assertTrue($this->callPrivateMethod($migration, 'processMigration'));

        $form = getItemByTypeName(Form::class, $form_name);
        $active_access_controls = FormAccessControlManager::getInstance()->getActiveAccessControlsForForm($form);
        foreach ($active_access_controls as $active_access_control) {
            $expected_data = array_map(
                fn($value) => is_callable($value) ? $value() : $value,
                $active_access_control_data
            );
            $actual_data = array_intersect_key($active_access_control->fields, $active_access_control_data);

            // Decode the config JSON strings to compare them as arrays, ignoring the token field
            if (isset($expected_data['config']) && isset($actual_data['config'])) {
                $expected_config = json_decode($expected_data['config'], true);
                $actual_config = json_decode($actual_data['config'], true);
                unset($expected_config['token'], $actual_config['token']);
                $expected_data['config'] = $expected_config;
                $actual_data['config'] = $actual_config;
            }

            $this->assertEqualsCanonicalizing($expected_data, $actual_data);
        }
    }

    public static function provideFormMigrationTranslations(): iterable
    {
        yield 'Test form migration for translations' => [
            'form_name'             => 'Test form migration for translations',
            'raw_translations'      => [
                'Test form migration for translations' => 'Tester la migration des formulaires pour les traductions',
                'This is a description to test the translation' => 'Voici une description pour tester la traduction',
                'First section' => 'Première section',
                'First question' => 'Première question',
                '&#60;p&#62;&#60;strong&#62;Test description for &#60;/strong&#62;first&#60;strong&#62; &#60;span style="color: #b96ad9;"&#62;question&#60;/span&#62;&#60;/strong&#62;&#60;/p&#62;' => '&#60;p&#62;&#60;strong&#62;Test de description&#60;/strong&#62; pour la première &#60;span style="color: #b96ad9;"&#62;&#60;strong&#62;question&#60;/strong&#62;&#60;/span&#62;&#60;/p&#62;',

                /**
                 * TODO: This translation is the default value for the first question of type "Short text"
                 * Actually, we can't translate the default value of this question type.
                 */
                // 'Default value for first question' => 'Valeur par défaut pour la première question',

                'Second question' => 'Deuxième question',
                'First option' => 'Première option',
                'Second option' => 'Deuxième option',
                'Third option' => 'Troisième option',
                'Second section' => 'Deuxième section',
                'Description question' => 'Description question',
                '&#60;p&#62;Description &#60;span style="background-color: #e03e2d; color: #ffffff;"&#62;content&#60;/span&#62;&#60;/p&#62;' => '&#60;p&#62;&#60;span style="background-color: #e03e2d; color: #ffffff;"&#62;Contenu&#60;/span&#62; de la description&#60;/p&#62;',
            ],
            'expected_translations' => function () {
                /** @var Form $form */
                $form = getItemByTypeName(Form::class, 'Test form migration for translations');
                $sections = array_combine(
                    array_map(fn ($section) => $section->getName(), $form->getSections()),
                    array_values($form->getSections())
                );
                $questions = array_combine(
                    array_map(fn ($question) => $question->getName(), $form->getQuestions()),
                    array_values($form->getQuestions())
                );
                $comments = array_combine(
                    array_map(fn ($comment) => $comment->getName(), $form->getFormComments()),
                    array_values($form->getFormComments())
                );

                return [
                    [
                        'items_id' => $form->getID(),
                        'itemtype' => Form::class,
                        'key'      => Form::TRANSLATION_KEY_NAME,
                        'translations' => ['one' => 'Tester la migration des formulaires pour les traductions']
                    ],
                    [
                        'items_id' => $form->getID(),
                        'itemtype' => Form::class,
                        'key'      => Form::TRANSLATION_KEY_HEADER,
                        'translations' => ['one' => 'Voici une description pour tester la traduction']
                    ],
                    [
                        'items_id' => $sections['First section']->getID(),
                        'itemtype' => Section::class,
                        'key'      => Section::TRANSLATION_KEY_NAME,
                        'translations' => ['one' => 'Première section']
                    ],
                    [
                        'items_id' => $questions['First question']->getID(),
                        'itemtype' => Question::class,
                        'key'      => Question::TRANSLATION_KEY_NAME,
                        'translations' => ['one' => 'Première question']
                    ],
                    [
                        'items_id' => $questions['First question']->getID(),
                        'itemtype' => Question::class,
                        'key'      => Question::TRANSLATION_KEY_DESCRIPTION,
                        'translations' => ['one' => '<p><strong>Test de description</strong> pour la première <span style="color: #b96ad9;"><strong>question</strong></span></p>']
                    ],
                    [
                        'items_id' => $sections['Second section']->getID(),
                        'itemtype' => Section::class,
                        'key'      => Section::TRANSLATION_KEY_NAME,
                        'translations' => ['one' => 'Deuxième section']
                    ],
                    [
                        'items_id' => $questions['Second question']->getID(),
                        'itemtype' => Question::class,
                        'key'      => Question::TRANSLATION_KEY_NAME,
                        'translations' => ['one' => 'Deuxième question']
                    ],
                    [
                        'items_id' => $questions['Second question']->getID(),
                        'itemtype' => Question::class,
                        'key'      => AbstractQuestionTypeSelectable::TRANSLATION_KEY_OPTION . '-0',
                        'translations' => ['one' => 'Première option']
                    ],
                    [
                        'items_id' => $questions['Second question']->getID(),
                        'itemtype' => Question::class,
                        'key'      => AbstractQuestionTypeSelectable::TRANSLATION_KEY_OPTION . '-1',
                        'translations' => ['one' => 'Deuxième option']
                    ],
                    [
                        'items_id' => $questions['Second question']->getID(),
                        'itemtype' => Question::class,
                        'key'      => AbstractQuestionTypeSelectable::TRANSLATION_KEY_OPTION . '-2',
                        'translations' => ['one' => 'Troisième option']
                    ],
                    [
                        'items_id' => $comments['Description question']->getID(),
                        'itemtype' => Comment::class,
                        'key'      => Comment::TRANSLATION_KEY_NAME,
                        'translations' => ['one' => 'Description question']
                    ],
                    [
                        'items_id' => $comments['Description question']->getID(),
                        'itemtype' => Comment::class,
                        'key'      => Comment::TRANSLATION_KEY_DESCRIPTION,
                        'translations' => ['one' => '<p><span style="background-color: #e03e2d; color: #ffffff;">Contenu</span> de la description</p>']
                    ]
                ];
            }
        ];
    }

    #[DataProvider('provideFormMigrationTranslations')]
    public function testFormMigrationTranslations($form_name, $raw_translations, $expected_translations): void
    {
        /**
         * @var \DBmysql $DB
         */
        global $DB;

        // Create a partial mock of FormMigration that only mocks getTranslationsFromFile method
        $migration = $this->getMockBuilder(FormMigration::class)
            ->setConstructorArgs([$DB, FormAccessControlManager::getInstance()])
            ->onlyMethods(['getTranslationsFromFile'])
            ->getMock();

        // Configure the mock to return our test translations
        $migration->method('getTranslationsFromFile')
            ->willReturnCallback(function (int $form_id, string $language) use ($raw_translations) {
                // Mock translations for our test case
                if ($form_id == 19 && $language == 'fr_FR') {
                    return $raw_translations;
                }
                return []; // Return empty array for any other form/language combination
            });

        $this->setPrivateProperty($migration, 'result', new PluginMigrationResult());
        $this->assertTrue($this->callPrivateMethod($migration, 'processMigration'));

        // Compute the expected translations
        $expected_translations = $expected_translations();

        /** @var Form $form */
        $form = getItemByTypeName(Form::class, $form_name);
        $this->assertNotFalse($form);
        $translations = FormTranslation::getTranslationsForForm($form);
        $this->assertSameSize($expected_translations, $translations);
        foreach ($expected_translations as $expected_translation) {
            $found = false;
            foreach ($translations as $translation) {
                if (
                    $translation->fields['items_id'] === $expected_translation['items_id']
                    && $translation->fields['itemtype'] === $expected_translation['itemtype']
                    && $translation->fields['key'] === $expected_translation['key']
                ) {
                    $this->assertEquals(
                        json_decode($translation->fields['translations'], true),
                        $expected_translation['translations']
                    );
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $this->fail(sprintf(
                    'Translation not found for itemtype: %s and key: %s',
                    $expected_translation['itemtype'],
                    $expected_translation['key']
                ));
            }
        }
    }

    public function testFormMigrationWithOrphanSection(): void
    {
        /**
         * @var \DBmysql $DB
         */
        global $DB;

        // Insert a section with no form
        $DB->insert(
            'glpi_plugin_formcreator_sections',
            [
                'name' => 'Orphan section',
            ]
        );

        $migration = new FormMigration($DB, FormAccessControlManager::getInstance());
        $result = new PluginMigrationResult();
        $this->setPrivateProperty($migration, 'result', $result);
        $this->assertTrue($this->callPrivateMethod($migration, 'processMigration'));

        $errors = array_filter(
            $result->getMessages(),
            static fn (array $entry) => $entry['type'] === MessageType::Error
        );
        $this->assertCount(1, $errors);
        $this->assertEquals(
            current($errors)['message'],
            'Section "Orphan section" has no form. It will not be migrated.'
        );
    }

    public function testFormMigrationWithOrphanQuestion(): void
    {
        /**
         * @var \DBmysql $DB
         */
        global $DB;

        // Insert a question with no form
        $DB->insert(
            'glpi_plugin_formcreator_questions',
            [
                'name' => 'Orphan question',
            ]
        );

        $migration = new FormMigration($DB, FormAccessControlManager::getInstance());
        $result = new PluginMigrationResult();
        $this->setPrivateProperty($migration, 'result', $result);
        $this->assertTrue($this->callPrivateMethod($migration, 'processMigration'));

        $errors = array_filter(
            $result->getMessages(),
            static fn (array $entry) => $entry['type'] === MessageType::Error
        );
        $this->assertCount(1, $errors);
        $this->assertEquals(
            current($errors)['message'],
            'Question "Orphan question" has no section. It will not be migrated.'
        );
    }
}
