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

namespace Glpi\Form\Migration;

use Glpi\Console\Migration\FormCreatorPluginToCoreCommand;
use Glpi\DBAL\QuerySubQuery;
use Glpi\DBAL\QueryUnion;
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
use Glpi\Message\MessageType;
use Glpi\Migration\AbstractPluginMigration;
use Plugin;

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

    protected function validatePrerequisites(): bool
    {
        $this->result->addMessage(MessageType::Notice, 'Checking plugin version...');

        $plugin = new Plugin();
        if (!$plugin->getFromDBbyDir('formcreator')) {
            $this->result->addMessage(MessageType::Error, 'Formcreator plugin is not installed.');
            return false;
        }

        $is_version_ok = FormCreatorPluginToCoreCommand::FORMCREATOR_REQUIRED_VERSION === $plugin->fields['version'];
        if (!$is_version_ok) {
            $this->result->addMessage(MessageType::Error, sprintf(
                'Last Formcreator version (%s) is required to be able to continue.',
                FormCreatorPluginToCoreCommand::FORMCREATOR_REQUIRED_VERSION
            ));
            return false;
        }

        $formcreator_tables = [
            'glpi_plugin_formcreator_categories',
            'glpi_plugin_formcreator_forms',
            'glpi_plugin_formcreator_sections',
            'glpi_plugin_formcreator_questions',
        ];
        $missing_tables = false;
        foreach ($formcreator_tables as $table) {
            if (!$this->db->tableExists($table)) {
                $this->result->addMessage(MessageType::Error, sprintf('Formcreator plugin table "%s" is missing.', $table));
                $missing_tables = true;
            }
        }
        if ($missing_tables) {
            $this->result->addMessage(MessageType::Error, 'Migration cannot be done.');
            return false;
        }

        return true;
    }

    protected function processMigration(): bool
    {
        $categories_iterator = $this->db->request(['FROM' => 'glpi_plugin_formcreator_categories']);
        $forms_iterator = $this->db->request(['FROM' => 'glpi_plugin_formcreator_forms']);
        $sections_iterator = $this->db->request(['FROM' => 'glpi_plugin_formcreator_sections']);
        $questions_iterator = $this->db->request(['FROM' => 'glpi_plugin_formcreator_questions']);
        $comments_iterator = $this->db->request([
            'FROM'  => 'glpi_plugin_formcreator_questions',
            'WHERE' => ['fieldtype' => 'description']
        ]);

        $this->progress_indicator?->setMaxSteps(
            $categories_iterator->count()
                + $forms_iterator->count()
                + $sections_iterator->count()
                + $questions_iterator->count()
                + $comments_iterator->count()
        );

        $this->processMigrationOfFormCategories();
        $this->processMigrationOfBasicProperties();
        $this->processMigrationOfSections();
        $this->processMigrationOfQuestions();
        $this->processMigrationOfComments();
        $this->updateBlockHorizontalRank();

        $this->progress_indicator?->setProgressBarMessage('');
        $this->progress_indicator?->finish();

        return true;
    }

    public function processMigrationOfFormCategories(): void
    {
        $this->progress_indicator?->setProgressBarMessage('Importing form categories...');

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
                    'glpi_plugin_formcreator_categories',
                    $raw_form_category['plugin_formcreator_categories_id']
                )['items_id'] ?? 0
            ];
            $form_category = $this->importItem(
                Category::class,
                $data,
                $data
            );

            $this->mapItem(
                'glpi_plugin_formcreator_categories',
                $raw_form_category['id'],
                Category::class,
                $form_category->getID()
            );

            $this->progress_indicator?->setCurrentStep($this->progress_indicator->getCurrentStep() + 1);
        }
    }

    public function processMigrationOfBasicProperties(): void
    {
        $this->progress_indicator?->setProgressBarMessage('Importing forms...');

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
                        'glpi_plugin_formcreator_categories',
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
                        'glpi_plugin_formcreator_categories',
                        $raw_form['plugin_formcreator_categories_id']
                    )['items_id'] ?? 0,
                ]
            );

            $this->mapItem(
                'glpi_plugin_formcreator_forms',
                $raw_form['id'],
                Form::class,
                $form->getID()
            );

            $this->progress_indicator?->setCurrentStep($this->progress_indicator->getCurrentStep() + 1);
        }
    }

    public function processMigrationOfSections(): void
    {
        $this->progress_indicator?->setProgressBarMessage('Importing sections...');

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
                        'glpi_plugin_formcreator_forms',
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
                'glpi_plugin_formcreator_sections',
                $raw_section['id'],
                Section::class,
                $section->getID()
            );

            $this->progress_indicator?->setCurrentStep($this->progress_indicator->getCurrentStep() + 1);
        }
    }

    public function processMigrationOfQuestions(): void
    {
        $this->progress_indicator?->setProgressBarMessage('Importing questions...');

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
            if (is_a($type_class, 'Glpi\Form\Migration\FormQuestionDataConverterInterface', true)) {
                $converter     = new $type_class();
                $default_value = $converter->convertDefaultValue($raw_question);
                $extra_data    = $converter->convertExtraData($raw_question);
            }

            $question = new Question();
            $data = array_filter([
                Section::getForeignKeyField() => $this->getMappedItemTarget(
                    'glpi_plugin_formcreator_sections',
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
                'glpi_plugin_formcreator_questions',
                $raw_question['id'],
                Question::class,
                $question->getID()
            );

            $this->progress_indicator?->setCurrentStep($this->progress_indicator->getCurrentStep() + 1);
        }
    }

    public function processMigrationOfComments(): void
    {
        $this->progress_indicator?->setProgressBarMessage('Importing comments...');

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
                        'glpi_plugin_formcreator_sections',
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
                'glpi_plugin_formcreator_questions',
                $raw_comment['id'],
                Comment::class,
                $comment->getID()
            );

            $this->progress_indicator?->setCurrentStep($this->progress_indicator->getCurrentStep() + 1);
        }
    }

    /**
     * Update horizontal rank of questions and comments to be consistent with the new form system
     *
     * @return void
     */
    public function updateBlockHorizontalRank(): void
    {
        $this->progress_indicator?->setProgressBarMessage('Updating horizontal rank...');

        $tables = [Question::getTable(), Comment::getTable()];

        $getSubQuery = function (string $column) {
            return new QuerySubQuery([
                'SELECT' => '*',
                'FROM'   => new QuerySubQuery([
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
                ], 'sub_query')
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
}
