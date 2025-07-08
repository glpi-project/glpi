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
use Glpi\Form\Condition\ConditionData;
use Glpi\Form\Condition\CreationStrategy;
use Glpi\Form\Condition\LogicOperator;
use Glpi\Form\Condition\ValidationStrategy;
use Glpi\Form\Condition\ValueOperator;
use Glpi\Form\Condition\VisibilityStrategy;
use Glpi\Form\Destination\CommonITILField\ContentField;
use Glpi\Form\Destination\CommonITILField\SimpleValueConfig;
use Glpi\Form\Destination\CommonITILField\TitleField;
use Glpi\Form\Destination\FormDestination;
use Glpi\Form\Destination\FormDestinationTicket;
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
use Glpi\Form\QuestionType\QuestionTypeItemDropdownExtraDataConfig;
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
            ],
        ];

        yield 'My test form category' => [
            [
                'name'                => 'My test form category',
                'description'         => '',
                'illustration'        => '',
                'forms_categories_id' => fn() => getItemByTypeName(Category::class, 'Root form category', true),
            ],
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
            ],
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
                'forms_categories_id' => fn() => getItemByTypeName(Category::class, 'My test form category', true),
            ],
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
                'forms_forms_id' => fn() => getItemByTypeName(Form::class, 'Test form migration for sections', true),
                'description'    => '',
                'rank'           => 0,
            ],
        ];

        yield 'Second section' => [
            [
                'name'           => 'Second section',
                'forms_forms_id' => fn() => getItemByTypeName(Form::class, 'Test form migration for sections', true),
                'description'    => '',
                'rank'           => 1,
            ],
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
        $section_id = fn() => self::getSectionIDFromFormName('Test form migration for questions', 'Section');

        $default_value = new QuestionTypeActorsDefaultValueConfig([2]);
        $extra_data = new QuestionTypeActorsExtraDataConfig(true);
        yield 'Actor question type' => [
            [
                Section::getForeignKeyField() => $section_id,
                'name'                        => 'Test form migration for questions - Actor',
                'type'                        => QuestionTypeRequester::class,
                'is_mandatory'                => 0,
                'vertical_rank'               => 0,
                'horizontal_rank'             => 1,
                'description'                 => null,
                'default_value'               => json_encode($default_value),
                'extra_data'                  => json_encode($extra_data),
            ],
        ];

        $extra_data = new QuestionTypeSelectableExtraDataConfig(array_map(fn($i) => "Option $i", range(1, 7)));
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
                'extra_data'                  => json_encode($extra_data),
            ],
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
                'extra_data'                  => json_encode($extra_data),
            ],
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
                'extra_data'                  => json_encode($extra_data),
            ],
        ];

        $default_value = new QuestionTypeItemDefaultValueConfig(1);
        $extra_data = new QuestionTypeItemDropdownExtraDataConfig(Location::getType());
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
                'extra_data'                  => json_encode($extra_data),
            ],
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
                'extra_data'                  => null,
            ],
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
                'extra_data'                  => null,
            ],
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
                'extra_data'                  => json_encode($extra_data),
            ],
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
                'extra_data'                  => null,
            ],
        ];

        $extra_data = new QuestionTypeDropdownExtraDataConfig(array_map(fn($i) => "Option $i", range(1, 5)), true);
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
                'extra_data'                  => json_encode($extra_data),
            ],
        ];

        $extra_data = new QuestionTypeSelectableExtraDataConfig(array_map(fn($i) => "Option $i", range(1, 4)));
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
                'extra_data'                  => json_encode($extra_data),
            ],
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
                'extra_data'                  => null,
            ],
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
                'extra_data'                  => json_encode($extra_data),
            ],
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
                'extra_data'                  => null,
            ],
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
                'extra_data'                  => null,
            ],
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
                'extra_data'                  => json_encode($extra_data),
            ],
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
                'extra_data'                  => null,
            ],
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
                Form::getForeignKeyField() => fn() => getItemByTypeName(
                    Form::class,
                    'Test form migration for access types with public access',
                    true
                ),
                'strategy'                 => DirectAccess::class,
                'config'                   => json_encode($access_config->jsonSerialize()),
                'is_active'                => 1,
            ],
        ];

        $access_config = new DirectAccessConfig(
            allow_unauthenticated: false
        );
        yield 'Test form migration for access types with private access' => [
            'form_name' => 'Test form migration for access types with private access',
            'active_access_control_data' => [
                Form::getForeignKeyField() => fn() => getItemByTypeName(
                    Form::class,
                    'Test form migration for access types with private access',
                    true
                ),
                'strategy'                 => DirectAccess::class,
                'config'                   => json_encode($access_config->jsonSerialize()),
                'is_active'                => 1,
            ],
        ];

        $access_config = new AllowListConfig(
            user_ids: [2],
            profile_ids: [4, 1],
            group_ids: []
        );
        yield 'Test form migration for access types with restricted access' => [
            'form_name' => 'Test form migration for access types with restricted access',
            'active_access_control_data' => [
                Form::getForeignKeyField() => fn() => getItemByTypeName(
                    Form::class,
                    'Test form migration for access types with restricted access',
                    true
                ),
                'strategy'                 => AllowList::class,
                'config'                   => json_encode($access_config->jsonSerialize()),
                'is_active'                => 1,
            ],
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
                '<p>This is a header to test the translation</p>' => '<p>Voici un en-tête pour tester la traduction</p>',
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
                    array_map(fn($section) => $section->getName(), $form->getSections()),
                    array_values($form->getSections())
                );
                $questions = array_combine(
                    array_map(fn($question) => $question->getName(), $form->getQuestions()),
                    array_values($form->getQuestions())
                );
                $comments = array_combine(
                    array_map(fn($comment) => $comment->getName(), $form->getFormComments()),
                    array_values($form->getFormComments())
                );

                return [
                    [
                        'items_id' => $form->getID(),
                        'itemtype' => Form::class,
                        'key'      => Form::TRANSLATION_KEY_NAME,
                        'translations' => ['one' => 'Tester la migration des formulaires pour les traductions'],
                    ],
                    [
                        'items_id' => $form->getID(),
                        'itemtype' => Form::class,
                        'key'      => Form::TRANSLATION_KEY_HEADER,
                        'translations' => ['one' => '<p>Voici un en-tête pour tester la traduction</p>'],
                    ],
                    [
                        'items_id' => $form->getID(),
                        'itemtype' => Form::class,
                        'key'      => Form::TRANSLATION_KEY_DESCRIPTION,
                        'translations' => ['one' => 'Voici une description pour tester la traduction'],
                    ],
                    [
                        'items_id' => $sections['First section']->getID(),
                        'itemtype' => Section::class,
                        'key'      => Section::TRANSLATION_KEY_NAME,
                        'translations' => ['one' => 'Première section'],
                    ],
                    [
                        'items_id' => $questions['First question']->getID(),
                        'itemtype' => Question::class,
                        'key'      => Question::TRANSLATION_KEY_NAME,
                        'translations' => ['one' => 'Première question'],
                    ],
                    [
                        'items_id' => $questions['First question']->getID(),
                        'itemtype' => Question::class,
                        'key'      => Question::TRANSLATION_KEY_DESCRIPTION,
                        'translations' => ['one' => '<p><strong>Test de description</strong> pour la première <span style="color: #b96ad9;"><strong>question</strong></span></p>'],
                    ],
                    [
                        'items_id' => $sections['Second section']->getID(),
                        'itemtype' => Section::class,
                        'key'      => Section::TRANSLATION_KEY_NAME,
                        'translations' => ['one' => 'Deuxième section'],
                    ],
                    [
                        'items_id' => $questions['Second question']->getID(),
                        'itemtype' => Question::class,
                        'key'      => Question::TRANSLATION_KEY_NAME,
                        'translations' => ['one' => 'Deuxième question'],
                    ],
                    [
                        'items_id' => $questions['Second question']->getID(),
                        'itemtype' => Question::class,
                        'key'      => AbstractQuestionTypeSelectable::TRANSLATION_KEY_OPTION . '-0',
                        'translations' => ['one' => 'Première option'],
                    ],
                    [
                        'items_id' => $questions['Second question']->getID(),
                        'itemtype' => Question::class,
                        'key'      => AbstractQuestionTypeSelectable::TRANSLATION_KEY_OPTION . '-1',
                        'translations' => ['one' => 'Deuxième option'],
                    ],
                    [
                        'items_id' => $questions['Second question']->getID(),
                        'itemtype' => Question::class,
                        'key'      => AbstractQuestionTypeSelectable::TRANSLATION_KEY_OPTION . '-2',
                        'translations' => ['one' => 'Troisième option'],
                    ],
                    [
                        'items_id' => $comments['Description question']->getID(),
                        'itemtype' => Comment::class,
                        'key'      => Comment::TRANSLATION_KEY_NAME,
                        'translations' => ['one' => 'Description question'],
                    ],
                    [
                        'items_id' => $comments['Description question']->getID(),
                        'itemtype' => Comment::class,
                        'key'      => Comment::TRANSLATION_KEY_DESCRIPTION,
                        'translations' => ['one' => '<p><span style="background-color: #e03e2d; color: #ffffff;">Contenu</span> de la description</p>'],
                    ],
                ];
            },
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

        // Check that the section hasn't been migrated
        $section = new Section();
        $this->assertFalse($section->getFromDBByCrit([
            'name' => 'Orphan section',
        ]));
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

        // Check that the question hasn't been migrated
        $question = new Question();
        $this->assertFalse($question->getFromDBByCrit([
            'name' => 'Orphan question',
        ]));
    }

    public function testFormMigrationUpdateHorizontalRanks(): void
    {
        /**
         * @var \DBmysql $DB
         */
        global $DB;

        // Insert a new form
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_forms',
            [
                'name' => 'Test form migration for update horizontal ranks',
            ]
        ));

        // Insert a new section
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_sections',
            [
                'name'                        => 'Test form migration for update horizontal ranks - Section',
                'plugin_formcreator_forms_id' => $DB->insertId(),
            ]
        ));

        // Insert three questions with same vertical rank
        $section_id = $DB->insertId();
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_questions',
            [
                'plugin_formcreator_sections_id' => $section_id,
                'name'                           => 'Test form migration for update horizontal ranks - Question 1',
                'row'                            => 0,
                'col'                            => 0,
                'width'                          => 1,
            ]
        ));
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_questions',
            [
                'plugin_formcreator_sections_id' => $section_id,
                'name'                           => 'Test form migration for update horizontal ranks - Comment 1',
                'fieldtype'                      => 'description',
                'row'                            => 0,
                'col'                            => 2,
                'width'                          => 1,
            ]
        ));
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_questions',
            [
                'plugin_formcreator_sections_id' => $section_id,
                'name'                           => 'Test form migration for update horizontal ranks - Question 2',
                'row'                            => 0,
                'col'                            => 4,
                'width'                          => 1,
            ]
        ));

        $migration = new FormMigration($DB, FormAccessControlManager::getInstance());
        $this->setPrivateProperty($migration, 'result', new PluginMigrationResult());
        $this->assertTrue($this->callPrivateMethod($migration, 'processMigration'));

        // Check that the horizontal ranks have been updated
        $section = getItemByTypeName(Section::class, 'Test form migration for update horizontal ranks - Section');
        if (!($section instanceof Section)) {
            $this->fail('Section not found');
        }
        $blocks = $section->getBlocks();

        // Blocks are grouped by vertical rank
        $this->assertCount(1, $blocks);
        $this->assertCount(3, current($blocks));

        // Check ranks
        foreach (current($blocks) as $index => $block) {
            $this->assertEquals(0, $block->fields['vertical_rank']);
            $this->assertEquals($index, $block->fields['horizontal_rank']);
        }
    }

    public function testFormMigrationWithDeletedForm(): void
    {
        /**
         * @var \DBmysql $DB
         */
        global $DB;

        // Insert a new form
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_forms',
            [
                'name'        => 'Test form migration for deleted form',
                'entities_id' => $this->getTestRootEntity(true),
                'is_deleted'  => 1,
            ]
        ));

        // Insert a new section
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_sections',
            [
                'name'                        => 'Test form migration for deleted form - Section',
                'plugin_formcreator_forms_id' => $DB->insertId(),
            ]
        ));

        // Process migration
        $migration = new FormMigration($DB, FormAccessControlManager::getInstance());
        $this->setPrivateProperty($migration, 'result', new PluginMigrationResult());
        $this->assertTrue($this->callPrivateMethod($migration, 'processMigration'));

        $form = getItemByTypeName(Form::class, 'Test form migration for deleted form');
        $this->assertTrue((bool) $form->isDeleted());
    }

    public function testFormMigrationFormContentField(): void
    {
        /**
         * @var \DBmysql $DB
         */
        global $DB;

        // Insert a new form
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_forms',
            [
                'name'        => 'Test form migration for form content field',
                'description' => '<p>Test description</p>',
                'content'     => '<p>Test header</p>',
            ]
        ));

        // Insert a new section
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_sections',
            [
                'name'                        => 'Test form migration for form content field - Section',
                'plugin_formcreator_forms_id' => $DB->insertId(),
            ]
        ));

        // Process migration
        $migration = new FormMigration($DB, FormAccessControlManager::getInstance());
        $this->setPrivateProperty($migration, 'result', new PluginMigrationResult());
        $this->assertTrue($this->callPrivateMethod($migration, 'processMigration'));

        $form = getItemByTypeName(Form::class, 'Test form migration for form content field');
        $this->assertNotFalse($form);

        $this->assertEquals(
            '<p>Test description</p>',
            $form->fields['description']
        );
        $this->assertEquals(
            '<p>Test header</p>',
            $form->fields['header']
        );
    }

    public static function provideFormMigrationTagConversion(): iterable
    {
        yield 'Single question tag' => [
            'rawContent'                => 'Question: ##question_9998##',
            'expectedPatternForTitle' => '/Question: <span[^>]*>#Question: Question for tag tests 1<\/span>/',
            'expectedPatternForContent' => '/Question: <span[^>]*>#Question: Question for tag tests 1<\/span>/',
        ];

        yield 'Single answer tag' => [
            'rawContent'                => 'Answer: ##answer_9998##',
            'expectedPatternForTitle' => '/Answer: <span[^>]*>#Answer: Question for tag tests 1<\/span>/',
            'expectedPatternForContent' => '/Answer: <span[^>]*>#Answer: Question for tag tests 1<\/span>/',
        ];

        yield 'Multiple tags' => [
            'rawContent'                => 'Q1: ##question_9998## A1: ##answer_9998## Q2: ##question_9999##',
            'expectedPatternForTitle' => '/Q1: <span[^>]*>#Question: Question for tag tests 1<\/span> A1: <span[^>]*>#Answer: Question for tag tests 1<\/span> Q2: <span[^>]*>#Question: Question for tag tests 2<\/span>/',
            'expectedPatternForContent' => '/Q1: <span[^>]*>#Question: Question for tag tests 1<\/span> A1: <span[^>]*>#Answer: Question for tag tests 1<\/span> Q2: <span[^>]*>#Question: Question for tag tests 2<\/span>/',
        ];

        yield 'HTML content with tags' => [
            'rawContent'                => '<h2>Form</h2><p>Question: ##question_9998##</p><p>Answer: ##answer_9998##</p>',
            'expectedPatternForTitle'   => '/FormQuestion: <span[^>]*>#Question: Question for tag tests 1<\/span>Answer: <span[^>]*>#Answer: Question for tag tests 1<\/span>/',
            'expectedPatternForContent' => '/<h2>Form<\/h2><p>Question: <span[^>]*>#Question: Question for tag tests 1<\/span><\/p><p>Answer: <span[^>]*>#Answer: Question for tag tests 1<\/span><\/p>/',
        ];

        yield 'FULLFORM placeholder' => [
            'rawContent'                => '##FULLFORM##',
            'expectedPatternForTitle'   => '/##FULLFORM##/',
            'expectedPatternForContent' => '/<p><b>1\) <span[^>]*>#Question: Question for tag tests 1<\/span><\/b>: <span[^>]*>#Answer: Question for tag tests 1<\/span><br><b>2\) <span[^>]*>#Question: Question for tag tests 2<\/span><\/b>: <span[^>]*>#Answer: Question for tag tests 2<\/span><br><\/p>/',
        ];

        yield 'No tags' => [
            'rawContent'                => 'Plain text without any tags',
            'expectedPatternForTitle' => '/^Plain text without any tags$/',
            'expectedPatternForContent' => '/^Plain text without any tags$/',
        ];
    }

    #[DataProvider('provideFormMigrationTagConversion')]
    public function testFormMigrationTagConversion(string $rawContent, string $expectedPatternForTitle, string $expectedPatternForContent): void
    {
        /**
         * @var \DBmysql $DB
         */
        global $DB;

        // Create a test form with simple tags in title and content
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_forms',
            [
                'name' => 'Test form migration for tag conversion',
            ]
        ));
        $form_id = $DB->insertId();

        // Insert section for the form
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_sections',
            [
                'name'                        => 'Section for tag tests',
                'plugin_formcreator_forms_id' => $form_id,
            ]
        ));
        $section_id = $DB->insertId();

        // Insert questions that will be referenced by tags
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_questions',
            [
                'id'                             => 9998,                         // Fixed ID to match our test tags
                'plugin_formcreator_sections_id' => $section_id,
                'name'                           => 'Question for tag tests 1',
                'row'                            => 0,
            ]
        ));
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_questions',
            [
                'id'                             => 9999,                         // Fixed ID to match our test tags
                'plugin_formcreator_sections_id' => $section_id,
                'name'                           => 'Question for tag tests 2',
                'row'                            => 1,
            ]
        ));

        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_targettickets',
            [
                'plugin_formcreator_forms_id' => $form_id,
                'target_name'                 => $rawContent,
                'content'                     => $rawContent,
            ]
        ));

        // Process migration
        $migration = new FormMigration($DB, FormAccessControlManager::getInstance());
        $this->setPrivateProperty($migration, 'result', new PluginMigrationResult());
        $this->assertTrue($this->callPrivateMethod($migration, 'processMigration'));

        // Verify that the tag conversion worked for both title and content fields
        /** @var Form $form */
        $form = getItemByTypeName(Form::class, 'Test form migration for tag conversion');
        $destination = current($form->getDestinations());
        /** @var FormDestinationTicket $ticket_destination */
        $ticket_destination = $destination->getConcreteDestinationItem();

        /** @var SimpleValueConfig $title_config */
        $title_config = $ticket_destination->getConfigurableFieldByKey(TitleField::getKey())
            ->getConfig($form, $destination->getConfig());
        $this->assertMatchesRegularExpression(
            $expectedPatternForTitle,
            $title_config->getValue()
        );

        /** @var SimpleValueConfig $content_config */
        $content_config = $ticket_destination->getConfigurableFieldByKey(ContentField::getKey())
            ->getConfig($form, $destination->getConfig());
        $this->assertMatchesRegularExpression(
            $expectedPatternForContent,
            $content_config->getValue()
        );
    }

    public function testFormMigrationWithRadioQuestion(): void
    {
        /**
         * @var \DBmysql $DB
         */
        global $DB;

        // Insert a new form
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_forms',
            [
                'name' => 'Test form migration for radio question',
            ]
        ));

        // Insert a new section
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_sections',
            [
                'name'                        => 'Test form migration for radio question - Section',
                'plugin_formcreator_forms_id' => $DB->insertId(),
            ]
        ));

        // Insert a new question with type "radio"
        $section_id = $DB->insertId();
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_questions',
            [
                'plugin_formcreator_sections_id' => $section_id,
                'name'                           => 'Test form migration for radio question - Question',
                'fieldtype'                      => 'radios',
                'default_values'                 => '1',
                'values'                         => json_encode(['1', '2', '3']),
            ]
        ));

        $migration = new FormMigration($DB, FormAccessControlManager::getInstance());
        $this->setPrivateProperty($migration, 'result', new PluginMigrationResult());
        $this->assertTrue($this->callPrivateMethod($migration, 'processMigration'));

        // Check that the question has been migrated
        /** @var Question $question */
        $question = getItemByTypeName(Question::class, 'Test form migration for radio question - Question');
        $this->assertInstanceOf(Question::class, $question);

        /** @var QuestionTypeRadio $question_type */
        $question_type = $question->getQuestionType();
        $this->assertInstanceOf(QuestionTypeRadio::class, $question_type);

        $this->assertEquals(
            [
                [
                    'uuid'    => 0,
                    'value'   => '1',
                    'checked' => true,
                ],
                [
                    'uuid'    => 1,
                    'value'   => '2',
                    'checked' => false,
                ],
                [
                    'uuid'    => 2,
                    'value'   => '3',
                    'checked' => false,
                ],
            ],
            $question_type->getValues($question)
        );
    }

    public static function provideFormMigrationVisibilityConditionsForQuestions(): iterable
    {
        yield 'QuestionTypeShortText - Always visible' => [
            'text',
            1,
            [],
            VisibilityStrategy::ALWAYS_VISIBLE,
        ];

        yield 'QuestionTypeShortText - Hidden if condition' => [
            'text',
            3,
            [
                [
                    'show_condition' => 1,
                    'show_value'     => 'Test',
                    'show_logic'     => 1,
                ],
            ],
            VisibilityStrategy::HIDDEN_IF,
            [
                [
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => 'Test',
                    'logic_operator' => LogicOperator::AND,
                ],
            ],
        ];

        yield 'QuestionTypeShortText - Visible if condition' => [
            'text',
            2,
            [
                [
                    'show_condition' => 1,
                    'show_value'     => 'Test',
                    'show_logic'     => 1,
                ],
            ],
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => 'Test',
                    'logic_operator' => LogicOperator::AND,
                ],
            ],
        ];

        $value_operators = [
            1 => ValueOperator::EQUALS,
            2 => ValueOperator::NOT_EQUALS,
            3 => ValueOperator::LESS_THAN,
            4 => ValueOperator::GREATER_THAN,
            5 => ValueOperator::LESS_THAN_OR_EQUALS,
            6 => ValueOperator::GREATER_THAN_OR_EQUALS,
            7 => ValueOperator::VISIBLE,
            8 => ValueOperator::NOT_VISIBLE,
            9 => ValueOperator::MATCH_REGEX,
        ];
        foreach ($value_operators as $key => $value_operator) {
            yield 'QuestionTypeShortText - Visible if condition with value operator ' . $value_operator->getLabel() => [
                'text',
                2,
                [
                    [
                        'show_condition' => $key,
                        'show_value'     => 'Test',
                        'show_logic'     => 1,
                    ],
                ],
                VisibilityStrategy::VISIBLE_IF,
                [
                    [
                        'value_operator' => $value_operator,
                        'value'          => 'Test',
                        'logic_operator' => LogicOperator::AND,
                    ],
                ],
            ];
        }

        yield 'QuestionTypeShortText - Visible if multiple conditions' => [
            'text',
            2,
            [
                [
                    'show_condition' => 1,
                    'show_value'     => 'Test',
                    'show_logic'     => 1,
                ],
                [
                    'show_condition' => 2,
                    'show_value'     => 'Test2',
                    'show_logic'     => 2,
                ],
            ],
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => 'Test',
                    'logic_operator' => LogicOperator::AND,
                ],
                [
                    'value_operator' => ValueOperator::NOT_EQUALS,
                    'value'          => 'Test2',
                    'logic_operator' => LogicOperator::OR,
                ],
            ],
        ];

        yield 'QuestionTypeRadio - Visible if' => [
            'field_type' => 'radios',
            'show_rule'  => 2,
            'conditions' => [
                [
                    'show_condition' => 1,
                    'show_value'     => 'Second option',
                    'show_logic'     => 1,
                ],
            ],
            'expected_visibility_strategy' => VisibilityStrategy::VISIBLE_IF,
            'expected_conditions'          => [
                [
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => 1,
                    'logic_operator' => LogicOperator::AND,
                ],
            ],
            'values' => '["First option","Second option"]',
        ];

        yield 'QuestionTypeCheckbox - Visible if' => [
            'field_type' => 'radios',
            'show_rule'  => 2,
            'conditions' => [
                [
                    'show_condition' => 1,
                    'show_value'     => 'Second option',
                    'show_logic'     => 1,
                ],
                [
                    'show_condition' => 1,
                    'show_value'     => 'Third option',
                    'show_logic'     => 2,
                ],
            ],
            'expected_visibility_strategy' => VisibilityStrategy::VISIBLE_IF,
            'expected_conditions'          => [
                [
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => 1,
                    'logic_operator' => LogicOperator::AND,
                ],
                [
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => 2,
                    'logic_operator' => LogicOperator::OR,
                ],
            ],
            'values' => '["First option","Second option","Third option"]',
        ];

        yield 'QuestionTypeDropdown - Visible if' => [
            'field_type' => 'select',
            'show_rule'  => 2,
            'conditions' => [
                [
                    'show_condition' => 1,
                    'show_value'     => 'Second option',
                    'show_logic'     => 1,
                ],
                [
                    'show_condition' => 1,
                    'show_value'     => 'Third option',
                    'show_logic'     => 2,
                ],
            ],
            'expected_visibility_strategy' => VisibilityStrategy::VISIBLE_IF,
            'expected_conditions'          => [
                [
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => 1,
                    'logic_operator' => LogicOperator::AND,
                ],
                [
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => 2,
                    'logic_operator' => LogicOperator::OR,
                ],
            ],
            'values' => '["First option","Second option","Third option"]',
        ];
    }

    #[DataProvider('provideFormMigrationVisibilityConditionsForQuestions')]
    public function testFormMigrationVisibilityConditionsForQuestions(
        string $field_type,
        int $show_rule,
        array $conditions,
        VisibilityStrategy $expected_visibility_strategy,
        array $expected_conditions = [],
        ?string $values = null
    ): void {
        /**
         * @var \DBmysql $DB
         */
        global $DB;

        // Create a form
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_forms',
            [
                'name' => 'Test form migration condition for questions',
            ]
        ));
        $form_id = $DB->insertId();

        // Insert a section for the form
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_sections',
            [
                'plugin_formcreator_forms_id' => $form_id,
            ]
        ));

        $section_id = $DB->insertId();

        // Insert a question for the form
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_questions',
            [
                'name'                           => 'Test form migration condition for questions - Target question',
                'plugin_formcreator_sections_id' => $section_id,
                'fieldtype'                      => $field_type,
                'values'                         => $values,
                'row'                            => 0,
                'col'                            => 0,
            ]
        ));
        $target_question_id = $DB->insertId();

        // Insert another question to apply conditions on
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_questions',
            [
                'name'                           => 'Test form migration condition for questions - Condition question',
                'plugin_formcreator_sections_id' => $section_id,
                'row'                            => 0,
                'col'                            => 1,
                'show_rule'                      => $show_rule,
            ]
        ));
        $condition_question_id = $DB->insertId();

        // Insert condition if needed
        if (!empty($conditions)) {
            foreach ($conditions as $condition) {
                $this->assertTrue($DB->insert(
                    'glpi_plugin_formcreator_conditions',
                    [
                        'itemtype'                        => 'PluginFormcreatorQuestion',
                        'items_id'                        => $condition_question_id,
                        'plugin_formcreator_questions_id' => $target_question_id,
                        'show_condition'                  => $condition['show_condition'],
                        'show_value'                      => $condition['show_value'],
                        'show_logic'                      => $condition['show_logic'],
                    ]
                ));
            }
        }

        // Process migration
        $migration = new FormMigration($DB, FormAccessControlManager::getInstance());
        $this->setPrivateProperty($migration, 'result', new PluginMigrationResult());
        $this->assertTrue($this->callPrivateMethod($migration, 'processMigration'));

        // Verify that the condition has been migrated correctly
        /** @var Form $form */
        $form = getItemByTypeName(Form::class, 'Test form migration condition for questions');
        $this->assertCount(1, $form->getSections());
        $this->assertCount(2, $form->getQuestions());

        /** @var Question $condition_question */
        $condition_question = getItemByTypeName(Question::class, 'Test form migration condition for questions - Condition question');
        $this->assertNotFalse($condition_question);
        $this->assertEquals($expected_visibility_strategy, $condition_question->getConfiguredVisibilityStrategy());
        $this->assertEquals(
            $expected_conditions,
            array_map(
                fn(ConditionData $condition) => [
                    'value_operator' => $condition->getValueOperator(),
                    'value'          => $condition->getValue(),
                    'logic_operator' => $condition->getLogicOperator(),
                ],
                $condition_question->getConfiguredConditionsData()
            )
        );
    }

    public static function provideFormMigrationValidationConditionsForQuestions(): iterable
    {
        yield 'No validation' => [
            'text',
            [],
            ValidationStrategy::NO_VALIDATION,
        ];

        yield 'QuestionTypeShortText - Valid if condition' => [
            'text',
            [
                [
                    'regex'     => '/^Test valid answer$/',
                    'fieldname' => 'regex',
                ],
            ],
            ValidationStrategy::VALID_IF,
            [
                [
                    'value_operator' => ValueOperator::MATCH_REGEX,
                    'value'          => '/^Test valid answer$/',
                    'logic_operator' => LogicOperator::AND,
                ],
            ],
        ];

        yield 'QuestionTypeNumber - Range validation with min only' => [
            'float',
            [
                [
                    'range_min' => '10',
                    'range_max' => null,
                    'fieldname' => 'range',
                ],
            ],
            ValidationStrategy::VALID_IF,
            [
                [
                    'value_operator' => ValueOperator::GREATER_THAN_OR_EQUALS,
                    'value'          => '10',
                    'logic_operator' => LogicOperator::AND,
                ],
            ],
        ];

        yield 'QuestionTypeNumber - Range validation with max only' => [
            'float',
            [
                [
                    'range_min' => null,
                    'range_max' => '100',
                    'fieldname' => 'range',
                ],
            ],
            ValidationStrategy::VALID_IF,
            [
                [
                    'value_operator' => ValueOperator::LESS_THAN_OR_EQUALS,
                    'value'          => '100',
                    'logic_operator' => LogicOperator::AND,
                ],
            ],
        ];

        yield 'QuestionTypeNumber - Range validation with min and max' => [
            'float',
            [
                [
                    'range_min' => '10',
                    'range_max' => '100',
                    'fieldname' => 'range',
                ],
            ],
            ValidationStrategy::VALID_IF,
            [
                [
                    'value_operator' => ValueOperator::GREATER_THAN_OR_EQUALS,
                    'value'          => '10',
                    'logic_operator' => LogicOperator::AND,
                ],
                [
                    'value_operator' => ValueOperator::LESS_THAN_OR_EQUALS,
                    'value'          => '100',
                    'logic_operator' => LogicOperator::AND,
                ],
            ],
        ];

        yield 'QuestionTypeNumber - Range validation with integer values' => [
            'integer',
            [
                [
                    'range_min' => '5',
                    'range_max' => '50',
                    'fieldname' => 'range',
                ],
            ],
            ValidationStrategy::VALID_IF,
            [
                [
                    'value_operator' => ValueOperator::GREATER_THAN_OR_EQUALS,
                    'value'          => '5',
                    'logic_operator' => LogicOperator::AND,
                ],
                [
                    'value_operator' => ValueOperator::LESS_THAN_OR_EQUALS,
                    'value'          => '50',
                    'logic_operator' => LogicOperator::AND,
                ],
            ],
        ];

        yield 'QuestionTypeShortText - Regex and range validation' => [
            'text',
            [
                [
                    'regex'     => '/^[A-Za-z0-9]+$/',
                    'fieldname' => 'regex',
                ],
                [
                    'range_min' => '5',
                    'range_max' => '50',
                    'fieldname' => 'range',
                ],
            ],
            ValidationStrategy::VALID_IF,
            [
                [
                    'value_operator' => ValueOperator::LENGTH_GREATER_THAN_OR_EQUALS,
                    'value'          => '5',
                    'logic_operator' => LogicOperator::AND,
                ],
                [
                    'value_operator' => ValueOperator::LENGTH_LESS_THAN_OR_EQUALS,
                    'value'          => '50',
                    'logic_operator' => LogicOperator::AND,
                ],
                [
                    'value_operator' => ValueOperator::MATCH_REGEX,
                    'value'          => '/^[A-Za-z0-9]+$/',
                    'logic_operator' => LogicOperator::AND,
                ],
            ],
        ];

        // QuestionTypeDropdown does not support any validation operators
        yield 'QuestionTypeDropdown - Regex and range validation' => [
            'select',
            [
                [
                    'regex'     => '/^[A-Za-z0-9]+$/',
                    'fieldname' => 'regex',
                ],
                [
                    'range_min' => '5',
                    'range_max' => '50',
                    'fieldname' => 'range',
                ],
            ],
            ValidationStrategy::NO_VALIDATION,
        ];
    }

    #[DataProvider('provideFormMigrationValidationConditionsForQuestions')]
    public function testFormMigrationValidationConditionsForQuestions(
        string $field_type,
        array $conditions,
        ValidationStrategy $expected_validation_strategy,
        array $expected_conditions = [],
        ?string $values = null
    ): void {
        /**
         * @var \DBmysql $DB
         */
        global $DB;

        // Create a form
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_forms',
            [
                'name' => 'Test form migration condition for questions',
            ]
        ));
        $form_id = $DB->insertId();

        // Insert a section for the form
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_sections',
            [
                'plugin_formcreator_forms_id' => $form_id,
            ]
        ));

        $section_id = $DB->insertId();

        // Insert a question for the form
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_questions',
            [
                'name'                           => 'Test form migration visibility condition for questions',
                'plugin_formcreator_sections_id' => $section_id,
                'fieldtype'                      => $field_type,
                'values'                         => $values,
                'row'                            => 0,
                'col'                            => 0,
            ]
        ));
        $question_id = $DB->insertId();

        // Insert condition if needed
        if (!empty($conditions)) {
            foreach ($conditions as $condition) {
                if ($condition['fieldname'] === 'regex') {
                    // Insert regex validation condition
                    $this->assertTrue($DB->insert(
                        'glpi_plugin_formcreator_questionregexes',
                        [
                            'plugin_formcreator_questions_id' => $question_id,
                            'regex'                           => $condition['regex'],
                            'fieldname'                       => $condition['fieldname'],
                        ]
                    ));
                } elseif ($condition['fieldname'] === 'range') {
                    // Insert range validation condition
                    $range_data = [
                        'plugin_formcreator_questions_id' => $question_id,
                        'fieldname'                       => $condition['fieldname'],
                    ];

                    if (isset($condition['range_min']) && $condition['range_min'] !== null) {
                        $range_data['range_min'] = $condition['range_min'];
                    }

                    if (isset($condition['range_max']) && $condition['range_max'] !== null) {
                        $range_data['range_max'] = $condition['range_max'];
                    }

                    $this->assertTrue($DB->insert(
                        'glpi_plugin_formcreator_questionranges',
                        $range_data
                    ));
                }
            }
        }

        // Process migration
        $migration = new FormMigration($DB, FormAccessControlManager::getInstance());
        $this->setPrivateProperty($migration, 'result', new PluginMigrationResult());
        $this->assertTrue($this->callPrivateMethod($migration, 'processMigration'));

        // Verify that the condition has been migrated correctly
        /** @var Form $form */
        $form = getItemByTypeName(Form::class, 'Test form migration condition for questions');
        $this->assertCount(1, $form->getSections());
        $this->assertCount(1, $form->getQuestions());

        /** @var Question $condition_question */
        $condition_question = getItemByTypeName(Question::class, 'Test form migration visibility condition for questions');
        $this->assertNotFalse($condition_question);
        $this->assertEquals($expected_validation_strategy, $condition_question->getConfiguredValidationStrategy());
        $this->assertEquals(
            $expected_conditions,
            array_map(
                fn(ConditionData $condition) => [
                    'value_operator' => $condition->getValueOperator(),
                    'value'          => $condition->getValue(),
                    'logic_operator' => $condition->getLogicOperator(),
                ],
                $condition_question->getConfiguredValidationConditionsData()
            )
        );
    }

    public function testFormMigrationVisibilityConditionsForSections(): void
    {
        /**
         * @var \DBmysql $DB
         */
        global $DB;

        // Create a form
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_forms',
            [
                'name' => 'Test form migration condition for sections',
            ]
        ));
        $form_id = $DB->insertId();

        // Insert a section for the form
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_sections',
            [
                'name'                        => 'Test form migration condition for sections - Target section',
                'plugin_formcreator_forms_id' => $form_id,
            ]
        ));
        $target_section_id = $DB->insertId();

        // Insert another section to apply conditions on
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_sections',
            [
                'name'                        => 'Test form migration condition for sections - Condition section',
                'plugin_formcreator_forms_id' => $form_id,
                'show_rule'                   => 3,
            ]
        ));
        $condition_section_id = $DB->insertId();

        // Insert a question for the form
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_questions',
            [
                'name'                           => 'Test form migration condition for sections - Target question',
                'plugin_formcreator_sections_id' => $target_section_id,
            ]
        ));
        $target_question_id = $DB->insertId();

        // Insert condition
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_conditions',
            [
                'itemtype'                        => 'PluginFormcreatorSection',
                'items_id'                        => $condition_section_id,
                'plugin_formcreator_questions_id' => $target_question_id,
                'show_condition'                  => 1,
                'show_value'                      => 'Test',
                'show_logic'                      => 1,
            ]
        ));

        // Process migration
        $migration = new FormMigration($DB, FormAccessControlManager::getInstance());
        $this->setPrivateProperty($migration, 'result', new PluginMigrationResult());
        $this->assertTrue($this->callPrivateMethod($migration, 'processMigration'));

        // Verify that the condition has been migrated correctly
        /** @var Form $form */
        $form = getItemByTypeName(Form::class, 'Test form migration condition for sections');
        $this->assertCount(2, $form->getSections());

        /** @var Section $condition_section */
        $condition_section = getItemByTypeName(Section::class, 'Test form migration condition for sections - Condition section');
        $this->assertNotFalse($condition_section);
        $this->assertEquals(VisibilityStrategy::HIDDEN_IF, $condition_section->getConfiguredVisibilityStrategy());
        $this->assertEquals(
            [
                [
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => 'Test',
                    'logic_operator' => LogicOperator::AND,
                ],
            ],
            array_map(
                fn(ConditionData $condition) => [
                    'value_operator' => $condition->getValueOperator(),
                    'value'          => $condition->getValue(),
                    'logic_operator' => $condition->getLogicOperator(),
                ],
                $condition_section->getConfiguredConditionsData()
            )
        );
    }

    public function testFormMigrationVisibilityConditionsForComments(): void
    {
        /**
         * @var \DBmysql $DB
         */
        global $DB;

        // Create a form
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_forms',
            [
                'name' => 'Test form migration condition for questions',
            ]
        ));
        $form_id = $DB->insertId();

        // Insert a section for the form
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_sections',
            [
                'plugin_formcreator_forms_id' => $form_id,
            ]
        ));
        $section_id = $DB->insertId();

        // Insert a question for the form
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_questions',
            [
                'name'                           => 'Test form migration condition for comments - Target question',
                'plugin_formcreator_sections_id' => $section_id,
            ]
        ));
        $target_question_id = $DB->insertId();

        // Insert a comment to apply conditions on
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_questions',
            [
                'name'                           => 'Test form migration condition for comments - Condition comment',
                'plugin_formcreator_sections_id' => $section_id,
                'fieldtype'                      => 'description',
                'show_rule'                      => 3,
            ]
        ));
        $condition_comment_id = $DB->insertId();

        // Insert condition
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_conditions',
            [
                'itemtype'                        => 'PluginFormcreatorQuestion',
                'items_id'                        => $condition_comment_id,
                'plugin_formcreator_questions_id' => $target_question_id,
                'show_condition'                  => 1,
                'show_value'                      => 'Test',
                'show_logic'                      => 1,
            ]
        ));

        // Process migration
        $migration = new FormMigration($DB, FormAccessControlManager::getInstance());
        $this->setPrivateProperty($migration, 'result', new PluginMigrationResult());
        $this->assertTrue($this->callPrivateMethod($migration, 'processMigration'));

        // Verify that the condition has been migrated correctly
        /** @var Form $form */
        $form = getItemByTypeName(Form::class, 'Test form migration condition for questions');
        $this->assertCount(1, $form->getSections());
        $this->assertCount(1, $form->getQuestions());
        $this->assertCount(1, $form->getFormComments());

        /** @var FormComment $condition_comment */
        $condition_comment = getItemByTypeName(Comment::class, 'Test form migration condition for comments - Condition comment');
        $this->assertNotFalse($condition_comment);
        $this->assertEquals(VisibilityStrategy::HIDDEN_IF, $condition_comment->getConfiguredVisibilityStrategy());
        $this->assertEquals(
            [
                [
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => 'Test',
                    'logic_operator' => LogicOperator::AND,
                ],
            ],
            array_map(
                fn(ConditionData $condition) => [
                    'value_operator' => $condition->getValueOperator(),
                    'value'          => $condition->getValue(),
                    'logic_operator' => $condition->getLogicOperator(),
                ],
                $condition_comment->getConfiguredConditionsData()
            )
        );
    }

    public static function provideFormMigrationVisibilityConditionsForDestinations(): iterable
    {
        $creation_strategies = [
            1 => CreationStrategy::ALWAYS_CREATED,
            2 => CreationStrategy::CREATED_IF,
            3 => CreationStrategy::CREATED_UNLESS,
        ];

        $types = [
            'glpi_plugin_formcreator_targettickets'  => 'PluginFormcreatorTargetTicket',
            'glpi_plugin_formcreator_targetproblems' => 'PluginFormcreatorTargetProblem',
            'glpi_plugin_formcreator_targetchanges'  => 'PluginFormcreatorTargetChange',
        ];

        foreach ($types as $table => $itemtype) {
            foreach ($creation_strategies as $key => $strategy) {
                if ($strategy === CreationStrategy::ALWAYS_CREATED) {
                    $expected_conditions = [];
                } else {
                    $expected_conditions = [
                        [
                            'value_operator' => ValueOperator::EQUALS,
                            'value'          => 'Test',
                            'logic_operator' => LogicOperator::AND,
                        ],
                    ];
                }

                yield 'Destination ' . $itemtype . ' - ' . $strategy->getLabel() => [
                    'legacy_itemtype'            => $itemtype,
                    'legacy_table'               => $table,
                    'legacy_strategy'            => $key,
                    'expected_creation_strategy' => $strategy,
                    'expected_conditions'        => $expected_conditions,
                ];
            }
        }
    }

    #[DataProvider('provideFormMigrationVisibilityConditionsForDestinations')]
    public function testFormMigrationVisibilityConditionsForDestinations(
        string $legacy_itemtype,
        string $legacy_table,
        int $legacy_strategy,
        CreationStrategy $expected_creation_strategy,
        array $expected_conditions
    ): void {
        /**
         * @var \DBmysql $DB
         */
        global $DB;

        // Create a form
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_forms',
            [
                'name' => 'Test form migration condition for destinations',
            ]
        ));
        $form_id = $DB->insertId();

        // Insert a section for the form
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_sections',
            [
                'plugin_formcreator_forms_id' => $form_id,
            ]
        ));

        $section_id = $DB->insertId();

        // Insert a question for the form
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_questions',
            [
                'name'                           => 'Test form migration condition for destinations',
                'plugin_formcreator_sections_id' => $section_id,
            ]
        ));
        $target_question_id = $DB->insertId();

        // Insert a target for the form
        $this->assertTrue($DB->insert(
            $legacy_table,
            [
                'name'                        => 'Test form migration condition for destinations',
                'content'                     => 'Test',
                'plugin_formcreator_forms_id' => $form_id,
                'show_rule'                   => $legacy_strategy,
            ]
        ));
        $destination_id = $DB->insertId();

        // Insert condition
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_conditions',
            [
                'itemtype'                        => $legacy_itemtype,
                'items_id'                        => $destination_id,
                'plugin_formcreator_questions_id' => $target_question_id,
                'show_condition'                  => 1,
                'show_value'                      => 'Test',
                'show_logic'                      => 1,
            ]
        ));

        // Process migration
        $migration = new FormMigration($DB, FormAccessControlManager::getInstance());
        $this->setPrivateProperty($migration, 'result', new PluginMigrationResult());
        $this->assertTrue($this->callPrivateMethod($migration, 'processMigration'));

        // Verify that the condition has been migrated correctly
        /** @var Form $form */
        $form = getItemByTypeName(Form::class, 'Test form migration condition for destinations');
        $this->assertCount(1, $form->getSections());
        $this->assertCount(1, $form->getQuestions());
        $this->assertCount(1, $form->getDestinations());

        /** @var FormDestination $destination */
        $destination = getItemByTypeName(FormDestination::class, 'Test form migration condition for destinations');
        $this->assertNotFalse($destination);
        $this->assertEquals($expected_creation_strategy, $destination->getConfiguredCreationStrategy());
        $this->assertEquals(
            $expected_conditions,
            array_map(
                fn(ConditionData $condition) => [
                    'value_operator' => $condition->getValueOperator(),
                    'value'          => $condition->getValue(),
                    'logic_operator' => $condition->getLogicOperator(),
                ],
                $destination->getConfiguredConditionsData()
            )
        );
    }

    public function testFormMigrationQuestionDropdownItemWithAdvancedOptions(): void
    {
        /**
         * @var \DBmysql $DB
         */
        global $DB;

        $itilcategory = $this->createItem(
            \ITILCategory::class,
            [
                'name' => 'Root Category',
            ]
        );
        $this->createItem(
            \ITILCategory::class,
            [
                'name'              => 'Sub Category',
                'itilcategories_id' => $itilcategory->getId(),
            ]
        );

        // Create a form
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_forms',
            [
                'name' => 'Test form migration question for dropdown item question with advanced options',
            ]
        ));
        $form_id = $DB->insertId();

        // Insert a section for the form
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_sections',
            [
                'plugin_formcreator_forms_id' => $form_id,
            ]
        ));

        $section_id = $DB->insertId();

        // Insert a question for the form
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_questions',
            [
                'name'                           => 'Test form migration question for dropdown item question with advanced options',
                'plugin_formcreator_sections_id' => $section_id,
                'fieldtype'                      => 'dropdown',
                'itemtype'                       => 'ITILCategory',
                'values'                         => json_encode([
                    'show_ticket_categories' => 'request',
                    'show_tree_depth'        => '0',
                    'show_tree_root'         => $itilcategory->getId(),
                    'selectable_tree_root'   => '0',
                    'entity_restrict'        => '3', // Entity restriction
                ]),
            ]
        ));
        $target_question_id = $DB->insertId();

        // Process migration
        $migration = new FormMigration($DB, FormAccessControlManager::getInstance());
        $this->setPrivateProperty($migration, 'result', new PluginMigrationResult());
        $this->assertTrue($this->callPrivateMethod($migration, 'processMigration'));

        // Verify that the question has been migrated correctly
        /** @var Question $question */
        $question = getItemByTypeName(Question::class, 'Test form migration question for dropdown item question with advanced options');
        /** @var QuestionTypeItemDropdown $question_type */
        $question_type = $question->getQuestionType();
        $this->assertEquals(\ITILCategory::getType(), $question_type->getDefaultValueItemtype($question));
        $this->assertEquals(['request'], $question_type->getCategoriesFilter($question));
        $this->assertEquals($itilcategory->getId(), $question_type->getRootItemsId($question));
        $this->assertEquals(0, $question_type->getSubtreeDepth($question));
    }

    public function testFormMigrationWithEntityThatDoesNotExist(): void
    {
        /**
         * @var \DBmysql $DB
         */
        global $DB;

        // Create a form
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_forms',
            [
                'name' => 'Test form migration with entity that does not exist',
                "entities_id" => 999, // Non-existing entity ID
            ]
        ));

        // Create a section for the form
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_sections',
            [
                'plugin_formcreator_forms_id' => $DB->insertId(),
            ]
        ));

        // Process migration
        $migration = new FormMigration($DB, FormAccessControlManager::getInstance());
        $this->setPrivateProperty($migration, 'result', new PluginMigrationResult());
        $this->assertTrue($this->callPrivateMethod($migration, 'processMigration'));

        // Verify that the form has not been migrated
        $this->assertFalse((new Form())->getFromDBByCrit(['name' => 'Test form migration with entity that does not exist']));

        // Verify that the section has not been migrated
        $this->assertFalse((new Section())->getFromDBByCrit(['name' => 'Test form migration with entity that does not exist - Section']));
    }

    public static function provideFormMigrationForSelectableQuestions(): iterable
    {
        yield 'checkbox' => [
            'fieldtype' => 'checkboxes',
            'conditions' => [
                [
                    'show_value' => 'Option 3',
                    'show_logic' => 1, // AND logic
                ],
            ],
            'expected_conditions' => [
                [
                    'value_operator' => ValueOperator::EQUALS,
                    'value' => 2, // Index of 'Option 3' in ['Option 1', 'Option 2', 'Option 3']
                    'logic_operator' => LogicOperator::AND,
                ],
            ],
        ];

        yield 'radio' => [
            'fieldtype' => 'radios',
            'conditions' => [
                [
                    'show_value' => 'Option 3',
                    'show_logic' => 1, // AND logic
                ],
            ],
            'expected_conditions' => [
                [
                    'value_operator' => ValueOperator::EQUALS,
                    'value' => 2, // Index of 'Option 3' in ['Option 1', 'Option 2', 'Option 3']
                    'logic_operator' => LogicOperator::AND,
                ],
            ],
        ];

        yield 'select' => [
            'fieldtype' => 'select',
            'conditions' => [
                [
                    'show_value' => 'Option 3',
                    'show_logic' => 1, // AND logic
                ],
            ],
            'expected_conditions' => [
                [
                    'value_operator' => ValueOperator::EQUALS,
                    'value' => 2, // Index of 'Option 3' in ['Option 1', 'Option 2', 'Option 3']
                    'logic_operator' => LogicOperator::AND,
                ],
            ],
        ];

        yield 'multiselect' => [
            'fieldtype' => 'multiselect',
            'conditions' => [
                [
                    'show_value' => 'Option 2',
                    'show_logic' => 1, // AND logic
                ],
                [
                    'show_value' => 'Option 3',
                    'show_logic' => 2, // OR logic
                ],
            ],
            'expected_conditions' => [
                [
                    'value_operator' => ValueOperator::EQUALS,
                    'value' => 1, // Index of 'Option 2' in ['Option 1', 'Option 2', 'Option 3']
                    'logic_operator' => LogicOperator::AND,
                ],
                [
                    'value_operator' => ValueOperator::EQUALS,
                    'value' => 2, // Index of 'Option 3' in ['Option 1', 'Option 2', 'Option 3']
                    'logic_operator' => LogicOperator::OR,
                ],
            ],
        ];
    }

    #[DataProvider('provideFormMigrationForSelectableQuestions')]
    public function testFormMigrationForSelectableQuestions(
        string $fieldtype,
        array $conditions,
        array $expected_conditions
    ): void {
        /**
         * @var \DBmysql $DB
         */
        global $DB;

        $question_name = "Test form migration for {$fieldtype} question";
        $condition_question_name = "{$question_name} - Condition question";

        // Create a form
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_forms',
            [
                'name' => $question_name,
            ]
        ));
        $form_id = $DB->insertId();

        // Insert a section for the form
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_sections',
            [
                'plugin_formcreator_forms_id' => $form_id,
            ]
        ));

        $section_id = $DB->insertId();

        // Insert a question for the form
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_questions',
            [
                'name'                           => $question_name,
                'plugin_formcreator_sections_id' => $section_id,
                'fieldtype'                      => $fieldtype,
                'values'                         => json_encode(['Option 1', 'Option 2', 'Option 3']),
                'row'                            => 0,
                'col'                            => 0,
            ]
        ));
        $target_question_id = $DB->insertId();

        // Insert another question to apply visibility conditions on
        $this->assertTrue($DB->insert(
            'glpi_plugin_formcreator_questions',
            [
                'name'                           => $condition_question_name,
                'plugin_formcreator_sections_id' => $section_id,
                'fieldtype'                      => 'text',
                'show_rule'                      => 3, // Show if condition is met
            ]
        ));
        $condition_question_id = $DB->insertId();

        // Insert conditions for the question
        foreach ($conditions as $condition) {
            $this->assertTrue($DB->insert(
                'glpi_plugin_formcreator_conditions',
                [
                    'itemtype'                        => 'PluginFormcreatorQuestion',
                    'items_id'                        => $condition_question_id,
                    'plugin_formcreator_questions_id' => $target_question_id,
                    'show_condition'                  => 1,
                    'show_value'                      => $condition['show_value'],
                    'show_logic'                      => $condition['show_logic'],
                ]
            ));
        }

        // Process migration
        $migration = new FormMigration($DB, FormAccessControlManager::getInstance());
        $this->setPrivateProperty($migration, 'result', new PluginMigrationResult());
        $this->assertTrue($this->callPrivateMethod($migration, 'processMigration'));

        // Verify that the question has been migrated correctly
        /** @var Question $question */
        $question = getItemByTypeName(Question::class, $question_name);
        /** @var AbstractQuestionTypeSelectable $question_type */
        $question_type = $question->getQuestionType();

        // Verify options based on question type
        $this->assertInstanceOf(AbstractQuestionTypeSelectable::class, $question_type);
        $this->assertEquals(['Option 1', 'Option 2', 'Option 3'], $question_type->getOptions($question));

        // Verify that the condition question has been migrated correctly
        /** @var Question $condition_question */
        $condition_question = getItemByTypeName(Question::class, $condition_question_name);
        $this->assertNotFalse($condition_question);
        $this->assertEquals(VisibilityStrategy::HIDDEN_IF, $condition_question->getConfiguredVisibilityStrategy());
        $this->assertEquals(
            $expected_conditions,
            array_map(
                fn(ConditionData $condition) => [
                    'value_operator' => $condition->getValueOperator(),
                    'value'          => $condition->getValue(),
                    'logic_operator' => $condition->getLogicOperator(),
                ],
                $condition_question->getConfiguredConditionsData()
            )
        );
    }
}
