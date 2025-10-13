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
use Glpi\Form\Condition\ConditionHandler\NumberConditionHandler;
use Glpi\Form\Condition\UsedAsCriteriaInterface;
use Override;

final class QuestionTypeNumber extends AbstractQuestionTypeShortAnswer implements UsedAsCriteriaInterface, CustomMandatoryMessageInterface
{
    #[Override]
    public function getInputType(): string
    {
        return 'number';
    }

    #[Override]
    public function getName(): string
    {
        return __("Number");
    }

    #[Override]
    public function getIcon(): string
    {
        return 'ti ti-number-123';
    }

    #[Override]
    public function getWeight(): int
    {
        return 30;
    }

    #[Override]
    public function getInputAttributes(): array
    {
        return ['step' => 'any'];
    }

    #[Override]
    public function getConditionHandlers(
        ?JsonFieldInterface $question_config
    ): array {
        return array_merge(parent::getConditionHandlers($question_config), [new NumberConditionHandler()]);
    }

    #[Override]
    public function getCustomMandatoryErrorMessage(): string
    {
        // On some browsers, filling text into a `number` input is allowed but
        // the payload will be an empty string on the backend.
        // In this case, the default mandatory message is not clear for the
        // user because the input is filled on the client.
        // The server has no idea this is the case because it receives an
        // empty string.
        // The simplest way to deal with this is to use a generic message that
        // work for both cases (missing or wrong value).
        return __('Please enter a valid number');
    }
}
