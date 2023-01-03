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
class Extension extends AbstractRequirement
{
    /**
     * Required extension name.
     *
     * @var string
     */
    protected $name;

    /**
     * @param string      $name         Required extension name.
     * @param bool        $optional     Indicate if extension is optional.
     * @param string|null $description  Describe usage of the extension.
     */
    public function __construct(string $name, bool $optional = false, ?string $description = null)
    {
        $this->title = sprintf(__('%s extension'), $name);
        $this->name = $name;
        $this->optional = $optional;
        $this->description = $description;
    }

    protected function check()
    {
        $this->validated = extension_loaded($this->name);
        $this->buildValidationMessage();
    }

    /**
     * Defines the validation message based on self properties.
     *
     * @return void
     */
    protected function buildValidationMessage()
    {
        if ($this->validated) {
            $this->validation_messages[] = sprintf(__('%s extension is installed.'), $this->name);
        } else if ($this->optional) {
            $this->validation_messages[] = sprintf(__('%s extension is not present.'), $this->name);
        } else {
            $this->validation_messages[] = sprintf(__('%s extension is missing.'), $this->name);
        }
    }
}
