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

namespace Glpi\Form\Migration;

use Glpi\Form\QuestionType\QuestionTypeInterface;

interface FormQuestionDataConverterInterface
{
    /**
     * Convert default value
     *
     * @param array $rawData
     * @return mixed
     */
    public function convertDefaultValue(array $rawData): mixed;

    /**
     * Convert extra data
     *
     * @param array $rawData
     * @return mixed
     */
    public function convertExtraData(array $rawData): mixed;

    /**
     * @return class-string<QuestionTypeInterface>
     */
    public function getTargetQuestionType(array $rawData): string;

    /**
     * Allow the converter to run some arbitrary code before we begin converting
     * values.
     *
     * For example, it might be used to create some required database items.
     */
    public function beforeConversion(array $rawData): void;
}
