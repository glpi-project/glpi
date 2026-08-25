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

use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypeFile;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use PHPUnit\Framework\Attributes\DataProvider;

final class QuestionTypeFileTest extends DbTestCase
{
    use FormTesterTrait;

    private function createFileQuestion(): Question
    {
        $builder = new FormBuilder();
        $builder->addQuestion("File", QuestionTypeFile::class);
        $form = $this->createForm($builder);

        $questions = array_filter(
            $form->getQuestions(),
            fn($question) => $question->fields['name'] === "File"
        );

        $question = array_pop($questions);

        $this->assertInstanceOf(Question::class, $question, 'File question must be created');

        return $question;
    }

    public static function providerRenderMethod(): iterable
    {
        yield 'end user template' => ['renderEndUserTemplate'];
        yield 'administration template' => ['renderAdministrationTemplate'];
    }

    #[DataProvider('providerRenderMethod')]
    public function testTemplateRendersFileInputWithMultipleAttribute(string $render_method): void
    {
        $question = $this->createFileQuestion();

        $html = (new QuestionTypeFile())->$render_method($question);

        $this->assertStringContainsString("type='file'", $html, 'File input must be of type file');
        $this->assertStringContainsString("multiple='multiple'", $html, 'File input must allow selecting multiple files via the native file picker');
    }
}
