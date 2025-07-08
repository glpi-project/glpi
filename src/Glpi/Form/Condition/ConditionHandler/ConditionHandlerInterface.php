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

namespace Glpi\Form\Condition\ConditionHandler;

use Glpi\Form\Condition\ConditionData;
use Glpi\Form\Condition\ValueOperator;

interface ConditionHandlerInterface
{
    /** @return ValueOperator[] */
    public function getSupportedValueOperators(): array;

    /**
     * Path to a valid twig template.
     *
     * The template must be able to display the needed input to represent this
     * condition value, using the following parameters:
     * - input_value: the value of the input that will be displayed
     * - input_name: the name that must be applied to the input
     * - input_label: the label of the input
     *
     * It will also receive any parameters returned by getTemplateParameters().
     *
     * A specific `data-glpi-conditions-editor-value` attribute must be added to
     * the input to allow the editor to target this input when needed.
     */
    public function getTemplate(): ?string;

    /**
     * Returns an array of parameters that will be passed to the template
     * defined in getTemplate().
     *
     * @param ConditionData $condition
     */
    public function getTemplateParameters(ConditionData $condition): array;

    public function applyValueOperator(
        mixed $a,
        ValueOperator $operator,
        mixed $b,
    ): bool;
}
