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

namespace Glpi\Form;

use Glpi\Form\QuestionType\QuestionTypeInterface;
use JsonSerializable;

final readonly class Answer implements JsonSerializable
{
    private int $question_id;
    private int $section_id;
    private string $question_label;
    private string $raw_question_type;
    private mixed $raw_extra_data;
    private mixed $raw_answer;

    public function __construct(Question $question, mixed $raw_answer)
    {
        $this->question_id       = $question->fields['id'];
        $this->section_id        = $question->fields['forms_sections_id'];
        $this->question_label    = $question->fields['name'];
        $this->raw_question_type = $question->fields['type'];
        $this->raw_extra_data    = $question->fields['extra_data'];
        $this->raw_answer        = $raw_answer;
    }

    public static function fromDecodedJsonData(array $data): self
    {
        if (
            !isset(
                $data['question_id'],
                $data['section_id'],
                $data['question_label'],
                $data['raw_question_type'],
                $data['raw_answer'],
            )
        ) {
            throw new \InvalidArgumentException('Invalid JSON data');
        }

        $question                              = new Question();
        $question->fields['id']                = $data['question_id'];
        $question->fields['forms_sections_id'] = $data['section_id'];
        $question->fields['name']              = $data['question_label'];
        $question->fields['type']              = $data['raw_question_type'];
        $question->fields['extra_data']        = $data['raw_extra_data'] ?? null;

        return new self($question, $data['raw_answer']);
    }

    public function getQuestion(): Question
    {
        $question                              = new Question();
        $question->fields['id']                = $this->getQuestionId();
        $question->fields['forms_sections_id'] = $this->getSectionId();
        $question->fields['name']              = $this->getQuestionLabel();
        $question->fields['type']              = $this->getRawType();
        $question->fields['extra_data']        = $this->getRawExtraData();

        return $question;
    }

    public function getQuestionId(): int
    {
        return $this->question_id;
    }

    public function getSectionId(): int
    {
        return $this->section_id;
    }

    public function getRawAnswer(): mixed
    {
        return $this->raw_answer;
    }

    public function getFormattedAnswer(): ?string
    {
        $type = $this->getType();
        if ($type === null) {
            return null;
        }

        return $type->formatRawAnswer($this->getRawAnswer());
    }

    public function getQuestionLabel(): string
    {
        return $this->question_label;
    }

    public function getRawType(): string
    {
        return $this->raw_question_type;
    }

    public function getType(): ?QuestionTypeInterface
    {
        $type = $this->getRawType();
        if (!is_a($type, QuestionTypeInterface::class, true)) {
            return null;
        }

        return new $type();
    }

    public function getRawExtraData(): mixed
    {
        return $this->raw_extra_data;
    }

    public function jsonSerialize(): array
    {
        return [
            'question_id'       => $this->getQuestionId(),
            'section_id'        => $this->getSectionId(),
            'question_label'    => $this->getQuestionLabel(),
            'raw_question_type' => $this->getRawType(),
            'raw_extra_data'    => $this->getRawExtraData(),
            'raw_answer'        => $this->getRawAnswer(),
        ];
    }
}
