<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

/**
 * Helper class to load all available question types
 */
final class QuestionTypesLoader
{
    /**
     * Internal buffer of computed question types
     * See computeQuestionTypes for the format
     *
     * @var array
     */
    protected array $types;

    public function __construct()
    {
        $this->types = self::computeQuestionTypes();
    }

    /**
     * Get all the available question types
     *
     * @return QuestionTypeInterface[]
     */
    public function getFinalTypes(): array
    {
        $types = [];

        foreach ($this->types as $parent_type_data) {
            array_push($types, ...$parent_type_data['subtypes']);
        }

        return $types;
    }

    /**
     * Get all the available parent questions types
     *
     * @return array
     */
    public function getParentTypes(): array
    {
        $types = [];

        foreach ($this->types as $parent_type_class => $parent_type_data) {
            $types[$parent_type_class] = $parent_type_data['label'];
        }

        return $types;
    }

    /**
     * Get available types for a given parent category
     *
     * @return QuestionTypeInterface[]
     */
    public function getChilden(string $parent_type): array
    {
        return $this->types[$parent_type]['subtypes'];
    }

    /**
     * Return an array of question types instance using the following format:
     * [
     *     'parent_type' => [
     *         'label' => 'Parent type label',
     *         'subtypes' => QuestionTypeInterface[],
     *     ],
     * ]
     *
     * @return array
     */
    protected function computeQuestionTypes(): array
    {
        $types = [];
        $raw_types = [
            // Short answer
            new QuestionTypeShortAnswerText(),
            new QuestionTypeShortAnswerEmail(),
            new QuestionTypeShortAnswerNumber(),

            // long answer
            new QuestionTypeLongAnswer(),
        ];

        // Build types array
        foreach ($raw_types as $type) {
            if (!($type instanceof QuestionTypeInterface)) {
                throw new \Exception(
                    sprintf(
                        "Question type '%s' must implement '%s'",
                        get_class($type),
                        QuestionTypeInterface::class
                    )
                );
            }


            if (!isset($types[$type->getParentType()])) {
                // First type encountering this parent type, init values
                $types[$type->getParentType()] = [
                    'label'    => $type->getParentName(),
                    'subtypes' => [$type],
                ];
            } else {
                // Parent type is already initalized, append the new value
                $types[$type->getParentType()]['subtypes'][] = $type;
            }
        }

        return $types;
    }
}
