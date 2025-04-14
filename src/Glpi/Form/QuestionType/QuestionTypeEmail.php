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

use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\Condition\ConditionHandler\ConditionHandlerInterface;
use Glpi\Form\Condition\ConditionHandler\StringConditionHandler;
use Glpi\Form\Condition\UsedAsCriteriaInterface;
use Override;
use Session;

final class QuestionTypeEmail extends AbstractQuestionTypeShortAnswer implements UsedAsCriteriaInterface
{
    #[Override]
    public function getInputType(): string
    {
        return 'email';
    }

    #[Override]
    public function getName(): string
    {
        return _n('Email', 'Emails', Session::getPluralNumber());
    }

    #[Override]
    public function getIcon(): string
    {
        return 'ti ti-mail';
    }

    #[Override]
    public function getWeight(): int
    {
        return 20;
    }

    #[Override]
    public function getConditionHandlers(
        ?JsonFieldInterface $question_config
    ): array {
        return array_merge(parent::getConditionHandlers($question_config), [new StringConditionHandler()]);
    }
}
