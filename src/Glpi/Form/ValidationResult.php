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

namespace Glpi\Form;

/**
 * Class for handling form validation results
 */
final class ValidationResult
{
    /**
     * @var bool Indicates if the validation is successful
     */
    private bool $valid;

    /**
     * @var array List of errors found during validation
     */
    private array $errors;

    /**
     * Constructor
     *
     * @param bool  $valid  Indicates if the validation is successful
     * @param array $errors List of errors found during validation
     */
    public function __construct(bool $valid = true, array $errors = [])
    {
        $this->valid = $valid;
        $this->errors = $errors;
    }

    /**
     * Check if the validation is successful
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * Get the list of errors found during validation
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Add an error to the list
     *
     * @param Question $question The question associated with the error
     * @param string   $message  The error message
     * @return void
     */
    public function addError(Question $question, string $message): void
    {
        $error = [
            'question_id'   => $question->getID(),
            'question_name' => $question->getName(),
            'message'       => $message,
        ];

        $this->valid = false;
        $this->addFormattedError($error);
    }

    /**
     * Add an error to the list (already formatted)
     */
    public function addFormattedError(array $formatted_error): void
    {
        $this->valid = false;
        $this->errors[] = $formatted_error;
    }
}
