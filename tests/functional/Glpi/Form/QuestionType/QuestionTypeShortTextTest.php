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

namespace tests\units\Glpi\Form\QuestionType;

use Glpi\Form\Destination\CommonITILField\SimpleValueConfig;
use Glpi\Form\Destination\CommonITILField\TitleField;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Form\Tag\AnswerTagProvider;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;

final class QuestionTypeShortTextTest extends DbTestCase
{
    use FormTesterTrait;

    public function testShortTextAnswerIsDisplayedInTicketDescription(): void
    {
        $builder = new FormBuilder();
        $builder->addQuestion("First name", QuestionTypeShortText::class);
        $form = $this->createForm($builder);

        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            "First name" => "John",
        ]);

        $this->assertStringContainsString(
            "1) First name: John",
            strip_tags($ticket->fields['content']),
        );
    }

    public function testLongAnswerUsedAsTitleIsTruncatedTo255Characters(): void
    {
        $builder = new FormBuilder();
        $builder->addQuestion("Title", QuestionTypeShortText::class);
        $form = $this->createForm($builder);
        $question = current($form->getQuestions());

        // Configure the ticket title to be sourced from the form answer
        $this->setDestinationFieldConfig(
            form: $form,
            key: TitleField::getKey(),
            config: new SimpleValueConfig((new AnswerTagProvider())->getTagForQuestion($question)->html)
        );

        // Submit with an answer exceeding VARCHAR(255) — used to throw a DB exception
        $long_answer = str_repeat('a', 300);
        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            "Title" => $long_answer,
        ]);

        $this->assertSame(255, mb_strlen($ticket->fields['name']));
    }
}
