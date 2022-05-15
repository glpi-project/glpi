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
class ExtensionClass extends Extension
{
    /**
     * Required class or interface name.
     *
     * @var string
     */
    private $class_name;

    /**
     * @param string $name        Extension name.
     * @param string $class_name  Required class or interface name.
     * @param bool $optional      Indicated if extension is optional.
     */
    public function __construct(string $name, string $class_name, bool $optional = false)
    {
        parent::__construct($name, $optional);
        $this->class_name = $class_name;
    }

    protected function check()
    {
        $this->validated = class_exists($this->class_name) || interface_exists($this->class_name);
        $this->buildValidationMessage();
    }
}
