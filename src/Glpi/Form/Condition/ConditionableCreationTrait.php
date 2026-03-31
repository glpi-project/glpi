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

namespace Glpi\Form\Condition;

use function Safe\json_encode;

trait ConditionableCreationTrait
{
    use ConditionableTrait;

    public function getConfiguredCreationStrategy(): CreationStrategy
    {
        $strategy_value = $this->fields['creation_strategy'] ?? "";
        $strategy = CreationStrategy::tryFrom($strategy_value);
        return $strategy ?? CreationStrategy::ALWAYS_CREATED;
    }

    protected function removeSavedConditionsIfAlwaysCreated(array $input): array
    {
        $strategy_field = 'creation_strategy';
        $condition_field = $this->getConditionsFieldName();

        if (
            isset($input[$strategy_field])
            && $input[$strategy_field] == CreationStrategy::ALWAYS_CREATED->value
        ) {
            $input[$condition_field] = json_encode([]);
        }

        return $input;
    }
}
