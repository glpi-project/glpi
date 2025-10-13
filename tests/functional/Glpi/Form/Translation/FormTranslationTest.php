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

namespace tests\units\Glpi\Form;

use Dropdown;
use Glpi\Form\Form;
use Glpi\Form\FormTranslation;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\AbstractQuestionTypeSelectable;
use Glpi\Form\QuestionType\QuestionTypeDropdown;
use Glpi\Form\QuestionType\QuestionTypeDropdownExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeSelectableExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Form\QuestionType\QuestionTypesManager;
use Glpi\Form\QuestionType\TranslationAwareQuestionType;
use Glpi\ItemTranslation\Context\TranslationHandler;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Session;

use function Safe\json_encode;

class FormTranslationTest extends \DbTestCase
{
    use FormTesterTrait;

    public function testGetLanguagesCanBeAddedToTranslation()
    {
        $form = $this->createFormWithTranslations();

        $this->assertEquals(
            FormTranslation::getLanguagesCanBeAddedToTranslation($form->getID()),
            array_diff_key(
                Dropdown::getLanguages(),
                ['fr_FR' => '', 'es_ES' => '']
            )
        );
    }

    public function testGetTranslationForKey()
    {
        $form = $this->createFormWithTranslations();

        foreach (['fr_FR', 'es_ES'] as $language) {
            foreach ($form->listTranslationsHandlers() as $handlers) {
                foreach ($handlers as $handler) {
                    $this->assertEquals(
                        $handler->getKey() . ' in ' . $language,
                        FormTranslation::getForItemKeyAndLanguage($handler->getItem(), $handler->getKey(), $language)
                            ->getTranslation()
                    );
                }
            }
        }
    }

    public function testTranslate()
    {
        global $CFG_GLPI;

        $form = $this->createFormWithTranslations();
        $handlers = array_merge(...array_values($form->listTranslationsHandlers()));

        $this->login();

        // Set the default language
        $_SESSION['glpilanguage'] = $CFG_GLPI['language'];
        foreach ($handlers as $handler) {
            $this->assertEquals(
                FormTranslation::translate($handler->getItem(), $handler->getKey()),
                $handler->getValue()
            );
        }

        // Set the language to French
        $_SESSION['glpilanguage'] = 'fr_FR';
        foreach ($handlers as $handler) {
            $this->assertEquals(
                FormTranslation::translate($handler->getItem(), $handler->getKey()),
                $handler->getKey() . ' in ' . Session::getLanguage()
            );
        }

        // Set the language to Spanish
        $_SESSION['glpilanguage'] = 'es_ES';
        foreach ($handlers as $handler) {
            $this->assertEquals(
                FormTranslation::translate($handler->getItem(), $handler->getKey()),
                $handler->getKey() . ' in ' . Session::getLanguage()
            );
        }

        // Set the language to Portuguese to test the fallback to the default language
        $_SESSION['glpilanguage'] = 'pt_PT';
        foreach ($handlers as $handler) {
            $this->assertEquals(
                FormTranslation::translate($handler->getItem(), $handler->getKey()),
                $handler->getValue()
            );
        }
    }

    public function testListTranslationHandlersFromFormWithHorizontalLayout(): void
    {
        $form_builder = (new FormBuilder())
            ->addQuestion(
                name: 'First question in first section',
                type: QuestionTypeShortText::class,
                horizontal_rank: 0,
            )
            ->addQuestion(
                name: 'Second question in first section',
                type: QuestionTypeShortText::class,
                horizontal_rank: 1,
            )
            ->addQuestion(
                name: 'Third question in first section',
                type: QuestionTypeShortText::class,
                horizontal_rank: 2,
            );
        $form = $this->createForm($form_builder);
        $this->assertCount(
            4, // Form name + 3 question titles
            $form->listTranslationsHandlers()
        );
    }

    public function createFormWithTranslations(): Form
    {
        $form_builder = (new FormBuilder())
            ->addSection('First Section')
            ->addComment('First comment in first section')
            ->addQuestion(
                'First question in first section',
                QuestionTypeShortText::class
            )
            ->addSection('Second Section')
            ->addQuestion(
                'First question in second section',
                QuestionTypeDropdown::class,
                '',
                json_encode((new QuestionTypeDropdownExtraDataConfig([
                    '123456789' => 'Option 1',
                    '987654321' => 'Option 2',
                ]))->jsonSerialize())
            );

        $form = $this->createForm($form_builder);

        $handlers = $form->listTranslationsHandlers();
        array_walk_recursive(
            $handlers,
            function ($handler) {
                $this->addTranslationToForm(
                    $handler->getItem(),
                    'fr_FR',
                    $handler->getKey(),
                    $handler->getKey() . ' in fr_FR'
                );

                $this->addTranslationToForm(
                    $handler->getItem(),
                    'es_ES',
                    $handler->getKey(),
                    $handler->getKey() . ' in es_ES'
                );
            }
        );

        return $form;
    }

    public function testTranslationsCascadeDeleteWhenDeletingTranslatableElements()
    {
        $form = $this->createFormWithTranslations();

        // Get all translatable handlers before deletion
        $handlers = $form->listTranslationsHandlers();
        $translation_items = [];

        // Collect all translated items and their translations
        array_walk_recursive(
            $handlers,
            function ($handler) use (&$translation_items) {
                $translations = FormTranslation::getTranslationsForItem($handler->getItem());
                if (!empty($translations)) {
                    $translation_items[] = $handler->getItem();
                }
            }
        );

        // Verify that we have translations before deletion
        $this->assertNotEmpty($translation_items, 'No translations found to test cascade deletion');

        // Delete the form
        $success = $form->delete(['id' => $form->getID()], true); // Force purge
        $this->assertTrue($success, "Failed to delete form with ID " . $form->getID());

        // Verify that all translations have been cascade deleted
        foreach ($translation_items as $item) {
            $item_type = $item->getType();
            $item_id = $item->getID();

            // Verify that all translations for this item have been cascade deleted
            $remaining_translations = FormTranslation::getTranslationsForItem($item);
            $this->assertEmpty(
                $remaining_translations,
                "Translations were not cascade deleted for {$item_type} {$item_id}. Found " . count($remaining_translations) . " remaining translations."
            );

            // Also verify by direct database query
            $translation_count = countElementsInTable(
                FormTranslation::getTable(),
                [
                    FormTranslation::$itemtype => $item_type,
                    FormTranslation::$items_id => $item_id,
                ]
            );
            $this->assertEquals(
                0,
                $translation_count,
                "Database still contains {$translation_count} translations for deleted {$item_type} {$item_id}"
            );
        }
    }

    public static function emptyDefaultValuesAreNotListedProvider(): iterable
    {
        $types = QuestionTypesManager::getInstance()->getQuestionTypes();
        foreach ($types as $type) {
            if ($type instanceof TranslationAwareQuestionType) {
                // Manually set specific extra data, we can't compute it
                // automatically
                $extra_data = match (true) {
                    default => null,
                    $type instanceof AbstractQuestionTypeSelectable => new QuestionTypeSelectableExtraDataConfig(options: []),
                };

                yield [
                    'type' => $type,
                    'extra_data' => $extra_data !== null ? json_encode($extra_data) : $extra_data,
                ];
            }
        }
    }

    #[DataProvider('emptyDefaultValuesAreNotListedProvider')]
    public function testEmptyDefaultValuesAreNotListed(
        TranslationAwareQuestionType $type,
        ?string $extra_data,
    ): void {
        // Arrange: create a form with a question without default value
        $builder = new FormBuilder("My form");
        $builder->addQuestion("My question", $type::class, "", $extra_data);
        $form = $this->createForm($builder);

        // Act: get translations handler for the question
        $question = Question::getById($this->getQuestionId($form, "My question"));
        $handlers = $type->listTranslationsHandlers($question);

        // Assert: no default value handlers should exist for the empty question
        $key = Question::TRANSLATION_KEY_DEFAULT_VALUE;
        $handlers = array_filter(
            $handlers,
            fn(TranslationHandler $h) => $h->getKey() == $key
        );
        $this->assertEmpty($handlers);
    }
}
