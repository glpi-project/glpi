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

use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\Condition\ConditionHandler\ConditionHandlerInterface;

/**
 * Items that implements this interface can be used as a criteria in a condition.
 */
interface UsedAsCriteriaInterface
{
    /**
     * Get the condition handlers that can be used with this item.
     *
     * @param JsonFieldInterface|null $question_config The question config
     * @return array<ConditionHandlerInterface> The condition handlers
     */
    public function getConditionHandlers(
        ?JsonFieldInterface $question_config
    ): array;

    /**
     * Get the supported value operators for this item.
     *
     * @param JsonFieldInterface|null $question_config The question config
     * @return array<ValueOperator> The supported value operators
     */
    public function getSupportedValueOperators(
        ?JsonFieldInterface $question_config
    ): array;
}
