<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

namespace Glpi\Form;

use CommonDBTM;
use Entity;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QuerySubQuery;
use Glpi\Form\QuestionType\QuestionTypeShortAnswerText;
use Glpi\Form\QuestionType\QuestionTypesLoader;
use Log;

/**
 * Helpdesk form
 * // TODO: add override attribute once php83 polyfill is integrated
 */
class Form extends CommonDBTM
{
    public static $rightname = 'form';

    /**
     * Lazy loaded array of sections
     * Should always be accessed through getSections()
     * @var Section[]|null
     */
    protected ?array $sections = null;

    public static function getTypeName($nb = 0)
    {
        return _n('Form', 'Forms', $nb);
    }

    public static function getIcon()
    {
        return "ti ti-forms";
    }

    public function defineTabs($options = [])
    {
        $tabs = parent::defineTabs();
        $this->addStandardTab(AnswersSet::getType(), $tabs, $options);
        $this->addStandardTab(Log::getType(), $tabs, $options);
        return $tabs;
    }

    public function getEmpty()
    {
        parent::getEmpty();
        $this->fields['name'] = __("Untitled form");
        $this->fields['header'] = __("My form description...");

        return true;
    }

    public function showForm($id, array $options = [])
    {
        if (!empty($id)) {
            $this->getFromDB($id);
        } else {
            $this->getEmpty();
        }
        $this->initForm($id, $options);

        // Render twig template
        $twig = TemplateRenderer::getInstance();
        $twig->display('pages/admin/form/form_editor.html.twig', [
            'item'                  => $this,
            'params'                => $options,
            'question_types'        => new QuestionTypesLoader(),
            'default_question_type' => QuestionTypeShortAnswerText::class,
        ]);
        return true;
    }

    public function rawSearchOptions()
    {
        $search_options = parent::rawSearchOptions();

        $search_options[] = [
            'id'            => '2',
            'table'         => self::getTable(),
            'field'         => 'id',
            'name'          => __('ID'),
            'massiveaction' => false,
            'datatype'      => 'number'
        ];
        $search_options[] = [
            'id'            => '80',
            'table'         => Entity::getTable(),
            'field'         => 'completename',
            'name'          => Entity::getTypeName(1),
            'datatype'      => 'dropdown',
            'massiveaction' => false,
        ];
        $search_options[] = [
            'id'       => '3',
            'table'    => $this->getTable(),
            'field'    => 'is_active',
            'name'     => __('Active'),
            'datatype' => 'bool'
        ];
        $search_options[] = [
            'id'            => '4',
            'table'         => $this->getTable(),
            'field'         => 'date_mod',
            'name'          => __('Last update'),
            'datatype'      => 'datetime',
            'massiveaction' => false
        ];
        $search_options[] = [
            'id'            => '5',
            'table'         => $this->getTable(),
            'field'         => 'date_creation',
            'name'          => __('Creation date'),
            'datatype'      => 'datetime',
            'massiveaction' => false
        ];

        return $search_options;
    }

    public function prepareInputForUpdate($input)
    {
        // Insert date_mod event if the framework would handle it by itself
        // This avoid "empty" updates when the form itself is not modified but
        // its questions are
        $input['date_mod'] = $_SESSION['glpi_currenttime'];

        return $input;
    }

    public function post_updateItem($history = 1)
    {
        // Update questions
        $this->updateQuestions();

        // Clear any lazy loaded data
        $this->clearLazyLoadedData();
    }

    /**
     * Get sections of this form
     *
     * @return Section[]
     */
    public function getSections(): array
    {
        // Lazy loading
        if ($this->sections === null) {
            $this->sections = [];

            // Read from database
            $sections_data = (new Section())->find([
                self::getForeignKeyField() => $this->fields['id']
            ]);

            foreach ($sections_data as $row) {
                $section = new Section();
                $section->getFromResultSet($row);
                $section->post_getFromDB();
                $this->sections[$row['id']] = $section;
            }
        }

        return $this->sections;
    }

    /**
     * Get all questions for this form
     *
     * @return Question[]
     */
    public function getQuestions(): array
    {
        $questions = [];
        foreach ($this->getSections() as $section) {
            // Its important to use the "+" operator here and not array_merge
            // because the keys must be preserved
            $questions = $questions + $section->getQuestions();
        }
        return $questions;
    }

    /**
     * Clear lazy loaded data
     *
     * @return void
     */
    protected function clearLazyLoadedData(): void
    {
        $this->sections = null;
    }

    /**
     * Temporary function to get the working section
     * Will be removed once multiple sections are supported
     *
     * @return Section
     */
    protected function getUniqueSection(): Section
    {
        // For now use a single hidden section as users cant interact with them
        $section = new Section();
        $sections = $section->find(['forms_forms_id' => $this->fields['id']]);
        if (!count($sections)) {
            $section = new Section();
            $section->add([
                'forms_forms_id' => $this->fields['id'],
                'name'           => 'tmp',
                'rank'           => 1,
            ]);

            return $section;
        } else {
            return Section::getById(array_pop($sections)['id']);
        }
    }

    /**
     * Update form's questions using the special data found in
     * $this->input['_questions']
     *
     * @return void
     */
    protected function updateQuestions(): void
    {
        $questions = $this->input['_questions'] ?? [];
        $section = $this->getUniqueSection();

        // Keep track of newly created questions id's so that they can be used
        // by futures client side requests
        $this->extra_response_data['added_questions'] = [];

        // Keep track of questions found
        $found_questions = [];

        // Parse each submitted question
        foreach ($questions as $input_index => $form_data) {
            $question = new Question();

            // Insert forced section id
            $form_data['forms_sections_id'] = $section->fields['id'];

            if ($form_data['id'] == 0) {
                // Add new question
                unset($form_data['id']);
                $id = $question->add($form_data);

                if (!$id) {
                    trigger_error("Failed to add question", E_USER_WARNING);
                    continue;
                }

                // Keep track of its id
                $found_questions[] = $id;
                $this->extra_response_data['added_questions'][] = [
                    'input_index' => $input_index,
                    'id'          => $id,
                ];
            } else {
                // Update existing question
                $success = $question->update($form_data);
                if (!$success) {
                    trigger_error("Failed to update question", E_USER_WARNING);
                }

                // Keep track of its id
                $found_questions[] = $form_data['id'];
            }
        }

        // Safety check to avoid deleting all questions if some code run an update
        // without the _questions keys.
        // Deletion is only done if the special "_delete_missing_questions" key
        // is present
        $delete_missing_questions = $this->input['_delete_missing_questions'] ?? false;
        if ($delete_missing_questions) {
            // Avoid empty IN clause
            if (empty($found_questions)) {
                $found_questions = [-1];
            }

            $missing_questions = (new Question())->find([
                // Is part of this form
                'forms_sections_id' => new QuerySubQuery([
                    'SELECT' => 'id',
                    'FROM'   => Section::getTable(),
                    'WHERE'  => [
                        'forms_forms_id' => $this->fields['id'],
                    ],
                ]),
                 // Was not found in the submitted data
                'id' => ['NOT IN', $found_questions],
            ]);

            foreach ($missing_questions as $row) {
                $question = new Question();
                $success = $question->delete($row);
                if (!$success) {
                    trigger_error("Failed to delete question", E_USER_WARNING);
                }
            }
        }

        // Special input has been handled, it can be deleted
        unset($this->input['_questions']);
    }
}
