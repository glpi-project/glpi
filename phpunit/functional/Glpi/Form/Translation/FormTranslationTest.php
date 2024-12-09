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

namespace tests\units\Glpi\Form;

use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeDropdown;
use Glpi\Form\QuestionType\QuestionTypeDropdownExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Form\Translation\FormTranslation;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Session;

class FormTranslationTest extends \DbTestCase
{
    use FormTesterTrait;

    public function testGetFormTranslationsForForm()
    {
        $form = $this->createFormWithTranslations();

        $this->assertEquals(
            count(FormTranslation::getFormTranslationsForForm($form->getID())),
            2
        );
    }

    public function testGetTranslationForKey()
    {
        $form = $this->createFormWithTranslations();
        $form_translations = FormTranslation::getFormTranslationsForForm($form->getID());
        $handlers = $form->listFormTranslationsHandlers();
        $translation_keys = [];
        array_walk_recursive(
            $handlers,
            function ($handler) use (&$translation_keys) {
                $translation_keys[] = $handler->getKey();
            }
        );

        foreach ($form_translations as $form_translation) {
            foreach ($translation_keys as $key) {
                $this->assertEquals(
                    $key . ' in ' . $form_translation->fields['language'],
                    $form_translation->getTranslationForKey($key)
                );
            }
        }
    }

    public function testGetLocalizedTranslationForKey()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $form = $this->createFormWithTranslations();
        $handlers = $form->listFormTranslationsHandlers();
        $translation_keys = [];
        array_walk_recursive(
            $handlers,
            function ($handler) use (&$translation_keys) {
                $translation_keys[$handler->getValue()] = $handler->getKey();
            }
        );

        $this->login();

        // Set the default language
        $_SESSION['glpilanguage'] = $CFG_GLPI['language'];
        foreach ($translation_keys as $value => $key) {
            $this->assertEquals(
                FormTranslation::getLocalizedTranslationForKey($form->getID(), $key),
                $value
            );
        }

        // Set the language to French
        $_SESSION['glpilanguage'] = 'fr_FR';
        foreach ($translation_keys as $key) {
            $this->assertEquals(
                FormTranslation::getLocalizedTranslationForKey($form->getID(), $key),
                $key . ' in ' . Session::getLanguage()
            );
        }

        // Set the language to Spanish
        $_SESSION['glpilanguage'] = 'es_ES';
        foreach ($translation_keys as $key) {
            $this->assertEquals(
                FormTranslation::getLocalizedTranslationForKey($form->getID(), $key),
                $key . ' in ' . Session::getLanguage()
            );
        }

        // Set the language to Portuguese to test the fallback to the default language
        $_SESSION['glpilanguage'] = 'pt_PT';
        foreach ($translation_keys as $value => $key) {
            $this->assertEquals(
                FormTranslation::getLocalizedTranslationForKey($form->getID(), $key),
                $value
            );
        }
    }

    public function testGetDefaultTranslation()
    {
        $form = $this->createFormWithTranslations();
        $form_translations = FormTranslation::getFormTranslationsForForm($form->getID());
        $handlers = $form->listFormTranslationsHandlers();
        $translation_keys = [];
        array_walk_recursive(
            $handlers,
            function ($handler) use (&$translation_keys) {
                $translation_keys[$handler->getValue()] = $handler->getKey();
            }
        );

        foreach ($form_translations as $form_translation) {
            foreach ($translation_keys as $value => $key) {
                $this->assertEquals(
                    $value,
                    $form_translation->getDefaultTranslation($form->getID(), $key)
                );
            }
        }
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

        $handlers = $form->listFormTranslationsHandlers();
        array_walk_recursive(
            $handlers,
            function ($handler) use ($form) {
                $this->addTranslationToForm(
                    $form,
                    'fr_FR',
                    $handler->getKey(),
                    $handler->getKey() . ' in fr_FR'
                );

                $this->addTranslationToForm(
                    $form,
                    'es_ES',
                    $handler->getKey(),
                    $handler->getKey() . ' in es_ES'
                );
            }
        );

        return $form;
    }
}
