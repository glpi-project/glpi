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

namespace Glpi\System\Requirement;

/**
 * @since 9.5.0
 */
abstract class AbstractRequirement implements RequirementInterface
{
    /**
     * Flag that indicates if requirement check has already been done.
     *
     * @var bool
     */
    private $has_been_checked = false;

    /**
     * Flag that indicates if requirement is considered as optional.
     *
     * @var bool
     */
    protected $optional = false;

    /**
     * Flag that indicates if requirement is considered as out of context.
     *
     * @var bool
     */
    protected $out_of_context = false;

    /**
     * Requirement title.
     *
     * @var string
     */
    protected $title;

    /**
     * Requirement description.
     *
     * @var string
     */
    protected $description;

    /**
     * Flag that indicates if requirement is validated on system.
     *
     * @var bool
     */
    protected $validated;

    /**
     * Requirement validation message.
     *
     * @var string[]
     */
    protected $validation_messages = [];

    /**
     * Check requirement.
     *
     * This method will be called once before access to any RequirementInterface method
     * and should be used to compute  $validated and $validation_messages properties.
     *
     * @return void
     */
    abstract protected function check();

    /**
     * Run requirement check once.
     *
     * @return void
     */
    private function doCheck()
    {
        if (!$this->has_been_checked) {
            $this->check();
            $this->has_been_checked = true;
        }
    }

    public function getTitle(): string
    {
        $this->doCheck();

        return $this->title;
    }

    public function getDescription(): ?string
    {
        $this->doCheck();

        return $this->description;
    }

    public function getValidationMessages(): array
    {
        $this->doCheck();

        return $this->validation_messages;
    }

    public function isMissing(): bool
    {
        $this->doCheck();

        return true !== $this->validated;
    }

    public function isOptional(): bool
    {
        $this->doCheck();

        return $this->optional;
    }

    public function isOutOfContext(): bool
    {
        $this->doCheck();

        return $this->out_of_context;
    }

    public function isValidated(): bool
    {
        $this->doCheck();

        return true === $this->validated;
    }
}
