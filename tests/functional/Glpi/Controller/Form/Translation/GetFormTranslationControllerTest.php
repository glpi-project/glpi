<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace tests\units\Glpi\Controller\Form\Translation;

use Glpi\Controller\Form\Translation\GetFormTranslationController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use Glpi\Form\Form;
use Glpi\Form\FormTranslation;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

final class GetFormTranslationControllerTest extends DbTestCase
{
    use FormTesterTrait;

    public function testTranslationRowsAreOnlyRenderedOnDemand(): void
    {
        $this->login();

        // Arrange: create a form translated in two languages
        $form = $this->createFormWithTranslations(['fr_FR', 'es_ES']);

        // Act: display the translations tab
        ob_start();
        FormTranslation::displayTabContentForItem($form);
        $crawler = new Crawler(ob_get_clean());

        // Assert: each language has its modal, but the translation rows are not rendered
        foreach (['fr_FR', 'es_ES'] as $language) {
            $modal = $crawler->filter(sprintf('#form-translation-modal-%s', $language));
            $this->assertCount(1, $modal);
            $this->assertCount(1, $modal->filter('[data-glpi-form-translation-content-url]'));
            $this->assertCount(0, $modal->filter('tbody tr'));
        }

        // Act: load the content of the modal of a single language
        $response = (new GetFormTranslationController())(new Request(), $form->getID(), 'fr_FR');
        $crawler = new Crawler($response->getContent());

        // Assert: the translation rows of the requested language are rendered
        $this->assertCount(1, $crawler->filter('[aria-label="Translation row for Form title"]'));
        $this->assertCount(1, $crawler->filter('[aria-label="Translation row for Question name"]'));
        $this->assertCount(1, $crawler->filter('input[value="My form in fr_FR"]'));
        $this->assertCount(0, $crawler->filter('input[value="My form in es_ES"]'));
    }

    public function testLanguageWithoutTranslation(): void
    {
        $this->login();
        $form = $this->createFormWithTranslations(['fr_FR']);

        $this->expectException(NotFoundHttpException::class);
        (new GetFormTranslationController())(new Request(), $form->getID(), 'es_ES');
    }

    public function testInvalidLanguage(): void
    {
        $this->login();
        $form = $this->createFormWithTranslations(['fr_FR']);

        $this->expectException(BadRequestHttpException::class);
        (new GetFormTranslationController())(new Request(), $form->getID(), 'invalid');
    }

    public function testUnknownForm(): void
    {
        $this->login();

        $this->expectException(NotFoundHttpException::class);
        (new GetFormTranslationController())(new Request(), PHP_INT_MAX, 'fr_FR');
    }

    public function testAccessIsDeniedWithoutFormRight(): void
    {
        $this->login();
        $form = $this->createFormWithTranslations(['fr_FR']);
        $_SESSION['glpiactiveprofile']['form'] = 0;

        $this->expectException(AccessDeniedHttpException::class);
        (new GetFormTranslationController())(new Request(), $form->getID(), 'fr_FR');
    }

    /**
     * @param string[] $languages
     */
    private function createFormWithTranslations(array $languages): Form
    {
        $builder = new FormBuilder("My form");
        $builder->addQuestion("My question", QuestionTypeShortText::class);
        $form = $this->createForm($builder);

        foreach ($languages as $language) {
            $this->addTranslationToForm(
                $form,
                $language,
                Form::TRANSLATION_KEY_NAME,
                "My form in $language",
            );
        }

        return $form;
    }
}
