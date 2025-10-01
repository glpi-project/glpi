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

use Glpi\Form\Destination\HasFieldWithQuestionId;
use Override;

#[HasFieldWithQuestionId(self::SPECIFIC_QUESTION_IDS, is_array: true)]
final class RequesterFieldConfig extends ITILActorFieldConfig
{
    #[Override]
    public static function jsonDeserialize(array $data): self
    {
        $strategies = array_map(
            fn(string $strategy) => ITILActorFieldStrategy::tryFrom($strategy),
            $data[self::STRATEGIES] ?? []
        );
        if ($strategies === []) {
            $strategies = [ITILActorFieldStrategy::FORM_FILLER];
        }

        return new self(
            strategies: $strategies,
            specific_itilactors_ids: $data[self::SPECIFIC_ITILACTORS_IDS] ?? [],
            specific_question_ids: $data[self::SPECIFIC_QUESTION_IDS] ?? [],
        );
    }
}
