<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

namespace Glpi\System\Requirement;

/**
 * @since 9.5.0
 */
interface RequirementInterface
{
    /**
     * Get the title of the requirement.
     *
     * @return string
     */
    public function getTitle(): string;

    /**
     * Get the description of the requirement.
     *
     * @return null|string
     */
    public function getDescription(): ?string;

    /**
     * Get the validation messages of the requirement.
     *
     * @return string[]
     */
    public function getValidationMessages(): array;

    /**
     * Indicates if requirement is missing on system.
     *
     * @return bool
     */
    public function isMissing(): bool;

    /**
     * Indicates if requirement is considered as optional.
     *
     * @return bool
     */
    public function isOptional(): bool;

    /**
     * Indicates if requirement is considered as out of context
     * (i.e. system is not compatible).
     *
     * @return bool
     */
    public function isOutOfContext(): bool;

    /**
     * Indicates if requirement is validated on system.
     *
     * @return bool
     */
    public function isValidated(): bool;
}
