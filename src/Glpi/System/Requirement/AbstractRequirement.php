<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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
     * @var bool|null
     */
    protected $optional;

    /**
     * Flag that indicates if requirement is recommended for security reasons.
     *
     * @var bool|null
     */
    protected ?bool $recommended_for_security;

    /**
     * Flag that indicates if requirement is considered as out of context.
     *
     * @var bool|null
     */
    protected $out_of_context;

    /**
     * Requirement title.
     *
     * @var string|null
     */
    protected $title;

    /**
     * Requirement description.
     *
     * @var string|null
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

    public function __construct(
        ?string $title,
        ?string $description = null,
        ?bool $optional = false,
        ?bool $recommended_for_security = false,
        ?bool $out_of_context = false
    ) {
        $this->title = $title;
        $this->description = $description;
        $this->optional = $optional;
        $this->recommended_for_security = $recommended_for_security;
        $this->out_of_context = $out_of_context;
    }

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
        if ($this->title !== null) {
            // No need to run checks if variable is defined by constructor.
            return $this->title;
        }

        $this->doCheck();

        return $this->title ?? '';
    }

    public function getDescription(): ?string
    {
        if ($this->description !== null) {
            // No need to run checks if variable is defined by constructor.
            return $this->description;
        }

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
        if ($this->optional !== null) {
            // No need to run checks if variable is defined by constructor.
            return $this->optional;
        }

        $this->doCheck();

        return $this->optional ?? false;
    }

    public function isRecommendedForSecurity(): bool
    {
        if ($this->recommended_for_security !== null) {
            // No need to run checks if variable is defined by constructor.
            return $this->recommended_for_security;
        }

        $this->doCheck();

        return $this->recommended_for_security ?? false;
    }

    public function isOutOfContext(): bool
    {
        if ($this->out_of_context !== null) {
            // No need to run checks if variable is defined by constructor.
            return $this->out_of_context;
        }

        $this->doCheck();

        return $this->out_of_context ?? false;
    }

    public function isValidated(): bool
    {
        $this->doCheck();

        return true === $this->validated;
    }
}
