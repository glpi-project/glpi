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

namespace Glpi\Form\QuestionType;

use Config;
use Document;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Form\Migration\FormQuestionDataConverterInterface;
use Glpi\Form\Question;
use Override;

final class QuestionTypeFile extends AbstractQuestionType implements FormQuestionDataConverterInterface
{
    #[Override]
    public function prepareEndUserAnswer(Question $question, mixed $answer): mixed
    {
        $form         = $question->getForm();
        $document     = new Document();
        $document_ids = [];
        foreach ($answer as $file) {
            $document_ids[] = $document->add([
                'name'             => sprintf('%s - %s', $form->getName(), $question->getName()),
                'entities_id'      => $form->getEntityID(),
                'is_recursive'     => $form->isRecursive(),
                '_filename'        => [$file],
                '_prefix_filename' => [$_POST['_prefix_' . $question->getEndUserInputName()]],
            ]);
        }

        return $document_ids;
    }

    #[Override]
    public function renderAdministrationTemplate(?Question $question): string
    {
        $template = <<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}

            {{ fields.fileField(
                'default_value',
                '',
                '',
                {
                    'init'           : question is not null ? true: false,
                    'no_label'       : true,
                    'full_width'     : true,
                    'mb'             : '',
                }
            ) }}
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'question'       => $question,
        ]);
    }

    #[Override]
    public function renderAdministrationOptionsTemplate(?Question $question): string
    {
        return '';
    }

    #[Override]
    public function renderEndUserTemplate(Question $question): string
    {
        $template = <<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}

            {{ fields.fileField(
                question.getEndUserInputName(),
                "",
                "",
                {
                    'init'                 : true,
                    'no_label'             : true,
                    'full_width'           : true,
                    'mb'                   : '',
                }
            ) }}
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'question' => $question,
        ]);
    }

    #[Override]
    public function formatRawAnswer(mixed $answer, Question $question): string
    {
        return implode(', ', array_map(
            fn($document_id) => (new Document())->getById($document_id)->fields['filename'],
            $answer
        ));
    }

    #[Override]
    public function getCategory(): QuestionTypeCategoryInterface
    {
        return QuestionTypeCategory::FILE;
    }

    #[Override]
    public function isAllowedForUnauthenticatedAccess(): bool
    {
        return Config::allowUnauthenticatedUploads();
    }

    #[Override]
    public function getTargetQuestionType(array $rawData): string
    {
        return self::class;
    }


    #[Override]
    public function beforeConversion(array $rawData): void {}

    #[Override]
    public function convertDefaultValue(array $rawData): null
    {
        return null;
    }

    #[Override]
    public function convertExtraData(array $rawData): null
    {
        return null;
    }
}
