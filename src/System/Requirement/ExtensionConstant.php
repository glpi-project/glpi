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
 * @since 10.0.0
 */
class ExtensionConstant extends AbstractRequirement
{
    /**
     * Required constant name.
     *
     * @var string
     */
    private $name;

    /**
     * @param string $title Constant title.
     * @param string $name Constant name.
     * @param bool $optional Indicated if extension is optional.
     * @param string $description Constant description.
     */
    public function __construct(string $title, string $name, bool $optional = false, string $description = '')
    {
        parent::__construct(
            $title,
            $description,
            $optional
        );

        $this->name = $name;
    }

    protected function check()
    {
        $this->validated = defined($this->name);
        if ($this->validated) {
            $this->validation_messages = [
                sprintf(__('The constant %s is present.'), $this->name)
            ];
        } else if ($this->optional) {
            $this->validation_messages = [
                sprintf(__('The constant %s is not present.'), $this->name)
            ];
        } else {
            $this->validation_messages = [
                sprintf(__('The constant %s is missing.'), $this->name)
            ];
        }
    }
}
