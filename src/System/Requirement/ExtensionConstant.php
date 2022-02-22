<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
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
     * @param string $name Constant name.
     * @param bool $optional Indicated if extension is optional.
     * @param string $description Constant description.
     * @param string $failure_message Failure message.
     */
    public function __construct(string $title, string $name, bool $optional = false, string $description = '')
    {
        $this->title = $title;
        $this->name = $name;
        $this->optional = $optional;
        $this->description = $description;
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
