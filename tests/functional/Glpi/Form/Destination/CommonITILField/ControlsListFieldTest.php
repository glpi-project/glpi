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

namespace tests\units\Glpi\Form\Destination\CommonITILField;

use Glpi\Form\Destination\CommonITILField\ControlsListField;
use Glpi\Form\Destination\FormDestinationChange;
use Glpi\Form\Tag\AnswerTagProvider;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\FormTesterTrait;

final class ControlsListFieldTest extends DbTestCase
{
    use FormTesterTrait;

    public function testContentWithoutTagsForChange(): void
    {
        // Arrange: create a form with a simple text config without tags
        $form = $this->createAndGetFormWithFirstAndLastNameQuestions(
            FormDestinationChange::class
        );
        $this->setDestinationFieldConfig(
            $form,
            ControlsListField::getKey(),
            "No controls",
        );

        // Act: submit form
        $change = $this->sendFormAndGetCreatedChange($form, [
            "First name" => "John",
            "Last name"  => "Smith",
        ]);

        // Assert: the impact field should contain the raw string we configured
        $this->assertEquals("No controls", $change->fields['controlistcontent']);
    }

    public function testContentWithTagsForChange(): void
    {
        // Arrange: create a form with tags
        $form = $this->createAndGetFormWithFirstAndLastNameQuestions(
            FormDestinationChange::class
        );
        $tag_manager = new AnswerTagProvider();
        $tags = $tag_manager->getTags($form);
        $this->setDestinationFieldConfig(
            $form,
            ControlsListField::getKey(),
            "Controls will be done by: {$tags[0]->html} {$tags[1]->html}"
        );

        // Act: submit form
        $change = $this->sendFormAndGetCreatedChange($form, [
            "First name" => "John",
            "Last name"  => "Smith",
        ]);

        // Assert: the impact field should contain the raw string we configured
        $this->assertEquals(
            "Controls will be done by: John Smith",
            $change->fields['controlistcontent']
        );
    }
}
