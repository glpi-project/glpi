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

namespace Glpi\Form\Migration;

use Glpi\DBAL\JsonFieldInterface;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QuerySubQuery;
use Glpi\DBAL\QueryUnion;
use Glpi\Form\AccessControl\ControlType\AllowList;
use Glpi\Form\AccessControl\ControlType\AllowListConfig;
use Glpi\Form\AccessControl\ControlType\DirectAccess;
use Glpi\Form\AccessControl\ControlType\DirectAccessConfig;
use Glpi\Form\AccessControl\FormAccessControl;
use Glpi\Form\AccessControl\FormAccessControlManager;
use Glpi\Form\Category;
use Glpi\Form\Comment;
use Glpi\Form\Form;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypeCheckbox;
use Glpi\Form\QuestionType\QuestionTypeDateTime;
use Glpi\Form\QuestionType\QuestionTypeDropdown;
use Glpi\Form\QuestionType\QuestionTypeEmail;
use Glpi\Form\QuestionType\QuestionTypeFile;
use Glpi\Form\QuestionType\QuestionTypeItem;
use Glpi\Form\QuestionType\QuestionTypeItemDropdown;
use Glpi\Form\QuestionType\QuestionTypeLongText;
use Glpi\Form\QuestionType\QuestionTypeNumber;
use Glpi\Form\QuestionType\QuestionTypeRadio;
use Glpi\Form\QuestionType\QuestionTypeRequester;
use Glpi\Form\QuestionType\QuestionTypeRequestType;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Form\QuestionType\QuestionTypeUrgency;
use Glpi\Form\Section;
use Glpi\Migration\AbstractPluginMigration;
use LogicException;

final class FormMigration extends AbstractPluginMigration
{
    /**
     * Retrieve the map of types to convert
     *
     * @return array
     */
    public function getTypesConvertMap(): array
    {
        return [
            // TODO: We do not have a question of type "Actor",
            // we have more specific types: "Assignee", "Requester" and "Observer"
            'actor'       => QuestionTypeRequester::class,

            'checkboxes'  => QuestionTypeCheckbox::class,
            'date'        => QuestionTypeDateTime::class,
            'datetime'    => QuestionTypeDateTime::class,
            'dropdown'    => QuestionTypeItemDropdown::class,
            'email'       => QuestionTypeEmail::class,
            'file'        => QuestionTypeFile::class,
            'float'       => QuestionTypeNumber::class,
            'glpiselect'  => QuestionTypeItem::class,
            'integer'     => QuestionTypeNumber::class,
            'multiselect' => QuestionTypeDropdown::class,
            'radios'      => QuestionTypeRadio::class,
            'requesttype' => QuestionTypeRequestType::class,
            'select'      => QuestionTypeDropdown::class,
            'textarea'    => QuestionTypeLongText::class,
            'text'        => QuestionTypeShortText::class,
            'time'        => QuestionTypeDateTime::class,
            'urgency'     => QuestionTypeUrgency::class,

            // Description is replaced by a new block : Comment
            'description' => null,

            // TODO: Must be implemented
            'fields'      => null,
            'tag'         => null,

            // TODO: This types are not supported by the new form system
            // we need to define alternative ways to handle them
            'hidden'      => null,
            'hostname'    => null,
            'ip'          => null,
            'ldapselect'  => null,
            'undefined'   => null,
        ];
    }

    public const PUBLIC_ACCESS_TYPE = 0;
    public const PRIVATE_ACCESS_TYPE = 1;
    public const RESTRICTED_ACCESS_TYPE = 2;

    /**
     * Get the class strategy to use based on the access type
     *
     * @return array Mapping between access type constants and strategy classes
     */
    public function getStrategyForAccessTypes(): array
    {
        return [
            self::PUBLIC_ACCESS_TYPE => DirectAccess::class,
            self::PRIVATE_ACCESS_TYPE => DirectAccess::class,
            self::RESTRICTED_ACCESS_TYPE => AllowList::class
        ];
    }

    /**
     * Create the appropriate strategy configuration based on form access rights
     *
     * @param array $form_access_rights The access rights data from the database
     * @return JsonFieldInterface The configuration object for the access control strategy
     * @throws LogicException When no strategy config is found for the given access type
     */
    public function getStrategyConfigForAccessTypes(array $form_access_rights): JsonFieldInterface
    {
        $clean_ids = fn($ids) => array_unique(array_filter($ids, fn($id) => is_int($id)));

        if (in_array($form_access_rights['access_rights'], [self::PUBLIC_ACCESS_TYPE, self::PRIVATE_ACCESS_TYPE])) {
            return new DirectAccessConfig(
                allow_unauthenticated: $form_access_rights['access_rights'] === self::PUBLIC_ACCESS_TYPE
            );
        } elseif ($form_access_rights['access_rights'] === self::RESTRICTED_ACCESS_TYPE) {
            return new AllowListConfig(
                user_ids: $clean_ids(json_decode($form_access_rights['user_ids'], true) ?? []),
                group_ids: $clean_ids(json_decode($form_access_rights['group_ids'], true) ?? []),
                profile_ids: $clean_ids(json_decode($form_access_rights['profile_ids'], true) ?? [])
            );
        }

        throw new LogicException("Strategy config not found for access type {$form_access_rights['access_rights']}");
    }

    protected function validatePrerequisites(): bool
    {
        $formcreator_schema = [
            'glpi_plugin_formcreator_categories' => [
                'id', 'name', 'plugin_formcreator_categories_id', 'level'
            ],
            'glpi_plugin_formcreator_forms' => [
                'id', 'name', 'description', 'plugin_formcreator_categories_id', 'entities_id',
                'is_recursive', 'is_visible'
            ],
            'glpi_plugin_formcreator_sections' => [
                'id', 'name', 'plugin_formcreator_forms_id', 'order', 'uuid'
            ],
            'glpi_plugin_formcreator_questions' => [
                'id', 'name', 'plugin_formcreator_sections_id', 'fieldtype', 'required', 'default_values',
                'itemtype', 'values', 'description', 'row', 'col', 'uuid'
            ],
            'glpi_plugin_formcreator_forms_users' => [
                'plugin_formcreator_forms_id', 'users_id'
            ],
            'glpi_plugin_formcreator_forms_groups' => [
                'plugin_formcreator_forms_id', 'groups_id'
            ],
            'glpi_plugin_formcreator_forms_profiles' => [
                'plugin_formcreator_forms_id', 'profiles_id'
            ],
        ];

        return $this->checkDbFieldsExists($formcreator_schema);
    }

    protected function processMigration(): bool
    {
        // Count all items to migrate
        $counts = [
            'categories' => $this->countRecords('glpi_plugin_formcreator_categories'),
            'forms' => $this->countRecords('glpi_plugin_formcreator_forms'),
            'sections' => $this->countRecords('glpi_plugin_formcreator_sections'),
            'questions' => $this->countRecords('glpi_plugin_formcreator_questions', ['NOT' => ['fieldtype' => 'description']]),
            'comments' => $this->countRecords('glpi_plugin_formcreator_questions', ['fieldtype' => 'description']),
        ];

        // Set total progress steps
        $this->progress_indicator?->setMaxSteps(
            array_sum($counts)
        );

        // Process each migration step
        $this->processMigrationOfFormCategories();
        $this->processMigrationOfBasicProperties();
        $this->processMigrationOfSections();
        $this->processMigrationOfQuestions();
        $this->processMigrationOfComments();
        $this->updateBlockHorizontalRank();
        $this->processMigrationOfAccessControls();

        $this->progress_indicator?->setProgressBarMessage('');
        $this->progress_indicator?->finish();

        return true;
    }

    private function processMigrationOfFormCategories(): void
    {
        $this->progress_indicator?->setProgressBarMessage(__('Importing form categories...'));

        // Retrieve data from glpi_plugin_formcreator_categories table
        $raw_form_categories = $this->db->request([
            'SELECT' => ['id', 'name', 'plugin_formcreator_categories_id'],
            'FROM'   => 'glpi_plugin_formcreator_categories',
            'ORDER'  => ['level ASC']
        ]);

        foreach ($raw_form_categories as $raw_form_category) {
            $data = [
                'name'                => $raw_form_category['name'],
                'forms_categories_id' => $this->getMappedItemTarget(
                    'PluginFormcreatorCategory',
                    $raw_form_category['plugin_formcreator_categories_id']
                )['items_id'] ?? 0
            ];
            $form_category = $this->importItem(
                Category::class,
                $data,
                $data
            );

            $this->mapItem(
                'PluginFormcreatorCategory',
                $raw_form_category['id'],
                Category::class,
                $form_category->getID()
            );

            $this->progress_indicator?->advance();
        }
    }

    private function processMigrationOfBasicProperties(): void
    {
        $this->progress_indicator?->setProgressBarMessage(__('Importing forms...'));

        // Retrieve data from glpi_plugin_formcreator_forms table
        $raw_forms = $this->db->request([
            'SELECT' => [
                'id',
                'description AS header',
                'name',
                'plugin_formcreator_categories_id',
                'entities_id',
                'is_recursive',
                'is_visible AS is_active'
            ],
            'FROM'   => 'glpi_plugin_formcreator_forms'
        ]);

        foreach ($raw_forms as $raw_form) {
            $form = $this->importItem(
                Form::class,
                [
                    'name'                  => $raw_form['name'],
                    'header'                => $raw_form['header'],
                    'forms_categories_id'   => $this->getMappedItemTarget(
                        'PluginFormcreatorCategory',
                        $raw_form['plugin_formcreator_categories_id']
                    )['items_id'] ?? 0,
                    'entities_id'           => $raw_form['entities_id'],
                    'is_recursive'          => $raw_form['is_recursive'],
                    'is_active'             => $raw_form['is_active'],
                    '_do_not_init_sections' => true
                ],
                [
                    'name'                => $raw_form['name'],
                    'entities_id'         => $raw_form['entities_id'],
                    'forms_categories_id' => $this->getMappedItemTarget(
                        'PluginFormcreatorCategory',
                        $raw_form['plugin_formcreator_categories_id']
                    )['items_id'] ?? 0,
                ]
            );

            $this->mapItem(
                'PluginFormcreatorForm',
                $raw_form['id'],
                Form::class,
                $form->getID()
            );

            $this->progress_indicator?->advance();
        }
    }

    private function processMigrationOfSections(): void
    {
        $this->progress_indicator?->setProgressBarMessage(__('Importing sections...'));

        // Retrieve data from glpi_plugin_formcreator_sections table
        $raw_sections = $this->db->request([
            'SELECT' => ['id', 'name', 'plugin_formcreator_forms_id', 'order', 'uuid'],
            'FROM'   => 'glpi_plugin_formcreator_sections'
        ]);

        foreach ($raw_sections as $raw_section) {
            $section = $this->importItem(
                Section::class,
                [
                    Form::getForeignKeyField() => $this->getMappedItemTarget(
                        'PluginFormcreatorForm',
                        $raw_section['plugin_formcreator_forms_id']
                    )['items_id'],
                    'name'                     => $raw_section['name'],
                    'rank'                     => $raw_section['order'] - 1, // New rank is 0-based
                    'uuid'                     => $raw_section['uuid']
                ],
                [
                    'uuid' => $raw_section['uuid']
                ]
            );

            $this->mapItem(
                'PluginFormcreatorSection',
                $raw_section['id'],
                Section::class,
                $section->getID()
            );

            $this->progress_indicator?->advance();
        }
    }

    private function processMigrationOfQuestions(): void
    {
        $this->progress_indicator?->setProgressBarMessage(__('Importing questions...'));

        // Process questions
        $raw_questions = array_values(iterator_to_array($this->db->request([
            'SELECT' => [
                'id',
                'name',
                'plugin_formcreator_sections_id',
                'fieldtype',
                'required',
                'default_values',
                'itemtype',
                'values',
                'description',
                'row',
                'col',
                'uuid'
            ],
            'FROM'   => 'glpi_plugin_formcreator_questions',
            'WHERE'  => ['NOT' => ['fieldtype' => 'description']],
            'ORDER'  => ['plugin_formcreator_sections_id', 'row', 'col']
        ])));

        foreach ($raw_questions as $raw_question) {
            $fieldtype = $raw_question['fieldtype'];
            $type_class = $this->getTypesConvertMap()[$fieldtype] ?? null;

            $default_value = null;
            $extra_data = null;
            if (is_a($type_class, FormQuestionDataConverterInterface::class, true)) {
                $converter     = new $type_class();
                $default_value = $converter->convertDefaultValue($raw_question);
                $extra_data    = $converter->convertExtraData($raw_question);
            }

            $question = new Question();
            $data = array_filter([
                Section::getForeignKeyField() => $this->getMappedItemTarget(
                    'PluginFormcreatorSection',
                    $raw_question['plugin_formcreator_sections_id']
                )['items_id'],
                'name'                        => $raw_question['name'],
                'type'                        => $type_class,
                'is_mandatory'                => $raw_question['required'],
                'vertical_rank'               => $raw_question['row'],
                'horizontal_rank'             => $raw_question['col'],
                'description'                 => !empty($raw_question['description'])
                                                    ? $raw_question['description']
                                                    : null,
                'default_value'               => $default_value,
                'extra_data'                  => $extra_data,
                'uuid'                        => $raw_question['uuid']
            ], fn ($value) => $value !== null);

            $question = $this->importItem(
                Question::class,
                $data,
                [
                    'uuid' => $raw_question['uuid'],
                ]
            );

            $this->mapItem(
                'PluginFormcreatorQuestion',
                $raw_question['id'],
                Question::class,
                $question->getID()
            );

            $this->progress_indicator?->advance();
        }
    }

    private function processMigrationOfComments(): void
    {
        $this->progress_indicator?->setProgressBarMessage(__('Importing comments...'));

        // Retrieve data from glpi_plugin_formcreator_questions table
        $raw_comments = $this->db->request([
            'SELECT' => [
                'id',
                'name',
                'plugin_formcreator_sections_id',
                'fieldtype',
                'required',
                'default_values',
                'description',
                'row',
                'col',
                'uuid'
            ],
            'FROM'   => 'glpi_plugin_formcreator_questions',
            'WHERE'  => ['fieldtype' => 'description']
        ]);

        foreach ($raw_comments as $raw_comment) {
            $comment = $this->importItem(
                Comment::class,
                [
                    Section::getForeignKeyField() => $this->getMappedItemTarget(
                        'PluginFormcreatorSection',
                        $raw_comment['plugin_formcreator_sections_id']
                    )['items_id'],
                    'name'                        => $raw_comment['name'],
                    'description'                 => $raw_comment['description'],
                    'vertical_rank'               => $raw_comment['row'],
                    'horizontal_rank'             => $raw_comment['col'],
                    'uuid'                        => $raw_comment['uuid']
                ],
                [
                    'uuid' => $raw_comment['uuid']
                ]
            );

            $this->mapItem(
                'PluginFormcreatorQuestion',
                $raw_comment['id'],
                Comment::class,
                $comment->getID()
            );

            $this->progress_indicator?->advance();
        }
    }

    /**
     * Update horizontal rank of questions and comments to be consistent with the new form system
     *
     * @return void
     */
    private function updateBlockHorizontalRank(): void
    {
        $this->progress_indicator?->setProgressBarMessage(__('Updating horizontal rank...'));

        $tables = [Question::getTable(), Comment::getTable()];

        $getSubQuery = function (string $column) {
            return new QuerySubQuery([
                'SELECT' => $column,
                'FROM'   => new QueryUnion([
                    [
                        'SELECT' => ['forms_sections_id', 'vertical_rank', 'horizontal_rank'],
                        'FROM'   => Question::getTable(),
                    ],
                    [
                        'SELECT' => ['forms_sections_id', 'vertical_rank', 'horizontal_rank'],
                        'FROM'   => Comment::getTable(),
                    ]
                ]),
                'WHERE'   => ['NOT' => ['horizontal_rank' => null]],
                'GROUPBY' => ['forms_sections_id', 'vertical_rank'],
                'HAVING'  => ['COUNT(*) = 1']
            ]);
        };

        foreach ($tables as $table) {
            $this->db->update(
                $table,
                ['horizontal_rank' => null],
                [
                    'forms_sections_id' => $getSubQuery('forms_sections_id'),
                    'vertical_rank'     => $getSubQuery('vertical_rank'),
                ]
            );
        }
    }

    private function processMigrationOfAccessControls(): void
    {
        $this->progress_indicator?->setProgressBarMessage(__('Importing access controls...'));

        // Retrieve data from glpi_plugin_formcreator_forms table
        $raw_form_access_rights = $this->db->request([
            'SELECT' => [
                'access_rights',
                new QueryExpression('glpi_plugin_formcreator_forms.id', 'forms_id'),
                'name', // Added to get form name for status reporting
                new QueryExpression('JSON_ARRAYAGG(users_id)', 'user_ids'),
                new QueryExpression('JSON_ARRAYAGG(groups_id)', 'group_ids'),
                new QueryExpression('JSON_ARRAYAGG(profiles_id)', 'profile_ids')
            ],
            'FROM'   => 'glpi_plugin_formcreator_forms',
            'LEFT JOIN'   => [
                'glpi_plugin_formcreator_forms_users' => [
                    'ON' => [
                        'glpi_plugin_formcreator_forms_users' => 'plugin_formcreator_forms_id',
                        'glpi_plugin_formcreator_forms'       => 'id'
                    ]
                ],
                'glpi_plugin_formcreator_forms_groups' => [
                    'ON' => [
                        'glpi_plugin_formcreator_forms_groups' => 'plugin_formcreator_forms_id',
                        'glpi_plugin_formcreator_forms'        => 'id'
                    ]
                ],
                'glpi_plugin_formcreator_forms_profiles' => [
                    'ON' => [
                        'glpi_plugin_formcreator_forms_profiles' => 'plugin_formcreator_forms_id',
                        'glpi_plugin_formcreator_forms'          => 'id'
                    ]
                ]
            ],
            'GROUPBY' => ['forms_id', 'access_rights']
        ]);

        foreach ($raw_form_access_rights as $form_access_rights) {
            $form = Form::getById(
                $this->getMappedItemTarget(
                    'PluginFormcreatorForm',
                    $form_access_rights['forms_id']
                )['items_id'] ?? 0
            );
            if ($form === null) {
                throw new LogicException("Form with id {$form_access_rights['forms_id']} not found");
            }

            $strategy_class = self::getStrategyForAccessTypes()[$form_access_rights['access_rights']] ?? null;
            if ($strategy_class === null) {
                throw new LogicException("Strategy class not found for access type {$form_access_rights['access_rights']}");
            }

            $manager = FormAccessControlManager::getInstance();
            $manager->createMissingAccessControlsForForm($form);

            $form_access_control = $this->importItem(
                FormAccessControl::class,
                [
                    Form::getForeignKeyField() => $form->getID(),
                    'strategy'                 => $strategy_class,
                    '_config'                  => self::getStrategyConfigForAccessTypes($form_access_rights)->jsonSerialize(),
                    'is_active'                => true
                ],
                [
                    Form::getForeignKeyField() => $form->getID(),
                    'strategy'                 => $strategy_class,
                ]
            );

            $this->mapItem(
                'PluginFormcreatorFormAccessType',
                $form_access_rights['forms_id'],
                FormAccessControl::class,
                $form_access_control->getID()
            );

            $this->progress_indicator?->advance();
        }
    }
}
