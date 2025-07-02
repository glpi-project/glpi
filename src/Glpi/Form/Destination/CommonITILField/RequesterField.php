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

namespace Glpi\Form\Destination\CommonITILField;

use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeEmail;
use Glpi\Form\QuestionType\QuestionTypeRequester;
use Override;
use Session;

final class RequesterField extends ITILActorField
{
    #[Override]
    public function getAllowedQuestionType(): array
    {
        return [new QuestionTypeRequester(), new QuestionTypeEmail()];
    }

    #[Override]
    public function getActorType(): string
    {
        return 'requester';
    }

    #[Override]
    public function getLabel(): string
    {
        return _n('Requester', 'Requesters', Session::getPluralNumber());
    }

    #[Override]
    public function getConfigClass(): string
    {
        return RequesterFieldConfig::class;
    }

    #[Override]
    public function getDefaultConfig(Form $form): RequesterFieldConfig
    {
        return new RequesterFieldConfig(
            [ITILActorFieldStrategy::FORM_FILLER],
        );
    }

    #[Override]
    public function getWeight(): int
    {
        return 100;
    }
}
