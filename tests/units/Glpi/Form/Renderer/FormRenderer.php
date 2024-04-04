<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace tests\units\Glpi\Form\Renderer;

use DbTestCase;
use Glpi\Form\QuestionType\QuestionTypesManager;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;

class FormRenderer extends DbTestCase
{
    use FormTesterTrait;

    /**
     * Some questions require extra data to be rendered
     * This provider provides extra data for each question type
     *
     * @return array
     */
    public function renderProvider(): array
    {
        return [
            [
                [
                    \Glpi\Form\QuestionType\QuestionTypeRadio::class => [
                        'options' => [
                            123 => 'Radio 1',
                        ]
                    ],
                    \Glpi\Form\QuestionType\QuestionTypeCheckbox::class => [
                        'options' => [
                            123 => 'Checkbox 1',
                        ]
                    ],
                ]
            ]
        ];
    }

    /**
     * Test the `render` method
     *
     * Note: the HTML content itself is not verified here as it would be too
     * complex.
     * It should be verified using a separate E2E test instead.
     *
     * Any error while rendering the form will still be caught by this tests so
     * we must try to send a very complex form.
     *
     * @dataProvider renderProvider
     *
     * @return void
     */
    public function testRender(array $extra_datas): void
    {
        $this->login();
        $types_manager = QuestionTypesManager::getInstance();

        // TODO: this is the same test data as coded into another seperate PR
        // it should be merged into a common provider

        // Create a form with each possible types of questions and multiple sections
        $form_builder = new FormBuilder();
        $i = 1;

        // Make sure we have at least 50 questions
        $questions = [];
        do {
            $questions = array_merge(
                $questions,
                // Remove keys to make sure new values are added at the end
                array_values($types_manager->getQuestionTypes())
            );
        } while (count($questions) < 50);

        foreach ($questions as $type) {
            $form_builder->addQuestion(
                name: "Question $i",
                type: $type::class,
                extra_data: isset($extra_datas[$type::class]) ? json_encode($extra_datas[$type::class]) : "",
                description: $i % 4 === 0 ? "Description of question $i" : "", // Add a description every 4 questions
                is_mandatory: $i % 2 === 0, // Half of the questions are mandatory
            );

            // Add a section every 10 questions
            if ($i % 10 === 0) {
                $form_builder->addSection("Section " . ($i / 10));
            }
        }

        $form = $this->createForm($form_builder);
        $form_renderer = \Glpi\Form\Renderer\FormRenderer::getInstance();

        // Render form
        $this
            ->string($form_renderer->render($form))
            ->isNotEmpty()
        ;
    }
}
