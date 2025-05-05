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

namespace Glpi\Form\Condition;

use JsonSerializable;
use Override;

final class EngineVisibilityOutput implements JsonSerializable
{
    private bool $form_visibility       = true;
    private array $sections_visibility  = [];
    private array $questions_visibility = [];
    private array $comments_visibility  = [];

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'form_visibility'      => $this->form_visibility,
            'sections_visibility'  => $this->sections_visibility,
            'questions_visibility' => $this->questions_visibility,
            'comments_visibility'  => $this->comments_visibility,
        ];
    }

    public function getNumberOfVisibleSections(): int
    {
        $visible = array_filter(
            $this->sections_visibility,
            fn($is_visible): bool => $is_visible
        );
        return count($visible);
    }

    public function setFormVisibility(bool $is_visible): void
    {
        $this->form_visibility = $is_visible;
    }

    public function setSectionVisibility(int $section_id, bool $is_visible): void
    {
        $this->sections_visibility[$section_id] = $is_visible;
    }

    public function setQuestionVisibility(int $question_id, bool $is_visible): void
    {
        $this->questions_visibility[$question_id] = $is_visible;
    }

    public function setCommentVisibility(int $comment_id, bool $is_visible): void
    {
        $this->comments_visibility[$comment_id] = $is_visible;
    }

    public function isFormVisible(): bool
    {
        return $this->form_visibility;
    }

    public function isSectionVisible(int $section_id): bool
    {
        return $this->sections_visibility[$section_id] ?? false;
    }

    public function isQuestionVisible(int $question_id): bool
    {
        return $this->questions_visibility[$question_id] ?? false;
    }

    public function isCommentVisible(int $comment_id): bool
    {
        return $this->comments_visibility[$comment_id] ?? false;
    }
}
