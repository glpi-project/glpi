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
 * @since 9.5.0
 */
class MemoryLimit extends AbstractRequirement
{
    /**
     * Minimal allocated memory size.
     *
     * @var int
     */
    private $min;

    /**
     * @param int $min  Minimal allocated memory.
     */
    public function __construct(int $min)
    {
        $this->title = __('Allocated memory');
        $this->min = $min;
    }

    protected function check()
    {
        $limit = \Toolbox::getMemoryLimit();

       /*
        * $limit can be:
        *  -1 : unlimited
        *  >0 : allocated bytes
        */
        if ($limit == -1 || $limit >= $this->min) {
            $this->validated = true;
            $this->validation_messages[] = $limit > 0
            ? sprintf(__('Allocated memory is sufficient.'), \Toolbox::getSize($this->min))
            : __('Allocated memory is unlimited.');
        } else {
            $this->validated = false;
            $this->validation_messages[] = sprintf(__('%1$s: %2$s'), __('Allocated memory'), \Toolbox::getSize($limit));
            $this->validation_messages[] = sprintf(__('A minimum of %s is commonly required for GLPI.'), \Toolbox::getSize($this->min));
            $this->validation_messages[] = __('Try increasing the memory_limit parameter in the php.ini file.');
        }
    }
}
