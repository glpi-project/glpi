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

namespace Glpi\Tests;

use Glpi\DBAL\JsonFieldInterface;

/**
 * Helper class to ease form creation using DbTestCase::createForm()
 */
class FormBuilder
{
    /**
     * Form name
     */
    protected string $name;

    /**
     * Form entity
     */
    protected int $entities_id;

    /**
     * Entity recursion
     */
    protected int $is_recursive;

    /**
     * Is this form enabled ?
     */
    protected bool $is_active;

    /**
     * Form header
     */
    protected string $header;

    /**
     * Is this form a draft ?
     */
    protected bool $is_draft;

    /**
     * Form sections
     */
    protected array $sections;

    /**
     * Form destinations
     */
    protected array $destinations;

    /**
     * Form access control restrictions
     */
    protected array $access_control;

    /**
     * Constructor
     *
     * @param string $name Form name
     */
    public function __construct(string $name = "Test form")
    {
        $this->name = $name;
        $this->entities_id = getItemByTypeName('Entity', '_test_root_entity', true);
        $this->is_recursive = true;
        $this->is_active = true;
        $this->header = "";
        $this->is_draft = false;
        $this->sections = [];
        $this->destinations = [];
        $this->access_control = [];
    }

    /**
     * Get form name
     *
     * @return string Form name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set form name
     *
     * @param string Form name
     *
     * @return self To allow chain calls
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get form entity
     *
     * @return int Form entity
     */
    public function getEntitiesId(): int
    {
        return $this->entities_id;
    }

    /**
     * Set form entity
     *
     * @param int Form entity
     *
     * @return self To allow chain calls
     */
    public function setEntitiesId(int $entities_id): self
    {
        $this->entities_id = $entities_id;
        return $this;
    }

    /**
     * Get entity recursion
     *
     * @return int Entity recursion
     */
    public function getIsRecursive(): int
    {
        return $this->is_recursive;
    }

    /**
     * Set entity recursion
     *
     * @param int Entity recursion
     *
     * @return self To allow chain calls
     */
    public function setIsRecursive(int $is_recursive): self
    {
        $this->is_recursive = $is_recursive;
        return $this;
    }

    /**
     * Get form status
     *
     * @return bool Form status
     */
    public function getIsActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Set form status
     *
     * @param bool Form status
     *
     * @return self To allow chain calls
     */
    public function setIsActive(bool $is_active): self
    {
        $this->is_active = $is_active;
        return $this;
    }

    /**
     * Get form header
     *
     * @return string Form header
     */
    public function getHeader(): string
    {
        return $this->header;
    }

    /**
     * Set form header
     *
     * @param string Form header
     *
     * @return self To allow chain calls
     */
    public function setHeader(string $header): self
    {
        $this->header = $header;
        return $this;
    }

    /**
     * Get form draft status
     *
     * @return bool Form draft status
     */
    public function getIsDraft(): bool
    {
        return $this->is_draft;
    }

    /**
     * Set form draft status
     *
     * @param bool Form draft status
     *
     * @return self To allow chain calls
     */
    public function setIsDraft(bool $is_draft): self
    {
        $this->is_draft = $is_draft;
        return $this;
    }

    /**
     * Get form sections
     *
     * @return array Form sections
     */
    public function getSections(): array
    {
        return $this->sections;
    }

    /**
     * Add a question to the form
     *
     * @param string $name        Question name
     * @param string $description Question description
     *
     * @return self To allow chain calls
     */
    public function addSection(string $name, string $description = ""): self
    {
        $this->sections[] = [
            'name'        => $name,
            'description' => $description,
            'questions'   => [],
            'comments'    => [],
        ];
        return $this;
    }

    /**
     * Add a question to the form
     *
     * @param string $name          Question name
     * @param string $type          Question type
     * @param mixed  $default_value Question default value
     * @param string $extra_data    Question extra data
     * @param string $description   Question description
     * @param bool   $is_mandatory  Is the question mandatory ?
     *
     * @return self To allow chain calls
     */
    public function addQuestion(
        string $name,
        string $type,
        mixed $default_value = "",
        string $extra_data = "",
        string $description = "",
        bool $is_mandatory = false,
    ): self {
        // Add first section if missing
        if (empty($this->sections)) {
            $this->addSection("First section");
        }

        // Add question into last section
        $this->sections[count($this->sections) - 1]['questions'][] = [
            'name'          => $name,
            'type'          => $type,
            'default_value' => $default_value,
            'extra_data'    => $extra_data,
            'description'   => $description,
            'is_mandatory'  => $is_mandatory,
        ];

        return $this;
    }

    public function addComment(
        string $name,
        string $description = "",
    ): self {
        // Add first section if missing
        if (empty($this->sections)) {
            $this->addSection("First section");
        }

        // Add question into last section
        $this->sections[count($this->sections) - 1]['comments'][] = [
            'name'          => $name,
            'description'   => $description,
        ];

        return $this;
    }

    /**
     * Get form destinations
     *
     * @return array Form destinations
     */
    public function getDestinations(): array
    {
        return $this->destinations;
    }

    /**
     * Add a destination to the form
     *
     * @param string $itemtype Destination itemtype
     * @param string $name     Destination name
     * @param array  $config   Config values
     *
     * @return self To allow chain calls
     */
    public function addDestination(
        string $itemtype,
        string $name,
        array $config = []
    ): self {
        // If first destination of the given itemtype, init its key
        if (!isset($this->destinations[$itemtype])) {
            $this->destinations[$itemtype] = [];
        }

        $this->destinations[$itemtype][] = [
            'name'   => $name,
            'config' => $config,
        ];
        return $this;
    }

    /**
     * Get form access controls restrictions.
     *
     * @return array
     */
    public function getAccessControls(): array
    {
        return $this->access_control;
    }

    /**
     * Add a destination to the form
     *
     * @param string                  $strategy
     * @param JsonFieldInterface $values
     *
     * @return self To allow chain calls
     */
    public function addAccessControl(
        string $strategy,
        JsonFieldInterface $config
    ): self {
        $this->access_control[$strategy] = $config;
        return $this;
    }
}
