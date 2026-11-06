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

namespace tests\units\Glpi\Controller\Form\Import;

use Glpi\Controller\Form\Import\Step3ResolveIssuesController;
use Glpi\Form\Destination\CommonITILField\ContentField;
use Glpi\Form\Destination\CommonITILField\SimpleValueConfig;
use Glpi\Form\Export\Serializer\FormSerializer;
use Glpi\Form\Form;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Form\Tag\QuestionTagProvider;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use ITILCategory;
use Symfony\Component\HttpFoundation\Request;

final class Step3ResolveIssuesControllerTest extends DbTestCase
{
    use FormTesterTrait;

    public function testFormItemsDropdownValuesContainTheFormName(): void
    {
        // Arrange: two forms with a question of the same name, and another
        // form in an entity that is not visible
        $uid = uniqid();
        $form_a = $this->createFormWithQuestion("Form A $uid", "Shared $uid");
        $form_b = $this->createFormWithQuestion("Form B $uid", "Shared $uid");
        $hidden_form = $this->createForm(
            (new FormBuilder("Hidden form $uid"))
                ->setEntitiesId(getItemByTypeName('Entity', '_test_child_2', true))
                ->setIsRecursive(0)
                ->addQuestion("Shared $uid", QuestionTypeShortText::class)
        );

        // Act: get the replacement values for questions
        $values = (new Step3ResolveIssuesController())->getFormItemsDropdownValues(
            Question::class,
            [
                $this->getTestRootEntity(only_id: true),
                getItemByTypeName('Entity', '_test_child_1', true),
            ],
        );

        // Assert: the questions are labelled with their form name
        $this->assertEquals(
            "Form A $uid > Shared $uid",
            $values[$this->getQuestionId($form_a, "Shared $uid")] ?? null,
        );
        $this->assertEquals(
            "Form B $uid > Shared $uid",
            $values[$this->getQuestionId($form_b, "Shared $uid")] ?? null,
        );
        $this->assertArrayNotHasKey(
            $this->getQuestionId($hidden_form, "Shared $uid"),
            $values,
        );
    }

    public function testFormItemsDropdownValuesAreEmptyForOtherItemtypes(): void
    {
        $values = (new Step3ResolveIssuesController())->getFormItemsDropdownValues(
            ITILCategory::class,
            [$this->getTestRootEntity(only_id: true)],
        );

        $this->assertEquals([], $values);
    }

    public function testResolveIssuesPageShowsFormName(): void
    {
        // Arrange: export a form whose destination uses an ambiguous question
        // of another form
        $this->login();
        $uid = uniqid();
        $form_a = $this->createFormWithQuestion("Form A $uid", "Shared $uid");
        $this->createFormWithQuestion("Form B $uid", "Shared $uid");
        $form = $this->createForm(new FormBuilder("Exported form $uid"));
        $destinations = $form->getDestinations();
        $destination = current($destinations);
        $this->updateItem(
            $destination::class,
            $destination->getId(),
            [
                'config' => [
                    ContentField::getKey() => (new SimpleValueConfig(
                        (new QuestionTagProvider())->getTags($form_a)[0]->html
                    ))->jsonSerialize(),
                ],
            ],
            ["config"],
        );
        $json = (new FormSerializer())->exportFormsToJson([$form])->getJsonContent();

        // Act: display the "Resolve issues" step
        $response = (new Step3ResolveIssuesController())->__invoke(
            Request::create('/Form/Import/ResolveIssues', 'POST', [
                'json'    => $json,
                'form_id' => $form->getID(),
            ])
        );

        // Assert: the replacement values contain the form names
        $this->assertStringContainsString(
            htmlescape("Form A $uid > Shared $uid"),
            $response->getContent(),
        );
    }

    private function createFormWithQuestion(string $form_name, string $question_name): Form
    {
        return $this->createForm(
            (new FormBuilder($form_name))->addQuestion($question_name, QuestionTypeShortText::class)
        );
    }
}
