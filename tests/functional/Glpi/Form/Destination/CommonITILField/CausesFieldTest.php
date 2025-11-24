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

namespace tests\units\Glpi\Form\Destination\CommonITILField;

use DbTestCase;
use Glpi\Form\Destination\CommonITILField\CausesField;
use Glpi\Form\Destination\FormDestinationProblem;
use Glpi\Form\Tag\AnswerTagProvider;
use Glpi\Tests\FormTesterTrait;

final class CausesFieldTest extends DbTestCase
{
    use FormTesterTrait;

    public function testContentWithoutTagsForProblem(): void
    {
        // Arrange: create a form with a simple text config without tags
        $form = $this->createAndGetFormWithFirstAndLastNameQuestions(
            FormDestinationProblem::class
        );
        $this->setDestinationFieldConfig(
            $form,
            CausesField::getKey(),
            "Unknown cause"
        );

        // Act: submit form
        $change = $this->sendFormAndGetCreatedProblem($form, [
            "First name" => "John",
            "Last name"  => "Smith",
        ]);

        // Assert: the impact field should contain the raw string we configured
        $this->assertEquals("Unknown cause", $change->fields['causecontent']);
    }

    public function testContentWithTagsForProblem(): void
    {
        // Arrange: create a form with tags
        $form = $this->createAndGetFormWithFirstAndLastNameQuestions(
            FormDestinationProblem::class
        );
        $tag_manager = new AnswerTagProvider();
        $tags = $tag_manager->getTags($form);
        $this->setDestinationFieldConfig(
            $form,
            CausesField::getKey(),
            "Caused by: {$tags[0]->html} {$tags[1]->html}"
        );

        // Act: submit form
        $change = $this->sendFormAndGetCreatedProblem($form, [
            "First name" => "John",
            "Last name"  => "Smith",
        ]);

        // Assert: the impact field should contain the raw string we configured
        $this->assertEquals(
            "Caused by: John Smith",
            $change->fields['causecontent']
        );
    }
}
