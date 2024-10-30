<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

use Glpi\Form\Form;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypeCheckbox;
use Glpi\Form\Section;
use Glpi\Form\Translation\FormTranslation;
use Glpi\Form\Translation\Serializer\FormTranslationSerializer;
use Glpi\Form\Translation\Specification\FormTranslationsSpecification;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;

class FormTranslationSerializerTest extends \DbTestCase
{
    use FormTesterTrait;

    private static FormTranslationSerializer $serializer;

    public static function setUpBeforeClass(): void
    {
        self::$serializer = new FormTranslationSerializer();
        parent::setUpBeforeClass();
    }

    public function testGetTranslationsFromJson(): void
    {
        $form = $this->createFormWithTranslations();
        $formTranslation = current(array_filter(
            FormTranslation::getFormTranslationsForForm($form->getID()),
            fn (FormTranslation $formTranslation) => $formTranslation->fields['language'] === 'fr_FR'
        ));
        $translations = self::$serializer->getTranslationsFromJson($formTranslation->fields['translations']);

        $this->assertInstanceOf(FormTranslationsSpecification::class, $translations);
        $this->assertCount(7, $translations->translations);
        $this->assertEquals(
            array_map(fn($translation) => $translation->translation, $translations->translations),
            [
                'Formulaire de test',
                'Description du formulaire de test',
                'Première section',
                'Description de la première section',
                'Première question',
                'Description de la première question',
                'Case à cocher 1',
            ]
        );
        $this->assertEquals(
            array_map(fn($translation) => $translation->key, $translations->translations),
            [
                Form::KEY_PREFIX_NAME                   . '-' . $form->getID(),
                Form::KEY_PREFIX_DESCRIPTION            . '-' . $form->getID(),
                Section::KEY_PREFIX_NAME                . '-' . $this->getSectionId($form, 'First section'),
                Section::KEY_PREFIX_DESCRIPTION         . '-' . $this->getSectionId($form, 'First section'),
                Question::KEY_PREFIX_NAME               . '-' . $this->getQuestionId($form, 'First question'),
                Question::KEY_PREFIX_DESCRIPTION        . '-' . $this->getQuestionId($form, 'First question'),
                QuestionTypeCheckbox::KEY_PREFIX_OPTION . '-' . $this->getQuestionId($form, 'First question') . '-123',
            ]
        );
    }

    public function testGetJsonFromTranslations(): void
    {
        $form = $this->createFormWithTranslations();
        $formTranslation = current(array_filter(
            FormTranslation::getFormTranslationsForForm($form->getID()),
            fn (FormTranslation $formTranslation) => $formTranslation->fields['language'] === 'fr_FR'
        ));
        $translations = self::$serializer->getTranslationsFromJson($formTranslation->fields['translations']);

        $result = self::$serializer->getJsonFromTranslations($translations);
        $resultArray = json_decode($result, true);
        foreach ($resultArray['translations'] as &$translation) {
            unset($translation['last_update']);
        }
        $result = json_encode($resultArray);

        $expectedJson = json_encode([
            'translations' => [
                ['key' => Form::KEY_PREFIX_NAME . '-' . $form->getID(), 'translation' => 'Formulaire de test'],
                ['key' => Form::KEY_PREFIX_DESCRIPTION . '-' . $form->getID(), 'translation' => 'Description du formulaire de test'],
                ['key' => Section::KEY_PREFIX_NAME . '-' . $this->getSectionId($form, 'First section'), 'translation' => 'Première section'],
                ['key' => Section::KEY_PREFIX_DESCRIPTION . '-' . $this->getSectionId($form, 'First section'), 'translation' => 'Description de la première section'],
                ['key' => Question::KEY_PREFIX_NAME . '-' . $this->getQuestionId($form, 'First question'), 'translation' => 'Première question'],
                ['key' => Question::KEY_PREFIX_DESCRIPTION . '-' . $this->getQuestionId($form, 'First question'), 'translation' => 'Description de la première question'],
                ['key' => QuestionTypeCheckbox::KEY_PREFIX_OPTION . '-' . $this->getQuestionId($form, 'First question') . '-123', 'translation' => 'Case à cocher 1'],
            ]
        ]);

        $this->assertJsonStringEqualsJsonString($expectedJson, $result);
    }

    public function testSetTranslation(): void
    {
        $form = $this->createFormWithTranslations();
        $formTranslation = current(array_filter(
            FormTranslation::getFormTranslationsForForm($form->getID()),
            fn (FormTranslation $formTranslation) => $formTranslation->fields['language'] === 'fr_FR'
        ));
        $translations = self::$serializer->getTranslationsFromJson($formTranslation->fields['translations']);

        self::$serializer->setTranslation($translations, 'test_key', 'test_translation');

        $this->assertCount(8, $translations->translations);
        $this->assertEquals('test_key', $translations->translations[7]->key);
        $this->assertEquals('test_translation', $translations->translations[7]->translation);
    }

    public function testRemoveTranslation(): void
    {
        $form = $this->createFormWithTranslations();
        $formTranslation = current(array_filter(
            FormTranslation::getFormTranslationsForForm($form->getID()),
            fn (FormTranslation $formTranslation) => $formTranslation->fields['language'] === 'fr_FR'
        ));
        $translations = self::$serializer->getTranslationsFromJson($formTranslation->fields['translations']);

        self::$serializer->removeTranslation($translations, Form::KEY_PREFIX_NAME . '-' . $form->getID());

        $this->assertCount(6, $translations->translations);
    }

    public function testGetTranslationForKey(): void
    {
        $form = $this->createFormWithTranslations();
        $formTranslation = current(array_filter(
            FormTranslation::getFormTranslationsForForm($form->getID()),
            fn (FormTranslation $formTranslation) => $formTranslation->fields['language'] === 'fr_FR'
        ));
        $translations = self::$serializer->getTranslationsFromJson($formTranslation->fields['translations']);

        $result = self::$serializer->getTranslationForKey($translations, Form::KEY_PREFIX_NAME . '-' . $form->getID());

        $this->assertEquals('Formulaire de test', $result);
    }

    public function testGetTranslationForKeyReturnsNullIfNotFound(): void
    {
        $form = $this->createFormWithTranslations();
        $formTranslation = current(array_filter(
            FormTranslation::getFormTranslationsForForm($form->getID()),
            fn (FormTranslation $formTranslation) => $formTranslation->fields['language'] === 'fr_FR'
        ));
        $translations = self::$serializer->getTranslationsFromJson($formTranslation->fields['translations']);

        $result = self::$serializer->getTranslationForKey($translations, 'non_existent_key');

        $this->assertNull($result);
    }

    public function createFormWithTranslations(): Form
    {
        $builder = (new FormBuilder("Test form"))->addSection('First section', 'First section description')
            ->addQuestion(
                'First question',
                QuestionTypeCheckbox::class,
                '123',
                json_encode([
                    'options' => [
                        123 => 'Checkbox 1',
                    ]
                ]),
            );

        $form = $this->createForm($builder);

        $this->createItem(FormTranslation::class, [
            'forms_forms_id' => $form->getID(),
            'language' => 'fr_FR',
            'translations' => [
                Form::KEY_PREFIX_NAME . '-' . $form->getID() => 'Formulaire de test',
                Form::KEY_PREFIX_DESCRIPTION . '-' . $form->getID() => 'Description du formulaire de test',
                Section::KEY_PREFIX_NAME . '-' . $this->getSectionId($form, 'First section') => 'Première section',
                Section::KEY_PREFIX_DESCRIPTION . '-' . $this->getSectionId($form, 'First section') => 'Description de la première section',
                Question::KEY_PREFIX_NAME . '-' . $this->getQuestionId($form, 'First question') => 'Première question',
                Question::KEY_PREFIX_DESCRIPTION . '-' . $this->getQuestionId($form, 'First question') => 'Description de la première question',
                QuestionTypeCheckbox::KEY_PREFIX_OPTION . '-' . $this->getQuestionId($form, 'First question') . '-123' => 'Case à cocher 1',
            ]
        ], ['translations']);

        return $form;
    }
}
