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
use Glpi\Form\QuestionType\QuestionTypeDropdown;
use Glpi\Form\QuestionType\QuestionTypeDropdownExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Form\FormTranslation;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Session;

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
                        FormTranslation::getTranslation($handler->getParentItem(), $handler->getKey(), $language)
                            ->getOneTranslation()
                    );
                }
            }
        }
    }

    public function testGetLocalizedTranslationForKey()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $form = $this->createFormWithTranslations();
        $handlers = array_merge(...array_values($form->listTranslationsHandlers()));

        $this->login();

        // Set the default language
        $_SESSION['glpilanguage'] = $CFG_GLPI['language'];
        foreach ($handlers as $handler) {
            $this->assertEquals(
                FormTranslation::getLocalizedTranslationForKey($handler->getParentItem(), $handler->getKey()),
                $handler->getValue()
            );
        }

        // Set the language to French
        $_SESSION['glpilanguage'] = 'fr_FR';
        foreach ($handlers as $handler) {
            $this->assertEquals(
                FormTranslation::getLocalizedTranslationForKey($handler->getParentItem(), $handler->getKey()),
                $handler->getKey() . ' in ' . Session::getLanguage()
            );
        }

        // Set the language to Spanish
        $_SESSION['glpilanguage'] = 'es_ES';
        foreach ($handlers as $handler) {
            $this->assertEquals(
                FormTranslation::getLocalizedTranslationForKey($handler->getParentItem(), $handler->getKey()),
                $handler->getKey() . ' in ' . Session::getLanguage()
            );
        }

        // Set the language to Portuguese to test the fallback to the default language
        $_SESSION['glpilanguage'] = 'pt_PT';
        foreach ($handlers as $handler) {
            $this->assertEquals(
                FormTranslation::getLocalizedTranslationForKey($handler->getParentItem(), $handler->getKey()),
                $handler->getValue()
            );
        }
    }

    public function testGetDefaultTranslation()
    {
        $form = $this->createFormWithTranslations();
        $handlers = array_merge(...array_values($form->listTranslationsHandlers()));

        foreach ($handlers as $handler) {
            $this->assertEquals(
                FormTranslation::getDefaultTranslation($handler->getParentItem(), $handler->getKey()),
                $handler->getValue()
            );
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

        $handlers = $form->listTranslationsHandlers();
        array_walk_recursive(
            $handlers,
            function ($handler) {
                $this->addTranslationToForm(
                    $handler->getParentItem(),
                    'fr_FR',
                    $handler->getKey(),
                    $handler->getKey() . ' in fr_FR'
                );

                $this->addTranslationToForm(
                    $handler->getParentItem(),
                    'es_ES',
                    $handler->getKey(),
                    $handler->getKey() . ' in es_ES'
                );
            }
        );

        return $form;
    }
}
